<?php
/**
 * Template Name: Troubleshooting
 * Port of app/troubleshooting/page.jsx + components/TroubleshootingClient.jsx
 * — content sourced from the quick_fix/issue/advanced_step CPTs + this
 * page's ACF fields, all editable in wp-admin.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$page_id = get_the_ID();
$heading                = tvr_field( 'heading', $page_id, 'Troubleshooting Guide' );
$subheading              = tvr_field( 'subheading', $page_id, 'Having trouble with Universal TV Remote? Follow these steps to resolve the most common issues quickly.' );
$quick_fixes_heading     = tvr_field( 'quick_fixes_heading', $page_id, 'Quick Fixes' );
$quick_fixes_subheading  = tvr_field( 'quick_fixes_subheading', $page_id, 'Try these first — they resolve most issues in under a minute.' );
$issues_heading          = tvr_field( 'issues_heading', $page_id, 'Common Issues & Solutions' );
$issues_subheading       = tvr_field( 'issues_subheading', $page_id, 'Click any issue to see causes and step-by-step solutions.' );
$advanced_heading        = tvr_field( 'advanced_heading', $page_id, 'Advanced Troubleshooting' );
$advanced_subheading     = tvr_field( 'advanced_subheading', $page_id, 'For persistent issues that the above steps did not resolve.' );
$support_cta_heading     = tvr_field( 'support_cta_heading', $page_id, 'Still need help?' );
$support_cta_text        = tvr_field( 'support_cta_text', $page_id, 'If the issue persists, our support team is happy to help.' );

$quick_fixes = tvr_get_content_posts( 'quick_fix' );
$issues      = tvr_get_content_posts( 'issue' );
$advanced    = tvr_get_content_posts( 'advanced_step' );

$difficulty_color = array(
	'Easy'   => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
	'Medium' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-500/20',
	'Hard'   => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
);

$faq_pairs = array();
foreach ( $issues as $issue ) {
	$faq_pairs[] = array( 'q' => $issue->post_title, 'a' => implode( ' ', tvr_lines( get_field( 'solutions', $issue->ID ) ) ) );
}
tvr_json_ld( tvr_faq_ld( $faq_pairs ) );
?>

<div class="mx-auto max-w-3xl px-4 py-12">

	<div class="mb-10">
		<h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"<?php echo tvr_heading_style_attr( $page_id ); ?>><?php echo esc_html( $heading ); ?></h1>
		<p class="mt-3 text-slate-500"><?php echo esc_html( $subheading ); ?></p>
	</div>

	<section class="mb-12">
		<h2 class="mb-4 text-xl font-bold text-slate-900"><?php echo esc_html( $quick_fixes_heading ); ?></h2>
		<p class="mb-5 text-sm text-slate-500"><?php echo esc_html( $quick_fixes_subheading ); ?></p>
		<div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white">
			<?php foreach ( $quick_fixes as $i => $fix ) : ?>
				<div class="flex items-start gap-4 px-5 py-4">
					<span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-600"><?php echo (int) $i + 1; ?></span>
					<div>
						<p class="font-semibold text-slate-900"><?php echo esc_html( $fix->post_title ); ?></p>
						<p class="mt-0.5 text-sm text-slate-500"><?php echo esc_html( get_field( 'description', $fix->ID ) ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="mb-12">
		<h2 class="mb-4 text-xl font-bold text-slate-900"><?php echo esc_html( $issues_heading ); ?></h2>
		<p class="mb-5 text-sm text-slate-500"><?php echo esc_html( $issues_subheading ); ?></p>
		<div class="space-y-3">
			<?php foreach ( $issues as $i => $issue ) :
				$difficulty = get_field( 'difficulty', $issue->ID ) ?: 'Easy';
				$badge      = $difficulty_color[ $difficulty ] ?? $difficulty_color['Easy'];
				$causes     = tvr_lines( get_field( 'causes', $issue->ID ) );
				$solutions  = tvr_lines( get_field( 'solutions', $issue->ID ) );
				?>
				<details class="group overflow-hidden rounded-xl border border-slate-200 bg-white">
					<summary class="flex w-full cursor-pointer list-none items-start justify-between gap-4 px-6 py-5 text-left transition hover:bg-slate-50">
						<div class="flex flex-wrap items-center gap-2.5">
							<span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-600"><?php echo (int) $i + 1; ?></span>
							<span class="text-base font-semibold text-slate-900"><?php echo esc_html( $issue->post_title ); ?></span>
							<span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo esc_attr( $badge ); ?>"><?php echo esc_html( $difficulty ); ?></span>
							<span class="text-xs text-slate-400"><?php echo esc_html( get_field( 'time', $issue->ID ) ); ?></span>
						</div>
						<svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
						</svg>
					</summary>
					<div class="border-t border-slate-100 px-6 pb-6 pt-5">
						<div class="grid gap-6 sm:grid-cols-2">
							<div>
								<p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Common Causes</p>
								<ul class="space-y-2">
									<?php foreach ( $causes as $c ) : ?>
										<li class="flex items-start gap-2 text-sm text-slate-600">
											<span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>
											<?php echo esc_html( $c ); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
							<div>
								<p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Solutions</p>
								<ol class="space-y-2">
									<?php foreach ( $solutions as $i2 => $s ) : ?>
										<li class="flex items-start gap-2.5 text-sm text-slate-600">
											<span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-500 text-[11px] font-bold text-white"><?php echo (int) $i2 + 1; ?></span>
											<?php echo esc_html( $s ); ?>
										</li>
									<?php endforeach; ?>
								</ol>
							</div>
						</div>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="mb-12">
		<h2 class="mb-4 text-xl font-bold text-slate-900"><?php echo esc_html( $advanced_heading ); ?></h2>
		<p class="mb-5 text-sm text-slate-500"><?php echo esc_html( $advanced_subheading ); ?></p>
		<div class="space-y-4">
			<?php foreach ( $advanced as $section ) :
				$steps = tvr_lines( get_field( 'steps', $section->ID ) );
				?>
				<div class="rounded-xl border border-slate-200 bg-white px-6 py-5">
					<h3 class="mb-4 font-semibold text-slate-900"><?php echo esc_html( $section->post_title ); ?></h3>
					<ol class="space-y-2.5">
						<?php foreach ( $steps as $i => $step ) : ?>
							<li class="flex items-start gap-3 text-sm text-slate-600">
								<span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500"><?php echo (int) $i + 1; ?></span>
								<?php echo esc_html( $step ); ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="rounded-xl border border-slate-200 bg-slate-50 px-6 py-8 text-center">
		<h2 class="text-lg font-bold text-slate-900"><?php echo esc_html( $support_cta_heading ); ?></h2>
		<p class="mt-2 text-sm text-slate-500"><?php echo esc_html( $support_cta_text ); ?></p>
		<div class="mt-5 flex flex-col items-center justify-center gap-3 sm:flex-row">
			<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rounded-full bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">Contact Support</a>
			<a href="<?php echo esc_url( home_url( '/help/' ) ); ?>" class="rounded-full border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400">View FAQs</a>
		</div>
	</section>

</div>

<?php get_footer(); ?>
