<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div <?php echo tvr_reveal_attrs(); ?> class="reveal mb-10 text-center">
	<h2 class="text-3xl font-bold tracking-tight text-navy-900 sm:text-4xl"><?php echo esc_html( $args['title'] ); ?></h2>
	<?php if ( ! empty( $args['subtitle'] ) ) : ?>
		<p class="mt-3 text-lg font-light text-slate-500"><?php echo esc_html( $args['subtitle'] ); ?></p>
	<?php endif; ?>
</div>
