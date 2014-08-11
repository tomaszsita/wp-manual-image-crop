=== Manual Image Crop ===
Contributors: tomasz.sita
Tags: crop, cropping, thumbnail, featured image, gallery, images, picture, image, image area
Tested up to: 3.9.1
Requires at least: 3.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WB5ZQWGUM7T96
Stable tag: 1.06

Plugin allows you to manually crop all the image sizes registered in your WordPress theme (in particular featured image).


== Description ==
Plugin allows you to manually crop all the image sizes registered in your WordPress theme (in particular featured image).
Simply click on the "Crop" link next to any image in your media library. 
The "lightbox" style interface will be brought up and you are ready to go.
Whole cropping process is really intuitive and simple.

Apart from media library list, the plugin adds links in few more places:
* Below featured image box ("Crop featured image")
* In the media insert modal window (once you select an image)

GitHub Repository:
https://github.com/tomaszsita/wp-manual-image-crop

Translations:
* French (Gabriel Féron)
* German (Bertram Greenhough)
* Italian (htrex)
* Polish (myself)
* Spanish (Andrew Kurtis)

Please contact me if you want to add a translation (or submit a pull request on GitHub)


== Installation ==
Manually:
* Upload `manual-image-crop` to the `/wp-content/plugins/` directory
* Activate the plugin through the 'Plugins' menu in WordPress

Automatically:
* Navigate to the 'Plugins' menu inside of the wordpress wp-admin dashboard, and select AD NEW 
* Search for 'Manual Imag Crop', and click install 
* When the plugin has been installed, Click 'Activate' 

== Changelog ==
= 1.06 =
* French, German, Italian, Polish, Spanish translations added
* Settings page added (quality, visibility, custom labels)
* Compatibility with Retina/HiDPI (@2x) plugin added
* Fixed issue with closing editor window from within media uploader screen

= 1.05 =
* WordPress 3.9 compatibility issues fixed
* Removed randomly floated 'Maximum upload file size'

= 1.04 =
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