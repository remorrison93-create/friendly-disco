(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'nk9-entry-form' );
		if ( ! form ) {
			return;
		}

		var methodRadios = form.querySelectorAll( 'input[name="entry_method"]' );
		var panels = form.querySelectorAll( '[data-method-panel]' );
		var instagramInput = form.querySelector( '#nk9_instagram_url' );
		var videoInput = form.querySelector( '#nk9_video_file' );
		var submitBtn = document.getElementById( 'nk9-submit-btn' );
		var maxMb = parseInt( form.getAttribute( 'data-max-video-mb' ), 10 ) || 500;

		function syncPanels() {
			var selected = form.querySelector( 'input[name="entry_method"]:checked' );
			var method = selected ? selected.value : 'instagram';

			panels.forEach( function ( panel ) {
				var isActive = panel.getAttribute( 'data-method-panel' ) === method;
				panel.hidden = ! isActive;
			} );

			if ( instagramInput ) {
				instagramInput.required = 'instagram' === method;
			}
			if ( videoInput ) {
				videoInput.required = 'video' === method;
			}
		}

		methodRadios.forEach( function ( radio ) {
			radio.addEventListener( 'change', syncPanels );
		} );
		syncPanels();

		if ( videoInput ) {
			videoInput.addEventListener( 'change', function () {
				if ( videoInput.files && videoInput.files[0] ) {
					var sizeMb = videoInput.files[0].size / ( 1024 * 1024 );
					if ( sizeMb > maxMb ) {
						alert( 'That video is ' + Math.round( sizeMb ) + 'MB, which is over the ' + maxMb + 'MB limit. Please choose a smaller clip or post it to Instagram instead.' );
						videoInput.value = '';
					}
				}
			} );
		}

		form.addEventListener( 'submit', function () {
			if ( submitBtn ) {
				submitBtn.disabled = true;
				submitBtn.textContent = 'Submitting…';
			}
		} );
	} );
} )();
