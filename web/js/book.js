$("#book-content").on('click', 'a.one-part-text', function(event) {
	if (event.ctrlKey || event.shiftKey || event.altKey) {
		// default behaviour
		return true;
	}
	var $self = $(this);
	if ($self.data('content-loaded')) {
		// content was already loaded; return to default behaviour
		return true;
	}
	if ($self.isLoading()) {
		// content is being currently loaded, so do not load it again
		return false;
	}
	$self.loading();
	var $target = $self.closest(':header:not(.text-title)');
	if ($target.length === 0) {
		$target = $self.closest('.text-entity');
	}
	if ($target.length === 0) {
		$target = $self;
	}
	$.get(this.href, function(textContent) {
		$self.loaded();
		$self.data('content-loaded', true);
		$('<div class="panel panel-default"></div>').html(textContent).insertAfter($target).boxcollapse(false);
		$.scrollTo($target);
	});
	return false;
});
