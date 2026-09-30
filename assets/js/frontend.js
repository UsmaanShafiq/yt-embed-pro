(function () {
	'use strict';

	// ── Thumbnail fallback chain ──────────────────────────────────────────
	// Primary: oar2.jpg (native 9:16 Shorts thumbnail from YouTube).
	// If that 404s, try oardefault → maxresdefault → hqdefault.
	function initThumbnails(wrap) {
		wrap.querySelectorAll('.yse-tile-thumb[data-video-id]').forEach(function (img) {
			var id = img.getAttribute('data-video-id');
			var tried = 0;
			var fallbacks = [
				'https://i.ytimg.com/vi/' + id + '/oardefault.jpg',
				'https://i.ytimg.com/vi/' + id + '/maxresdefault.jpg',
				'https://i.ytimg.com/vi/' + id + '/hqdefault.jpg',
			];
			img.addEventListener('error', function () {
				if (tried < fallbacks.length) {
					img.src = fallbacks[tried++];
				}
			});
		});
	}

	// ── Lightbox ──────────────────────────────────────────────────────────
	// The element that opened the lightbox, so focus can return to it on close.
	var lastOpener = null;

	function openLightbox(wrap, videoId) {
		var lb = wrap.querySelector('.yse-lightbox');
		if (!lb) return;
		var iframe = lb.querySelector('.yse-lightbox-iframe');
		if (!iframe) return;

		lastOpener = document.activeElement;

		// Apply landscape sizing for wall/video layouts.
		var content = lb.querySelector('.yse-lightbox-content');
		if (content) {
			var isLandscape = wrap.getAttribute('data-aspect') === 'landscape';
			content.classList.toggle('is-landscape', isLandscape);
		}

		iframe.src = 'https://www.youtube.com/embed/' + videoId
			+ '?autoplay=1&rel=0&playsinline=1&modestbranding=1';
		lb.removeAttribute('hidden');
		document.body.style.overflow = 'hidden';
		var closeBtn = lb.querySelector('.yse-lightbox-close');
		if (closeBtn) closeBtn.focus();
	}

	function closeLightbox(lb) {
		var iframe = lb.querySelector('.yse-lightbox-iframe');
		if (iframe) iframe.src = '';
		lb.setAttribute('hidden', '');
		document.body.style.overflow = '';

		// Keyboard and screen reader users continue from the video they opened.
		if (lastOpener && document.contains(lastOpener)) lastOpener.focus();
		lastOpener = null;
	}

	function initLightbox(wrap) {
		var lb = wrap.querySelector('.yse-lightbox');
		if (!lb) return;

		var closeBtn = lb.querySelector('.yse-lightbox-close');
		var backdrop = lb.querySelector('.yse-lightbox-backdrop');
		if (closeBtn) closeBtn.addEventListener('click', function () { closeLightbox(lb); });
		if (backdrop) backdrop.addEventListener('click', function () { closeLightbox(lb); });
	}

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			document.querySelectorAll('.yse-lightbox:not([hidden])').forEach(function (lb) {
				closeLightbox(lb);
			});
		}
	});

	// While a lightbox is open, keep focus inside it. If Tab moves focus to
	// the page behind the dialog, send it back to the close button.
	document.addEventListener('focusin', function (e) {
		var open = document.querySelector('.yse-lightbox:not([hidden])');
		if (open && !open.contains(e.target)) {
			var closeBtn = open.querySelector('.yse-lightbox-close');
			if (closeBtn) closeBtn.focus();
		}
	});

	// ── Feed request ──────────────────────────────────────────────────────
	// Every AJAX call sends the channel, playlist and server signature from the
	// feed's wrapper. The server rejects IDs that it didn't sign itself.
	function feedRequest(el, action) {
		var feed = el.closest('.yse-wrap') || el;
		var data = new FormData();
		data.append('action',      action);
		data.append('nonce',       yseVars.nonce);
		data.append('channel_id',  feed.getAttribute('data-channel-id') || '');
		data.append('playlist_id', feed.getAttribute('data-playlist-id') || '');
		data.append('sig',         feed.getAttribute('data-sig') || '');
		return data;
	}

	// ── Tile click (delegated — works for dynamically added tiles too) ────
	function initTileClicks(wrap) {
		wrap.addEventListener('click', function (e) {
			var tile = e.target.closest('.yse-tile');
			if (!tile) return;
			var videoId = tile.getAttribute('data-video-id');
			if (videoId) openLightbox(wrap, videoId);
		});
		// Keyboard: Enter / Space triggers click
		wrap.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter' && e.key !== ' ') return;
			var tile = e.target.closest('.yse-tile');
			if (!tile) return;
			e.preventDefault();
			var videoId = tile.getAttribute('data-video-id');
			if (videoId) openLightbox(wrap, videoId);
		});
	}

	// ── Load More ─────────────────────────────────────────────────────────
	function initLoadMore(wrap) {
		var btn = wrap.querySelector('.yse-load-more-btn');
		if (!btn) return;
		if (btn._yseInitDone) return; // prevent double-binding in tabs
		btn._yseInitDone = true;

		btn.addEventListener('click', function () {
			// In the tabs layout the panel is passed as wrap; the feed IDs live on .yse-wrap.
			var outerWrap = wrap.closest('.yse-wrap') || wrap;
			var nextToken = wrap.getAttribute('data-next-token');
			var perPage   = wrap.getAttribute('data-per-page') || outerWrap.getAttribute('data-per-page') || 12;
			var source    = wrap.getAttribute('data-source') || outerWrap.getAttribute('data-source') || 'shorts';

			if (!nextToken) {
				var lmw = btn.closest('.yse-load-more-wrap');
				if (lmw) lmw.remove();
				return;
			}

			btn.disabled = true;
			var originalText = btn.textContent;
			btn.textContent = originalText + '…';

			// Use the tracked count attribute — more reliable than counting DOM nodes,
			// especially on mobile where layout reflows can affect querySelectorAll timing.
			var tileOffset = parseInt(wrap.getAttribute('data-loaded-count') || '0', 10);

			// If a sort filter is active, use the sort endpoint so Popular
			// pagination continues fetching by viewCount, not by date.
			var currentSort = outerWrap.getAttribute('data-sort') || 'recent';
			var action = (currentSort === 'popular') ? 'yse_sort_feed' : 'yse_load_more';

			var data = feedRequest(wrap, action);
			data.append('page_token', nextToken);
			data.append('per_page',   perPage);
			data.append('source',     source);
			data.append('offset',     tileOffset);
			data.append('sort',       currentSort);

			fetch(yseVars.ajaxUrl, { method: 'POST', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res.success) {
						console.error('YSE load more error:', res.data);
						btn.disabled = false;
						btn.textContent = originalText;
						return;
					}

					// Grid → .yse-grid, Wall → .yse-wall
					var container = wrap.querySelector('.yse-grid') || wrap.querySelector('.yse-wall');
					var frag = document.createElement('div');
					frag.innerHTML = res.data.html;
					initThumbnails(frag);
					var newTileCount = frag.querySelectorAll('.yse-tile').length;
					while (frag.firstChild) {
						container.appendChild(frag.firstChild);
					}

					// Advance tracked count so next batch badges continue correctly.
					var prevCount = parseInt(wrap.getAttribute('data-loaded-count') || '0', 10);
					wrap.setAttribute('data-loaded-count', prevCount + newTileCount);
					if (res.data.next_token) {
						wrap.setAttribute('data-next-token', res.data.next_token);
						btn.disabled = false;
						btn.textContent = originalText;
					} else {
						wrap.removeAttribute('data-next-token');
						var lmw = btn.closest('.yse-load-more-wrap');
						if (lmw) lmw.style.display = 'none';
					}
				})
				.catch(function (err) {
					console.error('YSE fetch error:', err);
					btn.disabled = false;
					btn.textContent = originalText;
				});
		});
	}

	// ── Featured layout: list item → swap hero player ─────────────────────
	function initFeatured(wrap) {
		wrap.addEventListener('click', function (e) {
			var item = e.target.closest('.yse-featured-item-btn');
			if (!item) return;
			var videoId = item.getAttribute('data-video-id');
			var title   = item.getAttribute('data-title') || '';
			if (!videoId) return;

			// Swap the hero iframe src
			var heroIframe = wrap.querySelector('.yse-hero-iframe');
			if (heroIframe) {
				heroIframe.src = 'https://www.youtube.com/embed/' + videoId
					+ '?autoplay=1&rel=0&modestbranding=1';
			}

			// Update the hero title
			var heroTitle = wrap.querySelector('.yse-hero-title');
			if (heroTitle) heroTitle.textContent = title;

			// Highlight active list item
			wrap.querySelectorAll('.yse-featured-item-btn').forEach(function (i) {
				i.classList.remove('is-featured-active');
			});
			item.classList.add('is-featured-active');

			// Scroll list item into view if needed
			item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
		});

		// Keyboard support
		wrap.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter' && e.key !== ' ') return;
			var item = e.target.closest('.yse-featured-item-btn');
			if (item) { e.preventDefault(); item.click(); }
		});
	}

	// ── Tabs layout ───────────────────────────────────────────────────────
	function initTabs(wrap) {
		var nav = wrap.querySelector('.yse-tabs-nav');
		if (!nav) return;

		nav.addEventListener('click', function (e) {
			var btn = e.target.closest('.yse-tab-btn');
			if (!btn) return;
			var tabId = btn.getAttribute('data-tab');

			// Switch active tab button
			nav.querySelectorAll('.yse-tab-btn').forEach(function (b) {
				b.classList.toggle('is-active', b === btn);
				b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
			});

			// Switch active panel
			wrap.querySelectorAll('.yse-tab-panel').forEach(function (panel) {
				var active = panel.getAttribute('data-source') === tabId;
				panel.classList.toggle('is-active', active);
				if (active) initLoadMore(panel);
			});
		});

		// Init load more on each panel independently
		wrap.querySelectorAll('.yse-tab-panel').forEach(function (panel) {
			initThumbnails(panel);
			initLoadMore(panel);
		});
	}

	// ── Slider ────────────────────────────────────────────────────────────
	// Width is computed in JS so it always matches the actual rendered viewport.
	// CSS variables cannot be used reliably on flex children across browsers.
	function initSlider(wrap) {
		var viewport  = wrap.querySelector('.yse-slider-viewport');
		var track     = wrap.querySelector('.yse-slider-track');
		var prevBtn   = wrap.querySelector('.yse-slider-prev');
		var nextBtn   = wrap.querySelector('.yse-slider-next');
		var dotsWrap  = wrap.querySelector('.yse-slider-dots');

		if (!track || !viewport) return;

		var slides   = Array.from(track.querySelectorAll('.yse-slide'));
		var perView  = parseInt(wrap.getAttribute('data-per-view'), 10) || 4;
		var speed    = parseInt(wrap.getAttribute('data-speed'), 10) || 400;
		var autoplay = wrap.getAttribute('data-autoplay') === '1';
		var gap      = parseInt(getComputedStyle(track).gap) || 14;
		var current  = 0;
		var total    = slides.length;
		var slideW   = 0;
		var autoTimer = null;

		track.style.transition = 'transform ' + speed + 'ms cubic-bezier(0.4,0,0.2,1)';

		// Calculate and apply slide widths from actual viewport size
		function calcWidths() {
			gap     = parseInt(getComputedStyle(track).gap) || 14;
			slideW  = (viewport.offsetWidth - gap * (perView - 1)) / perView;
			slides.forEach(function (s) {
				s.style.width      = slideW + 'px';
				s.style.flexShrink = '0';
			});
		}

		// Build dots
		function buildDots() {
			if (!dotsWrap) return;
			dotsWrap.innerHTML = '';
			var pages = Math.ceil(total / perView);
			for (var i = 0; i < pages; i++) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'yse-slider-dot' + (i === 0 ? ' is-active' : '');
				dot.setAttribute('aria-label', 'Page ' + (i + 1));
				(function (pageIdx) {
					dot.addEventListener('click', function () {
						goTo(pageIdx * perView);
						resetAutoplay();
					});
				})(i);
				dotsWrap.appendChild(dot);
			}
		}

		function goTo(idx) {
			var maxIndex = Math.max(0, total - perView);
			current = Math.max(0, Math.min(idx, maxIndex));
			track.style.transform = 'translateX(-' + (current * (slideW + gap)) + 'px)';
			updateUI();
		}

		function updateUI() {
			var maxIndex = Math.max(0, total - perView);
			if (prevBtn) prevBtn.disabled = (current === 0);
			if (nextBtn) nextBtn.disabled = (current >= maxIndex);
			if (dotsWrap) {
				var activePageIdx = Math.floor(current / perView);
				Array.from(dotsWrap.querySelectorAll('.yse-slider-dot')).forEach(function (d, i) {
					d.classList.toggle('is-active', i === activePageIdx);
				});
			}
		}

		if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); resetAutoplay(); });
		if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); resetAutoplay(); });

		// Recalculate on resize and go back to current position
		var resizeTimer;
		window.addEventListener('resize', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				calcWidths();
				goTo(current);
			}, 120);
		});

		function startAutoplay() {
			autoTimer = setInterval(function () {
				var maxIndex = Math.max(0, total - perView);
				goTo(current >= maxIndex ? 0 : current + 1);
			}, 3500);
		}

		function resetAutoplay() {
			if (!autoplay) return;
			clearInterval(autoTimer);
			startAutoplay();
		}

		// Init
		calcWidths();
		buildDots();
		goTo(0);
		if (autoplay) startAutoplay();
	}


	// ── Sort filter bar (Recent / Popular) ───────────────────────────────
	function initSortBar(wrap) {
		var bar = wrap.querySelector('.yse-sort-bar');
		if (!bar) return;

		var btns   = bar.querySelectorAll('.yse-sort-btn');
		var grid   = wrap.querySelector('.yse-grid, .yse-wall');
		var source = wrap.getAttribute('data-source') || 'shorts';

		btns.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var sort = btn.getAttribute('data-sort');
				if (btn.classList.contains('is-active') || btn.classList.contains('is-loading')) return;

				// Update active state.
				btns.forEach(function (b) { b.classList.remove('is-active'); });
				btn.classList.add('is-active', 'is-loading');
				wrap.setAttribute('data-sort', sort);

				// Fade the current tiles while the new order loads.
				if (grid) {
					grid.style.opacity = '0.4';
					grid.style.pointerEvents = 'none';
				}

				var fd = feedRequest(wrap, 'yse_sort_feed');
				fd.append('source',   source);
				fd.append('sort',     sort);
				fd.append('per_page', wrap.getAttribute('data-per-page') || '12');

				fetch(yseVars.ajaxUrl, { method: 'POST', body: fd })
					.then(function (r) { return r.json(); })
					.then(function (res) {
						btn.classList.remove('is-loading');
						if (grid) {
							grid.style.opacity = '';
							grid.style.pointerEvents = '';
						}
						if (!res.success) return;

						// Replace grid tiles.
						if (grid) grid.innerHTML = res.data.html;

						// Update load-more state.
						wrap.setAttribute('data-next-token', res.data.next_token || '');
						wrap.setAttribute('data-loaded-count', res.data.count || '0');
						var lmBtn = wrap.querySelector('.yse-load-more-btn');
						if (lmBtn) {
							lmBtn.disabled = !res.data.next_token;
							lmBtn.closest('.yse-load-more-wrap').style.display = res.data.next_token ? '' : 'none';
						}

						// Tile clicks are delegated to the wrapper, so new tiles work
						// without re-binding. Only the thumbnail fallbacks need setup.
						if (grid) initThumbnails(grid);
					})
					.catch(function () {
						btn.classList.remove('is-loading');
						if (grid) { grid.style.opacity = ''; grid.style.pointerEvents = ''; }
					});
			});
		});
	}

	// ── Bootstrap ─────────────────────────────────────────────────────────
	function initWrap(wrap) {
		initThumbnails(wrap);
		initLightbox(wrap);

		var layout = wrap.getAttribute('data-layout');
		if (layout === 'featured') {
			initFeatured(wrap);
			// Featured does not use tile-click lightbox or load more.
		} else {
			initTileClicks(wrap);
			if (layout === 'grid') {
				initSortBar(wrap);
				initLoadMore(wrap);
			} else if (layout === 'wall') {
				initSortBar(wrap);
				initLoadMore(wrap);
			} else if (layout === 'slider') {
				initSlider(wrap);
			} else if (layout === 'tabs') {
				initTabs(wrap);
			}
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.yse-wrap').forEach(initWrap);
	});

	// Elementor live-editing support
	if (window.elementorFrontend) {
		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/yt_shorts_embed.default',
			function ($el) {
				var wrap = $el[0].querySelector('.yse-wrap');
				if (wrap) initWrap(wrap);
			}
		);
	}

})();
