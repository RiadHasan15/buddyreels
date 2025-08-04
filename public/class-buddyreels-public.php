<?php

/**
 * The public-facing functionality of the plugin.
 */
class BuddyReels_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            BUDDYREELS_PLUGIN_URL . 'public/css/buddyreels-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            BUDDYREELS_PLUGIN_URL . 'public/js/buddyreels-public.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'buddyreels_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'upload_nonce' => wp_create_nonce('buddyreels_upload_nonce'),
            'like_nonce' => wp_create_nonce('buddyreels_like_nonce'),
            'publish_nonce' => wp_create_nonce('buddyreels_publish_nonce'),
            'delete_nonce' => wp_create_nonce('buddyreels_delete_nonce'),
            'strings' => array(
                'uploading' => __('Uploading...', 'buddyreels'),
                'upload_error' => __('Upload failed. Please try again.', 'buddyreels'),
                'like_error' => __('Failed to like reel.', 'buddyreels'),
                'login_required' => __('Please log in to perform this action.', 'buddyreels'),
                'confirm_delete' => __('Are you sure you want to delete this reel?', 'buddyreels'),
            )
        ));
    }
}
