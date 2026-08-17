<?php
/**
 * Shared logic for the content-drops/ -> `post` (Draft) import pipeline.
 * Loaded on every request (like the rest of inc/) so both the CLI script
 * (scripts/import-blog-posts.php) and the wp-admin "Import from Folder"
 * page (inc/admin-blog-import.php, Posts -> Import from Folder) call the
 * exact same parsing/import code — see content-drops-README.md for the
 * folder convention this parses.
 *
 * .docx is parsed by hand (ZipArchive + DOMDocument over word/document.xml
 * + word/numbering.xml) rather than shelling out to macOS `textutil`:
 * textutil's docx->html conversion flattens Word's Heading styles into
 * styled <p> tags, not real <h2>/<h3>.
 *
 * Deliberately does NOT touch SEO Title/Meta Description/Focus Keyword
 * (Rank Math meta box, human judgement call) or post_status beyond `draft`.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const TVR_DOCX_NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

/**
 * Lives under wp-content/uploads/, NOT inside the theme
 * (wp-content/themes/.../content-drops/, where it originally lived) —
 * production hosting (and most hardened WP setups generally) makes
 * wp-content/themes/ read-only to the PHP process for exactly the reason
 * this folder needs write access: uploads/ is the one directory WP itself
 * requires to always be writable, so it's the only safe/portable place for
 * runtime-uploaded content to live. Confirmed as the actual failure mode
 * in production ("failed to save on server" for every file) before moving
 * this — local dev's Local-by-Flywheel PHP process has no such
 * restriction, which is why it worked there without ever surfacing this.
 */
function tvr_blog_content_drops_dir() {
	return wp_upload_dir()['basedir'] . '/content-drops';
}

/**
 * Build a numId -> 'ul'|'ol' map from word/numbering.xml, using each list
 * definition's top level (ilvl 0) numFmt. Nested list levels aren't
 * supported — out of scope for blog-post body content.
 */
function tvr_docx_numbering_map( $numbering_xml ) {
	if ( ! $numbering_xml ) return array();

	$dom = new DOMDocument();
	$dom->loadXML( $numbering_xml, LIBXML_NOWARNING | LIBXML_NOERROR );
	$xpath = new DOMXPath( $dom );
	$xpath->registerNamespace( 'w', TVR_DOCX_NS_W );

	$abstract_fmt = array();
	foreach ( $xpath->query( '//w:abstractNum' ) as $abstract ) {
		$id   = $abstract->getAttributeNS( TVR_DOCX_NS_W, 'abstractNumId' );
		$lvl0 = $xpath->query( './w:lvl[@w:ilvl="0"]/w:numFmt', $abstract )->item( 0 );
		if ( $lvl0 ) {
			$abstract_fmt[ $id ] = $lvl0->getAttributeNS( TVR_DOCX_NS_W, 'val' );
		}
	}

	$num_map = array();
	foreach ( $xpath->query( '//w:num' ) as $num ) {
		$num_id  = $num->getAttributeNS( TVR_DOCX_NS_W, 'numId' );
		$abs_ref = $xpath->query( './w:abstractNumId', $num )->item( 0 );
		if ( ! $abs_ref ) continue;
		$abs_id             = $abs_ref->getAttributeNS( TVR_DOCX_NS_W, 'val' );
		$fmt                = $abstract_fmt[ $abs_id ] ?? 'bullet';
		$num_map[ $num_id ] = ( 'bullet' === $fmt ) ? 'ul' : 'ol';
	}
	return $num_map;
}

function tvr_docx_para_style( $p, $xpath ) {
	$node = $xpath->query( './w:pPr/w:pStyle', $p )->item( 0 );
	return $node ? $node->getAttributeNS( TVR_DOCX_NS_W, 'val' ) : '';
}

