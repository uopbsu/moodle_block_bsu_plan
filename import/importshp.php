<?php // $Id: importrup.php,v 1.3 2011/10/13 11:25:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once($CFG->dirroot.'/lib/uploadlib.php');
    require_once("../lib_plan.php");
    require_once("lib_import.php");

    // require_login();

    $fid = optional_param('fid', -1, PARAM_INT);          // Faculty id
    $gid = optional_param('gid', 0, PARAM_INT);          // Group id
    $sid = optional_param('sid', 0, PARAM_INT);          // Speciality id    
    $kvalif = optional_param('kvalif', 0, PARAM_INT);          // Kvalifiction id
  	$action = optional_param('action', 'selectspec', PARAM_TEXT);       // action

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $importshp = get_string('importshp', 'block_bsu_plan');

    $PAGE->set_url('/blocks/bsu_info/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($importshp);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    // $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
	$PAGE->navbar->add($strtitle, new moodle_url("$CFG->BSU_PLAN/index.php", array()));
	$PAGE->navbar->add($importshp);
    echo $OUTPUT->header();

    notify ('<strong>ВНИМАНИЕ!!!<br />Функцию "Импорт РУП" можно использовать только для загрузки планов для групп нового набора 2014/2015 уч. года. <br />
    ДЛЯ ОБУЧАЮЩИХСЯ ГРУПП ПЛАНЫ НАДО КОРРЕКТИРОВАТЬ НЕПОСРЕДСТВЕННО В СИСТЕМЕ В РЕЖИМЕ ОНЛАЙН.<br /><br /> 
    ИСКЛЮЧЕНИЕМ являются только те планы, на которые ПОДПИСАНЫ ГРУППЫ НЕСКОЛЬКИХ КУРСОВ.<br /> 
    В таких планах невозможно изменять дисциплины для групп младших курсов в тех семестрах, 
    <br />которые уже пройдены группами старших курсов. Если необходимо внести коррективы в дисциплины для младших курсов, <br />
    то надо создавать копию плана с помощью функции <a href="cloneplan.php">"Создание копии РУП"</a> и <br />
    выполнять перевод групп младших курсов на копию с помощью функции <a href="movegroup.php">"Перевод группы на другой РУП".</a> <br />Группы старших курсов переводить не надо. <br />
    Только таким образом можно сохранить расписание, нагрузку и оценки обучающихся групп.<br />
    ИДЕАЛЬНЫЙ ВАРИАНТ: на каждую группу - отдельный план!!! (об этом говорилось неоднократно)<br /> 
    ПРИЕМЛИМЫЙ ВАРИАНТ: на каждый курс - отдельный план.
    
    <br /><br /><br /><br /></strong>');


    /*
    $time0 = time();
    $select = "editplan=1 and timestart<$time0 and timeend>$time0";
    $ACCESS_USER = $DB->get_records_select_menu('bsu_operator_dean', $select, null, '', 'id, userid');
    if (!$CFG->editplanclosed || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  { 
    } else {
        notify (get_string('accessdenied', 'block_bsu_plan'));        
        echo $OUTPUT->footer();
        exit();
    }
    */
    
    ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
    @set_time_limit(0);
    @ob_implicit_flush(true);
    @ob_end_flush();
	@raise_memory_limit("256M");
 	if (function_exists('apache_child_terminate')) {
	    @apache_child_terminate();
	}    
     
	// listbox_department("importshp.php", $fid);
	$strlistfaculties =  listbox_department("importshp.php", $fid);
	if (!$strlistfaculties)   { 
		// print_error(get_string('permission', 'block_bsu_plan'), 'block_plan', '../index.php');
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	


    if ($fid > 0)   {
        $faculty = $DB->get_record_select('bsu_ref_department', "departmentcode = $fid", null, 'id, departmentcode, name, shortname');
    }
  
    if ($action == 'upload')	{
        // echo '<hr />';
        $dir = '1/rup';
        $um = new upload_manager('newfile', false, true, 1, false, 32097152);
        // print_object($um); echo '<hr>';
        if ($um->process_file_uploads($dir))  {
            echo $OUTPUT->notification(get_string('uploadedfile'), 'notifysuccess');
            $fn = $um->get_new_filename();
            /*
            $newfile_name = $CFG->dataroot.'/'.$dir.'/'.$fn;
            $newfile_name = addslashes($newfile_name);
            */
            $oldname = $CFG->dataroot.'/'.$dir.'/'.$fn;
            // echo $oldname. '<hr>';
            $fnnew = transliterate($fn);
            $newname = $CFG->dataroot.'/'.$dir.'/'.$fnnew;
            // echo $newname . '<hr>';
            rename ($oldname, $newname );
            $newfile_name = addslashes($newname);
            // echo $newfile_name . '<hr>';
            // str_replace()
            // echo $fn;
            $a = explode(".", $fn);
            // print_object($a);
            $ext1 = end($a);
            // echo $ext1; exit();
            if ($ext1 != 'xml' && $ext1 != 'XML' && $ext1 != 'osf' && $ext1 != 'OSF')   {
                notice('<h1>ВНИМАНИЕ! Загружаемый файл не является файлом в формате XML. Импорт невозможен. Как сохранить файл в формате XML показано в <a  href ="http://dekanat.bsu.edu.ru/f.php/1/dekanat.ppt"> презентации </a>.</h1>', '');
            } 
            // print_object($um); echo '<hr>';
        } else {
            print_error(get_string("uploaderror", "assignment"), "i.php"); //submitting not allowed!
        }

        if (!file_exists($newfile_name)) {
             print_error("File '$newfile_name' not found!", "i.php");
        } else {
            echo $OUTPUT->notification("OK!", 'notifysuccess');
            
            $contents = file_get_contents($newfile_name);
            $xml = new SimpleXMLElement($contents);
            // print_object($xml);
            $attributes = $xml->attributes();
            if (isset($attributes->type) && $attributes->type == 'РУП СПО') {
                import_shaht_plan_spo($xml, $fid, $sid, $kvalif);
            } else {
                import_shaht_plan_vpo($xml, $fid, $sid, $kvalif);
            }
            /*
            $contents = str_replace("windows-1251", "utf-8", $contents);
            $textlib = textlib_get_instance();
          	$contents = $textlib->convert($contents, 'win1251');
            */       
            
        }
    }
    
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;
    // echo '</table>';

       
    // $context = get_context_instance(CONTEXT_FACULTY, $fid);
    if ($fid > 0 )  {
        $faculty = $DB->get_record_select('bsu_ref_department', "departmentcode = $fid", null, 'id, departmentcode, name, shortname');
        // $context = context_faculty::instance($fid);
        // $context = get_context_instance(CONTEXT_COURSE, $course->id);
        $context = get_context_instance(CONTEXT_FACULTY, $fid);
        // $editcapability = has_capability_bsu('block/bsu_plan:importplan', $context);
        // $viewcapability = has_capability('block/bsu_plan:viewcurriculum', $context);
        $editcapability = has_capability('block/bsu_plan:importplan', $context);    

        if ($editcapability)   {
            // listbox_groups_for_plans("importshp.php?fid=$fid", $fid, $gid);
                listbox_speciality("importshp.php?fid=$fid", $faculty->id, $sid);
                
                if ($sid > 0)   {
                    echo '<form method="post" enctype="multipart/form-data" action="importshp.php">';
                    listbox_kvalifspec("importshp.php?fid=$fid&sid=$sid");
                    echo '</table>';
                    $struploadrup = get_string("uploadrup", "block_bsu_plan");
                    // echo $struploadrup;
                    // echo $OUTPUT->heading($struploadrup);
        
                    $maxuploadsize = get_max_upload_file_size();
                	// $strchoose = get_string("choose");
                    echo '<br><center>';
                    echo '<input type="hidden" name="action" value="upload"/>'.
                         '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
                         '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">'.
                		 '<input type="hidden" name="fid" value="'. $fid.'" />'.
                		 '<input type="hidden" name="sid" value="'. $sid.'" />';
                         
                         /*
                         if ($action == 'selectspec')   {
                            $strsql = "SELECT idSpecyal as id, Specyal FROM {bsu_tsspecyal}
                                       where idFakultet=$faculty->id";
                            $specialitys = $DB->get_records_sql ($strsql);
                            echo '<select name="sid">
                                 <option selected="selected" value="0">Выберите специальность...</option>';
                            foreach ($specialitys as $speciality)   {
                                echo "<option value=\"$speciality->id\">$speciality->specyal</option><br>";
                		    }
                            echo '</select><br><br>';
                         } 
                         */      
        
                         echo '<input type="file" name="newfile" size="50" value="sdfgsdfg"><br><br>'.
                         '<input type="submit" value="'.$struploadrup.'">'.
                         '</form></br>';
                    echo '</center>';
                
        /*            
                    $specyalmenu = array();
                    $specyalmenu[0] = get_string('selectspecyal', 'block_bsu_plan').'...';
                    foreach ($specialitys as $speciality)   {
                        $specyalmenu[$speciality->id] = $speciality->specyal;
            		}
                    echo '<div align="center">';
                    // echo $OUTPUT->single_select("importshp.php?action=select&fid=$fid", 'sid', $specyalmenu, 0, null, 'switchspec');
                    echo '</div>';
                    echo $OUTPUT->footer();
                    exit();
        */            
             } else {
                  echo '</table>';
             }
        }  else {
           echo '</table>';
           notice(get_string('permission', 'block_bsu_plan'), '../index.php');
        }
    } else {
        echo '</table>';
    }
    echo $OUTPUT->footer();
?>