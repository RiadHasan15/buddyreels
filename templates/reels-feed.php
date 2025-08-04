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

<div class="buddyreels-feed" 
     data-user-id="<?php echo esc_attr($atts['user_id']); ?>"
     data-has-more="<?php echo esc_attr($atts['has_more']); ?>"
     data-total-pages="<?php echo esc_attr($atts['total_pages']); ?>"
     data-current-page="1">
    <?php while ($reels_query->have_posts()) : $reels_query->the_post(); ?>
        <?php $feed_handler->render_single_reel_in_feed(get_the_ID()); ?>
    <?php endwhile; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize vertical scroll feed
    initVerticalScrollFeed();
    
    function initVerticalScrollFeed() {
        var $feed = $('.buddyreels-feed');
        var $videos = $feed.find('.buddyreels-video');
        var currentlyPlaying = null;
        
        // Intersection Observer for video autoplay
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    var video = entry.target;
                    var $video = $(video);
                    
                    if (entry.isIntersecting) {
                        // Pause currently playing video
                        if (currentlyPlaying && currentlyPlaying !== video) {
                            currentlyPlaying.pause();
                            $(currentlyPlaying).siblings('.buddyreels-video-overlay').show();
                        }
                        
                        // Play current video
                        video.play().then(function() {
                            currentlyPlaying = video;
                            $video.siblings('.buddyreels-video-overlay').hide();
                        }).catch(function(error) {
                            console.log('Video autoplay failed:', error);
                        });
                    } else {
                        // Pause video when not visible
                        if (video === currentlyPlaying) {
                            video.pause();
                            currentlyPlaying = null;
                        }
                        $video.siblings('.buddyreels-video-overlay').show();
                    }
                });
            }, { 
                threshold: 0.7,
                rootMargin: '0px 0px -10% 0px'
            });
            
            $videos.each(function() {
                observer.observe(this);
            });
        }
        
        // Click to play/pause
        $videos.on('click', function() {
            var video = this;
            var $video = $(video);
            
            if (video.paused) {
                if (currentlyPlaying && currentlyPlaying !== video) {
                    currentlyPlaying.pause();
                    $(currentlyPlaying).siblings('.buddyreels-video-overlay').show();
                }
                video.play().then(function() {
                    currentlyPlaying = video;
                    $video.siblings('.buddyreels-video-overlay').hide();
                });
            } else {
                video.pause();
                currentlyPlaying = null;
                $video.siblings('.buddyreels-video-overlay').show();
            }
        });
        
        // Handle scroll events for better performance and lazy loading
        var scrollTimeout;
        var loadingMore = false;
        
        $feed.on('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                // Re-initialize video players if needed
                if (typeof initVideoPlayer === 'function') {
                    initVideoPlayer();
                }
                
                // Check if we need to load more reels
                var scrollTop = $feed.scrollTop();
                var scrollHeight = $feed[0].scrollHeight;
                var clientHeight = $feed[0].clientHeight;
                var hasMore = $feed.data('has-more') === '1';
                var currentPage = parseInt($feed.data('current-page'));
                var totalPages = parseInt($feed.data('total-pages'));
                
                if (!loadingMore && hasMore && currentPage < totalPages && 
                    scrollTop + clientHeight >= scrollHeight - 100) {
                    loadMoreReels();
                }
            }, 100);
        });
        
        function loadMoreReels() {
            if (loadingMore) return;
            
            loadingMore = true;
            var currentPage = parseInt($feed.data('current-page')) + 1;
            
            $.ajax({
                url: buddyreels_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'buddyreels_load_more_reels',
                    page: currentPage,
                    count: <?php echo esc_js($atts['count']); ?>,
                    user_id: <?php echo esc_js($atts['user_id']); ?>
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $feed.append(response.data.html);
                        $feed.data('current-page', currentPage);
                        
                        if (currentPage >= parseInt($feed.data('total-pages'))) {
                            $feed.data('has-more', '0');
                        }
                        
                        // Re-initialize video players for new content
                        if (typeof initVideoPlayer === 'function') {
                            initVideoPlayer();
                        }
                    }
                },
                complete: function() {
                    loadingMore = false;
                }
            });
        }
    }
});
</script>
