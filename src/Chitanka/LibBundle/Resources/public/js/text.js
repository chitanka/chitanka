function prepareGamebook()
{
	if ( ! $("div.gamebook").length ) {
		return;
	}
	var $epHeader = $("h2:contains('Епизоди')");
	if ( ! $epHeader.length ) {
		return;
	}
	var driver = {
		$eps   : $epHeader.siblings(),
		lastEp : 0,
		reveal : function(ep) {
			if (this.lastEp) {
				this.hide(this.lastEp);
			}
			this.show(ep);
			this.lastEp = ep;
			return this;
		},
		show    : function(ep) { this.$eps.eq(ep - 1).show(); },
		hide    : function(ep) { this.$eps.eq(ep - 1).hide(); },
		showAll : function()   { this.$eps.show(); return this; },
		hideAll : function()   { this.$eps.hide(); return this; }
	};
	var $help = $('<div class="notice" style="margin: 15em auto 45em">\
		<hr/>\
		<p>Неактивните епизоди бяха скрити. Ще се покажат в хода на играта.</p>\
		<div class="standalone"><a href="#l-1">Показване на всички епизоди</a></div>\
		<hr/>\
		</div>');
	$help.find("a").click(function() {
		driver.showAll();
		$help.hide();
		$epHeader.parent().unbind("click");
	});
	$epHeader.parent().append($help);
	$epHeader.parent().parent().click(function(e) {
		if ( $(e.target).is(".ep") ) {
			driver.reveal( $(e.target).text() );
		}
	});

	$epHeader.hide();
	driver.hideAll();
}

// goto next chapter links
$(document.body).on("click", "a[rel=next]", function(){
	if ( $(this).isLoading() ) {
		return false;
	}
	$(this).loading();
	$.getJSON((mgSettings.mirror || mgSettings.webroot) + "?jsoncallback=?",
	{
		"ajaxFunc" : "getTextPartContent",
		"action" : mgSettings.action,
		"textId" : mgSettings.textId,
		"chunkId" : mgSettings.nextChunkId,
		"sfbObjCount" : mgSettings.nextChunkId + 2, // anno + info
		"isAnon" : mgSettings.isAnon
	}, function(data){
		$("#textstart").remove();
		$("#text-end-msg").replaceWith(data.text);
		$("#toc").replaceWith(data.toc);
		$("html").animate({scrollTop: $("#textstart").offset().top}, 800);
		mgSettings.nextChunkId += 1;
		prepareGamebook();
	});
	return false;
});
// mark as read link
$("button.ok").click(function(){
	return markRead(this);
});

if ( ! location.hash ) {
	$(document.body).on("click", "a.goto-toc", function(){
		$("#toc .collapsible-ctrl a").click(); // uncollapse toc
		//$("html").animate({scrollTop: toc.offset().top}, 800);
	});
}

// if user is at the bottom of the window, show next chapter
// $(window).scroll(function(){
// 	if ($(window).scrollTop() == $(document).height() - $(window).height()){
// 		$("#text-end-msg a[rel=next]").click();
// 	}
// });

(function() {
	prepareGamebook();
})();
