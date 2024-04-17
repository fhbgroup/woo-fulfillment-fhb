<?php if (!defined( 'KIKA_PLUGIN_URL')) exit; ?>

<div class="wrap">
	<h2><?php _e('Settings','woocommerce-fhb-api'); ?></h2>

	<form method="post" action="<?php echo admin_url('admin-post.php') ?>">
		<input type="hidden" name="action" value="kika_setting_save" />

		<h2><?php _e('Connection','woocommerce-fhb-api'); ?></h2>

		<p><?php _e('You can generate login credentials ','woocommerce-fhb-api'); ?> <a href="https://zoe2.fhb.sk/api"><?php _e('here','woocommerce-fhb-api'); ?></a>.</p>

		<?php wp_nonce_field('kika-api-verify'); ?>

		<table class="form-table">
			<tr>
				<th><label for="appid">API AppId</label></th>
				<td><input name="appid" type="text" id="appid" value="<?php echo get_option('kika_appid') ?>" class="regular-text" /></td>
			</tr>

			<tr>
				<th><label for="secret">API Secret</label></th>
				<td><input name="secret" type="text" id="secret" value="<?php echo get_option('kika_secret') ?>" class="regular-text" /></td>
			</tr>

			<tr>
				<th><label for="sandbox">Sandbox mode</label></th>
				<td>
					<label>
						<input name="sandbox" type="checkbox" value="1" <?php echo get_option('kika_sandbox') ? 'checked' : '' ?> />
						<?php _e('Use test server','woocommerce-fhb-api'); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th><label for="autoimport">Autoimport</label></th>
				<td>
					<label>
						<input name="autoimport" type="checkbox" value="1" <?php echo get_option('kika_autoimport') ? 'checked' : '' ?> />
						Active
					</label>
				</td>
			</tr>
		</table>

		<h2><?php _e('Orders','woocommerce-fhb-api'); ?></h2>

		<table class="form-table">
			<tr>
				<th><label for="service"><?php _e('Default carrier','woocommerce-fhb-api'); ?></label></th>
				<td>
					<select name="service">
						<option value=""></option>
						<?php foreach($services as $service): ?>
							<option value="<?php echo $service->code ?>" <?php selected(get_option('kika_service'), $service->code) ?>>
								<?php echo $service->name ?>
							</option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="prefix">API ID prefix</label></th>
				<td><input name="prefix" type="text" id="prefix" value="<?php echo get_option('kika_prefix') ?>" class="regular-text" maxlength="4" /></td>
			</tr>

			<tr>
				<th><label for="prefixToVariable">API ID prefix to VariableSymbol</label></th>
				<td>
					<label>
						<input name="prefixToVariable" type="checkbox" value="1" <?php echo get_option('kika_prefix_to_variable') ? 'checked' : '' ?> />
						Active
					</label>
				</td>
			</tr>

			<tr>
				<th><label for="groupOrders"><?php _e('Group orders'); ?></label></th>
				<td>
					<label>
						<input name="groupOrders" type="checkbox" value="1" <?php echo get_option('kika_group_orders') ? 'checked' : '' ?> />
						Active
					</label>
				</td>
			</tr>

			<tr>
				<th><label for="ignoreProductPrefix"><?php _e('Ignore product prefix','woocommerce-fhb-api'); ?></label></th>
				<td><input id="ignoreProductPrefix" type="text" name="ignoreProductPrefix" value="<?php echo get_option('kika_ignore_product_prefix') ?>" /></td>
			</tr>

			<tr>
				<th><label for="ignoreCountries"><?php _e('Ignored countries (comma delimited country codes)','woocommerce-fhb-api'); ?></label></th>
				<td><input id="ignoreCountries" type="text" name="ignoreCountries" value="<?php echo get_option('kika_ignore_countries') ?>" /></td>
			</tr>

		</table>

		<h2><?php _e('Mapping of statuses','woocommerce-fhb-api'); ?></h2>

		<table class="form-table">

			<tr>
				<th><label for="confirmed"><?php _e('Notification confirmed','woocommerce-fhb-api'); ?></label></th>
				<td>
					<select name="confirmed">
						<option value=""></option>
						<?php foreach($statuses as $key => $value): ?>
							<option value="<?php echo $key ?>" <?php selected(get_option('kika_notify_confirmed'), $key) ?>>
								<?php echo $value ?>
							</option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="sent"><?php _e('Notification sent','woocommerce-fhb-api'); ?></label></th>
				<td>
					<select name="sent">
						<option value=""></option>
						<?php foreach($statuses as $key => $value): ?>
							<option value="<?php echo $key ?>" <?php selected(get_option('kika_notify_sent'), $key) ?>>
								<?php echo $value ?>
							</option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="delivered"><?php _e('Notification delivered','woocommerce-fhb-api'); ?></label></th>
				<td>
					<select name="delivered" id="">
						<option value=""></option>
						<?php foreach($statuses as $key => $value): ?>
							<option value="<?php echo $key ?>" <?php selected(get_option('kika_notify_delivered'), $key) ?>>
								<?php echo $value ?>
							</option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="confirmed"><?php _e('Notification returned','woocommerce-fhb-api'); ?></label></th>
				<td>
					<select name="returned" id="">
						<option value=""></option>
						<?php foreach($statuses as $key => $value): ?>
							<option value="<?php echo $key ?>" <?php selected(get_option('kika_notify_returned'), $key) ?>>
								<?php echo $value ?>
							</option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="confirmed"><?php _e('Order cancellation on stauses','woocommerce-fhb-api'); ?></label></th>
				<td>
					<select name="delete[]" id="" multiple size="<?php echo count($statuses) ?>">
						<?php foreach($statuses as $key => $value): ?>
							<option value="<?php echo $key ?>" <?php selected(true, in_array($key, get_option('kika_status_delete', []))) ?>>
								<?php echo $value ?>
							</option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>

		</table>

		<h2><?php _e('Payment methods','woocommerce-fhb-api'); ?></h2>

		<table class="form-table">

			<?php foreach($methods as $key => $name): ?>
			<tr>
				<th><label for="<?php echo $key ?>"><?php echo $name ?></label></th>
				<td>
					<label>
						<input name="<?php echo $key ?>" type="checkbox" value="1" <?php echo get_option($key) ? 'checked' : '' ?> />
						<?php _e('Send amount','woocommerce-fhb-api'); ?>
					</label>
				</td>
			</tr>
			<?php endforeach ?>
		</table>

        <h2><?php _e('Invoices','woocommerce-fhb-api'); ?></h2>

		<table class="form-table">

			<tr>
				<th><label for="invoicePrefix"><?php _e('Invoice prefix','woocommerce-fhb-api'); ?></label></th>
				<td><input name="invoicePrefix" type="text" id="invoicePrefix" value="<?php echo get_option('kika_invoice_prefix') ?>" class="regular-text" /></td>
			</tr>

			<tr>
				<th><label for="invoiceField"><?php _e('Invoice field','woocommerce-fhb-api'); ?></label></th>
				<td><input name="invoiceField" type="text" id="invoiceField" value="<?php echo get_option('kika_invoice_field') ?>" class="regular-text" /></td>
			</tr>

		</table>


        <h2><?php _e('Mapping of carriers','woocommerce-fhb-api'); ?></h2>

			<table class="form-table delivery-mapping" style="max-width: 400px;">
				<tr>
					<th><?php _e('Woocommerce carrier','woocommerce-fhb-api'); ?></th>
					<th><?php _e('Fullfilment carrier','woocommerce-fhb-api'); ?></th>
					<th></th>
				</tr>
				<?php if (isset($deliveryMapping)): ?>
					<?php foreach ($deliveryMapping as $idx => $mapping): ?>
						<tr>
							<td>
								<input name="deliveryMapping[<?php echo $idx; ?>]" type="text" id="deliveryMapping[<?php echo $idx; ?>]" value="<?php echo $mapping[0] ?>" class="regular-text" />
							</td>
							<td>
								<select name="deliveryMappingService[<?php echo $idx; ?>]">
									<option value=""></option>
									<?php foreach($services as $service): ?>
										<option value="<?php echo $service->code ?>" <?php if($service->code == $mapping[1]) echo "selected"
										 ?>>
											<?php echo $service->name ?>
										</option>
									<?php endforeach ?>
								</select>
							</td>
							<td>
								<button class="button kika-delivery-mapping-delete fhb_kika_button">Odstrániť</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td>
							<input name="deliveryMapping[0]" type="text" id="deliveryMapping[0]" class="regular-text" />
						</td>
						<td>
							<select name="deliveryMappingService[0]">
								<option value=""></option>
								<?php foreach($services as $service): ?>
									<option value="<?php echo $service->code ?>">
										<?php echo $service->name ?>
									</option>
								<?php endforeach ?>
							</select>
						</td>
						<td/>
				<?php endif; ?>
			</table>
			<br>
			<button class="button kika-delivery-mapping-add fhb_kika_button"><?php _e('Add new line','woocommerce-fhb-api'); ?></button>


		<p class="submit">
			<input type="submit" value="<?php _e('Save','woocommerce-fhb-api'); ?>" class="button-primary" />
		</p>

	</form>

</div>
