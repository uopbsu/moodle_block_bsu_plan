<?php   // $Id: disciplines.php,v 1.8 2012/10/20 12:29:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");    
    require_once("../../bsu_charge/lib_charge.php");
    require_once("../../bsu_charge/lib_charge_spec.php");
    require_once("../lib_report.php");
    require_once("../import/lib_import.php");
    require_once("lib_disciplines.php");

    $yid = optional_param('yid', 0, PARAM_INT);                 // ed yearid
    $fid = optional_param('fid', 0, PARAM_INT);					// departmentcode
    $pid = optional_param('pid', 0, PARAM_INT);                 // plan id
    $term = optional_param('term', 0, PARAM_INT);
    $did = optional_param('did', 0, PARAM_INT);					// discipline  id
    $action = optional_param('action', '', PARAM_ACTION);		// action
    $delta = optional_param('delta', 1, PARAM_INT);
    $tab = optional_param('tab', 'plan', PARAM_TEXT);
    $confirm = optional_param('confirm', 0, PARAM_INT);			// confirm
    $sid = optional_param('sid', 0, PARAM_TEXT);				// subdepartment id через ','

    if ($yid == 0)   {
        $yid = get_current_edyearid();
    }

/*    
	if($action == 'subdep') {
		$d = $DB->get_record_sql("SELECT disciplinenameid FROM {bsu_discipline} WHERE id=$did");
		$specialityid = $DB->get_record_sql("SELECT specialityid FROM {bsu_plan} WHERE id=$pid");
		
		$subdep = new stdClass();
		$subdep->yearid = $yid;
		$subdep->disciplineid = $did;
		$subdep->disciplinenameid = $d->disciplinenameid;
		$subdep->subdepartmentid = $sid;
		$subdep->specialityid = $specialityid->specialityid;
		$subdep->timemodified = time();
		$subdep->usermodified = $USER->id;
		
		$verify = $DB->get_records_sql("SELECT id FROM {bsu_discipline_subdepartment_zav} WHERE yearid=$yid AND disciplineid=$did AND
		       disciplinenameid=$d->disciplinenameid AND subdepartmentid IN ($sid) AND specialityid=$specialityid->specialityid");
		       
		if(!$verify) {
			$DB->insert_record('bsu_discipline_subdepartment_zav', $subdep);
		}
	}
/**/	
        
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    
    require_login();

    $time0 = time();
    $select = "editplan=1 and timestart<$time0 and timeend>$time0";
    $ACCESS_USER = $DB->get_records_select_menu('bsu_operator_dean', $select, null, '', 'id, userid');

    $isdelete = false;
    if($confirm == 1 && confirm_sesskey()) {
        if ($action == 'deldid')    {
            delete_disicpline_from_system($yid, $pid, $did, $term);
            $action = '';
        } else {
            $isdelete = $DB->delete_records_select('bsu_discipline_semestr', "disciplineid=$did AND numsemestr=$term");
            
            delete_form_kontrol_from_term($did, $term);
            
            $frm = new stdClass();
            $frm->yid = $yid;
            $frm->pid = $pid;
            $frm->did = $did;
            delete_charge_discipline_term($frm, $term);
            
            $disciplinenameid = $DB->get_field_select('bsu_discipline', 'disciplinenameid', "id = $did");
            $disname = $DB->get_field_select('bsu_ref_disciplinename', 'name', "id=$disciplinenameid");
            // add_to_log(1, 'discipline', 'delete semestr', "planid=$pid&disciplinenameid=$disciplinenameid&numsemestr=$term", $disname, $did, $USER->id);
            add_to_bsu_plan_log('discipline:delete semestr', $pid, $did, "planid=$pid&disciplinenameid=$disciplinenameid&numsemestr=$term", $disname);
        }    
        // $term = 99; 
        // redirect($redirlink, get_string('plandeleted', 'block_bsu_plan'), 0); 
    }

    switch ($action)    {   
        case 'excel': 
            $sql = "SELECT p.id, p.name as pname, s.Specyal  as sname, s.KodSpecyal as scode, p.kvalif 
                    FROM {bsu_plan} p
                    inner join {bsu_tsspecyal} s ON s.idSpecyal=p.specialityid
                    where p.id=$pid"; //  and g.groupid=$gid"; 
            // print $sql . '<br />';               
            if($plan = $DB->get_record_sql($sql)) {
                $showkaf = true;
                $strgroups = get_plan_groups($plan->id);
                if (!empty($strgroups)) {    
                    $agroups = explode ('<br>', $strgroups);
                }    
                if (!empty($agroups))   {   
                    $terms = get_terms_group($yid, $agroups);
                    foreach ($terms as $group => $t)  {                                            
                        if (in_array($term, $t)) $showkaf = true;  
                    }
                } else {
                    $agroups = array();
                }       
                
                if ($term == 101)   {
                    $CFG->stopeditingplan = false;
                    $table101 = table_specvidrabot0($yid, $fid, $plan, $agroups, $tab);
                    print_table_to_excel($table101, 1);
                } else if ($term == 100)   {
                    $CFG->stopeditingplan = false;
                    $table100 = table_practice0($yid, $fid, $plan, $agroups, $tab);
                    print_table_to_excel($table100, 1);
                } else  if ($term == 99)    {
                    $table = table_all_disciplines($yid, $fid, $plan, "\n");
                    print_table_to_excel($table, 1);
                } else {    
                    $CFG->stopeditingplan = false;
                    $table = table_disciplines($yid, $fid, $plan, $term, $agroups, $showkaf, $tab, 1);
                    print_table_to_excel($table, 1);
                }    
                exit();
            }
        break;        
        
        case 'semestrkontrol':
            $frm = data_submitted();
            // print_object($frm);
            $weekshours = array();
            foreach ($frm as $field => $value)  {
                $mask = explode('_', $field);
                switch($mask[0])  {
                    case 'minh': if (!isset($weekshours[$mask[1]])) $weekshours[$mask[1]] = new stdClass();
                                 $weekshours[$mask[1]]->minh = $value;
                    break;  
                    case 'maxh': $weekshours[$mask[1]]->maxh = $value;
                    break;  
                }
            }
            // print_object($weekshours);
            foreach ($weekshours as $iterm => $weekshour) {
                $weekshour->planid = $pid;
                $weekshour->term = $iterm;
                if ($week = $DB->get_record_select('bsu_plan_weeks_hours', "planid=$pid and term=$iterm"))   {
                    $weekshour->id = $week->id; 
                    $DB->update_record('bsu_plan_weeks_hours', $weekshour); 
                } else {
                    $DB->insert_record('bsu_plan_weeks_hours', $weekshour);
                }
            }
            $kvalif = $DB->get_field_select('bsu_plan', 'kvalif', "id = $pid");
            copy_weekshours_in_oop_plans($weekshours, $pid, $kvalif);
        break;

        case 'svod':
            $frm = data_submitted();
            // print_object($frm);
            
            $planzets = array();
            foreach ($frm as $field => $value)  {
                $mask = explode('~', $field);
                switch($mask[0])  {
                    case 'minzet': if (!isset($planzets[$mask[1]])) $planzets[$mask[1]] = new stdClass();
                                   $planzets[$mask[1]]->minzet = $value;
                    break;  
                    case 'maxzet': if (!isset($planzets[$mask[1]])) $planzets[$mask[1]] = new stdClass();
                                   $planzets[$mask[1]]->maxzet = $value;
                    break;  
                }
            }
            $kvalif = $DB->get_field_select('bsu_plan', 'kvalif', "id = $pid");
            switch ($kvalif)    {
                case 2: $fs = 'Б';
                        $cycles2 = array($fs.'1', $fs.'2', $fs.'3');
                break;
                case 3: $fs = 'С';
                        $cycles2 = array($fs.'1', $fs.'2', $fs.'3');
                break;
                case 4: $fs = 'М';
                        $cycles2 = array($fs.'1', $fs.'2');
                break;
                default: $fs = '';
                break;
            }
            
            /*
            foreach ($cycles2 as $cycle)    {
                $index1 = $cycle . '_Б_';
                $index2 = $cycle . '_В_';
                $planzets[$cycle] = new stdClass();
                $planzets[$cycle]->minzet = $planzets[$index1]->minzet + $planzets[$index2]->minzet; 
                $planzets[$cycle]->maxzet = $planzets[$index1]->maxzet + $planzets[$index2]->maxzet;
            } 
            */   
            // print_object($planzets);
                        
            foreach ($planzets as $cycle => $planzet) {
                $planzet->planid = $pid;
                $planzet->cyclename = $cycle;
                if ($week = $DB->get_record_select('bsu_plan_minmaxzet', "planid=$pid and cyclename='$cycle'"))   {
                    $planzet->id = $week->id; 
                    $DB->update_record('bsu_plan_minmaxzet', $planzet); 
                } else {
                    $DB->insert_record('bsu_plan_minmaxzet', $planzet);
                }
            }
            
            copy_planzets_in_oop_plans($planzets, $pid, $kvalif);       
        break;

        
        case 'hidemodules': hide_modules_in_plan($pid);
        break;      
    }

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = 'План';// get_string('disciplines', 'block_bsu_plan');

    $PAGE->set_title($strtitle3);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->requires->js('/blocks/bsu_plan/curriculums/graphicup.js', true);
    $PAGE->requires->js('/lib/jquery/js/jquery-1.9.1.js', true);
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
    if ($action == 'edit')  {
        $PAGE->navbar->add($strtitle3, new moodle_url("disciplines.php", array('fid' => $fid, 'pid' => $pid, 'term' => $term)));
        $PAGE->navbar->add(get_string($action));
    }  else {
        $PAGE->navbar->add($strtitle3);
    }
    echo $OUTPUT->header();

    if($confirm == 1 && $isdelete ) {
        echo $OUTPUT->notification('Дисциплина удалена в данном семестре.', 'notifysuccess');
    }    

    switch($action) {
        case 'shiftleft':  shift_discipline($fid, $pid, $delta);
        break;
        case 'shiftright': shift_discipline($fid, $pid, $delta);
        break;
    }            
            
    $redirlink = "disciplines.php?yid=$yid&pid=$pid&fid=$fid&term=$term&tab=$tab";
    
  
    switch($action) {
        // ========== РЕДАКТИРОВАНИЕ ДИСЦИПЛИНЫ
        // case 'add':
        case 'edit':
            $faculty = $DB->get_record_select('bsu_ref_department', "departmentcode = $fid", null, 'id, departmentcode, name, shortname');
            $plan = $DB->get_record_select('bsu_plan', "id=$pid", null, 'id, name');
            if ($action == 'edit')  {
                $discipline = $DB->get_record_sql("SELECT d.*, n.name as nname FROM {bsu_discipline} d 
                                                   INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                                                   WHERE d.id=$did");
                $semestr = $DB->get_record_select('bsu_discipline_semestr', "disciplineid=$did AND numsemestr=$term"); 
                unset($semestr->id);
                $array_discipline_semestr = (array)$discipline + (array)$semestr;
                $discipline_semestr = (object)$array_discipline_semestr;
                // print_object($discipline_semestr);
            } else {
                $discipline = new stdClass();
                $discipline->id = 0;
                $discipline->nname = '-'; 
                $discipline_semestr = new stdClass();
           		$discipline_semestr->term = $term;
                $discipline_semestr->action = $action;
            }                                                                  

            $dform = new discipline_form('disciplines.php');
            /*
            if (is_siteadmin()) {
       		   $dform = new discipline_form('disciplines.php');
            } else {   
               // $dform = new discipline_form_metodist('disciplines.php');
            }
            */
                  
       		$discipline_semestr->term = $term;
            $discipline_semestr->action = $action;
        	$dform->set_data($discipline_semestr);            
                        
            if ($dform->is_cancelled()) {
                redirect($redirlink, '', 0);
            } else {
                if ($dnew = $dform->get_data()) {
                    // print_object($dnew); exit();
         			$dnew->timemodified = time();
                    $dnew->modifierid = $USER->id;
        			if($dnew->id == 0) {
        			    // $agroupnew->id = (int)$agroupnew->name; 
                        $dnew->planid = $pid;
                        if ($discexist = $DB->get_record_select('bsu_discipline', "planid = $pid AND disciplinenameid=$dnew->disciplinenameid", null, 'id')) {
                            $dnew->disciplineid = $discexist->id;
                        } else {
            				$newid = $DB->insert_record('bsu_discipline', $dnew);
                            $dnew->disciplineid = $newid;
                        }
                        $dnew->numsemestr = $term; 
                        $DB->insert_record('bsu_discipline_semestr', $dnew);
        			} else {
        			    //  print_object($dnew);
        				$DB->update_record('bsu_discipline', $dnew);
                        // if (is_siteadmin()) {
                            $semestr = $DB->get_record_select('bsu_discipline_semestr', "disciplineid=$did AND numsemestr=$term");
                            $dnew->id = $semestr->id; 
                            $DB->update_record('bsu_discipline_semestr', $dnew);
                        // }    
                    }    
        			redirect($redirlink, get_string('changessaved'), 0);
        		}
            }    
    
    		echo '<table width="50%" align="center"><tr><td>';
    		$dform->display();
    		echo '</td></tr></table>';
            $sql = "SELECT FROM_UNIXTIME(d.timemodified, '%d.%m.%Y %h:%i') as timemodified, d.modifierid, concat (u.lastname, ' ', u.firstname) as fullname
                    FROM mdl_bsu_discipline d
                    inner join mdl_user u on u.id=d.modifierid
                    where d.id=$did";
            if ($whomodifierd = $DB->get_record_sql($sql))  {
                notify("Дисциплина редактировалась $whomodifierd->timemodified. Пользователь: $whomodifierd->fullname.", 'notifysuccess');
            }        
       break;


       // ===== УДАЛЕНИЕ СЕМЕСТРА ДИСЦИПЛИНЫ (вопрос) 
	   case 'delete':
                $discipline = $DB->get_record_sql("SELECT d.id, n.name as nname FROM {bsu_discipline} d 
                                                   INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                                                   WHERE d.id=$did");
                // $discipline->term = $term;                                                   
                $strdelete = get_string('deletediscipline', 'block_bsu_plan', $discipline->nname) . $term . '?<br /> <em>Замечание: при удалении также будет удалена и нагрузка по данной дисциплине в данном семестре.</em>';
                $redirlinkOK = $redirlink."&did=$did&confirm=1&sesskey=".sesskey();
                // echo  $redirlinkOK;
                echo $OUTPUT->confirm($strdelete, $redirlinkOK, $redirlink);
                
       
/*            if ($DB->record_exists_select('bsu_schedule', "disciplineid= $did"))   {
                // echo $OUTPUT->notification('Нельзя удалить план, на который подписаны группы. Необходимо сначала отписать группы.');
                notice('Нельзя удалить дисциплину, для которой уже создано расписание.', $redirlink);
            } else {
                $discipline = $DB->get_record_sql("SELECT d.id, n.name as nname FROM {bsu_discipline} d 
                                                   INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                                                   WHERE d.id=$did");
                // $discipline->term = $term;                                                   
                $strdelete = get_string('deletediscipline', 'block_bsu_plan', $discipline->nname) . $term . '?'; 
                echo $OUTPUT->confirm($strdelete, $redirlink."&did=$did&confirm=1&sesskey=".sesskey(), $redirlink);
            }
*/                
	   break;


       // ===== УДАЛЕНИЕ ДИСЦИПЛИНЫ (вопрос) 
	   case 'deldid':
            if (false) { // $DB->record_exists_select('bsu_schedule', "disciplineid = $did"))   {
                // echo $OUTPUT->notification('Нельзя удалить план, на который подписаны группы. Необходимо сначала отписать группы.');
                notice('Нельзя удалить дисциплину, для которой уже создано расписание.', $redirlink);
            } else {
                $discipline = $DB->get_record_sql("SELECT d.id, n.name as nname FROM {bsu_discipline} d 
                                                   INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                                                   WHERE d.id=$did");
                // $discipline->term = $term;                                                   
                $strdelete = get_string('deletealldiscipline', 'block_bsu_plan', $discipline->nname); 
                echo $OUTPUT->confirm($strdelete, $redirlink."&action=deldid&did=$did&tab=$tab&confirm=1&sesskey=".sesskey(), $redirlink."&tab=$tab");
            }    
	   break;
       
       
       // ===== УСТАНОВКА ФЛАЖКА notusing
       case 'notusing':
            // print "action=$action&yid=$yid&fid=$fid&pid=$pid&did=$did&term=$term&plantab=kurs3";
            $discipline = $DB->get_record_select('bsu_discipline', "id=$did", null, 'id, cyclename, notusing, disciplinenameid');
            $pos = mb_strpos($discipline->cyclename, 'ДВ', 0, 'UTF-8');
            $pos2 = mb_strpos($discipline->cyclename, '.В', 0, 'UTF-8');
            $pos3 = mb_strpos($discipline->cyclename, 'ФТД', 0, 'UTF-8');
            // $pos3 = mb_strpos($discipline->cyclename, '.В', 0, 'UTF-8');
            if (!($pos === false) || !($pos2 === false) || !($pos3 === false))  {
                $newnotusing = 1 - $discipline->notusing;
                // echo  $newnotusing . '<br />';
                if ($newnotusing == 0)   {
                    // добавить create_.....
                    $strgroups = get_plan_groups($pid);
                    if (!empty($strgroups)) {    
                        $agroups = explode ('<br>', $strgroups);
                        create_edworks_for_discipline($yid, $pid, $did, $agroups);
                    }                         
                } else if ($newnotusing == 1)   {
                    delete_discipline_charge($yid, $did, $pid);    
                }
                $newnotusing = 1 - $discipline->notusing;
                $DB->set_field_select('bsu_discipline', 'notusing', $newnotusing, "id=$did");
                $disname = $DB->get_field_select('bsu_ref_disciplinename', 'name', "id=$discipline->disciplinenameid");
                // add_to_log(1, 'discipline', 'notusing', "planid=$pid&disciplinenameid=$discipline->disciplinenameid&notusing=$newnotusing", $disname, $discipline->id, $USER->id);
                add_to_bsu_plan_log('discipline:notusing', $pid, $did, "planid=$pid&disciplinenameid=$discipline->disciplinenameid&notusing=$newnotusing", $disname);                               

            }   else {
                notify('Access denied.');
            } 
            
            
            
       // ===== ВЫВОД ТАБЛИЦЫ СО СПИСКОМ ДИСЦИПЛИН     
       default: 
            $scriptname = "disciplines.php";
            //$scriptname2 = "rupview.php?fid=$fid";
            $strlistfaculties =  listbox_department($scriptname, $fid);                        
        	if (!$strlistfaculties)   { 
                notice(get_string('permission', 'block_bsu_plan'), '../index.php');
        	}	
           	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
            echo $strlistfaculties;
        
        
            if($fid > 0){
                listbox_plan($scriptname."?fid=$fid&term=$term", $fid, $pid);
                if ($pid > 0)   {
                    /*
                    $sql = "SELECT p.id, p.name as pname, s.Specyal  as sname, s.KodSpecyal as scode 
                                    FROM {bsu_plan} p
                                    inner join {bsu_tsspecyal} s ON s.idSpecyal=p.specialityid
                                    where p.id=$pid"; //  and g.groupid=$gid";
                    */                        
                    // echo $sql;
                    // if($plan = $DB->get_record_sql($sql)) {
                    if($plan = $DB->get_record_select('bsu_plan', "id = $pid", null, 'id, name as pname, specialityid, profileid, edformid, kvalif, timenorm')) {
                            
                            if ($specyal = $DB->get_record_select('bsu_tsspecyal', "idSpecyal = $plan->specialityid", null, 'Specyal  as sname, KodSpecyal as scode')) {
                                $sname = $specyal->sname;
                            } else {
                                $sname = 'СПЕЦИАЛЬНОСТЬ НЕ НАЙДЕНА';
                            }     
                            $plan->sname = $sname; 
                            
                            echo '<tr> <td align=right>'.get_string('speciality', 'block_bsu_plan').': </td><td>';
                            echo '<b>'.$plan->sname.'</b>'; // $plan->scode . '. ' .
                            echo '</td></tr>';

                            if ($plan->profileid > 0) {
                                echo '<tr> <td align=right>'.get_string('profile', 'block_bsu_plan').': </td><td>';
                                $profile = '-';
                                if (!$profile = $DB->get_field('bsu_ref_profiles', 'name', array('id' => $plan->profileid))) {
                                    $profile = '-';
                                }
                                echo '<b>'.$profile.'</b>'; // $plan->scode . '. ' .
                                echo '</td></tr>';
                            }

                            echo '<tr> <td align=right>'.get_string('edform', 'block_bsu_plan').': </td><td>';
                            $edform = $DB->get_field('bsu_tsotdelenie', 'Otdelenie', array('idOtdelenie' => $plan->edformid ));
                            echo '<b>'.$edform.'</b>'; // $plan->scode . '. ' .
                            echo '</td></tr>';
                            
                            echo '<tr> <td align=right>'.get_string('kvalif', 'block_bsu_plan').': </td><td>';
                            $kvalif = $DB->get_field('bsu_tskvalifspec', 'Kvalif', array('idKvalif' =>  $plan->kvalif));
                            echo '<b>'.$kvalif.'</b>'; // $plan->scode . '. ' .
                            echo '</td></tr>';
                            
                            $strgroups = get_plan_groups($pid);
                            if ($strgroups != '')   {
                                $agroups = explode ('<br>', $strgroups);
                                echo '</td><tr> <td align=right>'.get_string('groups', 'block_bsu_plan').': </td><td>';
                                $strgroups = str_replace('<br>', ', ', $strgroups);
                                echo '<b>'.$strgroups.'</b>'; // $plan->scode . '. ' .
                            } else {
                                $agroups = array();
                            }

                            // if ($term == 100 || $term == 101)   {
                                list_box_year($scriptname."?fid=$fid&pid=$pid&term=$term", $yid);
                            // }
                            echo '</table>';
                            

                            $term = print_tabs_terms("disciplines.php?yid=$yid&fid=$fid&pid={$plan->id}", $yid, $fid, $plan, $tab, $term, $agroups);
                                //echo $OUTPUT->notification(get_string('no_inf_avail', 'block_bsu_plan'), 'notifysuccess');
                            switch($term)   {
                                 case 107:
                                    //  echo $OUTPUT->heading('Журнал операций', 3); 
                                    $table107 = table_bsu_plan_log($yid, $fid, $plan);
                                    //  echo'<center>'.html_writer::table($table105).'</center>';
                                    print_color_table($table107);
                                break;
                                    
                                case 106:
                                     echo $OUTPUT->heading('Распределение академических часов и зачетных единиц трудоемкости по курсам', 3); 
                                    $table106h = table_svod_hours($yid, $fid, $plan, $agroups);
                                    //echo'<center>'.html_writer::table($table105).'</center>';
                                    print_color_table($table106h);

                                    
                                    echo $OUTPUT->heading('Распределение зачетных единиц по циклам', 3); 
                                    echo  '<form name="svod" method="post" action="disciplines.php">';
                                	echo  '<input type="hidden" name="action" value="svod">';
                                    echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
                                	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
                                	echo  '<input type="hidden" name="pid" value="' .  $pid . '">';
                                	echo  '<input type="hidden" name="did" value="' .  $did . '">';
                                    echo  '<input type="hidden" name="tab" value="' .  $tab . '" />';                                    
                                    echo  '<input type="hidden" name="term" value="' .  $term . '">';
                                	echo  '<div align="center">';
                                    $table106 = table_svod_zet($yid, $fid, $plan, $agroups);
                                    //echo'<center>'.html_writer::table($table105).'</center>';
                                    print_color_table($table106);
                                	echo  '<input type="submit" name="save" value="Сохранить"></div>';
                                	echo  '</form>'; // </td></tr></table>
                                    
                                    notify ('<i>Замечание: при сохранении зачетных единиц по циклам автоматически такие же значения сохранятся и в планах, относящихся к одной ООП.</i><br /><br />', "notifysuccess");
                                break;
                                
                                case 105:
                                    echo  '<form name="semestrkontrol" method="post" action="disciplines.php">';
                                	echo  '<input type="hidden" name="action" value="semestrkontrol">';
                                    echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
                                	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
                                	echo  '<input type="hidden" name="pid" value="' .  $pid . '">';
                                	echo  '<input type="hidden" name="did" value="' .  $did . '">';
                                    echo  '<input type="hidden" name="tab" value="' .  $tab . '" />';                                    
                                    echo  '<input type="hidden" name="term" value="' .  $term . '">';
                                	echo  '<div align="center">';
                                    $table105 = table_semestr_kontrol($yid, $fid, $plan, $agroups);
                                    //echo'<center>'.html_writer::table($table105).'</center>';
                                    print_color_table($table105);
                                	echo  '<input type="submit" name="save" value="Сохранить"></div>';
                                	echo  '</form>'; // </td></tr></table>
                                break;    
                                
                                case 104:
                                    $plan->edform = $DB->get_field('bsu_tsotdelenie', 'Otdelenie', array('idOtdelenie' => $plan->edformid ));
                                    $plan->strgroups = str_replace('<br>', ', ', $strgroups);
                                    $table104 = table_plan_report2($yid, $fid, $plan, $agroups);
                                    echo'<center>'.html_writer::table($table104).'</center>';
                                break;    


                                case 103:
                                    $plan->edform = $DB->get_field('bsu_tsotdelenie', 'Otdelenie', array('idOtdelenie' => $plan->edformid ));
                                    $plan->strgroups = str_replace('<br>', ', ', $strgroups);
                                    $table103 = table_plan_report($yid, $fid, $plan, $agroups);
                                    echo'<center>'.html_writer::table($table103).'</center>';
                                break;    
                                
                                case 102:
                                    // $table103 = plan_table_grafikuchprocess($plan->id);
                                    // echo'<center>'.html_writer::table($table103).'</center>';
                                    $planid = $plan->id;
                                    include ("graphicup.php");
                                break;    

                                case 101:
                                    $table101 = table_specvidrabot0($yid, $fid, $plan, $agroups, $tab);
                                    echo'<center>'.html_writer::table($table101).'</center>';
                                    /*
                                    echo $yid . '<br />';
                                    echo $CFG->editplanopenedforyid . '<br />';
                                     var_dump(strrpos($CFG->editplanopenedforyid, "$yid"));
                                    $findme = strrpos($CFG->editplanopenedforyid, "$yid");
                                    print_object($findme);
                                    echo $findme . '<br />';
                                    */
                                    if ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                                        $options = array('action'=> 'add', 'yid' => $yid, 'fid' => $fid, 'pid' => $plan->id, 'term' => $term, 'tab'=>$tab, 'eid' => 16, 'sesskey' => sesskey());
                                        echo '<center>'.$OUTPUT->single_button(new moodle_url('editpractice.php', $options), 'Добавить спец. вид работы', 'get', $options).'</center><br>';
                                    }
                                    $options = array('action'=> 'excel', 'yid' => $yid, 'fid' => $fid, 'pid' => $plan->id, 'term' => $term, 'tab'=>$tab, 'sesskey' => sesskey());
                                    echo '<br><center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center><br>';
                                        
                                break;   


                                case 100:
                                    $table100 = table_practice0($yid, $fid, $plan, $agroups, $tab);
                                    echo'<center>'.html_writer::table($table100).'</center>';
                                    if ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                                        $options = array('action'=> 'add', 'yid' => $yid, 'fid' => $fid,  'pid' => $plan->id, 'term' => $term, 'tab'=>$tab, 'sesskey' => sesskey());
                                        echo '<center>'.$OUTPUT->single_button(new moodle_url('editpractice.php', $options), 'Добавить практику', 'get', $options).'</center><br>';
                                    }    
                                    $options = array('action'=> 'excel', 'yid' => $yid, 'fid' => $fid, 'pid' => $plan->id, 'term' => $term, 'tab'=>$tab, 'sesskey' => sesskey());
                                    echo '<br><center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center><br>';
                                    
                                break;    

                                case 99:
                                    notify ('<b><i>Замечание: максимальные и минимальные нормативные значения можно задать на вкладке "Свод".</i></b>', 'notifysuccess');  
                                    $table = table_all_disciplines($yid, $fid, $plan);
                                    if (!empty($table->errormsg))    {
                                        notify ('ВНИМАНИЕ! Найдены ошибки в РУП: количество кредитов (ЗЕТ) не соответствует нормативным значениям.<br />'. $table->errormsg);
                                    }
                                break;    
                                
                                default:
                                    $showkaf = false;
                                    if (!empty($agroups))   {   
                                        $terms = get_terms_group($yid, $agroups);
                                        foreach ($terms as $group => $t)  {                                            
                                            if (in_array($term, $t)) $showkaf = true;  
                                        }
                                    } else {
                                        $agroups = array();
                                    }       
                                
                                    $table = table_disciplines($yid, $fid, $plan, $term, $agroups, $showkaf, $tab);
                                    
                            }    
                            
                            if (!empty($table)) {

                                echo'<center>'.html_writer::table($table).'</center>';
                                
                                /*
                                if ($strgroups != '')   {
                                    $edyids = array (13, 14);
                                    $stryers[13] = '2012/2013 уч. году'; 
                                    $stryers[14] = '2013/2014 уч. году';
                                    echo '<table align=center border=1>';
                                    foreach($edyids as $yid1)  {
                                        $terms = get_terms_group($yid1, $agroups);
                                        foreach ($terms as $group => $t)  {                                            
                                            if (in_array($term, $t))    {
                                                echo '<tr><td><b>' . $term . "-й семестр для группы $group в " . $stryers[$yid1] . '.</b></td></tr>';
                                            }
                                        }    
                                    }    
                                    echo '</table>';
                                }    
                                */
                                
                                   
                                if ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                                    if ($term != 99)    {                                    
                                        // $options['action'] = 'add';
                                        // echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), 'Добавить дисциплину', 'get', $options).'</center><br>';
                                        $optionst = array('yid'=>$yid, 'fid'=>$fid, 'pid'=>$pid, 'tab'=>$tab, 'term'=>$term);
                                        $scriptnamet = 'addiscipline.php';
                                        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptnamet, $optionst), 'Добавить дисциплину', 'get', $optionst).'</center><br>';
                                    }
                                }
                                
                                $options = array('action'=> 'excel', 'yid' => $yid, 'fid' => $fid, 'pid' => $plan->id, 'term' => $term, 'sesskey' => sesskey());
                                echo '<br><center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center><br>';
                                if ($term == 99)    {
                                    $options = array('action'=> 'hidemodules', 'yid' => $yid, 'fid' => $fid, 'pid' => $plan->id, 'term' => $term, 'sesskey' => sesskey());
                                    echo '<br><center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), 'Скрыть модули', 'get', $options).'</center><br>';
                                }    
                                    
                                    
                                if (is_siteadmin() || $USER->id == 54087 || $USER->id == 58358) {    
                                    echo '<br><table align=center><tr><td>';
                                    $options['action'] = 'shiftleft';
                                    $options['delta'] = -1;
                                    echo $OUTPUT->single_button(new moodle_url($scriptname, $options), '<-- Сместить дисциплины на один семестр влево', 'get', $options);
                                    echo '</td><td>';
                                    $options['action'] = 'shiftright';
                                    $options['delta'] = 1;
                                    echo $OUTPUT->single_button(new moodle_url($scriptname, $options), 'Сместить дисциплины на один семестр вправо -->', 'get', $options);
                                    echo '</td></tr></table>';                            
                                }    
                            }    
                     } else {
                        echo '</table>';
                     }
                } else {
                    echo '</table>';            
                }
            }else{
                echo '</table>';
            }
    }
    echo $OUTPUT->footer();



