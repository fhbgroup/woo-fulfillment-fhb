<?php

namespace Kika\Repositories;

use WP_Query;
use WC_Product_Variation;
use WC_Product_Variable;
use WC_Product_Factory;


class ProductRepo
{

	const STATUS_KEY = 'fhb-api-status';
	const EXPORT_KEY = 'fhb-api-export';
	const STATUS_SYNCED = 'synced';
	const STATUS_ERROR = 'error';


	public function fetch($args)
	{
		$loop = new WP_Query($args);
		$data = [];

		while ($loop->have_posts()) {
			$loop->the_post();
			global $product;
			$data[] = $this->prepareData($product);
		};

		wp_reset_postdata();
		return $data;
	}


	public function fetchSimpleForExport($export, $limit = 50)
	{
		$args = [
			'post_type'   => 'product',
			'posts_per_page' => $limit,
			'post_status' => 'publish',

			'tax_query' => [
				[
					'taxonomy' => 'product_type',
					'field' => 'slug',
					'terms' => 'simple',
				]
			],

			'meta_query' => [
				'relation' => 'AND',

				[
					'relation' => 'OR',
					[
						'key' => self::STATUS_KEY,
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => self::STATUS_KEY,
						'compare' => '!=',
						'value' => self::STATUS_SYNCED,
					],
				],

				[
					'relation' => 'OR',
					[
						'key' => self::EXPORT_KEY,
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => self::EXPORT_KEY,
						'compare' => '!=',
						'value' => $export,
					],
				],
			]
		];

		return $this->fetch($args);
	}


	public function fetchVariationForExport($export, $limit = 50)
	{
		$args = [
			'post_type' => 'product_variation',
			'posts_per_page' => $limit,
			'post_status' => 'publish',

			'meta_query' => [
				'relation' => 'AND',

				[
					'relation' => 'OR',
					[
						'key' => self::STATUS_KEY,
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => self::STATUS_KEY,
						'compare' => '!=',
						'value' => self::STATUS_SYNCED,
					],
				],

				[
					'relation' => 'OR',
					[
						'key' => self::EXPORT_KEY,
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => self::EXPORT_KEY,
						'compare' => '!=',
						'value' => $export,
					],
				],
			]
		];

		return $this->fetch($args);
	}


	public function fetchVariationsForExportByParent($parent)
	{
		$args = [
			'post_type' => 'product_variation',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'post_parent' => $parent,
			'meta_query' => [
				'relation' => 'OR',
				[
					'key' => self::STATUS_KEY,
					'compare' => 'NOT EXISTS',
				],
				[
					'key' => self::STATUS_KEY,
					'compare' => '!=',
					'value' => self::STATUS_SYNCED,
				],

			]
		];

		return $this->fetch($args);
	}


	public function fetchSimpleOrVariationsById($id)
	{
		$factory = new WC_Product_Factory;
		$product = $factory->get_product($id);

		if (!$product) {
			return [];
		}

		if ($product instanceof WC_Product_Variable) {
			return $this->fetchVariationsForExportByParent($id);
		}

		if (get_post_meta($product->get_id(), self::STATUS_KEY, true) == self::STATUS_SYNCED) {
			return [];
		}

		return [$this->prepareData($product)];
	}


	public function countSimple()
	{
		$args = [
			'post_type'   => 'product',
			'post_status' => 'publish',

			'tax_query' => [
				[
					'taxonomy' => 'product_type',
					'field' => 'slug',
					'terms' => 'simple',
				]
			],
		];

		$loop = new WP_Query($args);
		return $loop->found_posts;
	}


	public function countVariation()
	{
		$args = [
			'post_type' => 'product_variation',
			'post_status' => 'publish',
		];

		$loop = new WP_Query($args);
		return $loop->found_posts;
	}


	public function countSimpleByStatus($status)
	{
		$args = [
			'post_type'   => 'product',
			'post_status' => 'publish',

			'tax_query' => [
				[
					'taxonomy' => 'product_type',
					'field' => 'slug',
					'terms' => 'simple',
				]
			],

			'meta_query' => [
				[
					[
						'key' => self::STATUS_KEY,
						'value' => $status,
					],
				],
			]
		];

		$loop = new WP_Query($args);
		return $loop->found_posts;
	}


	public function countVariationByStatus($status)
	{
		$args = [
			'post_type' => 'product_variation',
			'post_status' => 'publish',

			'meta_query' => [
				[
					[
						'key' => self::STATUS_KEY,
						'value' => $status,
					],
				],
			]
		];

		$loop = new WP_Query($args);
		return $loop->found_posts;
	}


	public function countSimpleSynced()
	{
		return $this->countSimpleByStatus(self::STATUS_SYNCED);
	}


	public function countSimpleError()
	{
		return $this->countSimpleByStatus(self::STATUS_ERROR);
	}


	public function countVariationSynced()
	{
		return $this->countVariationByStatus(self::STATUS_SYNCED);
	}


	public function countVariationError()
	{
		return $this->countVariationByStatus(self::STATUS_ERROR);
	}


	public function prepareData($product)
	{
		if ($product instanceof WC_Product_Variation) {
			$name = $product->get_title() . ', ' . $product->get_formatted_variation_attributes(true);
			$sku = get_post_meta($product->get_id(), '_sku', true);
		} else {
			$name = $product->get_title();
			$sku = $product->get_sku();
		}

		return [
			'product_id' => $product->get_id(),
			'id' => $sku,
			'name' => $name,
			'photoUrl' => wp_get_attachment_url($product->get_image_id())
		];
	}

}