<?php // $Id: syncpractice.php,v 1.3 2011/10/13 11:25:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once($CFG->dirroot.'/lib/uploadlib.php');
    require_once("../lib_plan.php");
    require_once("lib_import.php");
    require_once("../../bsu_charge/lib_charge.php");
    require_once("../../bsu_charge/lib_charge_spec.php");
    require_once("../lib_report.php");    

    define("SHIFR_KAF_LENGTH", 8);

/*
ALTER TABLE `dean`.`mdl_bsu_ref_practice_type` ADD COLUMN `formula` VARCHAR(255) AFTER `calcfunc`,
 ADD COLUMN `edworkkindid` INTEGER UNSIGNED DEFAULT 13 AFTER `formula`,
 ADD COLUMN `edformid` INTEGER UNSIGNED DEFAULT 0;
 */
    require_login();

    $fid = optional_param('fid', 0, PARAM_INT);					// faculty id
  	$action = optional_param('action', '', PARAM_TEXT);       // action
    $var = optional_param('variant', 1, PARAM_INT);       // action
    $ignoreerror = optional_param('error', 0, PARAM_INT);       // action
    $yid = optional_param('yid', 0, PARAM_INT);					// current year    

    if ($yid == 0)   {
        $yid = get_current_edyearid(true);
        // $yid++;
    }

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strscript = get_string('syncpractice', 'block_bsu_plan');

    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strscript);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    // $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
	$PAGE->navbar->add($strtitle, new moodle_url("$CFG->BSU_PLAN/index.php", array()));
	$PAGE->navbar->add($strscript . '(шаблон в первом столбце содержит ID плана)');
    echo $OUTPUT->header();

    if (!is_siteadmin()) {
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
    }            
  
    ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
    @set_time_limit(0);
    @ob_implicit_flush(true);
    @ob_end_flush();
	@raise_memory_limit("256M");
 	if (function_exists('apache_child_terminate')) {
	    @apache_child_terminate();
	}    

  
    if ($action == 'upload')	{
        // echo '<hr />';
        $dir = '1/csv';
        $um = new upload_manager('newfile', false, false, false, false, 32097152);
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
            // print_object($um); echo '<hr>';
        } else {
            print_error(get_string("uploaderror", "assignment"), "i.php"); //submitting not allowed!
        }

        if (!file_exists($newfile_name)) {
             print_error("File '$newfile_name' not found!", "i.php");
        } else {
            if ($var == 1)  {
                $table = sync_practice($newfile_name, $fid, $yid);
            } else if ($var == 2)  {
                $table = sync_specvidrabot($newfile_name, $fid, $yid);
            } else if ($var == 3)  {
                $table = sync_aspirant($newfile_name, $fid, $yid);
            } else if ($var == 4)  {
                $table = sync_mediki($newfile_name, $fid, $yid);
            }      
            // echo '<center>'.html_writer::table($table).'</center>';
            // save_table_to_file($table);
            // $options = array('action'=> 'excel', 'fid' => $fid, 'pid' => $plan->id, 'term' => $term, 'sesskey' => sesskey());
            // echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center><br>';
            if ($yid == 15) {
                $ASPIRANTPLAN = array(3082, 3083, 3084, 3085);
                $currsubdeps   = $DB->get_records_sql_menu("SELECT id as id1, id as id2 FROM mdl_bsu_vw_ref_subdepartments where yearid=$yid");
                $perehodsubdep = $DB->get_records_sql_menu("SELECT id2, id FROM mdl_bsu_vw_ref_subdepartments where yearid=$yid");
            } else {
                $ASPIRANTPLAN = array(2130, 2131, 2132, 2133, 2134);
            }   
            
            $textsqlplan = "SELECT id, name, lastshifr, specialityid, profileid, edformid, kvalif
                            FROM {bsu_plan}
                            WHERE (departmentcode = {$fid}) AND (deleted = 0) AND (notusing = 0)";
            if( $plans = $DB->get_records_sql($textsqlplan)) {
                // print_object($plans);
                foreach($plans AS $plan){
                    
                    $strgroups = get_plan_groups($plan->id);
                    // echo $strgroups . '<br />';
                    if ($strgroups != '')   {
                        $agroups = explode ('<br>', $strgroups);
                    } else {
                        $agroups = array();
                    }
                    $table1 = table_practice($yid, $fid, $plan, $agroups);               
                    $table2 = table_specvidrabot($yid, $fid, $plan, $agroups);
                }
            }            
        }
    }
    
    $scriptname = "syncpractice.php";
    $strlistfaculties =  listbox_department($scriptname."?error=$ignoreerror", $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;
    listbox_year($scriptname."?error=$ignoreerror&fid=$fid", $yid);
    echo '</table>';

    if($fid > 0){    
        $maxuploadsize = get_max_upload_file_size();
        
        $struploadrup = $strscript;
        // echo $struploadrup;
        /*
        echo $OUTPUT->heading($struploadrup . '<br />ВНИМАНИЕ! В первом столбце должен быть указан ID плана.');
        echo '<br><center>';
        echo '<form method="post" enctype="multipart/form-data" action="syncpractice.php">';
        echo '<input type="hidden" name="action" value="upload"/>'.
             '<input type="hidden" name="variant" value="1"/>'.
             '<input type="hidden" name="error" value="' . $ignoreerror . '"/>'.
             '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
             '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">';
       	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
        echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
        echo '<input type="file" name="newfile" size="50" value="sdfgsdfg"><br><br>'.
             '<input type="submit" value="'.$strscript.'">'.
             '</form><br />';
        echo '</center><hr />';
        */
        /*
        echo $OUTPUT->heading('Загрузка специальных видов работ'. '<br />ВНИМАНИЕ! В первом столбце должен быть указан ID плана.');
        echo '<br><center>';
        echo '<form method="post" enctype="multipart/form-data" action="syncpractice.php">';
        echo '<input type="hidden" name="action" value="upload"/>'.
             '<input type="hidden" name="variant" value="2"/>'.
             '<input type="hidden" name="error" value="' . $ignoreerror . '"/>'.
             '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
             '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">';
       	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
        echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
        echo '<input type="file" name="newfile" size="50" value="sdfgsdfg"><br><br>'.
             '<input type="submit" value="Загрузка спец видов работ">'.
             '</form></br>';
        echo '</center>';
        */
        echo $OUTPUT->heading('Загрузка руководство аспирантов'. '<br />ВНИМАНИЕ! В первом столбце должен быть указан ID плана.', 4);
        echo '<br><center>';
        echo '<form method="post" enctype="multipart/form-data" action="syncpractice.php">';
        echo '<input type="hidden" name="action" value="upload"/>'.
             '<input type="hidden" name="variant" value="3"/>'.
             '<input type="hidden" name="error" value="' . $ignoreerror . '"/>'.
             '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
             '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">';
       	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
        echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
        echo '<input type="file" name="newfile" size="50" value="sdfgsdfg"><br><br>'.
             '<input type="submit" value="Загрузка руководство аспирантов">'.
             '</form></br>';
        echo '</center>';

        echo $OUTPUT->heading('Загрузка ИндР, ИстБол и др. для медицинского института.'. '<br />ВНИМАНИЕ! В первом столбце должен быть указан ID плана.', 4);
        echo '<br><center>';
        echo '<form method="post" enctype="multipart/form-data" action="syncpractice.php">';
        echo '<input type="hidden" name="action" value="upload"/>'.
             '<input type="hidden" name="variant" value="4"/>'.
             '<input type="hidden" name="error" value="' . $ignoreerror . '"/>'.
             '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
             '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">';
       	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
        echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
        echo '<input type="file" name="newfile" size="50" value="sdfgsdfg"><br><br>'.
             '<input type="submit" value="Загрузка ИндР, ИстБол и др.">'.
             '</form></br>';
        echo '</center>';

    }
                    
    echo $OUTPUT->footer();


function sync_practice($filename, $fid, $yid)   
{   
    global $CFG, $DB, $OUTPUT, $ignoreerror; 

    $asubdepartments = get_subdepartments_menu($yid);
    $kvalif_ref = $DB->get_records_menu('bsu_tskvalifspec', null, '', 'KvalifKod, idKvalif');
    
    $text = file($filename);
	if($text == FALSE){
		error(get_string('errorfile', 'block_monitoring'), $redirlink);
	}
	$size = sizeof($text);

    $textlib = new textlib();
  	for($i=0; $i < $size; $i++)  {
		$text[$i] = $textlib->convert($text[$i], 'win1251');
    }   
    unset ($textlib);
    
    // print_object($text);

    $kafedri = array();
    $notfound = array();
    $kafedra = '';
    $kafid = $specyalid = $p = $errors = 0;
    $practice = array();
    
    for($i = 1; $i < $size; $i++)  {
        
        $data = explode (';', $text[$i]);
        $data0 = trim($data[0]);
        if (empty($data0)) {
            notify('Не задан ID плана в строке '. ($i+1) . '. Строка не анализируется.');
            continue;
        }
        // print_object($data);
        
        if (analyse_shifr_subdeps($i, 8, $data, $asubdepartments)) continue;
       
        if ($data0 > 0)     {
            $sql = "id in ($data0) and departmentcode=$fid";
        } else continue;    
        
       
        if ($plans = $DB->get_records_select('bsu_plan', $sql, null, '', 'id, name, edformid'))  {

            foreach ($plans as $plan)    {
                $edformid = $plan->edformid;
                $terms = trim ($data[6]);
                $terms = explode (',', $terms);
                foreach ($terms as $term)   {                
                    
                    $p++;
                    
                    // print_object($plan);
                    $practice[$p] = new stdClass();
                    // $practice[$i]->data = $data;
                    // $practice[$i]->plan = current($plans);
                    $practice[$p]->planid = $plan->id;
                    $practice[$p]->name = trim($data[5]);
                    if (empty ($practice[$p]->name)) {
                       $practice[$p]->name = 'практика'; 
                    }
                    $practice[$p]->term = $term;
                    $practice[$p]->week = str_replace(',', '.', $data[7]);
                    
                    $data4 = trim($data[4]);
                    $sql = "edformid = $edformid AND name like '$data4'";
                    if ($practicetype = $DB->get_record_select('bsu_ref_practice_type', $sql, null, 'id, name'))    {
                        $practice[$p]->practicetypeid = $practicetype->id;
                        $practice[$p]->practicetypename = $practicetype->name;
                        echo $OUTPUT->notification('Строка '. ($i+1) . '. Тип практики определен: ' . $practicetype->name, 'notifysuccess');
                    } else if ($practicetype = $DB->get_record_select('bsu_ref_practice_type', "name like '$data4'", null, 'id, name'))    {
                        $practice[$p]->practicetypeid = $practicetype->id;
                        $practice[$p]->practicetypename = $practicetype->name;
                        echo $OUTPUT->notification('Строка '. ($i+1) . '. Тип практики определен: ' . $practicetype->name, 'notifysuccess');
                    } else {     
                        echo $OUTPUT->notification('Строка '. ($i+1) . '. Тип практики не определен: ' . $data4);
                        $errors++;
                        $practice[$p]->practicetypeid = 0;
                    }    
                    
                    $practice[$p]->subdeps = array();
                    // $practice_subdeps = array($data[8], $data[9], $data[10], $data[11], $data[12], $data[12]);
                    $practice_subdeps = array();
                    for  ($k=8; $k<=13; $k++)   {
                       if (isset($data[$k]))   {
                            $datak = trim($data[$k]);
                        } else {
                            continue;
                        }    
                        if (!empty($datak))    {
                            $practice_subdeps[] = $data[$k];
                        }
                    }
    
                    $j = 1;
                    $subdepsids = array();
                    foreach ($practice_subdeps as $subdep) {
                        if (!empty($subdep))    {
                            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                            $index = (int)$index0;
                            // echo $index . '<br>'; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $practice[$p]->subdeps[$j] = new stdClass();                            
                                $practice[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index]; 
                                $j++;
                                $subdepsids[] = $asubdepartments[$index];
                            } 
                        }    
                    } 
                    
                    $strsubdeps = implode(',', $subdepsids); 
                    echo $OUTPUT->notification('Строка '. ($i+1) . ": план  $plan->id; семестр $term; тип практики {$practice[$p]->practicetypeid}; кафедра(ы) $strsubdeps.", 'notifysuccess');
                }                
            }
        } else {
            echo $OUTPUT->notification('Строка '. ($i+1) . ": план с ID in ($data0) на  факультете $fid не найден.");
            $errors++;
        }          
    }
    
    // print_object($practice);
    create_practice($practice);
   
   
    return;
}    



