<?php
/**
 * "Live Preview" side meta box — an iframe on the post/page edit screen
 * pointed at WordPress's own preview URL, so a writer can see roughly how
 * the page (including any [tvr_cta] buttons) will actually render without
 * leaving the editor. Refreshes automatically right after WordPress's own
 * autosave fires (the "after-autosave" jQuery event wp-autosave.js already
 * triggers on every edit screen — no extra enqueue needed), plus a manual
 * button for an immediate check.
 *
 * Split-screen layout: at desktop widths (>=1400px — wide enough for WP's
 * own #poststuff min-width:763px, its 160px admin menu, and this panel to
 * all fit without collision), tvr_live_preview_split_css() takes this
 * specific box OUT of WP's normal metabox flow entirely (position:fixed,
 * pinned to the right edge of the viewport, 420px wide) instead of trying to
 * resize WP's float-based #postbox-container-1 column to fit it. Below
 * 1400px this degrades to the plain stacked WP sidebar with a fixed-height
 * iframe.
 *
 * #wpbody-content is narrowed with `width: calc(100% - 420px)`, NOT
 * `margin-right` — #wpbody-content is a float with an explicit `width:100%`
 * in WP core CSS, and a float's width/margin are used as specified (no
 * auto-balancing the way normal-flow blocks get), so a margin-right on it
 * only adds dead space after an unshrunk box instead of narrowing it. An
 * earlier version used margin-right here (plus `margin-right:0 !important`
 * overrides on #post-body.columns-2 and #postbox-container-1 to cancel WP's
 * 300px/-300px sidebar gutter trick, wrongly believing that trick "no longer
 * matched reality" at a narrower width). Because the margin-right on
 * #wpbody-content never actually took effect, #post-body still rendered at
 * full width, so cancelling the gutter made #postbox-container-1 wrap onto a
 * new line — landing directly underneath this fixed, opaque panel and
 * hiding the Publish box, Categories, Tags, Featured image, and Heading
 * Style fields completely. Narrowing via `width` instead makes #wpbody-content
 * genuinely smaller, so WP's own gutter trick keeps working unmodified and
 * the sidebar renders beside the content, to the left of this panel, exactly
 * like a normal (narrower) WP edit screen.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'tvr_live_preview', 'Live Preview', 'tvr_render_live_preview_box', array( 'post', 'page' ), 'side', 'high' );
} );

add_action( 'admin_head-post.php', 'tvr_live_preview_split_css' );
add_action( 'admin_head-post-new.php', 'tvr_live_preview_split_css' );
function tvr_live_preview_split_css() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) return;
	?>
	<style>
		@media (min-width: 1400px) {
			/* #wpbody-content is a float with an explicit width:100% in WP
			   core CSS, so margin-right alone doesn't shrink it (a float's
			   width/margins are used as specified, not auto-balanced) — it
			   must be narrowed via width so WP's own #post-body.columns-2
			   sidebar gutter (300px / -300px) keeps working unmodified and
			   the sidebar renders beside the content instead of wrapping
			   underneath this panel. */
			#wpbody-content {
				width: calc(100% - 420px) !important;
			}
			#tvr_live_preview {
				position: fixed;
				top: 32px;
				right: 0;
				width: 420px;
				height: calc(100vh - 32px);
				margin: 0;
				overflow-y: auto;
				background: #fff;
				z-index: 100;
				border-left: 1px solid #dcdcde;
				box-sizing: border-box;
			}
			#tvr_live_preview.closed {
				height: auto;
			}
			#tvr-live-preview-frame {
				height: calc(100vh - 220px) !important;
			}
		}
	</style>
	<?php
}

// The preview is viewed while logged in, so WP would otherwise render the
// black wpadminbar inside the iframe too (eating vertical space, and it's
// not part of what a visitor would ever actually see). Strip it just for
// this iframe via a marker query var, not for real logged-in visits.
add_filter( 'show_admin_bar', function ( $show ) {
	return isset( $_GET['tvr_preview_frame'] ) ? false : $show;
} );

function tvr_render_live_preview_box( $post ) {
	if ( $post->post_status === 'auto-draft' ) {
		echo '<p style="font-size:12px;color:#666;">Lưu nháp lần đầu (gõ vài chữ rồi đợi vài giây để tự động lưu) để bắt đầu xem preview.</p>';
		return;
	}

	$preview_url = add_query_arg( 'tvr_preview_frame', '1', get_preview_post_link( $post ) );
	// No tvr_preview_frame marker here — this is the real WP preview link
	// (admin bar and all), and the target name matches the one WP's own
	// core "Preview" button in the Publish box uses, so clicking either one
	// reuses/updates the same browser tab instead of piling up new tabs.
	$real_preview_url = get_preview_post_link( $post );
	?>
	<iframe
		id="tvr-live-preview-frame"
		src="<?php echo esc_url( $preview_url ); ?>"
		style="width:100%;height:600px;border:1px solid #dcdcde;border-radius:4px;background:#fff;"
	></iframe>
	<p style="margin-top:8px;">
		<button type="button" class="button" id="tvr-live-preview-refresh">Refresh preview</button>
		<a href="<?php echo esc_url( $real_preview_url ); ?>" target="wp-preview-<?php echo (int) $post->ID; ?>" rel="noopener" class="button">Mở tab mới</a>
	</p>
	<script>
	( function () {
		var frame   = document.getElementById( 'tvr-live-preview-frame' );
		var baseSrc = frame.getAttribute( 'src' );

		function refresh() {
			var sep = baseSrc.indexOf( '?' ) === -1 ? '?' : '&';
			frame.src = baseSrc + sep + '_tvr_ts=' + Date.now();
		}

		document.getElementById( 'tvr-live-preview-refresh' ).addEventListener( 'click', refresh );
		if ( window.jQuery ) {
			jQuery( document ).on( 'after-autosave', refresh );
		}
	} )();
	</script>
	<?php
}
