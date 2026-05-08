<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- uninstall.php is a flat file, all variables are local to this execution context.

$p3dmv_sites_list = array(get_current_blog_id());

if (is_multisite()) {
	$p3dmv_sites_list = get_sites([
		'fields' => 'ids',
	]);
}

foreach ($p3dmv_sites_list as $p3dmv_site_id_value) {
	if (is_multisite()) {
		switch_to_blog((int) $p3dmv_site_id_value);
	}

	$p3dmv_menu_items_list = get_posts([
		'post_type'      => 'nav_menu_item',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	]);

	foreach ($p3dmv_menu_items_list as $p3dmv_menu_item_id_value) {
		delete_post_meta($p3dmv_menu_item_id_value, '_p3dmv_roles');
		delete_post_meta($p3dmv_menu_item_id_value, '_p3dmv_mode');
	}

	if (is_multisite()) {
		restore_current_blog();
	}
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound