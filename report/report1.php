<?php   // by zagorodnyuk 5/09/2012
        // http://localhost/dean/blocks/bsu_plan/curriculums/report1.php
    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");  
    require_once("../../bsu_charge/lib_charge.php"); 
    require_once("../../bsu_charge/lib_charge_spec.php");
    require_once("../lib_report.php");
    // require_once("lib.php");      

    $yid = optional_param('yid', 14, PARAM_INT);			// ed yearid
    // $fid = optional_param('fidall', 0, PARAM_INT);					// faculty id
    $fid = optional_param('fid', 0, PARAM_INT);					// faculty id
    $pid = optional_param('pid', 0, PARAM_INT);                 // plan id
    $eid = optional_param('edformid', 2, PARAM_INT);
    $spid = optional_param('spid', 0, PARAM_TEXT);                 // svodni plan id
    $level = optional_param('level', 'r', PARAM_TEXT);					// discipline  id
    $term = optional_param('term', 1, PARAM_INT);
    $action = optional_param('action', '', PARAM_ACTION);		// action
    
    require_login();
    
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = get_string('reports', 'block_bsu_plan');

    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
    $PAGE->set_title($strtitle3);
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    // $PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3);
    echo $OUTPUT->header();

    $scriptname = "report1.php";
    $context = get_context_instance(CONTEXT_SYSTEM);
    $editcapability_system = has_capability('block/bsu_plan:importplan', $context);
    $editcapability_faculty = false;    


    $fid2 = $fid;
    $strlistfaculties =  listbox_department($scriptname, $fid2);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	} else {
	   $editcapability_faculty = true;
	}
    $agroups = array();

  
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    // echo $strlistfaculties;
    // listbox_all_department($scriptname, $fidall);
    listbox_all_facultys($scriptname."?level=$level", $fid);  
    if ($fid > 0)   {
        listbox_edform($scriptname."?fid=$fid&yid=$yid&level=$level", $eid);

        listbox_svod_plan($scriptname."?fid=$fid&term=$term&level=$level&edformid=$eid", $fid, $spid, $eid);
        if ($spid > 0)   {
            $planids = get_planids_from_specialityid_edformid_kvalif_profileid($fid);
            // print_object($planids);
            if(isset($planids[$spid]))  {
                $planids = explode (',', $planids[$spid]);
                
                // сортируем планы по номеру группы
                $aplansids = array();
                foreach ($planids as $planid)  {
                    $strgroups = get_plan_groups($planid);
                    $agroups = explode ('<br>', $strgroups);
                    foreach ($agroups as $agroup)   {
                         $aplansids[$agroup] = $planid;
                    }
                } 
                ksort($aplansids);
                $planids = array();
                foreach ($aplansids as $group => $planid) {
                    $planids[] = $planid;
                }
                $planids = array_unique($planids);
                // print_object($planids);

                // выводим планы в подтаблице       
                $plannames = '<table class="generalbox" border=1 cellspacing="0" cellpadding="0">';
                
                
                
                foreach ($planids as $index => $planid)  {
                    $plan = $DB->get_record_select('bsu_plan', "id = $planid", null, 'id, name as pname, specialityid, profileid, edformid, kvalif');                              
                    $bgroups = get_plan_groups_with_count_stud($planid);
                    $cgroups = array();
                    foreach ($bgroups as $bgroup)   {
                        $agroups[] = substr($bgroup, 0, 8);
                        $cgroups[] = substr($bgroup, 0, 8);
                        $aplansids[$bgroup] = $planid;
                    }      
                    
                    $cterms = get_unique_terms_groups($yid, $cgroups);
                    /*
                    foreach ($cterms as $i => $v)  {
                        $cterms[$i] .= ' семестр';
                    } 
                    */ 
                    $termsids = implode (',', $cterms);
                    $sql = "SELECT s.id as sdid
                            FROM mdl_bsu_discipline_semestr s
                            INNER JOIN mdl_bsu_discipline d ON d.id=s.disciplineid 
                            INNER JOIN mdl_bsu_plan p ON p.id=d.planid                                        
                            WHERE d.planid=$planid and s.numsemestr in ($termsids) and d.notusing=0";

                    if ($datas = $DB->get_records_sql($sql))  {
                        $href = "disciplines.php?fid=$fid&pid=$planid";
                        $plannames .= '<tr> <td align=left><a href="'.$href.'">'.$plan->id.'. </a></td>';
                        $plannames .= '<td align=left><a href="'.$href.'">'.$plan->pname.'. </a><br />'.' </td>';
                        $plannames .= ' <td align=left>'.implode(',', $bgroups).'</td>';
                        $plannames .= ' <td align=left>'.$termsids.' семестры</td>';
                    } else {
                        $href = "disciplines.php?fid=$fid&pid=$planid";
                        $plannames .= '<tr> <td align=left><a href="'.$href.'">'.$plan->id.'. </a></td>';
                        // $plannames .= '<tr> <td align=left>'.$plan->id.'. </td>';
                        $plannames .= '<td align=left><a href="'.$href.'">'.$plan->pname.'. </a><br />'.' </td>';
                        $plannames .= ' <td align=left>'.implode(',', $bgroups).'</td>';
                        $plannames .= ' <td align=left>'.$termsids.' семестры</td>';
                        
                        $planids[$index] = 0;
                        
                    }   
                    $plannames .= '</tr>'; 
                }
                $plannames .= '</table>';
                echo '<tr> <td align=right>'.get_string('curriculums', 'block_bsu_plan').': </td><td>';
                echo '<b>'.$plannames.'</b>'; // $plan->scode . '. ' .
                echo '</td></tr>';
                
                list_box_year($scriptname."?fid=$fid&pid=$pid&term=$term&level=$level", $yid);
 
                //$table = table_svodplan_report2($yid, $fid, $planids, $agroups, 0);
         }
               
        }
    }
    echo '</table>';
    
    
    // print_object($agroups);
    $igroups = array();
    foreach ($agroups as $agroup)   {            
        if ($groupid = $DB->get_field_select('bsu_ref_groups', 'id', "name = '$agroup'"))  {
            $igroups[] = $groupid;
        }
    }
    $strids = implode (',', $igroups);
    if (!empty($strids))   {

		echo $OUTPUT->heading(get_string('audfund_head', 'block_bsu_area', ''), 2);
		$table = new html_table();
		$table->align = array ('center', 'left', 'center', 'left', 'center');
		$table->head = array (get_string('npp', 'block_bsu_area'),
		                      get_string('faculty', 'block_bsu_area'),
                              get_string('floor', 'block_bsu_area'),
		                      get_string('name'),
		                      get_string('square', 'block_bsu_area'),
		                      get_string('action'));
                                     
        $sql = "SELECT distinct roomid, r.* FROM mdl_bsu_schedule s
                inner join mdl_bsu_area_room r on r.id=s.roomid
                where yearid=$yid AND groupid in ($strids)
                ORDER BY departmentcode, floor, ordername";
        if ($datas = $DB->get_records_sql($sql))    {
            // print_object($datas);
            $i=1;
            foreach($datas as $data) {
                if($faculty = $DB->get_record_sql("SELECT departmentcode, name FROM {$CFG->prefix}bsu_ref_department WHERE departmentcode=$data->departmentcode ORDER BY departmentcode")) {
				if($faculty->departmentcode == '100') {
						$faculty = trim($faculty->name);
					} else	$faculty = trim(substr($faculty->name, 3, 255));
				} else $faculty = 'Не задан';

                $action_href = '';
				$table->data[] = array($i,
										$faculty,
                                        hexdec($data->floor),
										$data->name,
										$data->square,
										$action_href);
				$i++;
			}
			echo'<center>'.html_writer::table($table).'</center>';
                
        }
    }         

  
    if (!empty($table)) {
        $options = array('action'=> 'excel', 'level' => $level, 'yid' => $yid, 'fid' => $fid, 'edformid' => $eid,
                        'pid' => $pid, 'spid' => $spid, 'term' => $term, 'sesskey' => sesskey());
        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center><br /><br />';
    }

    echo $OUTPUT->footer();



?>