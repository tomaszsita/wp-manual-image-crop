<?php
/**
 * Manual Image Crop - Yoast SEO Compatibility Fix
 * 
 * This class fixes conflicts between Manual Image Crop and Yoast SEO plugin
 * by ensuring proper DOM manipulation and script loading order.
 */

class ManualImageCropYoastFix {

    private static $instance;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ManualImageCropYoastFix();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'initYoastFix'));
    }

    /**
     * Initialize Yoast SEO compatibility fixes
     */
    public function initYoastFix() {
        // Only run if Yoast SEO is active
        if (!$this->isYoastActive()) {
            return;
        }

        // Add compatibility fixes
        add_action('admin_footer', array($this, 'addYoastCompatibilityScript'));
        add_filter('wpseo_admin_footer_text', array($this, 'modifyYoastFooter'), 10, 1);
        
        // Ensure our scripts load after Yoast
        add_action('admin_enqueue_scripts', array($this, 'adjustScriptPriority'), 999);
    }

    /**
     * Check if Yoast SEO is active
     */
    private function isYoastActive() {
        return defined('WPSEO_VERSION') || 
               is_plugin_active('wordpress-seo/wp-seo.php') || 
               is_plugin_active('wordpress-seo-premium/wp-seo-premium.php');
    }

    /**
     * Adjust script loading priority to avoid conflicts
     */
    public function adjustScriptPriority() {
        // Remove our scripts and re-enqueue with higher priority
        wp_dequeue_script('miccrop');
        wp_dequeue_script('mic-media-modal');
        
        // Re-enqueue with higher priority and Yoast compatibility
        wp_enqueue_script('miccrop', plugins_url('assets/js/microp.js', dirname(__FILE__)), 
            array('jquery', 'media-views', 'yoast-seo-admin'), mic_VERSION, true);
        wp_enqueue_script('mic-media-modal', plugins_url('assets/js/mic-media-modal.js', dirname(__FILE__)), 
            array('jquery', 'media-views', 'miccrop', 'yoast-seo-admin'), mic_VERSION, true);
    }

    /**
     * Add Yoast SEO compatibility script
     */
    public function addYoastCompatibilityScript() {
        if (!$this->isYoastActive()) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {

            
            // Wait for Yoast to finish DOM manipulation
            var yoastInterval = setInterval(function() {
                if ($('.yoast-seo-score').length > 0 || $('.wpseo-score').length > 0) {
                    clearInterval(yoastInterval);

                    
                    // Re-initialize our crop links after Yoast modifications
                    setTimeout(function() {
                        micReinitializeCropLinks();
                    }, 500);
                }
            }, 100);
            
            // Function to reinitialize crop links
            window.micReinitializeCropLinks = function() {
                // Only add crop links where they don't exist yet
                $('.edit-attachment, .details .edit-attachment, .button.edit-attachment').each(function() {
                    var $this = $(this);
                    var href = $this.attr('href');
                    
                    if (href && href.indexOf('post=') !== -1 && !$this.siblings('.mic-crop-link').length) {
                        var match = href.match(/post=([0-9]+)/);
                        if (match) {
                            var postId = match[1];
                            var cropLink = '<a class="mic-crop-link crop-image-ml crop-image" data-attachment-id="' + postId + '" href="#"><?php _e('Crop Image','microp') ?></a>';
                            $this.after(' ' + cropLink);
                        }
                    }
                });
                
                // Also handle media library items
                $('#media-items .edit-attachment').each(function() {
                    var $this = $(this);
                    var href = $this.attr('href');
                    
                    if (href && href.indexOf('post=') !== -1 && !$this.siblings('.mic-crop-link').length) {
                        var match = href.match(/post=([0-9]+)/);
                        if (match) {
                            var postId = match[1];
                            var cropLink = '<a class="mic-crop-link edit-attachment crop-image" data-attachment-id="' + postId + '" href="#"><?php _e('Crop Image','microp') ?></a>';
                            $this.after(' ' + cropLink);
                        }
                    }
                });
                

            };
            
            // Monitor for Yoast DOM changes
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        // Check if Yoast added new elements
                        for (var i = 0; i < mutation.addedNodes.length; i++) {
                            var node = mutation.addedNodes[i];
                            if (node.nodeType === 1 && (node.classList.contains('yoast-seo-score') || 
                                node.classList.contains('wpseo-score') || 
                                node.querySelector('.yoast-seo-score, .wpseo-score'))) {

                                setTimeout(function() {
                                    micReinitializeCropLinks();
                                }, 100);
                                break;
                            }
                        }
                    }
                });
            });
            
            // Start observing
            if ($('.attachment-details').length > 0) {
                observer.observe($('.attachment-details')[0], {
                    childList: true,
                    subtree: true
                });
            }
            
            // Also handle Yoast's AJAX requests
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
                    setTimeout(function() {
                        micReinitializeCropLinks();
                    }, 200);
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Modify Yoast footer to avoid conflicts
     */
    public function modifyYoastFooter($footer_text) {
        // Add our compatibility notice
        $footer_text .= ' | <small>Manual Image Crop compatibility mode active</small>';
        return $footer_text;
    }
}

// Initialize the fix
ManualImageCropYoastFix::getInstance();
