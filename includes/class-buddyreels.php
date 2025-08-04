<?php

/**
 * The file that defines the core plugin class
 */
class BuddyReels {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        if (defined('BUDDYREELS_VERSION')) {
            $this->version = BUDDYREELS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'buddyreels';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        require_once BUDDYREELS_PLUGIN_DIR . 'includes/class-buddyreels-post-types.php';
        require_once BUDDYREELS_PLUGIN_DIR . 'includes/class-buddyreels-buddypress.php';
        require_once BUDDYREELS_PLUGIN_DIR . 'includes/class-buddyreels-upload.php';
        require_once BUDDYREELS_PLUGIN_DIR . 'includes/class-buddyreels-feed.php';
        require_once BUDDYREELS_PLUGIN_DIR . 'includes/class-buddyreels-likes.php';
        require_once BUDDYREELS_PLUGIN_DIR . 'admin/class-buddyreels-admin.php';
        require_once BUDDYREELS_PLUGIN_DIR . 'public/class-buddyreels-public.php';
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function define_admin_hooks() {
        $plugin_admin = new BuddyReels_Admin($this->get_plugin_name(), $this->get_version());
        
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function define_public_hooks() {
        $plugin_public = new BuddyReels_Public($this->get_plugin_name(), $this->get_version());

        add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));
        
        // Initialize components
        new BuddyReels_Post_Types();
        new BuddyReels_BuddyPress();
        new BuddyReels_Upload();
        new BuddyReels_Feed();
        new BuddyReels_Likes();
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        // Plugin is ready to run
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Fired during plugin activation.
     */
    public static function activate() {
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $buddyreels_dir = $upload_dir['basedir'] . '/buddyreels';
        
        if (!file_exists($buddyreels_dir)) {
            wp_mkdir_p($buddyreels_dir);
        }
        
        // Create database tables if needed
        self::create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Fired during plugin deactivation.
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create custom database tables
     */
    private static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'buddyreels_likes';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            reel_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_reel (user_id, reel_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
