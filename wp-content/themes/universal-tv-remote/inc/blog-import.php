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
	if ( $node ) {
		$num_id = $node->getAttributeNS( TVR_DOCX_NS_W, 'val' );
		return $numbering_map[ $num_id ] ?? 'ul';
	}

	// Docs exported with the `ListBullet`/`ListNumber` paragraph styles carry
	// no `w:numPr` (and so no numbering.xml entry) at all — the style name is
	// the only signal the paragraph is a list item. Without this they'd come
	// through as ordinary <p> paragraphs.
	$style = tvr_docx_para_style( $p, $xpath );
	if ( preg_match( '/^ListBullet\d*$/i', $style ) ) return 'ul';
	if ( preg_match( '/^ListNumber\d*$/i', $style ) ) return 'ol';
	return null;
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
 * Title / Meta Description / Tag / a focus-keyword field / Secondary
 * Keywords / Outline — before the article body starts. Two different
 * label spellings have been seen in practice: "Main Keyword" and "Primary
 * Keyword" for the same field, so both are recognized as aliases. Not
 * every doc's own label text will necessarily match this exact set —
 * treat this list as "known so far", not exhaustive.
 */
function tvr_docx_metadata_labels() {
	return array(
		'title'              => 'title',
		'meta description'   => 'meta_description',
		'tag'                => 'tags',
		'tags'               => 'tags',
		'main keyword'       => 'focus_keyword',
		'primary keyword'    => 'focus_keyword',
		'focus keyword'      => 'focus_keyword',
		'secondary keywords' => 'secondary_keywords',
		'outline'            => 'outline',
	);
}

/** Label alone on its own paragraph (the value follows on later paragraphs). */
function tvr_docx_match_metadata_label( $plain ) {
	$labels = tvr_docx_metadata_labels();
	return $labels[ strtolower( trim( $plain ) ) ] ?? null;
}

/**
 * "Label: value" on a single paragraph — the other real-world shape seen
 * in practice, alongside the label-alone-then-value-paragraphs shape
 * tvr_docx_match_metadata_label() handles. Checked separately since the
 * value here is complete in one paragraph (may itself contain colons,
 * e.g. "Title: Foo: A Guide" — greedy match to end of string handles
 * that), unlike the multi-paragraph continuation case.
 */
