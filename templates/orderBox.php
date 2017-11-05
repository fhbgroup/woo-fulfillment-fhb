<?php if (!defined( 'KIKA_PLUGIN_URL')) exit; ?>

<?php if($exported): ?>

	Exportovaná apiId: <span class="text-danger"><?php echo $post_id ?></span>

<?php else: ?>

	<table class="box-form">
		<tr>
			<td><label for="kika-cod">COD:</label></td>
			<td><input id="kika-cod" type="number" name="kika-cod" value="<?php echo get_option('kika_method_' . $order->payment_method) ? $order->get_total() : '' ?>" /></td>
		</tr>

		<tr>
			<td><label for="kika-service">Dopravca:</label></td>
			<td>
				<select id="kika-service" name="kika-service">
					<option value=""></option>
					<?php foreach($services as $service): ?>
						<option value="<?php echo $service->code ?>" <?php selected(get_option('kika_service'), $service->code) ?>>
							<?php echo $service->name ?>
						</option>
					<?php endforeach ?>
				</select>
			</td>
		</tr>
	</table>

	<button type="button" data-url="<?php echo admin_url("admin-ajax.php?action=fhb_kika_export_order&order=$post_id&nonce=$nonce") ?>" class="button kika-order-ajax" data-progress-text="Exportujem..." data-spinner="#product-spinner">
		Export...
	</button>

	<img id="product-spinner" src="<?php echo KIKA_PLUGIN_URL ?>/assets/ajax-loader.gif" alt="" style="margin: 6px; display:none">

	<div id="snippet-logs" class="log-box"></div>

<?php endif ?>
