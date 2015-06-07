jQuery.scrollTo = function(elemOrPos, time) {
	time = time || 800;

	var pos = 0;
	switch ( typeof elemOrPos ) {
		case 'number': pos = elemOrPos; break;
		case 'object': pos = $(elemOrPos).offset().top; break;
		case 'string': pos = parseInt(elemOrPos); break;
	}

	//console.log("scrolling to " + pos);
	$("html,body").animate({scrollTop: pos}, time);
};

var _togShowText = "показване";
var _togHideText = "скриване";
/**
*	Toggle visibility of one or more elements’ content.
*	Leave first heading shown and attach a toggle controller to it.
*	Inspired by MediaWiki.
*	Ideas from http://www.stainlessvision.com/collapsible-box-jquery
*	Example usage: $("#toc").boxcollapse()
*/
jQuery.fn.boxcollapse = function(putLink) {
	var ret = this.each(function() {
		putLink = typeof putLink == "undefined" ? true : putLink;
		// find first heading
		var $heading = $( $(":header,legend", this)[0] );
		if ( ! $heading.length ) return;
		var $box = $(this);
		// all content which shall be toggled
		var $boxContents = $heading.siblings().not(".sr-only");
		if ($boxContents.length === 0) {
			$boxContents = $heading.parent().siblings().not(".sr-only");
		}
		var contentIsShown = true;
		var toggleContents = function(){
			$boxContents.toggle();
			$box.toggleClass("collapsed");
			contentIsShown = ! contentIsShown;
		}
		if (putLink) {
			var $toggleLink = $('<a/>')
				.text(_togHideText)
				.click(function() {
					$toggleLink.text( contentIsShown ? _togShowText : _togHideText );
					toggleContents();
					return false;
				});
			$('<span/>').addClass('collapsible-ctrl')
				.append($toggleLink)
				.appendTo($heading);
		} else {
			$heading.addClass('pseudolink').click(function(){
				toggleContents();
			});
			var $toggleLink = $heading;
		}
		if ( $box.is('.collapsed') ) {
			$toggleLink.click();
			$box.addClass('collapsed');
		}
	});
	// hiding content distorts the previous scroll location given by a hash
	if ( location.hash ) {
		location.href = location.hash;
	}
	return ret;
};

/**
*	Disable elements through setting the disabled attribute.
*	Should work only on form elements.
*/
jQuery.fn.disable = function() {
	return this.each(function() {
		$(this).attr('disabled', 'disabled');
	});
};

/** Show a loading indication for every element. */
jQuery.fn.loading = function() {
	return this.each(function() {
		$(this).addClass("loading");
	});
};

jQuery.fn.isLoading = function() {
	return $(this).is(".loading");
};

/** Remove loading indication for every element. */
jQuery.fn.loaded = function() {
	return this.each(function() {
		$(this).removeClass("loading");
	});
};


/**
	@param method The name of the function to call.
		Must be registered in ActionPage::$ajaxExportList
	@param data An array of arguments to that function
	@param callback A function which should be called by success
	@param type Type of the returned data
	@param extraArgs A string to be appended to URL
	@see jQuery.myurl
*/
jQuery.myget = function(method, data, callback, type, extraArgs) {
	return jQuery.get(jQuery.myurl(method, extraArgs), data, callback, type);
};

/** @see jQuery.myget */
jQuery.mypost = function(method, data, callback, type, extraArgs) {
	return jQuery.post(jQuery.myurl(method, extraArgs), data, callback, type);
};


/**
	@param method The name of the function to call.
		Can be of the form "method" or "action.method".
		If no action is given, the current one is used.
	@param extraArgs A string to be appended to URL
*/
jQuery.myurl = function(method, extraArgs) {
	var action = mgSettings.action;
	if ( method.indexOf(".") != -1 ) {
		var p = method.split(".");
		action = p[0];
		method = p[1];
	}
	url = /*mgSettings.server + */mgSettings.webroot + "?action=" + action + "&"
		+ mgSettings.varAjaxFunc + "=" + encodeURIComponent(method);
	if ( typeof extraArgs != "undefined" ) {
		url += "&" + extraArgs;
	}
	return url;
};
