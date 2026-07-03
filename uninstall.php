<?php
/**
 * Uninstall file for Mewshtari Email in Order for WooCommerce
 *
 * @package Mewshtari\EmailInOrder
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Delete plugin options from database.
delete_option('meiofw_templates');
