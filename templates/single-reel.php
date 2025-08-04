<?php
/**
 * Template for displaying single reel
 */

get_header(); ?>

<div class="buddyreels-single-container">
    <?php while (have_posts()) : the_post(); ?>
        
        <?php
        $video_url = get_post_meta(get_the_ID(), '_buddyreels_video_url', true);
        $thumbnail_url = get_post_meta(get_the_ID(), '_buddyreels_thumbnail_url', true);
        $caption = get_post_meta(get_the_ID(), '_buddyreels_caption', true);
        $likes_handler = new BuddyReels_Likes();
        $likes_count = $likes_handler->get_likes_count(get_the_ID());
        $user_liked = is_user_logged_in() ? $likes_handler->user_has_liked(get_the_ID(), get_current_user_id()) : false;
        ?>
        
        <div class="buddyreels-single-video">
            <?php if ($video_url): ?>
                <video controls autoplay loop muted poster="<?php echo esc_url($thumbnail_url); ?>">
                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    <?php esc_html_e('Your browser does not support the video tag.', 'buddyreels'); ?>
                </video>
            <?php endif; ?>
        </div>
        
        <div class="buddyreels-single-info">
            <div class="buddyreels-single-author">
                <?php echo get_avatar(get_the_author_meta('ID'), 48); ?>
                <div class="buddyreels-single-author-info">
                    <h3><?php the_author(); ?></h3>
                    <div class="date"><?php echo esc_html(get_the_date()); ?></div>
                </div>
            </div>
            
            <?php if ($caption): ?>
                <div class="buddyreels-single-caption">
                    <?php echo wp_kses_post(wpautop($caption)); ?>
                </div>
            <?php endif; ?>
            
            <div class="buddyreels-single-actions">
                <button class="buddyreels-like-btn <?php echo $user_liked ? 'liked' : ''; ?>" 
                        data-reel-id="<?php echo esc_attr(get_the_ID()); ?>"
                        <?php echo !is_user_logged_in() ? 'disabled' : ''; ?>>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo $user_liked ? 'red' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                        <path d="20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span class="likes-count"><?php echo esc_html($likes_count); ?></span> <?php esc_html_e('Likes', 'buddyreels'); ?>
                </button>
                
                <button class="buddyreels-share-btn" data-url="<?php echo esc_url(get_permalink()); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="18" cy="5" r="3"></circle>
                        <circle cx="6" cy="12" r="3"></circle>
                        <circle cx="18" cy="19" r="3"></circle>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                    </svg>
                    <?php esc_html_e('Share', 'buddyreels'); ?>
                </button>
            </div>
            
            <?php if (comments_open() || get_comments_number()) : ?>
                <div class="buddyreels-comments">
                    <?php comments_template(); ?>
                </div>
            <?php endif; ?>
        </div>
        
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
