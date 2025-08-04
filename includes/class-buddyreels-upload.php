<?php

/**
 * Handle reel uploads and processing
 */
class BuddyReels_Upload {

    public function __construct() {
        add_action('wp_ajax_buddyreels_upload', array($this, 'ajax_upload_handler'));
        add_action('wp_ajax_buddyreels_publish_draft', array($this, 'publish_draft'));
        add_action('wp_ajax_buddyreels_delete_reel', array($this, 'delete_reel'));
    }

    /**
     * Handle AJAX upload
     */
    public function ajax_upload_handler() {
        // Add debugging
        error_log('BuddyReels upload handler called');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data: ' . print_r($_FILES, true));
        
        $result = $this->handle_upload();
        error_log('Upload result: ' . print_r($result, true));
        
        wp_send_json($result);
    }

    /**
     * Handle file upload and processing
     */
    public function handle_upload() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['buddyreels_nonce'], 'buddyreels_upload_nonce')) {
            return array('success' => false, 'message' => __('Security check failed', 'buddyreels'));
        }

        if (!is_user_logged_in()) {
            return array('success' => false, 'message' => __('You must be logged in to upload reels', 'buddyreels'));
        }

        if (!isset($_FILES['reel_video']) || $_FILES['reel_video']['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'message' => __('No video file uploaded or upload error', 'buddyreels'));
        }

        // Validate file type
        $file_type = wp_check_filetype($_FILES['reel_video']['name']);
        if ($file_type['ext'] !== 'mp4') {
            return array('success' => false, 'message' => __('Only MP4 files are allowed', 'buddyreels'));
        }

        // Validate file size (50MB max)
        if ($_FILES['reel_video']['size'] > 50 * 1024 * 1024) {
            return array('success' => false, 'message' => __('File size must be less than 50MB', 'buddyreels'));
        }

        // Handle upload
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array('mp4' => 'video/mp4')
        );

        $uploaded_file = wp_handle_upload($_FILES['reel_video'], $upload_overrides);

        if (isset($uploaded_file['error'])) {
            return array('success' => false, 'message' => $uploaded_file['error']);
        }

        // Create reel post
        $caption = sanitize_textarea_field($_POST['reel_caption']);
        $is_draft = isset($_POST['save_as_draft']) && $_POST['save_as_draft'] === '1';

        $post_data = array(
            'post_title' => !empty($caption) ? wp_trim_words($caption, 10) : __('Untitled Reel', 'buddyreels'),
            'post_content' => $caption,
            'post_status' => $is_draft ? 'draft' : 'publish',
            'post_type' => 'reel',
            'post_author' => get_current_user_id(),
        );

        $reel_id = wp_insert_post($post_data);

        if (is_wp_error($reel_id)) {
            return array('success' => false, 'message' => __('Failed to create reel post', 'buddyreels'));
        }

        // Save video URL
        update_post_meta($reel_id, '_buddyreels_video_url', $uploaded_file['url']);
        update_post_meta($reel_id, '_buddyreels_video_path', $uploaded_file['file']);
        update_post_meta($reel_id, '_buddyreels_caption', $caption);

        // Save thumbnail data if provided from frontend
        if (isset($_POST['thumbnail_data']) && !empty($_POST['thumbnail_data'])) {
            $this->save_thumbnail_from_data($reel_id, $_POST['thumbnail_data']);
        }

        return array(
            'success' => true, 
            'message' => $is_draft ? __('Reel saved as draft', 'buddyreels') : __('Reel uploaded successfully', 'buddyreels'),
            'reel_id' => $reel_id
        );
    }

    /**
     * Save thumbnail from JavaScript-generated base64 data
     */
    private function save_thumbnail_from_data($reel_id, $thumbnail_data) {
        // Remove data URL prefix if present
        if (strpos($thumbnail_data, 'data:image/') === 0) {
            $thumbnail_data = substr($thumbnail_data, strpos($thumbnail_data, ',') + 1);
        }

        $upload_dir = wp_upload_dir();
        $thumbnails_dir = $upload_dir['basedir'] . '/buddyreels/thumbnails';
        
        if (!file_exists($thumbnails_dir)) {
            wp_mkdir_p($thumbnails_dir);
        }

        $thumbnail_filename = 'thumb_' . $reel_id . '.jpg';
        $thumbnail_path = $thumbnails_dir . '/' . $thumbnail_filename;
        $thumbnail_url = $upload_dir['baseurl'] . '/buddyreels/thumbnails/' . $thumbnail_filename;

        // Decode and save the image
        $image_data = base64_decode($thumbnail_data);
        if ($image_data !== false) {
            $saved = file_put_contents($thumbnail_path, $image_data);
            
            if ($saved !== false) {
                update_post_meta($reel_id, '_buddyreels_thumbnail_url', $thumbnail_url);
                update_post_meta($reel_id, '_buddyreels_thumbnail_path', $thumbnail_path);
                
                // Set as post thumbnail
                $attachment_id = $this->create_attachment_from_file($thumbnail_path, $reel_id);
                if ($attachment_id) {
                    set_post_thumbnail($reel_id, $attachment_id);
                }
                return true;
            }
        }

        // Fallback: use default thumbnail
        $default_thumbnail = BUDDYREELS_PLUGIN_URL . 'public/images/default-reel-thumb.jpg';
        update_post_meta($reel_id, '_buddyreels_thumbnail_url', $default_thumbnail);
        return false;
    }

    /**
     * Create attachment from file
     */
    private function create_attachment_from_file($file_path, $parent_id = 0) {
        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name, null);
        
        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title' => sanitize_file_name($file_name),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment, $file_path, $parent_id);
        
        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
        }

        return $attachment_id;
    }

    /**
     * Publish draft reel
     */
    public function publish_draft() {
        if (!wp_verify_nonce($_POST['nonce'], 'buddyreels_publish_nonce') || !is_user_logged_in()) {
            wp_send_json_error(__('Security check failed', 'buddyreels'));
        }

        $reel_id = intval($_POST['reel_id']);
        $reel = get_post($reel_id);

        if (!$reel || $reel->post_author != get_current_user_id() || $reel->post_type !== 'reel') {
            wp_send_json_error(__('Invalid reel or permission denied', 'buddyreels'));
        }

        wp_update_post(array(
            'ID' => $reel_id,
            'post_status' => 'publish'
        ));

        wp_send_json_success(__('Reel published successfully', 'buddyreels'));
    }

    /**
     * Delete reel
     */
    public function delete_reel() {
        if (!wp_verify_nonce($_POST['nonce'], 'buddyreels_delete_nonce') || !is_user_logged_in()) {
            wp_send_json_error(__('Security check failed', 'buddyreels'));
        }

        $reel_id = intval($_POST['reel_id']);
        $reel = get_post($reel_id);

        if (!$reel || $reel->post_author != get_current_user_id() || $reel->post_type !== 'reel') {
            wp_send_json_error(__('Invalid reel or permission denied', 'buddyreels'));
        }

        // Delete associated files
        $video_path = get_post_meta($reel_id, '_buddyreels_video_path', true);
        $thumbnail_path = get_post_meta($reel_id, '_buddyreels_thumbnail_path', true);

        if ($video_path && file_exists($video_path)) {
            unlink($video_path);
        }

        if ($thumbnail_path && file_exists($thumbnail_path)) {
            unlink($thumbnail_path);
        }

        wp_delete_post($reel_id, true);

        wp_send_json_success(__('Reel deleted successfully', 'buddyreels'));
    }
}
