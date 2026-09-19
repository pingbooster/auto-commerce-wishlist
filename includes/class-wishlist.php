<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PB_Wishlist {

	private static $instance = null;
	const COOKIE_NAME = 'auto_commerce_wishlist';
	const META_KEY    = '_auto_commerce_wishlist'; // stores [ product_id => added_timestamp ]

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_head', array( $this, 'inline_accent_color' ) );

		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_single_button' ) );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_archive_button' ), 15 );

		add_shortcode( 'auto_commerce_wishlist', array( $this, 'render_wishlist_page' ) );
		add_shortcode( 'auto_commerce_wishlist_count', array( $this, 'render_count_shortcode' ) );

		add_action( 'wp_ajax_auto_commerce_wishlist_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nopriv_auto_commerce_wishlist_toggle', array( $this, 'ajax_toggle' ) );

		add_action( 'wp_ajax_auto_commerce_wishlist_move_all', array( $this, 'ajax_move_all_to_cart' ) );
		add_action( 'wp_ajax_nopriv_auto_commerce_wishlist_move_all', array( $this, 'ajax_move_all_to_cart' ) );

		// Wishlist counter badge auto-added next to any menu link pointing at the wishlist page.
		add_filter( 'wp_nav_menu_objects', array( $this, 'add_menu_badge' ), 10, 1 );

		add_action( 'wp_login', array( $this, 'merge_cookie_into_user' ), 10, 2 );
	}

	/* ---------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------- */

	public function assets() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		wp_enqueue_style( 'auto-commerce-wishlist', PB_WISHLIST_URL . 'assets/css/wishlist.css', array(), PB_WISHLIST_VERSION );
		wp_enqueue_script( 'auto-commerce-wishlist', PB_WISHLIST_URL . 'assets/js/wishlist.js', array( 'jquery' ), PB_WISHLIST_VERSION, true );

		wp_localize_script( 'auto-commerce-wishlist', 'AutoCommerceWishlist', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'auto_commerce_wishlist_nonce' ),
			'ids'     => array_map( 'absint', array_keys( $this->get_items() ) ),
		) );
	}

	public function inline_accent_color() {
		$color = PB_Wishlist_Theme_Color_Detector::get_accent_color();
		printf( '<style id="auto-commerce-wishlist-accent">:root{--auto-commerce-wishlist-accent:%s;}</style>' . "\n", esc_attr( $color ) );
	}

	/* ---------------------------------------------------------------------
	 * Storage helpers — items are stored as [ product_id => added_timestamp ]
	 * so reminder emails and reports know how long something's been saved.
	 * ------------------------------------------------------------------- */

	public function get_items() {

		if ( is_user_logged_in() ) {
			$items = get_user_meta( get_current_user_id(), self::META_KEY, true );
			return $this->normalize( $items );
		}

		if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$items = json_decode( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ), true );
			return $this->normalize( $items );
		}

		return array();
	}

	/**
	 * Accepts either the old flat-array format (plain list of IDs) or the
	 * new assoc format, and always returns a clean [id => timestamp] array.
	 */
	private function normalize( $items ) {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$clean = array();
		foreach ( $items as $key => $value ) {
			if ( is_int( $key ) && is_numeric( $value ) ) {
				// Old flat-list format: [0 => 12, 1 => 45] -> treat value as the product ID.
				$clean[ absint( $value ) ] = time();
				continue;
			}
			$id = absint( $key );
			if ( $id ) {
				$clean[ $id ] = absint( $value );
			}
		}
		return $clean;
	}

	private function save_items( $items ) {

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::META_KEY, $items );
		} else {
			wc_setcookie( self::COOKIE_NAME, wp_json_encode( $items ), time() + ( 30 * DAY_IN_SECONDS ) );
			$_COOKIE[ self::COOKIE_NAME ] = wp_json_encode( $items );
		}

		return $items;
	}

	public function merge_cookie_into_user( $user_login, $user ) {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return;
		}

		$cookie_items = json_decode( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ), true );
		$cookie_items = $this->normalize( $cookie_items );

		$user_items = get_user_meta( $user->ID, self::META_KEY, true );
		$user_items = $this->normalize( $user_items );

		$merged = $cookie_items + $user_items; // keep existing timestamps where both have the item
		update_user_meta( $user->ID, self::META_KEY, $merged );

		wc_setcookie( self::COOKIE_NAME, '', time() - HOUR_IN_SECONDS );
	}

	/* ---------------------------------------------------------------------
	 * Button rendering
	 * ------------------------------------------------------------------- */

	public function render_single_button() {
		if ( PB_Wishlist_Settings::show_on_single() ) {
			$this->render_button();
		}
	}

	public function render_archive_button() {
		if ( PB_Wishlist_Settings::show_on_archive() ) {
			$this->render_button();
		}
	}

	public function render_button() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id  = $product->get_id();
		$in_list     = array_key_exists( $product_id, $this->get_items() );
		$show_label  = PB_Wishlist_Settings::get_show_label();
		$label_class = $show_label ? '' : ' auto-commerce-wishlist-no-label';

		printf(
			'<button type="button" class="button auto-commerce-wishlist-btn%s%s" data-product-id="%d" aria-pressed="%s" aria-label="%s">
				<span class="auto-commerce-wishlist-icon" aria-hidden="true">%s</span>
				%s
			</button>',
			$in_list ? ' auto-commerce-wishlist-active' : '',
			esc_attr( $label_class ),
			absint( $product_id ),
			$in_list ? 'true' : 'false',
			$in_list ? esc_attr__( 'Remove from wishlist', 'auto-commerce-wishlist' ) : esc_attr__( 'Add to wishlist', 'auto-commerce-wishlist' ),
			$this->icon_svg(),
			$show_label ? '<span class="auto-commerce-wishlist-label">' . ( $in_list ? esc_html__( 'Saved', 'auto-commerce-wishlist' ) : esc_html__( 'Add to Wishlist', 'auto-commerce-wishlist' ) ) . '</span>' : ''
		);
	}

	private function icon_svg() {
		$choices = PB_Wishlist_Settings::get_icon_choices();
		$key     = PB_Wishlist_Settings::get_selected_icon();
		$path    = isset( $choices[ $key ] ) ? $choices[ $key ]['svg'] : $choices['heart']['svg'];

		return '<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' . $path . '</svg>';
	}

	/* ---------------------------------------------------------------------
	 * Menu badge — auto-attaches a count bubble to any menu item that
	 * links to the Wishlist page, no menu editing required beyond adding
	 * the page to the menu like normal.
	 * ------------------------------------------------------------------- */

	public function add_menu_badge( $items ) {

		if ( is_admin() || empty( $items ) ) {
			return $items;
		}

		$wishlist_page = PB_Wishlist_Settings::get_wishlist_page_id();
		if ( ! $wishlist_page ) {
			return $items;
		}

		$wishlist_url = get_permalink( $wishlist_page );
		$count        = count( $this->get_items() );

		foreach ( $items as $item ) {
			if ( untrailingslashit( $item->url ) === untrailingslashit( $wishlist_url ) ) {
				$item->title .= ' <span class="auto-commerce-wishlist-menu-badge">' . absint( $count ) . '</span>';
			}
		}

		return $items;
	}

	public function render_count_shortcode() {
		return '<span class="auto-commerce-wishlist-menu-badge">' . absint( count( $this->get_items() ) ) . '</span>';
	}

	/* ---------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------- */

	public function ajax_toggle() {
		check_ajax_referer( 'auto_commerce_wishlist_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product = wc_get_product( $product_id );
		if ( ! $product || 'publish' !== $product->get_status() ) {
			wp_send_json_error( array( 'message' => 'Invalid product.' ) );
		}

		$items = $this->get_items();

		if ( array_key_exists( $product_id, $items ) ) {
			unset( $items[ $product_id ] );
			$action = 'removed';
		} else {
			$items[ $product_id ] = time();
			$action               = 'added';
		}

		$items = $this->save_items( $items );

		wp_send_json_success( array(
			'action' => $action,
			'count'  => count( $items ),
		) );
	}

	public function ajax_move_all_to_cart() {
		check_ajax_referer( 'auto_commerce_wishlist_nonce', 'nonce' );

		$items = $this->get_items();
		$added = 0;

		foreach ( array_keys( $items ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_purchasable() && $product->is_in_stock() ) {
				WC()->cart->add_to_cart( $product_id );
				unset( $items[ $product_id ] );
				$added++;
			}
		}

		$this->save_items( $items );

		wp_send_json_success( array(
			'added'    => $added,
			'cart_url' => wc_get_cart_url(),
		) );
	}

	/* ---------------------------------------------------------------------
	 * [auto_commerce_wishlist] shortcode — the actual Wishlist page content
	 * ------------------------------------------------------------------- */

	public function render_wishlist_page() {

		// Read-only "someone shared their wishlist with you" view.
		if ( ! empty( $_GET['pb_share'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return PB_Wishlist_Share::render_shared_view( sanitize_text_field( wp_unslash( $_GET['pb_share'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$items = $this->get_items();

		if ( empty( $items ) ) {
			return '<p class="auto-commerce-wishlist-empty">' . esc_html__( 'Your wishlist is empty. Browse our collection and tap the icon on any piece to save it here.', 'auto-commerce-wishlist' ) . '</p>';
		}

		ob_start();

		echo '<div class="auto-commerce-wishlist-toolbar">';
		echo '<button type="button" class="button auto-commerce-wishlist-move-all">' . esc_html__( 'Move All to Cart', 'auto-commerce-wishlist' ) . '</button>';
		echo PB_Wishlist_Share::render_share_buttons( array_keys( $items ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';

		echo '<div class="auto-commerce-wishlist-grid">';

		foreach ( $items as $product_id => $timestamp ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			?>
			<div class="auto-commerce-wishlist-item">
				<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
					<?php echo wp_kses_post( $product->get_image( 'medium' ) ); ?>
					<h3><?php echo esc_html( $product->get_name() ); ?></h3>
				</a>
				<div class="auto-commerce-wishlist-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<div class="auto-commerce-wishlist-actions">
					<a class="button" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>">
						<?php esc_html_e( 'Add to Cart', 'auto-commerce-wishlist' ); ?>
					</a>
					<button type="button" class="button auto-commerce-wishlist-remove" data-product-id="<?php echo absint( $product_id ); ?>">
						<?php esc_html_e( 'Remove', 'auto-commerce-wishlist' ); ?>
					</button>
				</div>
			</div>
			<?php
		}

		echo '</div>';
		return ob_get_clean();
	}
}
