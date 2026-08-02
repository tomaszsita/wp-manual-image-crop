<?php
/**
 * Manual Image Crop - Jetpack Photon Compatibility Fix
 * 
 * This class fixes conflicts between Manual Image Crop and Jetpack Photon
 * by ensuring custom crops are properly handled and displayed.
 */

class ManualImageCropJetpackFix {

    private static $instance;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ManualImageCropJetpackFix();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'initJetpackFix'));
    }

    /**
     * Initialize Jetpack Photon compatibility fixes
     */
    public function initJetpackFix() {
        // Only run if Jetpack Photon is active
        if (!$this->isJetpackPhotonActive()) {
            return;
        }

        // Add compatibility fixes
        add_filter('wp_get_attachment_image_src', array($this, 'fixPhotonImageSrc'), 10, 4);
        add_filter('wp_get_attachment_image', array($this, 'fixPhotonImageHtml'), 10, 5);
        add_action('wp_ajax_mic_crop_image', array($this, 'handlePhotonCrop'), 5);
        add_action('mic_crop_success', array($this, 'clearPhotonCache'), 10, 1);
    }

    /**
     * Check if Jetpack Photon is active
     */
    private function isJetpackPhotonActive() {
        return class_exists('Jetpack_Photon') || 
               (defined('JETPACK__VERSION') && get_option('jetpack_photon'));
    }

    /**
     * Fix image src to bypass Photon for custom crops
     */
    public function fixPhotonImageSrc($image, $attachment_id, $size, $icon) {
        // Check if this is a custom crop
        $metadata = wp_get_attachment_metadata($attachment_id);
        if ($metadata && isset($metadata['micSelectedArea'][$size])) {
            // This is a custom crop, bypass Photon
            remove_filter('wp_get_attachment_image_src', array($this, 'fixPhotonImageSrc'), 10);
            $original_image = wp_get_attachment_image_src($attachment_id, $size);
            add_filter('wp_get_attachment_image_src', array($this, 'fixPhotonImageSrc'), 10, 4);
            
            return $original_image;
        }
        
        return $image;
    }

    /**
     * Fix image HTML to bypass Photon for custom crops
     */
    public function fixPhotonImageHtml($html, $attachment_id, $size, $icon, $attr) {
        // Check if this is a custom crop
        $metadata = wp_get_attachment_metadata($attachment_id);
        if ($metadata && isset($metadata['micSelectedArea'][$size])) {
            // This is a custom crop, bypass Photon
            remove_filter('wp_get_attachment_image', array($this, 'fixPhotonImageHtml'), 10);
            $original_html = wp_get_attachment_image($attachment_id, $size, $icon, $attr);
            add_filter('wp_get_attachment_image', array($this, 'fixPhotonImageHtml'), 10, 5);
            
            return $original_html;
        }
        
        return $html;
    }

    /**
     * Handle cropping when Photon is active
     */
    public function handlePhotonCrop() {
        // Ensure Photon doesn't interfere with our cropping process
        if (class_exists('Jetpack_Photon')) {
            // Temporarily disable Photon for this request
            add_filter('jetpack_photon_skip_for_url', '__return_true');
        }
    }

    /**
     * Clear Photon cache after successful crop
     */
    public function clearPhotonCache($crop_data) {
        if (class_exists('Jetpack_Photon')) {
            // Clear Photon cache for this attachment
            $attachment_id = $crop_data['attachmentId'];
            $size = $crop_data['editedSize'];
            
            // Get the image URL
            $image_url = wp_get_attachment_image_src($attachment_id, $size);
            if ($image_url) {
                // Clear Photon cache for this URL
                $this->clearPhotonUrlCache($image_url[0]);
            }
        }
    }

    /**
     * Clear Photon cache for specific URL
     */
    private function clearPhotonUrlCache($url) {
        // Make a request to clear Photon cache
        $photon_url = 'https://i0.wp.com/' . str_replace(array('http://', 'https://'), '', $url);
        wp_remote_get($photon_url . '?photon_cache_buster=' . time());
    }

    /**
     * Add admin notice about Photon compatibility
     */
    public function addPhotonNotice() {
        if ($this->isJetpackPhotonActive()) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong>Manual Image Crop:</strong> 
                    Jetpack Photon compatibility mode is active. Custom crops will bypass Photon CDN to ensure proper display.
                </p>
            </div>
            <?php
        }
    }
}

// Initialize the fix
ManualImageCropJetpackFix::getInstance();
