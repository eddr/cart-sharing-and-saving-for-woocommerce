var csas = ( function ( $ ) {

    var events = {};
    function create_events()
    {

        csas.events.save = new CustomEvent('cart_saved');
        csas.events.shared = new CustomEvent('cart_shared');
        csas.events.deleted = new CustomEvent('cart_deleted');
        csas.events.load = new CustomEvent('cart_loaded');
    }
    function hook_events()
    {

        let _hooks = ['save', 'share', 'delete', 'load'];
        let _prefix = 'csas-';

        _hooks.forEach(op => {

            console.log(`.${_prefix}${op} - cart`);

            $(`.${_prefix}${op} - cart`).on('click', function (e) {

                e.preventDefault();
                console.log(e.target);
                let _csas_cart_hash = get_csas_cart_hash(e.target);
                ajax_call(op + '_cart', { csas_hash: _csas_cart_hash }, eval(op + '_cart_success'));
            });
        });
    }

    function get_csas_cart_hash( p_el )
    {

        return p_el.dataset.cartKey;
    }

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
            success: p_success_callback,
            error: function () {

                alert('An error occurred.');
            }
        };
        $.ajax(_args);
    }

    // Success Callback for Save Cart
    function save_cart_success( p_data )
    {

        if ( p_data.success ) {
            alert(p_data.msg); // Alert or update your UI
        }
    }

    // Success Callback for Share Cart
    function share_cart_success( p_data )
    {

        if ( p_data.success ) {
            alert("Share this link: " + p_data.msg); // Present the link to the user
        }
    }

    // Success Callback for Delete Cart
    function delete_cart_success( p_data )
    {

        if ( p_data.success ) {
            let _selector = `details[data - cart - key = "${p_data.data.cart_key}"]`;
            let _cart_line = document.querySelector(_selector);
            _cart_line.remove();
            alert(p_data.msg); // Alert about cart deletion
        } else {
            alert(p_data.msg);
        }

    }

    // Success Callback for Load Cart
    function load_cart_success( p_data )
    {

        if ( p_data.success ) {
            alert(p_data.msg); // Alert or update UI with loaded cart data
        }
    }

    // Public API
    return {
        init: function () {

            hook_events();

            $(document.body).on('updated_wc_div', function () {

                hook_events();
                create_events();
            });
        },
        // Expose methods if needed
        hook_events: hook_events,
        get_csas_cart_hash: get_csas_cart_hash,
        ajax_call: ajax_call,
        save_cart_success: save_cart_success,
        share_cart_success: share_cart_success,
        delete_cart_success: delete_cart_success,
        load_cart_success: load_cart_success,
        create_events: create_events
    };

})(jQuery);

// Initialize the module
jQuery(document).ready(function ( $ ) {

    csas.init();
});
