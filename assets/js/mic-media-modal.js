/**
 * Manual Image Crop - Media Modal Integration
 * Replaces thickbox with WordPress Media Modal while maintaining the same functionality
 */

var jcrop_api, mic_attachment_id, mic_edited_size, mic_preview_scale;

// Frontend configurable preview sizes - will be calculated dynamically
var mic_preview_config = {
    mainPreviewMaxWidth: 0,  // Will be calculated from mic-left-col width
    mainPreviewMaxHeight: 0, // Will be calculated from mic-left-col height
    smallPreviewMaxWidth: 0, // Will be calculated from right column width
    smallPreviewMaxHeight: 0 // Will be calculated from right column height
};

jQuery(document).ready(function($) {

    // Function to calculate preview sizes based on actual DOM measurements
    function calculatePreviewSizes() {
        var $leftCol = $('.attachment-media-view');
        var $rightCol = $('.attachment-info');
        
        if ($leftCol.length && $rightCol.length) {
            var leftColWidth = $leftCol.width();
            var leftColHeight = $leftCol.height();
            var rightColWidth = $rightCol.width();
            var rightColHeight = $rightCol.height();
            
            // Calculate main preview size (leave some margin for padding/borders)
            mic_preview_config.mainPreviewMaxWidth = Math.floor(leftColWidth * 0.95);
            mic_preview_config.mainPreviewMaxHeight = Math.floor(leftColHeight * 0.95);
            
            // Calculate small preview size - 100% of right column width, max height 200px
            var maxSmallPreviewSize = Math.floor(rightColWidth * 1.0);
            mic_preview_config.smallPreviewMaxWidth = maxSmallPreviewSize;
            mic_preview_config.smallPreviewMaxHeight = 200; // Max height for vertical photos
            
            console.log('Calculated preview sizes:', mic_preview_config);
        } else {
            // Fallback values if DOM elements not found
            mic_preview_config.mainPreviewMaxWidth = 900;
            mic_preview_config.mainPreviewMaxHeight = 600;
            mic_preview_config.smallPreviewMaxWidth = 200;
            mic_preview_config.smallPreviewMaxHeight = 200;
            console.log('Using fallback preview sizes:', mic_preview_config);
        }
    }
    
    // Handle crop link clicks
    $(document).on('click', '.mic-crop-link', function(e) {
        e.preventDefault();
        var attachmentId = $(this).data('attachment-id');
        if (attachmentId) {
            openCropModal(attachmentId);
        }
    });

    // Handle window resize for responsive preview sizes
    var resizeTimeout;
    $(window).on('resize', function() {
        // Debounce resize events to avoid too many calculations
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if ($('.mic-modal-overlay').length > 0) {
                console.log('Window resized, recalculating preview sizes...');
                // Recalculate preview sizes
                calculatePreviewSizes();
                
                // Get current attachment ID and selected size
                var currentAttachmentId = mic_attachment_id;
                var currentSize = $('#mic-size-dropdown').val();
                
                if (currentAttachmentId && currentSize) {
                    // Reload content with current size preserved
                    reloadCropContent(currentAttachmentId, currentSize);
                }
            }
        }, 250); // Wait 250ms after resize stops
    });
    
    // Open crop modal using Media Modal
    function openCropModal(attachmentId) {
        // Create a simple modal using WordPress admin modal styles
        var modalHtml = '<div class="mic-modal-overlay">' +
            '<div class="mic-modal-content">' +
            '<div class="mic-modal-header">' +
            '<h2>Manual Image Crop</h2>' +
            '<button class="mic-modal-close">×</button>' +
            '</div>' +
            '<div class="mic-modal-body"></div>' +
            '</div>' +
            '</div>';
        
        // Add modal to body
        $('body').append(modalHtml);
        
        // Load crop content
        loadCropContent(attachmentId);
        
        // Handle close button
        $('.mic-modal-close, .mic-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                closeCropModal();
            }
        });
        
        // Handle escape key
        $(document).on('keydown.mic-modal', function(e) {
            if (e.keyCode === 27) { // ESC key
                closeCropModal();
            }
        });
    }
    
    // Load crop content
    function loadCropContent(attachmentId) {
        // Calculate preview sizes before making the request
        calculatePreviewSizes();
        
        $.ajax({
            url: ajaxurl + '?action=mic_editor_window&postId=' + attachmentId,
            type: 'GET',
            data: {
                nonce: MicCrop.nonce,
                mainPreviewMaxWidth: mic_preview_config.mainPreviewMaxWidth,
                mainPreviewMaxHeight: mic_preview_config.mainPreviewMaxHeight,
                smallPreviewMaxWidth: mic_preview_config.smallPreviewMaxWidth,
                smallPreviewMaxHeight: mic_preview_config.smallPreviewMaxHeight
            },
            success: function(data) {
                $('.mic-modal-body').html(data);
                initializeCropInterface(attachmentId);
            },
            error: function() {
                $('.mic-modal-body').html('<p>Error loading crop interface.</p>');
            }
        });
    }

    // Reload crop content with current size preserved
    function reloadCropContent(attachmentId, size) {
        // Calculate preview sizes before making the request
        calculatePreviewSizes();
        
        $.ajax({
            url: ajaxurl + '?action=mic_editor_window&postId=' + attachmentId + '&size=' + size,
            type: 'GET',
            data: {
                nonce: MicCrop.nonce,
                mainPreviewMaxWidth: mic_preview_config.mainPreviewMaxWidth,
                mainPreviewMaxHeight: mic_preview_config.mainPreviewMaxHeight,
                smallPreviewMaxWidth: mic_preview_config.smallPreviewMaxWidth,
                smallPreviewMaxHeight: mic_preview_config.smallPreviewMaxHeight
            },
            success: function(data) {
                $('.mic-modal-body').html(data);
                initializeCropInterface(attachmentId);
            },
            error: function() {
                $('.mic-modal-body').html('<p>Error loading crop interface.</p>');
            }
        });
    }
    
    // Close crop modal
    function closeCropModal() {
        $('.mic-modal-overlay').remove();
        $(document).off('keydown.mic-modal');
        
        // Clean up resize timeout
        if (resizeTimeout) {
            clearTimeout(resizeTimeout);
        }
        
        // Clean up Jcrop if it exists
        if (jcrop_api) {
            jcrop_api.destroy();
            jcrop_api = null;
        }
    }
    
    // Initialize crop interface (same as original)
    function initializeCropInterface(attachmentId) {
        // Set global variables (same as original)
        mic_attachment_id = attachmentId;
        
        // Initialize Jcrop (same as original)
        setTimeout(function() { 
            $('#jcrop_target').Jcrop({
                onChange: showPreview,
                onSelect: showPreview,
                minSize: [window.mic_minWidth || 50, window.mic_minHeight || 50],
                maxSize: [window.mic_previewWidth || 500, window.mic_previewHeight || 350],
                setSelect: window.mic_setSelect || [0, 0, 200, 200],
                aspectRatio: window.mic_aspectRatio || 1,
                // Prevent accidental deselection
                allowSelect: false,  // Prevent creating new selections by clicking
                allowMove: true,     // Allow moving existing selection
                allowResize: true    // Allow resizing existing selection
            }, function() {
                jcrop_api = this;
            });
        }, 300);
        
        // Handle crop button click (same as original)
        $(document).off('click', '#micCropImage').on('click', '#micCropImage', function() {
            $('#micCropImage').hide();
            $('.mic-loading-wrapper').show();
            $.post(ajaxurl + '?action=mic_crop_image', { 
                select: jcrop_api.tellSelect(), 
                scaled: jcrop_api.tellScaled(), 
                attachmentId: mic_attachment_id, 
                editedSize: mic_edited_size,  
                previewScale: mic_preview_scale, 
                make2x: $('#mic-make-2x').prop('checked'), 
                mic_quality: $('#micQuality').val(),
                nonce: MicCrop.nonce
            }, function(response) {
                if (response.status == 'ok') {
                    var newImage = new Image();
                    newImage.src = response.file + '?' + Math.random();
                    function updateImage() {
                        if(newImage.complete) {
                            $('img[src^="' + response.file + '"]').attr('src', newImage.src);
                            $('#micCropImage').show();
                            $('#micSuccessMessage').show().delay(5000).fadeOut();
                            $('.mic-loading-wrapper').hide();
                        } else {
                            setTimeout(updateImage, 200);
                        }
                    }
                    updateImage();
                } else {
                    $('#micFailureMessage').show().delay(10000).fadeOut();
                    $('#micFailureMessage .error-message').html(response.message);
                    $('#micCropImage').show();
                    $('.mic-loading-wrapper').hide();
                }
            }, 'json');
        });
        
        // Handle size dropdown changes (replaces tab clicks)
        $(document).off('change', '#mic-size-dropdown').on('change', '#mic-size-dropdown', function(e) {
            e.preventDefault();
            var selectedOption = $(this).find('option:selected');
            var size = selectedOption.val();
            var postId = selectedOption.data('postid');
            
            // Always recalculate preview sizes before loading new content
            calculatePreviewSizes();
            
            // Load new content with current preview sizes
            reloadCropContent(postId, size);
        });
    }
    
    // Preview function (same as original)
    function showPreview(coords) {
        var rx = window.mic_smallPreviewWidth / coords.w;
        var ry = window.mic_smallPreviewHeight / coords.h;

        $('#preview').css({
            width: Math.round(rx * window.mic_previewWidth) + 'px',
            height: Math.round(ry * window.mic_previewHeight) + 'px',
            marginLeft: '-' + Math.round(rx * coords.x) + 'px',
            marginTop: '-' + Math.round(ry * coords.y) + 'px'
        });
        
        // Handle 2x status (same as original)
        var mic_2xok = Math.round(coords.w * mic_preview_scale) > (window.mic_width * 2);
        if(mic_2xok === true) {
            $('#mic-2x-status').toggleClass('mic-ok', mic_2xok).html("Compatible");
        } else {
            $('#mic-2x-status').toggleClass('mic-ok', mic_2xok).html("Source too small");
        }
        if($('#mic-make-2x').prop('checked')) $('#mic-2x-status').show();
    }
});
