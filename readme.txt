=== Cart Sharing and Saving For WooCommerce ===
Contributors: EarlyBirds
Tags: cart, woocommerce, sharing, saving, wishlist
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.5
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Let shoppers save and share WooCommerce carts — wishlists, gift ideas, or getting feedback before buying. Great for enhancing conversion.

== Description ==

Save & Share Cart for WooCommerce makes it easy for your customers to save their shopping cart for later or share it with others.  
Both logged-in users and guests can generate a unique cart link, allowing them to revisit their cart at any time or send it to friends, family, or colleagues.  

As a store owner, you also get an admin screen to view all saved carts, giving you valuable insights into abandoned carts, customer preferences, and potential sales opportunities.  

This simple but powerful tool can help reduce cart abandonment, improve user experience, and even encourage viral sharing of products in your store.  

### Key Features
- **Save cart for later** – Customers and guests can save their carts and return to them with a unique link.  
- **Shareable cart links** – Customers can share their cart with friends, family, or on social media.  
- **Guest support** – Works even without user accounts.  
- **Admin saved carts screen** – Monitor all saved carts in the WordPress dashboard.  
- **Boost conversions** – Reduce abandoned carts and recover lost sales.  
- **Shortcodes** – Place “Save Cart” and “Share Cart” buttons anywhere you need with simple shortcodes.  
- **Gutenberg support** – Includes a Button block variation so you can add save/share cart actions directly in the Block Editor.  
- **Page cache support** –  
  - Compatible with W3 Total Cache (full, except Cloudflare which may require additional setup), WP Super Cache, WP Rocket, and partially with WP Fastest Cache.  
  - Shared cart URLs can be cached to reduce server load.  
  - Customer cart content is refreshed via REST only if changes occurred.  

### Use Cases
- Customers want to create a wishlist-style cart to purchase later.  
- Teams or friends shopping together can easily share carts.  
- Shoppers can send a cart of clothes (or any products) to friends/family to get feedback before buying.  
- Store owners can track saved/abandoned carts to optimize sales strategies.  
- Shoppers can send cart links as gift ideas.  

### Roadmap
We’re actively improving the plugin! Planned features include:  
1. **Saved cart naming** – Shoppers will be able to name their saved carts for easier organization.  
2. **More advanced Elementor support** – A dedicated widget for Elementor.  
3. **Additional caching integrations** and optimization.  
4. **More multilingual support** — Polylang and TranslatePress are on the roadmap.  

*(More features to come — we’d love your feedback!)*  

### Contribute & Support
This is an **early version** of the plugin. Bug reports, feature requests, and feedback are greatly appreciated and will help shape future development.  

- **Support forum** – Please use the [WordPress.org support forum](https://wordpress.org/support/plugin/carts-saving-and-sharing-for-woocommerce) for help and troubleshooting.  
- **GitHub** – Development happens in the open. You can suggest features, report bugs, or contribute code via our GitHub repository (link coming soon).  

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/save-share-cart-for-woocommerce` directory, or install the plugin directly through the WordPress plugins screen.  
2. Activate the plugin through the **Plugins** screen in WordPress.  
3. That’s it! The “Save Cart” button will now be available in WooCommerce carts.  
4. (Optional) Visit the **Saved Carts** admin page under WooCommerce → Saved Carts to view all saved carts.  
5. (Optional) Use the provided **shortcodes** or the **Gutenberg Button block variation** to place save/share buttons anywhere in your store.  

== Screenshots ==

1. **Save Cart button** – Customers can save their current cart with a single click.  
2. **Shareable cart link** – A unique link is generated for sharing carts.  
3. **Guest cart saving** – Works seamlessly for non-logged-in shoppers.  
4. **Admin saved carts list** – Store owners can view and manage saved carts from the dashboard.  

== FAQ ==

= Does this plugin work for guests? =  
Yes! Both logged-in users and guests can save and share carts.  

= Where can I find saved carts as an admin? =  
You’ll find a **Saved Carts** page under WooCommerce → Saved Carts in your WordPress dashboard.  

= Can customers rename their saved carts? =  
Not yet, but this feature is on the **Roadmap** and coming soon.  

= Does it work with all WooCommerce themes? =  
The plugin is designed to work with any theme that supports standard WooCommerce cart functionality.  

= How can I display the buttons outside the cart? =  
You can use the provided **shortcodes** or the **Gutenberg Button block variation** to place Save/Share buttons anywhere on your site.  

= I set saved and shared cart pages but the site is not updated. =  
Please flush rewrite rules (visit **Settings → Permalinks** and save once) and clear your entire site/page cache.  

= Are there any filters to customize the cart HTML output? =  
Yes, you can use these filters to adjust cart rendering:  
- `\EB\CSAS\Frontend\Display\get_saved_carts_html`  
- `\EB\CSAS\Frontend\Display\get_shared_cart_html`  

= Is there multilingual support? =  
Yes. The plugin has been tested with **WPML**. It also includes hooks compatible with **Polylang** and **TranslatePress**, though those are not fully tested yet.  

== Changelog ==

= 0.5 =
* Initial release – save and share WooCommerce carts for both logged-in users and guests.  
* Added shortcodes to display save/share buttons anywhere.  
* Added Gutenberg Button block variation for easy integration in the Block Editor.  
* Page cache support for W3 Total Cache, WP Super Cache, WP Rocket, and WP Fastest Cache (partial).  
