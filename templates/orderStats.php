<?php if (!defined( 'KIKA_PLUGIN_URL')) exit; ?>

<table class="form-table">
	<tr>
		<th><?php _e('Orders', 'woocommerce-fhb-api'); ?></th>
		<td>
			<span class="text-success"><?php echo $countSynced ?></span> / <span class="text-danger"><?php echo $countError ?></span> / <?php echo $count ?>
		</td>
	</tr>
</table>

<small>(<?php _e('Only orders with status processing are exported', 'woocommerce-fhb-api'); ?>)</small>