<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PB_Wishlist_Notifications
 *
 * Two email features, both for logged-in customers only (guests have no
 * email address on file to notify):
 *
 * 1. Reminder email if a wishlist item has sat unpurchased for N days.
 * 2. Back-in-stock alert if a wishlisted item that was out of stock
 *    becomes available again.
 */
class PB_Wishlist_Notifications {

	private static $instance = null;
	const REMINDER_DAYS    = 5; // send once, after the item has been saved this many days
	const REMINDER_SENT_KEY = '_auto_commerce_wishlist_reminder_sent'; // [product_id => true]
	const STOCK_NOTIFIED_KEY = '_auto_commerce_wishlist_stock_notified'; // [product_id => true]

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'auto_commerce_wishlist_daily_cron', array( $this, 'send_reminder_emails' ) );

		// Fires whenever WooCommerce updates a product's stock status.
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'maybe_notify_back_in_stock' ), 10, 3 );
	}

	/* ---------------------------------------------------------------------
	 * Reminder emails (cron)
	 * ------------------------------------------------------------------- */

	public function send_reminder_emails() {
		global $wpdb;

		$meta_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
				PB_Wishlist::META_KEY
			)
		);

		if ( empty( $meta_rows ) ) {
			return;
		}

		$cutoff = time() - ( self::REMINDER_DAYS * DAY_IN_SECONDS );

		foreach ( $meta_rows as $row ) {
			$items = maybe_unserialize( $row->meta_value );
			if ( ! is_array( $items ) || empty( $items ) ) {
				continue;
			}

			$already_sent = get_user_meta( $row->user_id, self::REMINDER_SENT_KEY, true );
			$already_sent = is_array( $already_sent ) ? $already_sent : array();

			$due_products = array();
			foreach ( $items as $product_id => $added_time ) {
				if ( $added_time <= $cutoff && empty( $already_sent[ $product_id ] ) ) {
					$due_products[] = $product_id;
					$already_sent[ $product_id ] = true;
				}
			}

			if ( empty( $due_products ) ) {
				continue;
			}

			$user = get_user_by( 'id', $row->user_id );
			if ( $user && is_email( $user->user_email ) ) {
				$this->send_reminder_email( $user, $due_products );
				update_user_meta( $row->user_id, self::REMINDER_SENT_KEY, $already_sent );
			}
		}
	}

	private function send_reminder_email( $user, $product_ids ) {

		$wishlist_page = get_page_by_path( 'wishlist' );
		$wishlist_url  = $wishlist_page ? get_permalink( $wishlist_page ) : home_url( '/wishlist/' );

		$lines = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$lines[] = '- ' . $product->get_name() . ' (' . wp_strip_all_tags( $product->get_price_html() ) . ')';
			}
		}

		if ( empty( $lines ) ) {
			return;
		}

		$subject = sprintf( /* translators: %s: store name */ __( 'Still thinking about it? Your saved items at %s', 'auto-commerce-wishlist' ), get_bloginfo( 'name' ) );

		$body  = sprintf( __( 'Hi %s,', 'auto-commerce-wishlist' ), $user->display_name ) . "\n\n";
		$body .= __( "We noticed a few pieces are still waiting in your wishlist:", 'auto-commerce-wishlist' ) . "\n\n";
		$body .= implode( "\n", $lines ) . "\n\n";
		$body .= __( 'View your wishlist here:', 'auto-commerce-wishlist' ) . ' ' . $wishlist_url . "\n\n";
		$body .= __( 'Thank you for shopping with us!', 'auto-commerce-wishlist' ) . "\n" . get_bloginfo( 'name' );

		wp_mail( $user->user_email, $subject, $body );
	}

	/* ---------------------------------------------------------------------
	 * Back-in-stock notifications
	 * ------------------------------------------------------------------- */

	public function maybe_notify_back_in_stock( $product_id, $status, $product ) {

		if ( 'instock' !== $status ) {
			return;
		}

		global $wpdb;

		$meta_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
				PB_Wishlist::META_KEY
			)
		);

		if ( empty( $meta_rows ) ) {
			return;
		}

		foreach ( $meta_rows as $row ) {
			$items = maybe_unserialize( $row->meta_value );
			if ( ! is_array( $items ) || ! array_key_exists( $product_id, $items ) ) {
				continue;
			}

			$notified = get_user_meta( $row->user_id, self::STOCK_NOTIFIED_KEY, true );
			$notified = is_array( $notified ) ? $notified : array();

			if ( ! empty( $notified[ $product_id ] ) ) {
				continue; // already told them about this restock
			}

			$user = get_user_by( 'id', $row->user_id );
			if ( $user && is_email( $user->user_email ) ) {
				$this->send_back_in_stock_email( $user, $product_id );
				$notified[ $product_id ] = true;
				update_user_meta( $row->user_id, self::STOCK_NOTIFIED_KEY, $notified );
			}
		}
	}

	private function send_back_in_stock_email( $user, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$subject = sprintf( /* translators: %s: product name */ __( 'Back in stock: %s', 'auto-commerce-wishlist' ), $product->get_name() );

		$body  = sprintf( __( 'Hi %s,', 'auto-commerce-wishlist' ), $user->display_name ) . "\n\n";
		$body .= sprintf( __( 'Good news — an item from your wishlist is back in stock: %s', 'auto-commerce-wishlist' ), $product->get_name() ) . "\n\n";
		$body .= wp_strip_all_tags( $product->get_price_html() ) . "\n";
		$body .= get_permalink( $product_id ) . "\n\n";
		$body .= __( 'Grab it before it sells out again!', 'auto-commerce-wishlist' ) . "\n" . get_bloginfo( 'name' );

		wp_mail( $user->user_email, $subject, $body );
	}
}
