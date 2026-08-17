<?php
/**
 * Rank Math SEO integration. Rank Math (free) is installed to give blog
 * posts (`post`) the SEO Title / Meta Description / Focus Keyword meta box
 * this theme has no equivalent for — inc/seo.php's tvr_current_page_seo()
 * only ever handled per-page-type SEO (front page, tv_brand, page
 * templates), not per-post.
 *
 * Left unconfigured, Rank Math emits its own title/description/canonical/
 * OG/Twitter tags on every public post type, which would duplicate the
 * hand-rolled tags inc/seo.php already prints for the front page, tv_brand,
 * and every ACF-driven page template. Scoping it to `post` only is done via
 * Rank Math's own frontend/* filters rather than unhooking anything:
 * everything Rank Math prints (title, description, canonical, OpenGraph,
 * Twitter) is read from one memoized "Paper" value per request
 * (includes/frontend/paper/class-paper.php), and each consumer silently
 * skips its own output the moment that value is empty — so forcing it
 * empty here is enough to fully silence Rank Math's front end everywhere
 * except real blog posts, without touching inc/seo.php at all.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( 'RANK_MATH_VERSION' ) ) return;

add_filter( 'rank_math/frontend/title', 'tvr_rank_math_scope_to_posts' );
add_filter( 'rank_math/frontend/description', 'tvr_rank_math_scope_to_posts' );
add_filter( 'rank_math/frontend/canonical', 'tvr_rank_math_scope_to_posts' );
function tvr_rank_math_scope_to_posts( $value ) {
	return is_singular( 'post' ) ? $value : '';
}

// Keep the SEO meta box + on-page analysis out of Pages/CPT edit screens —
// those are fully covered by inc/seo.php + ACF, so the box would just be a
// second, confusing, unused SEO UI there.
add_filter( 'rank_math/excluded_post_types', function ( $post_types ) {
	return array( 'post' );
} );
