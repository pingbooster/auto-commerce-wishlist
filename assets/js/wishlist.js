(function ($) {
	'use strict';

	function toggle(productId, $btn) {
		$.post(AutoCommerceWishlist.ajaxUrl, {
			action: 'auto_commerce_wishlist_toggle',
			nonce: AutoCommerceWishlist.nonce,
			product_id: productId
		}).done(function (res) {
			if (!res.success) {
				return;
			}

			var selector = '.auto-commerce-wishlist-btn[data-product-id="' + productId + '"]';
			var $matchingButtons = $(selector);
			if (res.data.action === 'added') {
				$matchingButtons.addClass('auto-commerce-wishlist-active')
					.attr('aria-pressed', 'true')
					.find('.auto-commerce-wishlist-label').text('Saved');
				announce('Product added to wishlist.');
			} else {
				$matchingButtons.removeClass('auto-commerce-wishlist-active')
					.attr('aria-pressed', 'false')
					.find('.auto-commerce-wishlist-label').text('Add to Wishlist');
				announce('Product removed from wishlist.');

				// If we're on the wishlist page itself, remove the whole card.
				$btn.closest('.auto-commerce-wishlist-item').fadeOut(200, function () {
					$(this).remove();
					if ($('.auto-commerce-wishlist-item').length === 0) {
						$('.auto-commerce-wishlist-grid').replaceWith(
							'<p class="auto-commerce-wishlist-empty">Your wishlist is empty. Browse our collection and tap the heart icon on any piece to save it here.</p>'
						);
					}
				});
			}
			$('.auto-commerce-wishlist-menu-badge').text(res.data.count);
		});
	}

	function announce(message) {
		var $status = $('.auto-commerce-wishlist-status');
		if (!$status.length) {
			$status = $('<div class="auto-commerce-wishlist-status" role="status" aria-live="polite"></div>').appendTo('body');
		}
		$status.text(message);
	}

	$(document).on('click', '.auto-commerce-wishlist-btn', function (e) {
		e.preventDefault();
		toggle($(this).data('product-id'), $(this));
	});

	$(document).on('click', '.auto-commerce-wishlist-remove', function (e) {
		e.preventDefault();
		toggle($(this).data('product-id'), $(this));
	});

	$(document).on('click', '.auto-commerce-wishlist-move-all', function (e) {
		e.preventDefault();
		var $btn = $(this).prop('disabled', true).text('Moving...');

		$.post(AutoCommerceWishlist.ajaxUrl, {
			action: 'auto_commerce_wishlist_move_all',
			nonce: AutoCommerceWishlist.nonce
		}).done(function (res) {
			if (res.success && res.data.added > 0) {
				window.location.href = res.data.cart_url;
			} else {
				$btn.prop('disabled', false).text('Move All to Cart');
				alert('No available items could be moved to the cart.');
			}
		});
	});

	$(document).on('click', '.auto-commerce-wishlist-share-copy', function (e) {
		e.preventDefault();
		var url = $(this).data('url');
		var $btn = $(this);

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(function () {
				var original = $btn.text();
				$btn.text('Link Copied!');
				setTimeout(function () { $btn.text(original); }, 2000);
			});
		} else {
			window.prompt('Copy this link:', url);
		}
	});

})(jQuery);
