    <script>
        /*function validate_form(obj)
        {
            var valid = true;
            var umk = obj.umk.value; 
            var template = /^http\:\/\/pegas\.bsu\.edu\.ru?.+/i;
            var template2 = /^pegas\.bsu\.edu\.ru?.+/i;
            
            var umkRes = template.exec(umk);
            var umkRes2 = template2.exec(umk);
            
            if( umkRes!=null || umkRes2!=null )
            {
                //alert(umkRes2+" Удача2");
                valid = true;
            }
            else
            {
                alert("Введен не корректный адрес УМК");
                valid = false;
            }
            return valid;
        }*/
    </script>
<?php
	
    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once('../../../lib/uploadlib.php');
    require_once("../import/lib_import.php");    
    //require_once("../../bsu_ref/lib_ref.php");
    
    $pid = optional_param('pid', 0, PARAM_INT);                 // plan id
    $did = optional_param('did', 0, PARAM_INT);                 // discipline id
    $dname = optional_param('dname', NULL, PARAM_TEXT);
    $action = optional_param('action', NULL, PARAM_TEXT);       // action
    $rid = optional_param('rid', 0, PARAM_INT);                 // record id
    
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strbuilding = get_string('area', 'block_bsu_area');

    $PAGE->set_url('/blocks/area/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strtitle);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add('Рабочие планы (по квалификации)', new moodle_url("deanpegas.php", array()));
    $PAGE->navbar->add('Список дисциплин', new moodle_url("disciplineslist.php?pid={$pid}", array()));
    $PAGE->navbar->add("Подписать УМК");

    
    echo $OUTPUT->header();
    if( $did > 0 )
    {
        //*********************************Форма для добавления УМК*********************************//
        $table_add = '<center><h4>'.'Добавить файлы для курса</h4><br /></center>'; // <h3>'.$dname.'</h3>
        
        $table_add .= '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
        $table_add .= '<tr><td>';
            //$table_add .= "<form name=\"form1\" method=\"post\" enctype=\"multipart/form-data\" onsubmit=\"return validate_form ( form1 );\">";
            $table_add .= "<form name=\"form1\" method=\"post\" enctype=\"multipart/form-data\" \">";
                //$table_add .= "1.&nbspУМК:&nbsp<input type='text' name='umk' size='100%' title='Вставте Url - ссылку с Pegas'><br />";
                //$table_add .= "<p align='right'><font size='1pt' color='red'><i>*скопировать ссылку на УМК с адресной строки, на сайте: <a href='http://pegas.bsu.edu.ru'>СЭО НИУ \"БелГУ\" \"Пегас\"</a></i></font></p>";
                $table_add .= "<input type='hidden' name='action' value='add'>";
                
                $sql =  "SELECT m.id, m.courseid FROM mdl_bsu_discipline_pegas_course m
                        WHERE m.planid={$pid} and m.disciplineid={$did}
                        ";
                        
                $options = $DB->get_records_sql($sql);
                
                $table_add .= "1.&nbspКурс ID:&nbsp&nbsp&nbsp&nbsp
                               <select name='dpcid'>";
                //$table_add .= "<option disabled>Выберите курс</option>";
                foreach($options as $option){
                    $table_add .= "<option value='{$option->id}'>{$option->courseid}</option>";
                }
                $table_add .= "</select><br /><br />";
                
                
                $table_add .= "2.&nbspТитул_1:&nbsp&nbsp&nbsp&nbsp<input type='file' name='filename1' >";
                $table_add .= "<font size='1pt' color='red'><i> *загрузить файл до 3 МБ</i></font><br /><br />";
                $table_add .= "3.&nbspТитул_2:&nbsp&nbsp&nbsp&nbsp<input type='file' name='filename2' >";
                $table_add .= "<font size='1pt' color='red'><i> *загрузить файл до 3 МБ</i></font><br /><br />";
                $table_add .= "4.&nbspМатериал:&nbsp<input type='file' name='filename3' >";
                $table_add .= "<font size='1pt' color='red'><i> *загрузить файл до 3 МБ</i></font><br /><br />";                
                $table_add .= "<center>
                            <input type=\"reset\" value=\"Очистить форму\">
                            <input type=\"submit\" value=\"Добавить УМК\">
                      </center>";
            $table_add .= "</form>";
        $table_add .= '</td></tr>';
        
        $table_add .= '</table>';
        
        //*********************************Действия после нажатия на кнопку добавить УМК*********************************//
        if($action == 'add'){
            $action = NULL;       // action
            $rid = 0;
            $frm = data_submitted();
            //print_object($frm);
            //********Перенесение файлов на сервер*********//
            if ( $_FILES['filename1']['size']!=0 )
            {
                if ( $_FILES['filename2']['size']!=0 )
                {
                    if ( $_FILES['filename3']['size']!=0 )
                    {
                        /*
                        $obj1 = new stdClass();
                        $obj1->umk_ref = $frm->umk;
                        
                        $tmp_url = array();
                        $tmp_url = explode("=", $obj1->umk_ref);
                        $obj1->courseid = end($tmp_url);
                        
                        $obj1->planid = $pid;
                        $obj1->disciplineid = $did;

                        $idrow = $DB->insert_record('bsu_discipline_pegas_course_materials', $obj1);

                        */

                        //$url = criterion_file_loading_for_reiting($frm, $pid."_".$did."_".$idrow."_t1_", "filename1", $pid, $did);
                        //$url2 = criterion_file_loading_for_reiting($frm, $pid."_".$did."_".$idrow."_t2_", "filename2", $pid, $did);
                        //$url3 = criterion_file_loading_for_reiting($frm, $pid."_".$did."_".$idrow."_m3_", "filename3", $pid, $did);
                        
                        //
                        $obj1 = new stdClass();
                        $obj1->dpcid = $frm->dpcid;
                        $idrow = $DB->insert_record('bsu_discipline_pegas_course_materials', $obj1);
                        //
                        unset($obj1);
//                        $url = criterion_file_loading_for_reiting($frm, $pid."_".$did."_t1_", "filename1", $pid, $did);
//                        $url2 = criterion_file_loading_for_reiting($frm, $pid."_".$did."_t2_", "filename2", $pid, $did);
//                        $url3 = criterion_file_loading_for_reiting($frm, $pid."_".$did."_m3_", "filename3", $pid, $did);

                        $url = criterion_file_loading_for_reiting($frm, $pid."_".$did."_".$idrow."_t1_", "filename1", $pid, $did);
                        $url2 = criterion_file_loading_for_reiting($frm, $pid."_".$did."_".$idrow."_t2_", "filename2", $pid, $did);
                        $url3 = criterion_file_loading_for_reiting($frm, $pid."_".$did."_".$idrow."_m3_", "filename3", $pid, $did);

                                                
                        $obj2 = new stdClass();
                        $obj2->id = $idrow;
                        $obj2->dpcid = $frm->dpcid;
                        $obj2->material1 = $url;
                        $obj2->material2 = $url2;
                        $obj2->material3 = $url3;
                        //$idrow = $DB->insert_record('bsu_discipline_pegas_course_materials', $obj2);
                        $idrow = $DB->update_record('bsu_discipline_pegas_course_materials', $obj2);
                        unset($obj2);
                        
                        /*
                        $obj2 = new stdClass();
                        $obj2->id = $idrow;
                        $obj2->dpcid = $frm->dpcid;
                        $idrow = $DB->update_record('bsu_discipline_pegas_course_materials', $obj2);
                        */
                        redirect("deanpegasumk.php?pid={$pid}&did={$did}&dname={$dname}",'Файлы перенесены на сервер, УМК записан',2);
                    }
                    else
                    {
                        notify('Не выбран файл "Материал"');
                    }
                }
                else
                {
                    notify('Не выбран файл "Титул 2"');
                }
            }
            else
            {
                notify('Не выбран файл "Титул 1"');
            }
        }
        //**************************Действия при нажатии на кнопку удаления*******************************//
        if( $action = 'delete' )
        {
            if( $rid !=0 )
            {
                if ($record_for_del = $DB->get_record("bsu_discipline_pegas_course_materials", array('id'=>$rid),"id, material1, material2, material3"))
                {
                    if ($del = $DB->delete_records("bsu_discipline_pegas_course_materials",array('id'=>$rid)))
                    {
                        notify("1. Файлы удалены с БД","notifysuccess");
                        if (unlink($CFG->dataroot.$record_for_del->material1) && unlink($CFG->dataroot.$record_for_del->material2) && unlink($CFG->dataroot.$record_for_del->material3))
                        { 
                            redirect("deanpegasumk.php?pid={$pid}&did={$did}&dname={$dname}","<font color='green'>2. Файлы удалены с Сервера</font>");
                            //notify("2. Файлы удалены с Сервера","notifysuccess"); 
                        }
                        else
                        { 
                            notify("2. Ошибка при удалении файлов с Сервера"); 
                        }
                    }
                    else{
                        notify("1. Ошибка при удалении файлов с БД");
                    }
                }
                else
                {
                    notify("Удаляемая запись не обнаружена в БД");
                }
            }
        }
    
        //**************************Отображение страницы (По умолчанию)***********************************//
        
        
        if($DB->record_exists("bsu_discipline_pegas_course", array('planid' => $pid, 'disciplineid' => $did)))
        {
            $sql = "SELECT s.id, m.id as mid, m.courseid, 
                        s.material1, s.material2, s.material3 FROM mdl_bsu_discipline_pegas_course m
                    INNER JOIN mdl_bsu_discipline_pegas_course_materials s on m.id = s.dpcid
                    WHERE m.planid={$pid} and m.disciplineid={$did}";
            //print_object($sql);
            $materials = $DB->get_records_sql($sql);
            
            // $table_add = '<center><h4>'.'Добавить УМК к дисциплине: </h4><h3>'.$dname.'</h3></center>';
            echo $OUTPUT->heading($dname);
            $table = new html_table();
            $table->align = array ('center', 'left', 'left', 'left', 'left', 'center');
            $table->width = "80%";
            $table->head = array (  '№ п/п',
                                    'Ссылка УМК',
                                    'Титул 1',
                                    'Титул 2',
                                    'Материал',
                                    'Действия'
                                  );
            $table_view = array();
            $i=1;
                foreach($materials as $material)
                {
                    //$aaa = substr($material->umk_ref,0,7);
                    //print_object($aaa);
                    //if( $aaa != 'http://' )        //Проверяем присутствие 'http://' в ссылке на УМК
                    //{
                    //    $material->umk_ref = 'http://'.$material->umk_ref;
                    //}
                    $table->data[] = array($i, 
                                "<a href=". 'http://pegas.bsu.edu.ru/course/view.php?id=' . $material->courseid." target='_blank'>http://pegas.bsu.edu.ru?course={$material->courseid}</a>", 
                                "<a href=".$CFG->wwwroot."/f.php".$material->material1." target='_blank'> Скачать \"Титул_1\"</a>",
                                "<a href=".$CFG->wwwroot."/f.php".$material->material2." target='_blank'> Скачать \"Титул_2\"</a>",
                                "<a href=".$CFG->wwwroot."/f.php".$material->material3." target='_blank'> Скачать \"Материал\"</a>",
                                "<a href='deanpegasumk.php?pid={$pid}&did={$did}&dname={$dname}&action=delete&rid={$material->id}'><img class='icon' title='Удалить' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>"
                                );
                    $i++;
                }
            
            echo'<center>'.html_writer::table($table).'</center>';
        }
        echo $table_add;
    }
    else
    {
        //notify("План не выбран");
        notify("Дисциплина не выбран");
    }

    echo $OUTPUT->footer();
      
    function criterion_file_loading_for_reiting($frm, $pref, $filename, $pid, $did){
    global $CFG, $DB, $USER;
        $fileurl = null;
        if (!empty($_FILES["$filename"]['name'])){
            $dirtmp = "1/umk/tmp";
            //Проверка существования дирректории
            //в случае отсутствия создания её
            $dir = "1/umk/{$pid}/{$did}";
            if(!file_exists($CFG->dataroot."/".$dir)){
                if (!mkdir($CFG->dataroot."/".$dir, 0777, true)) {
                    echo('Не удалось создать директорию...');
                }
            }
            if(!file_exists($CFG->dataroot."/".$dirtmp)){
                if (!mkdir($CFG->dataroot."/".$dirtmp, 0777, true)) {
                    echo('Не удалось создать директорию...');
                }
            }

            require_once($CFG->dirroot.'/lib/uploadlib.php');
                        
            $um = new upload_manager( "$filename" ,false ,false , 1, false, 3145728);
                if ($um->process_file_uploads($dirtmp))  {
                $oldname = $um->files["$filename"]['fullpath'];
                $newname = $CFG->dataroot."/".$dir."/{$pref}".transliterate($um->files["$filename"]['name']);
                rename ($oldname , $newname );
                $fileurl = "/".$dir."/{$pref}".transliterate($um->files["$filename"]['name']);
                } else {
                echo "<center>Ошибка загрузки файла</center>";
                notify('НЕОБХОДИМО перезагрузить файл с размером меньше 3 МБ!');
            }
        }
        
        return $fileurl;
    }
    
    function insert_idrow_into_filename($filename, $idrow)
    {
        $split_filename = explode(".",$filename);
        
        $result="";
        
        foreach($split_filename as $sf)
        {
            if( $sf != end($split_filename) )
            {                            
                $result .= $sf;
            }
            else
            {
                $result .= '_'.$idrow.'.';
                $result .= $sf;
            }
        }
        return $result;
    }
 
?>