<?php
/*
Plugin Name: Manual Image Crop
Plugin URI: https://github.com/tomaszsita/wp-manual-image-crop
Description: Plugin allows you to manually crop all the image sizes registered in your WordPress theme (in particular featured image). Simply click on the "Crop" link next to any image in your media library and select the area of the image you want to crop.
Version: 1.14
Author: Tomasz Sita
Author URI: https://github.com/tomaszsita
License: GPL2
Text Domain: microp
Domain Path: /languages/
*/

define('mic_VERSION', '1.14');

include_once(dirname(__FILE__) . '/lib/ManualImageCropSettingsPage.php');
include_once(dirname(__FILE__) . '/lib/ManualImageCropYoastFix.php');
include_once(dirname(__FILE__) . '/lib/ManualImageCropJetpackFix.php');
include_once(dirname(__FILE__) . '/lib/ManualImageCropGifFix.php');

//mic - stands for Manual Image Crop

add_action('plugins_loaded', 'mic_init_plugin');

add_option('mic_make2x', 'true'); //Add option so we can persist make2x choice across sessions

/**
 * inits the plugin
 */
function mic_init_plugin() {
	// we are gonna use our plugin in the admin area only, so ends here if it's a frontend
	if (!is_admin()) return;

	include_once(dirname(__FILE__) . '/lib/ManualImageCrop.php');

	load_plugin_textdomain('microp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	$ManualImageCrop = ManualImageCrop::getInstance();
	add_action( 'admin_enqueue_scripts', array($ManualImageCrop, 'enqueueAssets') );
	$ManualImageCrop->addEditorLinks();

	// Authorization runs at priority 1 so compatibility hooks (priority 5) never run unauthenticated.
	add_action( 'wp_ajax_mic_editor_window', 'mic_ajax_authorize_editor_window', 1 );
	add_action( 'wp_ajax_mic_crop_image', 'mic_ajax_authorize_crop_image', 1 );
	add_action( 'wp_ajax_mic_editor_window', 'mic_ajax_editor_window' );
	add_action( 'wp_ajax_mic_crop_image', 'mic_ajax_crop_image' );
}

/**
 * Verify nonce + edit capability for crop AJAX (JSON responses).
 *
 * @param int $attachment_id Attachment post ID.
 */
function mic_ajax_require_crop_permission( $attachment_id ) {
	if ( ! check_ajax_referer( 'mic_crop', 'nonce', false ) ) {
		wp_send_json( array(
			'status'  => 'error',
			'message' => __( 'Invalid security token.', 'microp' ),
		) );
	}

	$attachment_id = absint( $attachment_id );
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		wp_send_json( array(
			'status'  => 'error',
			'message' => __( 'Invalid attachment.', 'microp' ),
		) );
	}

	if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
		wp_send_json( array(
			'status'  => 'error',
			'message' => __( 'You are not allowed to crop this image.', 'microp' ),
		) );
	}
}

/**
 * Early auth for mic_crop_image (before other plugin hooks on this action).
 */
function mic_ajax_authorize_crop_image() {
	$attachment_id = isset( $_POST['attachmentId'] ) ? absint( $_POST['attachmentId'] ) : 0;
	mic_ajax_require_crop_permission( $attachment_id );
}

/**
 * Early auth for mic_editor_window (HTML response on failure).
 */
function mic_ajax_authorize_editor_window() {
	if ( ! check_ajax_referer( 'mic_crop', 'nonce', false ) ) {
		status_header( 403 );
		wp_die( esc_html__( 'Invalid security token.', 'microp' ), 403 );
	}

	$attachment_id = isset( $_REQUEST['postId'] ) ? absint( $_REQUEST['postId'] ) : 0;
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		status_header( 400 );
		wp_die( esc_html__( 'Invalid attachment.', 'microp' ), 400 );
	}

	if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
		status_header( 403 );
		wp_die( esc_html__( 'You are not allowed to crop this image.', 'microp' ), 403 );
	}
}

/**
 * ajax call rendering the image cropping area
 */
function mic_ajax_editor_window() {
	include_once(dirname(__FILE__) . '/lib/ManualImageCropEditorWindow.php');
	$ManualImageCropEditorWindow = ManualImageCropEditorWindow::getInstance();
	$ManualImageCropEditorWindow->renderWindow();
	exit;
}

/**
 * ajax call that does the cropping job and overrides the previous image version
 */
function mic_ajax_crop_image() {
	$ManualImageCrop = ManualImageCrop::getInstance();
	$ManualImageCrop->cropImage();
	exit;
}


/**
 * add settings link on plugin page
 */
function mic_settings_link($links) {
	        $settings_link = '<a href="options-general.php?page=Mic-setting-admin">' . __('Settings', 'microp') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'mic_settings_link' );


