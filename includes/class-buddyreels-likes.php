<?php

/**
 * Handle likes functionality
 */
class BuddyReels_Likes {

    public function __construct() {
        add_action('wp_ajax_buddyreels_toggle_like', array($this, 'toggle_like'));
        add_action('wp_ajax_nopriv_buddyreels_toggle_like', array($this, 'ajax_login_required'));
    }

    /**
     * Handle like toggle via AJAX
     */
    public function toggle_like() {
        if (!wp_verify_nonce($_POST['nonce'], 'buddyreels_like_nonce') || !is_user_logged_in()) {
            wp_send_json_error(__('Security check failed', 'buddyreels'));
        }

        $reel_id = intval($_POST['reel_id']);
        $user_id = get_current_user_id();

        if (!get_post($reel_id) || get_post_type($reel_id) !== 'reel') {
            wp_send_json_error(__('Invalid reel', 'buddyreels'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'buddyreels_likes';

        // Check if user already liked this reel
        $existing_like = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND reel_id = %d",
            $user_id,
            $reel_id
        ));

        if ($existing_like) {
            // Unlike
            $wpdb->delete(
                $table_name,
                array('user_id' => $user_id, 'reel_id' => $reel_id),
                array('%d', '%d')
            );
            $liked = false;
        } else {
            // Like
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'reel_id' => $reel_id,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s')
            );
            $liked = true;
        }

        // Get updated like count
        $like_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE reel_id = %d",
            $reel_id
        ));

        wp_send_json_success(array(
            'liked' => $liked,
            'like_count' => $like_count
        ));
    }

    /**
     * Handle non-logged-in users
     */
    public function ajax_login_required() {
        wp_send_json_error(__('You must be logged in to like reels', 'buddyreels'));
    }

    /**
     * Get likes count for a reel
     */
    public function get_likes_count($reel_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'buddyreels_likes';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE reel_id = %d",
            $reel_id
        ));
    }

    /**
     * Check if user has liked a reel
     */
    public function user_has_liked($reel_id, $user_id) {
        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'buddyreels_likes';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE reel_id = %d AND user_id = %d",
            $reel_id,
            $user_id
        )) > 0;
    }

    /**
     * Get users who liked a reel
     */
    public function get_reel_likes($reel_id, $limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'buddyreels_likes';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name, u.user_login 
             FROM $table_name l 
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
             WHERE l.reel_id = %d 
             ORDER BY l.created_at DESC 
             LIMIT %d",
            $reel_id,
            $limit
        ));
    }
}