/**
 * Font size in half-points (OOXML's own unit — 34 = 17pt), from whichever
 * run/paragraph-mark formatting sets it first. Used as a heading-level
 * signal for docs that use direct formatting instead of named Heading
 * styles — this content pipeline's real docs have no `w:pStyle` at all
 * (confirmed by inspecting a real exported file), just font sizes.
 */
function tvr_docx_para_font_size( $p, $xpath ) {
	$node = $xpath->query( './/w:sz', $p )->item( 0 );
	return $node ? (int) $node->getAttributeNS( TVR_DOCX_NS_W, 'val' ) : 0;
}

function tvr_docx_para_list_type( $p, $xpath, $numbering_map ) {
	$node = $xpath->query( './w:pPr/w:numPr/w:numId', $p )->item( 0 );
	if ( ! $node ) return null;
	$num_id = $node->getAttributeNS( TVR_DOCX_NS_W, 'val' );
	return $numbering_map[ $num_id ] ?? 'ul';
}

function tvr_docx_para_plain_text( $p, $xpath ) {
	$text = '';
	foreach ( $xpath->query( './/w:t', $p ) as $t ) $text .= $t->textContent;
	return $text;
}

function tvr_docx_run_prop_off( $run, $xpath, $tag ) {
	$node = $xpath->query( "./w:rPr/$tag", $run )->item( 0 );
	if ( ! $node ) return false;
	return in_array( $node->getAttributeNS( TVR_DOCX_NS_W, 'val' ), array( '0', 'false' ), true );
}

/** Bold/italic runs -> <strong>/<em>, tabs/line-breaks preserved, text escaped. */
function tvr_docx_para_inline_html( $p, $xpath ) {
	$out = '';
	foreach ( $xpath->query( './w:r', $p ) as $run ) {
		$text = '';
		foreach ( $run->childNodes as $child ) {
			if ( 'w:t' === $child->nodeName ) $text .= $child->textContent;
			elseif ( 'w:tab' === $child->nodeName ) $text .= "\t";
			elseif ( 'w:br' === $child->nodeName ) $text .= "\n";
		}
		if ( '' === $text ) continue;

		$text = str_replace( "\n", '<br>', esc_html( $text ) );

		$bold   = $xpath->query( './w:rPr/w:b', $run )->length > 0 && ! tvr_docx_run_prop_off( $run, $xpath, 'w:b' );
		$italic = $xpath->query( './w:rPr/w:i', $run )->length > 0 && ! tvr_docx_run_prop_off( $run, $xpath, 'w:i' );
		if ( $bold ) $text = '<strong>' . $text . '</strong>';
		if ( $italic ) $text = '<em>' . $text . '</em>';

		$out .= $text;
	}
	return $out;
}

function tvr_docx_table_html( $tbl, $xpath ) {
	$rows = array();
	foreach ( $xpath->query( './w:tr', $tbl ) as $tr ) {
		$is_header = $xpath->query( './w:trPr/w:tblHeader', $tr )->length > 0;
		$tag       = $is_header ? 'th' : 'td';
		$cells     = array();
		foreach ( $xpath->query( './w:tc', $tr ) as $tc ) {
			$parts = array();
			foreach ( $xpath->query( './w:p', $tc ) as $p ) {
				$inner = tvr_docx_para_inline_html( $p, $xpath );
				if ( '' !== trim( $inner ) ) $parts[] = $inner;
			}
			$cells[] = "<$tag>" . implode( '<br>', $parts ) . "</$tag>";
		}
		$rows[] = '<tr>' . implode( '', $cells ) . '</tr>';
	}
	return '<table><tbody>' . implode( '', $rows ) . '</tbody></table>';
}

/** `[image: filename.ext | alt text]` on its own line/paragraph. */
function tvr_docx_image_marker( $plain ) {
	if ( ! preg_match( '/^\[image:\s*([^|\]]+?)\s*\|\s*([^\]]+?)\s*\]$/i', trim( $plain ), $m ) ) return null;
	return array( 'file' => trim( $m[1] ), 'alt' => trim( $m[2] ) );
}

