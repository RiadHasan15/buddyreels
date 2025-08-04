<?php

/**
 * BuddyPress integration class
 */
class BuddyReels_BuddyPress {

    public function __construct() {
        add_action('bp_init', array($this, 'init'));
    }

    /**
     * Initialize BuddyPress integration
     */
    public function init() {
        // Add Reels tab to profile
        add_action('bp_setup_nav', array($this, 'add_reels_tab'));
        
        // Modify activity form to include reel upload
        add_action('bp_after_activity_post_form', array($this, 'add_reel_upload_to_activity_form'));
        
        // Handle reel upload from activity form
        add_action('wp_ajax_buddyreels_upload_from_activity', array($this, 'handle_activity_reel_upload'));
        add_action('wp_ajax_nopriv_buddyreels_upload_from_activity', array($this, 'handle_activity_reel_upload'));
        
        // Add reel to activity stream when published
        add_action('transition_post_status', array($this, 'add_reel_to_activity'), 10, 3);
    }

    /**
     * Add Reels tab to BuddyPress profile
     */
    public function add_reels_tab() {
        global $bp;

        bp_core_new_nav_item(array(
            'name' => __('Reels', 'buddyreels'),
            'slug' => 'reels',
            'screen_function' => array($this, 'reels_tab_screen'),
            'position' => 80,
            'default_subnav_slug' => 'my-reels'
        ));

        bp_core_new_subnav_item(array(
            'name' => __('My Reels', 'buddyreels'),
            'slug' => 'my-reels',
            'parent_url' => trailingslashit(bp_displayed_user_domain() . 'reels'),
            'parent_slug' => 'reels',
            'screen_function' => array($this, 'my_reels_screen'),
            'position' => 10
        ));
    }

    /**
     * Screen function for reels tab
     */
    public function reels_tab_screen() {
        add_action('bp_template_content', array($this, 'reels_tab_content'));
        bp_core_load_template('members/single/plugins');
    }

    /**
     * Screen function for my reels subtab
     */
    public function my_reels_screen() {
        add_action('bp_template_content', array($this, 'my_reels_content'));
        bp_core_load_template('members/single/plugins');
    }

    /**
     * Content for reels tab
     */
    public function reels_tab_content() {
        include BUDDYREELS_PLUGIN_DIR . 'templates/profile-reels-tab.php';
    }

    /**
     * Content for my reels subtab
     */
    public function my_reels_content() {
        include BUDDYREELS_PLUGIN_DIR . 'templates/profile-reels-tab.php';
    }

    /**
     * Add reel upload option to activity form
     */
    public function add_reel_upload_to_activity_form() {
        if (!is_user_logged_in()) {
            return;
        }

        include BUDDYREELS_PLUGIN_DIR . 'templates/activity-reel-upload.php';
    }

    /**
     * Handle reel upload from activity form
     */
    public function handle_activity_reel_upload() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['buddyreels_nonce'], 'buddyreels_upload_nonce')) {
            wp_die(__('Security check failed', 'buddyreels'));
        }

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to upload reels', 'buddyreels'));
        }

        $upload_handler = new BuddyReels_Upload();
        $result = $upload_handler->handle_upload();

        wp_send_json($result);
    }

    /**
     * Add reel to activity stream when published
     */
    public function add_reel_to_activity($new_status, $old_status, $post) {
        if ($post->post_type !== 'reel') {
            return;
        }

        if ($new_status === 'publish' && $old_status !== 'publish') {
            $video_url = get_post_meta($post->ID, '_buddyreels_video_url', true);
            $thumbnail_url = get_post_meta($post->ID, '_buddyreels_thumbnail_url', true);
            $caption = get_post_meta($post->ID, '_buddyreels_caption', true);

            $activity_content = '';
            if ($thumbnail_url) {
                $activity_content .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($caption) . '" style="max-width: 300px; height: auto;" /><br>';
            }
            $activity_content .= '<p>' . esc_html($caption) . '</p>';
            $activity_content .= '<p><a href="' . esc_url(get_permalink($post->ID)) . '">' . __('Watch Reel', 'buddyreels') . '</a></p>';

            bp_activity_add(array(
                'action' => sprintf(
                    __('%s shared a new reel', 'buddyreels'),
                    bp_core_get_userlink($post->post_author)
                ),
                'content' => $activity_content,
                'component' => 'activity',
                'type' => 'buddyreels_reel',
                'user_id' => $post->post_author,
                'item_id' => $post->ID
            ));
        }
    }
}
