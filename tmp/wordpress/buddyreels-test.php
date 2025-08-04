<?php
// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-load.php');

// Check if our plugin is active
if (!function_exists('buddyreels_init')) {
    echo "BuddyReels plugin not found or not active\n";
    exit;
}

// Simple AJAX endpoint test
if (isset($_POST['action']) && $_POST['action'] === 'buddyreels_upload') {
    echo "AJAX upload action received\n";
    echo "POST data: " . print_r($_POST, true) . "\n";
    echo "FILES data: " . print_r($_FILES, true) . "\n";
    
    // Try to call the upload handler directly
    if (class_exists('BuddyReels_Upload')) {
        $upload_handler = new BuddyReels_Upload();
        echo "Upload handler class found\n";
    } else {
        echo "Upload handler class NOT found\n";
    }
    
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>BuddyReels Test</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <h1>BuddyReels Upload Test</h1>
    
    <form id="test-form" enctype="multipart/form-data">
        <p>
            <label>Video File:</label><br>
            <input type="file" name="reel_video" accept="video/mp4">
        </p>
        <p>
            <label>Caption:</label><br>
            <textarea name="reel_caption"></textarea>
        </p>
        <p>
            <button type="submit">Test Upload</button>
        </p>
    </form>
    
    <div id="results"></div>
    
    <script>
    $('#test-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'buddyreels_upload');
        
        $.ajax({
            url: 'buddyreels-test.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#results').html('<pre>' + response + '</pre>');
            },
            error: function(xhr, status, error) {
                $('#results').html('<p style="color: red;">Error: ' + error + '</p>');
            }
        });
    });
    </script>
</body>
</html>