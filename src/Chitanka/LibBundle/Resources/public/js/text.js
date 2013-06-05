function prepareGamebook()
{
	var $container = $("div.gamebook");
	if ($container.length === 0) {
		return;
	}
	var $epHeader = $container.find("h2:contains('Епизоди')");
	if ($epHeader.length === 0) {
		return;
	}
	var driver = {
		$eps: $epHeader.parent().find(".section"),
		ep: function(ep) {
			return $("#"+this.epId(ep));
		},
		lastEp: 0,
		reveal: function(ep) {
			if (this.lastEp) {
				this.hide(this.lastEp);
			}
			this.show(ep);
			this.lastEp = ep;
			return this;
		},
		show: function(ep) {
			var epElm = this.ep(ep).show();
			var ancestor = epElm.parent();
			while (ancestor.is(":hidden")) {
				ancestor.show();
				ancestor = ancestor.parent();
			}
			var target = epElm.attr("id");
			if (location.hash !== target) {
				location.hash = target;
			}
		},
		hide: function(ep) {
			this.ep(ep).hide().parents(".section").hide();
		},
		showAll: function() {
			this.$eps.show();
			return this;
		},
		hideAll: function() {
			this.$eps.hide();
			return this;
		},
		epId: function(ep) {
			return "l-"+ep;
		}
	};
	var $help = $('<div class="notice" style="margin: 15em auto 45em">\
		<hr/>\
		<p>Неактивните епизоди бяха скрити. Ще се покажат в хода на играта.</p>\
		<div class="standalone"><a href="#l-1" class="ep-all">Показване на всички епизоди</a></div>\
		<div class="standalone">Отиване на епизод <input type="text" name="ep-goto" size="4"></div>\
		<hr/>\
		</div>');
	$help.find(".ep-all").click(function() {
		driver.showAll();
		$help.hide();
		$container.unbind("click");
	});
	$help.find('[name="ep-goto"]').on("change", function() {
		driver.reveal(this.value);
		$(this).val("").blur();
	});
	$epHeader.parent().append($help);
	$container.on("click", "a", function() {
		var match = $(this).attr("href").match(/#l-(\d+)/);
		if (match) {
			driver.reveal(match[1]);
		} else {
			$(".back-to-ep").remove();
			var edge = $("#main-content").css("margin-left") == "0px" ? "right" : "left";
			$backToEpLink = $('<a class="back-to-ep" style="position:fixed; top:3em">Назад към епизода</a>')
				.css(edge, "1em")
				.attr("href", "#"+driver.epId(driver.lastEp))
				.appendTo("body")
				.click(function(){
					$(this).remove();
				});
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
		var htmlInput = $cell.text()
			.replace(/………/g, '<textarea style="width: 99%; height: 5em"></textarea>')
			.replace(/…+(\(([^)]+)\))?/g, '<input type="text" style="width: 99%" placeholder="$2">');
		$cell.html(htmlInput);
		var childrenCount = $cell.children().length;
		if ($.trim($cell.text()) !== "") {
			childrenCount++;
		}
		if (childrenCount > 1) {
			var childrenWidth = Math.floor(100 / childrenCount) - 2/*give it some space*/;
			$cell.children().width(childrenWidth+"%");
		}
		$cell.children().each(function(idx, input) {
			$(input).attr("name", name+"-"+idx);
		});
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
