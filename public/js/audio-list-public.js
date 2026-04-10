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

	$(function() {
		// PDF Lazy Loading with Intersection Observer
		// Auto-loads PDF when scrolling into view
		
		const observer = new IntersectionObserver(entries => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					const item = entry.target;
					const iframe = item.querySelector('.pdf-frame');
					const src = item.dataset.src;

					if (src && !iframe.src) {
						iframe.src = src;
					}
					// Stop observing once loaded
					observer.unobserve(item);
				}
			});
		}, {
			rootMargin: "200px" // Start loading 200px before visible
		});

		// Observe all PDF items
		document.querySelectorAll('.pdf-item').forEach(item => {
			observer.observe(item);
		});
	});

})( jQuery );
