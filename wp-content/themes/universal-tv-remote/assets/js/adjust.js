/**
 * Adjust deep-link attribution — vanilla port of lib/adjust.js.
 */
window.TVRAdjust = (function () {
	var ADJUST_FORWARD_PARAMS = ['campaign', 'adgroup', 'creative', 'gclid', 'lpurl', 'gbraid', 'redirect'];
	var ADJUST_PARAM_KEYS = ['adjust_click_token'].concat(ADJUST_FORWARD_PARAMS);
	var PLACEHOLDER_PATTERN = /\{[^{}]+\}/;

	function extractAdjustParams(searchParams) {
		var out = {};
		ADJUST_PARAM_KEYS.forEach(function (key) {
			var value = searchParams.get(key);
			if (value) out[key] = value;
		});
		return out;
	}

	function mergeAdjustParams() {
		var merged = {};
		for (var i = 0; i < arguments.length; i++) {
			var source = arguments[i];
			if (source) Object.assign(merged, source);
		}
		return Object.keys(merged).length > 0 ? merged : null;
	}

	function getCurrentAdjustParams(persisted) {
		var live = extractAdjustParams(new URLSearchParams(window.location.search));
		return mergeAdjustParams(persisted, live);
	}

	function resolveAdjustUrl(href, adjustData) {
		var token = adjustData && adjustData.adjust_click_token;
		var resolved = href.replace('{adjust_click_token}', token || '');

		var url;
		try {
			url = new URL(resolved);
		} catch (e) {
			return href;
		}

		if (!url.hostname.endsWith('adjust.com')) return href;

		ADJUST_FORWARD_PARAMS.forEach(function (key) {
			var actual = adjustData && adjustData[key];
			if (actual) {
				url.searchParams.set(key, actual);
				return;
			}
			var current = url.searchParams.get(key);
			if (current && PLACEHOLDER_PATTERN.test(current)) {
				url.searchParams.delete(key);
			}
		});
		return url.toString();
	}

	return {
		extractAdjustParams: extractAdjustParams,
		mergeAdjustParams: mergeAdjustParams,
		getCurrentAdjustParams: getCurrentAdjustParams,
		resolveAdjustUrl: resolveAdjustUrl,
	};
})();
