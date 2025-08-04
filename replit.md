# BuddyReels

## Overview

BuddyReels is a custom WordPress plugin designed to add TikTok-style video reel functionality to BuddyPress-powered websites. The plugin enables users to upload MP4 videos through the BuddyPress activity form, automatically generates video thumbnails using FFmpeg, and displays reels in a mobile-first vertical feed format. It integrates seamlessly with BuddyPress profiles, activity streams, and social features like likes and comments.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Plugin Structure
- **Main Plugin Architecture**: Standard WordPress plugin structure following WordPress.org development standards
- **Integration Pattern**: Tight integration with BuddyPress plugin using hooks, filters, and BuddyPress-specific APIs
- **Security Implementation**: Proper nonce handling, input sanitization, and secure file uploads using `wp_handle_upload()`

### Frontend Architecture
- **Mobile-First Design**: Responsive grid layouts and vertical-scrolling feed optimized for mobile devices
- **Video Player**: Custom HTML5 video player with autoplay, loop, and muted-by-default functionality
- **UI Components**: 
  - Upload form integrated into BuddyPress activity composer
  - Profile tab for user's reel collection
  - Feed shortcode `[buddyreels_feed]` for displaying all reels
  - Like and comment system overlay

### Backend Architecture
- **File Handling**: Secure MP4 video uploads with validation and storage management
- **Thumbnail Generation**: Server-side FFmpeg integration to extract first frame as thumbnail
- **Data Storage**: WordPress post types and meta fields for reel data, likes stored as post/activity meta
- **Activity Stream Integration**: Auto-posting reels to BuddyPress activity feed with thumbnails and captions

### BuddyPress Integration
- **Profile Extension**: Custom "Reels" tab added to user profiles
- **Activity Stream**: Seamless integration with BuddyPress activity posting and display
- **Social Features**: Leverages BuddyPress user system for likes, comments, and social interactions

## External Dependencies

### Server Requirements
- **FFmpeg**: Required for video thumbnail generation via server-side `exec()` calls
- **PHP Extensions**: Video processing and file upload capabilities

### WordPress Dependencies
- **BuddyPress Plugin**: Core dependency for user profiles, activity streams, and social features
- **WordPress Media Library**: Integration for file storage and management

### Frontend Libraries
- **jQuery**: For AJAX functionality, form handling, and interactive features
- **HTML5 Video API**: For custom video player controls and autoplay functionality

### WordPress APIs Used
- **wp_handle_upload()**: Secure file upload handling
- **WordPress Post Types**: Custom post type for reel storage
- **WordPress Meta API**: For storing likes, comments, and reel metadata
- **WordPress Shortcode API**: For `[buddyreels_feed]` shortcode implementation
- **BuddyPress Hooks/Filters**: For profile tabs, activity integration, and user interactions