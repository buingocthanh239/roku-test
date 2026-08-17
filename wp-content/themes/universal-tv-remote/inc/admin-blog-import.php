<?php
/**
 * wp-admin trigger for the content-drops/ -> `post` (Draft) import (Posts ->
 * Import from Folder) — upload + a button instead of running
 * scripts/import-blog-posts.php from Terminal or needing separate file
 * (FTP/hosting panel) access. Actual parsing/import/validation logic lives
 * in inc/blog-import.php, shared with the CLI script.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php',
		'Import Blog Posts',
		'Import from Folder',
		'edit_posts',
		'tvr-import-blog-posts',
		'tvr_render_import_blog_posts_page'
	);
} );

/*
 * A second "Add Folder Post" button right next to core's native "Add Post"
 * button on Posts -> All Posts, so the bulk-import entry point is visible
 * without going through the sidebar submenu first. WP core's edit.php
 * (wp-admin/edit.php) has no action hook positioned right after that
 * button in this version — it's hard-coded HTML — so this appends a
 * second .page-title-action link via a small footer script instead,
 * same lightweight-JS-injection pattern already used elsewhere in this
 * theme (inc/admin.php's Parent Brand search box, inc/cta-button.php's
 * TinyMCE button).
 */
add_action( 'admin_footer-edit.php', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-post' !== $screen->id ) return;
	if ( ! current_user_can( 'edit_posts' ) ) return;
	// No `&post_type=post` here even though that's the screen we're on: WP
	// core's admin.php dispatcher computes a *different* expected hook name
	// when `$_REQUEST['post_type']` is set (it looks for the page under
	// parent `"edit.php?post_type=post"` instead of plain `"edit.php"`,
	// which add_submenu_page() registered it under) — adding that param
	// back breaks the link with a "Cannot load ..." wp_die(). `post` is
	// edit.php's default post type anyway, so the bare URL is equivalent.
	$url = admin_url( 'edit.php?page=tvr-import-blog-posts' );
	?>
	<script>
	(function () {
		var addNew = document.querySelector( '.wrap .page-title-action' );
		if ( ! addNew ) return;
		var btn = document.createElement( 'a' );
		btn.href = <?php echo wp_json_encode( $url ); ?>;
		btn.className = 'page-title-action';
		btn.textContent = 'Add Folder Post';
		addNew.insertAdjacentElement( 'afterend', btn );
	})();
	</script>
	<?php
} );

/**
 * Only these extensions are ever written to content-drops/ by the uploader
 * — never anything executable, regardless of what a browser reports as the
 * file's name/type. This is the actual security boundary (file-execution on
 * typical Apache/nginx setups is decided by the file's extension, not its
 * content), so this list must never grow to include anything the webserver
 * would execute (.php, .phtml, .cgi, ...).
 */
function tvr_blog_upload_allowed_extensions() {
	return array( 'docx', 'txt', 'jpg', 'jpeg', 'png', 'webp', 'gif' );
}

/**
 * Receives one content-drops/<slug>/ folder's files per request (the JS
 * side chunks by folder, see tvr_render_import_blog_posts_page()) — partly
 * for clear per-folder progress, partly to stay well under PHP's
 * max_file_uploads (20 by default; a single giant multi-folder request
 * could silently lose files past that limit with no error at all).
 */
add_action( 'wp_ajax_tvr_upload_blog_folder', function () {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'tvr_upload_blog_folder', 'nonce' );

	if ( empty( $_FILES['files'] ) || empty( $_POST['filenames'] ) ) {
		wp_send_json_error( array( 'message' => 'No files received.' ) );
	}

	$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
	if ( '' === $slug ) {
		wp_send_json_error( array( 'message' => 'Missing/invalid folder name.' ) );
	}

	$filenames = array_map( 'sanitize_file_name', wp_unslash( (array) $_POST['filenames'] ) );
	$files     = $_FILES['files'];
	$count     = is_array( $files['name'] ) ? count( $files['name'] ) : 0;

	if ( 0 === $count || $count !== count( $filenames ) ) {
		wp_send_json_error( array( 'message' => 'Mismatched file list.' ) );
	}

	$allowed_ext = tvr_blog_upload_allowed_extensions();
	$folder_dir  = tvr_blog_content_drops_dir() . '/' . $slug;

	$saved  = array();
	$errors = array();

	for ( $i = 0; $i < $count; $i++ ) {
		$filename = $filenames[ $i ];
		$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( UPLOAD_ERR_OK !== $files['error'][ $i ] ) {
			$errors[] = "$filename: upload error";
			continue;
		}
		if ( '' === $filename || ! in_array( $ext, $allowed_ext, true ) ) {
			$errors[] = "$filename: skipped (file type not allowed)";
			continue;
		}

		if ( ! file_exists( $folder_dir ) ) wp_mkdir_p( $folder_dir );

		$dest = $folder_dir . '/' . $filename;
		if ( ! move_uploaded_file( $files['tmp_name'][ $i ], $dest ) ) {
			$errors[] = "$filename: failed to save on server";
			continue;
		}
		$saved[] = $filename;
	}

	wp_send_json_success( array(
		'slug'   => $slug,
		'saved'  => $saved,
		'errors' => $errors,
	) );
} );

