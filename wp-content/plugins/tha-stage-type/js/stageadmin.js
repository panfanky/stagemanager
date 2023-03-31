/*to use $ as $ in wp*/
if(!window.hasOwnProperty("$")){
	var $ = jQuery.noConflict();
}else{
	if(typeof($)=="undefined"){
		var $ = jQuery.noConflict();
	}
}
$(document).on("click",".collapser", function(){
	$("#" + $(this).data("collapse")).toggle();
});
$(document).on("click",".thanewadder",function(e){
	e.preventDefault();
	//ajax wp_insert_post()
	var stageid=$(this).closest(".stagecont").data("stageid");
	var eventspace=$(this).closest(".eventspace");
	var data = {
		'action': 'quick_add_tha_event',
		'thaneweventstage': stageid,
		'thanewartistid': eventspace.find(".artistid").val(),
		'thanewpublishevent': eventspace.find("input[name='public']:checked").val(),
		'thanew_datepart': eventspace.find("input[name='thanew_datepart']").val(),
		'thanew_timepart': eventspace.find("input[name='thanew_timepart']").val(),
		'thanew_end_datepart': eventspace.find("input[name='thanew_end_datepart']").val(),
		'thanew_end_timepart': eventspace.find("input[name='thanew_end_timepart']").val(),
		'thanew_artistname': eventspace.find("input[name='searchartist']").val(),
		'thanew_artistimage': eventspace.find("input[name='image']").val(),
		'thanew_artistdesc': eventspace.find("textarea[name='thanew_artistdesc']").val(),
		'thanew_artistsubtitle': eventspace.find("input[name='subtitle']").val(),
		'thanew_artistheadliner': eventspace.find("input[name='headliner']:checked").val(),
		'thanew_artistvideo': eventspace.find("input[name='video']").val(),
		'thanew_artistfbpage': eventspace.find("input[name='fbpage']").val(),
		'thanew_artistwebsite': eventspace.find("input[name='website']").val()
	};
	var clicked=$(this);
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	$.post(ajaxurl, data, function(response) {
		console.log(response);
		if(response==0){//nonsuccess
			alert("Chyba: Nenastavena stage");
		}else if(response!="saved"){
			alert("Chyba:\n" + response);
		}else{
			var refreshdata = {
				'action': 'refresh_tree',
				'stageid': stageid
			}
			$.post(ajaxurl,refreshdata, function(response) {
				// console.log(response);
				//redraw event list
				$("#subtree").replaceWith(response);
				//reinit datepicker including new event row
				initDateFields();
				alert("Událost přidána. Data umělce uložena.");
			});
		}
	});
});

$(document).on("click",".thaeventquickedit",function(e){
	e.preventDefault();
	var stageid=$(this).closest(".stagecont").data("stageid");
	var eventspace=$(this).closest(".eventspace");
	var data = {
		'action': 'quick_edit_tha_event',
		'id': eventspace.attr("id"),
		'thanewartistid': eventspace.find(".artistid").val(),
		'thanewpublishevent': eventspace.find("input[name='public']:checked").val(),
		'thanew_datepart': eventspace.find("input[name='thanew_datepart']").val(),
		'thanew_timepart': eventspace.find("input[name='thanew_timepart']").val(),
		'thanew_end_datepart': eventspace.find("input[name='thanew_end_datepart']").val(),
		'thanew_end_timepart': eventspace.find("input[name='thanew_end_timepart']").val(),
		'thanew_artistimage': eventspace.find("input[name='image']").val(),
		'thanew_artistname': eventspace.find("input[name='searchartist']").val(),
		'thanew_artistdesc': eventspace.find("textarea[name='thanew_artistdesc']").val(),
		'thanew_artistsubtitle': eventspace.find("input[name='subtitle']").val(),
		'thanew_artistheadliner': eventspace.find("input[name='headliner']:checked").val(),
		'thanew_artistvideo': eventspace.find("input[name='video']").val(),
		'thanew_artistfbpage': eventspace.find("input[name='fbpage']").val(),
		'thanew_artistwebsite': eventspace.find("input[name='website']").val()
	};
	var clicked=$(this);
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	$.post(ajaxurl, data, function(response) {
		// console.log(response);
		if(response==0){
			alert("Chyba: Neodeslalo se ID eventu k úpravám");
		}else if(response!="saved"){
			alert("Chyba:\n" + response);
		}else{
			var refreshdata = {
				'action': 'refresh_tree',
				'stageid': stageid
			}
			$.post(ajaxurl,refreshdata, function(response) {
				// console.log(response);
				//redraw event list
				$("#subtree").replaceWith(response);
				//reinit datepicker including new event row
				initDateFields();
				alert("Upraveno. Aktuální data uložena pro událost i umělce.");
			});
		}
	});
});

