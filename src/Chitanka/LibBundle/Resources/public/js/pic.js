
$(document).ready(function() {
	$("div.gal-navigation").css({"width": "200px", "float": "left"});
	$("div.gal-content").css({display: "block"});


	var gallery = $('#gallery').galleriffic('#comics', {
		//delay:                  3000,
		numThumbs:              12,
		preloadAhead:           10,
		//enableTopPager:         false,
		//enableBottomPager:      true,
		imageContainerSel:      '#com-slideshow',
		controlsContainerSel:   '#com-controls',
		//captionContainerSel:    '',
		loadingContainerSel:    '#com-loading',
		renderSSControls:       false,
		//renderNavControls:      true,
		//playLinkText:           'Play',
		//pauseLinkText:          'Pause',
		prevLinkText:         '&larr; Назад',
		nextLinkText:         'Напред &rarr;',
		nextPageLinkText:     '&rsaquo;',
		prevPageLinkText:     '&lsaquo;',
		hashPrefix:           'page',
		enableHistory:         true,
		pagerContext:          2,
		//autoStart:              false,
		/*onChange:              function(prevIndex, nextIndex) {
			$('#thumbs ul.thumbs').children()
				.eq(prevIndex).fadeTo('fast', onMouseOutOpacity).end()
				.eq(nextIndex).fadeTo('fast', 1.0);
		},*/
		/*onTransitionOut:        function(callback) {
			if ( ! gallery || ! gallery.isFullScreen() ) {
				$('#com-slideshow').fadeOut('fast', callback);
			}
		},
		onTransitionIn:         function() {
			if ( ! gallery || ! gallery.isFullScreen() ) {
				$('#com-slideshow').fadeIn('fast');
			}
		},*/
		onPageTransitionOut:    function(callback) {
			$('#comics ul.thumbs').fadeOut('fast', callback);
		},
		onPageTransitionIn:     function() {
			$('#comics ul.thumbs').fadeIn('fast');
		}
	});

	$(document).bind('keydown', 'f', function(){ gallery.toggleFullScreen(); });
	$(document).bind('keydown', 'j', function(){ gallery.goBack();  });
	$(document).bind('keydown', 'k', function(){ gallery.goAhead(); });
	$(document).bind('keydown', 'ctrl+left', function(){ gallery.goBack();  });
	$(document).bind('keydown', 'ctrl+right', function(){ gallery.goAhead(); });
});
