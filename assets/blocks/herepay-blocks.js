/**
 * Herepay payment method for the WooCommerce block checkout.
 * Renders the same bank picker as the classic checkout (herepay-bank-picker.css).
 */
(function () {
    'use strict';

    var registerPaymentMethod = wc.wcBlocksRegistry.registerPaymentMethod;
    var el = wp.element.createElement;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var useRef = wp.element.useRef;
    var __ = wp.i18n.__;
    var sprintf = wp.i18n.sprintf;
    var _n = wp.i18n._n;
    var decodeEntities = wp.htmlEntities.decodeEntities;

    var params = window.herepayBlocksParams || {};
    var groups = Array.isArray(params.bankGroups) ? params.bankGroups : [];
    var title = decodeEntities(params.title || __('Herepay Payment Gateway', 'herepay-for-woocommerce'));

    function svg(paths, extra) {
        return el('svg', Object.assign({
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2,
            strokeLinecap: 'round', strokeLinejoin: 'round', 'aria-hidden': 'true'
        }, extra || {}), paths);
    }

    var chevronIcon = svg(el('path', { d: 'm6 9 6 6 6-6' }), { className: 'hp-chevron' });
    var searchIcon = svg([el('circle', { key: 'c', cx: 11, cy: 11, r: 8 }), el('path', { key: 'p', d: 'm21 21-4.3-4.3' })]);
    var checkIcon = svg(el('path', { d: 'm7 12.5 3 3 7-7' }), { strokeWidth: 3 });

    function onActivate(fn) {
        return {
            onClick: fn,
            onKeyDown: function (e) {
                if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
                    e.preventDefault();
                    fn(e);
                }
            }
        };
    }

    function BankLogo(props) {
        var _s = useState(false), failed = _s[0], setFailed = _s[1];
        var hasImg = !!props.src && !failed;
        var initial = (props.name || '?').charAt(0).toUpperCase();
        return el('span', { className: 'hp-tile__logo' + (hasImg ? ' has-img' : ''), 'aria-hidden': 'true' },
            initial,
            hasImg && el('img', {
                className: 'hp-tile__img', src: props.src, alt: '', loading: 'lazy',
                onError: function () { setFailed(true); }
            })
        );
    }

    function ChosenLogo(props) {
        var _s = useState(false), failed = _s[0], setFailed = _s[1];
        return failed ? null : el('img', {
            className: 'hp-group__chosen-logo', src: props.src, alt: '',
            onError: function () { setFailed(true); }
        });
    }

    function Notice(props) {
        return el('div', { className: 'hp-notice hp-notice--' + props.type },
            el('strong', null, props.title), props.text);
    }

    var Content = function (props) {
        var eventRegistration = props.eventRegistration || {};
        var emitResponse = props.emitResponse || {};

        // open: method whose bank list is expanded (null = all collapsed)
        // bank: chosen bank { method, prefix, name, logo } or null
        var _o = useState(groups.length ? groups[0].method : null), open = _o[0], setOpen = _o[1];
        var _b = useState(null), bank = _b[0], setBank = _b[1];
        var _q = useState(''), query = _q[0], setQuery = _q[1];
        var _e = useState(''), error = _e[0], setError = _e[1];
        var method = bank ? bank.method : '';

        // Hand the chosen bank to WooCommerce when "Place order" is pressed.
        useEffect(function () {
            var register = eventRegistration.onPaymentSetup || eventRegistration.onPaymentProcessing;
            if (typeof register !== 'function') {
                return undefined;
            }
            var types = emitResponse.responseTypes || { SUCCESS: 'success', ERROR: 'error' };
            return register(function () {
                if (!bank) {
                    var msg = __('Please choose your bank to continue.', 'herepay-for-woocommerce');
                    setError(msg);
                    return { type: types.ERROR, message: msg, messageContext: emitResponse.noticeContexts && emitResponse.noticeContexts.PAYMENTS };
                }
                return {
                    type: types.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            herepay_bank_prefix: bank.prefix,
                            herepay_payment_method: method,
                            herepay_bank_name: bank.name
                        }
                    }
                };
            });
        }, [bank, method, eventRegistration.onPaymentSetup, eventRegistration.onPaymentProcessing]);

        var description = params.description
            ? el('p', { className: 'hp-desc', style: { margin: '0 0 4px' } }, decodeEntities(params.description))
            : null;

        if (!params.configured) {
            return el('div', null, description, el(Notice, {
                type: 'warn',
                title: __('Payment gateway is not fully configured.', 'herepay-for-woocommerce'),
                text: __('Please contact the store administrator to configure Herepay payment settings.', 'herepay-for-woocommerce')
            }));
        }
        if (!groups.length) {
            return el('div', null, description, el(Notice, {
                type: 'error',
                title: __('Unable to load payment channels.', 'herepay-for-woocommerce'),
                text: __('Please try again later or contact support if the problem persists.', 'herepay-for-woocommerce')
            }));
        }

        function toggleGroup(g) {
            if (open === g.method) {
                // Collapse again only if there is a chosen bank to show in the header.
                if (bank && bank.method === g.method) {
                    setOpen(null);
                }
            } else {
                setOpen(g.method);
                setQuery('');
            }
        }

        // Choosing a bank collapses its list so the checkout stays compact.
        function chooseBank(g, b) {
            setBank({ method: g.method, prefix: b.prefix, name: b.name, logo: b.logo });
            setOpen(null);
            setQuery('');
            setError('');
        }

        var q = query.trim().toLowerCase();
        var active = bank ? bank.method : open;

        var groupEls = groups.map(function (g, i) {
            var isOpen = g.method === open;
            var chosen = bank && bank.method === g.method ? bank : null;
            var bodyId = 'hp-blk-group-' + i;
            var banks = isOpen && q
                ? g.banks.filter(function (b) { return (b.name + ' ' + b.prefix).toLowerCase().indexOf(q) !== -1; })
                : g.banks;

            return el('div', {
                key: g.method,
                className: 'hp-group' + (isOpen ? ' is-open' : '') + (g.method === active ? ' is-active' : '') + (chosen ? ' has-chosen' : '')
            },
                el('div', Object.assign({
                    className: 'hp-group__head', role: 'radio', tabIndex: 0,
                    'aria-checked': g.method === active ? 'true' : 'false', 'aria-expanded': isOpen ? 'true' : 'false', 'aria-controls': bodyId
                }, onActivate(function () { toggleGroup(g); })),
                    el('span', { className: 'hp-radio', 'aria-hidden': 'true' }),
                    el('span', { className: 'hp-group__text' },
                        el('span', { className: 'hp-group__title' }, g.method),
                        g.subtitle && el('span', { className: 'hp-group__sub' }, g.subtitle),
                        el('span', { className: 'hp-group__chosen' },
                            chosen && chosen.logo && el(ChosenLogo, { key: chosen.prefix, src: chosen.logo }),
                            chosen && el('span', { className: 'hp-group__chosen-name' }, chosen.name)
                        )
                    ),
                    el('span', { className: 'hp-group__change' }, __('Change', 'herepay-for-woocommerce')),
                    chevronIcon
                ),
                isOpen && el('div', { className: 'hp-group__body', id: bodyId },
                    el('label', { className: 'hp-search' },
                        searchIcon,
                        el('input', {
                            type: 'search', className: 'hp-search__input', autoComplete: 'off',
                            value: query,
                            placeholder: sprintf(_n('Search %d bank', 'Search %d banks', g.banks.length, 'herepay-for-woocommerce'), g.banks.length),
                            'aria-label': __('Search banks', 'herepay-for-woocommerce'),
                            onChange: function (e) { setQuery(e.target.value); },
                            onKeyDown: function (e) {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    if (banks.length) { chooseBank(g, banks[0]); }
                                }
                            }
                        })
                    ),
                    el('div', { className: 'hp-tiles', role: 'radiogroup', 'aria-label': g.method },
                        banks.map(function (b) {
                            var on = !!chosen && chosen.prefix === b.prefix;
                            return el('div', Object.assign({
                                key: b.prefix, className: 'hp-tile' + (on ? ' is-selected' : ''),
                                role: 'radio', tabIndex: 0, 'aria-checked': on ? 'true' : 'false', title: b.name
                            }, onActivate(function () { chooseBank(g, b); })),
                                el(BankLogo, { src: b.logo, name: b.name }),
                                el('span', { className: 'hp-tile__name' }, b.name),
                                el('span', { className: 'hp-tile__check', 'aria-hidden': 'true' }, checkIcon)
                            );
                        })
                    ),
                    !banks.length && el('div', { className: 'hp-empty' }, __('No bank matches your search.', 'herepay-for-woocommerce'))
                )
            );
        });

        return el('div', null,
            description,
            el('div', { className: 'hp-picker' + (error ? ' has-error' : '') },
                el('div', { className: 'hp-head' },
                    el('span', { className: 'hp-head__title' }, __('Choose your bank', 'herepay-for-woocommerce')),
                    el('span', { className: 'hp-secured' },
                        __('Secured by', 'herepay-for-woocommerce'), ' ',
                        params.logoUrl && el('img', { className: 'hp-secured__logo', src: params.logoUrl, alt: 'Herepay', width: 48, height: 12 })
                    )
                ),
                el('div', { className: 'hp-groups', role: 'radiogroup', 'aria-label': __('Payment method', 'herepay-for-woocommerce') }, groupEls),
                error && el('div', { className: 'hp-error', role: 'alert' }, error)
            )
        );
    };

    var Label = function (props) {
        // Logo only; the title stays available to screen readers.
        if (!params.logoUrl) {
            return el(props.components.PaymentMethodLabel, { text: title });
        }
        return el('span', { className: 'hp-block-label' },
            el('img', { className: 'hp-block-label__logo', src: params.logoUrl, alt: '' }),
            el('span', { className: 'hp-sr-only' }, title)
        );
    };

    registerPaymentMethod({
        name: 'herepay_payment_gateway',
        label: el(Label, null),
        content: el(Content, null),
        edit: el(Content, null),
        canMakePayment: function () { return true; },
        ariaLabel: title,
        supports: { features: params.supports || ['products'] }
    });
})();
