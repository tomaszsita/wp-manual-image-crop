var jcrop_api, mic_attachment_id, mic_edited_size, mic_preview_scale;
	
jQuery(document).ready(function($) {
	//image sizes tabs
	$(document).on('click', '.rm-crop-size-tab', function(e) {
		e.preventDefault();
		$(this).addClass('nav-tab-active');
		var tabUrl = $(this).attr('href');
		var sep = tabUrl.indexOf('?') === -1 ? '?' : '&';
		$.get(tabUrl + sep + 'nonce=' + encodeURIComponent(MicCrop.nonce), function(data) {
			$('.mic-modal-body').html(data);
		});
	});
	
	$( document ).on('click', '#micCropImage', function() {
		$('#micCropImage').hide();
		$('#micLoading').show();
		$.post(ajaxurl + '?action=mic_crop_image', { select: jcrop_api.tellSelect(), scaled: jcrop_api.tellScaled(), attachmentId: mic_attachment_id, editedSize: mic_edited_size,  previewScale: mic_preview_scale, make2x: $('#mic-make-2x').prop('checked'), mic_quality: $('#micQuality').val(), nonce: MicCrop.nonce } ,  function(response) {
			if (response.status == 'ok') {
				var newImage = new Image();
				newImage.src = response.file + '?' + Math.random();
				var count = 0;
				function updateImage() {
				    if(newImage.complete) {
				        $('img[src^="' + response.file + '"]').attr('src', newImage.src);
						$('#micCropImage').show();
						$('#micSuccessMessage').show().delay(5000).fadeOut();
						$('#micLoading').hide();
				    }else {
					    setTimeout(updateImage, 200);
				    }
				}
				updateImage();
			}else {
				$('#micFailureMessage').show().delay(10000).fadeOut();
				$('#micFailureMessage .error-message').html(response.message);
				$('#micCropImage').show();
				$('#micLoading').hide();
			}
		}, 'json');
	});
});