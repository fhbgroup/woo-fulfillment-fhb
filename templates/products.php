<?php if (!defined( 'KIKA_PLUGIN_URL')) exit; ?>

<div class="wrap">
	<h2><?php _e('Products', 'woocommerce-fhb-api'); ?></h2>

	<h2><?php _e('Exported / Error / All', 'woocommerce-fhb-api'); ?></h2>

	<div id="snippet-stats">

		<?php echo $stats ?>

	</div>

	<br>

	<button data-url="<?php echo admin_url("admin-ajax.php?action=fhb_kika_export_products&export=$export&nonce=$nonce") ?>" class="button kika-repeat-ajax" data-stop-text="<?php _e('Stop', 'woocommerce-fhb-api'); ?>..." data-end-text="<?php _e('Stopping', 'woocommerce-fhb-api'); ?>..." data-spinner="#products-spinner">
		Export...
	</button>

	<img id="products-spinner" src="<?php echo KIKA_PLUGIN_URL ?>/assets/ajax-loader.gif" alt="" style="margin: 6px; display:none">

	<div id="snippet-logs" data-ajax-append></div>

	<?php if (\Kika\StockSync::isAvailable()): ?>
		<h2><?php _e('Stock sync', 'woocommerce-fhb-api'); ?></h2>

		<button data-url="<?php echo admin_url("admin-ajax.php?action=fhb_kika_stock_sync&nonce=$nonce") ?>" class="button kika-ajax" data-progress-text="<?php _e('Syncing', 'woocommerce-fhb-api'); ?>..." data-spinner="#stock-sync-spinner">
			<?php _e('Sync stock now', 'woocommerce-fhb-api'); ?>
		</button>

		<img id="stock-sync-spinner" src="<?php echo KIKA_PLUGIN_URL ?>/assets/ajax-loader.gif" alt="" style="margin: 6px; display:none">

		<p id="snippet-stock-sync-log"></p>
	<?php endif ?>

</div>