/** `[feature: filename.ext]` or `[feature: filename.ext | alt text]` — alt is optional here. */
function tvr_docx_feature_marker( $plain ) {
	if ( ! preg_match( '/^\[feature:\s*([^|\]]+?)\s*(?:\|\s*([^\]]+?)\s*)?\]$/i', trim( $plain ), $m ) ) return null;
	return array( 'file' => trim( $m[1] ), 'alt' => isset( $m[2] ) ? trim( $m[2] ) : '' );
}

/**
 * This content pipeline's real docs open with a fixed metadata block —
 * labelled sections (each its own paragraph, larger + bold), in this
 * order, before the article body starts:
 *   Title / Meta Description / Tag / Main Keyword / Secondary Keywords / Outline
 * Recognized by exact (case-insensitive) label text, not by any style —
 * this template doesn't use named paragraph styles at all.
 */
function tvr_docx_metadata_labels() {
	return array(
		'title'              => 'title',
		'meta description'   => 'meta_description',
		'tag'                => 'tags',
		'tags'               => 'tags',
		'main keyword'       => 'focus_keyword',
		'focus keyword'      => 'focus_keyword',
		'secondary keywords' => 'secondary_keywords',
		'outline'            => 'outline',
	);
}

function tvr_docx_match_metadata_label( $plain ) {
	$labels = tvr_docx_metadata_labels();
	return $labels[ strtolower( trim( $plain ) ) ] ?? null;
}

/**
 * Parse a .docx into a post title + body HTML (with `<!--TVR_IMAGE_n-->`
 * placeholders for each `[image: ...]` marker, in document order) + the
 * ordered list of referenced images + an optional `[feature: ...]` marker
 * + SEO metadata. Two document shapes are supported:
 *
 * - This content pipeline's real docs: a fixed metadata block at the very
 *   top — Title / Meta Description / Tag / Main Keyword / Secondary
 *   Keywords / Outline, each its own paragraph (a bold label paragraph
 *   followed by one or more value/list paragraphs) — before the article
 *   body starts. Confirmed by inspecting a real exported file: it has no
 *   named paragraph styles (`w:pStyle`) anywhere, so headings inside the
 *   body are detected by font size instead (~17pt bold = H2, ~13pt bold =
 *   H3, matching what's actually in these docs). The body always repeats
 *   the title once more as a large heading right after Outline; that
 *   repeat is dropped since the metadata block already supplied it (and
 *   the theme already renders post_title as the page's real H1).
 * - A plain doc using actual Word Heading1/Heading2/Title/Heading3
 *   paragraph styles and no metadata block — the original, simpler shape
 *   this parser was first built for. Still supported as a fallback for
 *   anything that doesn't start with a recognized metadata label.
 *
 * @return array{title:string,html:string,images:array,feature:?array,metadata:array}|WP_Error
 */
