(function() {
	'use strict';
	function replaceWpPreferences() {
		if (typeof window.wp === 'undefined') { window.wp = {}; }
		window.wp.preferences = {
			get: function(key, defaultValue) {
				return typeof window.PDFEditorPreferences !== 'undefined' ? window.PDFEditorPreferences.getPreference(key, defaultValue) : defaultValue;
			},
			set: function(key, value) {
				if (typeof window.PDFEditorPreferences !== 'undefined') {
					window.PDFEditorPreferences.setPreference(key, value);
					return window.PDFEditorPreferences.savePreferences();
				}
				return Promise.resolve(false);
			},
			request: function() { return Promise.resolve({}); },
			getAll: function() { return typeof window.PDFEditorPreferences !== 'undefined' ? window.PDFEditorPreferences.getAllPreferences() : {}; },
			save: function() { return typeof window.PDFEditorPreferences !== 'undefined' ? window.PDFEditorPreferences.savePreferences() : Promise.resolve(false); }
		};
	}
	replaceWpPreferences();
	var checkInterval = setInterval(function() {
		if (window.wp && window.wp.preferences && typeof window.wp.preferences.get !== 'function') { replaceWpPreferences(); }
	}, 100);
	setTimeout(function() { clearInterval(checkInterval); }, 10000);
	var originalApiFetch = window.wp && window.wp.apiFetch ? window.wp.apiFetch : null;
	if (originalApiFetch) {
		window.wp.apiFetch = function(options) {
			if (options && options.path && options.path.indexOf('/wp/v2/users/me') !== -1) { return Promise.resolve({}); }
			return originalApiFetch.apply(this, arguments);
		};
	}
	var originalFetch = window.fetch;
	window.fetch = function(input, init) {
		if (typeof input === 'string' && input.indexOf('/wp-json/wp/v2/users/me') !== -1) {
			return Promise.resolve({ ok: true, status: 200, json: function() { return Promise.resolve({}); }, text: function() { return Promise.resolve('{}'); } });
		}
		return originalFetch.apply(this, arguments);
	};
})();
