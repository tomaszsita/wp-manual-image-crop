=== Manual Image Crop ===
Contributors: tomasz.sita
Tags: crop, cropping, thumbnail, featured image, gallery, images, picture, image, image area
Requires at least: 3.0.1
Tested up to: 3.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.03

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


== Changelog ==

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