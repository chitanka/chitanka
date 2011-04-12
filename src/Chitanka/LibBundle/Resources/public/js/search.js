$("input.search").each(function(){
	var def = "Търсене на…";

	$(this).bind("focus", function(){
		this.value == def && $(this).removeClass("helpinput").val("");
	}).bind("blur", function(){
		this.value === "" && $(this).addClass("helpinput").val(def);
	}).trigger("blur");
});
