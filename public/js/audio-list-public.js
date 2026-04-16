(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(() => {  // show/hide preview video players in iframes
		const checkboxes = document.querySelectorAll('ul.audio-list input.video-players-switcher');
		checkboxes.forEach(checkbox => {
	    checkbox.addEventListener('change', (event) => {
	    	const parentUl = event.currentTarget.closest('ul.audio-list');
	    	const childrenVideoPlayers = parentUl.querySelectorAll('div.youtube-item');
	      childrenVideoPlayers.forEach(childrenVideoPlayer => {
	      	childrenVideoPlayer.style.display = event.currentTarget.checked ? 'block' : 'none';
	      });
	    });
		});

		// YouTube Lazy Loading with Intersection Observer
		// Auto-loads YouTube iframe when scrolling into view

		const youtubeObserver = new IntersectionObserver(entries => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					const item = entry.target;
					const youtubeId = item && item.dataset && item.dataset.youtubeId;

					// Check if iframe not already loaded
					if (youtubeId && !item.querySelector('iframe')) {
						// Create and insert iframe
						const iframe = document.createElement('iframe');
						iframe.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;';
						iframe.src = `https://www.youtube.com/embed/${youtubeId}`;
						iframe.setAttribute('frameborder', '0');
						iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
						iframe.allowFullscreen = true;

						// Remove placeholder
						const placeholder = item.querySelector('.youtube-placeholder');
						if (placeholder) {
							placeholder.remove();
						}

						// Append iframe
						item.appendChild(iframe);
					}
					// Stop observing once loaded
					youtubeObserver.unobserve(item);
				}
			});
		}, {
			rootMargin: "300px" // Start loading 300px before visible (earlier than PDF for smooth playback)
		});

		// Observe all YouTube items
		document.querySelectorAll('.youtube-item').forEach(item => {
			youtubeObserver.observe(item);
		});
	});

})( jQuery );
