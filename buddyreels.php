<?php
/**
 * Plugin Name: BuddyReels
 * Plugin URI: https://example.com/buddyreels
 * Description: TikTok-style video sharing system integrated with BuddyPress
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: buddyreels
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('BUDDYREELS_VERSION', '1.0.0');
define('BUDDYREELS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BUDDYREELS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_buddyreels() {
    require_once BUDDYREELS_PLUGIN_DIR . 'includes/class-buddyreels.php';
    BuddyReels::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_buddyreels() {
    require_once BUDDYREELS_PLUGIN_DIR . 'includes/class-buddyreels.php';
    BuddyReels::deactivate();
}

register_activation_hook(__FILE__, 'activate_buddyreels');
register_deactivation_hook(__FILE__, 'deactivate_buddyreels');

/**
 * Check if BuddyPress is active
 */
function buddyreels_check_buddypress() {
    // Multiple checks to ensure BuddyPress is available
    $checks = array(
        function_exists('buddypress'),
        class_exists('BuddyPress'),
        defined('BP_VERSION'),
        defined('BP_PLUGIN_DIR')
    );
    
    // If any check passes, BuddyPress is active
    foreach ($checks as $check) {
        if ($check) {
            return true;
        }
    }
    
    // If no checks passed, show warning (but don't prevent plugin from running)
    add_action('admin_notices', 'buddyreels_buddypress_notice');
    return false;
}

/**
 * Admin notice if BuddyPress is not detected
 */
function buddyreels_buddypress_notice() {
    ?>
    <div class="notice notice-warning">
        <p><?php esc_html_e('BuddyReels works best with BuddyPress installed and activated. Some features may not work without BuddyPress.', 'buddyreels'); ?></p>
        <p><small>Detection status: 
            buddypress() function: <?php echo function_exists('buddypress') ? 'Found' : 'Not found'; ?> | 
            BuddyPress class: <?php echo class_exists('BuddyPress') ? 'Found' : 'Not found'; ?> | 
            BP_VERSION: <?php echo defined('BP_VERSION') ? BP_VERSION : 'Not defined'; ?> |
            BP_PLUGIN_DIR: <?php echo defined('BP_PLUGIN_DIR') ? 'Found' : 'Not found'; ?>
        </small></p>
    </div>
    <?php
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require BUDDYREELS_PLUGIN_DIR . 'includes/class-buddyreels.php';

/**
 * Begins execution of the plugin.
 */
function run_buddyreels() {
    $plugin = new BuddyReels();
    $plugin->run();
}

/**
 * Initialize the plugin after all plugins are loaded
 */
function buddyreels_init() {
    // Always try to run, but show notice if BuddyPress not detected
    buddyreels_check_buddypress();
    run_buddyreels();
}

// Hook into plugins_loaded to ensure BuddyPress is fully loaded before checking
add_action('plugins_loaded', 'buddyreels_init');