function tvr_docx_extract( $docx_path ) {
	$zip = new ZipArchive();
	if ( true !== $zip->open( $docx_path ) ) {
		return new WP_Error( 'docx_open_failed', "Could not open $docx_path as a zip — is it a real .docx file?" );
	}
	$document_xml  = $zip->getFromName( 'word/document.xml' );
	$numbering_xml = $zip->getFromName( 'word/numbering.xml' );
	$zip->close();

	if ( false === $document_xml ) {
		return new WP_Error( 'docx_malformed', "word/document.xml missing from $docx_path" );
	}

	$numbering_map = tvr_docx_numbering_map( $numbering_xml );

	$dom = new DOMDocument();
	$dom->loadXML( $document_xml, LIBXML_NOWARNING | LIBXML_NOERROR );
	$xpath = new DOMXPath( $dom );
	$xpath->registerNamespace( 'w', TVR_DOCX_NS_W );

	$body_children = $xpath->query( '/w:document/w:body/*' );
	$node_count    = $body_children->length;

	// --- Pass 1: the metadata block, if this doc has one. ---
	$metadata_raw   = array();
	$current_label  = null;
	$found_metadata = false;
	$body_start     = 0;

	for ( $i = 0; $i < $node_count; $i++ ) {
		$node = $body_children->item( $i );
		if ( 'w:p' !== $node->nodeName ) break; // a table before any label - not this template

		$plain = trim( tvr_docx_para_plain_text( $node, $xpath ) );
		$label = tvr_docx_match_metadata_label( $plain );

		if ( $label ) {
			$current_label  = $label;
			$found_metadata = true;
			if ( ! isset( $metadata_raw[ $label ] ) ) $metadata_raw[ $label ] = array();
			$body_start = $i + 1;
			continue;
		}

		if ( ! $found_metadata ) break; // doesn't open with a recognized label at all

		if ( '' === $plain ) { $body_start = $i + 1; continue; }

		if ( 'outline' === $current_label ) {
			$is_list = $xpath->query( './w:pPr/w:numPr', $node )->length > 0;
			if ( ! $is_list ) break; // first non-list paragraph after Outline ends the block
		}

		$metadata_raw[ $current_label ][] = $plain;
		$body_start = $i + 1;
	}

	$metadata = array(
		'title'              => isset( $metadata_raw['title'] ) ? trim( implode( ' ', $metadata_raw['title'] ) ) : '',
		'meta_description'   => isset( $metadata_raw['meta_description'] ) ? trim( implode( ' ', $metadata_raw['meta_description'] ) ) : '',
		'focus_keyword'      => isset( $metadata_raw['focus_keyword'] ) ? trim( implode( ' ', $metadata_raw['focus_keyword'] ) ) : '',
		'tags'               => isset( $metadata_raw['tags'] ) ? trim( implode( ', ', $metadata_raw['tags'] ) ) : '',
		'secondary_keywords' => isset( $metadata_raw['secondary_keywords'] ) ? trim( implode( ', ', $metadata_raw['secondary_keywords'] ) ) : '',
	);

	// The body always repeats the title once more, as a large heading,
	// right after the metadata block — drop that repeat.
	if ( $found_metadata && $body_start < $node_count ) {
		$next = $body_children->item( $body_start );
		if ( $next && 'w:p' === $next->nodeName && tvr_docx_para_font_size( $next, $xpath ) >= 40 ) {
			$body_start++;
		}
	}

	// --- Pass 2: the article body. ---
	$title            = $metadata['title'];
	$html             = array();
	$images           = array();
	$feature          = null;
	$list_open        = null;
	$first_plain      = null;
	$first_html_index = null;

	$close_list = function () use ( &$html, &$list_open ) {
		if ( $list_open ) {
			$html[]    = "</{$list_open}>";
			$list_open = null;
		}
	};

	for ( $i = $body_start; $i < $node_count; $i++ ) {
		$node = $body_children->item( $i );

		if ( 'w:tbl' === $node->nodeName ) {
			$close_list();
			$html[] = tvr_docx_table_html( $node, $xpath );
			continue;
		}
		if ( 'w:p' !== $node->nodeName ) continue;

		$plain = trim( tvr_docx_para_plain_text( $node, $xpath ) );

		$feature_ref = tvr_docx_feature_marker( $plain );
		if ( $feature_ref ) {
			$close_list();
			if ( null === $feature ) $feature = $feature_ref; // first one wins
			continue;
		}

		$image_ref = tvr_docx_image_marker( $plain );
		if ( $image_ref ) {
			$close_list();
			$images[] = $image_ref;
			$html[]   = '<!--TVR_IMAGE_' . ( count( $images ) - 1 ) . '-->';
			continue;
		}

		if ( '' === $plain ) continue;

		$style     = tvr_docx_para_style( $node, $xpath );
		$size      = tvr_docx_para_font_size( $node, $xpath );
		$list_type = tvr_docx_para_list_type( $node, $xpath, $numbering_map );
		$inner     = tvr_docx_para_inline_html( $node, $xpath );

		if ( $list_type ) {
			if ( $list_open !== $list_type ) {
				$close_list();
				$html[]    = "<{$list_type}>";
				$list_open = $list_type;
			}
			$html[] = "<li>{$inner}</li>";
			continue;
		}

		$close_list();

		if ( 'Title' === $style && '' === $title ) {
			$title = $plain;
			continue;
		}

		// Heading2/Heading3 named styles (plain-Word fallback shape), or —
		// this pipeline's actual docs — direct font size with no style at
		// all: ~17pt bold for H2, ~13pt bold for H3, with margin either
		// side to tolerate minor size drift between documents.
		$is_h2 = 'Heading2' === $style || ( '' === $style && $size >= 30 && $size < 40 );
		$is_h3 = 'Heading3' === $style || ( '' === $style && $size >= 24 && $size < 30 );

		if ( $is_h2 ) {
			$html[] = "<h2>{$inner}</h2>";
		} elseif ( $is_h3 ) {
			$html[] = "<h3>{$inner}</h3>";
		} elseif ( 'Heading1' === $style && '' === $title ) {
			$title = $plain;
		} else {
			if ( null === $first_plain ) {
				$first_plain      = $plain;
				$first_html_index = count( $html );
			}
			$html[] = "<p>{$inner}</p>";
		}
	}
	$close_list();

	// No metadata title and no Title/Heading1 style used either — fall
	// back to the first plain paragraph (and remove it from the body so
	// it isn't duplicated as both title and first paragraph).
	if ( '' === $title && null !== $first_plain ) {
		$title = $first_plain;
		unset( $html[ $first_html_index ] );
		$html = array_values( $html );
	}

	if ( '' === $title ) {
		return new WP_Error( 'docx_no_title', "Could not find a title (metadata block, Title-styled paragraph, or any text) in $docx_path" );
	}

	return array(
		'title'    => $title,
		'html'     => implode( "\n", $html ),
		'images'   => $images,
		'feature'  => $feature,
		'metadata' => $metadata,
	);
}

