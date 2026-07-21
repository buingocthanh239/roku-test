(function () {
	'use strict';

	// ---- Reveal.jsx port: fade+slide elements into view on scroll ----
	function initReveal() {
		var els = document.querySelectorAll('.reveal');
		if (!els.length || !('IntersectionObserver' in window)) {
			els.forEach(function (el) { el.classList.add('is-visible'); });
			return;
		}
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });
		els.forEach(function (el) { observer.observe(el); });
	}

	// ---- SiteHeader.jsx mobile menu toggle ----
	function initHeaderToggle() {
		var btn = document.getElementById('tvr-menu-toggle');
		var nav = document.getElementById('tvr-mobile-nav');
		var iconOpen = document.getElementById('tvr-menu-icon-open');
		var iconClose = document.getElementById('tvr-menu-icon-close');
		if (!btn || !nav) return;
		btn.addEventListener('click', function () {
			var isOpen = nav.classList.toggle('hidden') === false;
			btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			if (iconOpen && iconClose) {
				iconOpen.classList.toggle('hidden', isOpen);
				iconClose.classList.toggle('hidden', !isOpen);
			}
		});
	}

	// ---- CampaignTracker.jsx port ----
	function initCampaignTracker() {
		var params = new URLSearchParams(window.location.search);

		var adjustData = window.TVRAdjust.extractAdjustParams(params);
		if (Object.keys(adjustData).length > 0) {
			window.TVRAnalytics.saveAdjustParams(adjustData);
		}

		var CAMPAIGN_PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'fbclid'];
		var hasCampaignParam = CAMPAIGN_PARAMS.some(function (p) { return params.has(p); });
		if (!hasCampaignParam) return;

		var campaignData = {};
		CAMPAIGN_PARAMS.forEach(function (key) {
			var val = params.get(key);
			if (val) campaignData[key] = val;
		});
		window.TVRAnalytics.saveCampaignCookie(campaignData);
		window.TVRAnalytics.trackCampaignParams();
	}

	// ---- CtaLink.jsx port: Adjust-resolved store links + conversion tracking ----
	function buildResolvedHref(href) {
		var adjustData = window.TVRAdjust.getCurrentAdjustParams(window.TVRAnalytics.getAdjustParams());
		return window.TVRAdjust.resolveAdjustUrl(href, adjustData);
	}

	function initCtaLinks() {
		document.addEventListener('click', function (e) {
			var link = e.target.closest('[data-tvr-cta]');
			if (!link) return;
			e.preventDefault();

			var directLink = link.getAttribute('data-direct-link') === '1';
			var href = link.getAttribute('href');
			var targetUrl = directLink ? href : buildResolvedHref(href);
			var conversionLabel = link.getAttribute('data-conversion-label') || '';

			var redirected = false;
			function doRedirect() {
				if (redirected) return;
				redirected = true;
				window.location.href = targetUrl;
			}

			var timer = setTimeout(doRedirect, 1000);
			window.TVRAnalytics.trackConversion(conversionLabel, function () {
				clearTimeout(timer);
				doRedirect();
			});
		});
	}

	// ---- DirectoryClient.jsx port: client-side search + category filter ----
	function initDirectoryFilter() {
		var body = document.getElementById('tvr-directory-body');
		if (!body) return;

		var searchInput = document.getElementById('tvr-directory-search');
		var pillsWrap = document.getElementById('tvr-directory-pills');
		var countEl = document.getElementById('tvr-directory-count');
		var emptyEl = document.getElementById('tvr-directory-empty');
		var tableWrap = document.getElementById('tvr-directory-table-wrap');
		var rows = Array.prototype.slice.call(body.querySelectorAll('tr'));

		var initialParams = new URLSearchParams(window.location.search);
		var state = { category: initialParams.get('category') || '', query: initialParams.get('q') || '' };

		if (searchInput) searchInput.value = state.query;

		function setActivePill() {
			if (!pillsWrap) return;
			pillsWrap.querySelectorAll('[data-category]').forEach(function (btn) {
				var active = btn.getAttribute('data-category') === state.category;
				btn.classList.toggle('bg-brand-600', active);
				btn.classList.toggle('text-white', active);
				btn.classList.toggle('border', !active);
				btn.classList.toggle('border-slate-200', !active);
				btn.classList.toggle('bg-white', !active);
				btn.classList.toggle('text-slate-600', !active);
			});
		}

		function apply() {
			var q = state.query.trim().toLowerCase();
			var visibleCount = 0;
			rows.forEach(function (row, i) {
				var keywords = (row.getAttribute('data-keywords') || '').split(',');
				var name = (row.getAttribute('data-name') || '').toLowerCase();
				var domain = (row.getAttribute('data-domain') || '').toLowerCase();

				var matchesCategory = !state.category || keywords.indexOf(state.category) !== -1;
				var matchesQuery = !q || name.indexOf(q) !== -1 || domain.indexOf(q) !== -1 || keywords.some(function (k) { return k.indexOf(q) !== -1; });
				var visible = matchesCategory && matchesQuery;

				row.style.display = visible ? '' : 'none';
				if (visible) {
					visibleCount++;
					var numCell = row.querySelector('[data-role="row-number"]');
					if (numCell) numCell.textContent = visibleCount;
				}
			});

			if (countEl) {
				var label = visibleCount.toLocaleString('en-US') + ' result' + (visibleCount === 1 ? '' : 's');
				var activePillLabel = pillsWrap ? pillsWrap.querySelector('[data-category="' + state.category + '"]') : null;
				if (state.category && activePillLabel) label += ' in ' + activePillLabel.getAttribute('data-label');
				if (state.query) label += ' for "' + state.query + '"';
				countEl.textContent = label;
			}
			if (emptyEl) emptyEl.classList.toggle('hidden', visibleCount !== 0);
			if (tableWrap) tableWrap.classList.toggle('hidden', visibleCount === 0);
			setActivePill();
		}

		if (searchInput) {
			searchInput.addEventListener('input', function () {
				state.query = searchInput.value;
				apply();
			});
		}
		if (pillsWrap) {
			pillsWrap.addEventListener('click', function (e) {
				var btn = e.target.closest('[data-category]');
				if (!btn) return;
				state.category = btn.getAttribute('data-category');
				apply();
			});
		}
		rows.forEach(function (row) {
			row.addEventListener('click', function () {
				var url = row.getAttribute('data-url');
				if (url) window.location.href = url;
			});
		});

		apply();
	}

	document.addEventListener('DOMContentLoaded', function () {
		initReveal();
		initHeaderToggle();
		initCampaignTracker();
		initCtaLinks();
		initDirectoryFilter();
	});
})();
