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

	var enhanceChanceTable = function(idx, table) {
		var $table = $(table);
		$table.find("td").css({ padding: "1em 2em", "text-align": "center" });
		var $cells = $table.find("td");
		var $handle = $('<button style="display: block; margin: .5em auto">Случайно число</button>').click(function() {
			var randIdx = Math.floor(Math.random() * $cells.length);
			$cells.removeClass("hilite").eq(randIdx).addClass("hilite");
			$(this).blur();
		}).appendTo($table.find("caption")[0] || $('<caption/>').appendTo($table));
	};
	$(".js-chance-table table").each(enhanceChanceTable);

	var enhanceInputCell = function(idx, cell) {
		var $cell = $(cell);
		var name = namePrefix + "-" + idx;
		var htmlInput = $cell.text().replace(/…+/, '<input type="text" name="'+name+'" style="width: 100%">');
		$cell.html(htmlInput);
		$cell.on("change", ":input", function() {
			localStorage[this.name] = this.value;
		});
		$cell.find(":input").each(function() {
			if (localStorage[this.name]) {
				$(this).val(localStorage[this.name]);
			}
		});
	};
	var $inputTables = $(".js-gamebook-input").find("table");
	$inputTables.css({ width: "100%" });
	var $inputCells = $inputTables.find("td");
	var namePrefix = location.pathname;
	$inputCells.each(enhanceInputCell);

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
