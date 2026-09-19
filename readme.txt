=== Auto Commerce Wishlist ===
Contributors: same2cool
Tags: wishlist, woocommerce, ecommerce, save for later, theme matching
Requires at least: 5.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

![Auto Commerce Wishlist Banner](assets/banner-772x250.png)

== Description ==
A lightweight wishlist plugin for WooCommerce stores that automatically
matches the active theme's styling — no manual CSS required.

How it auto-matches any theme:
1. The "Add to Wishlist" button reuses the theme's own `.button` CSS class,
   the same one WooCommerce "Add to Cart" buttons use — so it instantly
   inherits the theme's real font, padding, shape, and hover animation.
2. The heart icon's color is auto-detected from the active theme using
   (in order): block theme.json color palette, Customizer theme_mods,
   custom theme-options tables, and finally the theme's stylesheet
   CSS variables. If nothing is found, it falls back to a neutral gold.
3. Detected color is cached for 12 hours and automatically re-checked
   whenever the Customizer is saved or the theme is switched.

== Installation ==
1. Upload the plugin ZIP through Plugins -> Add New -> Upload Plugin.
2. In WordPress: Plugins -> Add New -> Upload Plugin -> choose the zip -> Install Now -> Activate.
3. On activation, a "Wishlist" page is created automatically at /wishlist
   containing the [auto_commerce_wishlist] shortcode.
4. Go to Appearance -> Menus and add the new "Wishlist" page to your site menu.
5. Visit any product — a "Add to Wishlist" button now appears automatically
   near the Add to Cart button and on shop/category listing pages.

== Choosing the Wishlist Icon ==
Go to WooCommerce -> Auto Commerce Wishlist -> Icon & Display in your WordPress dashboard to choose between
Heart, Star, Bookmark, or a jewelry-themed Diamond icon, and toggle whether
the "Add to Wishlist" text label shows next to it. The icon color always
auto-matches your active theme regardless of which icon you pick.

== Additional Features ==

1. Wishlist counter badge
   Automatically appears next to any menu item that links to the Wishlist
   page — no setup needed beyond adding the page to a menu. You can also
   place it manually anywhere using the [auto_commerce_wishlist_count] shortcode.

2. Share Wishlist link
   On the Wishlist page, customers can click "Share Wishlist" to copy a
   link containing their saved items. Anyone who opens it sees a read-only
   version of that wishlist (e.g. useful for gift hints).

3. Share on WhatsApp
   A one-click WhatsApp share button sits next to "Share Wishlist" and
   opens WhatsApp with the link pre-filled into a message.

4. Move All to Cart
   A button on the Wishlist page adds every in-stock, purchasable item to
   the cart in one click and redirects to checkout.

5. Email reminder for saved items
   Logged-in customers who leave an item in their wishlist for 5+ days
   receive a one-time reminder email. (Guests can't be emailed since they
   have no account — this only applies to logged-in customers.)
   Change the number of days by editing REMINDER_DAYS in
   includes/class-notifications.php.

6. Back-in-stock notifications
   If a wishlisted product goes from out-of-stock to in-stock, any
   logged-in customer who saved it receives an automatic email. Sent once
   per restock to avoid repeat emails.

7. Admin report: Most Wishlisted Products
   Found under WooCommerce -> Most Wishlisted in your dashboard. Shows the
   top 25 most-saved products across all customers along with their
   current stock status, refreshed every 6 hours.

== Notes ==
- Works for both guests (30-day cookie) and logged-in customers (saved to
  their account, and merged automatically if they add items before logging in).
- WooCommerce -> Auto Commerce Wishlist -> Icon & Display lets store owners choose the icon, label visibility,
  product-page placement, archive placement, and the page that contains the
  [auto_commerce_wishlist] shortcode.

== Changelog ==

= 1.2.2 =
* Removed an unavailable Plugin URI from the plugin header.
* Escaped generated product image and price HTML.
* Updated the contributor username.

= 1.2.1 =
* Updated the tested WordPress version to 7.1.

= 1.2.0 =
* Renamed the plugin to Auto Commerce Wishlist.
* Updated the plugin slug and text domain to auto-commerce-wishlist.
* Kept all administration pages together under WooCommerce.