function print_tabs_terms($scriptname, $yid, $fid, $plan, $currtab, $term, $agroups)
{
	global $DB, $CFG;
    
    $planid = $plan->id;
    
    $CFG->stopeditingplan = false;
    $numkurs = substr($currtab, -1, 1);
    $edyear = $DB->get_record_select('bsu_ref_edyear', "id = $yid");
    $atermsids = get_active_term_for_plan($yid, $planid);
    $activekurses = array();
    
    $maxsem = get_maxsemestr_plan($planid);
    $maxkurs = round($maxsem / 2); 
    
    $maxactivekurs = 0;
    $toprow = array();
    $toprow[] = new tabobject('plan', $scriptname.'&tab=plan', 'План');
    for ($i=1; $i<=$maxkurs; $i++)   {
        $title = 'Курс '.$i;
        if (in_array($i*2-1, $atermsids) || in_array($i*2, $atermsids)) {
            $activekurses[] = $i;
            if ($i > $maxactivekurs) $maxactivekurs=$i;
            $title .= " ($edyear->edyear)";
        }  
        $toprow[] = new tabobject('kurs'.$i, $scriptname.'&tab=kurs'.$i, $title);
    }   
    
    $notactivekurses = array();
    if (!empty($agroups))   {
        for ($i=1; $i<=$maxkurs; $i++)   {
            if ($yid == 15) { 
                if (!in_array($i, $activekurses) && $i <= $maxactivekurs)   { 
                    $notactivekurses[] =  'kurs'.$i;
                }
            } else {
                if (!in_array($i, $activekurses))   { 
                    $notactivekurses[] =  'kurs'.$i;
                }
            }        
        }
    } else {
        $edyear->edyear = '-';
    }       

/*
    if ($yid == 15) {
        print_object($activekurses);
        print_object($notactivekurses);
    }
*/    
   $toprow[] = new tabobject('rprt', $scriptname.'&tab=rprt', 'Отчеты');    
   $tabs = array($toprow);
   print_tabs($tabs, $currtab, $notactivekurses, NULL);
   /* 
   notify('<b>ВНИМАНИЕ!!! Нагрузка на 2014/15 уч.год по курсовым работам, курсовым проектам, рефератам и контрольным работам будет формироваться на основе<br /> 
   данных, сохраненных в карточках дисциплин (там же где сохраняются зачеты и экзамены), а не в спец.видах работы.<br /> Все вышеперечисленные виды работ будут автоматически удалены из спец.видов работ, <br />
   кроме тех которые ведутся в этом учебном году. Остальные спец. вида работы можно добавлять с помощью кнопки "Добавить спец. вида работу".');
    */

    $toprow2 = array();
    $currtab2 = substr($currtab, 0, 4);
    $kurs = substr($currtab, 4, 1);
     
    switch ($currtab2)   {
        case 'plan':
                    if ($term == 0 || $term <= 18) {  //  || $term >= 99
                        $term = 99;  
                    }
                    $toprow2[] = new tabobject('term99',  $scriptname.'&tab=plan&term=99',  'Дисциплины');
                    $toprow2[] = new tabobject('term100', $scriptname.'&tab=plan&term=100', 'Практики');
                    $toprow2[] = new tabobject('term101', $scriptname.'&tab=plan&term=101', 'Спец.виды работ');
                    $toprow2[] = new tabobject('term102', $scriptname.'&tab=plan&term=102', 'Графики уч. процесса');
                    $toprow2[] = new tabobject('term105', $scriptname.'&tab=plan&term=105', 'Часов в неделю');
                    $toprow2[] = new tabobject('term106', $scriptname.'&tab=plan&term=106', 'Свод');
                    $toprow2[] = new tabobject('term107', $scriptname.'&tab=plan&term=107', 'Журнал операций');
        break;
        
        case 'kurs':
                    if ($term == 0) {  //  || $term >= 99
                        $term = 2*$kurs - 1;  
                    }
                    for ($i = 0; $i<=1; $i++)   {
                        $t = 2*$kurs - 1 + $i; 
                      	$sql = "SELECT count(b.id) as cnt FROM {bsu_discipline} a
                        inner join {bsu_discipline_semestr} b on a.id=b.disciplineid
                        where a.planid=$planid AND b.numsemestr=$t";
                        if ($c = $DB->get_record_sql($sql))  {
                            $cntdis = $c->cnt;
                        } else {
                            $cntdis = 0;
                        }
                        $toprow2[] = new tabobject('term'.$t, $scriptname."&tab=$currtab&term=".$t, '<b>'.$t.' семестр</b>'.' ('.$cntdis. " д., $edyear->edyear)");
                    }    
                    $toprow2[] = new tabobject('term100', $scriptname."&tab=$currtab&term=100", 'Практики' . " ($edyear->edyear)");
                    $toprow2[] = new tabobject('term101', $scriptname."&tab=$currtab&term=101", 'Спец.виды работ' . " ($edyear->edyear)");
                    
                    if (!empty($agroups))   {
                       	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
                        // echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left"><b>';
                        // echo $edyear->edyear;
                        // echo '</b></td></tr>';
                        
                        echo "<tr align=\"left\"> <td align=right>Группы <strong>$numkurs</strong>-го курса: </td><td align=\"left\"><b>";
                        // print_object($edyear);
                        $god = $edyear->god - 2000;
                        // echo $god . '<br />';
                        $god = $god - $numkurs + 1;
                        $prevgod = $god - 1;
                        
                        // echo $god;
                        // print_object($agroups);
                        
                        foreach ($agroups as $ii => $agroup)   {
                            $grgod = (int)substr($agroup, -4, 2);
                            // echo "$god != $grgod";
                            if ($god != $grgod) unset ($agroups[$ii]);
                            if ($prevgod == $grgod && $yid == 15) $CFG->stopeditingplan = true;
                        }
                        // print_object($agroups);
                        echo implode (', ', $agroups);
                        echo '</b></td></tr></table>';
                     }   
                    
                    if ($CFG->stopeditingplan)  {
                        notify('<b>ВНИМАНИЕ!!! На план подписаны группы нескольких курсов. Невозможно изменять план для групп младших курсов в тех семестрах, 
                                <br />которые уже пройдены группами старших курсов. Если необходимо внести коррективы в дисциплины, <br />
                                то надо сделать копию(и) данного плана и перевести группы младших курсов на копию(и) данного плана.<br />');
                    }

                    
        break;
        case 'rprt':
                    if ($term != 103 && $term != 104) {
                        $term = 103;
                    }    
                    $toprow2[] = new tabobject('term103', $scriptname.'&tab=rprt&term=103', 'Отчет №1');
                    $toprow2[] = new tabobject('term104', $scriptname.'&tab=rprt&term=104', 'Отчет №2');
        break;
    }
    
    $tabs = array($toprow2);
    print_tabs($tabs, 'term'.$term, NULL, NULL);
    
    return $term;
}


function get_active_term_for_plan($yid, $planid)
{
    global $DB;

    $atermsids = array();
        
    $strgroups = get_plan_groups($planid);
    if ($strgroups != '')   {
        $agroups = explode ('<br>', $strgroups);  
   
    
        $terms = get_terms_group($yid, $agroups);
        foreach ($terms as $term)   {
            foreach ($term as $t)   {
                $atermsids[] = $t;
            }    
        } 
        $atermsids = array_unique($atermsids);
    }    
    
    return $atermsids;    
}


function remove_term_from_form_kontrol($term, $fk)  
{
    if (!empty($fk))    {
        $arrayfk = str_split($fk, 1);
        // print_object($arrayfk);
        foreach ($arrayfk as $i => $afk)  {
            $decterm = hexdec($afk);
            if ($decterm == $term)  {
                unset($arrayfk[$i]); 
            }
        }
        $clearfk = implode('', $arrayfk);
        return  $clearfk;
    }
        
    
}

            
function delete_form_kontrol_from_term($did, $term)
{
    global $DB;

    $sql = "SELECT d.id, d.semestrexamen, d.semestrzachet, d.semestrkursovik, d.semestrdiffzach, d.semestrkp
            FROM mdl_bsu_discipline d 
            WHERE d.id=$did";
    
    if ($discipline_semestr = $DB->get_record_sql($sql))   {
        // print_object($discipline_semestr);
        $discipline_semestr->semestrexamen = remove_term_from_form_kontrol($term, $discipline_semestr->semestrexamen);
        $discipline_semestr->semestrzachet = remove_term_from_form_kontrol($term, $discipline_semestr->semestrzachet);
        $discipline_semestr->semestrdiffzach = remove_term_from_form_kontrol($term, $discipline_semestr->semestrdiffzach);
        $discipline_semestr->semestrkursovik = remove_term_from_form_kontrol($term, $discipline_semestr->semestrkursovik);
        $discipline_semestr->semestrkp = remove_term_from_form_kontrol($term, $discipline_semestr->semestrkp);
        $DB->update_record('bsu_discipline', $discipline_semestr);
        // print_object($discipline_semestr);
    }
}   


function hide_modules_in_plan($planid)
{
    global $DB;
    
    $sql = "SELECT id, planid, cyclename, identificatordiscipline FROM mdl_bsu_discipline m
            where planid = $planid and identificatordiscipline like '%.%.%.%'";

    if ($disciplines = $DB->get_records_sql($sql))  {
        foreach ($disciplines as $discipline)   {
            $parts = explode ('.',  $discipline->identificatordiscipline);
            $identdisc = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
            if ($modules = $DB->get_records_select('bsu_discipline', "planid=$discipline->planid AND identificatordiscipline = '$identdisc'", //  and notusing = 0 
                                                    null, '', 'id, planid, identificatordiscipline' ))    {
                // print_object($modules);
                if (count ($modules) == 1)  {
                    $module = reset($modules);
                    // $DB->delete_records_select('bsu_discipline_semestr', "disciplineid=$module->id");
                    // $DB->delete_records_select('bsu_discipline', "id=$module->id");
                    $DB->set_field_select ('bsu_discipline', 'notusing', 1, "id=$module->id");
                    notify ("Модуль $identdisc скрыт.", 'notifysuccess');
                } else {
                    // notify ('Найдено больше одного модуля.' . $discipline->planid);
                }
                
            } else {
               // notify ('Не найдено для ' . $identdisc . '. planid=' . $discipline->planid);
                $identdisc = $parts[0] . '.' . $parts[1] . '.0' . $parts[2];
                if ($modules = $DB->get_records_select('bsu_discipline', "planid=$discipline->planid AND identificatordiscipline = '$identdisc'", // and notusing = 0 
                                                        null, '', 'id, planid, identificatordiscipline' ))    {
                    // print_object($modules);
                    if (count ($modules) == 1)  {
                        $module = reset($modules);
                        // $DB->delete_records_select('bsu_discipline_semestr', "disciplineid=$module->id");
                        // $DB->delete_records_select('bsu_discipline', "id=$module->id");
                        $DB->set_field_select ('bsu_discipline', 'notusing', 1, "id=$module->id");
                        $identdiscnew = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
                        $DB->set_field_select ('bsu_discipline', 'identificatordiscipline', $identdiscnew, "id=$module->id");
                        notify ("Модуль $identdisc скрыт.", 'notifysuccess');
                    } else {
                        // notify ('Найдено больше одного модуля.' . $discipline->planid);
                    }
                }                
            }
        }
    }   
    

    $sql = "SELECT id, planid, cyclename, identificatordiscipline FROM mdl_bsu_discipline m
            where planid = $planid and identificatordiscipline like '%.%.%.%.%'";

    if ($disciplines = $DB->get_records_sql($sql))  {
        foreach ($disciplines as $discipline)   {
            $parts = explode ('.',  $discipline->identificatordiscipline);
            $identdisc = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.' . $parts[3];
            if ($modules = $DB->get_records_select('bsu_discipline', "planid=$discipline->planid AND identificatordiscipline = '$identdisc' and notusing = 0", 
                                                    null, '', 'id, planid, identificatordiscipline' ))    {
                // print_object($modules);
                if (count ($modules) == 1)  {
                    $module = reset($modules);
                    // $DB->delete_records_select('bsu_discipline_semestr', "disciplineid=$module->id");
                    // $DB->delete_records_select('bsu_discipline', "id=$module->id");
                    $DB->set_field_select ('bsu_discipline', 'notusing', 1, "id=$module->id");
                    notify ("Модуль $identdisc скрыт.", 'notifysuccess');
                } else {
                    // notify ('Найдено больше одного модуля.' . $discipline->planid);
                }
                
            } else {
               // notify ('Не найдено для ' . $identdisc . '. planid=' . $discipline->planid); 
            }
        }
    }   
    
               
}



function copy_planzets_in_oop_plans($planzets, $pid, $kvalif)
{
    global $DB;

    $sql =  "SELECT group_concat(planids) as planids FROM mdl_bsu_oop
            where shifr in (SELECT s.KodSpecyal as scode FROM mdl_bsu_plan p
            inner join mdl_bsu_tsspecyal s ON s.idSpecyal=p.specialityid
            where p.id=$pid and kvalifid=$kvalif)";    
    if ($planids = $DB->get_field_sql($sql))    {
        // print $planids . '<br />'; 
        $pids = explode(',' , $planids);
        foreach ($pids as $apid) {
            if ($apid == $pid) continue;
            foreach ($planzets as $cycle => $planzet) {
                $planzet->planid = $apid;
                $planzet->cyclename = $cycle;
                if ($week = $DB->get_record_select('bsu_plan_minmaxzet', "planid=$apid and cyclename='$cycle'"))   {
                    $planzet->id = $week->id; 
                    $DB->update_record('bsu_plan_minmaxzet', $planzet); 
                } else {
                    $DB->insert_record('bsu_plan_minmaxzet', $planzet);
                }
            }
        }
    }    
}



function copy_weekshours_in_oop_plans($weekshours, $pid, $kvalif)
{
    global $DB;

    $sql =  "SELECT group_concat(planids) as planids FROM mdl_bsu_oop
            where shifr in (SELECT s.KodSpecyal as scode FROM mdl_bsu_plan p
            inner join mdl_bsu_tsspecyal s ON s.idSpecyal=p.specialityid
            where p.id=$pid and kvalifid=$kvalif)";    
    if ($planids = $DB->get_field_sql($sql))    {
        // print $planids . '<br />'; 
        $pids = explode(',' , $planids);
        foreach ($pids as $apid) {
            foreach ($weekshours as $iterm => $weekshour) {
                $weekshour->planid = $apid;
                $weekshour->term = $iterm;
                if ($week = $DB->get_record_select('bsu_plan_weeks_hours', "planid=$apid and term=$iterm"))   {
                    $weekshour->id = $week->id; 
                    $DB->update_record('bsu_plan_weeks_hours', $weekshour); 
                } else {
                    $DB->insert_record('bsu_plan_weeks_hours', $weekshour);
                }
            }
        }
    }        
            
}            


function table_bsu_plan_log($yid, $fid, $plan) 
{
    global $CFG, $DB, $OUTPUT;

    $table = new html_table();
    $table->classes = array('logtable','generalbox');
    $table->head = array('Дисциплина/Практика', 'Учебная работа', 'Семестр', get_string('action'), 'Группа (подгруппа)', 
                         get_string('time'), get_string('fullnameuser'), get_string('ip_address'));
    $table->align = array('left', 'center', 'center');
    $table->data = array();
    
    $edworkkind = $DB->get_records_select_menu('bsu_ref_edworkkind', '', null, 'id', 'id, ir_name');
    $edworkkind[0] = '-'; 

    $sql = "SELECT l.id, FROM_UNIXTIME(l.time, '%d.%m.%Y %H:%i') as timemodified, l.ip, l.action, 
                   rd.name, l.term, l.edworkkindid, l.groupid, l.subgroupid, l.numstream, l.notusing, 
                   concat (u.lastname, ' ', u.firstname) as fullname
            FROM mdl_bsu_plan_log l
            LEFT JOIN mdl_user u ON l.userid = u.id
            LEFT JOIN mdl_bsu_discipline d on d.id=l.disciplineid
            INNER JOIN mdl_bsu_ref_disciplinename rd on rd.id = d.disciplinenameid
            where l.planid=$plan->id";
    if ($logs = $DB->get_records_sql($sql)) {
        foreach ($logs as $log) {
            $action='?';
            switch ($log->action)   {
                case 'discipline:delete':           $action='удаление дисциплины';
                break;
                case 'discipline:delete semestr':   $action='удаление семестра дисциплины';
                break;
                case 'discipline:notusing':         $action='отключение дисциплины по выбору';
                break;
                case 'practice:delete':             $action='удаление практики или спец.вида работы';
                                                    $log->name = $log->info;
                break;
                case 'stream:add':      $action='добавление в поток';    
                break;
                case 'stream:delete':   $action='удаление из потока';
                break;
            }    
            
            $groupname = '';
            if ($log->groupid > 0) {
                $groupname .= $DB->get_field_select('bsu_ref_groups', 'name', "id = $log->groupid");    
            }
           
            if ($log->subgroupid > 0) {
                $groupname .= '->' . $DB->get_field_select('bsu_discipline_subgroup', 'name', "id = $log->subgroupid");
            }
            if ($log->term == 0) $log->term = '';
            
            $row = array($log->name, $edworkkind[$log->edworkkindid], $log->term, $action, $groupname, 
                         $log->timemodified, $log->fullname, $log->ip);
            $table->data[] = $row;
        }
    }    

    // echo html_writer::table($table);
    return $table;

}

?>