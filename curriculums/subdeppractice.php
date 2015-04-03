<?PHP // $Id: subdeppractice.php,v 1.8 2011/10/20 12:29:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_charge/lib_charge.php");    
    require_once("../../bsu_charge/lib_charge_spec.php");    

    $yid = optional_param('yid', 0, PARAM_INT);					// current year
    $fid = required_param('fid', PARAM_INT);
    $planid = optional_param('pid', 0, PARAM_INT);      // plan id    
    $term = optional_param('term', 100, PARAM_INT);
	$prid = optional_param('prid', 0, PARAM_INT);			// Discipline id (courseid)
	$eid = optional_param('eid', 0, PARAM_INT);			// edworkkindid
    $sid = optional_param('sid', 0, PARAM_INT);			// speciality id
    $action = optional_param('action', '', PARAM_ACTION);		// action
    $tab = optional_param('tab', 'setsubgrandstud', PARAM_ACTION);		// action

    require_login();
    if($yid == 0) $yid = get_current_edyearid(); //  + 1;

	$scriptname = basename($_SERVER['PHP_SELF']);	// echo '<hr>'.basename(me());
	$arrscriptname = explode('.', $scriptname);
    $scriptname = $arrscriptname[0];
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = get_string('disciplines', 'block_bsu_plan');
    $strscript = get_string($scriptname, 'block_bsu_plan');    
    
    $strsearch         = get_string("search");
    $strsearchresults  = get_string("searchresults");
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strscript);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->navbar->add($strtitle,  new moodle_url("$CFG->BSU_PLAN/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3, new moodle_url("disciplines.php", array('fid' => $fid, 'pid' => $planid, 'term' => $term)));
    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

    $scriptname .= '.php';
	$strlistfaculties =  listbox_department($scriptname, $fid);
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;
    
    $kp = false;
    if($fid > 0){
        listbox_plan($scriptname."?fid=$fid", $fid, $planid);
        if ($planid > 0)   {
            $sql = "SELECT p.id, p.name as pname, s.Specyal  as sname, s.KodSpecyal as scode, p.specialityid 
                            FROM {bsu_plan} p
                            inner join {bsu_tsspecyal} s ON s.idSpecyal=p.specialityid
                            where p.id=$planid"; //  and g.groupid=$gid";        
            // echo $sql;
            if($plan = $DB->get_record_sql($sql)) {
                    echo '<tr> <td align=right>'.get_string('speciality', 'block_bsu_plan').': </td><td>';
                    echo '<b>'.$plan->sname.'</b>'; // $plan->scode . '. ' . 
                    echo '</td></tr>';
            }        

                // listbox_practice($scriptname."?fid=$fid&pid=$planid&term=$term", $planid, $prid);
                if ($prid > 0) {
                    $sql = "SELECT p.id, p.name, p.term, pt.name as ename FROM mdl_bsu_plan_practice p
                            inner join mdl_bsu_ref_edworkkind pt on pt.id = p.edworkkindid
                            where p.id=$prid";
                    $practice = $DB->get_record_sql($sql);
                    $prakname = $practice->name . ' (' . $practice->ename . ')';// $DB->get_field_select('bsu_plan_practice', 'name', "id=$prid");
                    echo '<tr align="left"> <td align=right>Учебная работа: </td><td align="left">';
                    echo $prakname;
                    echo '</td></tr>';   
                 
                    // $prakname = $DB->get_field_select('bsu_plan_practice', 'term', "id=$prid");
                    echo '<tr align="left"> <td align=right>Семестр: </td><td align="left">';
                    echo $practice->term;
                    echo '</td></tr>';   


                    $strgroups = get_plan_groups($planid, 0, true, true);
                    if ($strgroups != '')   {
                        echo '<tr> <td align=right>'.get_string('groups', 'block_bsu_plan').': </td><td>';
                        $strgroups = str_replace('<br>', ' ', $strgroups);
                        echo '<b>'.$strgroups.'</b>'; // $plan->scode . '. ' .
                        echo '</td></tr>';
                    }
                    
                    echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left"><b>';
                    $edyear = $DB->get_record_select('bsu_ref_edyear', "id = $yid");
                    echo $edyear->edyear;
                    echo '</b></td></tr>';

                    $kp = true;
                }
        }
    }                 
    echo '</table>';
   
    if ($kp) {
        $link = "?fid=$fid&pid=$planid&term=$term&prid=$prid&eid=$eid";
        //echo $OUTPUT->notification(get_string('no_inf_avail', 'block_bsu_plan'), 'notifysuccess');

        $prtypeid = $DB->get_field_select('bsu_plan_practice', 'practicetypeid', "id = $prid");
        if ($prtypeid >= 11 && $prtypeid <=14 || $prtypeid == 21 )  {
            check_podtype_0($yid, $prid);
            $frm = pedagog_savedata($yid, $fid, $planid, $prid, $prtypeid);
            // print_object($frm);
            pedagog_changelistsubdepartment($yid, $fid, $planid, $prid, $plan->specialityid, $frm, $prtypeid, $yid);
        } else {
            $frm = savedata($yid, $fid, $planid, $prid);
            changelistsubdepartment($yid, $fid, $planid, $prid, $plan->specialityid, $frm);
        }            
    }

    $link = "speccharge.php?fid=$fid";
    echo '<div align=center>'. "<a href=\"$link\">Вернуться на страницу просмотра практик и доп. видов работ</a>";
     
    echo $OUTPUT->footer();



function savedata($yid, $fid, $planid, $prid)
{
    global $CFG, $DB, $OUTPUT, $USER, $faculty;
    
    $practice = $DB->get_record_select('bsu_plan_practice', "id = $prid", null, 'id, practicetypeid, edworkkindid, term, week');

    $link = "subdeppractice.php?fid=$fid&pid=$planid&prid=$prid";
    if ($frm = data_submitted())   {
        // print_object($frm);
        $newprsubdeps = array();
        $newprsubdep = new stdClass();
        foreach($frm as $fieldname => $value)	{
    		if ($value != '')	{
                $mask = substr($fieldname, 0, 2);
                if ($mask == 'g_')	{
            	   $ids = explode('_', $fieldname);
            	   $id = $ids[1];
                   if ($id > 0) {
                        $DB->set_field_select('bsu_plan_practice_subdep', 'countsubgroups', $value, "id = $id");
                   } else {
                        $newprsubdep->countsubgroups = $value;
                   }     
                } else  if ($mask == 's_')	{
            	   $ids = explode('_', $fieldname);
            	   $id = $ids[1];
                   if ($id > 0) {
                        $DB->set_field_select('bsu_plan_practice_subdep', 'countstudents', $value, "id = $id");
                   } else {
                        $newprsubdep->countstudents = $value;
                   }     
                } else  if ($mask == 'h_')	{
            	   $ids = explode('_', $fieldname);
            	   $id = $ids[1];
                   $hours = str_replace(',', '.', $value);
                   if ($id > 0) {
                        $DB->set_field_select('bsu_plan_practice_subdep', 'hours', $hours, "id = $id");
                   }  else {
                        $newprsubdep->hours = $hours;
                   }        
                }  else if ($mask == 'k_')	{
                    $ids = explode('_', $fieldname);
                    $id = $ids[1]; 
                    if ($id > 0) {
                        if ($value > 0) {
                            $oldsubdepid = $DB->get_field_select('bsu_plan_practice_subdep', 'subdepartmentid', "id = $id");
                            // проверяем изменилась ли кафедра. Если да, то удаляем распределение преподавателей
                            if ($oldsubdepid != $value) {
                                $DB->set_field_select('bsu_plan_practice_subdep', 'subdepartmentid', $value, "id = $id");
                                if ($ids[2] > 0) {
                                     if ($edwork = $DB->get_record_select('bsu_edwork', "id = {$ids[2]}"))   { 
                                        // print "edworkmaskid=$edwork->edworkmaskid and subdepartmentid=$edwork->subdepartmentid<br />";
                                        $DB->delete_records_select('bsu_teachingload', "edworkmaskid=$edwork->edworkmaskid and subdepartmentid=$edwork->subdepartmentid");
                                     }
                                     $DB->set_field_select('bsu_edwork', 'subdepartmentid', $value, "id = {$ids[2]}");
                                     // $edworkmaskid = $DB->get_field_select('bsu_edwork', 'edworkmaskid', "id = {$ids[2]}"); 
                                     // $DB->delete_records_select('bsu_teachingload', "edworkmaskid = $edworkmaskid and subdepartmentid=$edwork->subdepartmentid");
                                }
                             }   
                        }   else {
                            $DB->delete_records_select('bsu_plan_practice_subdep', "id = $id");
                            if ($edwork = $DB->get_record_select('bsu_edwork', "id = {$ids[2]}"))   { 
                                // print "edworkmaskid=$edwork->edworkmaskid and subdepartmentid=$edwork->subdepartmentid<br />";
                                $DB->delete_records_select('bsu_teachingload', "edworkmaskid=$edwork->edworkmaskid and subdepartmentid=$edwork->subdepartmentid");
                                $DB->delete_records_select('bsu_edwork', "id = {$ids[2]}");
                            }    
                        }    
                    } else {
                        $newprsubdep->subdepartmentid = $value;
                    }                                       
                }   
            }
        }
        
        // print_object($newprsubdep);
        // exit();
        
        if (isset($newprsubdep->subdepartmentid) && !empty($newprsubdep->subdepartmentid))  {
            $newprsubdep->yearid = $yid; 
            $newprsubdep->practiceid = $prid;            
            if (!$DB->insert_record('bsu_plan_practice_subdep', $newprsubdep))	{
                print_error('Error insert in bsu_plan_practice_subdep', $link);
            }
        }
        
        if ($edmaskpr  = $DB->get_record_select('bsu_edwork_mask', "yearid = $yid AND practiceid=$prid"))  {
            $ischangehours = false;
            /*
            $sql = "SELECT ps.id, ps.practiceid as pspracticeid , ps.subdepartmentid as pssubdepartmentid, ps.countsubgroups, ps.countstudents, ps.hours as pshours, ps.podtype,
                    edm.id as edmid, edm.practiceid, edm.subdepartmentid, edm.yearid, edm.edworkmaskid, edm.planid, edm.term, edm.edworkkindid, edm.hours
                    FROM mdl_bsu_plan_practice_subdep ps
                    left join mdl_bsu_edwork edm using(practiceid,subdepartmentid)
                    where edm.yearid = $yid AND ps.practiceid=$prid";
            echo $sql . '<br />';
            if ($practice_subdeps = $DB->get_records_sql($sql)) {
            */
            if ($practice_subdeps = $DB->get_records_select('bsu_plan_practice_subdep', "yearid = $yid AND practiceid=$prid"))  {    
                
                // print_object($practice_subdeps);
    
                $edwork = new stdClass();
                $edwork->yearid = $yid;
                $edwork->planid = $planid;
                $edwork->practiceid = $prid;
                            
                foreach ($practice_subdeps as $prsubdep)    {
                    
                    // if (!isset($prsubdep->practiceid) || empty($prsubdep->practiceid))  {
                    if ($existedwork = $DB->get_record_select('bsu_edwork', "yearid = $yid AND practiceid=$prid and subdepartmentid=$prsubdep->subdepartmentid"))    {    
                        $newhours = calc_hours_for_practice_or_specvidrabot($practice, $prsubdep);                        
                        if ($newhours != $prsubdep->hours)  {
                            //  notify ("$newhours != $prsubdep->hours");
                            $DB->set_field_select('bsu_edwork', 'hours', $newhours, "id = {$existedwork->id}");
                            $ischangehours = true;
                        }
                    }  else {
                        $edwork->edworkmaskid = $edmaskpr->id;
                        $edwork->term =  $edmaskpr->term;  
                        $edwork->edworkkindid = $edmaskpr->edworkkindid;
                        $edwork->subdepartmentid = $prsubdep->subdepartmentid;
                        $edwork->hours = calc_hours_for_practice_or_specvidrabot($practice, $prsubdep);// $prsubdep->pshours;
                        // print_object($edwork);
                        if (!$DB->insert_record('bsu_edwork', $edwork)) {
                            $OUTPUT->notification('Ошибка записи в bsu_edwork_mask.');
                        } else {
                            $ischangehours = true;
                                // print_object($edwork);
                        }                        
                    }  
                }
            }
            
            if ($ischangehours) {
                $sql = "SELECT sum(hours) as hours FROM {bsu_edwork} WHERE yearid = $yid AND practiceid=$prid";
                // echo $sql . '<br />'; 
                if ($hour = $DB->get_record_sql($sql))  {
                    $DB->set_field_select('bsu_edwork_mask', 'hours', $hour->hours, "id = $edmaskpr->id");
                }
            }
        }  else {
            $strgroups = get_plan_groups($planid);
            if (!empty($strgroups)) {
                $agroups = explode ('<br>', $strgroups);
                if ($practice->edworkkindid == 13)    {
                    create_edworks_for_practice_new($yid, $planid, $practice->id, $agroups);
                } else {
                    create_edworks_for_specvidrabot($yid, $planid, $practice->id, $agroups);
                }     
            }    
        }          

        
    }            
    

    return $frm;
}



function changelistsubdepartment($yid, $fid, $planid, $prid, $specialityid, $frm)
{        
    global $CFG, $DB, $OUTPUT, $faculty, $USER;

    $allkaf = get_list_box_all_kaf($yid, $fid);

    $issubgroup = 1;
    // id, planid, practicetypeid, name, term, week, edworkkindid, timemodified, modifierid
    $sql = "SELECT p.id, pt.issubgroup, p.edworkkindid 
            FROM mdl_bsu_plan_practice p
            inner join mdl_bsu_ref_practice_type pt on pt.id = p.practicetypeid
            where p.id=$prid";
    // echo $sql;         
    if ($prtype = $DB->get_record_sql($sql))    {
        $issubgroup = $prtype->issubgroup;
    }  else {
        $issubgroup = 0;
        $sql = "SELECT p.id, p.edworkkindid 
                FROM mdl_bsu_plan_practice p
                where p.id=$prid";
        $prtype = $DB->get_record_sql($sql);
    }  
    
    if ($fid == 19999)    {
        $issubgroup = 0;
    }
    
    if ($issubgroup) {
        $strhead = 'Количество подгрупп';
    } else {
        $strhead = 'Кол-во студентов';
    }

    $table = new html_table();
    // $table->head  = array (	'Кафедра', $strhead, 'Часов нагрузки');
    $table->head  = array (	'Кафедра', 'Количество подгрупп', 'Кол-во студентов', 'Часов нагрузки');
   	$table->align = array ("left", "center", "center", "center");
  
    $sql = "SELECT ps.id, ps.subdepartmentid, s.name, ps.countsubgroups, ps.countstudents, ps.hours
            FROM mdl_bsu_plan_practice_subdep ps
            inner join mdl_bsu_vw_ref_subdepartments s on s.id=ps.subdepartmentid
            where practiceid=$prid and ps.yearid=$yid";  
    if ($subdeps = $DB->get_records_sql($sql))  {
        foreach ($subdeps as $sub){
            if ($edwork = $DB->get_record_select('bsu_edwork', "yearid = $yid and practiceid=$prid and subdepartmentid=$sub->subdepartmentid", null, 'id'))   {
                $edworkid = $edwork->id;
            } else {
                $edworkid = 0;
            }
            $strselect = html_writer::select($allkaf, 'k_'. $sub->id . '_' . $edworkid, $sub->subdepartmentid, array());
            /*
            if ($issubgroup) {
                $strinput = "<input type=text name=g_{$sub->id}_{$edworkid} size=3 value=\"$sub->countsubgroups\">";
                $strinput2 =  "<input type=text name=h_{$sub->id}_{$edworkid} size=3 value=\"$sub->hours\">";
                $table->data[] = array ($strselect, $strinput, $strinput2);
                // $strinput2 = '';
            } else {
                $strinput =  "<input type=text name=s_{$sub->id}_{$edworkid} size=3 value=\"$sub->countstudents\">";
                $strinput2 =  "<input type=text name=h_{$sub->id}_{$edworkid} size=3 value=\"$sub->hours\">";
                $table->data[] = array ($strselect, $strinput, $strinput2);
            } 
            */
            $strinput1 = "<input type=text name=g_{$sub->id}_{$edworkid} size=3 value=\"$sub->countsubgroups\">";
            $strinput2 =  "<input type=text name=s_{$sub->id}_{$edworkid} size=3 value=\"$sub->countstudents\">";
            $strinput3 =  "<input type=text name=h_{$sub->id}_{$edworkid} size=3 value=\"$sub->hours\">";
            // 59505 Жидких
            // print_object($prtype);
            // 4085 Герасименко О.А.
            /*
            if ($prtype->edworkkindid == 15 || $USER->id ==59502 || $USER->id == 3 || $USER->id == 72982 || $USER->id == 117412 || $USER->id == 6702  || $USER->id == 4085) { 
                $strinput3 =  "<input type=text name=h_{$sub->id}_{$edworkid} size=3 value=\"$sub->hours\">";
            } else {
                $strinput3 =  $sub->hours."<br /><small>(расчет часов выполняется по формуле)</small>";
            } 
            */    
            $table->data[] = array ($strselect, $strinput1, $strinput2, $strinput3);
               
        }        
    }

/*
    if ($issubgroup) {
        $strselect = html_writer::select($allkaf, 'k_0_0', 0, array());
        $strinput = "<input type=text name=g_0_0 size=3 value=0>";
        $strinput2 =  "<input type=text name=h_0_0 size=3 value=0>";
        $table->data[] = array ($strselect, $strinput, $strinput2);
        // $strinput2 = '';
    } else {
        $strselect = html_writer::select($allkaf, 'k_0_0', 0, array());
        $strinput =  "<input type=text name=s_0_0 size=3 value=0>";
        $strinput2 =  "<input type=text name=h_0_0 size=3 value=0>";
        $table->data[] = array ($strselect, $strinput, $strinput2);
    }    
*/

    $strselect = html_writer::select($allkaf, 'k_0_0', 0, array());
    $strinput1 = "<input type=text name=g_0_0 size=3 value=0>";
    $strinput2 =  "<input type=text name=s_0_0 size=3 value=0>";
    // print_object($prtype);
    if ($prtype->edworkkindid == 15 || $USER->id ==59502 || $USER->id == 3 || $USER->id == 72982 || $USER->id == 117412 || $USER->id == 6702) { 
        $strinput3 =  "<input type=text name=h_0_0 size=3 value=0>";
    } else {
        $strinput3 =  "0<br /><small>(расчет часов выполняется по формуле)</small>";
    }   
    

    $table->data[] = array ($strselect, $strinput1, $strinput2, $strinput3);
  
    echo  '<form name="addform" method="post" action="subdeppractice.php">';
    echo  '<input type="hidden" name="action" value="save">';
    echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
    echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
    echo  '<input type="hidden" name="pid" value="' .  $planid . '">';
    echo  '<input type="hidden" name="sid" value="' . $specialityid . '"/>';
    echo  '<input type="hidden" name="prid" value="' .  $prid . '"/>';
    echo  '<input type="hidden" name="sesskey" value="' .  sesskey() . '"/>';
    echo  '<input type="hidden" name="tab" value="setsubgrandstud"/>';
    
    echo'<center>'.html_writer::table($table).'</center>';
    echo  '<div align="center">';
    echo  '<input type="submit" name="savepoints" value="'. get_string('savechanges') . '"></div>';
    echo  '</form>';
    echo  '<p>&nbsp;</p>';
}
  
  

  

function savecountsubgroups($fid, $planid, $prid)
{
    global $DB;
      
   
}     


function pedagog_changelistsubdepartment($yid, $fid, $planid, $prid, $specialityid, $frm, $prtypeid, $yid)
{        
    global $CFG, $DB, $OUTPUT, $faculty;

    $strgroups = get_plan_groups($planid);
    if ($strgroups != '')   {
        $agroups = explode ('<br>', $strgroups);
    } else {
        $agroups = array();
    }   
    
    $terms = get_terms_group($yid, $agroups);
    $countstudents = get_count_students_groups($agroups);    

    $allkafedri = $DB->get_records_sql("SELECT id, name, departmentcode FROM {bsu_vw_ref_subdepartments} 
                                        WHERE departmentcode>10000 and yearid=$yid
                                        order by name");
    $facultykafedri = array();
    $anothekafedri = array();
    foreach ($allkafedri as $kafedra)   {
        if ($kafedra->departmentcode == $fid)   {
            $facultykafedri[$kafedra->id] = $kafedra->name;
        } else {
            $anothekafedri[$kafedra->id] = $kafedra->name;
        }
    }
    $allkaf = $facultykafedri + $anothekafedri;
    $allkaf[0] = 'Выберите кафедру ...';
     
    // $plan = $DB->get_record_select('bsu_plan', "id = $planid", null, 'id, specialityid');
    // $practice = $DB->get_record_select('bsu_discipline', "id = $prid", null, 'id');
    
 
    $table = new html_table();
    $table->head = array ('Подтип практики', 'Формула', 'Кафедра', 'Кол-во студентов'); // , 'Часов нагрузки'
    $table->align = array ('left', 'center', 'center'); // , 'center'
 
    $dbdatas = $DB->get_field_select('bsu_ref_practice_type', 'description', "id = $prtypeid");
    // echo "id = $prtypeid";     echo $dbdatas;
    
    $podtypes = explode('#', $dbdatas);
    // print_object($podtypes);
     
    foreach ($podtypes as $index => $podtype) {
        $formulas = explode (';', $podtype);
        // print_object($formulas);
        $idpodtype = $formulas[0]; 
        if ($data = $DB->get_record_select('bsu_plan_practice_subdep',"yearid=$yid AND practiceid={$prid} and podtype = $idpodtype"))	{ 
            $subdepartmentid = $data->subdepartmentid;
            $cntstud = $data->countstudents;
        } else {
            $practiceterm = $DB->get_field_select('bsu_plan_practice', 'term', "id = $prid");
            $subdepartmentid = 0;
            $cntstud = 0;
            foreach ($agroups as $group)    {
                if (in_array($practiceterm, $terms[$group] ))   {
                    $cntstud += $countstudents[$group];
                }
            }
        }     
        $idpodtype = trim($formulas[0]);
        $str1 = html_writer::select($allkaf, 'k~'. $idpodtype . '~' . $subdepartmentid, $subdepartmentid, array());
        $strinput =  "<input type=text name=s~{$idpodtype}~{$subdepartmentid} size=3 value=\"$cntstud\">";
        $table->data[] = array($formulas[1], $formulas[2], $str1, $strinput);           
    } 
 
    echo  '<form name="subdeppractice" method="post" action="subdeppractice.php">';
	echo  '<input type="hidden" name="action" value="save">';
    echo  '<input type="hidden" name="yid" value="' .  $yid . '">';    
	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
	echo  '<input type="hidden" name="pid" value="' .  $planid . '">';
    echo  '<input type="hidden" name="sid" value="' . $specialityid . '"/>';    
    echo  '<input type="hidden" name="prtypeid" value="' .  $prtypeid. '">';
    echo  '<input type="hidden" name="prid" value="' .  $prid . '"/>';
    echo  '<input type="hidden" name="sesskey" value="' .  sesskey() . '"/>';
    echo  '<center>'.html_writer::table($table).'</center>';
	echo  '<div align="center">';
	echo  '<input type="submit" name="savepoints" value="'. get_string('savechanges') . '"></div>';
	echo  '</form>';
    echo  '<p>&nbsp;<br /></p>';
}



function pedagog_savedata($yid, $fid, $planid, $prid, $prtypeid)
{
    global $CFG, $DB, $OUTPUT, $USER, $faculty;
    
    $practice = $DB->get_record_select('bsu_plan_practice', "id = $prid", null, 'id');
    // $prtypeid = $practice->practicetypeid; 

    $link = "subdeppractice.php?fid=$fid&pid=$planid&prid=$prid";
	if ($frm = data_submitted())   {
        foreach($frm as $field => $value)	{
            
            if ($field != '')	{
                $mask = substr($field, 0, 2);
                if ($mask == 'k~')	{
                    $ids = explode('~', $field);
                    if ($value == 0) {
                        if ($ids[2] > 0)    {
                            $podtype = $ids[1];
                            $DB->delete_records_select('bsu_plan_practice_subdep',"yearid = $yid AND practiceid={$prid} and podtype = $podtype");
                        }
                        continue;
                    } else {    
                        $podtype = $ids[1]; 
                        if ($data = $DB->get_record_select('bsu_plan_practice_subdep',"yearid = $yid AND practiceid={$prid} and podtype = $podtype"))	 {
                                        // AND subdepartmentid=$value 
                            $DB->set_field_select('bsu_plan_practice_subdep', 'subdepartmentid', $value, "id = $data->id");
                            $idx_s = 's~' . $podtype . '~' . $value;
                            if (isset($frm->{$idx_s}))   {
                                $DB->set_field_select('bsu_plan_practice_subdep', 'countstudents', $frm->{$idx_s}, "yearid = $yid AND practiceid={$prid} and podtype = $podtype");
                            }  
                                        
                        } else {
                            $rec = new stdClass();
                            $rec->yearid = $yid;
                            $rec->practiceid = $prid;            
                            $rec->subdepartmentid = $value;
                            $rec->podtype = $podtype;
                            $idx_s = 's~' . $podtype . '~' . $value;
                            if (isset($frm->{$idx_s}))   {
                                $rec->countstudents = $frm->{$idx_s}; 
                            }  
                            if (!$DB->insert_record('bsu_plan_practice_subdep', $rec))	{
                                print_error('Error insert in bsu_plan_practice_subdep', $link);
                            }
                        }
                    }    
                }
            }                                
        }
        
        calc_pedagog_practice_hours($yid, $prid, $prtypeid);
        update_charge_pedagog_practice($yid, $prid);
        
       /* 
       foreach($frm as $fieldname => $value)	{
    		if ($value != '')	{
                $mask = substr($fieldname, 0, 2);
                if ($mask == 's~')	{
            	   $ids = explode('~', $fieldname);
            	   $podtype = $ids[1];
                   $DB->set_field_select('bsu_plan_practice_subdep', 'countstudents', $value, "practiceid={$prid} and podtype = $podtype");
                }    
            }
       }     
       */
       
       
          
    }
        
        /*	   
        if (!empty($frm->add) and !empty($frm->addselect) and confirm_sesskey()) {
            $timemodified = time();
            $rec = new stdClass();
            $rec->practiceid = $practice->id;            
            foreach ($frm->addselect as $addsubdepid) {
                if ($data = $DB->get_record_select('bsu_plan_practice_subdep', 
                                   "practiceid={$practice->id} AND subdepartmentid=$addsubdepid"))	 {
                   echo $OUTPUT->notification("Кафедра $addsubdepid уже зарегистрирована на практику.");
                   return false;
                } else {
                    $rec->yearid = $yid; 
                    $rec->subdepartmentid = $addsubdepid;
                    if (!$DB->insert_record('bsu_plan_practice_subdep', $rec))	{
                        print_error('Error insert in bsu_plan_practice_subdep', $link);
                    }
                }    
			} //foreach
        } else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {
            // print_object($frm);
            foreach ($frm->removeselect as $removesubdep) {
				$DB->delete_records_select('bsu_plan_practice_subdep', 
                                            "practiceid={$practice->id} AND subdepartmentid=$removesubdep");
            }
        }
        */ 
    return $frm;
}


function check_podtype_0($yid, $prid)
{
  global $CFG, $DB;
  
  if ($datas = $DB->get_records_select('bsu_plan_practice_subdep',"yearid = $yid AND practiceid={$prid} and podtype = 0"))	{
        foreach ($datas as $data)   {
            $DB->delete_records_select('bsu_plan_practice_subdep', "id=$data->id");
        }
  }      
}


function calc_pedagog_practice_hours($yid, $prid, $prtypeid)
{
    global $DB;
    
    // $prtypeid = $DB->get_field_select('bsu_plan_practice', 'practicetypeid', "id=$prid");
    // $description = $DB->get_field_select('bsu_plan_practice', 'description', "id=$prid");
    $description = $DB->get_field_select('bsu_ref_practice_type', 'description', "id = $prtypeid");
    $datas = explode('#', $description);
    $formulas = array();
    foreach ($datas as $data)   {
        $ids = explode(';', $data);
        $id = trim($ids[0]);
        $formulas[$id] = $ids[2];
    }
    // print_object($formulas);
    if ($practices = $DB->get_records_select('bsu_plan_practice_subdep', "yearid = $yid AND practiceid=$prid"))   {
        foreach ($practices as $practice)   {
            $formula = explode ('*', $formulas[$practice->podtype]);
            $constanta = str_replace(',', '.', $formula[0]);
            $hours = $constanta*$practice->countstudents;
            $DB->set_field_select('bsu_plan_practice_subdep', 'hours', $hours, "id=$practice->id");
        }
    }
}   



function update_charge_pedagog_practice($yid, $prid)
{
    global $DB;
    
    if ($practices = $DB->get_records_select('bsu_plan_practice_subdep', "yearid = $yid AND practiceid=$prid"))   {
        if ($edworks = $DB->get_records_select('bsu_edwork',  "yearid = $yid AND practiceid = $prid")) {
            $edwork = reset($edworks);
            // if ($edworkmask = $DB->get_record_select('bsu_edwork_mask',  "practiceid = $prid")) {
            if ($edworkmask = $DB->get_record_select('bsu_edwork_mask',  "id = $edwork->edworkmaskid")) {    
            
                /*
                print_object($practices);
                print_object($edworkmask);
                print_object($edworks);
                */
                
                $practicehours = array();
                foreach ($practices as $practice)   {
                    $practicehours[$practice->subdepartmentid] = 0;
                }

                foreach ($practices as $practice)   {
                    $practicehours[$practice->subdepartmentid] += $practice->hours;
                }       
                
                // print_object($practicehours);
                                          
                                
                foreach ($practicehours as  $practicesubdepid => $practicehour)   {
                    $isexist = 0;
                    foreach ($edworks as $edwork)   {
                        // echo "$practicesubdepid == $edwork->subdepartmentid<br />";
                        
                        if ($practicesubdepid == $edwork->subdepartmentid) {
                            $isexist = 1;
                            
                            if ($practicehour != $edwork->hours) {
                                $DB->set_field_select('bsu_edwork', 'hours', $practicehour, "id = $edwork->id");
                                // echo "$practicehour != $edwork->hours<br />id = $edwork->id<br />";
                                
                            }
                        }
                    }
                    // echo $isexist .'<br />';
                    
                    if ($isexist == 0)  {
                        $edwork = new stdClass();
                        $edwork->yearid = $yid;
                        $edwork->planid = $edworkmask->planid;
                        $edwork->edworkmaskid = $edworkmask->id;
                        $edwork->practiceid = $prid;
                        $edwork->term =  $edworkmask->term;  
                        $edwork->edworkkindid = $edworkmask->edworkkindid;
                        $edwork->subdepartmentid = $practicesubdepid;
                        $edwork->hours = $practicehour;
                        // print_object($edwork);
                        if (!$DB->insert_record('bsu_edwork', $edwork)) {
                                $OUTPUT->notification('Ошибка записи в bsu_edwork_mask.');
                        } else {
                                // print_object($edwork);
                        }
                    }  
                }
                
                
                foreach ($edworks as $edwork)   {
                    $isexist = false;
                    foreach ($practices as $practice)   {
                        if ($practice->subdepartmentid == $edwork->subdepartmentid) {
                            $isexist = true;
                        }
                    }
                    if (!$isexist)  {
                        $DB->delete_records_select('bsu_edwork', "yearid = $yid AND practiceid = $edwork->practiceid AND subdepartmentid=$edwork->subdepartmentid");
                        $DB->delete_records_select('bsu_teachingload', "edworkmaskid = $edwork->edworkmaskid AND subdepartmentid=$edwork->subdepartmentid");
                   }
               } 
               
               $sumhours = $DB->get_record_sql("SELECT sum(hours) as summa FROM mdl_bsu_edwork where edworkmaskid=$edworkmask->id");
               $DB->set_field_select('bsu_edwork_mask', 'hours', $sumhours->summa, "id = $edworkmask->id");        
           }             
       }
    }
}



function calc_hours_for_practice_or_specvidrabot($practice, $subdep)
{
    global $DB;
    
    if ($practice->edworkkindid == 13)    {
        $calcfunc = $DB->get_field_select ("bsu_ref_practice_type", 'calcfunc', "id=$practice->practicetypeid");
        if (function_exists($calcfunc)) {
            $hours = $calcfunc($practice, $subdep);
        } else {
            $hours = 0;
        }            
    } else {
        $calcfunc = 'calcfunc_edworkkindid_' . $practice->edworkkindid;
        if (function_exists($calcfunc)) {
            $hours = $calcfunc($practice, $subdep, $subdep->countstudents);
        } else {
            $hours = 0;
        }    
    }
    // echo $calcfunc;
    return $hours;
    
}

?>