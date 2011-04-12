// goto next chapter links
$("a.one-part-text").click(function(){
	if ( $(this).isLoading() ) {
		return false;
	}
	$(this).loading();
	var textId = getTextIdFromLink( $(this) );
	if ( ! textId ) {
		return false;
	}
	var cont = $(this).parent();
	$.getJSON((mgSettings.mirror || mgSettings.webroot) + "?jsoncallback=?",
	{
		"ajaxFunc" : "getTextPartContent",
		"action" : "text",
		"textId" : textId,
		//"chunkId" : mgSettings.nextChunkId,
		"sfbObjCount" : textId,
		"isAnon" : mgSettings.isAnon
	}, function(data){
		$("#textstart").remove();
		cont.after( "<li>" + data.text + "</li>" );
		$("html").animate({scrollTop: cont.offset().top}, 800);
	});
	return false;
});
// mark as read link
$("a.ok").live("click", function(){
	return markRead( false, this );
});
