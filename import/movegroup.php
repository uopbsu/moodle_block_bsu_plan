<?php // $Id: importrup.php,v 1.3 2011/10/13 11:25:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("lib_import.php");
    require_once("../../bsu_schedule/lib_schedule.php");

    require_login();

    $yid = optional_param('yid', 0, PARAM_INT);					// current year
    $fid = optional_param('fid', 0, PARAM_INT);          // Faculty id
    $planid = optional_param('pid', 0, PARAM_INT);          // Plan id
    $planidto = optional_param('planidto', 0, PARAM_INT);          // Plan id
    //$sid = optional_param('sid', 0, PARAM_INT);          // Speciality id    
    //$kvalif = optional_param('kvalif', 0, PARAM_INT);          // Kvalifiction id
  	$action = optional_param('action', '', PARAM_TEXT);       // action
    $gid = optional_param('gid', 0, PARAM_INT);
    
    if($yid == 0) $yid = get_current_edyearid();

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


    notify ('<strong>ВНИМАНИЕ!!! <br />Перевод группы можно выполнять ТОЛЬКО на точную и полную копию того рабочего учебного плана, на который в данный момент подписана группа.<br />
    Т.е. сначала надо создать копию РУП с помощью функции <a href="cloneplan.php">"Создание копии РУП"</a>, а только потом выполнять перевод. <br />
    Для того, чтобы при переводе сохранилась нагрузка, на копию РУП надо перевести ВСЕ ГРУППЫ КУРСА.<br />
    Например, есть РУП с ID 1433, на который подписаны четыре группы 1-го курса (01001307, 01001308, 01001309, 01001310) <br />
    и четыре группы второго курса (01001209, 01001210, 01001211, 01001212) тогда:<br />
    1. Надо создать ОДНУ копию  РУП с ID 1433. Предположим у копии номер ID будет 2370.<br />
    2. Перевести с плана ID 1433 на план ID 2370 четыре группы первого курса 01001307, 01001308, 01001309, 01001310 последовательно одну за другой.<br /><br /><br /><br /></strong>');
    
    
    // move_charge_between_plan(95, 2366, 428);
    /*
        $planid_src = 95;
        $planid_dest = 2366;
        $groupid = 428;
          echo '!!!!';
          $allterms = '7,8';

    echo $OUTPUT->footer();
    exit();
    */
    
    /*
    $time0 = time();
    $select = "editplan=1 and timestart<$time0 and timeend>$time0";
    $ACCESS_USER = $DB->get_records_select_menu('bsu_operator_dean', $select, null, '', 'id, userid');
    $ACCESS_USER[900000] = 9570;

    if (!$CFG->editplanclosed || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  { 
    } else {
        // notify (get_string('accessdenied', 'block_bsu_plan'));
        notify ('Для перевода группы с плана на план обращайтесь к Штифанову А.И. (30-18-77).');                
        echo $OUTPUT->footer();
        exit();
    }
    */
    
    if ($action == 'move')	{
        
        ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
        @set_time_limit(0);
        @ob_implicit_flush(true);
        @ob_end_flush();
    	@raise_memory_limit("256M");
     	if (function_exists('apache_child_terminate')) {
    	    @apache_child_terminate();
    	}
        /*
        $planid_sourche = 162;
        $planid_dest = 718;
        $groupid = 30881;
        copy_plan($planid_sourche, $planid_dest, $groupid);
        */
        move_group_between_plan($yid, $planid, $planidto, $gid);
    }                 
    
	$strlistfaculties =  listbox_department($scriptname, $fid);
	if (!$strlistfaculties)   { 
		// print_error(get_string('permission', 'block_bsu_plan'), 'block_plan', '../index.php');
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;

    $kp = false;
    if ($fid > 0 )  {
        listbox_plan($scriptname."?fid=$fid", $fid, $planid);
        if ($planid > 0)   {
            listbox_groups_plan($scriptname."?fid=$fid&pid=$planid", $fid, $planid, $gid);
            if ($gid > 0) {
                $kp = true;
            }
        }
    }
     
    echo '</table>';

    if ($kp)    {
        $groupname = $DB->get_field_select('bsu_ref_groups', 'name', "id =$gid");    
        echo '<br><center>';
        echo $OUTPUT->heading('Перевод группы ' . $groupname . ' на учебный план (с сохранением расписания и нагрузки):' , 4);
        listbox_plan_dest($scriptname."?fid=$fid&pid=$planid&gid=$gid", $fid, $planid, $planidto);
         $strgroups = get_plan_groups($planidto);
         echo get_string('groups', 'block_bsu_plan').':  ';
         if ($strgroups != '')   {
            $strgroups = str_replace('<br>', ', ', $strgroups);
            echo '<b>'.$strgroups.'</b><br />'; // $plan->scode . '. ' .
        } else {
            echo '(отсутствуют)';
        }
        echo '<br><br>';
        echo '<form method="post"  action='.$scriptname.'>';        
        echo '<input type="hidden" name="action" value="move"/>'.
             '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">'.
    		 '<input type="hidden" name="fid" value="'. $fid.'" />'.
    		 '<input type="hidden" name="pid" value="'. $planid.'" />'.
             '<input type="hidden" name="planidto" value="'. $planidto.'" />'.
             '<input type="hidden" name="gid" value="'. $gid.'" />'.
             '<input type="submit" value="Выполнить перевод группы">'.
             '</form></br>';
        echo '</center>';
    }    
    
    echo $OUTPUT->footer();

// Display list plan as popup_form
function listbox_plan_dest($scriptname, $fid, $planid, $planidto)
{
  global $CFG, $OUTPUT, $DB;

  $planmenu = array();
  $planmenu[0] = get_string('selectcurriculum', 'block_bsu_plan').'...';

  if($allplans = $DB->get_records_sql("SELECT id, name FROM {bsu_plan}
                                      WHERE departmentcode=$fid
                                      ORDER BY id"))   {
		foreach ($allplans as $plan) 	{
		    if ($plan->id != $planid) { 
                $planmenu[$plan->id] = $plan->id . '. ' . $plan->name;
            }    
		}
  }

  echo $OUTPUT->single_select($scriptname, 'planidto', $planmenu, $planidto, null, 'switchplandest');
  return 1;
}
    

function move_group_between_plan($yid, $planid_src, $planid_dest, $groupid) 
{
    GLOBAL $DB, $OUTPUT;
    
    // echo "$planid_src, $planid_dest, $groupid";
    $copy_plan = true;
    //print "SELECT distinct term, term as id FROM {bsu_schedule} WHERE groupid=$groupid<br />";
    $d_src = array();
    $d_dest = array();
    
    if($terms = $DB->get_records_sql_menu("SELECT distinct term, term as id FROM {bsu_schedule} WHERE groupid=$groupid AND disciplineid<>0")) {
        // print_object($terms);        
        $error = array(); 
        foreach($terms as $value=>$key) {
            $sql = "SELECT d.disciplinenameid, d.gos, d.sr, d.mustlearning, ds.lection, ds.praktika, ds.lab, ds.srs 
                                                          FROM mdl_bsu_discipline d
                                                          INNER JOIN mdl_bsu_discipline_semestr ds ON d.id=ds.disciplineid
                                                          WHERE d.planid=$planid_src AND numsemestr=$value ORDER BY disciplinenameid";
            //print $sql.'<br />';                                                          
            $disciplines_src = $DB->get_records_sql($sql);
            $disciplines_dest = $DB->get_records_sql("SELECT d.disciplinenameid, d.gos, d.sr, d.mustlearning, ds.lection, ds.praktika, ds.lab, ds.srs 
                                                          FROM mdl_bsu_discipline d
                                                          INNER JOIN mdl_bsu_discipline_semestr ds ON d.id=ds.disciplineid
                                                          WHERE d.planid=$planid_dest AND numsemestr=$value ORDER BY disciplinenameid");
                                                          
            $disciplines_src = (array)$disciplines_src;
            $disciplines_dest = (array)$disciplines_dest;
            if(count($disciplines_src) != count($disciplines_dest)) {
                $copy_plan = false;
            } else {
                foreach($disciplines_src as $val=>$k) {
                    // $disciplines_src[$val]->gos = 11;
                    $src = (array)$disciplines_src[$val];
                    $dest = (array)$disciplines_dest[$val];
                    
                    $diff = array_diff($src, $dest);
                    if(count($diff) > 0 ) {
                        $copy_plan = false;
                        foreach($diff as $key_d=>$value_diff) {
                            $error[$key_d.'~'.$value.'~'.$src['disciplinenameid']] = 1;    
                        }                        
                    }  
                }
                
                foreach($disciplines_src as $val=>$k) {
                    $d_src[$val] = $k;
                }
                     
                foreach($disciplines_dest as $val=>$k) {
                    $d_dest[$val] = $k;
                }     
                
            }
        }

        //print "copy_plan=$copy_plan<br />";        
        if($copy_plan) {

            $disciplines_src = $d_src; 
            // print_object($disciplines_src); 
            $disciplines_dest = $d_dest; 
            // print_object($disciplines_dest); 
           
            foreach($disciplines_src as $val=>$k) {
                $src_dsc_nameid = $disciplines_src[$val]->disciplinenameid;
                $dest_dsc_nameid = $disciplines_dest[$val]->disciplinenameid;
                
                $src = $DB->get_record_select('bsu_discipline', "disciplinenameid=$src_dsc_nameid and planid=$planid_src", null, 'id');

                $dest = $DB->get_record_select('bsu_discipline', "disciplinenameid=$dest_dsc_nameid and planid=$planid_dest", null, 'id');
                
                $sql = "UPDATE {bsu_schedule} SET disciplineid=$dest->id WHERE disciplineid=$src->id";
                $DB->Execute($sql);
                $sql = "UPDATE {bsu_schedule_mask} SET disciplineid=$dest->id WHERE disciplineid=$src->id";
                $DB->Execute($sql);
                
                $sql = "UPDATE {bsu_discipline_synonym} SET disciplineid=$dest->id WHERE disciplineid=$src->id";
                $DB->Execute($sql);
                $sql = "UPDATE {bsu_discipline_synonym} SET s_disciplineid=$dest->id WHERE s_disciplineid=$src->id";
                $DB->Execute($sql);

                $sql = "UPDATE {bsu_discipline_subgroup} SET disciplineid=$dest->id 
                        WHERE disciplineid=$src->id and groupid=$groupid";
                $DB->Execute($sql);

                $sql = "UPDATE {bsu_marksheet} SET disciplineid=$dest->id 
                        WHERE disciplineid=$src->id and groupid=$groupid";
                $DB->Execute($sql);                        
                
                $sql = "UPDATE {bsu_marksheet_students} SET disciplineid=$dest->id 
                        WHERE disciplineid=$src->id and groupid=$groupid";
                $DB->Execute($sql);
            }
            $update = $DB->get_record_select('bsu_plan_groups', "groupid=$groupid", null, "id");
            
            $new = new stdClass();
            $new->id = $update->id;
            $new->planid = $planid_dest;
            // print 'dfdf';                    
            // print_object($new);                    
            $DB->update_record('bsu_plan_groups', $new);
            unset($new); 
        } else {
            echo $OUTPUT->notification('<strong>Перевести группу в этот учебный план невозможно.</strong>');
            echo '<ul>';
            foreach($error as $key=>$value) {
                $txt = explode('~', $key);
                $disc = get_disciplinenameid_name($txt[2]);                         
                print "<li>В учебных планах не совпадает количество часов \"$txt[0]\", по дисциплине \"$disc\" в семестре N $txt[1]</li>";                    
            }
            echo '</ul>';
        }
    } else {
        if ($update = $DB->get_record_select('bsu_plan_groups', "groupid=$groupid", null, "id"))    {
            $new = new stdClass();
            $new->id = $update->id;
            $new->planid = $planid_dest;
            $DB->update_record('bsu_plan_groups', $new);
            
            unset($new);
        }  else {
            $copy_plan = false;
            echo '<center><strong>Группа не найдена.</strong></center><br /><br /><br />';
        }   
    }

    if($copy_plan) {
        move_charge_between_plan($yid, $planid_src, $planid_dest, $groupid);
        echo $OUTPUT->notification('<strong>Группа переведена на другой учебный план с сохранением расписания, нагрузки преподавателей и оценок студентов.</strong>', 'notifysuccess');
        echo '<br /><br /><br />';
    }
}



function move_charge_between_plan($yid, $planid_src, $planid_dest, $groupid) 
{
    GLOBAL $DB, $OUTPUT;
    
    // echo "$planid_src, $planid_dest, $groupid";
    $copy_plan = true;
    
    $sql = "SELECT distinct term, term as id FROM mdl_bsu_edwork 
            WHERE yearid = $yid and groupid=$groupid AND disciplineid<>0";
                
    if($terms = $DB->get_records_sql_menu($sql)) {
        // print_object($terms);        
        $allterms = implode (',',  $terms);
        /*
        $sql = "SELECT d.disciplinenameid, d.id FROM mdl_bsu_discipline d
                INNER JOIN mdl_bsu_discipline_semestr ds ON d.id=ds.disciplineid
                WHERE d.planid=$planid_src AND numsemestr in ($allterms) 
                ORDER BY disciplinenameid";
        echo $sql . '<br />';
        $disciplines_src = $DB->get_records_sql_menu($sql);
        print_object($disciplines_src);
        */
        
        $sql = "SELECT d.disciplinenameid, d.id FROM mdl_bsu_discipline d
                INNER JOIN mdl_bsu_discipline_semestr ds ON d.id=ds.disciplineid
                WHERE d.planid=$planid_dest AND numsemestr in ($allterms)
                group by disciplinenameid 
                ORDER BY disciplinenameid";
        $disciplines_dest = $DB->get_records_sql_menu($sql);
        // echo $sql . '<br />'; 
        // print_object($disciplines_dest);
        
        if ($edworks = $DB->get_records_select('bsu_edwork', "yearid=$yid and groupid=$groupid"))   {
            // меняем план и id дисциплины в bsu_edwork
            foreach($edworks as $edwork)    {
                $newedwork = new stdClass();
                $newedwork->id = $edwork->id;
                $newedwork->planid = $planid_dest;
                if (isset($disciplines_dest[$edwork->disciplinenameid])) {
                    $newedwork->disciplineid = $disciplines_dest[$edwork->disciplinenameid];
                }    
                if ($edwork->streammaskid > 0)  {
                    $DB->set_field_select('bsu_discipline_stream_mask', 'planid', $planid_dest, "id = $edwork->streammaskid");
                    // echo "bsu_discipline_stream_mask  planid=$planid_dest id = $edwork->streammaskid<br />";
                }
                // print_object($newedwork);
                
                if (!$DB->update_record('bsu_edwork', $newedwork))   {
                    notify ('Ошибка при обновлении bsu_edwork'); 
                }
            } 
            // меняем план в bsu_edwork_mask
            $edmids = array();
            foreach($edworks as $edwork)    {
                $edmids[] = $edwork->edworkmaskid;
            }
            $edmids1 = array_unique($edmids);
            $strids = implode (',', $edmids1);
            if ($edmarr = $DB->get_records_select('bsu_edwork_mask', "id in ($strids)", null, '', 'id, planid'))   {
                foreach ($edmarr as $edm)   {
                    if ($edm->planid == $planid_src)    {
                        $DB->set_field_select('bsu_edwork_mask', 'planid', $planid_dest, "id = $edm->id");
                    }
                }
            }       
        }
        
        // переводим практики и спец. виды работ
        if ($practices = $DB->get_records_select('bsu_plan_practice', "planid=$planid_src and term in ($allterms)"))    {
            foreach ($practices as $practice)   {
                $conditions = array ('planid' => $planid_dest, 'practicetypeid' => $practice->practicetypeid, 
                            'name' => $practice->name, 'term' => $practice->term, 'week' => $practice->week , 
                            'edworkkindid' => $practice->edworkkindid);
                if ($newpractice = $DB->get_record('bsu_plan_practice', $conditions))   {
                    $sql = "UPDATE {bsu_edwork_mask} SET planid=$planid_dest, practiceid=$newpractice->id 
                            WHERE yearid=$yid and planid=$planid_src and practiceid=$practice->id";
                    $DB->Execute($sql);
                    $sql = "UPDATE {bsu_edwork} SET planid=$planid_dest, practiceid=$newpractice->id 
                            WHERE yearid=$yid and planid=$planid_src and practiceid=$practice->id";
                    $DB->Execute($sql);
                }
            }
        }        
    }
}
?>