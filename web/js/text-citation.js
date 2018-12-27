var Quotes = {
	btnOptions: {
		'id': 'citationUrlBtn',
		'title': 'Линк към маркирания пасаж',
		'class': 'btn',
		'style': 'display: block; bottom: 3px; right: 45px; opacity: 0.6; position: fixed; z-index: 100;',
		'placement': 'left'
	},
	selectorBefore: '#goto-top-link',
	$fireButton: null,
	$fakeA: null,

	init: function(createBtn, options, before) {
		if (!window.getSelection) { // no legacy browser support
			return;
		}
		// override defaults
		if (options) {
			if ('btnId' in options) this.btnOptions.id = options.btnId;
			if ('title' in options) this.btnOptions.title = options.title;
			if ('class' in options) this.btnOptions.class = options.class;
			if ('style' in options) this.btnOptions.style = options.style;
			if ('placement' in options) this.btnOptions.placement = options.placement;
		}
		if (before) this.selectorBefore = before;

		if (createBtn) {
			this.createButton();
		} else {
			this.$fireButton = $('#'+this.btnOptions.id);
		}

		if (0 === this.$fireButton.length) return;

		this.$fakeA = $('<a>', { href: window.location.href});

		this.$fireButton.popover({
			content: createCitationUrl,
			html: true,
			trigger: 'manual'
		})
			.click(function(e) {
				$(this).popover('toggle');

				// handle clicking on the popover itself
				$('.popover').off('click').on('click', function(e) {
					e.stopPropagation(); // prevent event for bubbling up => will not get caught with document.onclick
				});

				e.stopPropagation();
			});

		var self = this;
		$(document).click(function(e) {
			self.$fireButton.popover('hide');
		});
	},
	createButton: function() {
		this.$fireButton = $('<a>', {
			'id': this.btnOptions.id,
			'href': 'javascript:;',
			'title': this.btnOptions.title,
			'data-original-title': this.btnOptions.title,
			'data-placement': this.btnOptions.placement,
			'class': this.btnOptions.class,
			'style': this.btnOptions.style,
			'html': '<span class="fa fa-quote-left"></span><span class="sr-only">'+this.btnOptions.title+'</span>'
		});
		$(this.selectorBefore).before(this.$fireButton);
	},
	getSelectionParentElement: function() {
		var sel = window.getSelection();
		if (sel.rangeCount === 0) {
			return null;
		}
		/*
		* startContainer
startOffset: 277
endContainer
endOffset: 25
collapsed: false

0. show citation button when the user clicks in the #textcontent
	left to the paragraph
1. generate citation code
	?cite=p-X.00:p-Y.11
2. on page load - recognize citation code
	mark the corresponding pasage

		* */
		var range = sel.getRangeAt(0);
		console.log(range);
		console.log($(range.startContainer).closest('p'));
		console.log($(range.endContainer).closest('p'));
		var parentEl = range.startContainer;
		if (parentEl.nodeType === Node.TEXT_NODE) {
			return parentEl.parentNode;
		}
		return parentEl;
	},
	createUrl: function() {
		var hash = this.getSelectionParentElement().id;
		if (!hash || !hash.match('^p-')) {
			hash = 'textstart';
		}
		this.$fakeA.prop('hash', hash);
		return this.$fakeA.prop('href');
	}
};
var createCitationUrl = function() {
	var url = Quotes.createUrl();
	return '<input class="form-control" type="text" id="quoteUrl" value="'+ url +'" style="width: 330px;">';
};
$(function() {
	var properties = {
		btnId: 'fireQuoteBtn', // if you want to use not default values
		title: 'URL to marked area'
	};
	Quotes.init(true, properties, null);
});
