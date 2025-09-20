(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 */

	$(document).ready(function () {
		// Handle credits check button
		$('#check-credits').on('click', function (e) {
			e.preventDefault();

			const $button = $(this);
			const $creditsStatus = $('#credits-status');
			const $creditsInfo = $('#credits-info');

			// Get API key from form
			const apiKey = $('#api_key').val();

			if (!apiKey) {
				alert('Please enter an API key first.');
				return;
			}

			// Disable button and show loading
			$button.prop('disabled', true).text('Checking...');
			$creditsStatus.show();
			$creditsInfo.text('Loading...');

			// Make AJAX request
			$.ajax({
				url: photobooster_ai_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'photobooster_ai_check_credits',
					api_key: apiKey,
					nonce: photobooster_ai_admin.nonce
				},
				success: function (response) {
					if (response.success) {
						$creditsInfo.html('<strong>Credits remaining:</strong> ' + response.data.credits);
					} else {
						$creditsInfo.html('<strong>Error:</strong> ' + response.data.message);
					}
				},
				error: function () {
					$creditsInfo.html('<strong>Error:</strong> Failed to check credits. Please try again.');
				},
				complete: function () {
					// Re-enable button
					$button.prop('disabled', false).text('Check Credits');
				}
			});
		});
	});

})(jQuery);
