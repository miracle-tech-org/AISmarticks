$("#whapi_token_add").click(function(){
	var clone=$("#whapi_token_sample").clone();
	$(clone).find(".input_token").attr("name","settings[tokens][]").val("");
	$(clone).find(".input_channel_name").attr("name","settings[channels_names][]").val("");
	$(clone).find(".whapi_token_remove").css("display","block");
	$(clone).attr("id","whapi_token_sample_done");
	$(clone).appendTo("#whapi_token_samples");
	return false;
});

$(document).on("click",".whapi_token_remove",function(){
	if (confirm("Are you sure?")) $(this).closest(".whapi_token").remove();
	return false;
});

$(document).on("click",".whapiopenpopup",function(){
	window.open($(this).attr('href'),'popup','width=600,height=600');
	return false;
});

$("#whapi_simulate_metrics").click(function(){
	$.ajax({
		url: laroute.route('mailboxes.whapi.simulate'),
	}).done(function( msg ) {
		  alert(msg);
	});
	return false;
});