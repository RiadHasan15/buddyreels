<?php
/**
 * Template for BuddyPress profile reels tab
 */

$displayed_user_id = bp_displayed_user_id();
$is_own_profile = bp_is_my_profile();

// Get user's reels
$args = array(
    'post_type' => 'reel',
    'author' => $displayed_user_id,
    'posts_per_page' => -1,
    'post_status' => $is_own_profile ? array('publish', 'draft') : 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
);

$user_reels = get_posts($args);
?>

<div class="buddyreels-profile-reels">
    <?php if ($is_own_profile): ?>
        <div class="buddyreels-upload-section">
            <h3><?php esc_html_e('Upload New Reel', 'buddyreels'); ?></h3>
            
            <form id="buddyreels-upload-form" class="buddyreels-upload-form" enctype="multipart/form-data">
                <div class="buddyreels-form-group">
                    <label for="reel_video"><?php esc_html_e('Video File (MP4)', 'buddyreels'); ?></label>
                    <input type="file" id="reel_video" name="reel_video" accept="video/mp4" required />
                </div>
                
                <div class="buddyreels-video-preview" id="buddyreels-video-preview">
                    <video controls muted></video>
                </div>
                
                <div class="buddyreels-form-group">
                    <label for="reel_caption"><?php esc_html_e('Caption', 'buddyreels'); ?></label>
                    <textarea id="reel_caption" name="reel_caption" placeholder="<?php esc_attr_e('What\'s this reel about?', 'buddyreels'); ?>"></textarea>
                </div>
                
                <div class="buddyreels-upload-progress" id="buddyreels-upload-progress">
                    <div class="progress-fill"></div>
                </div>
                
                <div class="buddyreels-form-actions">
                    <button type="submit" class="buddyreels-btn buddyreels-btn-primary">
                        <?php esc_html_e('Upload Reel', 'buddyreels'); ?>
                    </button>
                    
                    <label>
                        <input type="checkbox" name="save_as_draft" value="1" />
                        <?php esc_html_e('Save as draft', 'buddyreels'); ?>
                    </label>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <div class="buddyreels-reels-section">
        <h3>
            <?php 
            if ($is_own_profile) {
                esc_html_e('My Reels', 'buddyreels');
            } else {
                printf(esc_html__('%s\'s Reels', 'buddyreels'), bp_get_displayed_user_fullname());
            }
            ?>
        </h3>
        
        <?php if ($user_reels): ?>
            <div class="buddyreels-profile-grid">
                <?php foreach ($user_reels as $reel): ?>
                    <?php
                    $video_url = get_post_meta($reel->ID, '_buddyreels_video_url', true);
                    $thumbnail_url = get_post_meta($reel->ID, '_buddyreels_thumbnail_url', true);
                    $caption = get_post_meta($reel->ID, '_buddyreels_caption', true);
                    $is_draft = $reel->post_status === 'draft';
                    ?>
                    
                    <div class="buddyreels-reel-item" data-reel-id="<?php echo esc_attr($reel->ID); ?>">
                        <?php if ($is_draft): ?>
                            <div class="buddyreels-draft-badge"><?php esc_html_e('Draft', 'buddyreels'); ?></div>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(get_permalink($reel->ID)); ?>">
                            <?php if ($thumbnail_url): ?>
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($caption); ?>" />
                            <?php elseif ($video_url): ?>
                                <video muted poster="<?php echo esc_url($thumbnail_url); ?>">
                                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                </video>
                            <?php endif; ?>
                        </a>
                        
                        <div class="buddyreels-reel-overlay">
                            <?php if ($caption): ?>
                                <div class="buddyreels-reel-caption">
                                    <?php echo esc_html(wp_trim_words($caption, 10)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($is_own_profile): ?>
                                <div class="buddyreels-reel-actions">
                                    <?php if ($is_draft): ?>
                                        <button class="buddyreels-publish-btn" data-reel-id="<?php echo esc_attr($reel->ID); ?>">
                                            <?php esc_html_e('Publish', 'buddyreels'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="buddyreels-delete-btn" data-reel-id="<?php echo esc_attr($reel->ID); ?>">
                                        <?php esc_html_e('Delete', 'buddyreels'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="buddyreels-no-reels">
                <?php 
                if ($is_own_profile) {
                    esc_html_e('You haven\'t uploaded any reels yet.', 'buddyreels');
                } else {
                    esc_html_e('This user hasn\'t uploaded any reels yet.', 'buddyreels');
                }
                ?>
            </p>
        <?php endif; ?>
    </div>
</div>
