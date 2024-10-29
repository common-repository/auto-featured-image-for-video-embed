<?php
/*
Plugin Name: Video Thumbnail Auto-Feature
Plugin URI: http://wpforce.com
Description: Plugin will grab a thumbnail from the video you embedded, download it, then set it as the featured image.
Version: 1.5
Author: Jonathan Dingman
Author URI: http://wpforce.com
License: GPLv2 or later
*/
	
function autoThumb( $post_id ) {
global $post;
if ( !$post ) { $post = get_post($post_id); }

	if ($post) {
	
		$post_id = $post->ID; 

		if ( !has_post_thumbnail($post_id) ) {

			// Run match checks for specific video sources
			if ( preg_match("/youtube\.com\/(v\/|watch\?v=)([\w\-]+)/", $post->post_content) ) {
				preg_match("/youtube\.com\/(v\/|watch\?v=)([\w\-]+)/", $post->post_content, $match_id);
				$youtubeCheck = TRUE;
			}
			elseif ( preg_match("/stillUrl=(.*?)\.bin/", $post->post_content) ) {
				preg_match("/stillUrl=(.*?)\.bin/", $post->post_content, $match_id);
				$wistiaCheck = TRUE;
			}
            elseif ( preg_match('/guid=(.*?)(&amp;|")/', $post->post_content) ) {
	            preg_match('/guid=(.*?)(&amp;|")/', $post->post_content, $match_id);
	            $wptvCheck = TRUE;
            }

		if  ( $match_id ) {
		
			// Ensure the specific URL is in the $match_id array so it can be used later
	
			if ($youtubeCheck) {
				$yt_id = $match_id[2];
				$match_id = array (1 => "http://img.youtube.com/vi/" . $yt_id . "/0.jpg");
			}
			elseif ($wistiaCheck) {
				$rename_id = $match_id[1];
				$match_id = array (1 => $rename_id . ".jpg");
			}
			elseif ($wptvCheck) {
				$url = "http://wordpress.tv/?feed=rss2&s=".$match_id[1];
				$urlResults = file_get_contents($url);
				preg_match('/<media:thumbnail url="(.*?)"/', $urlResults, $match_id);
			}
	
			// Setup the paths for the thumbnail
			$match_file_path = $match_id[1];
			$results = attach_image_url($match_file_path, $post_id, $post->post_name);

			} // end $match_id 
		}// end !has_post_thumbnail()
	} // end if $post		
} // end function autoThumb()

function attach_image_url($file, $post_id, $desc = null) {
    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');
    if ( ! empty($file) ) {
        // Download file to temp location
        $tmp = download_url( $file );
        // Set variables for storage
        // fix file filename for query strings
        preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $file, $matches);
		$file_array['name'] = basename($matches[0]);        
		$file_array['tmp_name'] = $tmp;
        // If error storing temporarily, unlink
        if ( is_wp_error( $tmp ) ) {
            @unlink($file_array['tmp_name']);
            $file_array['tmp_name'] = '';
        }
        // do the validation and storage stuff
        $id = media_handle_sideload( $file_array, $post_id, $desc );
        // If error storing permanently, unlink
        if ( is_wp_error($id) ) {@unlink($file_array['tmp_name']);}
        add_post_meta($post_id, '_thumbnail_id', $id, true);
    }
}

add_action('save_post', 'autoThumb');
add_action('draft_to_publish', 'autoThumb');
add_action('new_to_publish', 'autoThumb');
add_action('pending_to_publish', 'autoThumb');
add_action('future_to_publish', 'autoThumb');