function tvr_docx_match_inline_metadata( $plain ) {
	foreach ( tvr_docx_metadata_labels() as $label_text => $field ) {
		if ( preg_match( '/^' . preg_quote( $label_text, '/' ) . '\s*:\s*(.+)$/i', trim( $plain ), $m ) ) {
			return array( 'key' => $field, 'value' => trim( $m[1] ) );
		}
	}
	return null;
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

	/*
	 * --- Pass 1: the metadata block, if this doc has one. ---
	 * Two label shapes are both recognized (real docs have used either):
	 *   - "Label" alone on its own paragraph, value(s) on the paragraph(s)
	 *     after it (tvr_docx_match_metadata_label())
	 *   - "Label: value" together on one paragraph
	 *     (tvr_docx_match_inline_metadata())
	 * A handful of leading paragraphs that match neither (e.g. a wrapper
	 * heading like "SEO Publishing Snapshot") are tolerated before giving
	 * up — real content-brief docs sometimes have one, and skipping it
	 * here also keeps it out of the published post body. Once a label
	 * has actually been found, anything after that failing to match a
	 * label/continuation ends the block (start of the real article body).
	 */
	$metadata_raw    = array();
	$current_label   = null;
	$found_metadata  = false;
	$body_start      = 0;
	$preamble_budget = 5;

	for ( $i = 0; $i < $node_count; $i++ ) {
		$node = $body_children->item( $i );
		if ( 'w:p' !== $node->nodeName ) {
			if ( $found_metadata ) break;
			continue;
		}

		$plain = trim( tvr_docx_para_plain_text( $node, $xpath ) );

		if ( '' === $plain ) {
			if ( $found_metadata ) $body_start = $i + 1;
			continue;
		}

		$inline = tvr_docx_match_inline_metadata( $plain );
		if ( $inline ) {
			if ( ! isset( $metadata_raw[ $inline['key'] ] ) ) $metadata_raw[ $inline['key'] ] = array();
			$metadata_raw[ $inline['key'] ][] = $inline['value'];
			$current_label  = null; // self-contained — nothing to continue onto
			$found_metadata = true;
			$body_start     = $i + 1;
			continue;
		}

		$label = tvr_docx_match_metadata_label( $plain );
		if ( $label ) {
			$current_label  = $label;
			$found_metadata = true;
			if ( ! isset( $metadata_raw[ $label ] ) ) $metadata_raw[ $label ] = array();
			$body_start = $i + 1;
			continue;
		}

		if ( ! $found_metadata ) {
			if ( $i >= $preamble_budget ) break; // gave the preamble its chance; this isn't the template
			continue;
		}

		if ( null === $current_label ) break; // nothing open to continue — real body starts here

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

	/*
	 * The body commonly repeats the title once more right after the
	 * metadata block — drop that repeat. Checked by text match against
	 * the title already captured above (not just a large font size): a
	 * different real doc used the exact same size for this repeat as for
	 * an ordinary H2, so size alone isn't reliable across templates,
	 * while the text genuinely being the same title is a much stronger
	 * signal either way. Still checks size too, as a fallback for a
	 * repeat that isn't textually identical (e.g. re-wrapped/punctuated
	 * slightly differently) but is unambiguously oversized.
	 */
	if ( $found_metadata && '' !== $metadata['title'] && $body_start < $node_count ) {
		$next = $body_children->item( $body_start );
		if ( $next && 'w:p' === $next->nodeName ) {
			$next_text = trim( tvr_docx_para_plain_text( $next, $xpath ) );
			$is_repeat = 0 === strcasecmp( $next_text, $metadata['title'] ) || tvr_docx_para_font_size( $next, $xpath ) >= 40;
			if ( $is_repeat ) $body_start++;
		}
	}

	/*
	 * Which HTML heading level each named Word style maps to depends on
	 * where the title came from. The original plain-doc shape used
	 * `Heading1` for the article title itself, so Heading2 = H2 and
	 * Heading3 = H3. The content pipeline's newer docs put the title in a
	 * real `Title`-styled paragraph (or in the folder's seo_info.txt) and
	 * then use `Heading1` for the article's own sections — so everything
	 * shifts down one level there: Heading1 = H2, Heading2 = H3,
	 * Heading3 = H4. Decided once up front rather than per paragraph, so a
	 * single document can never map the same style two different ways.
	 * Size-based detection (docs with no styles at all) is unaffected.
	 */
	$heading_shift = ( '' !== $metadata['title'] ) ? 1 : 0;
	for ( $i = $body_start; $i < $node_count && ! $heading_shift; $i++ ) {
		$node = $body_children->item( $i );
		if ( 'w:p' === $node->nodeName && 'Title' === tvr_docx_para_style( $node, $xpath ) ) {
			$heading_shift = 1;
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

		// Named Heading styles (shifted or not, see $heading_shift above), or
		// — docs with no paragraph styles at all — direct font size: ~17pt
		// bold for H2, ~13pt bold for H3, with margin either side to tolerate
		// minor size drift between documents.
		$is_h2 = 'Heading' . ( 2 - $heading_shift ) === $style || ( '' === $style && $size >= 30 && $size < 40 );
		$is_h3 = 'Heading' . ( 3 - $heading_shift ) === $style || ( '' === $style && $size >= 24 && $size < 30 );
		$is_h4 = $heading_shift && 'Heading3' === $style;

		if ( $is_h2 ) {
			$html[] = "<h2>{$inner}</h2>";
		} elseif ( $is_h3 ) {
			$html[] = "<h3>{$inner}</h3>";
		} elseif ( $is_h4 ) {
			$html[] = "<h4>{$inner}</h4>";
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

/**
 * Label -> field map for the folder's sidecar config file — `seo_info.txt`
 * in the content pipeline's current folder structure, or the older
 * `meta.txt`. Same idea as tvr_docx_metadata_labels() but for the .txt
 * sidecar, whose real files label some fields in Vietnamese ("Từ khóa
 * chính" = focus keyword, "Từ khóa phụ" = secondary keywords); both
 * spellings are accepted for every field. As with the docx labels, treat
 * this list as "known so far", not exhaustive.
 */
function tvr_blog_info_txt_labels() {
	return array(
		'title'              => 'title',
		'tiêu đề'            => 'title',
		'meta description'   => 'meta_description',
		'meta_description'   => 'meta_description',
		'description'        => 'meta_description',
		'mô tả'              => 'meta_description',
		'mô tả meta'         => 'meta_description',
		'tag'                => 'tags',
		'tags'               => 'tags',
		'thẻ'                => 'tags',
		'thẻ tag'            => 'tags',
		'category'           => 'categories',
		'categories'         => 'categories',
		'chuyên mục'         => 'categories',
		'danh mục'           => 'categories',
		'focus keyword'      => 'focus_keyword',
		'main keyword'       => 'focus_keyword',
		'primary keyword'    => 'focus_keyword',
		'từ khóa chính'      => 'focus_keyword',
		'secondary keywords' => 'secondary_keywords',
		'từ khóa phụ'        => 'secondary_keywords',
		'outline'            => 'outline',
		'dàn ý'              => 'outline',
	);
}

/** Lowercases with the Vietnamese labels above in mind — plain strtolower() is byte-wise and would leave "Từ" as "từ" only by luck. */
function tvr_blog_info_txt_normalize_label( $text ) {
	$text = trim( $text );
	$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	return trim( rtrim( $text, ':' ) );
}

/** A label alone on its own line ("Tag", or "Tag:") — its value follows on the next line(s). */
function tvr_blog_info_txt_match_label( $line ) {
	$labels = tvr_blog_info_txt_labels();
	return $labels[ tvr_blog_info_txt_normalize_label( $line ) ] ?? null;
}

/** "Label: value" on one line — meta.txt's original shape, still accepted anywhere. */
function tvr_blog_info_txt_match_inline( $line ) {
	if ( ! preg_match( '/^([^:]{1,40}?)\s*:\s*(.+)$/u', trim( $line ), $m ) ) return null;
	$field = tvr_blog_info_txt_match_label( $m[1] );
	if ( null === $field ) return null;
	return array( 'key' => $field, 'value' => trim( $m[2] ) );
}

/**
 * Parse a sidecar config file into `field => array of raw value lines`.
 * Both shapes real files use are handled and can be mixed in one file: a
 * label alone on its own line with its value on the following line(s) (the
 * seo_info.txt shape — values like "Từ khóa phụ" and "Outline" genuinely
 * run over many lines), or "Label: value" on a single line (meta.txt's).
 *
 * @return array<string,string[]>
 */
function tvr_blog_parse_info_txt( $path ) {
	$fields = array();
	if ( ! file_exists( $path ) ) return $fields;

	$raw = (string) file_get_contents( $path );
	$raw = preg_replace( '/^\xEF\xBB\xBF/', '', $raw ); // UTF-8 BOM, if the file was saved from Windows
	$current = null;

	foreach ( preg_split( '/\R/u', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) continue;

		$label = tvr_blog_info_txt_match_label( $line );
		if ( $label ) {
			$current = $label;
			if ( ! isset( $fields[ $label ] ) ) $fields[ $label ] = array();
			continue;
		}

		$inline = tvr_blog_info_txt_match_inline( $line );
		if ( $inline ) {
			$fields[ $inline['key'] ][] = $inline['value'];
			$current = null; // self-contained — nothing for a following line to continue onto
			continue;
		}

		if ( null === $current ) continue; // stray text before any label (a heading, a note) — ignored
		$fields[ $current ][] = $line;
	}

	return $fields;
}

/** Collapse tvr_blog_parse_info_txt()'s raw line lists into usable scalar/list values. */
function tvr_blog_info_txt_to_meta( $fields ) {
	$join = function ( $key ) use ( $fields ) {
		return isset( $fields[ $key ] ) ? trim( implode( ' ', $fields[ $key ] ) ) : '';
	};
	// Comma or semicolon separated, and/or one per line — real files have
	// used every combination of those.
	$list = function ( $key ) use ( $fields ) {
		$out = array();
		foreach ( $fields[ $key ] ?? array() as $line ) {
			foreach ( preg_split( '/[,;]/u', $line ) as $piece ) {
				$piece = trim( $piece );
				if ( '' !== $piece ) $out[] = $piece;
			}
		}
		return array_values( array_unique( $out ) );
	};

	return array(
		'title'              => $join( 'title' ),
		'meta_description'   => $join( 'meta_description' ),
		'focus_keyword'      => $join( 'focus_keyword' ),
		'secondary_keywords' => isset( $fields['secondary_keywords'] ) ? implode( ', ', $fields['secondary_keywords'] ) : '',
		'tags'               => $list( 'tags' ),
		'categories'         => $list( 'categories' ),
	);
}

/** The folder's sidecar config file(s), keyed by base name — `seo_info.txt` and/or the older `meta.txt`. */
function tvr_blog_info_txt_paths( $dir ) {
	$found = array();
	foreach ( glob( $dir . '/*.txt' ) as $file ) {
		$base = strtolower( pathinfo( $file, PATHINFO_FILENAME ) );
		if ( in_array( $base, array( 'seo_info', 'meta' ), true ) ) $found[ $base ] = $file;
	}
	return $found;
}

/**
 * The post's final metadata: the folder's sidecar config file(s) merged
 * over whatever the .docx itself declared. The two sources overlap by
 * design — older folders carry the metadata block inside the .docx, the
 * current folder structure carries it in `seo_info.txt` instead, and
 * either may be the only one present. Scalars take the sidecar's value
 * when it has one (it's the explicit per-post config), tags are the union
 * of both, and categories only ever come from the sidecar.
 *
 * @return array{title:string,meta_description:string,focus_keyword:string,secondary_keywords:string,tags:string[],categories:string[]}
 */
function tvr_blog_collect_post_meta( $dir, $parsed ) {
	$docx    = $parsed['metadata'];
	$scalars = array( 'title', 'meta_description', 'focus_keyword', 'secondary_keywords' );
	$sidecar = array_fill_keys( $scalars, '' ) + array( 'tags' => array(), 'categories' => array() );

	// meta.txt first, seo_info.txt second, so seo_info.txt wins wherever a
	// folder happens to carry both and they disagree.
	$paths = tvr_blog_info_txt_paths( $dir );
	foreach ( array( 'meta', 'seo_info' ) as $base ) {
		if ( empty( $paths[ $base ] ) ) continue;
		$one = tvr_blog_info_txt_to_meta( tvr_blog_parse_info_txt( $paths[ $base ] ) );
		foreach ( $scalars as $key ) {
			if ( '' !== $one[ $key ] ) $sidecar[ $key ] = $one[ $key ];
		}
		$sidecar['tags']       = array_merge( $sidecar['tags'], $one['tags'] );
		$sidecar['categories'] = array_merge( $sidecar['categories'], $one['categories'] );
	}

	// Split on comma OR semicolon: different real docs have used either as
	// the separator for the docx's own `Tag` field.
	$docx_tags = '' !== $docx['tags'] ? preg_split( '/[,;]/u', $docx['tags'] ) : array();

	$meta = array(
		'tags'       => array_values( array_unique( array_filter( array_map( 'trim', array_merge( $sidecar['tags'], $docx_tags ) ) ) ) ),
		'categories' => array_values( array_unique( array_filter( array_map( 'trim', $sidecar['categories'] ) ) ) ),
	);
	foreach ( $scalars as $key ) {
		$meta[ $key ] = '' !== $sidecar[ $key ] ? $sidecar[ $key ] : $docx[ $key ];
	}
	return $meta;
}

/** Assigns (creating as needed) the post's categories, replacing whatever it had — including core's default "Uncategorized". */
function tvr_blog_set_categories( $post_id, $names ) {
	if ( empty( $names ) ) return;
	$term_ids = array();
	foreach ( $names as $name ) {
		if ( '' === $name ) continue;
		$term = term_exists( $name, 'category' );
		if ( ! $term ) $term = wp_insert_term( $name, 'category' );
		if ( is_wp_error( $term ) ) continue;
		$term_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	}
	if ( $term_ids ) wp_set_object_terms( $post_id, $term_ids, 'category', false );
}

/**
 * Read-only structure validation: required files present, the .docx
 * actually parses, every `[image: ...]` marker has a matching file in the
 * folder, plus which unmarked images get auto-placed one-per-H2-section vs.
 * genuinely left over. No DB writes — safe to call just to preview/validate
 * a folder before deciding to import it. Used by both the real import
 * (below) and the admin page's folder listing/upload preview.
 *
 * @return array{slug:string,parsed:array,meta:array,feature_path:string,feature_alt:string,feature_auto:bool,auto_placed:array,orphaned:array,existing:WP_Post[]}|WP_Error
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

	$unused = array();
	foreach ( glob( $dir . '/*' ) as $f ) {
		if ( $f === $feature_path ) continue;
		if ( ! preg_match( '/\.(jpe?g|png|webp|gif)$/i', $f ) ) continue;
		$referenced = false;
		foreach ( $parsed['images'] as $img ) {
			if ( basename( $f ) === $img['file'] ) { $referenced = true; break; }
		}
		if ( ! $referenced ) $unused[] = basename( $f );
	}
	natsort( $unused );
	$unused = array_values( $unused );

	/*
	 * Images sitting in the folder but not claimed by any [feature: ...]/
	 * [image: ...] marker aren't just left out — same reasoning as the
	 * feature-image auto-pick above, most real content folders never mark
	 * every image explicitly. Up to one per H2 section gets auto-placed
	 * right after that section's heading (centered, full width — per
	 * explicit feedback), in natural filename order. Anything left over
	 * past that (more unused images than H2 sections) really is unused
	 * and stays flagged as orphaned.
	 */
	$h2_count    = substr_count( $parsed['html'], '<h2>' );
	$auto_placed = array_slice( $unused, 0, $h2_count );
	$orphaned    = array_slice( $unused, $h2_count );

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
		'meta'         => tvr_blog_collect_post_meta( $dir, $parsed ),
		'feature_path' => $feature_path,
		'feature_alt'  => $feature_alt,
		'feature_auto' => $feature_auto,
		'auto_placed'  => $auto_placed,
		'orphaned'     => $orphaned,
		'existing'     => $existing,
	);
}

/**
 * Auto-fills Rank Math's per-post SEO Title/Meta Description/Focus Keyword
 * from the folder's collected metadata (tvr_blog_collect_post_meta() —
 * seo_info.txt/meta.txt over the docx's own metadata block), when present
 * — same meta keys Rank Math's own Yoast/AIOSEO importers write to
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
 * Splices one auto-placed image (see tvr_blog_validate_folder()'s
 * `auto_placed` list — unmarked images the folder had left over, one per
 * H2 section) in right after each H2 section's heading, centered and full
 * width per explicit feedback ("chèn đúng mục ... ảnh căn giữa, full
 * size"). Sideloads each image as it's placed.
 */
function tvr_blog_place_auto_images( $content, $dir, $post_id, $auto_placed, $alt_fallback ) {
	if ( empty( $auto_placed ) ) return $content;

	$parts   = preg_split( '/(<\/h2>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$rebuilt = '';
	$i       = 0;

	foreach ( $parts as $part ) {
		$rebuilt .= $part;
		if ( '</h2>' === $part && $i < count( $auto_placed ) ) {
			$file          = $auto_placed[ $i++ ];
			$attachment_id = tvr_blog_sideload_image_cached( $post_id, $dir . '/' . $file, $alt_fallback, $file, $alt_fallback );
			if ( is_wp_error( $attachment_id ) ) continue;
			$url      = wp_get_attachment_url( $attachment_id );
			$rebuilt .= "\n" . '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt_fallback ) . '" style="display:block;margin:1.5em auto;width:100%;height:auto;" />';
		}
	}
	return $rebuilt;
}

/**
 * @return array{post_id:int,created:bool,edit_url:string,auto_placed:array,orphaned:array,categories:array,tags:array}|WP_Error
 */
function tvr_blog_import_folder( $dir ) {
	$validated = tvr_blog_validate_folder( $dir );
	if ( is_wp_error( $validated ) ) return $validated;

	$slug         = $validated['slug'];
	$parsed       = $validated['parsed'];
	$meta         = $validated['meta'];
	$feature_path = $validated['feature_path'];
	$feature_alt  = $validated['feature_alt'];
	$auto_placed  = $validated['auto_placed'];
	$orphaned     = $validated['orphaned'];
	$existing     = $validated['existing'];

	// The sidecar config's `Title` is the post's title when it has one —
	// it's the explicit per-post config; the .docx's own heading is the
	// fallback for folders that don't ship a seo_info.txt/meta.txt.
	$post_args = array(
		'post_type'   => 'post',
		'post_status' => 'draft',
		'post_title'  => '' !== $meta['title'] ? $meta['title'] : $parsed['title'],
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
	$content = tvr_blog_place_auto_images( $content, $dir, $post_id, $auto_placed, $parsed['title'] );
	wp_update_post( array( 'ID' => $post_id, 'post_content' => $content ) );

	$feature_id = tvr_blog_sideload_image_cached( $post_id, $feature_path, $parsed['title'], 'feature', $feature_alt ?: $parsed['title'] );
	if ( is_wp_error( $feature_id ) ) return $feature_id;
	set_post_thumbnail( $post_id, $feature_id );

	tvr_blog_set_rank_math_meta( $post_id, $meta );
	tvr_blog_set_categories( $post_id, $meta['categories'] );
	if ( ! empty( $meta['tags'] ) ) wp_set_object_terms( $post_id, $meta['tags'], 'post_tag', false );

	return array(
		'post_id'     => $post_id,
		'created'     => empty( $existing ),
		'edit_url'    => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
		'auto_placed' => $auto_placed,
		'orphaned'    => $orphaned,
		'categories'  => $meta['categories'],
		'tags'        => $meta['tags'],
	);
}

/**
 * Folders that imported successfully get moved here (see
 * tvr_blog_archive_content_drop_folder()) so the "ready to import" list
 * doesn't keep accumulating already-done folders — archived, not deleted,
 * so the original .docx/images are still there if ever needed again.
 */
function tvr_blog_content_drops_archive_dir() {
	return tvr_blog_content_drops_dir() . '/_imported';
}

/** List folders currently sitting in content-drops/, newest first — excludes the archive. */
function tvr_blog_list_content_drop_folders() {
	$folders = glob( tvr_blog_content_drops_dir() . '/*', GLOB_ONLYDIR );
	if ( ! $folders ) return array();
	$archive_dir = tvr_blog_content_drops_archive_dir();
	return array_values( array_filter( $folders, function ( $dir ) use ( $archive_dir ) {
		return $dir !== $archive_dir;
	} ) );
}

/** Recursively deletes a directory — used only to clear an old archived copy before replacing it. */
function tvr_blog_rrmdir( $dir ) {
	if ( ! is_dir( $dir ) ) return;
	foreach ( scandir( $dir ) as $entry ) {
		if ( '.' === $entry || '..' === $entry ) continue;
		$path = $dir . '/' . $entry;
		is_dir( $path ) ? tvr_blog_rrmdir( $path ) : unlink( $path );
	}
	rmdir( $dir );
}

/** Moves a successfully-imported folder out of the pending list, into the archive. */
function tvr_blog_archive_content_drop_folder( $dir ) {
	$archive_root = tvr_blog_content_drops_archive_dir();
	if ( ! file_exists( $archive_root ) ) wp_mkdir_p( $archive_root );

	$dest = $archive_root . '/' . basename( $dir );
	if ( file_exists( $dest ) ) tvr_blog_rrmdir( $dest ); // re-imported the same slug — replace the old archive copy
	@rename( $dir, $dest );
}

/**
 * Runs every folder in content-drops/ and returns structured results —
 * no echo/print, so the CLI script and the admin page can each format the
 * same data their own way.
 *
 * @return array{slug:string,status:string,message:string,auto_placed:array,orphaned:array,categories:array,tags:array}[]
 */
function tvr_blog_import_run_all() {
	$results = array();

	foreach ( tvr_blog_list_content_drop_folders() as $dir ) {
		$slug = basename( $dir );
		try {
			$result = tvr_blog_import_folder( $dir );
		} catch ( \Throwable $e ) {
			$results[] = array( 'slug' => $slug, 'status' => 'error', 'message' => $e->getMessage(), 'auto_placed' => array(), 'orphaned' => array(), 'categories' => array(), 'tags' => array() );
			continue;
		}

		if ( is_wp_error( $result ) ) {
			$results[] = array( 'slug' => $slug, 'status' => 'error', 'message' => $result->get_error_message(), 'auto_placed' => array(), 'orphaned' => array(), 'categories' => array(), 'tags' => array() );
			continue;
		}

		tvr_blog_archive_content_drop_folder( $dir );

		$results[] = array(
			'slug'        => $slug,
			'status'      => $result['created'] ? 'created' : 'updated',
			'message'     => $result['edit_url'],
			'auto_placed' => $result['auto_placed'],
			'orphaned'    => $result['orphaned'],
			'categories'  => $result['categories'],
			'tags'        => $result['tags'],
		);
	}

	return $results;
}
