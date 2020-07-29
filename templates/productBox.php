<?php if (!defined( 'KIKA_PLUGIN_URL')) exit; ?>

<button type="button" data-url="<?php echo admin_url("admin-ajax.php?action=fhb_kika_export_product&product=$post_id&nonce=$nonce") ?>" class="button kika-ajax" data-progress-text="<?php _e('Exporting', 'woocommerce-fhb-api'); ?>..." data-spinner="#product-spinner">
	Export...
</button>

<img id="product-spinner" src="<?php echo KIKA_PLUGIN_URL ?>/assets/ajax-loader.gif" alt="" style="margin: 6px; display:none">

<div id="snippet-logs" class="log-box"></div>
