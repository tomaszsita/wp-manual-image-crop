<?php
/**
 * Manual Image Crop - Animated GIF Support
 * 
 * This class adds support for animated GIF files by providing
 * proper handling and user feedback for animated content.
 */

class ManualImageCropGifFix {

    private static $instance;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ManualImageCropGifFix();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'initGifFix'));
    }

    /**
     * Initialize animated GIF support
     */
    public function initGifFix() {
        add_filter('wp_handle_upload_prefilter', array($this, 'checkAnimatedGif'), 10, 1);
        add_action('wp_ajax_mic_crop_image', array($this, 'handleGifCrop'), 5);
        add_filter('mic_crop_success', array($this, 'handleGifCropSuccess'), 10, 1);
    }

    /**
     * Check if uploaded file is an animated GIF
     */
    public function checkAnimatedGif($file) {
        if ($file['type'] === 'image/gif') {
            $is_animated = $this->isAnimatedGif($file['tmp_name']);
            if ($is_animated) {
                // Store information about animated GIF
                $file['is_animated_gif'] = true;
                $file['gif_frames'] = $this->countGifFrames($file['tmp_name']);
            }
        }
        return $file;
    }

    /**
     * Check if GIF is animated
     */
    private function isAnimatedGif($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        $file_content = file_get_contents($file_path);
        if (!$file_content) {
            return false;
        }

        // Look for multiple Graphic Control Extension blocks
        $count = 0;
        $offset = 0;
        while (($offset = strpos($file_content, "\x21\xF9\x04", $offset)) !== false) {
            $count++;
            $offset++;
            if ($count > 1) {
                return true; // Multiple frames found
            }
        }

        return false;
    }

    /**
     * Count GIF frames
     */
    private function countGifFrames($file_path) {
        if (!file_exists($file_path)) {
            return 0;
        }

        $file_content = file_get_contents($file_path);
        if (!$file_content) {
            return 0;
        }

        // Count Graphic Control Extension blocks
        $count = 0;
        $offset = 0;
        while (($offset = strpos($file_content, "\x21\xF9\x04", $offset)) !== false) {
            $count++;
            $offset++;
        }

        return $count;
    }

    /**
     * Handle cropping of animated GIFs
     */
    public function handleGifCrop() {
        if (!isset($_POST['attachmentId'])) {
            return;
        }

        $attachment_id = intval($_POST['attachmentId']);
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if ($metadata && isset($metadata['is_animated_gif']) && $metadata['is_animated_gif']) {
            // This is an animated GIF, add special handling
            add_filter('mic_crop_success', array($this, 'addGifWarning'), 15, 1);
        }
    }

    /**
     * Add warning for animated GIF crops
     */
    public function addGifWarning($response) {
        if (is_array($response)) {
            $response['gif_warning'] = true;
            $response['message'] = 'Animated GIF cropped successfully. Note: Only the first frame was cropped.';
        }
        return $response;
    }

    /**
     * Handle successful GIF crop
     */
    public function handleGifCropSuccess($crop_data) {
        $attachment_id = $crop_data['attachmentId'];
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if ($metadata && isset($metadata['is_animated_gif']) && $metadata['is_animated_gif']) {
            // Update metadata to indicate this was an animated GIF crop
            $metadata['mic_animated_gif_cropped'] = true;
            $metadata['mic_crop_date'] = current_time('mysql');
            wp_update_attachment_metadata($attachment_id, $metadata);
        }
    }

    /**
     * Add JavaScript for animated GIF handling
     */
    public function addGifScript() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Check for animated GIFs in media library
            $('.mic-crop-link').each(function() {
                var $link = $(this);
                var attachmentId = $link.data('attachment-id');
                
                // Check if this is an animated GIF
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mic_check_gif',
                        attachment_id: attachmentId
                    },
                    success: function(response) {
                        if (response.is_animated_gif) {
                            // Add warning icon
                            $link.after('<span class="dashicons dashicons-warning" title="Animated GIF - only first frame will be cropped"></span>');
                        }
                    }
                });
            });
            
            // Handle crop response for animated GIFs
            $(document).on('mic_crop_complete', function(event, response) {
                if (response.gif_warning) {
                    // Show warning message
                    alert('Animated GIF cropped successfully!\n\nNote: Only the first frame was cropped. The animation will be lost.');
                }
            });
        });
        </script>
        <?php
    }
}

// Initialize the fix
ManualImageCropGifFix::getInstance();
