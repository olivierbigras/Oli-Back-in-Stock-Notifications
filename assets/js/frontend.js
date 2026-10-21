(function () {
	'use strict';

	function setMessage(form, text, isError) {
		var message = form.querySelector('.oli-bis-message');
		if (!message) {
			return;
		}
		message.hidden = !text;
		message.textContent = text || '';
		message.classList.toggle('is-error', !!isError);
		message.classList.toggle('is-success', !isError && !!text);
	}

	function onSubmit(event) {
		var form = event.target;
		if (!form.classList || !form.classList.contains('oli-bis-form')) {
			return;
		}

		event.preventDefault();

		if (!window.oliBisFrontend) {
			return;
		}

		var button = form.querySelector('.oli-bis-submit');
		var data = new FormData(form);
		data.set('nonce', window.oliBisFrontend.nonce);

		if (button) {
			button.disabled = true;
		}

		fetch(window.oliBisFrontend.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		})
			.then(function (response) {
				return response.json().then(function (payload) {
					return { ok: response.ok, payload: payload };
				});
			})
			.then(function (result) {
				var payload = result.payload || {};
				var body = payload.data || {};
				if (body.redirect) {
					window.location.href = body.redirect;
					return;
				}
				if (payload.success) {
					setMessage(form, body.message || window.oliBisFrontend.i18n.success, false);
					form.querySelectorAll('input, button').forEach(function (field) {
						if ('hidden' !== field.type && 'submit' !== field.type) {
							field.disabled = true;
						}
					});
					return;
				}
				setMessage(form, body.message || window.oliBisFrontend.i18n.error, true);
				if (button) {
					button.disabled = false;
				}
			})
			.catch(function () {
				setMessage(form, window.oliBisFrontend.i18n.error, true);
				if (button) {
					button.disabled = false;
				}
			});
	}

	document.addEventListener('submit', onSubmit);
})();
