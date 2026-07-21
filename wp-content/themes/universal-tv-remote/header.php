<?php if ( ! defined( 'ABSPATH' ) ) exit; ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="theme-color" content="#001b38" />
<link rel="preload" as="image" href="<?php echo esc_url( tvr_asset( 'app-devices.webp' ) ); ?>" fetchpriority="high" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<?php $fonts_href = 'https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap'; ?>
<link rel="preload" as="style" href="<?php echo esc_url( $fonts_href ); ?>" />
<link id="gfonts" rel="stylesheet" href="<?php echo esc_url( $fonts_href ); ?>" media="print" />
<script>(function(){var l=document.getElementById('gfonts');if(!l)return;function s(){l.media='all'}if(l.sheet)s();else l.addEventListener('load',s)})();</script>
<noscript><link rel="stylesheet" href="<?php echo esc_url( $fonts_href ); ?>" /></noscript>
<link rel="icon" href="<?php echo esc_url( tvr_asset( 'favicon.ico' ) ); ?>" sizes="any" />
<link rel="icon" href="<?php echo esc_url( tvr_asset( 'icon-192.png' ) ); ?>" sizes="192x192" type="image/png" />
<link rel="icon" href="<?php echo esc_url( tvr_asset( 'icon-512.png' ) ); ?>" sizes="512x512" type="image/png" />
<link rel="apple-touch-icon" href="<?php echo esc_url( tvr_asset( 'apple-icon.png' ) ); ?>" />
<link rel="manifest" href="<?php echo esc_url( get_template_directory_uri() . '/assets/manifest.json' ); ?>" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="flex min-h-screen flex-col">
<?php get_template_part( 'template-parts/global/site-header' ); ?>
<main class="flex-1">
