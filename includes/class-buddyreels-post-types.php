<?php

/**
 * Register custom post types and taxonomies
 */
class BuddyReels_Post_Types {

    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('single_template', array($this, 'load_reel_template'));
        add_action('wp_head', array($this, 'add_social_meta_tags'));
    }

    /**
     * Register the reel custom post type
     */
    public function register_post_types() {
        $labels = array(
            'name'                  => _x('Reels', 'Post type general name', 'buddyreels'),
            'singular_name'         => _x('Reel', 'Post type singular name', 'buddyreels'),
            'menu_name'             => _x('Reels', 'Admin Menu text', 'buddyreels'),
            'name_admin_bar'        => _x('Reel', 'Add New on Toolbar', 'buddyreels'),
            'add_new'               => __('Add New', 'buddyreels'),
            'add_new_item'          => __('Add New Reel', 'buddyreels'),
            'new_item'              => __('New Reel', 'buddyreels'),
            'edit_item'             => __('Edit Reel', 'buddyreels'),
            'view_item'             => __('View Reel', 'buddyreels'),
            'all_items'             => __('All Reels', 'buddyreels'),
            'search_items'          => __('Search Reels', 'buddyreels'),
            'not_found'             => __('No reels found.', 'buddyreels'),
            'not_found_in_trash'    => __('No reels found in Trash.', 'buddyreels'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'reels'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'comments'),
            'menu_icon'          => 'dashicons-video-alt3',
            'show_in_rest'       => true,
        );

        register_post_type('reel', $args);
    }

    /**
     * Add custom rewrite rules for reel permalinks
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^reels/([^/]+)/?$',
            'index.php?post_type=reel&name=$matches[1]',
            'top'
        );
    }

    /**
     * Load custom template for single reel
     */
    public function load_reel_template($template) {
        global $post;

        if ($post->post_type == 'reel' && is_single()) {
            $plugin_template = BUDDYREELS_PLUGIN_DIR . 'templates/single-reel.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Add social media meta tags for reel pages
     */
    public function add_social_meta_tags() {
        if (is_singular('reel')) {
            global $post;
            
            $video_url = get_post_meta($post->ID, '_buddyreels_video_url', true);
            $thumbnail_url = get_post_meta($post->ID, '_buddyreels_thumbnail_url', true);
            $caption = get_post_meta($post->ID, '_buddyreels_caption', true);
            $author = get_userdata($post->post_author);
            
            // OpenGraph tags
            echo '<meta property="og:title" content="' . esc_attr($post->post_title) . '" />' . "\n";
            echo '<meta property="og:description" content="' . esc_attr($caption) . '" />' . "\n";
            echo '<meta property="og:type" content="video.other" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '" />' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
            
            if ($thumbnail_url) {
                echo '<meta property="og:image" content="' . esc_url($thumbnail_url) . '" />' . "\n";
            }
            
            if ($video_url) {
                echo '<meta property="og:video" content="' . esc_url($video_url) . '" />' . "\n";
                echo '<meta property="og:video:type" content="video/mp4" />' . "\n";
            }
            
            // Twitter Card tags
            echo '<meta name="twitter:card" content="player" />' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($post->post_title) . '" />' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($caption) . '" />' . "\n";
            
            if ($thumbnail_url) {
                echo '<meta name="twitter:image" content="' . esc_url($thumbnail_url) . '" />' . "\n";
            }
            
            if ($author) {
                echo '<meta name="twitter:creator" content="@' . esc_attr($author->user_login) . '" />' . "\n";
            }
        }
    }
}
