<?php

namespace Kika;

use Kika\Api\ProductApi;
use Kika\Api\RestApiException;
use Kika\Repositories\ProductRepo;


class Products
{

	/** @var ProductApi */
	private $productApi;

	/** @var ProductRepo */
	private $productRepo;

	private $sandbox = false;


	public function __construct(ProductApi $productApi, ProductRepo $productRepo, $sandbox = false)
	{
		$this->productApi = $productApi;
		$this->productRepo = $productRepo;
		$this->sandbox = $sandbox;
		add_action('admin_menu', [$this, 'addMenuItems']);
		add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
		add_action('wp_ajax_fhb_kika_export_products', [$this, 'export']);
		add_action('wp_ajax_fhb_kika_export_product', [$this, 'exportSingle']);
	}


	public function addMenuItems()
	{
		add_submenu_page('kika-api', __('Products', 'woocommerce-fhb-api'), __('Products', 'woocommerce-fhb-api'), 'manage_options', 'kika-api-products', [$this, 'render']);
	}


	public function render()
	{
		$export = time();
		$nonce = wp_create_nonce('kika-api-verify');
		$stats = $this->createStats();
		require 'templates/products.php';
	}


	public function addMetaBoxes()
	{
		add_meta_box('wc_fhb_kika_product', 'FHB Kika API', [$this, 'renderBox'], 'product', 'side');
	}


	public function renderBox()
	{
		global $post_id;
		$nonce = wp_create_nonce('kika-api-verify');
		require 'templates/productBox.php';
	}


	public function export()
	{
		set_time_limit(0);
		$export = (int)$_GET['export'];

		if (!wp_verify_nonce( $_GET['nonce'], 'kika-api-verify') or !current_user_can( 'manage_options')) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}

		$products = $this->productRepo->fetchSimpleForExport($export);

		if (!$products) {
			$products = $this->productRepo->fetchVariationForExport($export);
		}

		if (!$products) {
			header('HTTP/1.0 404 Products not found.');
			echo($export);
			exit;
		}

		$logs = $this->exportProducts($products, $export);

		$result = [
			'snippets' => [
				'stats' => $this->createStats(),
				'logs' => $logs ? join('<br>', $logs) . '<br>' : '',
			],
		];

		echo json_encode($result);
		wp_die();
	}


	public function exportSingle()
	{
		$product = (int)$_GET['product'];

		if (!wp_verify_nonce( $_GET['nonce'], 'kika-api-verify') or !current_user_can( 'manage_options')) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}

		$products  = $this->productRepo->fetchSimpleOrVariationsById($product);

		$logs = $this->exportProducts($products, time());

		$result = [
			'snippets' => [
				'logs' => $logs ? join('<br>', $logs) . '<br>' : '<span class="log-error">' . __('Product exported', 'woocommerce-fhb-api') . '</span>',
			],
		];

		echo json_encode($result);
		wp_die();
	}


	private function createStats()
	{
		$countSimple = $this->productRepo->countSimple();
		$countVariation = $this->productRepo->countVariation();
		$countSimpleSynced = $this->productRepo->countSimpleSynced();
		$countSimpleError = $this->productRepo->countSimpleError();
		$countVariationSynced = $this->productRepo->countVariationSynced();
		$countVariationError = $this->productRepo->countVariationError();

		ob_start();
		require 'templates/productStats.php';
		return ob_get_clean();
	}


	private function createErrorMessage(RestApiException $e, $product)
	{
		if ($e->getCode() == 409) {
			return $product['name'] . ' ... ' . '<span class="log-error">duplictiné SKU</span>';
		}

		if ($e->getCode() == 400 and !$product['id']) {
			return $product['name'] . ' ... ' . '<span class="log-error"">chýba SKU</span>';
		}

		return $product['name'] . ' .... <span class="log-error">' . $e->getMessage() . '</span>';
	}


	private function exportProducts($products, $export)
	{
		$logs = [];

		foreach ($products as $product) {

			$this->updateProductInfo($product['product_id'], ProductRepo::EXPORT_KEY, $export);

			try {
				$this->productApi->create($product);
				$this->updateProductInfo($product['product_id'], ProductRepo::STATUS_KEY, ProductRepo::STATUS_SYNCED);

			} catch (RestApiException $e) {
				$this->updateProductInfo($product['product_id'], ProductRepo::STATUS_KEY, ProductRepo::STATUS_ERROR);
				$logs[] = $this->createErrorMessage($e, $product);
			}
		}

		return $logs;
	}


	private function updateProductInfo($productId, $key, $value)
	{
		if(!$this->sandbox) {
			update_post_meta($productId, $key, $value);
		}
	}


    public function setAutoExport($post_ids, $value)
    {
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, ProductRepo::AUTOEXPORT_KEY, $value);
        }
    }

}