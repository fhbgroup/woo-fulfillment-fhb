<?php

namespace Kika;

use Kika\Api\RestApiException;
use Kika\Api\V3\ProductApi;
use Kika\Repositories\OrderRepo;


class StockSync
{

	const JOB = 'wp_job_fhb_kika_stock_sync';
	const LOCK = 'kika_stock_sync_lock';

	/** @var ProductApi */
	private $productApi;

	/** @var OrderRepo */
	private $orderRepo;


	public function __construct(ProductApi $productApi, OrderRepo $orderRepo)
	{
		$this->productApi = $productApi;
		$this->orderRepo = $orderRepo;
		add_action(self::JOB, [$this, 'jobSync']);
		add_action('wp_ajax_fhb_kika_stock_sync', [$this, 'ajaxSync']);
	}


	public static function isAvailable()
	{
		return get_option('woocommerce_manage_stock') === 'yes';
	}


	public function jobSync()
	{
		$this->sync(false);
	}


	public function ajaxSync()
	{
		if (!wp_verify_nonce($_GET['nonce'], 'kika-api-verify') || !current_user_can('manage_options')) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}
		set_time_limit(0);

		echo json_encode(['snippets' => ['stock-sync-log' => esc_html($this->sync(true))]]);
		wp_die();
	}


	/**
	 * Manual runs ignore the kika_stock_sync setting, so the sync can be tried before enabling the hourly job.
	 * @return string last logged message - the outcome of the run
	 */
	private function sync($manual)
	{
		$lastMessage = '';
		$log = function($message) use (&$lastMessage) {
			error_log('[Kika StockSync] ' . $message);
			$lastMessage = $message;
		};

		$log('job started (' . ($manual ? 'manual' : 'cron') . ')');

		if (!$manual && !get_option('kika_stock_sync')) {
			$log('stock sync disabled in plugin settings, skipping');
			return $lastMessage;
		}
		if (!self::isAvailable()) {
			$log('WooCommerce stock management disabled, skipping');
			return $lastMessage;
		}
		if (get_transient(self::LOCK)) {
			$log('previous run still in progress (lock set), skipping');
			return $lastMessage;
		}
		set_transient(self::LOCK, 1, 15 * MINUTE_IN_SECONDS);
		$startedAt = microtime(true);

		try {
			// count unexported orders BEFORE reading ZOE stock - an order exported in between
			// is then deducted twice (stock briefly too low) instead of not at all (oversell)
			$pending = $this->orderRepo->fetchUnexportedReducedStock();
			$log(sprintf('unexported orders reserve %d SKU(s): %s', count($pending), json_encode($pending)));

			$freeQuantities = $this->fetchFreeQuantities();
			$updated = $unchanged = $notFound = $notManaged = 0;

			foreach ($freeQuantities as $sku => $free) {
				$productId = wc_get_product_id_by_sku($sku);
				$product = $productId ? wc_get_product($productId) : null;

				if (!$product) {
					$notFound++;
					continue;
				}

				// 'edit' context: variations inheriting stock from parent return false here
				if ($product->get_manage_stock('edit') !== true) {
					$notManaged++;
					continue;
				}

				$reserved = isset($pending[$sku]) ? $pending[$sku] : 0;
				$qty = max(0, $free - $reserved);
				$current = $product->get_stock_quantity();
				if ((int) $current === $qty) {
					$unchanged++;
					continue;
				}

				wc_update_product_stock($product, $qty, 'set');
				$updated++;
				$log(sprintf('SKU %s (product %d): %s -> %d (ZOE free %d, unexported %d)', $sku, $productId, var_export($current, true), $qty, $free, $reserved));
			}

			$log(sprintf(
				'done in %.1fs: %d received from ZOE, %d updated, %d unchanged, %d not found in WC, %d without own stock management',
				microtime(true) - $startedAt, count($freeQuantities), $updated, $unchanged, $notFound, $notManaged
			));
		} catch (RestApiException $e) {
			$log(sprintf('API error (HTTP %d), nothing updated: %s', $e->getCode(), $e->getMessage()));
		} catch (\Throwable $e) {
			$log(sprintf('failed: %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()));
		} finally {
			delete_transient(self::LOCK);
		}

		return $lastMessage;
	}


	/** all pages are fetched before anything is written - a failing page aborts the whole run */
	private function fetchFreeQuantities()
	{
		$result = [];
		$seen = 0;
		$page = 1;

		do {
			$response = $this->productApi->readAll($page++);
			foreach ($response->products as $product) {
				$seen++;
				if ($product->type === 'multi') {
					continue; // bundles have no physical stock of their own
				}
				$result[$product->id] = (int) $product->free_quantity;
			}
		} while (!empty($response->products) && $seen < $response->total);

		error_log(sprintf('[Kika StockSync] fetched %d page(s) from %s, %d of %d products usable (multi products skipped)', $page - 1, $this->productApi->getEndpoint(), count($result), $seen));

		return $result;
	}

}
