<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

if (wp_next_scheduled('wp_job_fhb_kika_export_order')) {
    wp_clear_scheduled_hook('wp_job_fhb_kika_export_order');
}