// WP only recompresses an upload when it generates a smaller intermediate
// size (e.g. "large"); an original already at or below that threshold is
// served to visitors byte-for-byte as exported by whoever wrote the post,
// which is often far above a reasonable web quality. Re-encode JPEGs (the
// only format content-writer screenshots/photos come in) through WP's own
// image editor right after upload so even the "original" is web-sized.
function tvr_blog_optimize_upload( $file_path ) {
	$filetype = wp_check_filetype( $file_path );
	if ( 'image/jpeg' !== $filetype['type'] ) {
		return;
	}
	$editor = wp_get_image_editor( $file_path );
	if ( is_wp_error( $editor ) ) {
		return;
	}
	$editor->set_quality( 82 );
	$editor->save( $file_path );
}

/** Same sideload pattern as import-services.php's icon sideload. */
function tvr_blog_sideload_image( $path, $title, $post_id = 0, $alt = '' ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$upload = wp_upload_bits( basename( $path ), null, file_get_contents( $path ) );
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'upload_failed', $upload['error'] );
	}
	tvr_blog_optimize_upload( $upload['file'] );
	$filetype      = wp_check_filetype( $upload['file'] );
	$attachment_id = wp_insert_attachment( array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => $title,
		'post_status'    => 'inherit',
	), $upload['file'], $post_id );
	if ( is_wp_error( $attachment_id ) ) return $attachment_id;
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	if ( '' !== $alt ) update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	return $attachment_id;
}

