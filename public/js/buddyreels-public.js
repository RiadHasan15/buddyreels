(function($) {
    'use strict';

    $(document).ready(function() {
        initReelUpload();
        initReelFeed();
        initLikeSystem();
        initVideoPlayer();
        initMobileUpload();
    });

    // Initialize reel upload functionality
    function initReelUpload() {
        // Handle video file selection for preview and thumbnail generation
        $('input[name="reel_video"]').on('change', function(e) {
            var file = e.target.files[0];
            if (file && file.type === 'video/mp4') {
                generateVideoThumbnail(file);
                showVideoPreview(file);
            }
        });

        $('#buddyreels-upload-form').on('submit', function(e) {
            e.preventDefault();
            
            console.log('Form submission started');
            console.log('buddyreels_ajax object:', buddyreels_ajax);
            
            var $form = $(this);
            var formData = new FormData(this);
            formData.append('action', 'buddyreels_upload');
            
            // Check if buddyreels_ajax is available
            if (typeof buddyreels_ajax === 'undefined') {
                alert('JavaScript configuration missing. Please refresh the page.');
                return;
            }
            
            formData.append('buddyreels_nonce', buddyreels_ajax.upload_nonce);
            
            // Add thumbnail data if available
            var thumbnailData = $form.data('thumbnail');
            if (thumbnailData) {
                formData.append('thumbnail_data', thumbnailData);
            }
            
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            var uploadingText = (buddyreels_ajax && buddyreels_ajax.strings && buddyreels_ajax.strings.uploading) || 'Uploading...';
            
            $submitBtn.prop('disabled', true).text(uploadingText);
            
            // Show progress bar
            var $progressBar = $('#buddyreels-upload-progress');
            $progressBar.show();
            
            $.ajax({
                url: buddyreels_ajax.ajax_url || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percentComplete = (e.loaded / e.total) * 100;
                            $progressBar.find('.progress-fill').css('width', percentComplete + '%');
                        }
                    });
                    return xhr;
                },
                success: function(response) {
                    console.log('Upload response:', response);
                    if (response.success) {
                        var message = response.message || (response.data && response.data.message) || 'Upload successful';
                        showNotification(message, 'success');
                        $('#buddyreels-upload-form')[0].reset();
                        $('#buddyreels-video-preview').hide();
                        $('#buddyreels-thumbnail-preview').remove();
                        
                        // Refresh the page if on profile reels tab
                        if (window.location.href.includes('/reels/')) {
                            location.reload();
                        }
                    } else {
                        var errorMessage = response.message || 
                                         (response.data && response.data.message) || 
                                         (buddyreels_ajax && buddyreels_ajax.strings && buddyreels_ajax.strings.upload_error) ||
                                         'Upload failed';
                        showNotification(errorMessage, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Upload error:', xhr, status, error);
                    var errorMsg = (buddyreels_ajax && buddyreels_ajax.strings && buddyreels_ajax.strings.upload_error) || 'Upload failed';
                    showNotification(errorMsg, 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                    $progressBar.hide().find('.progress-fill').css('width', '0%');
                }
            });
        });
    }

    // Generate thumbnail from video file using Canvas API (fast browser-based solution)
    function generateVideoThumbnail(file) {
        var video = document.createElement('video');
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        var url = URL.createObjectURL(file);
        
        video.preload = 'metadata';
        video.muted = true;
        
        video.onloadedmetadata = function() {
            // Set canvas dimensions
            canvas.width = 300;
            canvas.height = (video.videoHeight / video.videoWidth) * 300;
            
            // Seek to 1 second or 10% of video duration
            var seekTime = Math.min(1, video.duration * 0.1);
            video.currentTime = seekTime;
        };
        
        video.onseeked = function() {
            // Draw video frame to canvas
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert to base64
            var thumbnailData = canvas.toDataURL('image/jpeg', 0.8);
            
            // Store thumbnail data on form
            $('#buddyreels-upload-form').data('thumbnail', thumbnailData);
            
            // Show thumbnail preview
            var $thumbnail = $('#buddyreels-thumbnail-preview');
            if ($thumbnail.length === 0) {
                $thumbnail = $('<div id="buddyreels-thumbnail-preview" style="margin: 10px 0;"><strong>Thumbnail:</strong><br><img style="max-width: 150px; border-radius: 8px; border: 2px solid #ddd;"></div>');
                $('#buddyreels-video-preview').after($thumbnail);
            }
            $thumbnail.find('img').attr('src', thumbnailData);
            
            // Clean up
            URL.revokeObjectURL(url);
        };
        
        video.src = url;
    }

    // Show video preview
    function showVideoPreview(file) {
        var url = URL.createObjectURL(file);
        var $preview = $('#buddyreels-video-preview');
        
        if ($preview.length === 0) {
            $preview = $('<div id="buddyreels-video-preview" style="margin: 10px 0;"><strong>Preview:</strong><br><video controls style="max-width: 300px; border-radius: 8px;"></video></div>');
            $('input[name="reel_video"]').after($preview);
        }
        
        $preview.find('video').attr('src', url).show();
        $preview.show();
    }

    // Initialize reel feed functionality
    function initReelFeed() {
        var $feed = $('.buddyreels-feed');
        var loading = false;
        var page = 2;
        var hasMore = true;

        // Infinite scroll
        $(window).on('scroll', function() {
            if (!loading && hasMore && $(window).scrollTop() + $(window).height() >= $(document).height() - 1000) {
                loadMoreReels();
            }
        });

        function loadMoreReels() {
            if (loading || !hasMore) return;
            
            loading = true;
            
            $.ajax({
                url: buddyreels_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'buddyreels_load_more_reels',
                    page: page,
                    count: 10,
                    user_id: $feed.data('user-id') || 0
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $feed.append(response.data.html);
                        page++;
                        hasMore = response.data.has_more;
                        initVideoPlayer(); // Re-initialize for new videos
                    } else {
                        hasMore = false;
                    }
                },
                complete: function() {
                    loading = false;
                }
            });
        }
    }

    // Initialize like system
    function initLikeSystem() {
        $(document).on('click', '.buddyreels-like-btn', function(e) {
            e.preventDefault();
            
            if (!buddyreels_ajax.like_nonce) {
                showNotification(buddyreels_ajax.strings.login_required, 'error');
                return;
            }
            
            var $btn = $(this);
            var reelId = $btn.data('reel-id');
            
            if ($btn.prop('disabled')) return;
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: buddyreels_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'buddyreels_toggle_like',
                    reel_id: reelId,
                    nonce: buddyreels_ajax.like_nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        $btn.toggleClass('liked', data.liked);
                        $btn.find('.likes-count').text(data.like_count);
                        $btn.find('svg').attr('fill', data.liked ? 'red' : 'none');
                    } else {
                        showNotification(response.data || buddyreels_ajax.strings.like_error, 'error');
                    }
                },
                error: function() {
                    showNotification(buddyreels_ajax.strings.like_error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    // Initialize video player
    function initVideoPlayer() {
        var currentlyPlaying = null;
        
        $('.buddyreels-video').each(function() {
            var $video = $(this);
            var video = this;
            
            // Intersection Observer for autoplay
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            if (currentlyPlaying && currentlyPlaying !== video) {
                                currentlyPlaying.pause();
                            }
                            video.play();
                            currentlyPlaying = video;
                        } else {
                            video.pause();
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe(video);
            }
            
            // Click to play/pause
            $video.on('click', function() {
                if (video.paused) {
                    if (currentlyPlaying && currentlyPlaying !== video) {
                        currentlyPlaying.pause();
                    }
                    video.play();
                    currentlyPlaying = video;
                } else {
                    video.pause();
                }
            });
            
            // Update play button visibility
            $video.on('play', function() {
                $video.siblings('.buddyreels-video-overlay').hide();
            });
            
            $video.on('pause', function() {
                $video.siblings('.buddyreels-video-overlay').show();
            });
        });
    }

    // Initialize mobile upload features
    function initMobileUpload() {
        var $fileInput = $('#reel_video');
        var $preview = $('#buddyreels-video-preview');
        var $previewVideo = $preview.find('video');
        
        $fileInput.on('change', function() {
            var file = this.files[0];
            
            if (file && file.type.startsWith('video/')) {
                var url = URL.createObjectURL(file);
                $previewVideo.attr('src', url);
                $preview.show();
                
                // Generate thumbnail using JavaScript instead of FFmpeg
                generateVideoThumbnail(file);
                
                // Auto-generate caption from filename
                var filename = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]/g, ' ');
                if (!$('#reel_caption').val()) {
                    $('#reel_caption').val(filename);
                }
            }
        });
        
        // Share functionality
        $(document).on('click', '.buddyreels-share-btn', function() {
            var url = $(this).data('url');
            
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this reel',
                    url: url
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(url).then(function() {
                    showNotification('Link copied to clipboard!', 'success');
                });
            }
        });
    }

    // Profile reels management
    $(document).on('click', '.buddyreels-publish-btn', function() {
        var $btn = $(this);
        var reelId = $btn.data('reel-id');
        
        $.ajax({
            url: buddyreels_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'buddyreels_publish_draft',
                reel_id: reelId,
                nonce: buddyreels_ajax.publish_nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data, 'success');
                    location.reload();
                } else {
                    showNotification(response.data, 'error');
                }
            }
        });
    });

    $(document).on('click', '.buddyreels-delete-btn', function() {
        if (!confirm(buddyreels_ajax.strings.confirm_delete)) {
            return;
        }
        
        var $btn = $(this);
        var reelId = $btn.data('reel-id');
        
        $.ajax({
            url: buddyreels_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'buddyreels_delete_reel',
                reel_id: reelId,
                nonce: buddyreels_ajax.delete_nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data, 'success');
                    $btn.closest('.buddyreels-reel-item').fadeOut();
                } else {
                    showNotification(response.data, 'error');
                }
            }
        });
    });

    // Utility function to show notifications
    function showNotification(message, type) {
        var $notification = $('<div class="buddyreels-notification buddyreels-notification-' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }

})(jQuery);