function check_kavichki($discname)
{
    $returnstr = trim($discname);

    if ($returnstr[0] == '"')    {
        $returnstr = mb_substr($discname, 1, -1, 'UTF-8');
        $returnstr = str_ireplace('""', '"', $returnstr);
        // echo $returnstr . '<br>';
    } else {
        // notify('!!! ' . $discname . '!');
    }
    
    return $returnstr;
}


function create_practice($practices)  
{     
    global $DB;
   
    foreach ($practices as $practice)   {
        // $practiceid = 0;
        // print_object($practice);
        if ($existpractices = $DB->get_records_select('bsu_plan_practice', "edworkkindid=13 AND planid=$practice->planid AND practicetypeid=$practice->practicetypeid  
                                        AND name='$practice->name' AND term=$practice->term AND week=$practice->week"))    {
            foreach ($existpractices as $existpractice) {                                      
                notify ('Практика "' . $existpractice->name . '" уже существует в плане ' . $existpractice->planid);
                foreach ($practice->subdeps as $practice_subdep) {
                    $practice_subdep->practiceid = $existpractice->id; // id, yearid, practiceid, subdepartmentid, countsubgroups, countstudents
                    if (!$DB->record_exists_select('bsu_plan_practice_subdep', "practiceid = $practice_subdep->practiceid AND subdepartmentid=$practice_subdep->subdepartmentid"))  { 
                        if ($DB->insert_record('bsu_plan_practice_subdep', $practice_subdep)) {
                            notify ('Добавлена кафедра для практики: ' . $practice_subdep->subdepartmentid, 'notifysuccess');
                        } else {
                            print_object($practice_subdep);
                            notify('Error inserting bsu_plan_practice_subdep.');
                        }
                    }    
                }
            }            
        } else {    
            
            if ($practiceid = $DB->insert_record('bsu_plan_practice', $practice)) {
                notify ('Добавлена практика: ' . $practice->name . ' в план ' . $practice->planid, 'notifysuccess');
            } else {
                print_object($practice);
                notify('Error inserting bsu_plan_practice.');
                continue;
            }
           
            foreach ($practice->subdeps as $practice_subdep) {
                $practice_subdep->practiceid = $practiceid; 
                if ($DB->insert_record('bsu_plan_practice_subdep', $practice_subdep)) {
                    notify ('Добавлена кафедра для практики: ' . $practice_subdep->subdepartmentid, 'notifysuccess');
                } else {
                    print_object($practice_subdep);
                    notify('Error inserting bsu_plan_practice_subdep.');
                }
            }        
        } 
    }
    
}    



function sync_specvidrabot($filename, $fid, $yid)   
{   
    global $CFG, $DB, $OUTPUT; 

    $asubdepartments = get_subdepartments_menu();
    $kvalif_ref = $DB->get_records_menu('bsu_tskvalifspec', null, '', 'KvalifKod, idKvalif');
    $edworkkind = $DB->get_records_menu('bsu_ref_edworkkind', null, '', 'name, id');
    $edworkkind['КОНСгак'] = 10; 
    
    $text = file($filename);
	if($text == FALSE){
		error(get_string('errorfile', 'block_monitoring'), $redirlink);
	}
	$size = sizeof($text);

    $textlib = new textlib();
  	for($i=0; $i < $size; $i++)  {
		$text[$i] = $textlib->convert($text[$i], 'win1251');
    }   
    unset ($textlib);
    
    // print_object($text);

    $kafedri = array();
    $notfound = array();
    $kafedra = '';
    $kafid = $specyalid = $p = $errors = 0;
    $practice = array();
    
    for($i = 1; $i < $size; $i++)  {
        
        $data = explode (';', $text[$i]);
        $data0 = trim($data[0]);
        if (empty($data0)) {
            notify('Не задан ID плана в строке '. ($i+1) . '. Строка не анализируется.');
            continue;
        }
        // print_object($data);
        
        $data4 = trim($data[4]);
        if (!isset($edworkkind[$data4]))    {
            notify("Неизвестный вид работы '$data4' в строке ". ($i+1) . '. Строка не анализируется.');
            continue;
        }   
        $edworkkindid = $edworkkind[$data4];
        
  
        // print_object($practice_subdeps);
        if (analyse_shifr_subdeps($i, 7, $data, $asubdepartments)) continue;
                        
        // print_object($specyals);
        if ($data0 > 0)     {
              $sql = "id in ($data0) and departmentcode=$fid";
        }    
        // echo  $sql;
        if ($plans = $DB->get_records_select('bsu_plan', $sql, null, '', 'id, name, edformid'))  {

            foreach ($plans as $plan)    {
                $edformid = $plan->edformid;
                $terms = trim ($data[6]);
                $terms = explode (',', $terms);
                foreach ($terms as $term)   {                
                    $p++;
                    // print_object($plan);
                    $specvidrabot[$p] = new stdClass();
                    $specvidrabot[$p]->planid = $plan->id;
                    $specvidrabot[$p]->edworkkindid = $edworkkindid;
                    $specvidrabot[$p]->name = trim($data[5]);
                    if (empty ($specvidrabot[$p]->name)) {
                       $specvidrabot[$p]->name = '-'; 
                    }
                    $specvidrabot[$p]->term = $term;
                    $specvidrabot[$p]->week = 0;
                    $specvidrabot[$p]->practicetypeid = 0;
                    
                    $specvidrabot[$p]->subdeps = array();
                    
                    $practice_subdeps = array();
                    for  ($k=7; $k<=12; $k++)   {
                        if (isset($data[$k]))   {
                            $datak = trim($data[$k]);
                        } else {
                            continue;
                        }    
                        if (!empty($datak))    {
                            $practice_subdeps[] = $data[$k];
                        }
                    }
                    $j = 1;
                    $subdepsids = array();
                    foreach ($practice_subdeps as $subdep) {
                        if (!empty($subdep))    {
                            $index0 = mb_substr($subdep, 0, 8);
                            $index = (int)$index0;
                            // echo $index . '<br>'; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $specvidrabot[$p]->subdeps[$j] = new stdClass();                            
                                $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index]; 
                                $j++;
                                $subdepsids[] = $asubdepartments[$index];
                            } 
                        }    
                    } 
                    $strsubdeps = implode(',', $subdepsids); 
                    echo $OUTPUT->notification('Строка '. ($i+1) . ": план  $plan->id; семестр $term; вид работы $edworkkindid; кафедра(ы) $strsubdeps.", 'notifysuccess');
                    
                }                
            }
        } else {
            echo $OUTPUT->notification('Строка '. ($i+1) . ": план с ID in ($data0) на  факультете $fid не найден.");
            $errors++;
        }          
    }
    
    // print_object($specvidrabot);
    create_specvidrabot($specvidrabot);  
    return;
} 



