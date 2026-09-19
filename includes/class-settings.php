<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PB_Wishlist_Settings
 *
 * A tiny settings screen (Settings -> Wishlist Icon) that lets the store
 * owner pick which icon shows on the "Add to Wishlist" button, without
 * touching any code. Icon color still auto-matches the theme.
 */
class PB_Wishlist_Settings {

	private static $instance = null;
	const OPTION_ICON  = 'auto_commerce_wishlist_icon';
	const OPTION_LABEL = 'auto_commerce_wishlist_show_label';
	const OPTION_SINGLE = 'auto_commerce_wishlist_show_single';
	const OPTION_ARCHIVE = 'auto_commerce_wishlist_show_archive';
	const OPTION_PAGE = 'auto_commerce_wishlist_page_id';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * The available icon choices. Each is a self-contained SVG path,
	 * drawn with currentColor / var(--auto-commerce-wishlist-accent) so it always
	 * matches the auto-detected theme color regardless of which is picked.
	 */
	public static function get_icon_choices() {
		return array(
			'heart'    => array(
				'label' => __( 'Heart', 'auto-commerce-wishlist' ),
				'svg'   => '<path d="M12 21s-6.7-4.35-9.33-8.2C.86 10.15 1.4 6.6 4.2 4.9c2.2-1.35 4.9-.85 6.6 1.1L12 7.4l1.2-1.4c1.7-1.95 4.4-2.45 6.6-1.1 2.8 1.7 3.34 5.25 1.53 7.9C18.7 16.65 12 21 12 21z"/>',
			),
			'star'     => array(
				'label' => __( 'Star', 'auto-commerce-wishlist' ),
				'svg'   => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/>',
			),
			'bookmark' => array(
				'label' => __( 'Bookmark', 'auto-commerce-wishlist' ),
				'svg'   => '<path d="M6 2h12a1 1 0 0 1 1 1v18l-7-4-7 4V3a1 1 0 0 1 1-1z"/>',
			),
			'diamond'  => array(
				'label' => __( 'Diamond (jewelry-themed)', 'auto-commerce-wishlist' ),
				'svg'   => '<path d="M6 3h12l4 6-10 12L2 9l4-6z"/>',
			),
		);
	}

	public static function get_selected_icon() {
		$choices = self::get_icon_choices();
		$saved   = get_option( self::OPTION_ICON, 'heart' );
		return isset( $choices[ $saved ] ) ? $saved : 'heart';
	}

	public static function get_show_label() {
		return (bool) get_option( self::OPTION_LABEL, true );
	}

	public static function show_on_single() {
		return (bool) get_option( self::OPTION_SINGLE, true );
	}

	public static function show_on_archive() {
		return (bool) get_option( self::OPTION_ARCHIVE, true );
	}

	public static function get_wishlist_page_id() {
		return absint( get_option( self::OPTION_PAGE, 0 ) );
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Auto Commerce Wishlist', 'auto-commerce-wishlist' ),
			__( 'Auto Commerce Wishlist', 'auto-commerce-wishlist' ),
			'manage_woocommerce',
			'auto-commerce-wishlist',
			array( $this, 'render_overview_page' )
		);

