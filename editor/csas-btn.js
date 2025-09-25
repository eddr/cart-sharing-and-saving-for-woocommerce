/**
 * Registers a preconfigured "Share Cart Button" block variation and
 * attaches an (optional) URL-locking HOC for the editor UI.
 *
 * @package    csas
 * @subpackage gutenberg
 * @since      1.0.0
 */
wp.domReady(
    () =>
    {
    /**
     * Register a Button variation with preset attributes.
     *
     * @since 1.0.0
     * @return {void}
     */
        wp.blocks.registerBlockVariation('core/button', {
            name: 'csas/share-button',
            title: 'Share Cart Button',
            description: 'Shared Cart link button with preset styles and',
            icon: 'admin-links',
            attributes: {
                className: 'csas-share-cart revalidate-url',
                url: '',
                text: 'Share Cart',
            },
            scope: [ 'inserter' ],
        });

    wp.blocks.registerBlockVariation('core/button', {
        name: 'csas/save-cart-button',
        title: 'Save Cart Button',
        description: 'Shared Cart link button with preset styles and',
        icon: 'admin-links',
        attributes: {
            className: 'csas-save-cart',
            url: '',
            text: 'Add To My Carts',
        },
        scope: [ 'inserter' ],
    });



    /** --------------------------------------------------------------------
     * (Optional) Lock the URL so editors can’t change it
     * ------------------------------------------------------------------- */

    /** @since 1.0.0 */
    const _hooks = wp.hooks;

    /** @since 1.0.0 */
    const _compose = wp.compose;

    /** @since 1.0.0 */
    const _element = wp.element;

    /** @since 1.0.0 */
    const _add_filter = _hooks.addFilter;

    /** @since 1.0.0 */
    const _create_higher_order_component = _compose.createHigherOrderComponent;

    /**
     * Higher-order component wrapper that could enforce a locked URL.
     * (Currently pass-through; add control logic here if you decide to lock.)
     *
     * @since 1.0.0
     *
     * @param {Function} p_Block_Edit  Original block edit component.
     * @return {Function}              Wrapped component.
     */
    const _with_locked_url = _create_higher_order_component(
        ( p_Block_Edit ) =>
        {
        /**
         * Wrapped BlockEdit render function.
         *
         * @since 1.0.0
         *
         * @param {Object} p_props  Component props.
         * @return {JSX.Element}    Rendered element.
         */
            return function ( p_props ) {
                // Example placeholder where you could enforce attributes:
                // if ( p_props.name === 'core/button' && p_props.attributes.className === 'csas-share-cart' ) {
                //     p_props.setAttributes( { url: 'https://example.com/share-cart' } );
                // }

                return _element.createElement(p_Block_Edit, p_props);
            };
        },
        'with_locked_url'
    );

    /**
     * Register the URL-locking HOC on the editor.BlockEdit filter.
     *
     * @since 1.0.0
     * @return {void}
     */
    _add_filter(
        'editor.BlockEdit',
        'csas/share_button',
        _with_locked_url
    );
    }
);