function create_specvidrabot($specvidrabots, $ischeckexistrecod=true)  
{     
    global $DB;
   
    foreach ($specvidrabots as $specvidrabot)   {
        // $practiceid = 0;
        // print_object($practice);
        if ($ischeckexistrecod) {
            $flag = $DB->record_exists_select('bsu_plan_practice', "planid=$specvidrabot->planid AND edworkkindid=$specvidrabot->edworkkindid
                                               AND name='$specvidrabot->name'  AND term=$specvidrabot->term");
        } else {
            $flag = false;
        }

        if (!$flag) {    
            if ($id = $DB->insert_record('bsu_plan_practice', $specvidrabot)) {
                notify ('Добавлен спец.вида работы: ' . $specvidrabot->name . ' в план ' . $specvidrabot->planid, 'notifysuccess');
            } else {
                print_object($specvidrabot);
                notify('Error inserting bsu_plan_practice.');
                continue;
            }
           
            foreach ($specvidrabot->subdeps as $practice_subdep) {
                $practice_subdep->practiceid = $id; 
                if ($DB->insert_record('bsu_plan_practice_subdep', $practice_subdep)) {
                    notify ('Добавлена кафедра для спец.вида работы: ' . $practice_subdep->subdepartmentid, 'notifysuccess');
                } else {
                    print_object($practice_subdep);
                    notify('Error inserting bsu_plan_practice_subdep.');
                }
            }        
        } else {
            notify ('Практика "' . $specvidrabot->name . '" уже существует в плане ' . $specvidrabot->planid);
        }
    }
    
}       


function sync_aspirant($filename, $fid, $yid)   
{   
    global $CFG, $DB, $OUTPUT; 

    $asubdepartments = array();
    $asubdepartmentcodes = array();
    if ($subdepartments = $DB->get_records('bsu_vw_ref_subdepartments', array('yearid'=>$yid)))    {
        // print_object($subdepartments);
        foreach ($subdepartments as $subdepartment) {
            $index0 = mb_substr($subdepartment->name, 0, SHIFR_KAF_LENGTH);
            $index = (int)$index0;
            // echo $index . '<br>'; 
            $asubdepartments[$index] = $subdepartment->id;
            $asubdepartmentcodes[$subdepartment->id] = $index0;      
        } 
    } else {
        echo 'Not found!';
    }
    
    // print_object($asubdepartments);
    // exit();

    $kvalif_ref = $DB->get_records_menu('bsu_tskvalifspec', null, '', 'KvalifKod, idKvalif');
    $edworkkind = $DB->get_records_menu('bsu_ref_edworkkind', null, '', 'name, id');
    
    $text = file($filename);
	if($text == FALSE){
		error(get_string('errorfile', 'block_monitoring'), $redirlink);
	}
	$size = sizeof($text);

    $textlib = new textlib();
  	for($i=0; $i < $size; $i++)  {
		$text[$i] = $textlib->convert($text[$i], 'win1251');
    }   
    unset ($textlib);
    
    // print_object($text);

    $kafedri = array();
    $notfound = array();
    $kafedra = '';
    $kafid = $specyalid = $p = $errors = 0;
    $specvidrabot = array();
    
    for($i = 1; $i < $size; $i++)  {
        
        $data = explode (';', $text[$i]);
        
        // id plana
        $data0 = trim($data[0]); // id plana
        if (empty($data0)) continue;
        // print_object($data);
        
        // print_object($specyals);
        if ($data0 > 0)     {
              $sql = "id in ($data0)";
        } 
        // echo  $sql;
        if ($plans = $DB->get_records_select('bsu_plan', $sql, null, '', 'id, name'))  {
            echo $OUTPUT->notification('План найден ' . $data0, 'notifysuccess');
            foreach ($plans as $plan)    {
                
                
                // аспРУКВ докРУКВ соискРУКВ 
                $data1 = trim($data[1]);
                if (!isset($edworkkind[$data1]))    {
                    notify ("Неопределенный вид нагрузки: $data4");
                    continue;  
                }
                $edworkkindid = $edworkkind[$data1];

                // курс    
                $data2 = trim($data[2]);
                $term = 2*$data2 - 1;
                  
                
                switch ($edworkkindid)  {
                    case 24: // аспирантов РФ
                        // Количество аспирантов РФ
                        $cntstud = trim($data[4]);
                        if ($cntstud > 0) {
                            $p++;
                            $specvidrabot[$p] = new stdClass();
                            $specvidrabot[$p]->yearid = $yid;
                            $specvidrabot[$p]->planid = $plan->id;
                            $specvidrabot[$p]->practicetypeid  = 1; // аспиаранты РФ 
                            $specvidrabot[$p]->edworkkindid = $edworkkindid;
                            $specvidrabot[$p]->name = 'Руководство аспирантами РФ (' . trim($data[3]) . ')' ;
                            $specvidrabot[$p]->term = $term;
                            $specvidrabot[$p]->week = 0;
                            $specvidrabot[$p]->subdeps = array();
                            $subdep = trim($data[7]);
                            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                            $index = (int)$index0;
                            // echo $index . '<br>';
                            $j=0; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $specvidrabot[$p]->subdeps[$j] = new stdClass();  
                                $specvidrabot[$p]->subdeps[$j]->yearid = $yid;                          
                                $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index];
                                $specvidrabot[$p]->subdeps[$j]->countstudents = $cntstud; 
                                $specvidrabot[$p]->subdeps[$j]->hours = $cntstud*50; 
                                $j++;
                            }  else {
                                echo $OUTPUT->notification('Не найдена кафедра для  ' . $data0);
                            }
                        }    
        
        
                        // Количество аспирантов СНГ
                        $cntstud = trim($data[5]);
                        if ($cntstud > 0) {
                            $p++;
                            $specvidrabot[$p] = new stdClass();
                            $specvidrabot[$p]->yearid = $yid;
                            $specvidrabot[$p]->planid = $plan->id;
                            $specvidrabot[$p]->practicetypeid  = 2; // аспиаранты РФ 
                            $specvidrabot[$p]->edworkkindid = $edworkkindid;
                            $specvidrabot[$p]->name = 'Руководство аспирантами СНГ (' . trim($data[3]) . ')';
                            $specvidrabot[$p]->term = $term;
                            $specvidrabot[$p]->week = 0;
                            $specvidrabot[$p]->subdeps = array();
                            $subdep = trim($data[7]);
                            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                            $index = (int)$index0;
                            // echo $index . '<br>';
                            $j=0; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $specvidrabot[$p]->subdeps[$j] = new stdClass(); 
                                $specvidrabot[$p]->subdeps[$j]->yearid = $yid;                           
                                $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index];
                                $specvidrabot[$p]->subdeps[$j]->countstudents = $cntstud; 
                                $specvidrabot[$p]->subdeps[$j]->hours = $cntstud*75; 
                                $j++;
                            }  else {
                                echo $OUTPUT->notification('Не найдена кафедра для  ' . $data0);
                            }
                        }    
        
                        // Количество аспирантов иностранных
                        $cntstud = trim($data[6]);
                        if ($cntstud > 0) {
                            $p++;
                            $specvidrabot[$p] = new stdClass();
                            $specvidrabot[$p]->yearid = $yid;
                            $specvidrabot[$p]->planid = $plan->id;
                            $specvidrabot[$p]->practicetypeid  = 3; // аспиаранты РФ 
                            $specvidrabot[$p]->edworkkindid = $edworkkindid;
                            $specvidrabot[$p]->name = 'Руководство иностранными аспирантами (' . trim($data[3]) . ')';
                            $specvidrabot[$p]->term = $term;
                            $specvidrabot[$p]->week = 0;
                            $specvidrabot[$p]->subdeps = array();
                            $subdep = trim($data[7]);
                            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                            $index = (int)$index0;
                            // echo $index . '<br>';
                            $j=0; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $specvidrabot[$p]->subdeps[$j] = new stdClass();
                                $specvidrabot[$p]->subdeps[$j]->yearid = $yid;                            
                                $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index];
                                $specvidrabot[$p]->subdeps[$j]->countstudents = $cntstud; 
                                $specvidrabot[$p]->subdeps[$j]->hours = $cntstud*100; 
                                $j++;
                            }  else {
                                echo $OUTPUT->notification('Не найдена кафедра для  ' . $data0);
                            }
                        }
                 break;       
                
                 case 34:  // докторанты
                    $cntstud = trim($data[4]);
                    if ($cntstud > 0) {
                        $p++;
                        $specvidrabot[$p] = new stdClass();
                        $specvidrabot[$p]->yearid = $yid;
                        $specvidrabot[$p]->planid = $plan->id;
                        $specvidrabot[$p]->practicetypeid  = 1; // аспиаранты РФ 
                        $specvidrabot[$p]->edworkkindid = $edworkkindid;
                        $specvidrabot[$p]->name = 'Руководство докторантами (' . trim($data[3]) . ')' ;
                        $specvidrabot[$p]->term = $term;
                        $specvidrabot[$p]->week = 0;
                        $specvidrabot[$p]->subdeps = array();
                        $subdep = trim($data[7]);
                        $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                        $index = (int)$index0;
                        // echo $index . '<br>';
                        $j=0; 
                        if (!empty($index) && isset($asubdepartments[$index])) {
                            $specvidrabot[$p]->subdeps[$j] = new stdClass();
                            $specvidrabot[$p]->subdeps[$j]->yearid = $yid;                            
                            $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index];
                            $specvidrabot[$p]->subdeps[$j]->countstudents = $cntstud; 
                            $specvidrabot[$p]->subdeps[$j]->hours = $cntstud*50; 
                            $j++;
                        }  else {
                            echo $OUTPUT->notification('Не найдена кафедра для  ' . $data0);
                        }
                    }
                 break;
                 
                 case 44: // соискатели
                 
                        $cntstud = trim($data[4]);
                        if ($cntstud > 0) {
                            $p++;
                            $specvidrabot[$p] = new stdClass();
                            $specvidrabot[$p]->yearid = $yid;
                            $specvidrabot[$p]->planid = $plan->id;
                            $specvidrabot[$p]->practicetypeid  = 11; // соискатели РФ 
                            $specvidrabot[$p]->edworkkindid = $edworkkindid;
                            $specvidrabot[$p]->name = 'Руководство соискателями РФ (' . trim($data[3]) . ')' ;
                            $specvidrabot[$p]->term = $term;
                            $specvidrabot[$p]->week = 0;
                            $specvidrabot[$p]->subdeps = array();
                            $subdep = trim($data[7]);
                            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                            $index = (int)$index0;
                            // echo $index . '<br>';
                            $j=0; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $specvidrabot[$p]->subdeps[$j] = new stdClass();
                                $specvidrabot[$p]->subdeps[$j]->yearid = $yid;                            
                                $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index];
                                $specvidrabot[$p]->subdeps[$j]->countstudents = $cntstud; 
                                $specvidrabot[$p]->subdeps[$j]->hours = $cntstud*25; 
                                $j++;
                            }  else {
                                echo $OUTPUT->notification('Не найдена кафедра для  ' . $data0);
                            }
                        }    
        
        
                        // Количество аспирантов СНГ
                        $cntstud = trim($data[5]);
                        if ($cntstud > 0) {
                            $p++;
                            $specvidrabot[$p] = new stdClass();
                            $specvidrabot[$p]->yearid = $yid;
                            $specvidrabot[$p]->planid = $plan->id;
                            $specvidrabot[$p]->practicetypeid  = 12; // соискатели СНГ 
                            $specvidrabot[$p]->edworkkindid = $edworkkindid;
                            $specvidrabot[$p]->name = 'Руководство сискателями СНГ (' . trim($data[3]) . ')';
                            $specvidrabot[$p]->term = $term;
                            $specvidrabot[$p]->week = 0;
                            $specvidrabot[$p]->subdeps = array();
                            $subdep = trim($data[7]);
                            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                            $index = (int)$index0;
                            // echo $index . '<br>';
                            $j=0; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $specvidrabot[$p]->subdeps[$j] = new stdClass();
                                $specvidrabot[$p]->subdeps[$j]->yearid = $yid;                            
                                $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index];
                                $specvidrabot[$p]->subdeps[$j]->countstudents = $cntstud; 
                                $specvidrabot[$p]->subdeps[$j]->hours = $cntstud*37.5; 
                                $j++;
                            }  else {
                                echo $OUTPUT->notification('Не найдена кафедра для  ' . $data0);
                            }
                        } 
                   break;        
                }       
            }
        } else {
            echo $OUTPUT->notification('План не найден для специальности ' . $data2);
            $errors++;
        }          
        
 
    }
    
    // print_object($specvidrabot);
    create_specvidrabot($specvidrabot, false); //  
    
    
    return;
} 



