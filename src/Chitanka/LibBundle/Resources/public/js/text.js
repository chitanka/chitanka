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
		allShown: false,
		reveal: function(ep) {
			if (!this.allShown) {
				if (this.lastEp) {
					this.hide(this.lastEp);
				}
				this.show(ep);
			}
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
			this.allShown = true;
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
	});
	$help.find('[name="ep-goto"]').on("change", function() {
		driver.reveal(this.value);
		$(this).val("").blur();
	});
	$epHeader.parent().append($help);
	$container.on("click", "a", function() {
		var match = $(this).attr("href").match(/#l-(\d+)$/);
		if (match) {
			driver.reveal(match[1]);
		} else {
			$(".back-to-ep").remove();
			var edge = $("#main-content").css("margin-left") == "0px" ? "right" : "left";
			var linkText = driver.lastEp == 0 ? 'Назад' : 'Назад към епизода';
			$backToEpLink = $('<a class="back-to-ep" style="position:fixed; bottom:2em">'+linkText+'</a>')
				.attr("href", "#"+driver.epId(driver.lastEp))
				.css(edge, "1em")
				.appendTo("body")
				.click(function() {
					$(this).remove();
					history.go(-1);
					return false;
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

	var InputStorage = function(storageKey) {
		storageKey = storageKey || "gamebook-input";
		this.container = localStorage[storageKey] ? JSON.parse(localStorage[storageKey]) : {};

		var persist = function(container) {
			localStorage[storageKey] = JSON.stringify(container);
		};
		this.get = function(key) {
			return this.container[key];
		};
		this.set = function(key, value) {
			this.container[key] = value;
			persist(this.container);
		};
		this.has = function(key) {
			return this.container[key];
		};
		this.clear = function(key) {
			this.container[key] && delete this.container[key];
			persist(this.container);
		};
		this.clearAll = function() {
			this.container = {};
			persist(this.container);
		};
	};
	var storage = new InputStorage;

	function replaceInputPlaceholders(str, name) {
		return str
			// … x3
			.replace(/………/g, '<textarea style="width: 99%; height: 5em"></textarea>')
			// (…=radio 1 / radio 2 / radio 3)
			.replace(/\(…=([^)]+)\)/, function(m0, m1) {
				var elems = [];
				m1.split(" / ").forEach(function(value, i) {
					elems.push('<label><input type="radio" name="r-'+name+'" value="'+i+'">'+value+'</label>');
				});
				return elems.join(" ");
			})
			// […=checkbox 1 / checkbox 2 / checkbox 3]
			.replace(/\[…=([^\]]+)\]/, function(m0, m1) {
				var elems = [];
				m1.split(" / ").forEach(function(value, i) {
					elems.push('<label><input type="checkbox" name="c-'+name+'-'+i+'">'+value+'</label>');
				});
				return elems.join(" ");
			})
			// …(=initial value)
			.replace(/…\(=([^)]+)\)/g, '<input type="text" style="width: 98%" value="$1">')
			// …(placeholder value)
			.replace(/…\(([^)]+)\)/g, '<input type="text" style="width: 99%" placeholder="$1">')
			.replace(/…/g, '<input type="text" style="width: 99%">');
	}

	var enhanceInputCell = function(idx, cell) {
		var $cell = $(cell);
		var name = namePrefix + "-" + idx;
		$cell.html(replaceInputPlaceholders($cell.html(), name));
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
			if (this.type == "checkbox" && !$(this).prop("checked")) {
				storage.clear(this.name);
				return;
			}
			storage.set(this.name, this.value);
		});
		$cell.find(":input").each(function() {
			if (!storage.has(this.name)) {
				return;
			}
			switch (this.type) {
				case "radio":
					if (this.value == storage.get(this.name)) {
						$(this).prop("checked", true);
					}
					break;
				case "checkbox":
					$(this).prop("checked", true);
					break;
				default:
					$(this).val(storage.get(this.name));
			}
		});
	};
	var $inputContainer = $(".js-gamebook-input");
	$inputContainer
		.wrap('<form></form>')
		.append('<div style="text-align:right"><input type="reset" value="Зануляване на записите"></div>')
		.closest("form").on("reset", function() {
			storage.clearAll();
		});
	var $inputTables = $inputContainer.find("table");
	$inputTables.css({ width: "100%" });
	var $inputCells = $inputTables.find("td");
	var namePrefix = location.pathname;
	$inputCells.each(enhanceInputCell);


	$(".js-gamebook-board").each(function() {
		var width = 20;
		var offset = width/2;
		var top = $(this).offset().top;
		var left = $(this).offset().left;
		var $dot = $('<div style="background-color:red"></div>')
			.css({
				position: "absolute",
				top: top,
				left: left,
				width: width+"px",
				height: width+"px",
				"border-radius": (width/2)+"px"
			})
			.appendTo('body');
		$(this).click(function(event) {
			top = event.pageY - offset;
			left = event.pageX - offset;
			$dot.css({ top: top, left: left });
			return false;
		})
		.attr("title", "Щракнете, за да сложите или преместите пионката")
		.find("img").removeAttr("title");
	});
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
