$("#book-content:not(.book-type-single)").on('click', 'a.textlink', function(event) {
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
	var $target = $self.closest(':header');
	$.get(this.href, function(textContent) {
		$self.loaded();
		$self.data('content-loaded', true);
		$('<div class="panel panel-default"></div>').html(textContent).insertAfter($target).boxcollapse(false);
		$('html').animate({scrollTop: $target.offset().top}, 800);
	});
	return false;
});