function sync_mediki($filename, $fid, $yid)   
{   
    global $CFG, $DB, $OUTPUT; 

    $asubdepartments = array();
    $asubdepartmentcodes = array();
    if ($subdepartments = $DB->get_records('bsu_vw_ref_subdepartments', array('yearid'=>$yid)))    {
        // print_object($subdepartments);
        // print_object($subdepartments);
        foreach ($subdepartments as $subdepartment) {
            $index0 = mb_substr($subdepartment->name, 0, SHIFR_KAF_LENGTH);
            $index = (int)$index0;
            // echo $index . '<br>'; 
            $asubdepartments[$index] = $subdepartment->id;
            $asubdepartmentcodes[$subdepartment->id] = $index0;      
        } 
    } else {
        echo 'Not found!';
    }

    $edworkkind = $DB->get_records_menu('bsu_ref_edworkkind', null, '', 'name, id');
 
    $text = file($filename);
	if($text == FALSE){
		error(get_string('errorfile', 'block_monitoring'), $redirlink);
	}
	$size = sizeof($text);

    $textlib = new textlib();
  	for($i=0; $i < $size; $i++)  {
		$text[$i] = $textlib->convert($text[$i], 'win1251');
    }   
    unset ($textlib);
    
    // print_object($text);

    $kafedri = array();
    $notfound = array();
    $kafedra = '';
    $kafid = $specyalid = $p = $errors = 0;
    $specvidrabot = array();
    
    for($i = 1; $i < $size; $i++)  {
        
        $data = explode (';', $text[$i]);
        
        // id plana
        $planid = trim($data[0]); // id plana
        if (empty($planid)) continue;
        // print_object($data);
        
        // print_object($specyals);
        if ($planid > 0)     {
              $sql = "id in ($planid)";
        } 

        // echo  $sql;
        if ($plans = $DB->get_records_select('bsu_plan', $sql, null, '', 'id, name'))  {
            
            echo $OUTPUT->notification('План найден ' . $planid, 'notifysuccess');
            
            foreach ($plans as $plan)    {
                
                $strgroups = get_plan_groups($plan->id);
                if (empty($strgroups)) continue;
                $agroups = explode ('<br>', $strgroups);
   
                $terms = get_terms_group($yid, $agroups);
                // $countstudents = get_count_students_groups($agroups);                
                
                // цикл    
                $cyclename = trim($data[1]);
                $discname = trim($data[2]);
                $term = trim($data[3]);

                $cntgrup = $cntstud = 0;
                foreach ($agroups as $group)    {
                    if (in_array($term, $terms[$group] ))   {
                        $cntgrup++;
                        
                        $groupid = (int)$group;
                        $cntstud += get_count_students_group($groupid);
                    }
                }                        
                
               
                if ($disciplinenameid = $DB->get_field_select('bsu_ref_disciplinename', 'id', "name = '$discname'")) {
                    notify ("Имя дисциплины $discname найдено в справочнике", 'notifysuccess');
                }  else {
                    notify ("Имя дисциплины $discname не найдено в справочнике");
                    continue;
                }               
                // ИндР
                $edw = trim($data[4]);
                if (!isset($edworkkind[$edw]))    {
                    notify ("Неопределенный вид нагрузки: $edw");
                    continue;  
                }
                $edworkkindid = $edworkkind[$edw];
                
                //if ($edworkkindid == 15) continue;
                
                switch ($edworkkindid)  {
                    case 15: // ИндР    
                            
                            
                            $sql = "planid = $plan->id AND identificatordiscipline = '$cyclename'"; //   and  disciplinenameid = $disciplinenameid";
                            echo $sql;        
                            if ($discipline = $DB->get_record_select('bsu_discipline', $sql, null, 'id'))   {
                                if (!$hours = $DB->get_field_select('bsu_discipline_semestr', 'praktika', "disciplineid = $discipline->id and numsemestr = $term"))  {
                                    if (!$hours = $DB->get_field_select('bsu_discipline_semestr', 'lab', "disciplineid = $discipline->id and numsemestr = $term"))  {
                                        notify ("Не найдено количество часов по практике (или по л.р.) в disciplineid = $discipline->id and numsemestr = $term");
                                    }    
                                }  
                            } else {
                                notify ("Дисциплина не найдена!!! ($sql)");
                                break;
                            }
                        
                            $p++;
                            $specvidrabot[$p] = new stdClass();
                            $specvidrabot[$p]->planid = $plan->id;
                            $specvidrabot[$p]->practicetypeid  = 0; // 
                            $specvidrabot[$p]->edworkkindid = $edworkkindid;
                            $specvidrabot[$p]->name = $discname . '(' .$cyclename . ')' ;
                            $specvidrabot[$p]->term = $term;
                            $specvidrabot[$p]->week = 0;
                            $specvidrabot[$p]->ahours = $hours;
                            $specvidrabot[$p]->subdeps = array();
                            $subdep = trim($data[5]);
                            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                            // $index0 = new_shifr_kaf_from_old_shifr($subdep, $asubdepartments);
                            $index = (int)$index0;
                            // echo $index . '<br>';
                            $j=0; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $specvidrabot[$p]->subdeps[$j] = new stdClass(); 
                                $specvidrabot[$p]->subdeps[$j]->yearid = $yid;                            
                                $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index];
                                $specvidrabot[$p]->subdeps[$j]->countstudents = $cntstud; // $countstudents 
                                $specvidrabot[$p]->subdeps[$j]->countsubgroups = $cntgrup;
                                $specvidrabot[$p]->subdeps[$j]->hours = $cntgrup*$hours*0.2; 
                                $j++;
                            }  else {
                                echo $OUTPUT->notification('Не найдена кафедра для  ' . $planid);
                            }
                 break;       
                
                 case 41:  // ИстБол
                           $p++;
                            $specvidrabot[$p] = new stdClass();
                            $specvidrabot[$p]->planid = $plan->id;
                            $specvidrabot[$p]->practicetypeid  = 0; // 
                            $specvidrabot[$p]->edworkkindid = $edworkkindid;
                            $specvidrabot[$p]->name = $discname . ' (' .$cyclename . ')' ;
                            $specvidrabot[$p]->term = $term;
                            $specvidrabot[$p]->week = 0;
                            $specvidrabot[$p]->ahours = 0;
                            $specvidrabot[$p]->subdeps = array();
                            $subdep = trim($data[5]);
                            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                            // $index0 = new_shifr_kaf_from_old_shifr($subdep, $asubdepartments);
                            $index = (int)$index0;
                            // echo $index . '<br>';
                            $j=0; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $specvidrabot[$p]->subdeps[$j] = new stdClass();
                                $specvidrabot[$p]->subdeps[$j]->yearid = $yid;                             
                                $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index];
                                $specvidrabot[$p]->subdeps[$j]->countstudents = $cntstud; // $countstudents 
                                $specvidrabot[$p]->subdeps[$j]->countsubgroups = $cntgrup;
                                $specvidrabot[$p]->subdeps[$j]->hours = $cntstud;  // 1 час на кол-во студентов
                                $j++;
                            }  else {
                                echo $OUTPUT->notification('Не найдена кафедра для  ' . $planid);
                            }

                 break;
                 
                 case 42: // ПрСуд
                 case 43: // ПрТрМ
                 
                           $p++;
                            $specvidrabot[$p] = new stdClass();
                            $specvidrabot[$p]->planid = $plan->id;
                            $specvidrabot[$p]->practicetypeid  = 0; // 
                            $specvidrabot[$p]->edworkkindid = $edworkkindid;
                            $specvidrabot[$p]->name = $discname . ' (' .$cyclename . ')' ;
                            $specvidrabot[$p]->term = $term;
                            $specvidrabot[$p]->week = 0;
                            $specvidrabot[$p]->ahours = 0;
                            $specvidrabot[$p]->subdeps = array();
                            $subdep = trim($data[5]);
                            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
                            // $index0 = new_shifr_kaf_from_old_shifr($subdep, $asubdepartments);
                            $index = (int)$index0;
                            // echo $index . '<br>';
                            $j=0; 
                            if (!empty($index) && isset($asubdepartments[$index])) {
                                $specvidrabot[$p]->subdeps[$j] = new stdClass();
                                $specvidrabot[$p]->subdeps[$j]->yearid = $yid;                             
                                $specvidrabot[$p]->subdeps[$j]->subdepartmentid = $asubdepartments[$index];
                                $specvidrabot[$p]->subdeps[$j]->countstudents = $cntstud; // $countstudents 
                                $specvidrabot[$p]->subdeps[$j]->countsubgroups = $cntgrup;
                                $specvidrabot[$p]->subdeps[$j]->hours = $cntstud*0.5;  // 0,5 час на кол-во студентов
                                $j++;
                            }  else {
                                echo $OUTPUT->notification('Не найдена кафедра для  ' . $planid);
                            }
                 
                 break;        
                }       
            }
        } else {
            echo $OUTPUT->notification('План не найден для специальности ' . $data2);
            $errors++;
        }          
        
 
    }
    
    // print_object($specvidrabot);
    create_specvidrabot($specvidrabot);   
    
    return;
} 


