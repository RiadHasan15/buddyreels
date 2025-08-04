<?php

/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete custom post type posts
$reels = get_posts(array(
    'post_type' => 'reel',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($reels as $reel) {
    // Delete associated files
    $video_path = get_post_meta($reel->ID, '_buddyreels_video_path', true);
    $thumbnail_path = get_post_meta($reel->ID, '_buddyreels_thumbnail_path', true);
    
    if ($video_path && file_exists($video_path)) {
        unlink($video_path);
    }
    
    if ($thumbnail_path && file_exists($thumbnail_path)) {
        unlink($thumbnail_path);
    }
    
    // Delete post and all meta
    wp_delete_post($reel->ID, true);
}

// Delete custom database tables
global $wpdb;

$table_names = array(
    $wpdb->prefix . 'buddyreels_likes'
);

foreach ($table_names as $table_name) {
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Delete options
$options = array(
    'buddyreels_max_file_size',
    'buddyreels_auto_publish',
    'buddyreels_enable_comments'
);

foreach ($options as $option) {
    delete_option($option);
}

// Delete upload directory
$upload_dir = wp_upload_dir();
$buddyreels_dir = $upload_dir['basedir'] . '/buddyreels';

if (is_dir($buddyreels_dir)) {
    $files = array_diff(scandir($buddyreels_dir), array('.', '..'));
    foreach ($files as $file) {
        $file_path = $buddyreels_dir . '/' . $file;
        if (is_dir($file_path)) {
            // Remove subdirectories recursively
            $sub_files = array_diff(scandir($file_path), array('.', '..'));
            foreach ($sub_files as $sub_file) {
                unlink($file_path . '/' . $sub_file);
            }
            rmdir($file_path);
        } else {
            unlink($file_path);
        }
    }
    rmdir($buddyreels_dir);
}

// Clean up any remaining meta data
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_buddyreels_%'");

// Clean up any activity entries
if (function_exists('bp_activity_delete')) {
    $activities = bp_activity_get(array(
        'filter' => array(
            'action' => 'buddyreels_reel'
        ),
        'show_hidden' => true
    ));
    
    if (!empty($activities['activities'])) {
        foreach ($activities['activities'] as $activity) {
            bp_activity_delete_by_activity_id($activity->id);
        }
    }
}
