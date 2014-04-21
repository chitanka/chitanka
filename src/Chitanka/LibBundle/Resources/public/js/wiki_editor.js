(function(window, $) {

window.loadWikiEditor = function($editable, $heading, options) {
	var $sourcebox = $editable.next(".markdown-source");
	if ($sourcebox.length == 0) {
		$sourcebox = $('<textarea class="markdown-source"></textarea>').hide().insertAfter($editable);
	}

	var getSource = function($editable) {
		if (options.format != "md") {
			return $.trim($editable.html()) + "\n";
		}
		var reMarker = new reMarked({
			link_list: false,
			h1_setext: false,
			h2_setext: false
		});
		return reMarker.render($editable[0]) + "\n";
	};

	var savePage = function(summary, button) {
		var params = {
			page: options.page +"."+ options.format,
			title: $heading.text(),
			content: $sourcebox.val(),
			summary: summary
		};
		var $button = $(button).addClass("loading");
		$.post(options.wiki_save_path, params, function(response) {
			$button.prop("disabled", true);
		})
		.always(function() {
			$button.removeClass("loading");
		});
		return false;
	};

	var toggleSourceView = function(button) {
		if ($sourcebox.is(":visible")) {
			if (options.format == "md") {
				$editable.css({ opacity: 0.3 });
				$.post(options.wiki_preview_path, {content: $sourcebox.val()}, function(response) {
					$editable.html(response).css({ opacity: 1 });
				});
			} else {
				$editable.html($sourcebox.val());
			}
		} else {
			if ($.trim($sourcebox.val()) == "") {
				var source = getSource($editable);
				$sourcebox.val(source);
			}
			if ($sourcebox.val().match(/[| -]{120}/)) { // wide tables
				$sourcebox.attr("wrap", "off");
			} else {
				$sourcebox.removeAttr("wrap");
			}
		}
		$editable.toggle();
		$sourcebox.toggle();
		$(button).find(".fa").toggleClass("fa-code fa-eye");
	};

	$editable.hallo({
		plugins: {
			//halloformat: {},
			//halloheadings: { formatBlocks: ["p", "h1", "h2", "h3", "h4"] },
			//hallolists: {},
			//hallolink: {},
			//halloreundo: {},
			halloextrabuttons: {
				position: 'right',
				id: 'wikiButton',
				buttons: [{
					action: function(event, button) {
						var summary = prompt("Кратко описание на промените");
						if (summary) {
							savePage(summary, button);
						}
					},
					icon: 'save',
					label: 'Запис',
					disabled: false,
					id: 'wikiSave'
				}, {
					action: function(event, button) {
						toggleSourceView(button);
					},
					icon: 'code',
					label: 'Изходен код / Преглед',
					id: 'wikiSourceToggle'
				}, {
					action: function(event, button, toolbar, widget) {
						widget.element.hallo({editable: false}).blur();
						toolbar.hide();
						$heading.attr("contenteditable", false);
					},
					icon: 'times',
					label: 'Основен режим на преглед'
				}]
			}
		},
		editable: true,
		toolbar: 'halloToolbarFixed'
	}).focus().off("hallodeactivated");

	$heading.attr("contenteditable", true);

	$editable.on('hallomodified', function(event, data) {
		$("#wikiSave").prop("disabled", false);
	});

	$("#wikiSourceToggle").click();
};

})(window, jQuery);
