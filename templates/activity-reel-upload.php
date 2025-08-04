<?php
/**
 * Template for reel upload in BuddyPress activity form
 */

if (!is_user_logged_in()) {
    return;
}
?>

<div class="buddyreels-activity-upload">
    <div class="buddyreels-upload-toggle">
        <button type="button" id="buddyreels-toggle-upload" class="buddyreels-btn buddyreels-btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="23,7 16,12 23,17"></polygon>
                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
            </svg>
            <?php esc_html_e('Share a Reel', 'buddyreels'); ?>
        </button>
    </div>
    
    <div class="buddyreels-upload-form-container" id="buddyreels-activity-upload-form" style="display: none;">
        <form class="buddyreels-upload-form" enctype="multipart/form-data">
            <div class="buddyreels-form-group">
                <label for="activity_reel_video"><?php esc_html_e('Video File (MP4)', 'buddyreels'); ?></label>
                <input type="file" id="activity_reel_video" name="reel_video" accept="video/mp4" required />
            </div>
            
            <div class="buddyreels-video-preview" id="buddyreels-activity-video-preview">
                <video controls muted></video>
            </div>
            
            <div class="buddyreels-form-group">
                <label for="activity_reel_caption"><?php esc_html_e('Caption', 'buddyreels'); ?></label>
                <textarea id="activity_reel_caption" name="reel_caption" placeholder="<?php esc_attr_e('What\'s this reel about?', 'buddyreels'); ?>"></textarea>
            </div>
            
            <div class="buddyreels-upload-progress" id="buddyreels-activity-upload-progress">
                <div class="progress-fill"></div>
            </div>
            
            <div class="buddyreels-form-actions">
                <button type="submit" class="buddyreels-btn buddyreels-btn-primary">
                    <?php esc_html_e('Share Reel', 'buddyreels'); ?>
                </button>
                
                <button type="button" class="buddyreels-btn buddyreels-btn-secondary" id="buddyreels-cancel-upload">
                    <?php esc_html_e('Cancel', 'buddyreels'); ?>
                </button>
                
                <label>
                    <input type="checkbox" name="save_as_draft" value="1" />
                    <?php esc_html_e('Save as draft', 'buddyreels'); ?>
                </label>
            </div>
            
            <?php wp_nonce_field('buddyreels_upload_nonce', 'buddyreels_nonce'); ?>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle upload form
    $('#buddyreels-toggle-upload').on('click', function() {
        $('#buddyreels-activity-upload-form').slideToggle();
    });
    
    // Cancel upload
    $('#buddyreels-cancel-upload').on('click', function() {
        $('#buddyreels-activity-upload-form').slideUp();
        $('#buddyreels-activity-upload-form form')[0].reset();
        $('#buddyreels-activity-video-preview').hide();
        $('#buddyreels-thumbnail-preview').remove();
    });
    
    // Handle video file selection for thumbnail generation
    $('#activity_reel_video').on('change', function(e) {
        var file = e.target.files[0];
        if (file && file.type === 'video/mp4') {
            generateActivityVideoThumbnail(file, $(this).closest('form'));
            showActivityVideoPreview(file);
        }
    });
    
    // Handle form submission with thumbnail data
    $('.buddyreels-upload-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var formData = new FormData(this);
        formData.append('action', 'buddyreels_upload_from_activity');
        formData.append('buddyreels_nonce', $('input[name="buddyreels_nonce"]').val());
        
        // Add thumbnail data if available
        var thumbnailData = $form.data('thumbnail');
        if (thumbnailData) {
            formData.append('thumbnail_data', thumbnailData);
        }
        
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Uploading...');
        $('#buddyreels-activity-upload-progress').show();
        
        $.ajax({
            url: buddyreels_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload(); // Refresh to show new activity
                } else {
                    alert(response.data.message || 'Upload failed');
                }
            },
            error: function() {
                alert('Upload failed');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
                $('#buddyreels-activity-upload-progress').hide();
            }
        });
    });
    
    // Generate thumbnail for activity form
    function generateActivityVideoThumbnail(file, $form) {
        var video = document.createElement('video');
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        var url = URL.createObjectURL(file);
        
        video.preload = 'metadata';
        video.muted = true;
        
        video.onloadedmetadata = function() {
            canvas.width = 300;
            canvas.height = (video.videoHeight / video.videoWidth) * 300;
            var seekTime = Math.min(1, video.duration * 0.1);
            video.currentTime = seekTime;
        };
        
        video.onseeked = function() {
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            var thumbnailData = canvas.toDataURL('image/jpeg', 0.8);
            $form.data('thumbnail', thumbnailData);
            
            // Show thumbnail preview
            var $thumbnail = $('#buddyreels-activity-thumbnail-preview');
            if ($thumbnail.length === 0) {
                $thumbnail = $('<div id="buddyreels-activity-thumbnail-preview" style="margin: 10px 0;"><strong>Thumbnail:</strong><br><img style="max-width: 150px; border-radius: 8px; border: 2px solid #ddd;"></div>');
                $('#buddyreels-activity-video-preview').after($thumbnail);
            }
            $thumbnail.find('img').attr('src', thumbnailData);
            
            URL.revokeObjectURL(url);
        };
        
        video.src = url;
    }
    
    // Show video preview for activity form
    function showActivityVideoPreview(file) {
        var url = URL.createObjectURL(file);
        var $preview = $('#buddyreels-activity-video-preview');
        $preview.find('video').attr('src', url).show();
        $preview.show();
    }
    
    // Video preview
    $('#activity_reel_video').on('change', function() {
        var file = this.files[0];
        
        if (file && file.type.startsWith('video/')) {
            var url = URL.createObjectURL(file);
            $('#buddyreels-activity-video-preview video').attr('src', url);
            $('#buddyreels-activity-video-preview').show();
            
            // Auto-generate caption
            var filename = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]/g, ' ');
            if (!$('#activity_reel_caption').val()) {
                $('#activity_reel_caption').val(filename);
            }
        }
    });
    
    // Handle form submission
    $('#buddyreels-activity-upload-form form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'buddyreels_upload_from_activity');
        
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text(buddyreels_ajax.strings.uploading);
        
        var $progressBar = $('#buddyreels-activity-upload-progress');
        $progressBar.show();
        
        $.ajax({
            url: buddyreels_ajax.ajax_url,
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
                if (response.success) {
                    // Show success message
                    if (typeof showNotification === 'function') {
                        showNotification(response.data.message, 'success');
                    } else {
                        alert(response.data.message);
                    }
                    
                    // Reset form and hide
                    $('#buddyreels-activity-upload-form form')[0].reset();
                    $('#buddyreels-activity-video-preview').hide();
                    $('#buddyreels-activity-upload-form').slideUp();
                    
                    // Refresh activity stream if available
                    if (typeof jq !== 'undefined' && jq('#buddypress').length) {
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(response.data.message || buddyreels_ajax.strings.upload_error, 'error');
                    } else {
                        alert(response.data.message || buddyreels_ajax.strings.upload_error);
                    }
                }
            },
            error: function() {
                if (typeof showNotification === 'function') {
                    showNotification(buddyreels_ajax.strings.upload_error, 'error');
                } else {
                    alert(buddyreels_ajax.strings.upload_error);
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
                $progressBar.hide().find('.progress-fill').css('width', '0%');
            }
        });
    });
});
</script>