/** Re-run safe: reuses the attachment from a previous run instead of re-uploading. */
function tvr_blog_sideload_image_cached( $post_id, $path, $title, $cache_key, $alt = '' ) {
	$meta_key = '_tvr_sideload_' . sanitize_title( $cache_key );
	$existing = get_post_meta( $post_id, $meta_key, true );
	if ( $existing && get_post( $existing ) ) return (int) $existing;

	$attachment_id = tvr_blog_sideload_image( $path, $title, $post_id, $alt );
	if ( is_wp_error( $attachment_id ) ) return $attachment_id;
	update_post_meta( $post_id, $meta_key, $attachment_id );
	return $attachment_id;
}

function tvr_blog_parse_meta_txt( $path ) {
	$meta = array( 'category' => '', 'tags' => array() );
	if ( ! file_exists( $path ) ) return $meta;
	foreach ( file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
		if ( ! preg_match( '/^([a-z_]+)\s*:\s*(.+)$/i', trim( $line ), $m ) ) continue;
		$key = strtolower( trim( $m[1] ) );
		$val = trim( $m[2] );
		if ( 'category' === $key ) $meta['category'] = $val;
		elseif ( 'tags' === $key ) $meta['tags'] = array_values( array_filter( array_map( 'trim', explode( ',', $val ) ) ) );
	}
	return $meta;
}

function tvr_blog_set_category( $post_id, $name ) {
	if ( '' === $name ) return;
	$term = term_exists( $name, 'category' );
	if ( ! $term ) $term = wp_insert_term( $name, 'category' );
	if ( is_wp_error( $term ) ) return;
	$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	wp_set_object_terms( $post_id, array( $term_id ), 'category', false );
}

/**
 * Read-only structure validation: required files present, the .docx
 * actually parses, every `[image: ...]` marker has a matching file in the
 * folder, plus a heads-up for image files that are present but never
 * referenced. No DB writes — safe to call just to preview/validate a
 * folder before deciding to import it. Used by both the real import
 * (below) and the admin page's folder listing/upload preview.
 *
 * @return array{slug:string,parsed:array,feature_path:string,feature_alt:string,feature_auto:bool,orphaned:array,existing:WP_Post[]}|WP_Error
 */