		add_submenu_page(
			'woocommerce',
			__( 'Icon & Display', 'auto-commerce-wishlist' ),
			__( 'Icon & Display', 'auto-commerce-wishlist' ),
			'manage_options',
			'auto-commerce-wishlist-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function render_overview_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Auto Commerce Wishlist', 'auto-commerce-wishlist' ); ?></h1>
			<p><?php esc_html_e( 'Manage every Auto Commerce Wishlist feature from this WooCommerce menu.', 'auto-commerce-wishlist' ); ?></p>
			<h2><?php esc_html_e( 'Quick links', 'auto-commerce-wishlist' ); ?></h2>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=auto-commerce-wishlist-settings' ) ); ?>"><?php esc_html_e( 'Icon & Display', 'auto-commerce-wishlist' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=auto-commerce-wishlist-guidance' ) ); ?>"><?php esc_html_e( 'Guidance', 'auto-commerce-wishlist' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=auto-commerce-wishlist-report' ) ); ?>"><?php esc_html_e( 'Most Wishlisted', 'auto-commerce-wishlist' ); ?></a>
			</p>
			<h2><?php esc_html_e( 'What is included', 'auto-commerce-wishlist' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Theme-matching wishlist buttons on products and shop listings.', 'auto-commerce-wishlist' ); ?></li>
				<li><?php esc_html_e( 'Wishlist page, menu counter, share links, WhatsApp sharing, and Move All to Cart.', 'auto-commerce-wishlist' ); ?></li>
				<li><?php esc_html_e( 'Back-in-stock alerts, wishlist reminders, and most-wishlisted product reporting.', 'auto-commerce-wishlist' ); ?></li>
			</ul>
		</div>
		<?php
	}

	public function register_settings() {
		register_setting( 'auto_commerce_wishlist_settings_group', self::OPTION_ICON, array(
			'sanitize_callback' => array( $this, 'sanitize_icon' ),
			'default'           => 'heart',
		) );
		register_setting( 'auto_commerce_wishlist_settings_group', self::OPTION_LABEL, array(
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		) );
		register_setting( 'auto_commerce_wishlist_settings_group', self::OPTION_SINGLE, array( 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ) );
		register_setting( 'auto_commerce_wishlist_settings_group', self::OPTION_ARCHIVE, array( 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ) );
		register_setting( 'auto_commerce_wishlist_settings_group', self::OPTION_PAGE, array( 'sanitize_callback' => 'absint' ) );
	}

	public function sanitize_icon( $value ) {
		$choices = self::get_icon_choices();
		return isset( $choices[ $value ] ) ? $value : 'heart';
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$choices  = self::get_icon_choices();
		$selected = self::get_selected_icon();
		$color    = PB_Wishlist_Theme_Color_Detector::get_accent_color();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Wishlist Icon Settings', 'auto-commerce-wishlist' ); ?></h1>
			<p><?php esc_html_e( 'Choose which icon shoppers see on the "Add to Wishlist" button. The color always auto-matches your active theme.', 'auto-commerce-wishlist' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'auto_commerce_wishlist_settings_group' ); ?>

				<table class="widefat" style="max-width:700px;border:1px solid #ccd0d4;">
					<tbody>
					<?php foreach ( $choices as $key => $icon ) : ?>
						<tr>
							<td style="width:50px;text-align:center;">
								<svg width="26" height="26" viewBox="0 0 24 24" style="fill:<?php echo esc_attr( $color ); ?>;">
									<?php echo $icon['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</svg>
							</td>
							<td>
								<label>
									<input type="radio" name="<?php echo esc_attr( self::OPTION_ICON ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $selected, $key ); ?> />
									<?php echo esc_html( $icon['label'] ); ?>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

			<p style="margin-top:20px;">
					<label>
						<input type="hidden" name="<?php echo esc_attr( self::OPTION_LABEL ); ?>" value="0" />
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION_LABEL ); ?>" value="1" <?php checked( self::get_show_label() ); ?> />
						<?php esc_html_e( 'Show text label ("Add to Wishlist") next to the icon', 'auto-commerce-wishlist' ); ?>
					</label>
			</p>

			<p><label><input type="hidden" name="<?php echo esc_attr( self::OPTION_SINGLE ); ?>" value="0" /><input type="checkbox" name="<?php echo esc_attr( self::OPTION_SINGLE ); ?>" value="1" <?php checked( self::show_on_single() ); ?> /> <?php esc_html_e( 'Show on individual product pages', 'auto-commerce-wishlist' ); ?></label></p>
			<p><label><input type="hidden" name="<?php echo esc_attr( self::OPTION_ARCHIVE ); ?>" value="0" /><input type="checkbox" name="<?php echo esc_attr( self::OPTION_ARCHIVE ); ?>" value="1" <?php checked( self::show_on_archive() ); ?> /> <?php esc_html_e( 'Show on shop and category pages', 'auto-commerce-wishlist' ); ?></label></p>
			<p>
				<label for="auto-commerce-wishlist-page"><strong><?php esc_html_e( 'Wishlist page', 'auto-commerce-wishlist' ); ?></strong></label><br />
				<?php wp_dropdown_pages( array( 'name' => self::OPTION_PAGE, 'id' => 'auto-commerce-wishlist-page', 'selected' => self::get_wishlist_page_id(), 'show_option_none' => __( 'Select a page', 'auto-commerce-wishlist' ) ) ); ?>
				<span class="description"><?php esc_html_e( 'This page should contain the [auto_commerce_wishlist] shortcode.', 'auto-commerce-wishlist' ); ?></span>
			</p>

				<?php submit_button( __( 'Save Icon Settings', 'auto-commerce-wishlist' ) ); ?>
			</form>
		</div>
		<?php
	}
}
