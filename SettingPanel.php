<?php

namespace Kika;

use Kika\Repositories\ParcelServiceRepo;
use WC_Payment_Gateways;


class SettingPanel
{

	/** @var ParcelServiceRepo */
	private $parcelServiceRepo;


	public function __construct(ParcelServiceRepo $parcelServiceRepo)
	{
		$this->parcelServiceRepo = $parcelServiceRepo;
		add_action('admin_menu', [$this, 'addMenuItem']);
		add_action('admin_post_kika_setting_save', [$this, 'process']);
	}


	public function addMenuItem()
	{
		add_submenu_page('kika-api', __('Settings', 'woocommerce-fhb-api'), __('Settings', 'woocommerce-fhb-api'), 'manage_options', 'kika-api-setting', [$this, 'render']);
	}


	public function render()
	{
		$statuses = wc_get_order_statuses();
		$services = $this->parcelServiceRepo->fetch(true);

		$gateways = new WC_Payment_Gateways();
		$methods = [];
		foreach($gateways->payment_gateways() as $method) {
			if($method->enabled !== 'yes') {
				continue;
			}
			$methods['kika_method_' . $method->id] = $method->title;
		}

		$loadedMapping = get_option('kika_delivery_service_mapping');
		$loadedMapping = unserialize($loadedMapping);

		foreach ($loadedMapping as $key => $value) {
			$deliveryMapping[] = [$key, $value];
		}

		require 'templates/settings.php';
	}


	public function process()
	{
		if (!current_user_can( 'manage_options'))
		{
			wp_die('You are not allowed to be on this page.');
		}

		check_admin_referer( 'kika-api-verify' );

		update_option('kika_appid', sanitize_text_field($_POST['appid']));
		update_option('kika_secret', sanitize_text_field($_POST['secret']));

		update_option('kika_notify_confirmed', sanitize_text_field($_POST['confirmed']));
		update_option('kika_notify_sent', sanitize_text_field($_POST['sent']));
		update_option('kika_notify_delivered', sanitize_text_field($_POST['delivered']));
		update_option('kika_notify_returned', sanitize_text_field($_POST['returned']));
		update_option('kika_service', sanitize_text_field($_POST['service']));
		update_option('kika_sandbox', sanitize_text_field($_POST['sandbox']));
		update_option('kika_prefix', sanitize_text_field($_POST['prefix']));
		update_option('kika_ignore_product_prefix', sanitize_text_field($_POST['ignoreProductPrefix']));
		update_option('kika_ignore_countries', sanitize_text_field($_POST['ignoreCountries']));
		update_option('kika_status_delete', is_array($_POST['delete']) ? array_map('sanitize_text_field', $_POST['delete']) : null);

		$autoimport = sanitize_text_field($_POST['autoimport']);
		if($autoimport) {
		    if (!wp_next_scheduled('wp_job_fhb_kika_export_order')) {
		        wp_schedule_event(time() + 3600, 'hourly', 'wp_job_fhb_kika_export_order');
		    }
		} else {
		    if (wp_next_scheduled('wp_job_fhb_kika_export_order')) {
	        	wp_clear_scheduled_hook('wp_job_fhb_kika_export_order');
		    }
		}
		update_option('kika_autoimport', $autoimport);

		$gateways = new WC_Payment_Gateways();
		foreach($gateways->payment_gateways() as $method) {
			if($method->enabled !== 'yes') {
				continue;
			}
			$id = 'kika_method_' . $method->id;
			update_option($id, sanitize_text_field($_POST[$id]));
		}

        update_option('kika_invoice_prefix', sanitize_text_field($_POST['invoicePrefix']));
		update_option('kika_invoice_field', sanitize_text_field($_POST['invoiceField']));

        $mappingSave = [];
		foreach ($_POST['deliveryMapping'] as $key => $value) {
			$mappingSave[sanitize_text_field($value)] = sanitize_text_field($_POST['deliveryMappingService'][$key]);
		}

		update_option('kika_delivery_service_mapping', serialize($mappingSave));

		wp_redirect(admin_url('admin.php?page=kika-api-setting&m=1'));
		exit;
	}

}