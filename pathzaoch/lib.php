<?php
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                      /// 
    ///     Форма добавления вакансий                                                                        ///   
    ///                                                                                                      ///
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_ispravlenie(){
    global $DB;
        $abstracts = 
                            html_writer::start_tag('form', array( 'method' => 'post' )).
                                html_writer::empty_tag('input', array( 'type'=> 'hidden', 'name' => 'action',   'value' => "ispravlenie" )).
                                html_writer::start_tag('table', array( 'border' => 0 , 'width' => '100%') ).
                                    html_writer::start_tag('tr').
                                        html_writer::start_tag('td', array( 'width' => '250' ,  'valign' => 'top', 'align' => 'right') ).
                                            "&nbsp;".
                                        html_writer::end_tag('td').
                                        html_writer::start_tag('td', array( 'valign' => 'top', 'align' => 'left' ) ).
                                            html_writer::empty_tag('input', array( 'type'=> 'submit', 'value' => 'Исправить' )).
                                        html_writer::end_tag('td').
                                    html_writer::end_tag('tr').
                                html_writer::end_tag('table').
                            html_writer::end_tag('form');
             
        return $abstracts;
  
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция формирует таблицу сотрудников кафедры                                                       ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function list_pathzaoch(){
    global $DB;

        $table = new html_table();
        $table->width = '100%' ;
        $table->head  = array('№', 'План', 'Дисциплины с проблемами');
        $table->rowclasses = array();
        $table->data = array();

        $textsql = "SELECT id, name
                    FROM {bsu_plan}
                    WHERE (deleted = 0) AND (edformid = 3) ";
        if($zaochs = $DB->get_records_sql($textsql) ){
            foreach($zaochs AS $zaoch){
                $field0 = $zaoch->id;
                $field1 = $zaoch->name;
                $field2 = list_pathzaoch_discipline($zaoch->id);
                $table->data[] =   array($field0, $field1, $field2);
            }
        }

        return $table;
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция формирует поле дисциплины                                                                   ///
    ///     $planid - (id таблицы bsu_plan) план                                                               ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function list_pathzaoch_discipline($planid){
    global $DB;
        
        $field2 = " ";
        
        $textsql = "SELECT id, planid, disciplinenameid,  semestrexamen, semestrzachet, semestrkursovik
                    FROM {bsu_discipline}
                    WHERE (planid = {$planid})";
        if($zaochs = $DB->get_records_sql($textsql) ){
            foreach($zaochs AS $zaoch){
                if( pathzaoch_discipline_proverkasemestr_exz($zaoch) ){
                    $field2 = $field2.pathzaoch_disciplinename($zaoch->disciplinenameid)." ".$zaoch->id." экз.<br>";
                }
                if( pathzaoch_discipline_proverkasemestr_zah($zaoch) ){
                    $field2 = $field2.pathzaoch_disciplinename($zaoch->disciplinenameid)." ".$zaoch->id." зач.<br>";
                }
                if( pathzaoch_discipline_proverkasemestr_kur($zaoch) ){
                    $field2 = $field2.pathzaoch_disciplinename($zaoch->disciplinenameid)." ".$zaoch->id." курс.<br>";
                }
            }
        }

        return $field2;
    }
    
    /////////////////////////////////////////////////////////////////////////////////////
    ///                                                                               ///
    ///     Получаем наименование дисциплины                                          ///
    ///     $id - (id таблицы bsu_ref_disciplinename) - дисциплины                    ///
    ///                                                                               ///
    /////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_disciplinename($id){
    global $DB;
        
        $result = "<font color = red> ?- </font>";
        
        if($object = $DB->get_record('bsu_ref_disciplinename', array('id' => $id ) ) ){
            $result = $object->name;
        }
        
        return $result;
    }
    
    /////////////////////////////////////////////////////////////////////////////////////
    ///                                                                               ///
    ///     Проверка семестра дисциплины екзамена (1 - если верно)                    ///
    ///     $object - запись (таблицы bsu_discipline) - дисциплины                    ///
    ///                                                                               ///
    /////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_discipline_proverkasemestr_exz($object){
    global $DB;
        
        $result = 1;
        
        //$mas16semestrexamens = array();
        $semestrexamen = $object->semestrexamen;
        $colsimsemestrexamen = strlen ($semestrexamen);
        if($colsimsemestrexamen == 0){
            $result = 0;
        }
        for ($i = 0; $i < $colsimsemestrexamen; $i++) {
            if( (hexdec($semestrexamen[$i]) == 0) ){
                $result = 0;
            }else{
                if($disciplinesemestrs = $DB->get_records('bsu_discipline_semestr', array('disciplineid' => $object->id ) ) ){
                    foreach($disciplinesemestrs AS $disciplinesemestr){
                        if( hexdec($semestrexamen[$i]) == $disciplinesemestr->numsemestr ){
                            $result = 0;
                        }
                    }
                }    
            }
        }
        
        return $result;
    }
    
    /////////////////////////////////////////////////////////////////////////////////////
    ///                                                                               ///
    ///     Проверка семестра дисциплины зачёт (1 - если верно)                       ///
    ///     $object - запись (таблицы bsu_discipline) - дисциплины                    ///
    ///                                                                               ///
    /////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_discipline_proverkasemestr_zah($object){
    global $DB;
        
        $result = 1;

        $semestrzachet = $object->semestrzachet;
        $colsimsemestrzachet = strlen ($semestrzachet);
        if($colsimsemestrzachet == 0){
            $result = 0;
        }
        for ($i = 0; $i < $colsimsemestrzachet; $i++) {
            if( (hexdec($semestrzachet[$i]) == 0) ){
                $result = 0;
            }else{
                if($disciplinesemestrs = $DB->get_records('bsu_discipline_semestr', array('disciplineid' => $object->id ) ) ){
                    foreach($disciplinesemestrs AS $disciplinesemestr){
                        if( hexdec($semestrzachet[$i]) == $disciplinesemestr->numsemestr ){
                            $result = 0;
                        }
                    }
                }    
            }
        }
        
        return $result;
    }
    
    /////////////////////////////////////////////////////////////////////////////////////
    ///                                                                               ///
    ///     Проверка семестра дисциплины курсовая(1 - если верно)                     ///
    ///     $object - запись (таблицы bsu_discipline) - дисциплины                    ///
    ///                                                                               ///
    /////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_discipline_proverkasemestr_kur($object){
    global $DB;
        
        $result = 1;

        $semestrkursovik = $object->semestrkursovik;
        $colsimsemestrkursovik = strlen ($semestrkursovik);
        if($colsimsemestrkursovik == 0){
            $result = 0;
        }
        for ($i = 0; $i < $colsimsemestrkursovik; $i++) {
            if( (hexdec($semestrkursovik[$i]) == 0) ){
                $result = 0;
            }else{
                if($disciplinesemestrs = $DB->get_records('bsu_discipline_semestr', array('disciplineid' => $object->id ) ) ){
                    foreach($disciplinesemestrs AS $disciplinesemestr){
                        if( hexdec($semestrkursovik[$i]) == $disciplinesemestr->numsemestr ){
                            $result = 0;
                        }
                    }
                }    
            }
        }
        
        return $result;
    }
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция формирует таблицу сотрудников кафедры                                                       ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_action_ispravlenie(){
    global $DB;

        $textsql = "SELECT id, name
                    FROM {bsu_plan}
                    WHERE (deleted = 0) AND (edformid = 3) ";
        if($zaochs = $DB->get_records_sql($textsql) ){
            foreach($zaochs AS $zaoch){
                pathzaoch_action_pathzaoch_discipline($zaoch->id);
            }
        }

        return 1;
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция формирует поле дисциплины                                                                   ///
    ///     $planid - (id таблицы bsu_plan) план                                                               ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_action_pathzaoch_discipline($planid){
    global $DB;
        
        $textsql = "SELECT id, planid, disciplinenameid,  semestrexamen, semestrzachet, semestrkursovik
                    FROM {bsu_discipline}
                    WHERE (planid = {$planid})";
        if($zaochs = $DB->get_records_sql($textsql) ){
            foreach($zaochs AS $zaoch){
                if( pathzaoch_discipline_proverkasemestr_exz($zaoch) ){
                    pathzaoch_action_isprav_exz($zaoch);
                }
                if( pathzaoch_discipline_proverkasemestr_zah($zaoch) ){
                    pathzaoch_action_isprav_zah($zaoch);
                }
                /*
                if( pathzaoch_discipline_proverkasemestr_kur($zaoch) ){
                    pathzaoch_action_isprav_kur($zaoch);
                }
                */
                
            }
        }

        return 1;
    }
    
    /////////////////////////////////////////////////////////////////////////////////////
    ///                                                                               ///
    ///     Проверка семестра дисциплины (1 - если верно)                             ///
    ///     $object - запись (таблицы bsu_discipline) - дисциплины            ///
    ///                                                                               ///
    /////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_action_isprav_exz($object){
    global $DB;
        
        $result = 1;
        
        //$mas16semestrexamens = array();
        $semestrexamen = $object->semestrexamen;
        $colsimsemestrexamen = strlen ($semestrexamen);

        $objectupd = new stdClass();
        $objectupd->id = $object->id;
        $objectupd->semestrexamen = "";
                        
        for ($i = 0; $i < $colsimsemestrexamen; $i++) {
            $semexamen = "";
            if($disciplinesemestrs = $DB->get_records('bsu_discipline_semestr', array('disciplineid' => $object->id ) ) ){
                foreach($disciplinesemestrs AS $disciplinesemestr){
                    if( (hexdec($semestrexamen[$i]) >= $disciplinesemestr->numsemestr * 0.5) AND ( (hexdec($semestrexamen[$i])-1) < $disciplinesemestr->numsemestr * 0.5) ){
                        echo $object->id." <br>";
                        $semexamen = $disciplinesemestr->numsemestr;
                    }
                }
            } 
            $termhex = dechex($semexamen);
            $termhex = strtoupper($termhex); 
            $objectupd->semestrexamen = $objectupd->semestrexamen.$termhex;   
        }
        
        $DB->update_record('bsu_discipline', $objectupd);
        
        return $result;
    }
    
    /////////////////////////////////////////////////////////////////////////////////////
    ///                                                                               ///
    ///     Проверка семестра дисциплины (1 - если верно)                             ///
    ///     $object - запись (таблицы bsu_discipline) - дисциплины            ///
    ///                                                                               ///
    /////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_action_isprav_zah($object){
    global $DB;
        
        $result = 1;

        $semestrzachet = $object->semestrzachet;
        $colsimsemestrzachet = strlen ($semestrzachet);

        $objectupd = new stdClass();
        $objectupd->id = $object->id;
        $objectupd->semestrzachet = "";
                        
        for ($i = 0; $i < $colsimsemestrzachet; $i++) {
            $semzachet = "";
            if($disciplinesemestrs = $DB->get_records('bsu_discipline_semestr', array('disciplineid' => $object->id ) ) ){
                foreach($disciplinesemestrs AS $disciplinesemestr){
                    if( (hexdec($semestrzachet[$i]) >= $disciplinesemestr->numsemestr * 0.5) AND ( (hexdec($semestrzachet[$i])-1) < $disciplinesemestr->numsemestr * 0.5) ){
                        echo $object->id." <br>";
                        $semzachet = $disciplinesemestr->numsemestr;
                    }
                }
            } 
            $termhex = dechex($semzachet);
            $termhex = strtoupper($termhex); 
            $objectupd->semestrzachet = $objectupd->semestrzachet.$termhex;   
        }
        
        $DB->update_record('bsu_discipline', $objectupd);
        
        return $result;
    }
    
    /////////////////////////////////////////////////////////////////////////////////////
    ///                                                                               ///
    ///     Проверка семестра дисциплины (1 - если верно)                             ///
    ///     $object - запись (таблицы bsu_discipline) - дисциплины            ///
    ///                                                                               ///
    /////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_action_isprav_kur($object){
    global $DB;
        
        $result = 1;

        $semestrkursovik = $object->semestrkursovik;
        $colsimsemestrkursovik = strlen ($semestrkursovik);

        $objectupd = new stdClass();
        $objectupd->id = $object->id;
        $objectupd->semestrkursovik = "";
                        
        for ($i = 0; $i < $colsimsemestrkursovik; $i++) {
            $semkursovik = "";
            if($disciplinesemestrs = $DB->get_records('bsu_discipline_semestr', array('disciplineid' => $object->id ) ) ){
                foreach($disciplinesemestrs AS $disciplinesemestr){
                    if( (hexdec($semestrkursovik[$i]) >= $disciplinesemestr->numsemestr * 0.5) AND ( (hexdec($semestrkursovik[$i])-1) < $disciplinesemestr->numsemestr * 0.5) ){
                        echo $object->id." <br>";
                        $semkursovik = $disciplinesemestr->numsemestr;
                    }
                }
            }
            $termhex = dechex($semkursovik);
            $termhex = strtoupper($termhex); 
            $objectupd->semestrkursovik = $objectupd->semestrkursovik.$termhex;   
        }
        
        $DB->update_record('bsu_discipline', $objectupd);
        
        return $result;
    }
    
?>