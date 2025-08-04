<?php

/**
 * The admin-specific functionality of the plugin.
 */
class BuddyReels_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            BUDDYREELS_PLUGIN_URL . 'admin/css/buddyreels-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            BUDDYREELS_PLUGIN_URL . 'admin/js/buddyreels-admin.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('BuddyReels', 'buddyreels'),
            __('BuddyReels', 'buddyreels'),
            'manage_options',
            'buddyreels',
            array($this, 'admin_page'),
            'dashicons-video-alt3',
            25
        );

        add_submenu_page(
            'buddyreels',
            __('All Reels', 'buddyreels'),
            __('All Reels', 'buddyreels'),
            'manage_options',
            'edit.php?post_type=reel'
        );

        add_submenu_page(
            'buddyreels',
            __('Settings', 'buddyreels'),
            __('Settings', 'buddyreels'),
            'manage_options',
            'buddyreels-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('BuddyReels Dashboard', 'buddyreels'); ?></h1>
            
            <div class="buddyreels-dashboard">
                <div class="buddyreels-stats">
                    <?php
                    $total_reels = wp_count_posts('reel');
                    $published_reels = $total_reels->publish;
                    $draft_reels = $total_reels->draft;
                    
                    global $wpdb;
                    $likes_table = $wpdb->prefix . 'buddyreels_likes';
                    $total_likes = $wpdb->get_var("SELECT COUNT(*) FROM $likes_table");
                    ?>
                    
                    <div class="buddyreels-stat-box">
                        <h3><?php echo esc_html($published_reels); ?></h3>
                        <p><?php esc_html_e('Published Reels', 'buddyreels'); ?></p>
                    </div>
                    
                    <div class="buddyreels-stat-box">
                        <h3><?php echo esc_html($draft_reels); ?></h3>
                        <p><?php esc_html_e('Draft Reels', 'buddyreels'); ?></p>
                    </div>
                    
                    <div class="buddyreels-stat-box">
                        <h3><?php echo esc_html($total_likes); ?></h3>
                        <p><?php esc_html_e('Total Likes', 'buddyreels'); ?></p>
                    </div>
                </div>
                
                <div class="buddyreels-recent-reels">
                    <h2><?php esc_html_e('Recent Reels', 'buddyreels'); ?></h2>
                    
                    <?php
                    $recent_reels = get_posts(array(
                        'post_type' => 'reel',
                        'posts_per_page' => 10,
                        'post_status' => array('publish', 'draft')
                    ));
                    
                    if ($recent_reels) {
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>Title</th><th>Author</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        
                        foreach ($recent_reels as $reel) {
                            $author = get_userdata($reel->post_author);
                            echo '<tr>';
                            echo '<td><strong>' . esc_html($reel->post_title) . '</strong></td>';
                            echo '<td>' . esc_html($author->display_name) . '</td>';
                            echo '<td>' . esc_html(ucfirst($reel->post_status)) . '</td>';
                            echo '<td>' . esc_html(get_the_date('Y/m/d', $reel)) . '</td>';
                            echo '<td>';
                            echo '<a href="' . esc_url(get_edit_post_link($reel->ID)) . '">Edit</a> | ';
                            echo '<a href="' . esc_url(get_permalink($reel->ID)) . '">View</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                    } else {
                        echo '<p>' . esc_html__('No reels found.', 'buddyreels') . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page content
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            // Handle settings save
            $max_file_size = intval($_POST['max_file_size']);
            $auto_publish = isset($_POST['auto_publish']) ? 1 : 0;
            $enable_comments = isset($_POST['enable_comments']) ? 1 : 0;
            
            update_option('buddyreels_max_file_size', $max_file_size);
            update_option('buddyreels_auto_publish', $auto_publish);
            update_option('buddyreels_enable_comments', $enable_comments);
            
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved!', 'buddyreels') . '</p></div>';
        }
        
        $max_file_size = get_option('buddyreels_max_file_size', 50);
        $auto_publish = get_option('buddyreels_auto_publish', 0);
        $enable_comments = get_option('buddyreels_enable_comments', 1);
        ?>
        
        <div class="wrap">
            <h1><?php esc_html_e('BuddyReels Settings', 'buddyreels'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('buddyreels_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Max File Size (MB)', 'buddyreels'); ?></th>
                        <td>
                            <input type="number" name="max_file_size" value="<?php echo esc_attr($max_file_size); ?>" min="1" max="200" />
                            <p class="description"><?php esc_html_e('Maximum file size for reel uploads in megabytes.', 'buddyreels'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Auto Publish', 'buddyreels'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_publish" value="1" <?php checked($auto_publish, 1); ?> />
                                <?php esc_html_e('Automatically publish reels after upload (skip draft status)', 'buddyreels'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Comments', 'buddyreels'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_comments" value="1" <?php checked($enable_comments, 1); ?> />
                                <?php esc_html_e('Allow comments on reels', 'buddyreels'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'buddyreels_video_meta',
            __('Reel Video', 'buddyreels'),
            array($this, 'video_meta_box'),
            'reel',
            'normal',
            'high'
        );
    }

    /**
     * Video meta box content
     */
    public function video_meta_box($post) {
        wp_nonce_field('buddyreels_meta_nonce', 'buddyreels_meta_nonce');
        
        $video_url = get_post_meta($post->ID, '_buddyreels_video_url', true);
        $thumbnail_url = get_post_meta($post->ID, '_buddyreels_thumbnail_url', true);
        $caption = get_post_meta($post->ID, '_buddyreels_caption', true);
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="buddyreels_video_url"><?php esc_html_e('Video URL', 'buddyreels'); ?></label></th>
                <td>
                    <input type="url" id="buddyreels_video_url" name="buddyreels_video_url" value="<?php echo esc_attr($video_url); ?>" class="regular-text" />
                    <?php if ($video_url): ?>
                        <br><video src="<?php echo esc_url($video_url); ?>" controls style="max-width: 300px; height: auto; margin-top: 10px;"></video>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th><label for="buddyreels_thumbnail_url"><?php esc_html_e('Thumbnail URL', 'buddyreels'); ?></label></th>
                <td>
                    <input type="url" id="buddyreels_thumbnail_url" name="buddyreels_thumbnail_url" value="<?php echo esc_attr($thumbnail_url); ?>" class="regular-text" />
                    <?php if ($thumbnail_url): ?>
                        <br><img src="<?php echo esc_url($thumbnail_url); ?>" style="max-width: 200px; height: auto; margin-top: 10px;" />
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th><label for="buddyreels_caption"><?php esc_html_e('Caption', 'buddyreels'); ?></label></th>
                <td>
                    <textarea id="buddyreels_caption" name="buddyreels_caption" rows="4" class="large-text"><?php echo esc_textarea($caption); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['buddyreels_meta_nonce']) || !wp_verify_nonce($_POST['buddyreels_meta_nonce'], 'buddyreels_meta_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== 'reel') {
            return;
        }

        $video_url = sanitize_url($_POST['buddyreels_video_url']);
        $thumbnail_url = sanitize_url($_POST['buddyreels_thumbnail_url']);
        $caption = sanitize_textarea_field($_POST['buddyreels_caption']);

        update_post_meta($post_id, '_buddyreels_video_url', $video_url);
        update_post_meta($post_id, '_buddyreels_thumbnail_url', $thumbnail_url);
        update_post_meta($post_id, '_buddyreels_caption', $caption);
    }
}