function tvr_blog_validate_folder( $dir ) {
	$slug = basename( $dir );

	// Any single .docx file, not a fixed "content.docx" name — real content
	// folders tend to have it named after the article title, not the
	// convention's generic filename.
	$docx_files = glob( $dir . '/*.docx' );
	if ( 1 !== count( $docx_files ) ) {
		return new WP_Error( 'missing_docx', count( $docx_files ) === 0
			? 'no .docx file found'
			: 'more than one .docx file found (' . implode( ', ', array_map( 'basename', $docx_files ) ) . ') — the folder must contain exactly one' );
	}
	$docx_path = $docx_files[0];

	$parsed = tvr_docx_extract( $docx_path );
	if ( is_wp_error( $parsed ) ) return $parsed;

	foreach ( $parsed['images'] as $img ) {
		if ( ! file_exists( $dir . '/' . $img['file'] ) ) {
			return new WP_Error( 'missing_inline_image', "the docx references \"{$img['file']}\" but that file isn't in the folder" );
		}
	}

	/*
	 * Featured image, in order of preference:
	 *   1. an explicit [feature: ...] marker inside the .docx
	 *   2. exactly one file literally named feature.<ext>
	 *   3. auto-pick: the first image file (natural sort — "photo-2" before
	 *      "photo-10") not already claimed by an [image: ...] marker.
	 * (3) exists because most real content folders never set (1) or (2) at
	 * all — requiring every folder to be hand-edited just to name a
	 * featured image defeats the point of bulk upload. Which one got
	 * picked is always surfaced in the admin preview table so a wrong
	 * guess is easy to spot and override with a [feature: ...] line.
	 */
	$feature_auto = false;
	if ( ! empty( $parsed['feature'] ) ) {
		$feature_file = $parsed['feature']['file'];
		if ( ! file_exists( $dir . '/' . $feature_file ) ) {
			return new WP_Error( 'missing_feature_image', "the docx's [feature: ...] marker references \"$feature_file\" but that file isn't in the folder" );
		}
		$feature_path = $dir . '/' . $feature_file;
		$feature_alt  = $parsed['feature']['alt'];
	} else {
		$feature_files = glob( $dir . '/feature.*' );
		if ( 1 === count( $feature_files ) ) {
			$feature_path = $feature_files[0];
			$feature_alt  = '';
		} else {
			$referenced_files = wp_list_pluck( $parsed['images'], 'file' );
			$candidates       = array();
			foreach ( glob( $dir . '/*' ) as $f ) {
				if ( ! preg_match( '/\.(jpe?g|png|webp|gif)$/i', $f ) ) continue;
				if ( in_array( basename( $f ), $referenced_files, true ) ) continue;
				$candidates[] = $f;
			}
			natsort( $candidates );
			$candidates = array_values( $candidates );
			if ( empty( $candidates ) ) {
				return new WP_Error( 'feature_image', 'no featured image found — the folder has no unused image to auto-pick from, and no [feature: ...] marker or feature.<ext> file either' );
			}
			$feature_path = $candidates[0];
			$feature_alt  = '';
			$feature_auto = true;
		}
	}

	$orphaned = array();
	foreach ( glob( $dir . '/*' ) as $f ) {
		if ( $f === $feature_path ) continue;
		if ( ! preg_match( '/\.(jpe?g|png|webp|gif)$/i', $f ) ) continue;
		$referenced = false;
		foreach ( $parsed['images'] as $img ) {
			if ( basename( $f ) === $img['file'] ) { $referenced = true; break; }
		}
		if ( ! $referenced ) $orphaned[] = basename( $f );
	}

	$existing = get_posts( array(
		'post_type'      => 'post',
		'name'           => $slug,
		'post_status'    => 'any',
		'posts_per_page' => 1,
	) );

	// A different post type already owns this exact slug — inserting would
	// silently get auto-suffixed ("-2") and break idempotency on every
	// future run instead of updating the same post.
	$collision = get_posts( array(
		'name'           => $slug,
		'post_type'      => 'any',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'post__not_in'   => $existing ? array( $existing[0]->ID ) : array(),
	) );
	$collision = array_filter( $collision, function ( $p ) { return 'post' !== $p->post_type; } );
	if ( $collision ) {
		$other = reset( $collision );
		return new WP_Error( 'slug_collision', "slug \"$slug\" is already used by an existing {$other->post_type} (ID {$other->ID}) — rename the folder" );
	}

	return array(
		'slug'         => $slug,
		'parsed'       => $parsed,
		'feature_path' => $feature_path,
		'feature_alt'  => $feature_alt,
		'feature_auto' => $feature_auto,
		'orphaned'     => $orphaned,
		'existing'     => $existing,
	);
}

/**
 * Auto-fills Rank Math's per-post SEO Title/Meta Description/Focus Keyword
 * from the docx metadata block (Title/Meta Description/Main Keyword), when
 * present — same meta keys Rank Math's own Yoast/AIOSEO importers write to
 * (`includes/admin/importers/class-yoast.php`), confirmed against the
 * installed plugin's source rather than guessed. The editor still reviews
 * and can change any of these in the Rank Math box — this just means they
 * start pre-filled with what the content brief already specified instead
 * of blank.
 */
function tvr_blog_set_rank_math_meta( $post_id, $metadata ) {
	if ( ! defined( 'RANK_MATH_VERSION' ) ) return;
	if ( '' !== $metadata['title'] ) update_post_meta( $post_id, 'rank_math_title', $metadata['title'] );
	if ( '' !== $metadata['meta_description'] ) update_post_meta( $post_id, 'rank_math_description', $metadata['meta_description'] );
	if ( '' !== $metadata['focus_keyword'] ) update_post_meta( $post_id, 'rank_math_focus_keyword', $metadata['focus_keyword'] );
}

