<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<div class="mx-auto max-w-3xl px-4 py-16">
	<?php while ( have_posts() ) : the_post(); ?>
		<h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"><?php the_title(); ?></h1>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mt-6 overflow-hidden rounded-2xl"><?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto object-cover' ) ); ?></div>
		<?php endif; ?>
		<div class="prose mt-4 max-w-none text-slate-600"><?php the_content(); ?></div>
	<?php endwhile; ?>
</div>
<?php get_footer(); ?>