$(document).on("click",".thaeventdelete",function(){
	var stageid=$(this).closest(".stagecont").data("stageid");
	var eventspace=$(this).closest(".eventspace");
	var data = {
		'action': 'quick_delete_tha_event',
		'id': eventspace.attr("id")
	};
	var clicked=$(this);
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	if(confirm("Opravdu smazat?")){
		$.post(ajaxurl, data, function(response) {
			// console.log(response);
			if(response==0){
				alert("Chyba: Neodeslalo se ID eventu k mazání");
			}else if(response!="deleted"){
				alert("Chyba:\n" + response);
			}else{
				var refreshdata = {
					'action': 'refresh_tree',
					'stageid': stageid
				}
				$.post(ajaxurl,refreshdata, function(response) {
					// console.log(response);
					//redraw event list
					$("#subtree").replaceWith(response);
					//reinit datepicker including new event row
					initDateFields();
					alert("Událost smazána.");
				});
			}
		});
	}
});


function emptyEventspace(eventspace){
	eventspace.removeClass("knownartist");
	eventspace.find(".iconcontrol").removeClass("knownartist");
	eventspace.find(".artistid").val("");
	eventspace.find("input[name=subtitle]").val("");
	eventspace.find("input[name=video]").val("");
	eventspace.find("input[name=fbpage]").val("");
	eventspace.find("input[name=website]").val("");
	eventspace.find("textarea[name=thanew_artistdesc]").text("");
	eventspace.find("input[name=headliner]").prop("checked", false);eventspace.find(".iconcontrol").removeClass("urlset");
	// if image chosen 
	eventspace.find("input[name=image]").val("");
	eventspace.find(".iconcontrol").removeClass("urlset");
}
function populateEventspaceWith(thisoption){
	var ls=thisoption.closest(".livesearch_cont");
	var eventspace=thisoption.closest(".eventspace");
	var dataobj=thisoption.data();
	ls.find(".searchartist").val(thisoption.text());
	ls.find(".artistid").val(dataobj["id"]);
	ls.find(".livesearch").hide();
	// eventspace.find("input[name=subtitle]").addClass("urlset")
	$.each(dataobj, function(k,v){
		if(dataobj[k]) eventspace.find(".iconcontrol.ic_"+k).addClass("urlset");
	});
	//populate form with artist data
	eventspace.find("input[name=subtitle]").val(dataobj["subtitle"]);
	eventspace.find("input[name=video]").val(dataobj["video"]);
	eventspace.find("input[name=fbpage]").val(dataobj["fbpage"]);
	eventspace.find("input[name=website]").val(dataobj["website"]);
	eventspace.find("input[name=image]").val(dataobj["image"]);
	eventspace.find("textarea[name=thanew_artistdesc]").text(dataobj["desc"]);
	if(dataobj["headliner"]=="on") eventspace.find("input[name=headliner]").prop("checked",true); else eventspace.find("input[name=headliner]").prop("checked",false);
	eventspace.addClass("knownartist");
}


function livesearch(inputfield){
	var inputstring=$(inputfield).val();
	var eventspace=inputfield.closest(".eventspace");
	
	var lsroll=inputfield.closest(".livesearch_cont").find(".livesearch");
	// gray / green save button
	if(inputstring=="") eventspace.find(".thaeventsaver").prop("disabled", true);
	else {
		eventspace.find(".thaeventsaver").prop("disabled", false);
		console.log(eventspace.attr("id"));
		//disable leaving adder when allowed its saving and reseting
		if(eventspace.attr("id")=="adder"){
			$(".ic_edit").removeClass("enabled");
			$(".socialcolapse").removeClass("enabled");
			eventspace.find(".thaeventreset").show();
		}
	}
		
	var data = {
		'action': 'livesearch_artists',
		'searchartist': inputstring
	};
	$.post(ajaxurl, data, function(response) {
		// console.log(response);		
		var eventspace=inputfield.closest(".eventspace");
		
		if(response==0){//field empty
			lsroll.hide();
			emptyEventspace(eventspace);
		}else{			
			lsroll.show();
			var oneresult=false;
			
			if(response!="new"){
				var livesearchhtml="";
				responseobj=JSON.parse(response);
				// console.log(responseobj);
				var lastid;
				
				//show options
				$.each(responseobj, function(k,v){
					livesearchhtml += "<a href='#' data-id='" + k + "' data-subtitle='" + v.subtitle + "' data-headliner='" + v.headliner + "' data-video='" + v.video + "' data-fbpage='" + v.fbpage + "' data-website='" + v.website + "' data-image='" + v.image + "' data-desc='" + v.desc + "'>" + v.title + "</a>";
					lastid=k;
				});
				lsroll.html(livesearchhtml);
				if(Object.keys(responseobj).length==1) oneresult=true;
			}
			// console.log(oneresult);
			// console.log("now chosing");
			if(oneresult && responseobj[lastid]["title"]==inputstring){
			// console.log("found only one and searchfield filled with known artist name");
			
				populateEventspaceWith(eventspace.find("a[data-id=" + lastid + "]"));
				
			}else{
				// console.log("searchfield filled with new name");
			
				emptyEventspace(eventspace);
				
				/* if no suggestions */
				if(response=="new"){
					lsroll.html("(nenalezen - bude přidán)");
				}
			}
		}
	});
}

