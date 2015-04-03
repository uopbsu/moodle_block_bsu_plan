<?php // $Id: importrup.php,v 1.3 2011/10/13 11:25:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("lib_import.php");

    require_login();

    $fid = optional_param('fid', 0, PARAM_INT);          // Faculty id
    $pid = optional_param('pid', 0, PARAM_INT);          // Plan id
    //$sid = optional_param('sid', 0, PARAM_INT);          // Speciality id    
    //$kvalif = optional_param('kvalif', 0, PARAM_INT);          // Kvalifiction id
  	$action = optional_param('action', '', PARAM_TEXT);       // action

	$scriptname = basename($_SERVER['PHP_SELF']);	// echo '<hr>'.basename(me());
	$arrscriptname = explode('.', $scriptname);
    // $scriptname = $arrscriptname[0];
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strscript = get_string($arrscriptname[0], 'block_bsu_plan');    
    

    $PAGE->set_url('/blocks/bsu_info/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strscript);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    // $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
	$PAGE->navbar->add($strtitle, new moodle_url("$CFG->BSU_PLAN/index.php", array()));
	$PAGE->navbar->add($strscript);
    echo $OUTPUT->header();
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
   
    if ($action == 'clone')	{
        /*
            ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
    @set_time_limit(0);
    @ob_implicit_flush(true);
    @ob_end_flush();
	@raise_memory_limit("256M");
 	if (function_exists('apache_child_terminate')) {
	    @apache_child_terminate();
	}    
*/
        clone_shaht_plan_vpo($fid, $pid);
    }                 
    
    if ($action == '' || $action == 'clone')	{
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
                        echo '<form method="post"  action='.$scriptname.'>';
                        echo '<br><center>';
                        echo '<input type="hidden" name="action" value="clone"/>'.
                             '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">'.
                    		 '<input type="hidden" name="fid" value="'. $fid.'" />'.
                    		 '<input type="hidden" name="pid" value="'. $pid.'" />'.
                             '<input type="submit" value="Создать копию РУП">'.
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
    
    if ($plan = $DB->get_record_select('bsu_plan', "id = $pid"))    {

        $plan->cycles = $DB->get_records_select('bsu_plan_cycle', "planid = $pid");
        
        if ($grafiki = $DB->get_records_select('bsu_plan_grafikuchprocess', "planid = $pid")) {
            foreach ($grafiki as $key => $grafik)   {
                $semestrgraf = $DB->get_records_select('bsu_plan_weeks', "planid = $pid AND numkurs = $grafik->numkurs");
                $grafiki[$key]->semestrgraf = $semestrgraf;
            }
            
        }   
        $plan->grafiki = $grafiki;  
        
        $plan->planweeks = $DB->get_records_select('bsu_plan_weeks_normi', "planid = $pid");  

        if ($praktikshacht = $DB->get_records_select('bsu_plan_practice_shacht', "planid = $pid")) {
            foreach ($praktikshacht as $index => $praktike)   {
                $praktikshacht[$index]->semestrs = $DB->get_records_select('bsu_plan_practice_semestr_shacht', "practiceid = $praktike->id");
            }
        }        
        $plan->praktiki = $praktikshacht;
        $plan->specvidrabot = $DB->get_records_select('bsu_plan_specvidrabot', "planid = $pid");
            

        if ($praktices = $DB->get_records_select('bsu_plan_practice', "planid = $pid")) {
            foreach ($praktices as $index => $praktice)   {
                $praktices[$index]->practice_subdep = $DB->get_records_select('bsu_plan_practice_subdep', "practiceid = $praktice->id");
            }
        }        
        $plan->praktices = $praktices;
          
        $sql = "SELECT d.*, n.name as nname FROM {bsu_discipline} d 
                INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                WHERE d.planid=$pid";
        if ($disciplines = $DB->get_records_sql($sql))  {
            foreach ($disciplines as $index => $discipline)   {
                $disciplines[$index]->credits= $DB->get_records_select('bsu_discipline_creditovkurs', "disciplineid = $discipline->id");
                $disciplines[$index]->semestrs= $DB->get_records_select('bsu_discipline_semestr', "disciplineid = $discipline->id");
                $disciplines[$index]->subdepartments= $DB->get_records_select('bsu_discipline_subdepartment', "disciplineid = $discipline->id");
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


    
function clone_shaht_plan_vpo($fid, $planid)
{
    global $DB, $OUTPUT;
    
    $plan = get_plan_vpo_from_db($planid);
    
    $plan->name .= " (копия ID {$planid})";
    // print_object($plan);
    

    if ($planid = $DB->insert_record('bsu_plan', $plan)) {
        $data=new stdClass();
        $data->departmentcode=$plan->departmentcode;
        $data->planid=$planid;
        $data->yearid=get_current_edyearid();
        $DB->insert_record('bsu_plan_department_year', $data);
        
        echo $OUTPUT->notification('ПЛАН добавлен в БД. Id = ' . $planid, 'notifysuccess');
        /*
        foreach ($plan->cycles as $cycle) {
            $cycle->planid = $planid;
            // $cycle->cycleid =  get_cycleid($cycle->abbrev, $cycle->name);
            if ($DB->insert_record('bsu_plan_cycle', $cycle)) {
                echo $OUTPUT->notification('ЦИКЛ добавлен в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ЦИКЛа в БД.');
            }
        }
        */
        foreach ($plan->grafiki as $grafik)  {
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

        foreach ($plan->disciplines as $discipline)   {
            $discipline->planid = $planid;
            // print_object($discipline);
            if ($disciplineid = $DB->insert_record('bsu_discipline', $discipline)) {
                echo $OUTPUT->notification('ДИСЦИПЛИНА добавлена в БД с id='.$disciplineid, 'notifysuccess');
                foreach ($discipline->credits as $credit)   {
                    $credit->disciplineid = $disciplineid;
                    $credit->crects = str_replace(',', '.', $credit->crects);
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
                
                foreach ($discipline->subdepartments as $subdep)   {
                    $subdep->disciplineid = $disciplineid;
                    if ($DB->insert_record('bsu_discipline_subdepartment', $subdep)) {
                        echo $OUTPUT->notification('Кафедра по дисциплине добавлена в БД.', 'notifysuccess');
                    } else {
                        print_object($subdep);
                        echo $OUTPUT->notification('Ошибка при добавлении кафедры по дисциплине в БД.');
                    }
                }

            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ДИСЦИПЛИНЫ в БД.');
            }
        }

        foreach ($plan->planweeks as $planweek)   {
            $planweek->planid = $planid;
            if ($DB->insert_record('bsu_plan_weeks_normi', $planweek)) {
                echo $OUTPUT->notification('НЕДЕЛЯ добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении НЕДЕЛИ в БД.');
            }
        }

  
        foreach ($plan->praktiki as $praktik)   {
            $praktik->planid = $planid;
            if ($practiceid = $DB->insert_record('bsu_plan_practice_shacht', $praktik)) {
                echo $OUTPUT->notification('ПРАКТИКА добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ПРАКТИКИ в БД.');
            }
            
            if (isset($plan->praktiki->semestrs))  {
                foreach ($plan->praktiki->semestrs as $semestr)   {
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

        foreach ($plan->specvidrabot as $specvidr)   {
            $specvidr->planid = $planid;
            if ($DB->insert_record('bsu_plan_specvidrabot', $specvidr)) {
                echo $OUTPUT->notification('СПЕЦВИДРАБОТА добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении СПЕЦВИДРАБОТ в БД.');
            }
        }
        
        foreach ($plan->praktices as $praktice)   {
            $praktice->planid = $planid;
            if ($practiceid = $DB->insert_record('bsu_plan_practice', $praktice)) {
                echo $OUTPUT->notification('ПРАКТИКА/СПЕЦВИДРАБОТА добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ПРАКТИКИ/СПЕЦВИДРАБОТА в БД.');
            }
            
            if (isset($praktice->practice_subdep))  {
                foreach ($praktice->practice_subdep as $subdep)   {
                    $subdep->practiceid = $practiceid;
                    if ($DB->insert_record('bsu_plan_practice_subdep', $subdep)) {
                        echo $OUTPUT->notification('Кафедры практики добавлены в БД.', 'notifysuccess');
                    } else {
                        print_object($subdep);
                        echo $OUTPUT->notification('Ошибка при добавлении Кафедры практики в БД.');
                    }
                }
            }       
        }

    } else {
        echo $OUTPUT->notification('Ошибка при добавлении ПЛАНА в БД.');
    }

    
}

?>