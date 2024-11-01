<?php 
/*
Plugin Name:  bangumi-sync-plugin
Plugin URI:   http://thec.me/
Version:      1.1
Description:  Sync your post to bangumi when you make a new post at your wordpress.
Author:       TheC
Author URI:   http://thec.me/
*/

include('bangumi-sync-inc.php');

// add option page to admin bar
add_action( 'admin_menu', 'bgm_sync_add_menu');

// add sync controller to post.php
add_action( 'add_meta_boxes', 'bgm_sync_add_control' );

// bind to publish
add_action( 'publish_post', 'bgm_sync_post' );
?>