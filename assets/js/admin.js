(function ($) {
	'use strict';

	$(document).ready(function () {

		// Color pickers
		$('.yse-color-picker').wpColorPicker();

		function setResult($el, success, msg) {
			$el.removeClass('success error').addClass(success ? 'success' : 'error').text(msg);
		}

		// Test connection
		$('#yse-test-btn').on('click', function () {
			var $btn    = $(this);
			var $result = $('#yse-test-result');
			var apiKey  = $('[name="yse_settings[api_key]"]').val();
			var channel = $('[name="yse_settings[channel_id]"]').val();
			var label   = $btn.text();

			$btn.prop('disabled', true).text(yseAdmin.testing);
			$result.removeClass('success error').text('');

			$.post(yseAdmin.ajaxUrl, {
				action: 'yse_test_connection', nonce: yseAdmin.nonce,
				api_key: apiKey, channel_id: channel
			}, function (res) {
				$btn.prop('disabled', false).text(label);
				setResult($result, res.success, res.data ? res.data.message || res.data : '');
			});
		});

		// Clear cache
		$('#yse-clear-cache-btn').on('click', function () {
			var $btn    = $(this);
			var $result = $('#yse-clear-result');
			var label   = $btn.text();

			$btn.prop('disabled', true).text(yseAdmin.clearing);

			$.post(yseAdmin.ajaxUrl, {
				action: 'yse_clear_cache', nonce: yseAdmin.nonce
			}, function (res) {
				$btn.prop('disabled', false).text(label);
				setResult($result, res.success, res.data ? res.data.message || res.data : '');
			});
		});

	});
}(jQuery));