function get_subdepartments_menu($yid)
{
    global $DB;
    
    $asubdepartments = array();
    if ($subdepartments = $DB->get_records_select('bsu_vw_ref_subdepartments', "id>1 and yearid=$yid"))    {
        // print_object($subdepartments);"))    {
        foreach ($subdepartments as $subdepartment) {
            $index0 = mb_substr($subdepartment->name, 0, SHIFR_KAF_LENGTH);
            $index = (int)$index0;
            $asubdepartments[$index] = $subdepartment->id;
        } 
    } else {
        echo 'Not found!';
    }
    
    return $asubdepartments;
}


function analyse_shifr_subdeps($i, $kstart,  $data, $asubdepartments)
{        
    // print_object($asubdepartments);
    
    $iscontinue = false;
    $practice_subdeps = array();    
    for  ($k=$kstart; $k<=12; $k++)   {
        if (isset($data[$k]))   {
            $datak = trim($data[$k]);
        } else {
            $datak = '';
            continue;
        }    
        if (!empty($datak))    {
            $subdep = $datak;
            $index0 = mb_substr($subdep, 0, SHIFR_KAF_LENGTH);
            
            $index = (int)$index0;
            // echo $index . '<br>'; 
            if (empty($index))  {
                $iscontinue = true;
                notify("Не задан код кафедры $index0 в строке ". ($i+1) . '. Строка не анализируется.');       
            }    
            if (!isset($asubdepartments[$index])) {
                notify("Неизвестный код кафедры $index0 в строке ". ($i+1) . '. Строка не анализируется.');
                $iscontinue = true;
            }  else {
                $practice_subdeps[] = $asubdepartments[$index];
            } 
            if ($index == 0)    {
                notify("Нулевой код кафедры в строке ". ($i+1) . '. Строка не анализируется.');
                $iscontinue = true;
            } 
        }
    }

    if (!$iscontinue)   { 
        if (empty($practice_subdeps)) {
            notify("Не задан ни один действительный код кафедры в строке ". ($i+1) . '. Строка не анализируется.');
            $iscontinue = true;
        }
    }    

    return $iscontinue;
}            


function new_shifr_kaf_from_old_shifr($subdep, $asubdepartments)
{
    global $DB;
    
    $newindex = 0;
    $oldindex = mb_substr($subdep, 0, 6);    
    $index = (int)$oldindex;
    if (!empty($index) && isset($asubdepartments[$index])) {
        $oldsubdepartmentid = $asubdepartments[$index];
        $newsubdep = $DB->get_field_select('bsu_vw_ref_subdepartments', 'name', "id2 = $oldsubdepartmentid");
        $newindex = mb_substr($newsubdep, 0, 8);
    }                
    echo  "<br />$oldindex ==> $newindex<br />";        
    return $newindex;
}
?>