<?php

function mic_after_regenerate_thumbnails( $attachment ) {
	if ( !isset($_POST['action']) ) return $attachment;
	if ( stripslashes($_POST['action']) !== 'regeneratethumbnail' ) return $attachment;

	$id = (int) $_REQUEST['id'];
	if ( empty($id) ) return $attachment;

	remove_filter( 'wp_update_attachment_metadata', 'mic_after_regenerate_thumbnails', 100 );
	foreach( $attachment['sizes'] as $size_key => $size_data ) {
		$crop_data = get_post_meta( $id, '_mic_resizesize-' . $size_key, true );
		
		if ( $crop_data ) {
			$ManualImageCrop = ManualImageCrop::getInstance();
			$ManualImageCrop->cropImage( $crop_data, true );
		}
	}
	add_filter( 'wp_update_attachment_metadata', 'mic_after_regenerate_thumbnails', 100 );

	return $attachment;
}
add_filter( 'wp_update_attachment_metadata', 'mic_after_regenerate_thumbnails', 100 );