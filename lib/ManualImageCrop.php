<?php
/**
 * Class responsible for all the logic
 * @author tomasz
 *
 */
class ManualImageCrop {

	private static $instance; 

	/**
	 * Returns the instance of the class [Singleton]
	 * @return ManualImageCrop
	 */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ManualImageCrop();
        }
        return self::$instance;
    }

	private function __construct() {

	}

	/**
	 * Enqueues all necessary CSS and Scripts
	 */
	public function enqueueAssets() {
		add_thickbox();

		wp_register_style( 'rct-admin', plugins_url('assets/css/mic-admin.css', dirname( __FILE__ ) ) );
		wp_enqueue_style( 'rct-admin' );

		wp_register_style( 'jquery-jcrop', plugins_url('assets/css/jquery.Jcrop.min.css', dirname( __FILE__ ) ) );
		wp_enqueue_style( 'jquery-jcrop' );
		
		wp_enqueue_script( 'jquery-color', plugins_url('assets/js/jquery.color.js', dirname( __FILE__ )), array( 'jquery') );
		wp_enqueue_script( 'jquery-jcrop', plugins_url('assets/js/jquery.Jcrop.min.js', dirname( __FILE__ )), array( 'jquery') );
		wp_enqueue_script( 'miccrop', plugins_url('assets/js/microp.js', dirname( __FILE__ )), array( 'jquery') );
	}

	/**
	 * Hooks Editor Links into proper places
	 */
	public function addEditorLinks() {
		add_action( 'media_row_actions', array($this, 'addMediaEditorLinks'), 10, 2 );
		add_action( 'admin_post_thumbnail_html', array($this, 'addCropFeatureImageEditorLink'), 10, 2 );
		add_action( 'print_media_templates', array($this, 'addAttachementEditLink') );
		add_action( 'admin_print_footer_scripts', array($this, 'addAfterUploadAttachementEditLink') );
	}

	/**
	 * Adds links in media library list
	 */
	public function addMediaEditorLinks($links, $post) {
		if (preg_match('/image/', $post->post_mime_type)) {
			$links['crop'] = '<a class="thickbox mic-link" rel="crop" title="Manual Image Crop" href="' . admin_url( 'admin-ajax.php' ) . '?action=mic_editor_window&postId=' . $post->ID . '">' . __('Crop','microp') . '</a>';
		}
		return $links;
	}

	/**
	 * Adds link below "Remove featured image" in post editing form
	 */
	public function addCropFeatureImageEditorLink($content, $post) {
		$content .= '<a id="micCropFeatureImage" class="thickbox mic-link" rel="crop" title="Manual Image Crop" href="' . admin_url( 'admin-ajax.php' ) . '?action=mic_editor_window&postId=' . get_post_thumbnail_id($post) . '">' . __('Crop featured image','microp') . '</a>
<script>
setInterval(function() {
	if (jQuery(\'#remove-post-thumbnail\').is(\':visible\')) {
		jQuery(\'#micCropFeatureImage\').show();
	}else {
		jQuery(\'#micCropFeatureImage\').hide();
	}
}, 200);
</script>';
		return $content;
	}

	/**
	 * Adds link in the ligthbox media library
	 */
	public function addAttachementEditLink() { ?>
	<script>
		var micEditAttachemtnLinkAdded = false;
		var micEditAttachemtnLinkAddedInterval = 0;
		jQuery(document).ready(function() {			
			micEditAttachemtnLinkAddedInterval = setInterval(function() {
				if (jQuery('.details .edit-attachment').length) {
					try {
						var mRegexp = /\?post=([0-9]+)/; 
						var match = mRegexp.exec(jQuery('.details .edit-attachment').attr('href'));
						jQuery('.crop-image-ml.crop-image').remove();
						jQuery('.details .edit-attachment').after( '<a class="thickbox mic-link crop-image-ml crop-image" rel="crop" title="Manual Image Crop" href="' + ajaxurl + '?action=mic_editor_window&postId=' + match[1] + '"><?php _e('Crop Image','microp') ?></a>' );
					} catch (e) {
						console.log(e);
					}
				}

				if (jQuery('.attachment-details .details-image').length) {
					try {
						var postId = jQuery('.attachment-details').attr('data-id');
						jQuery('.button.crop-image-ml.crop-image').remove();
						jQuery('.button.edit-attachment').after( ' <a class="thickbox mic-link crop-image-ml crop-image button" rel="crop" title="Manual Image Crop" href="' + ajaxurl + '?action=mic_editor_window&postId=' + postId + '"><?php _e('Crop Image','microp') ?></a>' );
					} catch (e) {
						console.log(e);
					}
				}
			}, 500);
		});
	</script>
	<?php
	}

	/**
	 * Adds link in the ligthbox media library
	 */
	public function addAfterUploadAttachementEditLink() {
	?>
	<script>
		var micEditAttachemtnLinkAdded = false;
		var micEditAttachemtnLinkAddedInterval = 0;
		jQuery(document).ready(function() {
			micEditAttachemtnLinkAddedInterval = setInterval(function() {
				if (jQuery('#media-items .edit-attachment').length) {
					jQuery('#media-items .edit-attachment').each(function(i, k) {
						try {
							var mRegexp = /\?post=([0-9]+)/; 
							var match = mRegexp.exec(jQuery(this).attr('href'));
							if (!jQuery(this).parent().find('.edit-attachment.crop-image').length && jQuery(this).parent().find('.pinkynail').attr('src').match(/upload/g)) {
								jQuery(this).after( '<a class="thickbox mic-link edit-attachment crop-image" rel="crop" title="Manual Image Crop" href="' + ajaxurl + '?action=mic_editor_window&postId=' + match[1] + '"><?php _e('Crop Image','microp') ?></a>' );
							}
						} catch (e) {
							console.log(e);
						}
					});
				}
			}, 500);
		});
	</script>
  <?php
	}

	/**
	 * Crops the image based on params passed in $_POST array
	 */
	public function cropImage() {
		global $_wp_additional_image_sizes;

		$uploadsDir = wp_upload_dir();
		
		// checks for ssl. wp_upload_dir does not handle ssl (ssl admin trips on this and subsequent ajax success to browser)
		if (is_ssl()) {			
			$uploadsDir['baseurl'] = preg_replace('#^http://#i', 'https://', $uploadsDir['baseurl']);
		}

		$src_file_url = wp_get_attachment_image_src($_POST['attachmentId'], 'full');

		if (!$src_file_url) {
			echo json_encode (array('status' => 'error', 'message' => 'wrong attachment' ) );
			exit;
		}
		
		
		$src_file = str_replace($uploadsDir['baseurl'], $uploadsDir['basedir'], $src_file_url[0]);
		$dst_file_url = wp_get_attachment_image_src($_POST['attachmentId'], $_POST['editedSize']);
		
		if (!$dst_file_url) {
			exit;
		}
		$dst_file = str_replace($uploadsDir['baseurl'], $uploadsDir['basedir'], $dst_file_url[0]);
		

		//checks if the destination image file is present (if it's not, we want to create a new file, as the WordPress returns the original image instead of specific one)
		if ($dst_file == $src_file) {
			$attachmentData = wp_generate_attachment_metadata( $_POST['attachmentId'], $dst_file );
			
			//new destination file path - replaces original file name with the correct one
			$dst_file = str_replace( basename($attachmentData['file']), $attachmentData['sizes'][ $_POST['editedSize'] ]['file'], $dst_file);

			//saves new path to the image size in the database
			wp_update_attachment_metadata( $_POST['attachmentId'],  $attachmentData );
			
			//retrieves the new url to file (needet to refresh the preview)
			$dst_file_url = wp_get_attachment_image_src($_POST['attachmentId'], $_POST['editedSize']);
		}

		//sets the destination image dimensions
		if (isset($_wp_additional_image_sizes[$_POST['editedSize']])) {
			$dst_w = min(intval($_wp_additional_image_sizes[$_POST['editedSize']]['width']), $_POST['select']['w'] * $_POST['previewScale']);;
			$dst_h = min(intval($_wp_additional_image_sizes[$_POST['editedSize']]['height']), $_POST['select']['h'] * $_POST['previewScale']);
		} else {
			$dst_w = min(get_option($_POST['editedSize'].'_size_w'), $_POST['select']['w'] * $_POST['previewScale']);
			$dst_h = min(get_option($_POST['editedSize'].'_size_h'), $_POST['select']['h'] * $_POST['previewScale']);
		}
		
		if (!$dst_w || !$dst_h) {
			echo json_encode (array('status' => 'error', 'message' => 'wrong dimensions' ) );
			exit;
		}

		//prepares coordinates that will be passed to cropping function
		$dst_x = 0;
		$dst_y = 0;
		$src_x = max(0, $_POST['select']['x']) * $_POST['previewScale'];
		$src_y = max(0, $_POST['select']['y']) * $_POST['previewScale'];
		$src_w = max(0, $_POST['select']['w']) * $_POST['previewScale'];
		$src_h = max(0, $_POST['select']['h']) * $_POST['previewScale'];
		
		//saves the selected area
		$imageMetadata = wp_get_attachment_metadata($_POST['attachmentId']);
		$imageMetadata['micSelectedArea'][$_POST['editedSize']] = array(
																	'x' => $_POST['select']['x'],
																	'y' => $_POST['select']['y'],
																	'w' => $_POST['select']['w'],
																	'h' => $_POST['select']['h'],
																);
		wp_update_attachment_metadata($_POST['attachmentId'], $imageMetadata);
		
		$quality = intval($_POST['mic_quality']);

        if ( function_exists('wp_get_image_editor') ) {
            $img = wp_get_image_editor( $src_file );
            
            if ( ! is_wp_error( $img ) ) {
				
            	$img->crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, false );
                $img->set_quality( $quality );
                $img->save($dst_file);
            }else {
            	echo json_encode (array('status' => 'error', 'message' => 'WP_ERROR: ' . $img->get_error_message() ) );
				exit;
            }
        } else {
            //determines what's the image format
            $ext = pathinfo($src_file, PATHINFO_EXTENSION);
            if ($ext == "gif"){
                $src_img = imagecreatefromgif($src_file);
            } else if($ext =="png"){
                $src_img = imagecreatefrompng($src_file);
            } else {
                $src_img = imagecreatefromjpeg($src_file);
            }
            
            if ($src_img === false ) {
            	echo json_encode (array('status' => 'error', 'message' => 'PHP ERROR: Cannot create image from the source file' ) );
            	exit;
            }
            
            $dst_img = imagecreatetruecolor($dst_w, $dst_h);
            $resampleReturn  = imagecopyresampled($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
            
            if ($resampleReturn === false ) {
            	echo json_encode (array('status' => 'error', 'message' => 'PHP ERROR: imagecopyresampled' ) );
	          	exit;
            }
            
			$imageSaveReturn = true;
            if ($ext == "gif"){
                $imageSaveReturn = imagegif($dst_img, $dst_file);
            } else if($ext =="png"){
                $imageSaveReturn = imagepng($dst_img, $dst_file);
            } else {
                $imageSaveReturn = imagejpeg($dst_img, $dst_file, $quality);
            }
            
            if ($imageSaveReturn === false ) {
            	echo json_encode (array('status' => 'error', 'message' => 'PHP ERROR: imagejpeg/imagegif/imagepng' ) );
            	exit;
        	}
        }
        
		// Generate Retina Image
		if( isset( $_POST['make2x'] ) && $_POST['make2x'] === 'true' ) {
			$dst_w2x = $dst_w * 2;
			$dst_h2x = $dst_h * 2;
		
			$dot = strrpos($dst_file,".");
			$dst_file2x = substr($dst_file,0,$dot).'@2x'.substr($dst_file,$dot);
		
			// Check image size and create the retina file if possible
			if ( $src_w > $dst_w2x && $src_h > $dst_h2x) {
				if ( function_exists('wp_get_image_editor') ) {
					$img = wp_get_image_editor( $src_file );
					if ( ! is_wp_error( $img ) ) {
						$img->crop( $src_x, $src_y, $src_w, $src_h, $dst_w2x, $dst_h2x, false );
						$img->set_quality( $quality );
						$img->save($dst_file2x);
					}else {
            			echo json_encode (array('status' => 'error', 'message' => 'WP_ERROR: ' . $img->get_error_message() ) );
						exit;
            		}
				} else {			
					$dst_img2x = imagecreatetruecolor($dst_w2x, $dst_h2x);
					$resampleReturn = imagecopyresampled($dst_img2x, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w2x, $dst_h2x, $src_w, $src_h);
            
		            if ($resampleReturn === false ) {
		            	echo json_encode (array('status' => 'error', 'message' => 'PHP ERROR: imagecopyresampled' ) );
			          	exit;
		            }
		            
		            $imageSaveReturn = true;
					if ($ext == "gif"){
						$imageSaveReturn = imagegif($dst_img2x, $dst_file2x);
					} else if($ext =="png"){
						$imageSaveReturn = imagepng($dst_img2x, $dst_file2x);
					} else {
						$imageSaveReturn = imagejpeg($dst_img2x, $dst_file2x, $quality);
					}
					
					if ($imageSaveReturn === false ) {
						echo json_encode (array('status' => 'error', 'message' => 'PHP ERROR: imagejpeg/imagegif/imagepng' ) );
						exit;
					}
				}
			}
		}
		// update 'mic_make2x' option status to persist choice  
		if( isset( $_POST['make2x'] ) && $_POST['make2x'] !== get_option('mic_make2x') ) {
			update_option('mic_make2x', $_POST['make2x']);
		}

		//returns the url to the generated image (to allow refreshing the preview)
		echo json_encode (array('status' => 'ok', 'file' => $dst_file_url[0] ) );
		exit;
	}
}
