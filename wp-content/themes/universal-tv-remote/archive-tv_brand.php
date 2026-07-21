<?php
/**
 * /services/ — port of app/services/page.jsx + components/DirectoryClient.jsx.
 * Renders the FULL brand list server-side (not the paginated main query —
 * see tvr_get_all_brands()) with data-attributes for the JS filter in
 * assets/js/main.js#initDirectoryFilter, plus real crawlable links to every
 * category hub below (the pills above are JS-only filters, not links).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$brands     = tvr_get_all_brands();
$categories = tvr_all_categories();
$total      = count( $brands );
?>

<div class="mx-auto max-w-6xl px-4 py-12">
	<header class="mb-8">
		<h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Supported TVs</h1>
		<p class="mt-2 text-slate-600">A list of <?php echo esc_html( number_format_i18n( $total ) ); ?> TV brands and models supported by Universal TV Remote.</p>
	</header>

	<div class="relative mb-5">
		<svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
			<circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3-3" />
		</svg>
		<input type="search" id="tvr-directory-search" placeholder="Search by brand name or type&hellip;" class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-12 pr-4 text-slate-800 shadow-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-100" />
	</div>

	<div id="tvr-directory-pills" class="mb-6 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] sm:flex-wrap sm:overflow-visible sm:pb-0 [&::-webkit-scrollbar]:hidden">
		<button type="button" data-category="" data-label="" class="flex-none rounded-full bg-brand-600 px-3 py-2 text-sm font-medium text-white transition">All</button>
		<?php foreach ( $categories as $c ) : ?>
			<button type="button" data-category="<?php echo esc_attr( $c['key'] ); ?>" data-label="<?php echo esc_attr( $c['label'] ); ?>" class="flex-none rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-brand-300">
				<?php echo esc_html( $c['label'] ); ?> <span class="text-slate-400"><?php echo esc_html( $c['count'] ); ?></span>
			</button>
		<?php endforeach; ?>
	</div>

	<p class="mb-3 text-sm text-slate-500" id="tvr-directory-count"><?php echo esc_html( number_format_i18n( $total ) ); ?> results</p>

	<div id="tvr-directory-empty" class="hidden rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center text-slate-500">No TV brands match your search.</div>

	<div id="tvr-directory-table-wrap" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
		<table class="w-full min-w-[480px] text-sm">
			<thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
				<tr>
					<th class="hidden w-12 px-4 py-3 text-center font-medium sm:table-cell">#</th>
					<th class="px-4 py-3 font-medium">Brand</th>
					<th class="hidden px-4 py-3 font-medium sm:table-cell">Category</th>
					<th class="px-4 py-3 text-center font-medium">Wi-Fi Control</th>
					<th class="hidden px-4 py-3 sm:table-cell"></th>
				</tr>
			</thead>
			<tbody id="tvr-directory-body" class="divide-y divide-slate-100">
				<?php foreach ( $brands as $i => $s ) :
					$url = home_url( '/services/' . $s['domain'] . '/' );
					$category_label = $s['primary_category'] ? tvr_category_label( $s['primary_category'] ) : '&mdash;';
					?>
					<tr class="cursor-pointer transition hover:bg-slate-50"
						data-url="<?php echo esc_url( $url ); ?>"
						data-name="<?php echo esc_attr( strtolower( $s['name'] ) ); ?>"
						data-domain="<?php echo esc_attr( strtolower( $s['domain'] ) ); ?>"
						data-keywords="<?php echo esc_attr( implode( ',', $s['categories'] ) ); ?>">
						<td class="hidden px-4 py-3.5 text-center text-slate-400 sm:table-cell" data-role="row-number"><?php echo (int) $i + 1; ?></td>
						<td class="px-4 py-3.5">
							<a href="<?php echo esc_url( $url ); ?>" class="flex items-center gap-3" onclick="event.stopPropagation()">
								<?php echo tvr_service_icon_html( $s['id'], $s['name'], 36 ); ?>
								<span class="font-medium text-slate-900"><?php echo esc_html( $s['name'] ); ?></span>
							</a>
						</td>
						<td class="hidden px-4 py-3.5 text-slate-500 sm:table-cell"><?php echo $category_label === '&mdash;' ? '&mdash;' : esc_html( $category_label ); ?></td>
						<td class="px-4 py-3.5 text-center">
							<?php if ( $s['totp'] ) : ?>
								<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
									<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
									Supported
								</span>
							<?php else : ?>
								<span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-300">IR / Basic</span>
							<?php endif; ?>
						</td>
						<td class="hidden px-4 py-3.5 text-right sm:table-cell">
							<a href="<?php echo esc_url( $url ); ?>" class="text-sm font-medium text-brand-600 hover:text-brand-700" onclick="event.stopPropagation()">Details &rarr;</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<nav aria-label="Browse by category" class="mx-auto max-w-6xl px-4 pb-10">
	<h2 class="mb-3 text-sm font-semibold text-slate-700">Browse by category</h2>
	<div class="flex flex-wrap gap-2">
		<?php foreach ( $categories as $c ) : ?>
			<a href="<?php echo esc_url( home_url( '/services/category/' . $c['key'] . '/' ) ); ?>" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-brand-300 hover:text-brand-700">
				<?php echo esc_html( $c['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</nav>

<?php get_footer(); ?>
