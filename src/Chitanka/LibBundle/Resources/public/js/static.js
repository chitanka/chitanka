var actionNames = {
	"author" : "Автор",
	"translator" : "Преводач",
	"text" : "Заглавие",
	"series" : "Серия",
	"book" : "Книга",
	"label" : "Етикет"
};
$(function(){
	var defSearchVal = "Търсене на…";
	var accesskey = "Т";
	var $search = $('<input type="text" id="q" tabindex="0" class="search"/>')
		.attr({
			"value"     : defSearchVal,
			"accesskey" : accesskey,
			"title"     : "Клавиш за достъп — " + accesskey
		})
		.focus(function(){
			if (this.value == defSearchVal) {this.value = ""}
		})
		.blur(function(){
			if (this.value == "") {this.value = defSearchVal}
		})
		.autocomplete(searchData, {
			formatItem: function(item) {
				return '<span class="ac_action ac_'+ item.action +'">' + item.text + ' <span class="ac_comment">('+ actionNames[item.action] +')</span></span>';
			},
			formatResult: function(item) {
				return item.text;
			},
			matchContains: true,
			max: 25,
			scroll: false
		}).result(function(event, item) {
			location.href = mgSettings.webroot + item.action + "/" + item.url;
		});
	$('<div id="search"><form><ul><li></li></ul></form></div>')
		.contents()
			.find("li").append($search).end()
			.parent()
		.appendTo("body");
});
