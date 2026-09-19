<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PB_Wishlist_Share
 *
 * Turns the current wishlist into a shareable link (?pb_share=...) that
 * anyone can open in a read-only view — handy for jewelry, where people
 * often want to hint at or share a wishlist with a partner or family
 * member (e.g. engagement ring shopping).
 */
class PB_Wishlist_Share {

	/**
	 * Encodes a list of product IDs into a compact, URL-safe token.
	 * No login or database record needed — the token itself carries the data.
	 */
	public static function encode_ids( $ids ) {
		$ids = array_slice( array_unique( array_filter( array_map( 'absint', $ids ) ) ), 0, 100 );
		return rtrim( strtr( base64_encode( implode( ',', $ids ) ), '+/', '-_' ), '=' );
	}

	public static function decode_ids( $token ) {
		$b64 = strtr( $token, '-_', '+/' );
		$pad = strlen( $b64 ) % 4;
		if ( $pad ) {
			$b64 .= str_repeat( '=', 4 - $pad );
		}
		$decoded = base64_decode( $b64, true );
		if ( false === $decoded ) {
			return array();
		}
		$ids = array_filter( array_map( 'absint', explode( ',', $decoded ) ) );
		return array_values( $ids );
	}

	public static function get_share_url( $ids ) {
		$wishlist_page = PB_Wishlist_Settings::get_wishlist_page_id();
		$base          = $wishlist_page ? get_permalink( $wishlist_page ) : home_url( '/wishlist/' );
		return add_query_arg( 'pb_share', self::encode_ids( $ids ), $base );
	}

	/**
	 * Renders the "Share Wishlist" (copy link) and "Share on WhatsApp" buttons
	 * shown at the top of the wishlist page.
	 */
	public static function render_share_buttons( $ids ) {
		if ( empty( $ids ) ) {
			return '';
		}

		$share_url    = self::get_share_url( $ids );
		$whatsapp_msg = rawurlencode( sprintf( __( 'Take a look at my wishlist from %s:', 'auto-commerce-wishlist' ), get_bloginfo( 'name' ) ) . ' ' . $share_url );
		$whatsapp_url = 'https://wa.me/?text=' . $whatsapp_msg;

		ob_start();
		?>
		<button type="button" class="button auto-commerce-wishlist-share-copy" data-url="<?php echo esc_url( $share_url ); ?>">
			<?php esc_html_e( 'Share Wishlist', 'auto-commerce-wishlist' ); ?>
		</button>
		<a class="button auto-commerce-wishlist-share-whatsapp" href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Share on WhatsApp', 'auto-commerce-wishlist' ); ?>
		</a>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the read-only view someone sees when they open a shared link.
	 * Deliberately has no remove/toggle controls — it's someone else's list.
	 */
	public static function render_shared_view( $token ) {

		$ids = self::decode_ids( $token );

		if ( empty( $ids ) ) {
			return '<p class="auto-commerce-wishlist-empty">' . esc_html__( 'This wishlist link looks invalid or has expired.', 'auto-commerce-wishlist' ) . '</p>';
		}

		ob_start();
		?>
		<p class="auto-commerce-wishlist-shared-note"><?php esc_html_e( 'Someone shared this wishlist with you:', 'auto-commerce-wishlist' ); ?></p>
		<div class="auto-commerce-wishlist-grid">
			<?php foreach ( $ids as $product_id ) :
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
					<a class="button" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>">
						<?php esc_html_e( 'Add to Cart', 'auto-commerce-wishlist' ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
