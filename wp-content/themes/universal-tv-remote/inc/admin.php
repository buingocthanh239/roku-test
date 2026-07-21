<?php
/**
 * wp-admin editing UX for `tv_brand`: a lightweight "Parent Brand" picker
 * (AJAX search over post titles, writes straight to post_parent) standing in
 * for the native hierarchical "Parent" dropdown — that control is gated on
 * hierarchical=true (which we can't use, see inc/cpt-tv-brand.php) and a
 * plain <select> wouldn't scale to 1200 rows anyway. Also a Wi-Fi-support
 * checkbox and two admin list columns.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'tvr_add_meta_boxes' );
function tvr_add_meta_boxes() {
	add_meta_box( 'tvr_details', 'TV Details', 'tvr_render_meta_box', 'tv_brand', 'side', 'default' );
}

function tvr_render_meta_box( $post ) {
	wp_nonce_field( 'tvr_save_meta', 'tvr_meta_nonce' );
	$totp         = get_post_meta( $post->ID, '_tv_totp', true );
	$parent_id    = $post->post_parent;
	$parent_title = $parent_id ? get_the_title( $parent_id ) : '';
	?>
	<p>
		<label>
			<input type="checkbox" name="tvr_totp" value="1" <?php checked( $totp, '1' ); ?> />
			Wi-Fi control supported
		</label>
	</p>
	<p>
		<label for="tvr_parent_search"><strong>Parent Brand</strong></label><br />
		<input type="text" id="tvr_parent_search" autocomplete="off"
			value="<?php echo esc_attr( $parent_title ); ?>"
			placeholder="Leave empty for a top-level brand" style="width:100%" />
		<input type="hidden" name="tvr_parent_id" id="tvr_parent_id" value="<?php echo esc_attr( $parent_id ); ?>" />
		<span id="tvr_parent_results" style="display:block;"></span>
		<span style="font-size:11px;color:#666;display:block;margin-top:4px;">
			Set this for a specific TV model (e.g. "Sony A95K") so it nests under its brand (e.g. "Sony").
		</span>
	</p>
	<script>
	(function () {
		var search  = document.getElementById('tvr_parent_search');
		var hidden  = document.getElementById('tvr_parent_id');
		var results = document.getElementById('tvr_parent_results');
		var timer;
		search.addEventListener('input', function () {
			clearTimeout(timer);
			var q = search.value.trim();
			hidden.value = '';
			if (q.length < 2) { results.innerHTML = ''; return; }
			timer = setTimeout(function () {
				var url = ajaxurl + '?action=tvr_search_brands&q=' + encodeURIComponent(q) + '&exclude=<?php echo (int) $post->ID; ?>';
				fetch(url).then(function (r) { return r.json(); }).then(function (items) {
					results.innerHTML = '';
					items.forEach(function (item) {
						var div = document.createElement('div');
						div.textContent = item.title;
						div.style.cssText = 'padding:4px 6px;cursor:pointer;border:1px solid #ddd;border-top:none;background:#fff;';
						div.addEventListener('click', function () {
							search.value = item.title;
							hidden.value = item.id;
							results.innerHTML = '';
						});
						results.appendChild(div);
					});
				});
			}, 250);
		});
	})();
	</script>
	<?php
}

add_action( 'wp_ajax_tvr_search_brands', 'tvr_ajax_search_brands' );
function tvr_ajax_search_brands() {
	if ( ! current_user_can( 'edit_posts' ) ) wp_send_json( array() );
	$q       = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$exclude = isset( $_GET['exclude'] ) ? (int) $_GET['exclude'] : 0;
	$posts   = get_posts( array(
		'post_type'      => 'tv_brand',
		's'              => $q,
		'posts_per_page' => 15,
		'post__not_in'   => $exclude ? array( $exclude ) : array(),
		'post_status'    => 'publish',
	) );
	wp_send_json( array_map( function ( $p ) {
		return array( 'id' => $p->ID, 'title' => $p->post_title );
	}, $posts ) );
}

add_action( 'save_post_tv_brand', 'tvr_save_meta' );
function tvr_save_meta( $post_id ) {
	if ( ! isset( $_POST['tvr_meta_nonce'] ) || ! wp_verify_nonce( $_POST['tvr_meta_nonce'], 'tvr_save_meta' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	update_post_meta( $post_id, '_tv_totp', isset( $_POST['tvr_totp'] ) ? '1' : '0' );

	$parent_id = isset( $_POST['tvr_parent_id'] ) ? (int) $_POST['tvr_parent_id'] : 0;
	if ( $parent_id !== (int) wp_get_post_parent_id( $post_id ) ) {
		remove_action( 'save_post_tv_brand', 'tvr_save_meta' );
		wp_update_post( array( 'ID' => $post_id, 'post_parent' => $parent_id ) );
		add_action( 'save_post_tv_brand', 'tvr_save_meta' );
	}
}

// Admin list columns.
add_filter( 'manage_tv_brand_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $key => $label ) {
		$new[ $key ] = $label;
		if ( $key === 'title' ) {
			$new['tvr_parent'] = 'Parent Brand';
			$new['tvr_totp']   = 'Wi-Fi';
		}
	}
	return $new;
} );

add_action( 'manage_tv_brand_posts_custom_column', function ( $column, $post_id ) {
	if ( $column === 'tvr_parent' ) {
		$parent = wp_get_post_parent_id( $post_id );
		echo $parent ? esc_html( get_the_title( $parent ) ) : '&mdash;';
	}
	if ( $column === 'tvr_totp' ) {
		echo get_post_meta( $post_id, '_tv_totp', true ) === '1' ? '&#10003;' : '&mdash;';
	}
}, 10, 2 );

add_filter( 'manage_edit-tv_brand_sortable_columns', function ( $cols ) {
	$cols['tvr_parent'] = 'parent';
	return $cols;
} );
