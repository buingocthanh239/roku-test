<?php
/**
 * Template Name: Contact
 * Port of app/contact/page.jsx + components/ContactForm.jsx — upgraded to
 * send real server-side mail via Contact Form 7 when the plugin is active
 * and a form has been linked (tvr_cf7_form_id option); otherwise falls back
 * to the original's client-only mailto: form so the page always works.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$cf7_active = shortcode_exists( 'contact-form-7' );
$cf7_id     = get_option( 'tvr_cf7_form_id' );
$page_id    = get_the_ID();
$heading    = tvr_field( 'heading', $page_id, 'Contact us' );
$intro      = tvr_field( 'intro', $page_id, 'Spotted an outdated guide or a missing TV brand? Have a question about Universal TV Remote? Send us a message — real humans reply.' );
?>

<div class="mx-auto max-w-2xl px-4 py-16">
	<h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"<?php echo tvr_heading_style_attr( $page_id ); ?>><?php echo esc_html( $heading ); ?></h1>
	<p class="mt-2 text-slate-600"><?php echo esc_html( $intro ); ?></p>

	<?php if ( $cf7_active && $cf7_id ) : ?>
		<div class="mt-8 tvr-cf7">
			<?php echo do_shortcode( '[contact-form-7 id="' . (int) $cf7_id . '"]' ); ?>
		</div>
	<?php else : ?>
		<form class="mt-8 space-y-5" id="tvr-contact-form">
			<div class="grid gap-5 sm:grid-cols-2">
				<label class="block">
					<span class="text-sm font-medium text-slate-700">Name</span>
					<input required id="tvr-contact-name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100" />
				</label>
				<label class="block">
					<span class="text-sm font-medium text-slate-700">Email</span>
					<input type="email" required id="tvr-contact-email" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100" />
				</label>
			</div>
			<label class="block">
				<span class="text-sm font-medium text-slate-700">Message</span>
				<textarea required rows="5" id="tvr-contact-message" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100"></textarea>
			</label>
			<button type="submit" class="rounded-full bg-brand-600 px-6 py-3 font-semibold text-white transition hover:bg-brand-700">Send message</button>
			<p class="text-sm text-slate-500">
				This opens your mail app. You can also email us directly at
				<a href="mailto:<?php echo esc_attr( TVR_CONTACT_EMAIL ); ?>" class="font-medium text-brand-600 hover:text-brand-700"><?php echo esc_html( TVR_CONTACT_EMAIL ); ?></a>.
			</p>
		</form>
		<script>
		document.getElementById('tvr-contact-form').addEventListener('submit', function (e) {
			e.preventDefault();
			var name = document.getElementById('tvr-contact-name').value;
			var email = document.getElementById('tvr-contact-email').value;
			var message = document.getElementById('tvr-contact-message').value;
			var subject = 'Contact from ' + name;
			var body = message + '\n\n— ' + name + ' (' + email + ')';
			window.location.href = 'mailto:<?php echo esc_js( TVR_CONTACT_EMAIL ); ?>?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
		});
		</script>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
