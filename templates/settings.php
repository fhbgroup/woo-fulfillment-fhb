<?php if (!defined( 'KIKA_PLUGIN_URL')) exit; ?>

<div class="wrap">
	<h2>Nastavenie</h2>

	<form method="post" action="<?php echo admin_url('admin-post.php') ?>">
		<input type="hidden" name="action" value="kika_setting_save" />

		<h2>Autorizácia</h2>

		<p>Prístupové údaje si môžete vygenerovať <a href="https://system.fhb.sk/zoe/api/">tu</a>.</p>

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
				<th><label for="sandbox">Sandbox mod</label></th>
				<td>
					<label>
						<input name="sandbox" type="checkbox" value="1" <?php echo get_option('kika_sandbox') ? 'checked' : '' ?> />
						Použiť testovací server
					</label>
				</td>
			</tr>
		</table>

		<h2>Objednávky</h2>

		<table class="form-table">
			<tr>
				<th><label for="service">Default prepravca</label></th>
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
				<th><label for="secret">Prefix API Id</label></th>
				<td><input name="prefix" type="text" id="secret" value="<?php echo get_option('kika_prefix') ?>" class="regular-text" maxlength="4" /></td>
			</tr>

		</table>

		<h2>Mapovanie statusov</h2>

		<table class="form-table">

			<tr>
				<th><label for="confirmed">Notifikácia confirmed</label></th>
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
				<th><label for="sent">Notifikácia sent</label></th>
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
				<th><label for="delivered">Notifikácia delivered</label></th>
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
				<th><label for="confirmed">Notifikácia returned</label></th>
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
				<th><label for="confirmed">Zrušenie objednávky</label></th>
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

		<h2>Platobné metódy</h2>

		<table class="form-table">

			<?php foreach($methods as $key => $name): ?>
			<tr>
				<th><label for="<?php echo $key ?>"><?php echo $name ?></label></th>
				<td>
					<label>
						<input name="<?php echo $key ?>" type="checkbox" value="1" <?php echo get_option($key) ? 'checked' : '' ?> />
						Posielať cenu do api
					</label>
				</td>
			</tr>
			<?php endforeach ?>
		</table>

        <h2>Faktúry</h2>

		<table class="form-table">

			<tr>
				<th><label for="invoicePrefix">Prefix faktúry</label></th>
				<td><input name="invoicePrefix" type="text" id="invoicePrefix" value="<?php echo get_option('kika_invoice_prefix') ?>" class="regular-text" /></td>
			</tr>

			<tr>
				<th><label for="invoiceField">Pole s faktúrou</label></th>
				<td><input name="invoiceField" type="text" id="invoiceField" value="<?php echo get_option('kika_invoice_field') ?>" class="regular-text" /></td>
			</tr>

		</table>


        <h2>Mapovanie prepravcov</h2>

			<table class="form-table delivery-mapping" style="max-width: 400px;">
				<tr>
					<th>Woocommerce prepravca</th>
					<th>Fullfilment prepravca</th>
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
			<button class="button kika-delivery-mapping-add fhb_kika_button">Pridať riadok</button>


		<p class="submit">
			<input type="submit" value="Uložiť" class="button-primary" />
		</p>

	</form>

</div>
