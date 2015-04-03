function hide_show(id_kurs, planid){
    
    /*переменные хранящие ссылки Edit и Save*/
    var id_ref_e = 'edit_' + id_kurs; // ID правой кнопки edit
    var id_ref_left_e = 'edit1_' + id_kurs; // ID левой кнопки edit
    var id_ref_s = 'save_' + id_kurs; // ID правой кнопки save
    var id_ref_left_s = 'save1_' + id_kurs; // ID левой кнопки save
    var div_edit=document.getElementById(id_ref_e).style.display; //св-во display правой кнопки edit
    var div_save=document.getElementById(id_ref_s).style.display; //св-во display правой кнопки save
    
    /* Скрытие/Отображение количества недель весны и осени */
    /*Весна*/
    var week_spring_l = 'week_spring_label_' + id_kurs; // ID лейбла с кол-вом недель весной
    var week_spring_i = 'week_spring_div_input_' + id_kurs; // ID div c Input с видом мероприятия
    var div_l=document.getElementById(week_spring_l).style.display; // св-во display лейбла с видом мероприятия
    var div=document.getElementById(week_spring_i).style.display; // св-во display с видом мероприятия
    /*Осень*/
    var week_autumn_l = 'week_autumn_label_' + id_kurs; // ID лейбла с кол-вом недель весной
    var week_autumn_i = 'week_autumn_div_input_' + id_kurs; // ID div c Input с видом мероприятия
    var div_l_aut=document.getElementById(week_autumn_l).style.display; // св-во display лейбла с видом мероприятия
    var div_aut=document.getElementById(week_autumn_i).style.display; // св-во display с видом мероприятия
    /*Весна*/
    if(div_l=="none"){
        div_l="block";
    }
    else{
        div_l="none";
    }
    
    if(div=="none"){
        div="block";
    }
    else{
        div="none";
    }
    /*Осень*/
    if(div_l_aut=="none"){
        div_l_aut="block";
    }
    else{
        div_l_aut="none";
    }
    
    if(div_aut=="none"){
        div_aut="block";
    }
    else{
        div_aut="none";
    }
    
    document.getElementById(week_spring_l).style.display=div_l;
    document.getElementById(week_spring_i).style.display=div;
    document.getElementById(week_autumn_l).style.display=div_l;
    document.getElementById(week_autumn_i).style.display=div;
    
    
    /* цикл для скрытия/отображения ячеек с видом мероприятия (52 штуки)*/
    for(var i=1; i<53; i++ ){
        var id_element_l = 'l_' + id_kurs + '_' + i; // ID лейбла с видом мероприятия
        var id_element = id_kurs + '_' + i; // ID Input с видом мероприятия
        var div_l=document.getElementById(id_element_l).style.display; // св-во display лейбла с видом мероприятия
        var div=document.getElementById(id_element).style.display; // св-во display с видом мероприятия
        
        if(div_l=="none"){
            div_l="block";
        }
        else{
            div_l="none";
        }
        
        if(div=="none"){
            div="block";
        }
        else{
            div="none";
        }
        document.getElementById(id_element_l).style.display=div_l;
        document.getElementById(id_element).style.display=div;
    }
    
    if(div_edit=="none"){
        var graphik='';
        for(var i=1; i<53; i++){
            var id_element = 'input_' + id_kurs + '_' + i; // ID Input с видом мероприятия
            //alert(id_element);
            var val=document.getElementById(id_element).value; // value с видом мероприятия\
            if (!val){
                val='Т';
            }
            graphik += val;
        }
        var week_spring_i = 'week_spring_input_' + id_kurs; // ID div c Input с видом мероприятия
        var week_autumn_i = 'week_autumn_input_' + id_kurs; // ID div c Input с видом мероприятия
        
        /* Значения Input - ов с кол-вом недель весны и осени*/
        var numweekspring = document.getElementById(week_spring_i).value;
        var numweekautumn = document.getElementById(week_autumn_i).value;
        //alert(week_spring_i + ' _ ' + numweekspring);
        //alert(week_autumn_i + ' _ ' + numweekautumn);
        
        $.post("graphicup_set.php", { 'planid': planid, 'graphik': graphik, 'id_kurs': id_kurs, 'numweekspring': numweekspring, 'numweekautumn': numweekautumn },
            function (status) {
                //alert("TEST");
                arrreturn = status.split('_');
                arrnewval = status.split('');
                //alert(arrreturn[1]);
                //alert(arrreturn[2]);
                //перерисовка инпутов в случае выполнения аякс подзапроса
                for(var i=0; i<52; i++){
                    i++;
                    var id_element_l = 'l_' + id_kurs + '_' + i; // ID Input с видом мероприятия
                    i--;
                    if(arrnewval[i] == 'Т'){
                        arrnewval[i] = '';
                    }
                    $("#" + id_element_l).html(arrnewval[i]);
                }
                $("#" + week_spring_l).html('<center>'+ arrreturn[1] +'</center>');
                $("#" + week_autumn_l).html('<center>'+ arrreturn[2] +'</center>');
                $('#statusbar').html("<font color='green'>Изменения в курсе сохранены</font>").show();
                $('#statusbar').hide(8000).html();
            },
            "json"
        );
        //перерисовка инпутов в случае выполнения аякс подзапроса
        //Снова делаем видимой кнопку редактирования
        div_edit="block";
    }
    else{
        div_edit="none";
    }
    
    if(div_save=="none"){
        div_save="block";
    }
    else{
        div_save="none";
    }
    document.getElementById(id_ref_e).style.display=div_edit;
    document.getElementById(id_ref_s).style.display=div_save;
    document.getElementById(id_ref_left_e).style.display=div_edit;
    document.getElementById(id_ref_left_s).style.display=div_save;
    
}