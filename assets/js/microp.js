var jcrop_api, mic_attachment_id, mic_edited_size, mic_preview_scale;
	
jQuery(document).ready(function($) {
	//image sizes tabs
	$(document).on('click', '.rm-crop-size-tab', function(e) {
		e.preventDefault();
		$(this).addClass('nav-tab-active');
		$.get($(this).attr('href'), function(data) {
			$('#TB_ajaxContent').html(data);
		});
	});
	
	$( document ).on('click', '#micCropImage', function() {
		$('#micCropImage').hide();
		$('#micLoading').show();
		$.post(ajaxurl + '?action=mic_crop_image', { select: jcrop_api.tellSelect(), scaled: jcrop_api.tellScaled(), attachmentId: mic_attachment_id, editedSize: mic_edited_size,  previewScale: mic_preview_scale, make2x: $('#mic-make-2x').prop('checked'), mic_quality: $('#micQuality').val() } ,  function(response) {
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
	
	 $('.mic-link').click( function() {
		tb_position();
	});
	 
	 $(document).on('click', '#TB_closeWindowButton, .TB_overlayBG', function(e) {
		 $('#TB_overlay,#TB_window').remove();
	 });
	 
	 function adjustMicWindowSize() {
		 	if( ! $('#TB_ajaxContent .mic-editor-wrapper').length) {
		 		return;
		 	}
			var tbWindow = $('#TB_window'), width = $(window).width(), H = 560, W = ( 980 < width ) ? 980 : width, adminbar_height = 0;

			if ( $('body.admin-bar').length )
				adminbar_height = 28;

			if ( tbWindow.size() ) {
				tbWindow.width( W ).height( H - 45 - adminbar_height );
				$('#TB_iframeContent, #TB_ajaxContent').width( W).height( H - 75 - adminbar_height );
				tbWindow.css({'margin-left': '-' + parseInt((( W) / 2),10) + 'px'});
				if ( typeof document.body.style.maxWidth != 'undefined' )
					tbWindow.css({'top': 20 + adminbar_height + 'px','margin-top':'0'});
			};
	 }
	 
	 setInterval(adjustMicWindowSize, 200);

});