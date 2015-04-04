function showErrorToUser(response) {
	var message = "Възникнала е грешка: " + response.statusText + " (" + response.status + ")";
	switch (response.status) {
		case 401: message = "Нямате необходимите права за това действие."; break;
	}
	alert(message);
}

function showNewForm(link) {
	var $link = $(link).addClass("loading");

	$.get(link.href)
	.done(function(data) {
		$link.before(data).prev("form").find(":input:visible:eq(0)").focus();
	})
	.fail(function(response) {
		showErrorToUser(response);
	})
	.always(function() {
		$link.removeClass("loading");
	});
}

function showEditForm(link) {
	$(link).addClass("loading");

	var match = link.href.match(/edit\/(.+)/);
	if (match === null) {
		alert(link.href + " is an invalid edit link.");

		return;
	}

	$.get(link.href, function(data){
		var sel = $("#"+match[1].replace(/\//g, "_"));
		$(sel).hide().after(data).next("form").find(":input:visible:eq(0)").focus();
		$(link).removeClass("loading");
	});
}


function submitForm(form) {
	var $form = $(form);
	var $button = $form.find(":submit").addClass("loading");
	$.post(form.action, $form.serialize(), function(response) {
		if (typeof response == "string") {
			$form.replaceWith(response);
		} else {
			if (response.addClass) {
				$button.addClass(response.addClass);
			} else if (response.removeClass) {
				$button.removeClass(response.removeClass);
			}
			if (response.setTitle) {
				$button.attr("title", response.setTitle);
			}
		}
	}, "json")
	.fail(function(response) {
		showErrorToUser(response);
	})
	.always(function() {
		$button.removeClass("loading").blur();
	});
}

function submitNewForm(form) {
	var $form = $(form);
	var $button = $form.find(":submit").addClass("loading");
	$.post(form.action, $form.serialize(), function(data) {
		$form.prev().append(data).end().remove();
	})
	.fail(function(response) {
		showErrorToUser(response);
	})
	.always(function() {
		$button.removeClass("loading");
	});
}

function submitEditForm(form) {
	var $form = $(form);
	var $button = $form.find(":submit").addClass("loading");
	$.post(form.action, $form.serialize(), function(data) {
		$form.prev().html(data).show().end().remove();
	})
	.fail(function(response) {
		showErrorToUser(response);
	})
	.always(function() {
		$button.removeClass("loading");
	});
}

function submitDeleteForm(form) {
	var $form = $(form);
	var $button = $form.find(":submit").addClass("loading");
	$.ajax({
		url: form.action,
		type: "DELETE",
		data: $form.serialize(),
	})
	.done(function(data) {
		var $parent = $form.closest(".deletable");
		if ( ! $parent.length) {
			$parent = $form.parent();
		}
		$parent.remove();
	})
	.fail(function(response) {
		showErrorToUser(response);
	})
	.always(function() {
		$button.removeClass("loading");
	});
}


function enhanceModifying()
{
	$("body")
		.delegate("a.action-new", "click", function(){
			if (user.canTakeAction(this)) {
				showNewForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("a.action-edit", "click", function(){
			if (user.canTakeAction(this)) {
				showEditForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("a[data-edit]", "click", function(event){
			if (event.altKey || event.shiftKey) {
				window.open($(this).data("edit"));
				return false;
			}
			return true;
		})
		.delegate("form.new-form", "submit", function(){
			if (user.canTakeAction(this)) {
				submitNewForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("form.edit-form", "submit", function(){
			if (user.canTakeAction(this)) {
				submitEditForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("form.delete-form", "submit", function(){
			if (user.canTakeAction(this)) {
				if (confirm("Наистина ли да се изтрие? Връщане назад няма.")) {
					submitDeleteForm(this);
				}
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("form.action-form", "submit", function(){
			if (user.canTakeAction(this)) {
				submitForm(this);
			} else {
				alert("Нямате необходимите права за това действие.");
			}

			return false;
		})
		.delegate("button.cancel", "click", function(){
			$(this.form).prev().show().end().remove();
		});

}


function showBookmarks()
{
	var $texts = $(".text-entity");
	if ($texts.length) {
		var textIds = $texts.map(function(){ return $(this).data("id") }).get().join(",");
		$.post(_GLOBALS.scriptname + "user-special-texts", {texts: textIds}, function(response){
			$texts.each(function(){
				var id = $(this).data("id");
				if (typeof response.read[id] == "number") {
					$("a.textlink", this).addClass("read");
				}
				if (typeof response.favorities[id] == "number") {
					var newText = "Премахване от Избрани";
					$("form.bookmark-form button", this)
						.addClass("active")
						.attr("title", newText);
;
				}
			});
		}, "json");
	}
}

// create a menu toggle link
(function(){
	var togglerCookieName = "nomenu";
	var $sidebar = $("#navigation");
	if ($sidebar.find(".sidebar-menu").length === 0) {
		return;
	}
	var togglerHtml = '<div>\
		<a href="#hide-sidebar" class="sidebar-toggle hide-toggle fa-stack" title="Скриване на менюто">\
			<span class="fa fa-bars fa-stack-1x"></span>\
			<span class="fa fa-ban fa-stack-2x text-warning"></span>\
		</a>\
		<a href="#'+$sidebar.attr('id')+'" class="sidebar-toggle show-toggle hide fa fa-bars" title="Показване на менюто"></a>\
		</div>';
	$(togglerHtml).on("click", "a", function() {
		if ($sidebar.css("float") == "none") { // sidebar is at the bottom of the page
			$.scrollTo($sidebar, 500);
			return false;
		}
		$sidebar.toggle();
		$("#main-content").toggleClass("no-sidebar");
		$(this).siblings().addBack().toggleClass("hide");
		$(this).blur();
		$.cookie(togglerCookieName, $sidebar.is(":visible") ? null : 1, {path: '/'});
		return false;
	})
	.appendTo("#content-wrapper");

	if ($.cookie(togglerCookieName) && $sidebar.css("float") != "none") {
		$(".hide-toggle").click();
	}
})();


function registerBookClueLinks() {
	$(document).on('click', '.book-clue', function() {
		var elm = $(this);
		if (elm.is('loading')) {
			return;
		}
		var href = elm.data('href');
		if (!href) {
			elm.popover('toggle');
			return;
		}
		elm.addClass('loading');
		$.get(href, function(clueContent) {
			elm.removeClass('loading').data('href', '');
			elm.popover({
				html: true,
				placement: 'auto',
				trigger: 'manual',
				content: clueContent
			}).popover('show');
		});
	});
}

function registerGotoTopLink() {
	var $gotoTopLink = $("#goto-top-link");
	$gotoTopLink.click(function() {
		$.scrollTo(0, 500);
		$(this).blur();
		return false;
	});
	var $window = $(window);
	$window.scroll(function() {
		if ($window.scrollTop() > $window.height()) {
			$gotoTopLink.is(':hidden') && $gotoTopLink.fadeIn();
		} else {
			$gotoTopLink.is(':visible') && $gotoTopLink.fadeOut();
		}
	});
}

function activateTabByHash(hash) {
	var activeTab = $('[href=' + hash + ']:first');
	activeTab && activeTab.tab('show');
}

$(function(){
	$('#user-tools,#search').appendTo('#main-content-wrapper');

	if (user.isAuthenticated()) {
		showBookmarks();
	}

	$(".popover-trigger").popover({
		html: true,
		placement: 'auto',
		trigger: 'hover'
	});

	registerBookClueLinks();

	enhanceModifying();

	$(".toc").boxcollapse();

	registerGotoTopLink();

	if (location.hash) {
		activateTabByHash(location.hash);
	}
});


/**
	Initialise table sorter on all tables marked as sortable.
	@param table Optional table selector
*/
function initTableSorter(options) {
	if ( ! $.tablesorter ) {
		return;
	}
	$(document).ready(function() {
		$.tablesorter.defaults.widgets = ['zebra'];
		$.tablesorter.defaults.decimal = [','];
		// TODO sorting of KiB
		$("table.sortable").tablesorter(options);
	});
}

/**
	@global postform
	@param cid Comment ID
*/
function initReply(cid) {
	var target = "#postform";
	var postform = $(target);
	if ( ! postform.length ) {
		return true;
	}
	postform.show();
	postform[0].replyto.value = cid;
	if (cid > 0) {
		target = "#replyto" + cid;
		$(target).append(postform);
	}
	location.href = target;
	return false;
}


function getTextIdFromLink( link ) {
	var matches = $(link).attr("class").match(/text-(\d+)/);
	if ( ! matches ) {
		return false;
	}
	return matches[1];
}

/** Checks if a value exists in an array */
function inArray( val, arr ) {
	return jQuery.inArray( val, arr ) != -1;
}


/*
	Miscellaneous AJAX functions
*/

/**
* Rate a text
* @param form ID of the text being rated
*/
function saveRating(form) {
	if ( ! user.isAuthenticated()) {
		alert("Само регистрирани потребители могат да дават оценки.");

		return false;
	}
	$form = $(form);
	$(".indicator", $form).addClass("loading");
	return ! $.post(form.action, $form.serialize(), function(newForm){
		$form.replaceWith(newForm);
	});
}


/** Log-in form */
var showLoginForm = (function() {
	var isShown = false;
	var responseHandler = null;

	return (function( anchor ) {
		if ( isShown ) {
			return false;
		}

		if ( ! responseHandler ) {
			responseHandler = function( response ) {
				$.event.trigger("ajaxStop");
				$("<div/>")
					.addClass("ajax-response-top")
					.append(response)
					.prependTo("body");
				isShown = true;
			};
		}

		return ! $.mypost(
			"login.makeLoginForm",
			{"returnto" : location.href},
			responseHandler);
	});
})();


/** Comment preview */
var makePreview = (function() {
	var box = null;
	var responseHandler = null;

	return (function( form, comment, reader ) {
		if ( ! box ) {
			box = $("<div/>").attr("id", "previewed").insertAfter(form);
			responseHandler = function( response ) {
				$.event.trigger("ajaxStop");
				box.empty().html(response);
				location.href = "#previewed";
			};
		}

		return ! $.mypost(
			"makePreview",
			{"commenttext" : comment, "reader" : reader},
			responseHandler);
	});
})();


var user = {
	isAuthenticated: function() {
		return $(".user-profile").length > 0;
	},

	canTakeAction: function(handle) {
		var credentials = $(handle).data("credentials");
		if ( ! credentials) {
			return this.isAuthenticated();
		}

		return $.inArray(credentials, userGroups) != -1 || $.inArray("god", userGroups) != -1;
	}
};
