<?php
// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-load.php');

// Check if our plugin is active
if (!function_exists('buddyreels_init')) {
    echo "BuddyReels plugin not found or not active\n";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>BuddyReels Vertical Feed Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #000;
        }
        .test-container {

            background: #000;
        }
        .test-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            text-align: center;
            z-index: 1000;
        }
        .test-content {
            padding-top: 60px;

        }
    </style>
</head>
<body>
    <div class="test-header">
        <h2>BuddyReels Vertical Feed Test</h2>
        <p>TikTok-style vertical scrolling feed</p>
    </div>
    
    <div class="test-container">
        <div class="test-content">
            <?php
            // Test the shortcode
            echo do_shortcode('[buddyreels_feed count="5" orderby="date" order="DESC"]');
            ?>
        </div>
    </div>
    
    <script>
    // Test if the feed is working
    $(document).ready(function() {
        console.log('Feed test page loaded');
        
        // Check if feed elements exist
        var $feed = $('.buddyreels-feed');
        var $videos = $('.buddyreels-video');
        
        console.log('Feed found:', $feed.length);
        console.log('Videos found:', $videos.length);
        
        // Test scroll behavior
        $feed.on('scroll', function() {
            console.log('Feed scrolled');
        });
        
        // Test video click
        $(document).on('click', '.buddyreels-video', function() {
            console.log('Video clicked');
        });
    });
    </script>
</body>
</html>