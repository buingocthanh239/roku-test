<?php
/**
 * /services/{domain}/ — port of app/services/[slug]/page.jsx.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
the_post();

$post_id = get_the_ID();
$service = tvr_service_by_post( get_post() );
$name    = $service['name'];
$is_model = $service['parent_id'] > 0;
$brand_service = $is_model ? tvr_find_brand_by_domain( $service ) : null;
$models  = ! $is_model ? tvr_models_by_brand( $post_id ) : array();
$related = tvr_related_brands( $service );
$links   = tvr_app_links();

$breadcrumb_items = array(
	array( 'name' => 'Home', 'url' => home_url( '/' ) ),
	array( 'name' => 'Supported TVs', 'url' => home_url( '/services/' ) ),
);
if ( $brand_service ) {
	$breadcrumb_items[] = array( 'name' => $brand_service['name'], 'url' => home_url( '/services/' . $brand_service['domain'] . '/' ) );
}
$breadcrumb_items[] = array( 'name' => $name, 'url' => home_url( '/services/' . $service['domain'] . '/' ) );

tvr_json_ld( tvr_breadcrumb_ld( $breadcrumb_items ) );
?>

<div class="mx-auto max-w-3xl px-4 py-10">
	<nav class="mb-6 flex items-center gap-1.5 text-sm text-slate-500">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-brand-600">Home</a>
		<span>/</span>
		<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="hover:text-brand-600">Supported TVs</a>
		<?php if ( $brand_service ) : ?>
			<span>/</span>
			<a href="<?php echo esc_url( home_url( '/services/' . $brand_service['domain'] . '/' ) ); ?>" class="hover:text-brand-600"><?php echo esc_html( $brand_service['name'] ); ?></a>
		<?php endif; ?>
		<span>/</span>
		<span class="text-slate-700"><?php echo esc_html( $is_model && $service['model'] ? $service['model'] : $name ); ?></span>
	</nav>

	<div class="flex items-center gap-4">
		<?php echo tvr_service_icon_html( $post_id, $name, 64, 'border border-slate-200 p-1' ); ?>
		<div>
			<h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Universal TV Remote Control for <?php echo esc_html( $name ); ?></h1>
			<p class="mt-1 text-sm text-slate-500">How to Use Your Phone as a Remote for <?php echo esc_html( $name ); ?></p>
		</div>
	</div>

	<div class="mt-6 space-y-4 text-slate-600">
		<p>Turn your smartphone into a <strong class="text-slate-900"><?php echo esc_html( $name ); ?></strong> remote in just a few minutes.</p>
		<p>This guide explains how to connect your phone to a <?php echo esc_html( $name ); ?> and use remote features such as volume controls, channel navigation, power controls, touchpad support, and text input.</p>
		<p>
			Before starting, download
			<a href="<?php echo esc_url( $links['ios'] ); ?>" target="_blank" rel="noopener noreferrer" class="font-bold text-slate-900 underline decoration-brand-500 decoration-2">Universal TV Remote Control</a>
			and connect both devices to the same Wi-Fi network.
		</p>
	</div>

	<div class="mt-6 overflow-hidden rounded-2xl bg-gradient-to-br from-navy-900 via-navy-900 to-slate-800">
		<div class="flex flex-col items-center gap-6 p-6 text-center sm:flex-row sm:items-center sm:gap-8 sm:text-left">
			<img src="<?php echo esc_url( tvr_asset( 'logo.png' ) ); ?>" alt="Universal TV Remote" class="h-14 w-14 flex-shrink-0 rounded-2xl shadow-lg" />
			<div class="min-w-0 flex-1">
				<h3 class="text-base font-bold text-white">Get Universal TV Remote</h3>
				<p class="mt-1 text-sm leading-relaxed text-slate-400">Control your <span class="text-slate-200"><?php echo esc_html( $name ); ?></span> TV from your phone &mdash; free to download.</p>
			</div>
			<div class="flex flex-shrink-0 items-center gap-4">
				<img src="<?php echo esc_url( tvr_asset( 'qr-ios.png' ) ); ?>" alt="Scan to download" class="h-20 w-20 rounded-lg bg-white p-1" />
				<div class="flex flex-col gap-1">
					<a href="<?php echo esc_url( $links['ios'] ); ?>" target="_blank" rel="noopener noreferrer">
						<img src="<?php echo esc_url( tvr_asset( 'appStore.svg' ) ); ?>" alt="Download on the App Store" class="h-10 w-auto" />
					</a>
					<p class="text-xs leading-snug text-slate-400">Scan QR to download on iPhone</p>
				</div>
			</div>
		</div>
	</div>

	<?php if ( ! empty( $models ) ) : ?>
		<section class="border-t border-slate-100 py-6">
			<h2 class="text-xl font-bold text-slate-900">Supported <?php echo esc_html( $name ); ?> Models</h2>
			<div class="mt-3 grid gap-2 sm:grid-cols-3">
				<?php foreach ( $models as $m ) :
					$m_model = get_post_meta( $m->ID, '_tv_model', true );
					?>
					<a href="<?php echo esc_url( home_url( '/services/' . $m->post_name . '/' ) ); ?>" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-brand-300 hover:text-brand-700 hover:shadow-sm">
						<?php echo esc_html( $m_model ?: $m->post_title ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $related ) && ! $is_model ) : ?>
		<section class="border-t border-slate-100 py-6">
			<h2 class="text-xl font-bold text-slate-900">Related TV brands</h2>
			<div class="mt-3 grid gap-3 sm:grid-cols-3">
				<?php foreach ( $related as $r ) : ?>
					<a href="<?php echo esc_url( home_url( '/services/' . $r['domain'] . '/' ) ); ?>" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-brand-300 hover:shadow-sm">
						<?php echo tvr_service_icon_html( $r['id'], $r['name'], 32 ); ?>
						<span class="truncate text-sm font-medium text-slate-800"><?php echo esc_html( $r['name'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<div class="mt-8 rounded-xl bg-slate-100 p-4 text-xs leading-relaxed text-slate-500">
		<p class="font-semibold text-slate-600">Disclaimer</p>
		<p class="mt-1">This content is for educational purposes only.</p>
		<p class="mt-2">Begamob is not affiliated with or endorsed by <?php echo esc_html( $name ); ?>. All trademarks and product names are the property of their respective owners and are used solely for identification purposes.</p>
	</div>
</div>

<?php get_footer(); ?>
