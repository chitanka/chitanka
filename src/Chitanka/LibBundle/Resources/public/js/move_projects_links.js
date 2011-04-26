user_functions.push(function(){
	$("#project-links")
		.removeClass("project-links")
		.addClass("navbody")
		.find("ul").addClass("textmenu").end()
		.appendTo("#nav-main");
});
