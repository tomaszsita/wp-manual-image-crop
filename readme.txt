=== Manual Image Crop ===
Contributors: tomasz.sita
Tags: crop, cropping, thumbnail, featured image, gallery, images, picture, image, image area
Requires at least: 3.0.1
Tested up to: 3.7.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.04

Plugin allows you to manually crop all the image sizes registered in your WordPress theme (in particular featured image).


== Description ==
Plugin allows you to manually crop all the image sizes registered in your WordPress theme (in particular featured image).
Simply click on the "Crop" link next to any image in your media library. 
The "lightbox" style interface will be brought up and you are ready to go.
Whole cropping process is really intuitive and simple.

Apart from media library list, the plugin adds links in few more places:
* Below featured image box ("Crop featured image")
* In the media insert modal window (once you select an image)


== Installation ==
Manually:
1. Upload `manual-image-crop` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Automatically:
1. Navigate to the 'Plugins' menu inside of the wordpress wp-admin dashboard, and select AD NEW 
2. Search for 'Manual Imag Crop', and click install 
3. When the plugin has been installed, Click 'Activate' 

== Frequently Asked Questions ==
= How to display additional image sizes with custom labels? =
A theme can add custom image sizes in their functions.php file, the typical code looks like:

`add_theme_support( 'post-thumbnails' );`
`add_image_size( 'sample-thumb-mid', 400, 400, 1 );`
`add_image_size( 'sample-thumb-big', 800, 400, 1 );`

If you want these images to show up with custom friendly labels you need to use also **image_size_names_choose** filter, eg:

`function add_my_image_size_labels ($sizes) {`
`    $custom_sizes = array(`
`        'sample-thumb-mid' => __('Custom label for middle size','your_theme_textdomain'),`
`        'sample-thumb-big' => __('Custom label for big size','your_theme_textdomain')`
`    );`
`    return array_merge( $sizes, $custom_sizes );`
`}`
`add_filter('image_size_names_choose', 'add_my_image_size_labels');`

== Changelog ==

= 1.04 
* Added remembering of the previously selected area after cropping the specific image size
* Fixed the "wp_enqueue_script was called incorrectly" warning in the WP debug mode

= 1.03 =
* Fixed the issue with overwriting the original image when cropping image size registered after the attachment had been uploaded

= 1.02 =
* Fixed HTTP Authentication no preview issue
* Fixed path issues on multisite/subfolder WP installations

= 1.01 =
* Fixed Chrome stretched image issue
* Improved compatibility with other plugins using 'thickbox'

= 1.0 =
* Initial version