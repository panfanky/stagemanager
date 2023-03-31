/*to use $ as jQuery in wp*/
if(!window.hasOwnProperty("$")){
	var $ = jQuery.noConflict();
}else{
	if(typeof($)=="undefined"){
		var $ = jQuery.noConflict();
	}
}
$.datepicker.setDefaults({
			dayNames: [ "ne", "po", "út", "st", "čt", "pá", "so" ],
			altFormat: "D",
			showOn: "both",
			buttonText: "",
			buttonImage: "/wp-content/plugins/tha-stage-type/img/cal.svg",
			onSelect: function(dateText){
				$(this).closest(".calendarcont").removeClass("unset");
				//auto end podle startdate i když už je nastavenej
				if($(this).attr("name")=="thanew_datepart"){
					var endinput=$(this).closest(".eventrow").find("input[name='thanew_end_datepart']");
					endinput.closest(".calendarcont").removeClass("unset");
					endinput.datepicker("setDate",$(this).val());
				}
			}
		});
function initDateFields(){
	$(".datepicker").each(function(){
			// dateFormat: "d. mm. yy" set by WP
		$(this).datepicker({
			defaultDate: new Date( 2022, 5, 3, 13, 0 ),
			disabled: true,
			altField: $(this).closest(".calendarcont").find('.dayname')
		}); 
		$(".spaceopen .datepicker").datepicker("enable");
	});
}
$(document).ready(function(){
	initDateFields();
});