function tvr_render_import_blog_posts_page() {
	if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Not allowed.' );

	$results = null;

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		check_admin_referer( 'tvr_run_blog_import' );
		// A large batch (many folders x several images each) can easily run
		// past PHP's default 30s admin request limit — this button needs to
		// survive a genuinely "bulk" run.
		if ( ! ini_get( 'safe_mode' ) ) @set_time_limit( 0 );
		$results = tvr_blog_import_run_all();
	}

	$folders          = tvr_blog_list_content_drop_folders();
	$upload_nonce     = wp_create_nonce( 'tvr_upload_blog_folder' );
	$allowed_ext_list = implode( ', ', tvr_blog_upload_allowed_extensions() );
	?>
	<div class="wrap">
		<h1>Import Blog Posts from Folder</h1>
		<p>
			See <code>content-drops/README.md</code> for the folder convention
			(<code>content.docx</code> + <code>feature.&lt;ext&gt;</code> + inline
			images + <code>meta.txt</code>). Every post is created/updated as a
			<strong>Draft</strong> — nothing here is ever published. SEO Title,
			Meta Description and Focus Keyword are not touched; set those in the
			Rank Math box after reviewing each Draft.
		</p>

		<?php if ( null !== $results ) : ?>
			<h2>Import results</h2>
			<?php if ( empty( $results ) ) : ?>
				<p><em>No folders found to import.</em></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead><tr><th style="width:25%">Folder</th><th>Result</th></tr></thead>
					<tbody>
					<?php foreach ( $results as $r ) : ?>
						<tr>
							<td><code><?php echo esc_html( $r['slug'] ); ?></code></td>
							<td>
								<?php if ( 'error' === $r['status'] ) : ?>
									<span style="color:#b32d2e;">✗ Error:</span> <?php echo esc_html( $r['message'] ); ?>
								<?php else : ?>
									<span style="color:#008a20;">✓ <?php echo esc_html( ucfirst( $r['status'] ) ); ?></span>
									— <a href="<?php echo esc_url( $r['message'] ); ?>">Edit post</a>
									<?php if ( $r['orphaned'] ) : ?>
										<br /><span style="color:#996800;">Warning: image(s) never referenced in content.docx: <?php echo esc_html( implode( ', ', $r['orphaned'] ) ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<hr />
		<?php endif; ?>

		<h2>Upload folders</h2>
		<p>
			Pick either one post's folder directly, or a parent folder containing
			several post folders at once (for bulk upload) — each gets copied into
			<code><?php echo esc_html( tvr_blog_content_drops_dir() ); ?></code> on
			the server, file by file, folder by folder. Allowed file types:
			<code><?php echo esc_html( $allowed_ext_list ); ?></code> — anything
			else (including OS junk like <code>.DS_Store</code>) is skipped.
		</p>
		<input type="file" id="tvr-folder-upload-input" webkitdirectory multiple style="display:none" />
		<button type="button" id="tvr-folder-upload-btn" class="button button-secondary">Choose Folder to Upload</button>
		<div id="tvr-upload-status" style="margin-top:10px;max-width:700px;"></div>

		<hr />

		<h2>Folders ready to import</h2>
		<?php if ( empty( $folders ) ) : ?>
			<p><em>No folders in content-drops/ right now.</em></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped" style="max-width:900px;">
				<thead><tr><th style="width:20%">Folder</th><th>Status</th></tr></thead>
				<tbody>
				<?php foreach ( $folders as $dir ) :
					$slug      = basename( $dir );
					$validated = tvr_blog_validate_folder( $dir );
				?>
					<tr>
						<td><code><?php echo esc_html( $slug ); ?></code></td>
						<td>
							<?php if ( is_wp_error( $validated ) ) : ?>
								<span style="color:#b32d2e;">✗ <?php echo esc_html( $validated->get_error_message() ); ?></span>
							<?php else : ?>
								<span style="color:#008a20;">✓ Ready</span>
								— "<?php echo esc_html( $validated['parsed']['title'] ); ?>"
								(<?php echo count( $validated['parsed']['images'] ); ?> inline image<?php echo 1 === count( $validated['parsed']['images'] ) ? '' : 's'; ?>)
								<?php echo $validated['existing'] ? ' — will <strong>update</strong> an existing draft' : ' — will <strong>create</strong> a new draft'; ?>
								<?php if ( $validated['feature_auto'] ) : ?>
									<br /><span style="color:#996800;">Featured image: auto-picked <code><?php echo esc_html( basename( $validated['feature_path'] ) ); ?></code> (no <code>[feature: ...]</code> marker or <code>feature.&lt;ext&gt;</code> file — add one to choose a different image instead)</span>
								<?php endif; ?>
								<?php if ( $validated['orphaned'] ) : ?>
									<br /><span style="color:#996800;">Warning: image(s) never referenced in the docx: <?php echo esc_html( implode( ', ', $validated['orphaned'] ) ); ?></span>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" style="margin-top:16px;">
				<?php wp_nonce_field( 'tvr_run_blog_import' ); ?>
				<?php submit_button( 'Run Import (' . count( $folders ) . ' folder' . ( 1 === count( $folders ) ? '' : 's' ) . ')', 'primary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	</div>
	<script>
	(function () {
		var input  = document.getElementById( 'tvr-folder-upload-input' );
		var button = document.getElementById( 'tvr-folder-upload-btn' );
		var status = document.getElementById( 'tvr-upload-status' );
		var nonce  = <?php echo wp_json_encode( $upload_nonce ); ?>;

		button.addEventListener( 'click', function () { input.click(); } );

		// Group the flat FileList (each file carries its full
		// webkitRelativePath, e.g. "picked-folder/content.docx" if a single
		// post's folder was selected directly, or
		// "picked-folder/post-slug/content.docx" if a parent folder holding
		// several post folders was selected) into { slug: [file, ...] }.
		function groupBySlug( fileList ) {
			var groups = {};
			Array.prototype.forEach.call( fileList, function ( file ) {
				var parts = file.webkitRelativePath.split( '/' );
				var slug, filename;
				if ( parts.length <= 2 ) {
					slug = parts[ 0 ];
					filename = parts[ parts.length - 1 ];
				} else {
					slug = parts[ 1 ];
					filename = parts[ parts.length - 1 ];
				}
				if ( ! filename || filename.charAt( 0 ) === '.' ) return; // OS junk (.DS_Store, ._*)
				if ( ! groups[ slug ] ) groups[ slug ] = [];
				groups[ slug ].push( file );
			} );
			return groups;
		}

		function uploadFolder( slug, files ) {
			var formData = new FormData();
			formData.append( 'action', 'tvr_upload_blog_folder' );
			formData.append( 'nonce', nonce );
			formData.append( 'slug', slug );
			files.forEach( function ( file ) {
				formData.append( 'files[]', file, file.name );
				formData.append( 'filenames[]', file.name );
			} );
			return fetch( ajaxurl, { method: 'POST', body: formData, credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } );
		}

		input.addEventListener( 'change', function () {
			var groups = groupBySlug( input.files );
			var slugs  = Object.keys( groups );
			if ( ! slugs.length ) return;

			button.disabled = true;
			var done = 0;
			var lines = [];
			status.innerHTML = 'Uploading ' + slugs.length + ' folder(s)…';

			function next() {
				if ( done >= slugs.length ) {
					status.innerHTML = lines.join( '<br>' ) + '<br><strong>Done — reloading…</strong>';
					window.location.reload();
					return;
				}
				var slug = slugs[ done ];
				uploadFolder( slug, groups[ slug ] ).then( function ( res ) {
					done++;
					if ( res.success ) {
						var line = '✓ ' + slug + ': ' + res.data.saved.length + ' file(s) uploaded';
						if ( res.data.errors.length ) line += ' — ' + res.data.errors.join( '; ' );
						lines.push( line );
					} else {
						lines.push( '✗ ' + slug + ': ' + ( res.data && res.data.message ? res.data.message : 'upload failed' ) );
					}
					status.innerHTML = lines.join( '<br>' ) + '<br>Uploading… (' + done + '/' + slugs.length + ')';
					next();
				} ).catch( function ( err ) {
					done++;
					lines.push( '✗ ' + slug + ': ' + err.message );
					status.innerHTML = lines.join( '<br>' );
					next();
				} );
			}
			next();
		} );
	})();
	</script>
	<?php
}
