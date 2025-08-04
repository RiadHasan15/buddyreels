<?php

/**
 * Handle reel feed functionality
 */
class BuddyReels_Feed {

    public function __construct() {
        add_shortcode('buddyreels_feed', array($this, 'render_feed_shortcode'));
        add_action('wp_ajax_buddyreels_load_more_reels', array($this, 'load_more_reels'));
        add_action('wp_ajax_nopriv_buddyreels_load_more_reels', array($this, 'load_more_reels'));
    }

    /**
     * Render the reels feed shortcode
     */
    public function render_feed_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 10,
            'user_id' => 0,
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts);

        ob_start();
        $this->render_feed($atts);
        return ob_get_clean();
    }

    /**
     * Render the feed
     */
    private function render_feed($atts) {
        $args = array(
            'post_type' => 'reel',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['count']),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
            'meta_query' => array(
                array(
                    'key' => '_buddyreels_video_url',
                    'compare' => 'EXISTS'
                )
            )
        );

        if (!empty($atts['user_id'])) {
            $args['author'] = intval($atts['user_id']);
        }

        $reels_query = new WP_Query($args);

        // Add data attributes for lazy loading
        $atts['has_more'] = $reels_query->max_num_pages > 1;
        $atts['total_pages'] = $reels_query->max_num_pages;

        include BUDDYREELS_PLUGIN_DIR . 'templates/reels-feed.php';

        wp_reset_postdata();
    }

    /**
     * Load more reels via AJAX
     */
    public function load_more_reels() {
        $page = intval($_POST['page']);
        $count = intval($_POST['count']);
        $user_id = intval($_POST['user_id']);

        $args = array(
            'post_type' => 'reel',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_buddyreels_video_url',
                    'compare' => 'EXISTS'
                )
            )
        );

        if ($user_id > 0) {
            $args['author'] = $user_id;
        }

        $reels_query = new WP_Query($args);

        if ($reels_query->have_posts()) {
            ob_start();
            
            while ($reels_query->have_posts()) {
                $reels_query->the_post();
                $this->render_single_reel_in_feed(get_the_ID());
            }
            
            $html = ob_get_clean();
            wp_send_json_success(array('html' => $html, 'has_more' => $page < $reels_query->max_num_pages));
        } else {
            wp_send_json_success(array('html' => '', 'has_more' => false));
        }

        wp_reset_postdata();
    }

    /**
     * Render a single reel in the feed
     */
    public function render_single_reel_in_feed($reel_id) {
        $reel = get_post($reel_id);
        $video_url = get_post_meta($reel_id, '_buddyreels_video_url', true);
        $thumbnail_url = get_post_meta($reel_id, '_buddyreels_thumbnail_url', true);
        $caption = get_post_meta($reel_id, '_buddyreels_caption', true);
        $author = get_userdata($reel->post_author);
        $likes_count = $this->get_likes_count($reel_id);
        $comments_count = wp_count_comments($reel_id)->approved;
        $user_liked = is_user_logged_in() ? $this->user_has_liked($reel_id, get_current_user_id()) : false;

        ?>
        <div class="buddyreels-feed-item" data-reel-id="<?php echo esc_attr($reel_id); ?>">
            <div class="buddyreels-video-container">
                <video 
                    class="buddyreels-video" 
                    loop 
                    muted 
                    playsinline
                    poster="<?php echo esc_url($thumbnail_url); ?>"
                    data-src="<?php echo esc_url($video_url); ?>"
                >
                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    <?php esc_html_e('Your browser does not support the video tag.', 'buddyreels'); ?>
                </video>
                
                <div class="buddyreels-video-overlay">
                    <div class="buddyreels-play-button">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5,3 19,12 5,21"></polygon>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="buddyreels-feed-info">
                <div class="buddyreels-author">
                    <?php echo get_avatar($author->ID, 32); ?>
                    <span class="buddyreels-author-name"><?php echo esc_html($author->display_name); ?></span>
                </div>
                
                <?php if ($caption): ?>
                <div class="buddyreels-caption">
                    <?php echo wp_kses_post(wpautop($caption)); ?>
                </div>
                <?php endif; ?>
                
                <div class="buddyreels-actions">
                    <button class="buddyreels-like-btn <?php echo $user_liked ? 'liked' : ''; ?>" 
                            data-reel-id="<?php echo esc_attr($reel_id); ?>"
                            <?php echo !is_user_logged_in() ? 'disabled' : ''; ?>>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo $user_liked ? 'red' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                            <path d="20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        <span class="likes-count"><?php echo esc_html($likes_count); ?></span>
                    </button>
                    
                    <a href="<?php echo esc_url(get_permalink($reel_id)); ?>" class="buddyreels-comment-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span><?php echo esc_html($comments_count); ?></span>
                    </a>
                    
                    <button class="buddyreels-share-btn" data-url="<?php echo esc_url(get_permalink($reel_id)); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get likes count for a reel
     */
    private function get_likes_count($reel_id) {
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
    private function user_has_liked($reel_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'buddyreels_likes';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE reel_id = %d AND user_id = %d",
            $reel_id,
            $user_id
        )) > 0;
    }
}
