<?php
/**
 * Template for displaying reels feed
 */

if (!$reels_query->have_posts()) {
    echo '<p class="buddyreels-no-reels">' . esc_html__('No reels found.', 'buddyreels') . '</p>';
    return;
}

$feed_handler = new BuddyReels_Feed();
?>

<div class="buddyreels-feed" data-user-id="<?php echo esc_attr($atts['user_id']); ?>">
    <?php while ($reels_query->have_posts()) : $reels_query->the_post(); ?>
        <?php $feed_handler->render_single_reel_in_feed(get_the_ID()); ?>
    <?php endwhile; ?>
    
    <?php if ($reels_query->max_num_pages > 1): ?>
        <div class="buddyreels-load-more">
            <button class="buddyreels-btn buddyreels-btn-secondary" id="buddyreels-load-more-btn">
                <?php esc_html_e('Load More Reels', 'buddyreels'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    var page = 2;
    var loading = false;
    var hasMore = <?php echo json_encode($reels_query->max_num_pages > 1); ?>;
    
    $('#buddyreels-load-more-btn').on('click', function() {
        if (loading || !hasMore) return;
        
        loading = true;
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('<?php esc_html_e('Loading...', 'buddyreels'); ?>').prop('disabled', true);
        
        $.ajax({
            url: buddyreels_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'buddyreels_load_more_reels',
                page: page,
                count: <?php echo esc_js($atts['count']); ?>,
                user_id: <?php echo esc_js($atts['user_id']); ?>
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $('.buddyreels-feed').append(response.data.html);
                    page++;
                    hasMore = response.data.has_more;
                    
                    if (!hasMore) {
                        $btn.hide();
                    }
                    
                    // Re-initialize video players for new content
                    if (typeof initVideoPlayer === 'function') {
                        initVideoPlayer();
                    }
                } else {
                    hasMore = false;
                    $btn.hide();
                }
            },
            complete: function() {
                loading = false;
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
