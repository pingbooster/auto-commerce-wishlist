<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A concise in-dashboard setup guide so a store owner never needs to search
 * for documentation after activating the plugin.
 */
class PB_Wishlist_Guidance {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_guidance_page' ) );
	}

	public function add_guidance_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Wishlist Guidance', 'auto-commerce-wishlist' ),
			__( 'Wishlist Guidance', 'auto-commerce-wishlist' ),
			'manage_woocommerce',
			'auto-commerce-wishlist-guidance',
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$page_id  = PB_Wishlist_Settings::get_wishlist_page_id();
		$page_url = $page_id ? get_permalink( $page_id ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Auto Commerce Wishlist: How to Use', 'auto-commerce-wishlist' ); ?></h1>
			<p><?php esc_html_e( 'The wishlist is ready after activation. Follow these short steps to make it visible to shoppers.', 'auto-commerce-wishlist' ); ?></p>

			<h2><?php esc_html_e( 'Quick setup', 'auto-commerce-wishlist' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Open WooCommerce → Auto Commerce Wishlist → Icon & Display and choose the icon, text label, and where wishlist buttons appear.', 'auto-commerce-wishlist' ); ?></li>
				<li><?php esc_html_e( 'Select the page that contains the wishlist. The plugin normally creates one automatically.', 'auto-commerce-wishlist' ); ?></li>
				<li><?php esc_html_e( 'Add that Wishlist page to your site menu. The saved-items counter appears automatically beside its menu link.', 'auto-commerce-wishlist' ); ?></li>
				<li><?php esc_html_e( 'Visit a product and save an item to test the button, counter, and wishlist page.', 'auto-commerce-wishlist' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Shortcodes', 'auto-commerce-wishlist' ); ?></h2>
			<table class="widefat striped" style="max-width:800px;">
				<thead><tr><th><?php esc_html_e( 'Shortcode', 'auto-commerce-wishlist' ); ?></th><th><?php esc_html_e( 'Use', 'auto-commerce-wishlist' ); ?></th></tr></thead>
				<tbody>
					<tr><td><code>[auto_commerce_wishlist]</code></td><td><?php esc_html_e( 'Displays the customer wishlist page.', 'auto-commerce-wishlist' ); ?></td></tr>
					<tr><td><code>[auto_commerce_wishlist_count]</code></td><td><?php esc_html_e( 'Displays the current customer’s saved-item count anywhere a shortcode is allowed.', 'auto-commerce-wishlist' ); ?></td></tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Customer features', 'auto-commerce-wishlist' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Guests can save items for 30 days in their browser. When they log in, the saved items merge into their account.', 'auto-commerce-wishlist' ); ?></li>
				<li><?php esc_html_e( 'Customers can share a read-only wishlist link, including through WhatsApp.', 'auto-commerce-wishlist' ); ?></li>
				<li><?php esc_html_e( 'Logged-in customers can receive a one-time reminder after five days and a back-in-stock email for a saved item.', 'auto-commerce-wishlist' ); ?></li>
			</ul>

			<h2><?php esc_html_e( 'Store management', 'auto-commerce-wishlist' ); ?></h2>
			<p><?php esc_html_e( 'WooCommerce → Most Wishlisted shows the 25 products saved most often. This is useful for restocking and promotions.', 'auto-commerce-wishlist' ); ?></p>

			<h2><?php esc_html_e( 'Troubleshooting', 'auto-commerce-wishlist' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'If the buttons do not appear, make sure WooCommerce is active and placement is enabled in WooCommerce → Auto Commerce Wishlist → Icon & Display.', 'auto-commerce-wishlist' ); ?></li>
				<li><?php esc_html_e( 'If the counter is not visible, add the selected Wishlist page to the same navigation menu used by your theme.', 'auto-commerce-wishlist' ); ?></li>
				<li><?php esc_html_e( 'Clear your site cache after changing a theme color or switching themes.', 'auto-commerce-wishlist' ); ?></li>
			</ul>

			<?php if ( $page_url ) : ?>
				<p><a class="button button-primary" href="<?php echo esc_url( $page_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Wishlist Page', 'auto-commerce-wishlist' ); ?></a></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
