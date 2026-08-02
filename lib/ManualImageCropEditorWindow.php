<?php
/**
 * Class responsible for rendering the cropping Window
 * @author tomasz
 *
 */
class ManualImageCropEditorWindow {
	
	// Configurable preview size limits - can be adjusted for testing
	private static $mainPreviewMaxWidth = 500;
	private static $mainPreviewMaxHeight = 350;
	private static $smallPreviewMaxWidth = 180;
	private static $smallPreviewMaxHeight = 180;
	
	// Getter methods for preview size limits
	public static function getMainPreviewMaxWidth() {
		return self::$mainPreviewMaxWidth;
	}
	
	public static function getMainPreviewMaxHeight() {
		return self::$mainPreviewMaxHeight;
	}
	
	public static function getSmallPreviewMaxWidth() {
		return self::$smallPreviewMaxWidth;
	}
	
	public static function getSmallPreviewMaxHeight() {
		return self::$smallPreviewMaxHeight;
	}

	private static $instance;

	/**
	 * Returns the instance of the class [Singleton]
	 * @return ManualImageCropEditorWindow
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new ManualImageCropEditorWindow();
		}
		return self::$instance;
	}

	private function __construct() {

	}

	public function renderWindow() {
		$sizesSettings = MicSettingsPage::getSettings();
		
		// Get preview size configuration from frontend - required parameters
		if (!isset($_GET['mainPreviewMaxWidth']) || !isset($_GET['mainPreviewMaxHeight']) || 
			!isset($_GET['smallPreviewMaxWidth']) || !isset($_GET['smallPreviewMaxHeight'])) {
			wp_die('Error: Preview size configuration parameters are missing. This could cause incorrect image cropping.');
		}
		
		$mainPreviewMaxWidth = intval($_GET['mainPreviewMaxWidth']);
		$mainPreviewMaxHeight = intval($_GET['mainPreviewMaxHeight']);
		$smallPreviewMaxWidth = intval($_GET['smallPreviewMaxWidth']);
		$smallPreviewMaxHeight = intval($_GET['smallPreviewMaxHeight']);
		
		// Validate that the values are reasonable
		if ($mainPreviewMaxWidth <= 0 || $mainPreviewMaxHeight <= 0 || 
			$smallPreviewMaxWidth <= 0 || $smallPreviewMaxHeight <= 0) {
			wp_die('Error: Invalid preview size configuration values. All values must be positive integers.');
		}
		

		
		?>
<div class="mic-editor-wrapper">
	<div class="edit-attachment-frame mode-select hide-menu hide-router">
		<div class="attachment-media-view landscape">
			<?php
			// Get image sizes and edited size
			global $_wp_additional_image_sizes;
			$imageSizes = get_intermediate_image_sizes();
			$editedSize = in_array($_GET['size'], $imageSizes) ? $_GET['size'] : null;
			$postId = filter_var($_GET['postId'], FILTER_SANITIZE_NUMBER_INT);
			
			// Process image sizes for dropdown
			$sizeLabels = apply_filters( 'image_size_names_choose', array(
					'thumbnail' => __('Thumbnail'),
					'medium'    => __('Medium'),
					'large'     => __('Large'),
					'full'      => __('Full Size'),
			) );
			$sizeLabels = apply_filters( 'image_size_names_choose', array() );

			foreach ($imageSizes as $s) {
				if ( ! isset($sizesSettings[$s]) ) {
					$sizesSettings[$s] = array('label' => '', 'quality' => 80, 'visibility' => 'visible');
				}

				if ( $sizesSettings[$s]['visibility'] == 'hidden') {
					if ($editedSize == $s) {
						$editedSize = null;
					}
					continue;
				}

				if (isset($_wp_additional_image_sizes[$s])) {
					$cropMethod = $_wp_additional_image_sizes[$s]['crop'];
				} else {
					$cropMethod = get_option($s.'_crop');
				}
				if ($cropMethod == 0) {
					continue;
				}

				if ( is_null($editedSize) ) {
					$editedSize = $s;
				}
			}
			
			//reads the specific registered image size dimension
			if (isset($_wp_additional_image_sizes[$editedSize])) {
				$width = intval($_wp_additional_image_sizes[$editedSize]['width']);
				$height = intval($_wp_additional_image_sizes[$editedSize]['height']);
				$cropMethod = $_wp_additional_image_sizes[$editedSize]['crop'];
			} else {
				$width = get_option($editedSize.'_size_w');
				$height = get_option($editedSize.'_size_h');
				$cropMethod = get_option($editedSize.'_crop');
			}

			$uploadsDir = wp_upload_dir();

			$metaData = wp_get_attachment_metadata($postId);

			$src_file_url = wp_get_attachment_image_src($postId, 'full');
			if (!$src_file_url) {
				echo json_encode (array('status' => 'error', 'message' => 'wrong attachement' ) );
				exit;
			}
			$src_file = str_replace($uploadsDir['baseurl'], $uploadsDir['basedir'], $src_file_url[0]);
			$sizes = getimagesize($src_file);

			$original[0] = $sizes[0];
			$original[1] = $sizes[1];

			// Check if target size is larger than original image
			$targetTooLarge = false;
			$targetTooLargeMessage = '';
			
			if ($width > $sizes[0] && $height > $sizes[1]) {
				$targetTooLarge = true;
				$targetTooLargeMessage = sprintf(
					__('Target size (%dx%d) is larger than original image (%dx%d). Cropping is not possible.', 'microp'),
					$width, $height, $sizes[0], $sizes[1]
				);
			}

			if ($width > $sizes[0]) {
				$sizes[1] = ( $sizes[1] * ($width / $sizes[0]) );
				$height = ceil($height);
				$sizes[0] = $width;
			}

			$previewWidth = min($sizes[0], $mainPreviewMaxWidth);
			$previewHeight = min($sizes[1], $mainPreviewMaxHeight);
			$previewRatio = 1;

			if ($sizes[1]  / $mainPreviewMaxHeight < $sizes[0] / $mainPreviewMaxWidth) {
				$previewHeight = $sizes[1] * $previewWidth / $sizes[0] ;
				$previewRatio = $sizes[1] / $previewHeight;
			}else {
				$previewWidth = $sizes[0] * $previewHeight / $sizes[1];
				$previewRatio = $sizes[0] / $previewWidth;
			}

			$minWidth = min($width / $previewRatio, $previewWidth);
			$minHeight = min($height / $previewRatio, $previewHeight);

			if ($cropMethod != 0) {
				$aspectRatio = ($width / $height);
				// if ($aspectRatio * $minWidth > $sizes[0]) {
				// 	$aspectRatio = ($previewWidth / $minHeight);
				// }

				if (1 / $aspectRatio * $minHeight > $sizes[1]) {
					$aspectRatio = ($minWidth / $previewHeight);
				}

				if ($minWidth / $aspectRatio > $previewHeight) {
					$aspectRatio = $minWidth / $previewHeight;
				}
			}else {
				$aspectRatio = $sizes[0] / $sizes[1];
			}


			$smallPreviewWidth = min($width, $smallPreviewMaxWidth);
			$smallPreviewHeight = min($height, $smallPreviewMaxHeight);

			if ($width > $height) {
				$smallPreviewHeight = $smallPreviewWidth * 1/ $aspectRatio;
				// Ensure height doesn't exceed max height
				if ($smallPreviewHeight > $smallPreviewMaxHeight) {
					$smallPreviewHeight = $smallPreviewMaxHeight;
					$smallPreviewWidth = $smallPreviewHeight * $aspectRatio;
				}
			}else {
				$smallPreviewWidth = $smallPreviewHeight * $aspectRatio;
				// Ensure width doesn't exceed max width
				if ($smallPreviewWidth > $smallPreviewMaxWidth) {
					$smallPreviewWidth = $smallPreviewMaxWidth;
					$smallPreviewHeight = $smallPreviewWidth / $aspectRatio;
				}
			}



			?>
			<?php if ($targetTooLarge): ?>
				<div class="mic-target-too-large-error" style="margin: 20px; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">
					<h4 style="margin: 0 0 10px 0; color: #721c24; width: 100%;">⚠️ <?php _e('Target Size Too Large', 'microp'); ?></h4>
					<p style="margin: 0;"><?php echo $targetTooLargeMessage; ?></p>
					<p style="margin: 10px 0 0 0; font-size: 0.9em;">
						<strong><?php _e('Solution:', 'microp'); ?></strong> 
						<?php _e('Choose a smaller image size or upload a larger original image.', 'microp'); ?>
					</p>
				</div>
			<?php else: ?>
				<div style="margin: auto; width: <?php echo $previewWidth; ?>px;">
					<img style="width: <?php echo $previewWidth; ?>px; height: <?php echo $previewHeight; ?>px;" id="jcrop_target" src="<?php echo wp_get_attachment_url($postId); ?>">
				</div>
			<?php endif; ?>
		</div>
		<div class="attachment-info">
			<div class="settings">
				<span class="setting" data-setting="image-size">
					<label for="mic-size-dropdown" class="name"><?php _e('Pick the image size:','microp'); ?></label>
					<div class="setting-content">
						<select id="mic-size-dropdown" class="mic-size-dropdown">
						<?php
						foreach ($imageSizes as $s) {
							if ( ! isset($sizesSettings[$s]) ) {
								$sizesSettings[$s] = array('label' => '', 'quality' => 80, 'visibility' => 'visible');
							}

							if ( $sizesSettings[$s]['visibility'] == 'hidden') {
								continue;
							}

							if (isset($_wp_additional_image_sizes[$s])) {
								$cropMethod = $_wp_additional_image_sizes[$s]['crop'];
							} else {
								$cropMethod = get_option($s.'_crop');
							}
							if ($cropMethod == 0) {
								continue;
							}

							// Get user defined label for the size or just cleanup a bit
							$label = isset($sizeLabels[$s]) ? $sizeLabels[$s] : ucfirst( str_replace( '-', ' ', $s ) );
							$label = $sizesSettings[$s]['label'] ? $sizesSettings[$s]['label'] : $label;
							
							// Create option with data attributes for JavaScript
							echo '<option value="' . $s . '" data-postid="' . $postId . '"' . ( ($s == $editedSize) ? ' selected' : '' ) . '>' . $label . '</option>';
						}
						?>
						</select>
					</div>
				</span>
				
				<span class="setting" data-setting="new-preview">
					<label class="name"><?php _e('New image:','microp') ?></label>
					<div class="setting-content">
						<div style="width: <?php echo $smallPreviewWidth; ?>px; height: <?php echo $smallPreviewHeight; ?>px; overflow: hidden;">
							<img id="preview" src="<?php echo wp_get_attachment_url($postId); ?>">
						</div>
					</div>
				</span>
				
				<span class="setting" data-setting="previous-preview">
					<label class="name"><?php _e('Previous image:','microp');
					$editedImage =  wp_get_attachment_image_src($postId, $editedSize);
					?></label>
					<div class="setting-content">
						<div style="width: <?php echo $smallPreviewWidth; ?>px; height: <?php echo $smallPreviewHeight; ?>px; overflow: hidden;">
							<img id="micPreviousImage" style="max-width: <?php echo $smallPreviewWidth; ?>px; max-height: <?php echo $smallPreviewHeight; ?>px;" src="<?php echo $editedImage[0] . '?' . time(); ?>">
						</div>
					</div>
				</span>


				
				<?php 
				if ( is_plugin_active('wp-retina-2x/wp-retina-2x.php') ) { ?>
				<span class="setting" data-setting="retina">
					<label for="mic-make-2x" class="name"><?php _e('Generate Retina/HiDPI (@2x):', 'microp') ?></label>
					<div class="setting-content">
						<input type="checkbox" id="mic-make-2x" <?php if(get_option('mic_make2x') === 'true' ) echo 'checked="checked"' ?> />
						<span id="mic-2x-status"></span>
					</div>
				</span>
				<?php 
				} ?>
				
				<?php if (!$targetTooLarge): ?>
				<span class="setting" data-setting="crop-button">
					<div class="setting-content">
						<input id="micCropImage" class="button button-primary button-large" type="button" value="<?php _e('Crop it!','microp') ?>" style="width: 100%; margin-bottom: 10px;" />
						<div class="mic-loading-wrapper" style="display: none;">
							<img src="<?php echo includes_url(); ?>js/thickbox/loadingAnimation.gif" id="micLoading" />
						</div>
						
						<div id="micSuccessMessage" class="updated below-h2" style="display: none; margin-top: 10px;">
							<?php _e('The image has been cropped successfully','microp') ?>
						</div>
						<div id="micFailureMessage" class="error below-h2" style="display: none; margin-top: 10px;">
							<span class="error-message"></span><br>
							<?php _e('An Error has occured. Please try again or contact plugin\'s author.','microp') ?>
						</div>
					</div>
				</span>
				<?php endif; ?>
			</div>

			<div class="settings">
				<?php 
				$ext = strtolower( pathinfo($src_file, PATHINFO_EXTENSION) );
				if ($ext == 'jpg' || $ext == 'jpeg') {
					echo '<span class="setting" data-setting="quality">
					<label for="micQuality" class="name">' . __('Target JPEG Quality', 'microp') . '</label>
					<div class="setting-content">
						<select id="micQuality" name="mic_quality">
						<option value="100">' . __('100 (best quality, biggest file)', 'microp') . '</option>
						<option value="80" ' . ( !isset($sizesSettings[$editedSize]['quality']) || $sizesSettings[$editedSize]['quality'] == '80' ? 'selected' : '' ) . '>' . __('80 (very high quality)', 'microp') . '</option>
						<option value="70" ' . ( isset($sizesSettings[$editedSize]['quality']) && $sizesSettings[$editedSize]['quality'] == '70' ? 'selected' : '' ) . '>' . __('70 (high quality)', 'microp') . '</option>
						<option value="60" ' . ( isset($sizesSettings[$editedSize]['quality']) && $sizesSettings[$editedSize]['quality'] == '60' ? 'selected' : '' ) . '>' . __('60 (good)', 'microp') . '</option>
						<option value="50" ' . ( isset($sizesSettings[$editedSize]['quality']) && $sizesSettings[$editedSize]['quality'] == '50' ? 'selected' : '' ) . '>' . __('50 (average)', 'microp') . '</option>
						<option value="30" ' . ( isset($sizesSettings[$editedSize]['quality']) && $sizesSettings[$editedSize]['quality'] == '30' ? 'selected' : '' ) . '>' . __('30 (low)', 'microp') . '</option>
						<option value="10" ' . ( isset($sizesSettings[$editedSize]['quality']) && $sizesSettings[$editedSize]['quality'] == '10' ? 'selected' : '' ) . '>' . __('10 (very low, smallest file)', 'microp') . '</option>
						</select>
					</div></span>';
				}
				?>
				
				<span class="setting" data-setting="dimensions">
					<label class="name"><?php _e('Dimensions:','microp') ?></label>
					<div class="setting-content">
						<div class="mic-dimensions-compact">
							<div class="mic-original"><?php _e('Original:','microp') ?> <strong><?php echo $original[0]; ?> x <?php echo $original[1]; ?> px</strong></div>
							<div class="mic-target"><?php _e('Target:','microp') ?> <strong><?php echo $width.' x '.$height.' px'; ?></strong></div>
						</div>
					</div>
				</span>
			</div>
	</div>
<script>
		jQuery(document).ready(function($) {
			mic_attachment_id = <?php echo $postId; ?>;
			mic_edited_size = '<?php echo $editedSize; ?>';
			mic_preview_scale = <?php echo $previewRatio; ?>;
			
			// Pass variables to window object for Media Modal
			window.mic_minWidth = <?php echo $minWidth; ?>;
			window.mic_minHeight = <?php echo $minHeight; ?>;
			window.mic_previewWidth = <?php echo $previewWidth; ?>;
			window.mic_previewHeight = <?php echo $previewHeight; ?>;
			window.mic_smallPreviewWidth = <?php echo $smallPreviewWidth; ?>;
			window.mic_smallPreviewHeight = <?php echo $smallPreviewHeight; ?>;
			window.mic_width = <?php echo $width; ?>;
			window.mic_height = <?php echo $height; ?>;
			
			<?php if ( isset( $metaData['micSelectedArea'][$editedSize] ) ) { ?>
				window.mic_setSelect = [<?php echo max(0, $metaData['micSelectedArea'][$editedSize]['x']) ?>, <?php echo max(0, $metaData['micSelectedArea'][$editedSize]['y']) ?>, <?php echo max(0, $metaData['micSelectedArea'][$editedSize]['x']) + $metaData['micSelectedArea'][$editedSize]['w']; ?>, <?php echo max(0, $metaData['micSelectedArea'][$editedSize]['y']) + $metaData['micSelectedArea'][$editedSize]['h']; ?>];
			<?php }else { ?>
				window.mic_setSelect = [<?php echo max(0, ($previewWidth - ($previewHeight * $aspectRatio)) / 2) ?>, <?php echo max(0, ($previewHeight - ($previewWidth / $aspectRatio)) / 2) ?>, <?php echo $previewWidth * $aspectRatio; ?>, <?php echo $previewHeight; ?>];
			<?php }?>
			window.mic_aspectRatio = <?php echo $aspectRatio; ?>;
			
			$('#mic-make-2x').change(function() {$('#mic-2x-status').toggle()});
		});
		</script>
<?php
	}
}
