<?php
/**
 * Template Name: Help / FAQ
 * Port of app/help/page.jsx + components/HelpClient.jsx — content sourced
 * from the `faq_item` CPT (grouped by the `faq_category` taxonomy) and this
 * page's ACF fields, both editable in wp-admin.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$page_id  = get_the_ID();
$hero_title    = tvr_field( 'hero_title', $page_id, 'Frequently Asked Questions' );
$hero_subtitle = tvr_field( 'hero_subtitle', $page_id, 'Answers to the most common questions about Universal TV Remote — compatibility, setup, features, and more.' );

$categories = get_terms( array( 'taxonomy' => 'faq_category', 'hide_empty' => true, 'orderby' => 'term_order' ) );
if ( is_wp_error( $categories ) ) $categories = array();

$qa_pairs = array();
foreach ( tvr_get_content_posts( 'faq_item' ) as $item ) {
	$qa_pairs[] = array( 'q' => $item->post_title, 'a' => get_field( 'answer', $item->ID ) );
}
tvr_json_ld( tvr_faq_ld( $qa_pairs ) );
?>

<section class="bg-navy-900 text-white">
	<div class="mx-auto max-w-3xl px-4 py-16 text-center">
		<h1 class="text-3xl font-extrabold tracking-tight sm:text-5xl"<?php echo tvr_heading_style_attr( $page_id ); ?>><?php echo esc_html( $hero_title ); ?></h1>
		<p class="mx-auto mt-3 max-w-xl text-slate-300"><?php echo esc_html( $hero_subtitle ); ?></p>
		<div class="relative mx-auto mt-8 max-w-xl">
			<svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3-3" />
			</svg>
			<input type="search" id="tvr-help-search" placeholder="Search FAQs&hellip;" class="w-full rounded-xl border border-white/15 bg-white/5 py-3 pl-12 pr-4 text-white placeholder:text-slate-400 outline-none transition focus:border-brand-400 focus:bg-white/10" />
		</div>
	</div>
</section>

<div class="mx-auto max-w-3xl px-4 py-12">
	<div id="tvr-help-empty" class="hidden rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center text-slate-500">No FAQs match your search.</div>
	<div id="tvr-help-groups" class="space-y-8">
		<?php foreach ( $categories as $cat ) :
			$items = tvr_get_content_posts( 'faq_item', array(
				'tax_query' => array( array( 'taxonomy' => 'faq_category', 'field' => 'term_id', 'terms' => $cat->term_id ) ),
			) );
			if ( ! $items ) continue;
			$search_text = strtolower( $cat->name . ' ' . implode( ' ', array_map( function ( $it ) { return $it->post_title . ' ' . get_field( 'answer', $it->ID ); }, $items ) ) );
			?>
			<section data-help-group data-search-text="<?php echo esc_attr( $search_text ); ?>">
				<div class="mb-2 flex items-baseline justify-between">
					<h2 class="text-xl font-bold text-slate-900"><?php echo esc_html( $cat->name ); ?></h2>
					<span class="text-sm text-slate-400"><?php echo count( $items ); ?> topics</span>
				</div>
				<div class="rounded-2xl border border-slate-200 bg-white px-5">
					<?php foreach ( $items as $item ) :
						$answer = get_field( 'answer', $item->ID );
						?>
						<details class="group border-b border-slate-100 last:border-0" data-help-item data-search-text="<?php echo esc_attr( strtolower( $item->post_title . ' ' . $answer ) ); ?>">
							<summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-slate-800 transition hover:text-brand-600">
								<span class="font-medium"><?php echo esc_html( $item->post_title ); ?></span>
								<svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
									<path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
								</svg>
							</summary>
							<p class="pb-4 pr-8 text-sm leading-relaxed text-slate-600"><?php echo esc_html( $answer ); ?></p>
						</details>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var input = document.getElementById('tvr-help-search');
	if (!input) return;
	var groups = document.querySelectorAll('[data-help-group]');
	var groupsWrap = document.getElementById('tvr-help-groups');
	var emptyEl = document.getElementById('tvr-help-empty');

	input.addEventListener('input', function () {
		var q = input.value.trim().toLowerCase();
		var visibleGroups = 0;
		groups.forEach(function (group) {
			var items = group.querySelectorAll('[data-help-item]');
			var visibleItems = 0;
			items.forEach(function (item) {
				var match = !q || item.getAttribute('data-search-text').indexOf(q) !== -1;
				item.style.display = match ? '' : 'none';
				if (match) visibleItems++;
			});
			var groupVisible = visibleItems > 0;
			group.style.display = groupVisible ? '' : 'none';
			if (groupVisible) visibleGroups++;
		});
		groupsWrap.classList.toggle('hidden', visibleGroups === 0);
		emptyEl.classList.toggle('hidden', visibleGroups !== 0);
	});
});
</script>

<?php get_footer(); ?>