/**
 * @return array{post_id:int,created:bool,edit_url:string,orphaned:array}|WP_Error
 */
function tvr_blog_import_folder( $dir ) {
	$meta_path = $dir . '/meta.txt';

	$validated = tvr_blog_validate_folder( $dir );
	if ( is_wp_error( $validated ) ) return $validated;

	$slug         = $validated['slug'];
	$parsed       = $validated['parsed'];
	$feature_path = $validated['feature_path'];
	$feature_alt  = $validated['feature_alt'];
	$orphaned     = $validated['orphaned'];
	$existing     = $validated['existing'];

	$post_args = array(
		'post_type'   => 'post',
		'post_status' => 'draft',
		'post_title'  => $parsed['title'],
		'post_name'   => $slug,
	);
	if ( $existing ) {
		$post_args['ID'] = $existing[0]->ID;
		$post_id = wp_update_post( $post_args, true );
	} else {
		$post_id = wp_insert_post( $post_args, true );
	}
	if ( is_wp_error( $post_id ) ) return $post_id;

	$content = $parsed['html'];
	foreach ( $parsed['images'] as $i => $img ) {
		$attachment_id = tvr_blog_sideload_image_cached( $post_id, $dir . '/' . $img['file'], $img['alt'], $img['file'], $img['alt'] );
		if ( is_wp_error( $attachment_id ) ) return $attachment_id;
		$img_html = '<img src="' . esc_url( wp_get_attachment_url( $attachment_id ) ) . '" alt="' . esc_attr( $img['alt'] ) . '" />';
		$content  = str_replace( '<!--TVR_IMAGE_' . $i . '-->', $img_html, $content );
	}
	wp_update_post( array( 'ID' => $post_id, 'post_content' => $content ) );

	$feature_id = tvr_blog_sideload_image_cached( $post_id, $feature_path, $parsed['title'], 'feature', $feature_alt ?: $parsed['title'] );
	if ( is_wp_error( $feature_id ) ) return $feature_id;
	set_post_thumbnail( $post_id, $feature_id );

	tvr_blog_set_rank_math_meta( $post_id, $parsed['metadata'] );

	$meta = tvr_blog_parse_meta_txt( $meta_path );
	tvr_blog_set_category( $post_id, $meta['category'] );
	if ( ! empty( $meta['tags'] ) ) wp_set_object_terms( $post_id, $meta['tags'], 'post_tag', false );

	return array(
		'post_id'  => $post_id,
		'created'  => empty( $existing ),
		'edit_url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
		'orphaned' => $orphaned,
	);
}

/** List folders currently sitting in content-drops/, newest first. */
function tvr_blog_list_content_drop_folders() {
	$folders = glob( tvr_blog_content_drops_dir() . '/*', GLOB_ONLYDIR );
	return $folders ? $folders : array();
}

/**
 * Runs every folder in content-drops/ and returns structured results —
 * no echo/print, so the CLI script and the admin page can each format the
 * same data their own way.
 *
 * @return array{slug:string,status:string,message:string,orphaned:array}[]
 */
function tvr_blog_import_run_all() {
	$results = array();

	foreach ( tvr_blog_list_content_drop_folders() as $dir ) {
		$slug = basename( $dir );
		try {
			$result = tvr_blog_import_folder( $dir );
		} catch ( \Throwable $e ) {
			$results[] = array( 'slug' => $slug, 'status' => 'error', 'message' => $e->getMessage(), 'orphaned' => array() );
			continue;
		}

		if ( is_wp_error( $result ) ) {
			$results[] = array( 'slug' => $slug, 'status' => 'error', 'message' => $result->get_error_message(), 'orphaned' => array() );
			continue;
		}

		$results[] = array(
			'slug'     => $slug,
			'status'   => $result['created'] ? 'created' : 'updated',
			'message'  => $result['edit_url'],
			'orphaned' => $result['orphaned'],
		);
	}

	return $results;
}
