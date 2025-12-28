<?php
namespace AFCGlide\Listings;

/**
 * Fired during plugin deactivation.
 *
 * @package AFCGlide_Listings
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Listings_Deactivator {

    /**
     * Deactivate the plugin.
     */
    public static function deactivate() {
        // Security check
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        // Flush rewrite rules so permalinks go back to normal
        flush_rewrite_rules();
    }
}
?>