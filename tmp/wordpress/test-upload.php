<?php
// Simple upload test page
?>
<!DOCTYPE html>
<html>
<head>
    <title>BuddyReels Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; }
        input, textarea, button { padding: 8px; }
        button { background: #0073aa; color: white; border: none; padding: 10px 20px; cursor: pointer; }
        .progress { width: 100%; height: 20px; background: #f0f0f0; margin: 10px 0; display: none; }
        .progress-fill { height: 100%; background: #0073aa; width: 0%; transition: width 0.3s; }
        .notification { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .notification.success { background: #d4edda; color: #155724; }
        .notification.error { background: #f8d7da; color: #721c24; }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <h1>BuddyReels Upload Test</h1>
    
    <form id="test-upload-form" enctype="multipart/form-data">
        <div class="form-group">
            <label>Video File (MP4):</label>
            <input type="file" name="reel_video" accept="video/mp4" required>
        </div>
        
        <div class="form-group">
            <label>Caption:</label>
            <textarea name="reel_caption" placeholder="Enter a caption..."></textarea>
        </div>
        
        <div class="progress" id="upload-progress">
            <div class="progress-fill"></div>
        </div>
        
        <button type="submit">Upload Reel</button>
    </form>
    
    <div id="video-preview" style="margin-top: 20px; display: none;">
        <strong>Preview:</strong><br>
        <video controls style="max-width: 300px;"></video>
    </div>
    
    <div id="thumbnail-preview" style="margin-top: 20px; display: none;">
        <strong>Thumbnail:</strong><br>
        <img style="max-width: 150px; border: 2px solid #ddd;">
    </div>

    <script>
    $(document).ready(function() {
        // Handle file selection
        $('input[name="reel_video"]').on('change', function(e) {
            var file = e.target.files[0];
            if (file && file.type === 'video/mp4') {
                showVideoPreview(file);
                generateThumbnail(file);
            }
        });
        
        // Handle form submission
        $('#test-upload-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'buddyreels_upload');
            formData.append('buddyreels_nonce', 'test_nonce'); // We'll fix this
            
            // Add thumbnail if available
            var thumbnailData = $(this).data('thumbnail');
            if (thumbnailData) {
                formData.append('thumbnail_data', thumbnailData);
            }
            
            var $btn = $('button[type="submit"]');
            $btn.prop('disabled', true).text('Uploading...');
            $('#upload-progress').show();
            
            $.ajax({
                url: '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percent = (e.loaded / e.total) * 100;
                            $('.progress-fill').css('width', percent + '%');
                        }
                    });
                    return xhr;
                },
                success: function(response) {
                    console.log('Response:', response);
                    showNotification('Upload response received: ' + JSON.stringify(response), 'success');
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr, status, error);
                    showNotification('Upload failed: ' + error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Upload Reel');
                    $('#upload-progress').hide().find('.progress-fill').css('width', '0%');
                }
            });
        });
        
        function showVideoPreview(file) {
            var url = URL.createObjectURL(file);
            $('#video-preview').find('video').attr('src', url).show();
            $('#video-preview').show();
        }
        
        function generateThumbnail(file) {
            var video = document.createElement('video');
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            var url = URL.createObjectURL(file);
            
            video.preload = 'metadata';
            video.muted = true;
            
            video.onloadedmetadata = function() {
                canvas.width = 300;
                canvas.height = (video.videoHeight / video.videoWidth) * 300;
                video.currentTime = Math.min(1, video.duration * 0.1);
            };
            
            video.onseeked = function() {
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                var thumbnailData = canvas.toDataURL('image/jpeg', 0.8);
                $('#test-upload-form').data('thumbnail', thumbnailData);
                $('#thumbnail-preview').find('img').attr('src', thumbnailData);
                $('#thumbnail-preview').show();
                URL.revokeObjectURL(url);
            };
            
            video.src = url;
        }
        
        function showNotification(message, type) {
            var $notification = $('<div class="notification ' + type + '">' + message + '</div>');
            $('body').append($notification);
            setTimeout(function() { $notification.remove(); }, 5000);
        }
    });
    </script>
</body>
</html>