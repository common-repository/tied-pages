function isGutenberg() {
    return !!(window.wp && wp.data && wp.data.select && wp.data.select('core/editor'));
}

function scrollToEvent($ele, scrollTopVal) {
	scrollTopVal = scrollTopVal || 0;
	
	var $scrollWrapper = jQuery('body,html');
	var $scrollOffset = 20 + jQuery('#wpadminbar').height();
	
	if ( isGutenberg() ) {
		$scrollWrapper = jQuery(".interface-interface-skeleton__content");
		$scrollOffset = 20 + jQuery('.interface-interface-skeleton__header').height();
	}

	if ($ele != null && $ele.length > 0) {
		$scrollWrapper.animate({
			scrollTop: ($ele.offset().top - $scrollOffset)
		}, 300);
	} else {
		if (scrollTopVal != null) {
			$scrollWrapper.animate({
				scrollTop: scrollTopVal
			}, 300);
		}
	}
}