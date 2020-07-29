<?php if (!defined( 'KIKA_PLUGIN_URL')) exit; ?>

<div class="wrap">
	<h2><?php _e('Orders', 'woocommerce-fhb-api'); ?></h2>

	<h2><?php _e('Exported / Error / All', 'woocommerce-fhb-api'); ?></h2>

	<div id="snippet-stats">

		<?php echo $stats ?>

	</div>

	<br>

	<button data-url="<?php echo admin_url("admin-ajax.php?action=fhb_kika_export_orders&export=$export&nonce=$nonce") ?>" class="button kika-repeat-ajax fhb_kika_button" data-stop-text="<?php _e('Stop', 'woocommerce-fhb-api'); ?>..." data-end-text="<?php _e('Stopping', 'woocommerce-fhb-api'); ?>..." data-spinner="#orders-spinner">
		Export...
	</button>

	<img id="orders-spinner" src="<?php echo KIKA_PLUGIN_URL ?>/assets/ajax-loader.gif" alt="" style="margin: 6px; display:none">

	<div id="snippet-logs" data-ajax-append></div>

</div>
