<?php // $Id: importrup.php,v 1.3 2011/10/13 11:25:28 shtifanov Exp $

    // СТРОКА 205:   ВНИМАНИЕ!!! Закомментировать эту строку после обновления планов и закрытия базы
    
    require_once("../../../config.php");
    require_once($CFG->dirroot.'/lib/uploadlib.php');
    require_once("../lib_plan.php");
    require_once("lib_import.php");

    require_login();

    $fid = optional_param('fid', 0, PARAM_INT);          // Faculty id
    $pid = optional_param('pid', 0, PARAM_INT);          // Plan id
    //$sid = optional_param('sid', 0, PARAM_INT);          // Speciality id    
    //$kvalif = optional_param('kvalif', 0, PARAM_INT);          // Kvalifiction id
  	$action = optional_param('action', '', PARAM_TEXT);       // action

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $importshp = get_string('updateshp', 'block_bsu_plan');

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

    $time0 = time();
    $select = "editplan=1 and timestart<$time0 and timeend>$time0";
    $ACCESS_USER = $DB->get_records_select_menu('bsu_operator_dean', $select, null, '', 'id, userid');

    if (!$CFG->editplanclosed || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  { 
    } else {
        notify (get_string('accessdenied', 'block_bsu_plan'));        
        echo $OUTPUT->footer();
        exit();
    }

    $CFG->show_only_differences = true;
    $OUTPUT->globaltable = new html_table();
    $OUTPUT->globaltable->head  = array ('Раздел плана', 'Поле / Текущая версия / Новая версия');
    $OUTPUT->globaltable->align = array ("left", "left");
    $OUTPUT->makeupdate = false;            

    $scriptname = 'updateshp.php';
    
    if ($fid > 0)   {
        $faculty = $DB->get_record_select('bsu_ref_department', "departmentcode = $fid", null, 'id, departmentcode, name, shortname');
    }

    if ($action == 'upload')	{
        
        ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
        @set_time_limit(0);
        @ob_implicit_flush(true);
        @ob_end_flush();
    	@raise_memory_limit("256M");
     	if (function_exists('apache_child_terminate')) {
    	    @apache_child_terminate();
    	}    

        // echo '<hr />';
        $dir = '1/rup';
        $um = new upload_manager('newfile', false, true, false, false, 32097152);
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
            $dirupdate = '1/updaterup';
            $filecopyname = $USER->id . '_' . $USER->sesskey;
            $copyfile_name = $CFG->dataroot.'/'.$dirupdate.'/'.$filecopyname;
            if (copy($newfile_name, $copyfile_name)) { 
                // echo $OUTPUT->notification("Копирование успешно выполнено.", 'notifysuccess'); 
            } else { 
                echo $OUTPUT->notification("Ошибка при копировании файла ". $newfile_name); 
            }
 
            // echo $OUTPUT->notification("OK!", 'notifysuccess');
            $contents = file_get_contents($newfile_name);
            $xml = new SimpleXMLElement($contents);
            // print_object($xml);
            $attributes = $xml->attributes();
            if (isset($attributes->type) && $attributes->type == 'РУП СПО') {
                update_shaht_plan_spo($xml, $fid, $pid);
            } else {
                update_shaht_plan_vpo($xml, $fid, $pid);
            }
            // print_object($OUTPUT->globaltable);
            // echo '<center>'.html_writer::table($OUTPUT->globaltable).'</center>';
            /*
            $contents = str_replace("windows-1251", "utf-8", $contents);
            $textlib = textlib_get_instance();
          	$contents = $textlib->convert($contents, 'win1251');
           */     
             $options = array('fid' => $fid, "action"=>"update", 'sesskey' => $USER->sesskey, 'pid' => $pid);
             echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), 'Выполнить обновление учебного плана', 'get', $options).'</center>';    
        }
    }
    
    if ($action == 'update')	{
        $dirupdate = '1/updaterup';
        $filecopyname = $USER->id . '_' . $USER->sesskey;
        $copyfile_name = $CFG->dataroot.'/'.$dirupdate.'/'.$filecopyname;

        if (!file_exists($copyfile_name)) {
             print_error("File '$copyfile_name' not found!", $scriptname);
        } else {
            $OUTPUT->makeupdate = true;
            // echo $OUTPUT->notification("OK!", 'notifysuccess');
            $contents = file_get_contents($copyfile_name);
            $xml = new SimpleXMLElement($contents);
            // print_object($xml);
            $attributes = $xml->attributes();
            if (isset($attributes->type) && $attributes->type == 'РУП СПО') {
                update_shaht_plan_spo($xml, $fid, $pid);
            } else {
                update_shaht_plan_vpo($xml, $fid, $pid);
            }
        }
    }                 
        
    
    if ($action == '' || $action == 'update')	{
    	// listbox_department("importshp.php", $fid);
    	$strlistfaculties =  listbox_department($scriptname, $fid);
    	if (!$strlistfaculties)   { 
    		// print_error(get_string('permission', 'block_bsu_plan'), 'block_plan', '../index.php');
            notice(get_string('permission', 'block_bsu_plan'), '../index.php');
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
                    listbox_plan($scriptname."?fid=$fid", $fid, $pid);
                    echo '</table>';
                                    
                    if ($pid > 0)   {
                        echo '<form method="post" enctype="multipart/form-data" action='.$scriptname.'>';
                        $struploadrup = get_string("uploadrupforupdate", "block_bsu_plan");
                        // echo $struploadrup;
                        // echo $OUTPUT->heading($struploadrup);
                        $maxuploadsize = get_max_upload_file_size();
                    	// $strchoose = get_string("choose");
                        echo '<br><center>';
                        echo '<input type="hidden" name="action" value="upload"/>'.
                             '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
                             '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">'.
                    		 '<input type="hidden" name="fid" value="'. $fid.'" />'.
                    		 '<input type="hidden" name="pid" value="'. $pid.'" />';
                             echo '<input type="file" name="newfile" size="50" value="sdfgsdfg"><br><br>'.
                             '<input type="submit" value="Загрузить новую версию и сравнить со старой версией РУП">'.
                             '</form></br>';
                        echo '</center>';
                    
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
    }    
    echo $OUTPUT->footer();
    

function get_plan_vpo_from_db($pid)
{
    global $DB, $OUTPUT;
    
    $yid = get_current_edyearid();
    //  $yid--; ВНИМАНИЕ!!! Закомментировать эту строку после обновления планов и закрытия базы
    
    
    if ($plan = $DB->get_record_select('bsu_plan', "id = $pid"))    {

        $plan->cycles = $DB->get_records_select('bsu_plan_cycle', "planid = $pid");
        
        if ($grafiki = $DB->get_records_select('bsu_plan_grafikuchprocess', "planid = $pid")) {
            foreach ($grafiki as $key => $grafik)   {
                $semestrgraf = $DB->get_records_select('bsu_plan_weeks', "planid = $pid AND numkurs = $grafik->numkurs");
                $grafiki[$key]->semestrgraf = $semestrgraf;
            }
            
        }   
        $plan->grafiki = $grafiki;    


        if ($praktik = $DB->get_records_select('bsu_plan_practice_shacht', "planid = $pid")) {
            foreach ($praktik as $index => $praktike)   {
                $praktik[$index]->semestrs = $DB->get_records_select('bsu_plan_practice_semestr_shacht', "practiceid = $praktike->id");
            }
        }        
        $plan->praktiki = $praktik;
        $plan->specvidrabot = $DB->get_records_select('bsu_plan_specvidrabot', "planid = $pid");
            
           
        $sql = "SELECT d.*, n.name as nname FROM {bsu_discipline} d 
                INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                WHERE d.planid=$pid";
        if ($disciplines = $DB->get_records_sql($sql))  {
            foreach ($disciplines as $index => $discipline)   {
                $disciplines[$index]->credits= $DB->get_records_select('bsu_discipline_creditovkurs', "disciplineid = $discipline->id");
                $disciplines[$index]->semestrs= $DB->get_records_select('bsu_discipline_semestr', "disciplineid = $discipline->id");
            }  
            $plan->disciplines = $disciplines;
        } else {
            echo $OUTPUT->notification('Дисциплины плана не найдены.');
            $plan = false;
        } 
        
        $plan->groups = array();
        $strgroup = get_plan_groups($pid);
        if ($strgroup != '')    {
            $plan->groups = explode ('<br>', $strgroup);
            $terms = array();
            foreach ($plan->groups as $group)    {
                $terms[] = get_term_group($yid, $group, 2); // !!!!!!!!!!!! $polug  ??????
            }
            $plan->maxterm = max($terms);
            $OUTPUT->maxterm = $plan->maxterm;     
        }
           
    } else {
        echo $OUTPUT->notification('План не найден.');
        $plan = false; 
    }
    
    return $plan;
}


    
function update_shaht_plan_vpo($xml, $fid, $planid)
{
    global $DB, $OUTPUT;
    
    $plannew = import_shaht_plan_vpo ($xml, $fid, 1, 1, false, true);
    $planold = get_plan_vpo_from_db($planid);
/*
    echo '<table align=center border=1><tr><td align=left valign=top >';
    print_object($planold);
    echo '</td><td>&nbsp;<====>&nbsp;</td><td align=left valign=top >'; 
    print_object($plannew);
    echo '</td></tr></table>';      
*/
    // echo $OUTPUT->notification('<strong>Титул плана</strong>', 'notifysuccess');
    $excludefields = array ('id', 'plantypeid', 'edyearid', 'specialityid', 'edformid', 'departmentcode', 'checksum', 'kvalif', 'groups', 'maxterm', 'period', 'timenorm', 
                            'modifierid', 'deleted', 'grafiki', 'praktiki', 'specvidrabot', 'disciplines', 'cycles', 'planweeks', 'praktikinew');
    $table = compare_objects($planold, $plannew, $excludefields);
    
    analys_titul_plan($table);
    
    update_record_bsu_table('bsu_plan', $table->updaterec, $planid, 'Титул плана ' . $planold->name);
    
    $h = 'h2';
    $OUTPUT->globaltable->data[] = array('', "<$h>Титул плана '$planold->name'.</$h>" );
    $OUTPUT->globaltable->data[] = array('', html_writer::table($table));
    
    if (empty($planold->grafiki))   {
        $planold->grafiki = array();
    }
    // echo $OUTPUT->notification('<strong>Графики учебного процесса</strong>', 'notifysuccess');
    $OUTPUT->globaltable->data[] = array('', "<$h>Графики учебного процесса</$h>");
    compare_grafiki($planold->grafiki, $plannew->grafiki, $planid);
    
    /* 
    if (empty($planold->praktiki))    {
         $planold->praktiki = array();
    }     
    $OUTPUT->globaltable->data[] = array('', "<$h>Практики</$h>");
    compare_praktiki($planold->praktiki, $plannew->praktiki, $planid);                                
   
    if (empty($planold->specvidrabot))    {
        $planold->specvidrabot = array();
    }            
    $OUTPUT->globaltable->data[] = array('', "<$h>Спецвидработа</$h>");
    compare_specvidrabot($planold->specvidrabot, $plannew->specvidrabot, $planid);
    */ 
    
    $OUTPUT->globaltable->data[] = array('', "<$h>ДИСЦИПЛИНЫ</$h>");
    compare_disciplines($planold->disciplines, $plannew->disciplines, $planid);

    // print_object($OUTPUT->globaltable);
    if ($OUTPUT->makeupdate)    {
        echo $OUTPUT->notification('Процесс обновления РУП завершен.', 'notifysuccess');
    } else {
        echo '<center>'.html_writer::table($OUTPUT->globaltable).'</center>';
    }   
}


function compare_objects($oldobject, $newobject, $excludefields)
{
    global $CFG;
    
    $table = new html_table();
    $table->head = array (); // ('Поле', 'Текущая версия', 'Новая версия');
    // $table->align = array ("left", "center", "center");
    $table->updaterec = new stdClass();

    $allfields = array();
    if (!empty($oldobject)) {
        foreach ($oldobject as $key => $value)    {
            $allfields[] = $key;
        }
    }
    if (!empty($newobject)) {
        foreach ($newobject as $key => $value)    {
            $allfields[] = $key;
        }
    }
    $allfields = array_unique($allfields);
    $allfields = array_diff($allfields, $excludefields);   
    
    $tabledata1 = array('Поле'); // , , 
    $tabledata2 = array('Текущая версия');
    $tabledata3 = array('Новая версия');
    foreach ($allfields as $key)    {
        $valueold = $valuenew = '';
        if (isset($oldobject->{$key}))    {
            $valueold = $oldobject->{$key};
        }    
        if (isset($newobject->{$key}))    {
            $valuenew = $newobject->{$key};
        }     
        if ($valueold == 0 && empty($valuenew)) $valuenew = 0;
        $strkey = get_string($key, 'block_bsu_plan');
        
        
        if ($valueold <> $valuenew) {
            // $table->data[] = array($strkey, "<font color=red><strong>$valueold</strong></font>", "<font color=red><strong>$valuenew</strong></font>");
            // $table->head[] = '-';
            $table->updaterec->{$key} = $valuenew;
            $tabledata1[] = $strkey;
            $tabledata2[] = "<font color=red><strong>$valueold</strong></font>";
            $tabledata3[] = "<font color=red><strong>$valuenew</strong></font>";
        } else {
            // $table->data[] = array($strkey, $valueold, $valuenew);
            // $table->head[] = '+';
            if (!$CFG->show_only_differences)    {
                $tabledata1[] = $strkey;
                $tabledata2[] = $valueold;
                $tabledata3[] = $valuenew;
            }    
        }            
    }
    if (count($tabledata1) == 1)    {
        // $table->head = array ('Изменений не обнаружено');
        $table->data[] = array ('Изменений не обнаружено.');
    } else {
        $table->data[] = $tabledata1;
        $table->data[] = $tabledata2;
        $table->data[] = $tabledata3;
    }    
    
    return $table;
} 


function compare_grafiki($oldgrafiki, $newgrafiki, $planid)
{
    global $DB, $OUTPUT; 
    
    if (empty($oldgrafiki) && $OUTPUT->makeupdate)    {
        $grafiki = $newgrafiki;
        foreach ($grafiki as $grafik)  {
            $grafik->planid = $planid;
            if ($DB->insert_record('bsu_plan_grafikuchprocess', $grafik)) {
                echo $OUTPUT->notification('ГРАФИК добавлен в БД.', 'notifysuccess');
                if (isset($grafik->semestrgraf))    {
                    foreach ($grafik->semestrgraf as $semestrgraf)  {
                        $semestrgraf->planid  = $planid;
                        if ($DB->insert_record('bsu_plan_weeks', $semestrgraf)) {
                            echo $OUTPUT->notification('ГРАФИК СЕМЕСТРОВЫЙ добавлен в БД.', 'notifysuccess');
                        } else {
                            echo $OUTPUT->notification('Ошибка при добавлении ГРАФИКа СЕМЕСТРОВОГО в БД.');
                        }
                    }
                }
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ГРАФИКа в БД.');
            }
        }        
        
    }  else {
        
        $excludefields = array ('id', 'planid', 'semestrgraf');
        
        if (empty($oldgrafiki)) {
            $old = array();
            foreach ($newgrafiki as $new)   {
                $table = compare_objects($old, $new, $excludefields);
                // echo '<center>Курс №'. $new->numkurs . html_writer::table($table).'</center>';
                $OUTPUT->globaltable->data[] = array('<strong>Курс №'. $new->numkurs . '</strong>', html_writer::table($table));
                foreach ($new->semestrgraf as $newsg)   {
                    $table = compare_objects($old, $newsg, $excludefields);
                    // echo '<center>Семестр №'. $newsg->numsemestr . html_writer::table($table).'</center>';
                    $OUTPUT->globaltable->data[] = array('<strong>Семестр №'. $newsg->numsemestr . '</strong>', html_writer::table($table));
                }
            }
        } else {
            foreach ($oldgrafiki as $old)   {
                foreach ($newgrafiki as $new)   {
                    if ($old->numkurs == $new->numkurs) {
                        $table = compare_objects($old, $new, $excludefields);
                        update_record_bsu_table('bsu_plan_grafikuchprocess', $table->updaterec, $old->id, 'ГРАФИК');  
                        
                        /*
                        $arrupdaterec = (array)$table->updaterec;
                        if (empty($arrupdaterec))   continue;  
                        print_object($table->updaterec);
                        $table->updaterec->id = $old->id; 
                        if ($OUTPUT->makeupdate)    {
                            if ($DB->update_record('bsu_plan_grafikuchprocess', $table->updaterec)) {
                                echo $OUTPUT->notification('ГРАФИК обновлен в БД.', 'notifysuccess');
                            } else {
                                echo $OUTPUT->notification('Ошибка при обновлении: ГРАФИК.');
                            }
                        }
                        */
                            
                        // echo '<center>Курс №'. $new->numkurs . html_writer::table($table).'</center>';
                        $OUTPUT->globaltable->data[] = array('<strong>Курс №'. $new->numkurs . '</strong>', html_writer::table($table));
                        foreach ($new->semestrgraf as $newsg)   {
                            foreach ($old->semestrgraf as $oldsg)   {
                                if ($oldsg->numsemestr == $newsg->numsemestr)   {
                                   $table = compare_objects($oldsg, $newsg, $excludefields);
                                   update_record_bsu_table('bsu_plan_weeks', $table->updaterec, $oldsg->id, 'ГРАФИК СЕМЕСТРОВЫЙ ');
                                   // echo '<center>Семестр №'. $newsg->numsemestr . html_writer::table($table).'</center>';
                                   $OUTPUT->globaltable->data[] = array('<strong>Семестр №'. $newsg->numsemestr . '</strong>', html_writer::table($table));
                                }
                            }    
                        }
                        break;
                    }
                }    
            }
        }    
    }    
}


function compare_praktiki($oldpraktiki, $newpraktiki, $planid)
{
    global $DB, $OUTPUT;    

    if (empty($oldpraktiki) && $OUTPUT->makeupdate)    {
        if (!empty($newpraktiki)) {           
            foreach ($newpraktiki as $praktik)   {
                $praktik->planid = $planid;
                if ($practiceid = $DB->insert_record('bsu_plan_practice_shacht', $praktik)) {
                    echo $OUTPUT->notification('ПРАКТИКА добавлена в БД.', 'notifysuccess');
                } else {
                    echo $OUTPUT->notification('Ошибка при добавлении ПРАКТИКИ в БД.');
                }
            
                if (isset($praktik->semestrs))  {
                    foreach ($praktik->semestrs as $semestr)   {
                        $semestr->practiceid = $practiceid;
                        if ($DB->insert_record('bsu_plan_practice_semestr_shacht', $semestr)) {
                            echo $OUTPUT->notification('Семестр практики добавлен в БД.', 'notifysuccess');
                        } else {
                            print_object($semestr);
                            echo $OUTPUT->notification('Ошибка при добавлении Семестра практики в БД.');
                        }
                    }
                }
            }
        }    
    }  else { 
        $excludefields = array ('id', 'planid', 'practiceid', 'semestrs');// , 'semestrgraf');
        if (empty($oldpraktiki))    {
            $old = array();
            foreach ($newpraktiki as $new)   {
                $table = compare_objects($old, $new, $excludefields);
                // echo '<center>Практика вида '. $new->vid . html_writer::table($table).'</center>';
                $OUTPUT->globaltable->data[] = array('<strong>'. $new->vid . '</strong>', html_writer::table($table));
                if (isset($new->semestrs))  {
                    foreach ($new->semestrs as $newsg)   {
                        $table = compare_objects($old, $newsg, $excludefields);
                        // echo '<center>Семестр №'. $newsg->term . html_writer::table($table).'</center>';
                        $OUTPUT->globaltable->data[] = array('<strong>Семестр №'. $newsg->term . '</strong>', html_writer::table($table));
                    }
                }    
            }
        } else {
            // print_object($newpraktiki);
            // print_object($oldpraktiki);
            foreach ($newpraktiki as $new)   {
                foreach ($oldpraktiki as $old)   {
                    if ($old->vid == $new->vid && $old->isnew == $new->isnew) {
                        $table = compare_objects($old, $new, $excludefields);
                        update_record_bsu_table('bsu_plan_practice_shacht', $table->updaterec, $old->id, 'ПРАКТИКА');
                        // echo '<center>Практика вида '. $new->vid . html_writer::table($table).'</center>';
                        $OUTPUT->globaltable->data[] = array('<strong>'. $new->vid . '</strong>', html_writer::table($table));
                        foreach ($old->semestrs as $oldsg)   {
                            foreach ($new->semestrs as $newsg)   {
                                if ($oldsg->term == $newsg->term)   {
                                   $table = compare_objects($oldsg, $newsg, $excludefields);
                                   update_record_bsu_table('bsu_plan_practice_semestr_shacht', $table->updaterec, $oldsg->id, 'Семестр практики');
                                   // echo '<center>Семестр №'. $newsg->term . html_writer::table($table).'</center>';
                                   $OUTPUT->globaltable->data[] = array('<strong>Семестр №'. $newsg->term . '</strong>', html_writer::table($table));
                                }
                            }    
                        }
                        break;
                    }
                }    
            }
        }    
    }    
}    
    
    
function compare_specvidrabot($oldspec, $newspec, $planid)
{
    global $DB, $OUTPUT;    
    
    if (empty($oldspec) && $OUTPUT->makeupdate)    {
        $specvidrabot = $newspec;
        foreach ($specvidrabot as $specvidr)   {
            $specvidr->planid = $planid;
            if ($DB->insert_record('bsu_plan_specvidrabot', $specvidr)) {
                echo $OUTPUT->notification('СПЕЦВИДРАБОТА добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении СПЕЦВИДРАБОТ в БД.');
            }
        }
    } else {    
        $excludefields = array ('id', 'planid'); // , 'practiceid', 'semestrs');// , 'semestrgraf');
        if (empty($oldspec))    {
            $old = array();            
            foreach ($newspec as $new)   {
                $table = compare_objects($old, $new, $excludefields);
                // echo '<center>Спецвида работа '. $new->field . html_writer::table($table).'</center>';
                $OUTPUT->globaltable->data[] = array('<strong>'. $new->field . '</strong>', html_writer::table($table));
            }
        } else {    
            foreach ($newspec as $new)   {
                foreach ($oldspec as $old)   {
                    if ($old->field == $new->field) {
                        $table = compare_objects($old, $new, $excludefields);
                        update_record_bsu_table('bsu_plan_specvidrabot', $table->updaterec, $old->id, 'СПЕЦВИДРАБОТА');
                        // echo '<center>Спецвида работа '. $new->field . html_writer::table($table).'</center>';
                        $OUTPUT->globaltable->data[] = array('<strong>'. $new->field . '</strong>', html_writer::table($table));
                        break;
                    }
                }    
            }
         }   
    }    
}



function compare_disciplines($olddis, $newdis, $planid)
{
    global $DB, $OUTPUT;    
    
    $alldiscnameids = array();
    
    if (!empty($olddis)) {
        $olddiscids = array();
        foreach ($olddis as $key => $olddis1)    {
            $alldiscnameids[] = $olddis1->disciplinenameid;
            $olddiscids[$olddis1->disciplinenameid] = $key;
        }
    }
    
    if (!empty($newdis)) {
        $newdiscids = array();
        foreach ($newdis as $key => $newdis1)    {
            $alldiscnameids[] = $newdis1->disciplinenameid;
            $newdiscids[$newdis1->disciplinenameid] = $key;
        }
    }
    
    $alldiscnameids = array_unique($alldiscnameids);
    $excludefields = array ('id', 'planid', 'credits', 'semestrs', 'profileid', 'notusing');// , 'semestrgraf');

    
    foreach ($alldiscnameids as $alldiscnameid) {
        $makecontinue = false;
        $strwarning = '';        
        if (isset($olddiscids[$alldiscnameid])) {
            $ooo = $olddis[$olddiscids[$alldiscnameid]]; 
        } else {
            $ooo = array();
            $strwarning = '<br><b><font color=red>ВНИМАНИЕ! Новая дисциплина.</font></b>';
        }
        
        if (isset($newdiscids[$alldiscnameid])) {
            $nnn = $newdis[$newdiscids[$alldiscnameid]];
            if (empty($ooo))    {
                if (check_maxterm_discipline($nnn)) {
                    $strwarning .= '<font color=green> Добавить дисциплину можно.</font>';
                } else {
                    $strwarning .= '<font color=red> Добавить дисциплину нельзя, т.к. семестр уже пройден.</font>';
                }
            }    
            if (empty($ooo) && $OUTPUT->makeupdate)    {
                if (check_maxterm_discipline($nnn)) {
                    insert_new_discipline($nnn, $planid);
                    // continue;
                }
                $makecontinue = true;                    
            } 
                       
        } else {
            $nnn = array();
            $strwarning = '<br><b><font color=red>ВНИМАНИЕ! Дисциплина отсутствует в новой версии плана.</font></b>';
            
            if (!empty($ooo))    {
                if (check_minterm_discipline($ooo)) {
                    $strwarning .= '<font color=green> Удалить дисциплину можно.</font>';
                } else {
                    $strwarning .= '<font color=red> Удалить  дисциплину нельзя, т.к. семестр уже пройден.</font>';
                }
            }

            if (!empty($ooo) && $OUTPUT->makeupdate)    {
                // echo 'check_minterm_discipline';
                // print_object($ooo);
                if (check_minterm_discipline($ooo)) {
                    // delete_old_discipline($ooo, $planid);
                    $DB->delete_records_select('bsu_discipline', "id = $ooo->id");
                    // echo 'DELEEEEEETTTTTTTEEEEEEEEEEEEE!!!!!!!!!!!';
                    // continue;
                }
                $makecontinue = true;    
            } 
          
        }
        
        // if (empty($ooo))  continue;
        if ($makecontinue) {
            $makecontinue = false;
            // continue;
        } else {
            $table = compare_objects($ooo, $nnn, $excludefields);
            update_record_bsu_table('bsu_discipline', $table->updaterec, $ooo->id, 'ДИСЦИПЛИНА');
            // echo '<center>Дисциплина '. $ooo->nname . ':' . $nnn->nname .  html_writer::table($table).'</center>';
            if (isset($ooo->nname))   {
                $nname = $ooo->nname . $strwarning;
            } else {
                $nname = $nnn->nname . $strwarning;
            }
            $OUTPUT->globaltable->data[] = array('<strong>'. $nname . '</strong>', html_writer::table($table));
            
            if (isset($ooo->semestrs)) {
                $ooosemestrs = $ooo->semestrs;
            } else {
                $ooosemestrs = array();
            }
            
            if (isset($nnn->semestrs)) {
                $nnnsemestrs = $nnn->semestrs;
            } else {
                $nnnsemestrs = array();
            }
            compare_disciplines_semestrs($ooosemestrs, $nnnsemestrs, $ooo->id);
        }    
    }
}


function compare_disciplines_semestrs($oldterm, $newterm, $disciplineid)
{
    global $DB, $OUTPUT;
    
    $allnumterm = array();

    if (!empty($oldterm)) {
        $oldnumterm = array();
        foreach ($oldterm as $key => $oldt) {
            $allnumterm[] = $oldt->numsemestr;
            $oldnumterm[$oldt->numsemestr] = $key;
        }
    }
    
    if (!empty($newterm)) {
        $newnumterm = array();
        foreach ($newterm as $key => $oldt) {
            $allnumterm[] = $oldt->numsemestr;
            $newnumterm[$oldt->numsemestr] = $key;
        }
    }

    
    $allnumterm = array_unique($allnumterm);
    $excludefields = array ('id', 'disciplineid');
    
    foreach ($allnumterm as $numterm) {
        $strwarning = '';
        if (isset($oldnumterm[$numterm])) {
            $ooo = $oldterm[$oldnumterm[$numterm]]; 
        } else {
            $ooo = array();
            $strwarning = '<br><b><font color=red>ВНИМАНИЕ! У дисциплины добавлен новый семестр.</font></b>';    
        }
        
        if (isset($newnumterm[$numterm])) {
            $nnn = $newterm[$newnumterm[$numterm]];
            
            if ($numterm > $OUTPUT->maxterm)    {
                if (empty($ooo))   { 
                    $strwarning .= '<font color=green> Добавить семестр можно.</font>';
                }    
                if (empty($ooo) && $OUTPUT->makeupdate)    {
                    $nnn->disciplineid = $disciplineid;
                    if ($DB->insert_record('bsu_discipline_semestr', $nnn)) {
                        echo $OUTPUT->notification('Семестр дисциплины добавлен в БД.', 'notifysuccess');
                    } else {
                        print_object($nnn);
                        echo $OUTPUT->notification('Ошибка при добавлении Семестра дисицплины в БД.');
                    }
                    continue;
                }
            } else {
                if (empty($ooo))   { 
                    $strwarning .= '<font color=red> Добавить семестр нельзя, т.к. семестр уже пройден.</font>';
                }    
            }                
        } else {
            $nnn = array();
            $strwarning = '<br><b><font color=red>ВНИМАНИЕ! У дисциплины удален семестр.</font></b>';

            if (!empty($ooo))    {
                if ($numterm > $OUTPUT->maxterm)    {
                    $strwarning .= '<font color=green> Удалить семестр можно.</font>';
                } else {
                    $strwarning .= '<font color=red> Удалить семестр нельзя, т.к. семестр уже пройден.</font>';
                }
            } 
            
            if (!empty($ooo) && $OUTPUT->makeupdate)    {
                if ($numterm > $OUTPUT->maxterm)    {
                    // echo 'DELETE SEMESTR!!!!!!!!<br>';
                    // echo $numterm;
                    // print_object($OUTPUT);
                    $DB->delete_records_select('bsu_discipline_semestr', "id = $ooo->id");
                    continue;
                }    
            } 
            
        }

        if (empty($ooo))  continue;
                
        if ($numterm <= $OUTPUT->maxterm)    {
            $excludefields = array ('id', 'disciplineid', 'numsemestr', 'lection', 'praktika', 'lab', 'ksr', 'srs');
            /*
                                            , 'examenhours', 'zachet', 
                                           'zachetdiff', 'examen', 'kp', 'kr', 'zet', 'referat', 'essay', 'kontr', 'rgr', 'intlec', 'intlab', 
                                           'intpr', 'intiz', 'prlecinweek', 'prlabinweek', 'prprinweek', 'przet');
            */                                            
        }   else {
            $excludefields = array ('id', 'disciplineid');
        } 
        
        $table = compare_objects($ooo, $nnn, $excludefields);
        update_record_bsu_table('bsu_discipline_semestr', $table->updaterec, $ooo->id, 'Семестр дисциплины');

        if (isset($ooo->numsemestr)) {
            $numsemestr = $ooo->numsemestr;
        } else {
            $numsemestr = $nnn->numsemestr;
        } 
        
        $numsemestr .= $strwarning;
        // echo '<center>Семестр № '. $ooo->numsemestr . ':' . $nnn->numsemestr .  html_writer::table($table).'</center>';
        $OUTPUT->globaltable->data[] = array('<strong>Семестр № '. $numsemestr . '</strong>', html_writer::table($table));
    }
}


function analys_titul_plan($table)
{
    global $OUTPUT;
    
    // print_object($table);

    $counterror = 0;    
    $updaterec = $table->updaterec;
    
    if (isset($updaterec->name))    {
        // echo $OUTPUT->notification("<strong>ВНИМАНИЕ!!! Новое название плана не совпадает со старым!!!</strong>");
        $counterror++;
    }
    
    if (isset($updaterec->shortname))    {
        // echo $OUTPUT->notification("<strong>ВНИМАНИЕ!!! Короткое название плана не совпадает со старым!!!</strong>");
        $counterror++;
    }

    if (isset($updaterec->lastshifr))    {
        echo $OUTPUT->notification("<strong>ВНИМАНИЕ!!! Шифр в новой версии плана не совпадает со старым!!!</strong>");
        $counterror++;
    }
    
    if ($counterror>0)  {
        // echo '<center><strong>Титул плана</strong> '.  html_writer::table($table).'</center>';
        // echo $OUTPUT->notification("<strong>Много не совпадений в титуле плана. Скорее всего выбран не соответсвующий файл. Обновление прервано.</strong>");
        // echo $OUTPUT->footer();
        // exit();
        echo $OUTPUT->notification("<strong>ВНИМАНИЕ! Много не совпадений в титуле плана! ПРОВЕРЬТЕ, может быть выбран не соответствующий файл! </strong>");        
    }
}



function update_record_bsu_table($tablename, $updaterec, $id, $what)
{
    global $DB, $OUTPUT;    

    $arrupdaterec = (array)$updaterec;
    if (empty($arrupdaterec)) return false;
    
    $updaterec->id = $id;
    // print_object($updaterec);     
    if ($OUTPUT->makeupdate)    {
        if ($DB->update_record($tablename, $updaterec)) {
            echo $OUTPUT->notification($what.' обновлен(а) в БД.', 'notifysuccess');
        } else {
            echo $OUTPUT->notification('Ошибка при обновлении: '.$what);
        }
    }
}


function insert_new_discipline($discipline, $planid)
{
    global $DB, $OUTPUT;    
    
    $discipline->planid = $planid;
    // print_object($discipline);
    if ($disciplineid = $DB->insert_record('bsu_discipline', $discipline)) {
        echo $OUTPUT->notification('ДИСЦИПЛИНА добавлена в БД с id='.$disciplineid, 'notifysuccess');
        foreach ($discipline->credits as $credit)   {
            $credit->disciplineid = $disciplineid;
            $credit->CrECTS = str_replace(',', '.', $credit->CrECTS);
            $credit->zet = str_replace(',', '.', $credit->zet);
            if ($DB->insert_record('bsu_discipline_creditovkurs', $credit)) {
                echo $OUTPUT->notification('КредитовПоКурсам добавлен в БД.', 'notifysuccess');
            } else {
                print_object($credit);
                echo $OUTPUT->notification('Ошибка при добавлении КредитовПоКурсам в БД.');
            }
        }
    
        if (!isset($discipline->semestrs))  {
            echo $OUTPUT->notification('<b>ВНИМАНИЕ! У дисциплины ' . $discipline->nname . ' не заданы часы занятий в семестре.</b>');
        } else {
    
            foreach ($discipline->semestrs as $semestr)   {
                $semestr->disciplineid = $disciplineid;
                if ($DB->insert_record('bsu_discipline_semestr', $semestr)) {
                    echo $OUTPUT->notification('Семестр дисциплины добавлен в БД.', 'notifysuccess');
                } else {
                    print_object($semestr);
                    echo $OUTPUT->notification('Ошибка при добавлении Семестра дисицплины в БД.');
                }
            }
        }    
    
    } else {
        echo $OUTPUT->notification('Ошибка при добавлении ДИСЦИПЛИНЫ в БД.');
    }
}

// true если семестр дисциплины больше максимального пройденного
function check_maxterm_discipline($discipline)
{
    global $OUTPUT;
    
    $terms = array();
    if (isset($discipline->semestrs))  {
        foreach ($discipline->semestrs as $semestr)   {
           $terms[] = $semestr->numsemestr;
        }
        $maxterm = max($terms);    
    } else {
        $maxterm = 0;
    }
    // print_object($terms);
    
    if ($maxterm > $OUTPUT->maxterm) $ret = true;
    else $ret = false; 
    
    return $ret;
}


function check_minterm_discipline($discipline)
{
    global $OUTPUT;
    
    $terms = array();
    if (isset($discipline->semestrs))  {
        foreach ($discipline->semestrs as $semestr)   {
           $terms[] = $semestr->numsemestr;
        }
        $minterm = min($terms);    
    } else {
        $minterm = 0;
    }
    // print_object($terms);
    // echo $minterm;
    
    if ($minterm > $OUTPUT->maxterm) $ret = true;
    else $ret = false; 
    
    return $ret;
}    
    
?>