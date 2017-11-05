<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

global $wpdb;

$plugin_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'kika_%'" );

foreach($plugin_options as $option) {
	delete_option($option->option_name);
}