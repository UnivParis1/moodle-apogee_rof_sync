function call_Synchro(type,table) {
	$('#span_'+type+'_'+table).html('<img src="img/ajax-loader5.gif" width="16">');
	$.ajax({
	    type: "POST",
	    url: "ajax.php",
	    data : '&type='+type+'&table='+table,
	    success:
	    function(retour){
	    	$('#span_'+type+'_'+table).html('<img src="img/status-active.png" width="16">');
	    }
	});	
}