function start_loader() {
	$('body').append('<div id="preloader"><div class="loader-holder"><div></div><div></div><div></div><div></div>')
}
function end_loader() {
	$('#preloader').fadeOut('fast', function () {
		$('#preloader').remove();
	})
}
// function 
window.alert_toast = function ($msg = 'TEST', $bg = 'success', $pos = '') {
	var Toast = Swal.mixin({
		toast: true,
		position: $pos || 'top-end',
		showConfirmButton: false,
		timer: 5000
	});
	Toast.fire({
		icon: $bg,
		title: $msg
	})
}

/**
 * Global Select2 Initialization & Enhancements
 */
window.init_select2 = function (container = 'body') {
	if (typeof $.fn.select2 !== 'undefined') {
		$(container).find('select').each(function () {
			var _this = $(this);
			// Skip selects explicitly excluded from Select2
			if (_this.attr('data-no-select2') !== undefined) return;
			// Initialize if not already initialized
			if (!_this.hasClass("select2-hidden-accessible")) {
				var minSearch = _this.attr('data-minimum-results-for-search');
				_this.select2({
					placeholder: _this.attr('data-placeholder') || "Select here",
					width: 'resolve',
					allowClear: false,
					minimumResultsForSearch: minSearch !== undefined ? (minSearch === 'Infinity' ? Infinity : parseInt(minSearch)) : 10
				});
			}
		});
	}
}

// Set global defaults
if (typeof $.fn.select2 !== 'undefined') {
	$.fn.select2.defaults.set("selectOnClose", true);
}

$(document).ready(function () {
	// Initialize all Select2 elements on page load
	init_select2();

	// Reusable Login Handler
	function handleLogin(formId, endpoint, redirectUrl) {
		$(formId).submit(function (e) {
			e.preventDefault()
			start_loader()
			if ($('.err_msg').length > 0)
				$('.err_msg').remove()
			$.ajax({
				url: _base_url_ + 'classes/Login.php?f=' + endpoint,
				method: 'POST',
				data: $(this).serialize(),
				error: err => {
					console.log(err)
				},
				success: function (resp) {
					if (resp) {
						resp = JSON.parse(resp)
						if (resp.status == 'success') {
							location.replace(_base_url_ + redirectUrl);
						} else if (resp.status == 'incorrect') {
							var _frm = $(formId)
							var _msg = "<div class='alert alert-danger text-white err_msg'><i class='fa fa-exclamation-triangle'></i> Incorrect username or password</div>"
							_frm.prepend(_msg)
							_frm.find('input').addClass('is-invalid')
							$('[name="username"]').focus()
						}
						end_loader()
					}
				}
			})
		})
	}

	// Initialize login forms
	handleLogin('#login-frm', 'login', 'admin');
	handleLogin('#flogin-frm', 'flogin', 'faculty');
	handleLogin('#slogin-frm', 'slogin', 'student');
	// System Info
	$('#system-frm').submit(function (e) {
		e.preventDefault()
		start_loader()
		if ($('.err_msg').length > 0)
			$('.err_msg').remove()
		$.ajax({
			url: _base_url_ + 'classes/SystemSettings.php?f=update_settings',
			data: new FormData($(this)[0]),
			cache: false,
			contentType: false,
			processData: false,
			method: 'POST',
			type: 'POST',
			error: function (err) {
				console.log(err)
				alert_toast('An error occurred while saving settings.', 'error')
				end_loader()
			},
			success: function (resp) {
				if (resp == 1) {
					alert_toast("Settings saved successfully", 'success')
					setTimeout(function () { location.reload() }, 800)
				} else {
					console.log('Settings response:', resp)
					$('#msg').html('<div class="alert alert-danger err_msg">An Error occured. Check console for details.</div>')
					end_loader()
				}
			}
		})
	})

	/**
	 * Inline Calculator Feature & Zero Auto-clear
	 * Evaluates mathematical expressions starting with '=' in input fields on blur.
	 * Also converts type="number" to type="text" to support the '=' character.
	 * Clears '0' when focusing on a field.
	 */
	$(document).on('focus', 'input', function () {
		var _this = $(this);
		if (_this.attr('type') == 'number') {
			_this.attr('type', 'text').addClass('was-number');
		}
		if (_this.val().trim() == '0') {
			_this.val('');
		}
	});

	$(document).on('blur', 'input', function () {
		var _this = $(this);
		var val = _this.val().trim();
		if (val.startsWith('=')) {
			try {
				var expression = val.substring(1).replace(/[^0-9+\-*/(). ]/g, '');
				if (expression) {
					// Use a safer evaluation method or strict sanitization
					var result = eval(expression);
					if (!isNaN(result)) {
						_this.val(parseFloat(result).toFixed(2)).trigger('change').trigger('input');
					}
				}
			} catch (e) {
				console.error("Calculator Error:", e);
				// Optionally revert or show error
			}
		}
		if (_this.hasClass('was-number')) {
			_this.attr('type', 'number').removeClass('was-number');
		}
	});

	/**
	 * Select2 Tab selection enhancement
	 * Automatically selects the first available result when pressing Tab in a Select2 search box.
	 */
	$(document).on('keydown', '.select2-search__field', function (e) {
		if (e.which === 9) { // Tab key
			var highlighted = $('.select2-results__option--highlighted');
			var searchVal = $(this).val();

			if (highlighted.length === 0) {
				// Fallback to the first non-disabled result if none is explicitly highlighted
				highlighted = $('.select2-results__option[aria-selected]:not(.select2-results__option--disabled)').first();
			}

			if (highlighted.length > 0) {
				// If user typed at least 3 characters or something is highlighted, select it
				if (searchVal.length >= 3 || $('.select2-results__option--highlighted').length > 0) {
					highlighted.trigger('mouseup');
					// Force selection via Select2 internal trigger if mouseup didn't cut it
					var ee = $.Event("keydown", { keyCode: 13, which: 13 });
					$(this).trigger(ee);
				}
			}
		}
	});

	/**
	 * Auto-highlight first result in Select2 search
	 * Ensures that as results change or when opening, the top match is highlighted for easier Tab/Enter selection.
	 */
	$(document).on('keyup', '.select2-search__field', function (e) {
		// Skip navigation keys
		if ([9, 13, 27, 37, 38, 39, 40].indexOf(e.which) > -1) return;

		setTimeout(function () {
			var highlighted = $('.select2-results__option--highlighted');
			if (highlighted.length === 0) {
				$('.select2-results__option[aria-selected]:not(.select2-results__option--disabled)').first().addClass('select2-results__option--highlighted');
			}
		}, 0);
	});

	// Also highlight on open
	$(document).on('select2:open', function () {
		setTimeout(function () {
			if ($('.select2-results__option--highlighted').length === 0) {
				$('.select2-results__option[aria-selected]:not(.select2-results__option--disabled)').first().addClass('select2-results__option--highlighted');
			}
		}, 0);
	});
})
