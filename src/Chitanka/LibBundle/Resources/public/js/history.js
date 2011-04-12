// visibility modes of the authors
var _hauthors = [];

function toggle_texts( author, anchor ) {
	var _textsId = "texts-" + author;
	var textsId = "#" + _textsId;

	if ( inArray( author, _hauthors ) ) {
		$(textsId).toggle();
		return false;
	}

	_hauthors.push( author );

	var authorId = "#author-" + author;
	var t = $("<div/>").attr("id", _textsId).addClass("texts")
		.text("Зарежда се…")
		.appendTo(authorId);
	var timeout_int = 0.5; // in seconds
	function show_progress() {
		t.append(".");
		timeoutId = window.setTimeout( show_progress, timeout_int * 1000 );
	}
	var timeoutId = window.setTimeout( show_progress, timeout_int * 1000 );

	var responseHandler = function( response ) {
		$.event.trigger("ajaxStop");
		t.empty().append(response);
		location.href = authorId;
		window.clearTimeout(timeoutId);
		initTableSorter(textsId + " table");
	};
	showHideAllTextsLink();
	return ! $.mypost(
		"history.getTextsByAuthor",
		{"a" : author},
		responseHandler,
		null,
		'_ser=' + hist_url_params);
}


var showHideAllTextsLink = (function() {
	var hasLink = false;

	return (function() {
		if ( hasLink ) {
			return;
		}
		$('<a href="#"></a>')
			.text("Скриване на показаните произведения")
			.click(function() {
				$("div.texts").hide();
				return false;
			})
			.appendTo("<li/>")
			.appendTo("#control-links");
		hasLink = true;
	});
})();
