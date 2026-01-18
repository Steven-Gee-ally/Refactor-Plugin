<?php
namespace AFCGlide\Core;

class AFCGlide_Checker {
    public static function verify_integrity() {
        $required_folders = ['assets/css', 'assets/js', 'templates', 'includes'];
        $missing = [];

        foreach ($required_folders as $folder) {
            if (!file_exists(AFCG_PATH . $folder)) {
                $missing[] = $folder;
            }
        }

        if (!empty($missing)) {
            add_action('admin_notices', function() use ($missing) {
                echo '<div class="notice notice-error"><p><strong>AFCGlide Alert:</strong> Missing core folders: ' . implode(', ', $missing) . '. Deployment is incomplete!</p></div>';
            });
        }
        
        // Check if Agent Info is set in the new Settings Page
        if (!get_option('afc_agent_name')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p><strong>Setup Required:</strong> Please enter the Agent Name in <a href="admin.php?page=afcglide-settings">AFCGlide Settings</a> to activate the backbone.</p></div>';
            });
        }
    }
}
// Add to your Master File: \AFCGlide\Core\AFCGlide_Checker::verify_integrity();
