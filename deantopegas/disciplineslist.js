u=1000000;

$(document).ready(function(){
	
    $('#add > a').click(function(){
	   
		//var i=$('#frm input[name^="%kurs_"]').length+1;
        var step=u+1;
        //alert(step);
        var did = $(this).data('did');
		var o=$("<input type='text' name='" + step + "_kurs_" + did + "' id='" + step + "_kurs_" + did + "' size='5' value='' /><br><br>");
        
        var len = $(document).scrollTop();
        
        $("#" + did).append(o);

        $("#" + step + "_kurs_" + did).focus(); 

        $('html, body').animate( {

            scrollTop: len

        }, 'slow')
        
        u=step;
        
        
	});
    
 
    
});