<?php
/**
 * /services/category/{key}/ — port of app/services/category/[key]/page.jsx.
 * Full unpaginated list (same as the original), indexable-first then
 * alphabetical, so Googlebot's crawl budget favors substantive pages.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$term  = get_queried_object();
$label = tvr_category_label( $term->slug );
$services = tvr_brands_in_category( $term->slug );

usort( $services, function ( $a, $b ) {
	$ai = tvr_is_indexable( $a ) ? 0 : 1;
	$bi = tvr_is_indexable( $b ) ? 0 : 1;
	if ( $ai !== $bi ) return $ai - $bi;
	return strcasecmp( $a['name'], $b['name'] );
} );

$other_categories = array_values( array_filter( tvr_all_categories(), function ( $c ) use ( $term ) {
	return $c['key'] !== $term->slug;
} ) );

tvr_json_ld( tvr_breadcrumb_ld( array(
	array( 'name' => 'Home', 'url' => home_url( '/' ) ),
	array( 'name' => 'Supported TVs', 'url' => home_url( '/services/' ) ),
	array( 'name' => $label, 'url' => home_url( '/services/category/' . $term->slug . '/' ) ),
) ) );
?>

<div class="mx-auto max-w-5xl px-4 py-10">
	<nav class="mb-6 flex items-center gap-1.5 text-sm text-slate-500">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-brand-600">Home</a>
		<span>/</span>
		<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="hover:text-brand-600">Supported TVs</a>
		<span>/</span>
		<span class="text-slate-700"><?php echo esc_html( $label ); ?></span>
	</nav>

	<h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl"><?php echo esc_html( $label ); ?> supported by Universal TV Remote</h1>
	<p class="mt-3 text-slate-600">
		<?php echo esc_html( number_format_i18n( count( $services ) ) ); ?> <?php echo esc_html( strtolower( $label ) ); ?>
		in our directory work with Universal TV Remote. Open any one to see its Wi-Fi control support and how to set it up with the app.
	</p>

	<ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
		<?php foreach ( $services as $s ) : ?>
			<li>
				<a href="<?php echo esc_url( home_url( '/services/' . $s['domain'] . '/' ) ); ?>" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-brand-300 hover:shadow-sm">
					<?php echo tvr_service_icon_html( $s['id'], $s['name'], 32 ); ?>
					<span class="min-w-0 flex-1">
						<span class="block truncate text-sm font-medium text-slate-900"><?php echo esc_html( $s['name'] ); ?></span>
						<span class="block truncate text-xs text-slate-400"><?php echo esc_html( $s['domain'] ); ?></span>
					</span>
					<?php if ( $s['totp'] ) : ?>
						<span class="flex-none rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Wi-Fi</span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="mt-10 border-t border-slate-100 pt-6">
		<h2 class="mb-3 text-sm font-semibold text-slate-700">Browse other categories</h2>
		<div class="flex flex-wrap gap-2">
			<?php foreach ( $other_categories as $c ) : ?>
				<a href="<?php echo esc_url( home_url( '/services/category/' . $c['key'] . '/' ) ); ?>" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-brand-300 hover:text-brand-700">
					<?php echo esc_html( $c['label'] ); ?>
				</a>
			<?php endforeach; ?>
			<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="rounded-full px-3 py-1.5 text-sm font-medium text-brand-600 hover:text-brand-700">All supported TVs &rarr;</a>
		</div>
	</div>
</div>

<?php get_footer(); ?>
