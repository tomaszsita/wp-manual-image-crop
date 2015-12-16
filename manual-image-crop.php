<?php
/*
Plugin Name: Manual Image Crop
Plugin URI: https://github.com/tomaszsita/wp-manual-image-crop
Description: Plugin allows you to manually crop all the image sizes registered in your WordPress theme (in particular featured image). Simply click on the "Crop" link next to any image in your media library and select the area of the image you want to crop.
Version: 1.12
Author: Tomasz Sita
Author URI: https://github.com/tomaszsita
License: GPL2
Text Domain: microp
Domain Path: /languages/
*/

define('mic_VERSION', '1.12');

include_once(dirname(__FILE__) . '/lib/ManualImageCropSettingsPage.php');

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

	//attach admin actions
	add_action('wp_ajax_mic_editor_window', 'mic_ajax_editor_window');
	add_action('wp_ajax_mic_crop_image', 'mic_ajax_crop_image');
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
	$settings_link = '<a href="options-general.php?page=Mic-setting-admin">' . __('Settings') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'mic_settings_link' );
