/**
 * Herepay classic checkout: bank picker (accordion + tiles).
 *
 * WooCommerce re-renders the payment section on every `updated_checkout`,
 * so all handlers are delegated and the selection is kept in `state` and
 * re-applied after each refresh.
 */
jQuery(function ($) {
    'use strict';

    var params = window.herepay_params || {};
    var i18n = params.i18n || {};
    // open: method whose bank list is expanded (null = all collapsed, undefined = not decided yet)
    // bank: the chosen bank { method, prefix, name, logo } or null
    var state = { open: undefined, bank: null };

    function picker() {
        return $('#herepay-payment-form.hp-picker');
    }

    function markBroken(img) {
        img.parentNode.classList.remove('has-img');
        img.classList.add('is-broken');
    }

    // Logos that already failed before this script loaded never fire another error event.
    function checkLogos($picker) {
        $picker.find('img.hp-tile__img:not(.is-broken)').each(function () {
            if (this.complete && this.naturalWidth === 0) {
                markBroken(this);
            }
        });
    }

    function render() {
        var $picker = picker();
        if (!$picker.length) {
            return;
        }
        checkLogos($picker);

        var $groups = $picker.find('.hp-group');
        var methods = $groups.map(function () { return $(this).data('method'); }).get();

        // Chosen bank no longer offered (channels refreshed): forget it.
        if (state.bank && !$groups.filter(function () { return $(this).data('method') === state.bank.method; })
            .find('.hp-tile').filter(function () { return $(this).data('prefix') === state.bank.prefix; }).length) {
            state.bank = null;
            state.open = undefined;
        }
        if (state.open === undefined || (state.open !== null && methods.indexOf(state.open) === -1)) {
            state.open = state.bank ? null : (methods[0] || null);
        }

        var active = state.bank ? state.bank.method : state.open;

        $groups.each(function () {
            var $g = $(this);
            var m = $g.data('method');
            var isOpen = m === state.open;
            var chosen = state.bank && state.bank.method === m ? state.bank : null;

            $g.toggleClass('is-open', isOpen)
                .toggleClass('is-active', m === active)
                .toggleClass('has-chosen', !!chosen);
            $g.children('.hp-group__head').attr({
                'aria-checked': m === active ? 'true' : 'false',
                'aria-expanded': isOpen ? 'true' : 'false'
            });

            var $chosen = $g.find('.hp-group__chosen').empty();
            if (chosen) {
                if (chosen.logo) {
                    $chosen.append($('<img/>', { 'class': 'hp-group__chosen-logo', src: chosen.logo, alt: '' })
                        .on('error', function () { $(this).remove(); }));
                }
                $chosen.append($('<span/>', { 'class': 'hp-group__chosen-name' }).text(chosen.name));
            }

            $g.find('.hp-tile').each(function () {
                var $t = $(this);
                var on = !!chosen && $t.data('prefix') === chosen.prefix;
                $t.toggleClass('is-selected', on).attr('aria-checked', on ? 'true' : 'false');
            });
        });

        $picker.find('#herepay_bank_prefix').val(state.bank ? state.bank.prefix : '');
        $picker.find('#herepay_payment_method').val(state.bank ? state.bank.method : '');
        $picker.find('#herepay_bank_name').val(state.bank ? state.bank.name : '');

        if (state.bank) {
            clearError();
        }
    }

    function showError(msg) {
        var $picker = picker();
        $picker.addClass('has-error').find('.hp-error').text(msg).prop('hidden', false);
    }

    function clearError() {
        picker().removeClass('has-error').find('.hp-error').prop('hidden', true).text('');
    }

    // Choosing a bank collapses its list so the checkout stays compact.
    function selectTile($tile) {
        state.bank = {
            method: $tile.closest('.hp-group').data('method'),
            prefix: $tile.data('prefix'),
            name: String($tile.data('name')),
            logo: $tile.data('logo') || ''
        };
        state.open = null;
        render();
    }

    function filter($input) {
        var q = $.trim($input.val()).toLowerCase();
        var $body = $input.closest('.hp-group__body');
        var shown = 0;
        $body.find('.hp-tile').each(function () {
            var $t = $(this);
            var hay = (String($t.data('name')) + ' ' + String($t.data('prefix'))).toLowerCase();
            var match = !q || hay.indexOf(q) !== -1;
            $t.prop('hidden', !match);
            if (match) {
                shown++;
            }
        });
        $body.find('.hp-empty').prop('hidden', shown !== 0);
    }

    function isActivate(e) {
        return e.type === 'click' || e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar';
    }

    $(document.body)
        .on('click keydown', '.hp-picker .hp-group__head', function (e) {
            if (!isActivate(e)) {
                return;
            }
            e.preventDefault();
            var m = $(this).closest('.hp-group').data('method');
            if (state.open === m) {
                // Collapse again only if there is a chosen bank to show in the header.
                if (state.bank && state.bank.method === m) {
                    state.open = null;
                }
            } else {
                state.open = m;
            }
            render();
        })
        .on('click keydown', '.hp-picker .hp-tile', function (e) {
            if (!isActivate(e)) {
                return;
            }
            e.preventDefault();
            selectTile($(this));
        })
        .on('input', '.hp-picker .hp-search__input', function () {
            filter($(this));
        })
        .on('keydown', '.hp-picker .hp-search__input', function (e) {
            // Enter in the search box must not submit the whole checkout form.
            if (e.key === 'Enter') {
                e.preventDefault();
                var $first = $(this).closest('.hp-group__body').find('.hp-tile:not([hidden])').first();
                if ($first.length) {
                    selectTile($first);
                }
            }
        })
        .on('updated_checkout', render)
        .on('checkout_error', function () {
            $('.woocommerce-checkout-payment').unblock && $('.woocommerce-checkout-payment').unblock();
        });

    // Bank logo failed to load: fall back to the bank's initial letter.
    document.addEventListener('error', function (e) {
        var img = e.target;
        // Classic picker only (the block picker is React-managed and handles this itself).
        // Hide rather than remove so no other script loses track of the node.
        if (img && img.classList && img.classList.contains('hp-tile__img') && img.closest('#herepay-payment-form')) {
            markBroken(img);
        }
    }, true);

    // Block "Place order" until a bank is chosen.
    $('form.checkout').on('checkout_place_order_herepay_payment_gateway', function () {
        if (!state.prefix) {
            showError(i18n.choose_bank || 'Please choose your bank to continue.');
            var $p = picker();
            if ($p.length) {
                $('html, body').animate({ scrollTop: $p.offset().top - 100 }, 300);
            }
            return false;
        }
        return true;
    });

    render();
});