$(document).on("keyup", ".searchartist", function(e){
	
	// nice2have : use keyboard
	if(e.which==38){//up
		console.log("up");
	}
	else if(e.which==40){//down
		console.log("dn");
	}else{
		livesearch($(this));		
	}
	
});
// activate livesearch when returning to the input field
$(document).on("click", ".searchartist", function(){
		livesearch($(this));		
})
$(document).on("click",".livesearch a", function(e){
	e.preventDefault();
	populateEventspaceWith($(this));
});
// $(document).on("mouseenter",".livesearch a", function(){
	// $(this).focus();
// });

//hide livesearch on click outside //https://stackoverflow.com/questions/152975/how-do-i-detect-a-click-outside-an-element/3028037#3028037
function hideOnClickOutside(element) {
    const outsideClickListener = event => {
        if (event.target.closest(element) === null) {
          $(element).find(".livesearch").hide();
          // removeClickListener()
        }
    }

    const removeClickListener = () => {
        document.removeEventListener('click', outsideClickListener)
    }

    document.addEventListener('click', outsideClickListener)
}

const isVisible = elem => !!elem && !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length ) // source (2018-03-11): https://github.com/$/$/blob/master/src/css/hiddenVisibleSelectors.js 

hideOnClickOutside(".livesearch_cont");

$(document).on("click",".ic_edit.enabled, .socialcolapse.enabled", function(e){
	e.preventDefault();
	eventspace=$(this).closest(".eventspace");
	if((eventspace).attr("id")=="adder"){
		//adder, while still empty, can be left unsaved. Enable all edit buttons when adder is open
		$(".ic_edit").addClass("enabled");
		$(".socialcolapse").addClass("enabled");
	}else{
		$(".ic_edit").removeClass("enabled");
		$(".socialcolapse").removeClass("enabled");
	}
	// console.log("colapse");
	// console.log(eventspace);
	$(".eventspace").removeClass("spaceopen");
	$(".eventspace input, .eventspace textarea").prop("disabled", true);
	$(".datepicker").datepicker("disable");
	eventspace.addClass("spaceopen");
	eventspace.find("input, textarea").prop("disabled", false);
	eventspace.find(".datepicker").each(function(){
		$(this).datepicker("enable");
	});
});
$(document).on("click",".thaeventreset", function(e){
	e.preventDefault();
	var stageid=$(this).closest(".stagecont").data("stageid");
	// console.log("colapse");
	var refreshdata = {
		'action': 'refresh_tree',
		'stageid': stageid
	}
	$.post(ajaxurl,refreshdata, function(response) {
		// console.log(response);
		//redraw event list
		$("#subtree").replaceWith(response);
		//reinit datepicker including new event row
		initDateFields();
	});
});



$(document).ready(function($) {
	
	// save the send_to_editor handler function
	window.send_to_editor_default = window.send_to_editor;

	$(document).on("click",".spaceopen .iconcontrol.ic_image", function(){
		
		// replace the default send_to_editor handler function with our own
		window.send_to_editor = window.attach_image;
		window.this_eventspace = this.closest(".eventspace");
		tb_show('Set Artist Image', 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true', false);
		//TB_iframe : always set this parameter as true, so that window shown in an iframe.
		//post_id : set id as 0 which means image will not be attached to any post.
		// Third parameter: Set this option as false when you are not going to work with group of images.
		return false;
	});
	
	// handler function which is invoked after the user selects an image from the gallery popup.
	// this function displays the image and sets the id so it can be persisted to the post meta
	window.attach_image = function(html) {
		
		// turn the returned image html into a hidden image element so we can easily pull the relevant attributes we need
		$('body').append('<div id="temp_image">' + html + '</div>');
			
		var img = $('#temp_image').find('img');
		
		imgurl   = img.attr('src');
		imgclass = img.attr('class');
		imgid    = parseInt(imgclass.replace(/\D/g, ''), 10);
		console.log(window.this_eventspace);
		$(window.this_eventspace).find("input[name='image']").val(imgid);
		$(window.this_eventspace).find(".iconcontrol.ic_image").addClass("urlset");
		window.this_eventspace = null;

		// $('#upload_image_id').val(imgid);
		// $('#remove-book-image').show();

		// $('img#book_image').attr('src', imgurl);
		try{tb_remove();}catch(e){};
		$('#temp_image').remove();
		
		// restore the send_to_editor handler function
		window.send_to_editor = window.send_to_editor_default;
		
	}

	//reinit datepicker including new event row
	initDateFields();
});
/*image set*/
// $(document).on("click",".iconcontrol.ic_image", function(){
			// window.send_to_editor_default = window.send_to_editor;
	
				// window.send_to_editor = window.attach_image;
	// tb_show('', 'media-upload.php?post_id=634&amp;type=image&amp;TB_iframe=true');
	// console.log("showing");
// });