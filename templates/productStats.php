<?php if (!defined( 'KIKA_PLUGIN_URL')) exit; ?>

<table class="form-table">
	<tr>
		<th><?php _e('Simple products', 'woocommerce-fhb-api'); ?></th>
		<td>
			<span class="text-success"><?php echo $countSimpleSynced ?></span> / <span class="text-danger"><?php echo $countSimpleError ?></span> / <?php echo $countSimple ?>
		</td>
	</tr>

	<tr>
		<th><?php _e('Variable products', 'woocommerce-fhb-api'); ?></th>
		<td>
			<span class="text-success"><?php echo $countVariationSynced ?></span> / <span  class="text-danger"><?php echo $countVariationError ?></span> / <?php echo $countVariation ?>
		</td>
	</tr>
</table>