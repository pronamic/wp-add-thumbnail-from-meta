<?php
/*
Plugin Name: Add thumbnail from meta
Plugin URI: http://pronamic.eu/wp-plugins/add-thumbnail-from-meta/
Description: WordPress plugin wich will add an thumbnail to an WordPress post from an post meta value.

Version: 0.1
Requires at least: 3.0

Author: Pronamic
Author URI: http://pronamic.eu/

Text Domain: add_thumbnail_from_meta
Domain Path: /languages/

License: GPL
*/

require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Add thumbnail from meta
 */
function add_thumbnail_from_meta() {
	global $post;

	$meta_key = 'emg_thumbnail';

	$url = get_post_meta( $post->ID, $meta_key, true );

	if ( ! empty( $url ) ) {
		$response = wp_remote_get( $url );

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$url_path = parse_url( $url, PHP_URL_PATH );

			$file_name = basename( $url_path );
			
			$bits = wp_remote_retrieve_response_message( $response );
			
			$date = new DateTime( $post->post_date );

			$result = wp_upload_bits( $file_name, null, $bits, $date->format( 'Y/m' ) );
	
			if ( $result['error'] === false ) { // no error
				$file_type = wp_check_filetype( $result['file'] );

				$attachment = array(
					'post_parent'    => $post->ID,
					'post_title'     => get_the_title(),
					'post_content'   => get_the_title(),
					'post_mime_type' => $file_type['type'],
					'post_status'    => 'inherit',
					'post_date'      => $post->post_date,
					'post_date_gmt'  => $post->post_date_gmt,
					'guid'           => $result['url']
				);

				$attachment_id = wp_insert_attachment( $attachment, $result['file'], $post->ID );

				if ( file_is_displayable_image( $result['file'] ) ) {
					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $result['file'] );

					wp_update_attachment_metadata( $attachment_id,  $attachment_data );

					update_post_meta( $post->ID, '_thumbnail_id', $attachment_id );
				}

				delete_post_meta( $post->ID, $meta_key );
			}
		}
	}
}

add_action( 'the_post','add_thumbnail_from_meta' );
