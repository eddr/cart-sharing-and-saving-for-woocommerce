/**
 * CSAS Functions including cart sharing functionality (desktop + mobile)
 * 
 */

const { __, _x, _n, _nx, sprintf } = window.wp.i18n;

let csas = ( function ( $ ) {

    let events = {};

    // ------------------------------------------------------------------------
    // Utilities
    // ------------------------------------------------------------------------

    /**
     * Encode a string for use in URLs.
     *
     * @param {string} p_str String to encode.
     * @returns {string} Encoded string.
     */
    function encode( p_str )
    {
        return encodeURIComponent(p_str || "");
    }

    /**
     * Retrieve the current language two-letter code from the frontend.
     *
     * This function attempts to detect the active language in WordPress
     * using multiple possible sources:
     *  - WPML (via global ICL_LANGUAGE_CODE)
     *  - Polylang (via global pll_current_language)
     *  - <html lang="..."> attribute (default WordPress behavior)
     *
     * @returns {string} Two-letter language code (e.g. "en", "fr", "de").
     */
    function get_current_lang_code()
    {
        let _lang = 'en';

        if ( typeof ICL_LANGUAGE_CODE !== 'undefined' )
        {
            _lang = ICL_LANGUAGE_CODE;
        }
        else if ( typeof pll_current_language !== 'undefined' )
        {
            _lang = pll_current_language;
        }
        else if ( document.documentElement.lang )
        {
            _lang = document.documentElement.lang;
        }

        return _lang.split( '-' )[0];
    }


    /**
     * No operation function.
     *
     * @returns {void}
     */
    function noop()
    {
    }

    /**
     * Convert possibly-HTML content to plain text (for safe sharing).
     *
     * @param {string} p_html Input HTML or text.
     * @returns {string} Plain text string.
     */
    function html_to_text( p_html )
    {
        let _div = document.createElement('div');
        _div.innerHTML = p_html || '';
        let _text = _div.textContent || _div.innerText || '';
        _text = _text.replace(/\s+/g, ' ').trim();
        return _text;
    }

    /**
     * Determine if current host is a local dev host.
     *
     * @returns {boolean} True if localhost-like host.
     */
    function is_local_host()
    {
        let _h = location.hostname;
        return _h === 'localhost' || _h === '127.0.0.1' || _h === '0.0.0.0' || _h === '::1';
    }

    /**
     * Check if current context should be considered secure for share UI visibility.
     * Allows HTTP when explicitly enabled for testing or on localhost.
     *
     * @returns {boolean} True if considered secure for showing native share button.
     */
    function is_share_secure_context()
    {
        let _https = location.protocol === 'https:';
        let _flag  = !!( window.csas_data && csas_data.allow_insecure_share );
        return _https || _flag || is_local_host();
    }

    /**
     * Check if Web Share API is available (independent of protocol).
     *
     * @returns {boolean} True if navigator.share exists.
     */
    function is_web_share_available()
    {
        return !!( navigator && navigator.share );
    }

    /**
     * Open a popup window centered on screen.
     *
     * @param {string} p_url URL to open.
     * @param {number} p_w   Width of popup.
     * @param {number} p_h   Height of popup.
     * @returns {void}
     */
    function open_popup( p_url, p_w, p_h )
    {
        let _y = ( window.top.outerHeight / 2 ) + window.top.screenY - ( p_h / 2 );
        let _x = ( window.top.outerWidth / 2 ) + window.top.screenX - ( p_w / 2 );

        window.open(
            p_url,
            '_blank',
            'popup=yes,toolbar=0,location=0,status=0,menubar=0,scrollbars=1,resizable=1,width=' + p_w + ',height=' + p_h + ',top=' + _y + ',left=' + _x
        );
    }

    /**
     * Return the first non-empty value between two params.
     *
     * @param {*} p_val Potential value.
     * @param {*} p_fallback Fallback value.
     * @returns {*} Preferred value.
     */
    function prefer( p_val, p_fallback )
    {
        return ( typeof p_val !== 'undefined' && p_val !== null && p_val !== '' ) ? p_val : p_fallback;
    }

    /**
     * Normalize URL by collapsing accidental duplicate slashes (not after scheme).
     *
     * @param {string} p_url URL to normalize.
     * @returns {string} Normalized URL.
     */
    function normalize_url( p_url )
    {
        if ( !p_url ) {
            return '';
        }
        return p_url.replace(/([^:])\/\/+/g, '$1/');
    }

    /**
     * Extract share URL from server response or DOM.
     * Canonical field: p_data.data.share_cart_url
     *
     * @param {Object} p_data Response data.
     * @returns {string} URL to share.
     */
    function extract_share_url( p_data )
    {
        let _from_resp = null;

        if ( p_data && p_data.data && p_data.data.share_cart_url ) {
            _from_resp = p_data.data.share_cart_url;
        } else if ( p_data && ( p_data.share_cart_url || p_data.share_url || p_data.url ) ) {
            _from_resp = p_data.share_cart_url || p_data.share_url || p_data.url;
        } else if ( p_data && p_data.data && ( p_data.data.share_url || p_data.data.url ) ) {
            _from_resp = p_data.data.share_url || p_data.data.url;
        }

        if ( _from_resp ) {
            return normalize_url(_from_resp);
        }

        let _el = document.querySelector('.shared-cart-link');
        let _fallback = _el ? _el.href : location.href;
        return normalize_url(_fallback);
    }

    // ------------------------------------------------------------------------
    // Events
    // ------------------------------------------------------------------------

    /**
     * Create all custom events for csas.
     *
     * @returns {void}
     */
    function create_events()
    {
        csas.events = {};
        csas.events.save    = new CustomEvent('cart_saved');
        csas.events.shared  = new CustomEvent('cart_shared');
        csas.events.deleted = new CustomEvent('cart_deleted');
        csas.events.load    = new CustomEvent('cart_loaded');
    }


    /**
     * Output current user carts.
     *
     * Checks localStorage for cached carts HTML. 
     * If `csas_carts_touched` is "0", the cached HTML is used directly.
     * Otherwise, fetches fresh data from the API, updates the placeholder,
     * and saves new values into localStorage:
     *  - csas_carts_html      : HTML markup of the carts
     *  - csas_carts_timestamp : UNIX timestamp in milliseconds
     *  - csas_carts_touched   : "1" when fetched, "0" when cache should be used
     *
     * @returns {void}
     */
    function output_current_user_carts() 
    {
        const _placeholder = document.querySelector( '.csas-carts-placeholder' );

        if ( !_placeholder ) {
            return;
        }

        const _touched = localStorage.getItem( 'csas_carts_touched' );
        const _cached_html = localStorage.getItem( 'csas_carts_html' );

        if ( _touched === '0' && _cached_html ) {
            _placeholder.outerHTML = _cached_html;
            hook_events();
            return;
        }

        let _lang = get_current_lang_code();
        let _args = {
            path: '/csas/v1/carts-html?lang=' + _lang
        };

        _placeholder.innerHTML = '<span class="csas-loader"></span>';

        wp.apiFetch( _args )
        .then( ( response ) => {
            if ( response.success ) {

                if ( _placeholder ) {
                    _placeholder.outerHTML = response.data.html;
                }

                localStorage.setItem( 'csas_carts_html', response.data.html );
                localStorage.setItem( 'csas_carts_timestamp', Date.now().toString() );
                localStorage.setItem( 'csas_carts_touched', '0' );

                hook_events();
            }
        }).finally( () => {

            let _placeholder = document.querySelector( '.csas-carts-placeholder' );
            if ( _placeholder ) {
                _placeholder.innerHTML = '';
            }
        });
    }

    /**
     * Displays share url
     * 
     * @returns {void}
     */
    function share_update( p_data ) {

        let _csas_cart_hash = p_data.data.cart_hash;
        csas_data.cart_data.wc_cart_hash = Cookies.get('woocommerce_cart_hash');
        
        let _share_url = csas_data.urls.share_page_url + _csas_cart_hash;

        if ( _share_url ) {

            let _msg = ( window.csas_data 
                        && csas_data.texts 
                        && csas_data.texts.cart_shared ) 
                        || _default_raw_msg;

            _msg = _msg.replace( '{copy_link_button}', get_copy_button() );
            share_cart_success( {
                msg: '',
                data: { share_cart_url: _share_url }
            } );

            return; // important: don’t continue to XHR
        }
    }

    /**
     * Hook up event listeners for cart operations.
     *
     * @returns {void}
     */
    function hook_events()
    {

        let _hooks = ['save', 'share', 'delete', 'load'];
        let _invalidating_hooks = ['save', 'delete'];
        
        let _prefix = 'csas-';
        let _default_raw_msg = '';

        _hooks.forEach(op => {

            $(`.${_prefix}${op}-cart`).on('click', function ( e ) {

                e.preventDefault();

                let _csas_cart_hash = csas.get_csas_cart_hash( e.currentTarget ) || csas_data.cart_data.current_csas_hash;

                if ( op === 'share' ) {

                    let _revalidate = false;
                    if ( csas_data.cart_data.wc_cart_hash !== Cookies.get('woocommerce_cart_hash') ) {

                        _revalidate = true;
                    }

                    console.log( _revalidate );
                    if ( _revalidate ) {

                        console.log( 'revalidating' );
                        ajax_call( op + '_cart', { csas_hash: _csas_cart_hash, revalidate: _revalidate ? '1' : '0' }, csas.share_update );
                        localStorage.setItem( 'csas_carts_touched', '1' );
                       
                    }
                    else {

                        csas.share_update( { data: { cart_hash: _csas_cart_hash } });
                    }

                    return;
                    
                }
                
                if ( _invalidating_hooks.includes( op ) ) {

                    localStorage.setItem( 'csas_carts_touched', '1' );
                }
                
                ajax_call( op + '_cart', { csas_hash: _csas_cart_hash }, eval( op + '_cart_success' ) );
            });
        });

    }

    /**
     * Hook copy link button for legacy usage.
     *
     * @returns {void}
     */
    function hook_copy_button()
    {
        let _btn = document.querySelector('.csas-copy-link');

        if ( _btn ) {
            _btn.addEventListener('click', function () {
                let _a = document.querySelector('.shared-cart-link');
                if ( _a && _a.href ) {
                    navigator.clipboard.writeText(_a.href)
                        .then(function () {
                            alert('Link copied'); })
                        .catch(function () {
                            alert('Copy failed.'); });
                }
            });
        }
    }

    /**
     * Get cart hash from DOM element.
     *
     * @param {HTMLElement} p_el Element containing dataset.
     * @returns {string} Cart key.
     */
    function get_csas_cart_hash( p_el )
    {
        return p_el.dataset.cartKey;
    }

    /**
     * Return HTML for copy link button (legacy helper).
     *
     * @returns {string} HTML markup.
     */
    function get_copy_button()
    {
        let _clipboard_url = csas_data.imgs.clipboard;
        let _html = '<img src="' + _clipboard_url + '" class="csas-copy-link" alt="Copy link" role="button" tabindex="0"/>';

        return _html;
    }

    // ------------------------------------------------------------------------
    // AJAX + Dialog helpers
    // ------------------------------------------------------------------------

    /**
     * Perform AJAX call for cart operation.
     *
     * @param {string}   p_action           Action type.
     * @param {Object}   p_data             Data payload.
     * @param {Function} p_success_callback Success callback.
     * @returns {void}
     */
    function ajax_call( p_action, p_data, p_success_callback )
    {
        let _data = Object.assign(
            {
                'action': 'csas_cart_ops',
                'op': p_action,
                'nonce': csas_data.csas_ajax_object.cart_ops_nonce
            },
            p_data
        );

        let _args = {
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: _data,
            success: p_success_callback || noop,
            error: function () {
                alert('An error occurred.');
            }
        };
        $.ajax( _args );
    }

    /**
     * Build args for xdialog with a body and title.
     *
     * @param {string} p_title Title for dialog.
     * @param {string} p_body  HTML body.
     * @returns {Object} Dialog args.
     */
    function get_dialog_buttons_args( p_title, p_body )
    {
        let _dialog_args = {
            title: p_title,
            buttons: { 'ok': { text: window.csas_data.static_texts.close, clazz: 'wp-element-button button xd-ok' } },
            extraClass: 'csas-message-box',
            body: p_body
        };
        return _dialog_args;
    }

    // ------------------------------------------------------------------------
    // Icons (external logos with inline fallback)
    // ------------------------------------------------------------------------

    /**
     * Get icon markup by key, preferring external SVG/PNG URLs from csas_data.logos.
     * If a URL is not provided, falls back to a tiny inline SVG so the UI still works.
     *
     * Expected keys: native, copy, whatsapp, telegram, facebook, x, messenger, email
     *
     * @param {string} p_key   Icon key.
     * @param {string} p_label Accessible label (unused visually; icon is decorative).
     * @returns {string} HTML markup for the icon.
     */
    function get_icon_markup( p_key, p_label )
    {
        let _logos = ( window.csas_data && csas_data.logos ) || {};
        let _url   = _logos[ p_key ];

        if ( _url ) {
            // Decorative image; accessible name comes from the adjacent label text.
            return '<img src="' + _url + '" alt="" aria-hidden="true" width="18" height="18" loading="lazy">';
        }

        // Fallback inline SVGs
        let _fallback = {
            native:   '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M13 3l-1.41 1.41L14.17 7H10a7 7 0 100 14h1v-2h-1a5 5 0 110-10h4.17l-2.58 2.59L13 13l5-5-5-5z"/></svg>',
            copy:     '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M16 1H4a2 2 0 00-2 2v12h2V3h12V1zm3 4H8a2 2 0 00-2 2v14a2 2 0 002 2h11a2 2 0 002-2V7a2 2 0 00-2-2zm0 16H8V7h11v14z"/></svg>',
            whatsapp: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 00-8.5 15l-1 3.7 3.8-1a9.9 9.9 0 0015.7-8.5A9.93 9.93 0 0012.04 2zm5.8 14.2c-.25.7-1.2 1.3-1.9 1.4-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.7-.6a10.9 10.9 0 01-3.6-2.9 8.3 8.3 0 01-1.8-3c-.2-.9 0-1.6.2-2 .2-.4.6-.9 1.2-1 .3-.1.6 0 .9.6.3.7 1 2.4 1.1 2.6.1.2.1.4 0 .6 0 .1-.1.2-.2.3l-.3.3c-.1.1-.2.2-.1.4.2.5.9 1.8 2.2 2.9 1.5 1.3 2.7 1.7 3.3 1.9.2.1.4 0 .5-.1l.4-.5c.1-.1.3-.1.4 0 .2 0 1.9.9 2.2 1.1.2.1.4.2.5.3.1.2.1.4 0 .6z"/></svg>',
            telegram: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M9.7 16.9l-.4 4.1c.6 0 .8-.2 1.1-.5l2.7-2.6 5.6 4.1c1 .6 1.7.3 2-.9l3.6-16.9c.3-1.2-.4-1.7-1.4-1.4L1.7 10.3c-1.2.3-1.2 1 .2 1.4l5.7 1.8 13.1-8.2c.6-.4 1.2-.2.7.2"/></svg>',
            facebook: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M22 12a10 10 0 10-11.5 9.9v-7h-2.2V12h2.2V9.6c0-2.1 1.2-3.3 3.2-3.3.9 0 1.8.2 1.8.2v2h-1c-1 0-1.4.6-1.4 1.3V12h2.5l-.4 2.9h-2.1v7A10 10 0 0022 12z"/></svg>',
            x:        '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M18.2 2H21l-6.4 7.3L22 22h-5.5l-4.3-6-4.9 6H2.5l6.9-7.9L2 2h5.6l3.9 5.5L18.2 2z"/></svg>',
            messenger:'<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 2C6.5 2 2 6 2 10.9c0 2.7 1.3 5.2 3.6 6.8V22l3.3-1.8c.9.2 1.8.3 2.8.3 5.5 0 10-4.5 10-9.4C21.7 6 17.3 2 12 2zm.7 10.8L10 9.9l-5 3.1 5.6-6.4 2.8 2.9 4.8-2.9-5.5 6.2z"/></svg>',
            email:    '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 13L2 6.8V18a2 2 0 002 2h16a2 2 0 002-2V6.8L12 13zm10-9H2l10 6 10-6z"/></svg>'
        };

        return _fallback[ p_key ] || '';
    }

    // ------------------------------------------------------------------------
    // Share Panel (UI + Handlers)
    // ------------------------------------------------------------------------

    /**
     * Build the HTML for the share panel.
     *
     * @param {Object} p_opts Options { url, title, text, note }.
     * @returns {string} HTML markup.
     */
    function build_share_panel_html( p_opts )
    {
        let _u  = encode(p_opts.url);
        let _t  = encode(p_opts.title);
        let _tx = encode(p_opts.text);

        let _targets = [
            { id: 'native',    label: 'Share…',     native: true },
            { id: 'copy',      label: 'Copy link' },
            { id: 'whatsapp',  label: 'WhatsApp',   href: 'https://wa.me/?text=' + _tx + '%20' + _u },
            { id: 'telegram',  label: 'Telegram',   href: 'https://t.me/share/url?url=' + _u + '&text=' + _tx },
            { id: 'facebook',  label: 'Facebook',   href: 'https://www.facebook.com/sharer/sharer.php?u=' + _u },
            { id: 'x',         label: 'X (Twitter)', href: 'https://twitter.com/intent/tweet?url=' + _u + '&text=' + _tx },
            { id: 'messenger', label: 'Messenger',  href: 'fb-messenger://share?link=' + _u },
            { id: 'email',     label: 'Email',      href: 'mailto:?subject=' + _t + '&body=' + _tx + '%0A%0A' + _u }
        ];

        let _native_visible = is_share_secure_context();
        let _items_html = _targets.map(function ( p_tg ) {
            let _is_native = !!p_tg.native;
            let _hidden = _is_native && !_native_visible ? ' hidden' : '';
            let _role = 'button';
            let _href_attr = p_tg.href ? 'href="' + p_tg.href + '"' : '';
            let _data_attr = 'data-share-id="' + p_tg.id + '"';
            let _tag = p_tg.href ? 'a' : 'button';
            let _type_attr = p_tg.href ? '' : 'type="button"';
            let _icon = get_icon_markup(p_tg.id, p_tg.label);

            return '' +
            '<li class="csas-share-item' + _hidden + '">' +
                '<' + _tag + ' ' + _type_attr + ' ' + _href_attr + ' ' + _data_attr + ' class="csas-share-btn" role="' + _role + '" aria-label="' + p_tg.label + '">' +
                    '<span class="csas-share-icon" aria-hidden="true">' + _icon + '</span>' +
                    //'<span class="csas-share-label">' + p_tg.label + '</span>' +
                '</' + _tag + '>' +
            '</li>';
        }).join('');

        return '' +
            '<div class="csas-share-panel" role="group" aria-label="Share options">' +
                '<div class="csas-share-feedback" aria-live="polite" aria-atomic="true"></div>' +
                '<ul class="csas-share-list">' + _items_html + '</ul>' +
            '</div>';

        return '' +
        '<div class="csas-share-panel" role="group" aria-label="Share options">' +
            '<div class="csas-share-feedback" aria-live="polite" aria-atomic="true"></div>' +
            '<div class="csas-share-meta">' +
                '<div class="csas-share-url-wrap">' +
                    '<input class="csas-share-url" type="url" value="' + p_opts.url + '" readonly aria-label="Share URL" />' +
                    '<button type="button" class="csas-share-copy" data-share-id="copy" aria-label="Copy link">Copy</button>' +
                '</div>' +
                ( p_opts.note ? '<p class="csas-share-note">' + p_opts.note + '</p>' : '' ) +
            '</div>' +
            '<ul class="csas-share-list">' + _items_html + '</ul>' +
        '</div>';
    }

    function set_feedback( p_feedback, p_msg )
    {
        if ( p_feedback ) {
            p_feedback.textContent = p_msg;
        }
    }

    /**
     * Attach handlers for the share panel, including testing fallbacks.
     *
     * @param {HTMLElement} p_root_el Root element of panel.
     * @param {Object}      p_opts    Options (url, title, text).
     * @returns {void}
     */
    function attach_share_panel_handlers( p_root_el, p_opts )
    {
        let _feedback  = p_root_el.querySelector('.csas-share-feedback');
        let _url_input = p_root_el.querySelector('.csas-share-url');

        let _link_copied_msg = p_opts.link_copied_msg || 'Link copied!';

        // Copy buttons
        p_root_el.querySelectorAll('[data-share-id="copy"]').forEach(function ( p_btn ) {
            p_btn.addEventListener('click', function () {
                let _url = p_opts.url;
                if ( navigator.clipboard && navigator.clipboard.writeText ) {
                    navigator.clipboard.writeText(_url)
                        .then(function () {
                            set_feedback(_feedback,  _link_copied_msg); })
                        .catch(function () {
                            _url_input.select();
                            document.execCommand('copy');
                            set_feedback(_feedback,  _link_copied_msg);
                        });
                } else {
                    _url_input.select();
                    document.execCommand('copy');
                    set_feedback(_feedback,  _link_copied_msg);
                }
            });
        });

        // Native share (HTTP testing aware).
        let _native_btn = p_root_el.querySelector('[data-share-id="native"]');
        if ( _native_btn ) {
            _native_btn.addEventListener('click', function () {
                if ( is_web_share_available() && is_share_secure_context() ) {
                    navigator.share({
                        title: p_opts.title,
                        text: p_opts.text,
                        url: p_opts.url
                    })
                    .then(function () {
                        set_feedback(_feedback,  'Thanks for sharing!'); })
                    .catch(function () {
                        set_feedback(_feedback,  'Share canceled.'); });
                } else {
                    if ( navigator.clipboard && navigator.clipboard.writeText ) {
                        navigator.clipboard.writeText(p_opts.url)
                            .then(function () {
                                set_feedback(_feedback,  'Native share unavailable here; link copied for testing.'); })
                            .catch(function () {
                                set_feedback(_feedback,  'Native share unavailable. Please copy manually.'); });
                    } else {
                        set_feedback(_feedback,  'Native share unavailable. Please copy manually.');
                    }
                }
            });
        }

        // External target links: open in NEW TAB instead of popup
        p_root_el.querySelectorAll('.csas-share-btn[href]').forEach(function ( p_a ) {
            // Always open in a new tab for http(s) and most app endpoints; let the browser handle mailto: etc.
            p_a.setAttribute('target', '_blank');
            p_a.setAttribute('rel', 'noopener noreferrer');

            // Remove any previous popup behavior if this gets reattached
            p_a.removeEventListener && p_a.removeEventListener('click', function () {}); // no-op guard

            // No preventDefault here—let the browser open the new tab.
        });

        // Keyboard niceties (Ctrl/Cmd + C to copy)
        p_root_el.addEventListener('keydown', function ( p_e ) {
            let _k = ( p_e.key || '' ).toLowerCase();
            if ( ( p_e.ctrlKey || p_e.metaKey ) && _k === 'c' ) {
                if ( navigator.clipboard && navigator.clipboard.writeText ) {
                    navigator.clipboard.writeText(p_opts.url)
                        .then(function () {
                            set_feedback(_feedback,  _link_copied_msg); });
                }
            }
        });
    }

    // ------------------------------------------------------------------------
    // Success Callbacks
    // ------------------------------------------------------------------------

    /**
     * Success callback for saving cart.
     *
     * @param {Object} p_data Server response.
     * @returns {void}
     */
    function save_cart_success( p_data )
    {
        let _msg = p_data.msg;
        let _dialog_args = csas.get_dialog_buttons_args(
            csas_data.titles.cart_saving_title,
            '<p>' + _msg + '</p>'
        );
        xdialog.open(_dialog_args);
    }

    /**
     * Success callback for sharing cart. Renders the share panel.
     *
     * @param {Object} p_data Server response.
     * @returns {void}
     */
    function share_cart_success( p_data )
    {
        // Currently unused
        //ensure_share_styles();

        let _default_raw_msg = ''; //'Check out my cart'
        let _raw_msg = ( p_data && p_data.msg ) ? p_data.msg : ( csas_data.texts && csas_data.texts.share_text ) || _default_raw_msg;
        let _plain_text = html_to_text(_raw_msg);

        let _share_url = extract_share_url(p_data);
        let _title = csas_data.titles.cart_sharing_title || 'Share Cart';
        let _note  = ( csas_data.texts && csas_data.texts.share_note ) || '';

        let _panel_html = build_share_panel_html({ url: _share_url, title: _title, text: _plain_text, note: _note });

        let _dialog_body = '' +
            '<p>' + _plain_text + '</p>' +
            //'<p><a class="shared-cart-link" href="' + _share_url + '" target="_blank" rel="noopener noreferrer">' + _share_url + '</a></p>' +
            _panel_html;

        let _dialog_args = csas.get_dialog_buttons_args(_title, _dialog_body);
        xdialog.open(_dialog_args);

        setTimeout(function () {
            let _panel = document.querySelector('.csas-share-panel');
            if ( _panel ) {
                attach_share_panel_handlers(_panel, { url: _share_url, title: _title, text: _plain_text });
                let _url_input = _panel.querySelector('.csas-share-url');
                if ( _url_input ) {
                    _url_input.focus();
                    _url_input.select();
                }
            }
        }, 0);

        hook_copy_button();
    }

    /**
     * Success callback for deleting cart.
     *
     * @param {Object} p_data Server response.
     * @returns {void}
     */
    function delete_cart_success( p_data )
    {
        let _selector = '.csas-cart-box[data-cart-key="' + p_data.data.cart_hash + '"]';
        let _cart_line = document.querySelector( _selector );
        if ( _cart_line ) {

            _cart_line.remove();
        }

        let _dialog_body = '<p>' + p_data.msg + '</p>';
        let _dialog_args = csas.get_dialog_buttons_args(csas_data.titles.cart_deleting_title, _dialog_body);
        xdialog.open( _dialog_args );
    }

    /**
     * Success callback for loading cart.
     *
     * @param {Object} p_data Server response.
     * @returns {void}
     */
    function load_cart_success( p_data )
    {
        let _dialog_args = csas.get_dialog_buttons_args(
            csas_data.titles.cart_applied_title,
            '<p>' + p_data.msg + '</p>'
        );
        xdialog.open(_dialog_args);
    }

    // ------------------------------------------------------------------------
    // Init
    // ------------------------------------------------------------------------

    /**
     * Initialize module: styles + event hooks.
     *
     * @returns {void}
     */
    function init()
    {
        hook_events();

        $( document.body ).on('updated_wc_div', function () {
            hook_events();
            create_events();
        });

        output_current_user_carts();
    }

    /**
     * Get share URL from closest .csas-cart-box > .csas-cart-shareable-url (hidden input).
     *
     * @param {HTMLElement} p_el Element inside/near the cart box (e.g., the clicked button).
     * @returns {string} URL to share (normalized), or empty string if not found.
     */
    function get_share_url_from_dom( p_el )
    {
        let _box = p_el && p_el.closest ? p_el.closest('.csas-cart-box') : null;
        if ( !_box ) {
            return '';
        }
        let _inp = _box.querySelector('.csas-cart-shareable-url');
        if ( _inp ) {
            let _val = ( typeof _inp.value !== 'undefined' ) ? _inp.value : _inp.getAttribute('value');
            return normalize_url(_val || '');
        }

        // Fallbacks: link in the box, then page URL
        let _a = _box.querySelector('.shared-cart-link');
        return normalize_url(_a && _a.href ? _a.href : location.href);
    }

    // Public API
    return {
        get_copy_button: get_copy_button,
        init: init,
        hook_events: hook_events,
        get_csas_cart_hash: get_csas_cart_hash,
        ajax_call: ajax_call,
        save_cart_success: save_cart_success,
        share_cart_success: share_cart_success,
        delete_cart_success: delete_cart_success,
        load_cart_success: load_cart_success,
        create_events: create_events,
        hook_copy_button: hook_copy_button,
        get_dialog_buttons_args: get_dialog_buttons_args,
        get_current_lang_code: get_current_lang_code,
        get_share_url_from_dom: get_share_url_from_dom,
        output_current_user_carts: output_current_user_carts,
        share_update: share_update
    };


} )(jQuery);

// Initialize the module
jQuery(document).ready(function ( $ ) {
    // For testing only — remove/disable in production
    window.csas_data = window.csas_data || {};
    if ( typeof csas_data.allow_insecure_share === 'undefined' ) {

        csas_data.allow_insecure_share = true; // enables native button visibility on HTTP/local
    }

    // Provide your official logo URLs somewhere in your theme/plugin bootstrap:
    csas_data.logos = {
        native:    csas_data.urls.plugin_images_url + 'share.svg',
        copy:      csas_data.urls.plugin_images_url + 'clipboard.svg',
        whatsapp:  csas_data.urls.plugin_images_url + 'whatsapp.svg',
        telegram:  csas_data.urls.plugin_images_url + 'telegram.svg',
        facebook:  csas_data.urls.plugin_images_url + 'facebook.svg',
        x:         csas_data.urls.plugin_images_url + 'x.svg',
        messenger: csas_data.urls.plugin_images_url + 'messenger.svg',
        email:     csas_data.urls.plugin_images_url + 'email.svg'
    };

    csas.init();
});
