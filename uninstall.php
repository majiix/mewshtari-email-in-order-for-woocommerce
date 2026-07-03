<?php
/**
 * Uninstall File.
 * Cleans up options database entries on plugin deletion.
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'mewshtari_email_templates' );
