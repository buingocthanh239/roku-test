/**
 * Cookie-persisted campaign/Adjust attribution + gtag helpers — vanilla port
 * of lib/analytics.js.
 */
window.TVRAnalytics = (function () {
	var CAMPAIGN_COOKIE = '_campaign';
	var CAMPAIGN_COOKIE_DAYS = 30;
	var ADJUST_COOKIE = '_adjust';
	var ADJUST_COOKIE_DAYS = 30;

	function setCookie(name, data, days) {
		var expires = new Date(Date.now() + days * 86400000).toUTCString();
		document.cookie = name + '=' + encodeURIComponent(JSON.stringify(data)) + ';path=/;expires=' + expires + ';SameSite=Lax';
	}

	function getCookie(name) {
		var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
		if (!match) return null;
		try {
			return JSON.parse(decodeURIComponent(match[1]));
		} catch (e) {
			return null;
		}
	}

	function saveCampaignCookie(data) { setCookie(CAMPAIGN_COOKIE, data, CAMPAIGN_COOKIE_DAYS); }
	function getCampaignCookie() { return getCookie(CAMPAIGN_COOKIE); }
	function saveAdjustParams(data) { setCookie(ADJUST_COOKIE, data, ADJUST_COOKIE_DAYS); }
	function getAdjustParams() { return getCookie(ADJUST_COOKIE); }

	function trackEvent(opts) {
		if (typeof window.gtag !== 'function') return;
		window.gtag('event', opts.action, Object.assign({
			event_category: opts.category,
			event_label: opts.label,
			value: opts.value,
		}, opts.rest || {}));
	}

	function trackConversion(conversionLabel, onComplete) {
		if (typeof window.gtag !== 'function') {
			if (onComplete) onComplete();
			return;
		}

		var campaign = getCampaignCookie();
		var adsId = TVR_CONFIG && TVR_CONFIG.conversionId;

		if (adsId && conversionLabel) {
			window.gtag('event', 'conversion', {
				send_to: adsId + '/' + conversionLabel,
				value: 0.0,
				currency: 'USD',
				event_callback: onComplete,
			});
		} else if (onComplete) {
			onComplete();
		}

		var extra = { event_category: 'engagement', event_label: conversionLabel || 'cta' };
		if (campaign) {
			extra.campaign_source = campaign.utm_source;
			extra.campaign_medium = campaign.utm_medium;
			extra.campaign_name = campaign.utm_campaign;
			extra.campaign_term = campaign.utm_term;
			extra.campaign_content = campaign.utm_content;
			extra.click_id = campaign.gclid || campaign.fbclid || undefined;
		}
		window.gtag('event', 'cta_click', extra);
	}

	function trackCampaignParams() {
		if (typeof window.gtag !== 'function') return;
		var params = new URLSearchParams(window.location.search);
		var keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'fbclid'];
		var data = {};
		var has = false;
		keys.forEach(function (key) {
			var val = params.get(key);
			if (val) { data[key] = val; has = true; }
		});
		if (has) window.gtag('event', 'campaign_landing', data);
	}

	return {
		saveCampaignCookie: saveCampaignCookie,
		getCampaignCookie: getCampaignCookie,
		saveAdjustParams: saveAdjustParams,
		getAdjustParams: getAdjustParams,
		trackEvent: trackEvent,
		trackConversion: trackConversion,
		trackCampaignParams: trackCampaignParams,
	};
})();
