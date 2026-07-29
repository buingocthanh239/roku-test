<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$qr      = $args['qr'] ?? true;
$center  = $args['center'] ?? true;
$light   = $args['light'] ?? false;
$links   = tvr_app_links();
$ios_url = $args['ios_url'] ?? $links['ios'];
$mac_url = $args['mac_url'] ?? $links['mac'];
?>
<div class="flex items-center gap-5 <?php echo $center ? 'justify-center' : ''; ?>">
	<?php if ( $qr ) : ?>
		<div class="hidden sm:block">
			<img src="<?php echo esc_url( tvr_asset( 'qr-ios.png' ) ); ?>" alt="Scan to download" width="130" height="130" class="rounded-xl bg-white p-2 shadow-sm ring-1 ring-black/10" />
		</div>
	<?php endif; ?>
	<div class="flex flex-col gap-2">
		<a href="<?php echo esc_url( $ios_url ); ?>" target="_blank" rel="noopener noreferrer">
			<img src="<?php echo esc_url( tvr_asset( 'appStore.svg' ) ); ?>" alt="Download on the App Store" class="h-10 w-auto sm:h-[52px]" />
		</a>
		<a href="<?php echo esc_url( $mac_url ); ?>" target="_blank" rel="noopener noreferrer">
			<img src="<?php echo esc_url( tvr_asset( 'macAppStore.svg' ) ); ?>" alt="Download on the Mac App Store" class="h-10 w-auto sm:h-[52px]" />
		</a>
		<?php if ( $qr ) : ?>
			<span class="hidden sm:block text-sm leading-snug <?php echo $light ? 'text-slate-500' : 'text-slate-300'; ?>">Scan the QR code to download on your iPhone</span>
		<?php endif; ?>
	</div>
</div>
