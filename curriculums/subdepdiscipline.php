<?PHP // $Id: subdepdiscipline.php,v 1.8 2011/10/20 12:29:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_charge/lib_charge.php");    
    require_once("../../bsu_charge/lib_charge_spec.php");

    $fid = required_param('fid', PARAM_INT);
    $planid = optional_param('pid', 0, PARAM_INT);      		// plan id    
    $term = optional_param('term', 1, PARAM_INT);
	$did = optional_param('did', 0, PARAM_INT);					// Discipline id (courseid)
	$eid = optional_param('eid', 0, PARAM_INT);					// edworkkindid
    $sid = optional_param('sid', 0, PARAM_INT);					// speciality id
    $action = optional_param('action', '', PARAM_ACTION);		// action
    $tab = optional_param('tab', '', PARAM_ACTION);				// action
    $plantab = optional_param('plantab', '', PARAM_ACTION);				// action
    
    $yid = optional_param('yid', 0, PARAM_INT);					// current year
    $sdid = optional_param('sdid', 0, PARAM_INT);				// subdepartment id
    $id = optional_param('id', 0, PARAM_INT);					// id record from table bsu_discipline_subdepartment_zav
    
    if ($yid == 0)   {
        $yid = get_current_edyearid();
        // $yid++;
    }

    require_login();

	if($sdid) {
		$d = $DB->get_record_sql("SELECT disciplinenameid FROM {bsu_discipline} WHERE id=$did");
		$d = $d->disciplinenameid;

		$new_record = new stdClass();
		$new_record->yearid = $yid;
		$new_record->disciplineid = $did;
		$new_record->specialityid=$sid;
		$new_record->disciplinenameid = $d;
		$new_record->subdepartmentid = $sdid;
		$new_record->timemodified = time();
		$new_record->usermodified = $USER->id;

		$sql = "SELECT id FROM {bsu_discipline_subdepartment} 
				WHERE
					yearid=$yid AND 
					disciplineid=$did AND
					specialityid=$sid AND 
					disciplinenameid=$d AND
					subdepartmentid=$sdid
		       ";
		if(!$DB->get_records_sql($sql)) {
			$DB->insert_record('bsu_discipline_subdepartment', $new_record);
			$update = new stdClass();
			$update->id = $id;
			$update->deleted = 1;
			$update->timemodified = time();
			$update->usermodified = $USER->id;
			
			$DB->update_record('bsu_discipline_subdepartment_zav', $update);
		}
	}


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
    $PAGE->navbar->add($strtitle3, new moodle_url("disciplines.php", array('fid' => $fid, 'pid' => $planid, 'tab' => $plantab, 'yid' => $yid)));
    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

/*
    if (!is_siteadmin())    {
        notify ('Доступ временно закрыт.');
        echo $OUTPUT->footer();
        exit();
    }
*/

    $scriptname .= '.php';
	$strlistfaculties =  listbox_department($scriptname, $fid);
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

/*
    if ($action == 'set1')  {
        $discipsubdeps = $DB->get_records_sql("SELECT id, subdepartmentid FROM {bsu_discipline_subdepartment}
                                               WHERE yearid = $yid and disciplineid=$did");
        print_object($discipsubdeps);                                               
        $discipsubdep = reset($discipsubdeps); 
        print_object($discipsubdep);
        $DB->set_field_select('bsu_discipline_subdepartment_groups', 'subdepartmentid', $discipsubdep->subdepartmentid,  "disciplineid=$did");
    }
*/
    
    if ($action == 'delraspr')  {
        $DB->delete_records_select('bsu_discipline_subdepartment_groups', "disciplineid=$did");
    }
    
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;

    $DISCIPSUBDEPS = $DB->get_records_sql("SELECT id, subdepartmentid FROM {bsu_discipline_subdepartment}
                                           WHERE yearid = $yid and disciplineid=$did");

    if ((count($DISCIPSUBDEPS)>1 && $tab == '') || $tab == 'chargekafedri')   {
        $tab = 'chargekafedri';
    }  else {
        $tab = 'setkafedri';
    }   
    
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
            /*
            if ($term > 0) {
            listbox_term($scriptname."?fid=$fid&pid=$planid", $fid, $planid, $term);
            }
            */
            // if ($term > 0) {

/*
                if (is_siteadmin()) {
                    listbox_discipline($scriptname."?fid=$fid&pid=$planid&term=$term", $fid, $planid, $term, $did);
                } else { 
                    $strsql = "SELECT d.id, n.Name as nname
                                FROM {bsu_discipline} d
                                INNER JOIN {bsu_ref_disciplinename} n ON n.Id=d.disciplinenameid
                                WHERE d.id = $did";
                    $dd = $DB->get_record_sql($strsql);
                    echo '<tr> <td align=right>'.get_string('speciality', 'block_bsu_plan').': </td><td>';
                    echo '<b>'.$dd->nname.'</b>'; // $plan->scode . '. ' . 
                    echo '</td></tr>';
                }    
*/

                    $strsql = "SELECT d.id, n.Name as nname, d.disciplinenameid
                                FROM {bsu_discipline} d
                                INNER JOIN {bsu_ref_disciplinename} n ON n.Id=d.disciplinenameid
                                WHERE d.id = $did";
                    $dd = $DB->get_record_sql($strsql);
                    echo '<tr> <td align=right>'.get_string('discipline', 'block_bsu_plan').': </td><td>';
                    echo '<b>'.$dd->nname.'</b>'; // $plan->scode . '. ' . 
                    echo '</td></tr>';

                
                if ($did > 0) {
                    $strgroups = get_plan_groups($planid);
                    if ($strgroups != '')   {
                        $agroups = explode ('<br>', $strgroups);
                        echo '<tr> <td align=right>'.get_string('groups', 'block_bsu_plan').': </td><td>';
                        $strgroups = str_replace('<br>', ', ', $strgroups);
                        echo '<b>'.$strgroups.'</b>'; // $plan->scode . '. ' .
                        echo '</td></tr>';
                    } else {
                        $agroups = array();
                    }
                    $edyear= $DB->get_field_select('bsu_ref_edyear', 'edyear', "id = $yid");
                    echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left">';
                    echo $edyear;
                    echo '</td></tr>';

                    $kp = true;
                }
           //  }
        }
    }                 
    echo '</table>';
   
    if ($kp) {
        $link = "?yid=$yid&fid=$fid&pid=$planid&term=$term&did=$did&eid=$eid";
        //echo $OUTPUT->notification(get_string('no_inf_avail', 'block_bsu_plan'), 'notifysuccess');
        print_tabs_actions($scriptname.$link, $tab, $did, $yid);
        // print_object($frm);
        switch ($tab)   {
            case 'setkafedri':
                    // 7886  Забнина
                    // 106008 Ковалев
                    // 72982 Бондарева
                    // 52652 Дворяшина
                    // 59505 Жидких
                    // 93089 Куценко
                    // 67505 Терехова
                    // 6702  Шевченко
                    // 67543 Беленко
                    // 2065  Немцев С.Н.
                    // 91946, 91870, 20, 1835, 53756 з/о
                    // 61848 Литвинова
                    // 66281 Бондаренко
                    // 100315 Палышева
                    $ACCESS_USER = array(7886, 3, 18, 106008, 66281, 52652, 100315, 72982, 6702, 2053); 
                    // 72982, 59502, 67543, 2065, 91946, 91870, 20, 1835, 53756, 93089,
                    
                    if (!in_array($USER->id, $ACCESS_USER)&&!is_siteadmin()) { //  &&  
                    // if (!is_siteadmin()) {
                        // notice(get_string('permission', 'block_bsu_plan'), '../index.php');
                        notice('Доступ закрыт. Для назначения кафедр обращайтесь к Забниной Галине Геннадьевне (т. 30-14-81)');
                    }    
                    
                    $frm = savedata($yid, $fid, $planid, $did, $plan->specialityid);
                    changelistsubdepartment($yid, $fid, $planid, $did, $plan->specialityid, $frm);

                    $d = $DB->get_record_sql("SELECT disciplinenameid FROM {bsu_discipline} WHERE id=$did");
                    $d = $d->disciplinenameid;

                    $sql = "SELECT id, subdepartmentid FROM {bsu_discipline_subdepartment_zav} 
                    		WHERE 
                    			disciplinenameid=$d AND
                    			yearid=$yid AND 
                    			deleted=0
                       ";
                    if($records = $DB->get_records_sql($sql)) {
	                    echo '<center><h3>Список кафедр претендующих на эту дисциплину</h3></center>';
					    $table = new html_table();
					    $table->head  = array ('Кафедра', 'Действие'); 
					   	$table->align = array ("left", "center"); 
    
                    	$departments = $DB->get_records_sql_menu("SELECT id, name FROM {bsu_vw_ref_subdepartments} WHERE yearid=$yid");
	                    foreach($records as $record) {
	                    	$ids = explode(',', $record->subdepartmentid);
	                    	foreach($ids AS $key=>$value) {
	                    		$href = "<a href='subdepdiscipline.php?fid=$fid&pid=$planid&did=$did&sid=$plan->specialityid&yid=$yid&sdid=$value&id=$record->id'>Утвердить</a>";
								$table->data[] = array ($departments[$value], $href);	
							}
						}
						echo'<center>'.html_writer::table($table).'</center>';
					}
                    
                    
            break;                     
            case 'chargekafedri':
                    if (count($DISCIPSUBDEPS)>1)    {
                        $frm = savecharge($yid, $fid, $planid, $did);
                        /*
                        echo $OUTPUT->heading('<font color=red><b>ВНИМАНИЕ!!! <br />Прежде чем выполнить распределение по кафедрам <br />
                        необходимо создать недостающие подгруппы и сформировать отсутствующие потоки для данной дисциплины. <br />Эти операции выполняются в блоке "Учебные планы".</b></font>');
                        */
                        // notify('Страница в стадии разработки.');
                        update_bsu_discipline_subdepartment_groups($yid, $fid, $planid, $did, $dd->disciplinenameid, $agroups);
                        chargekafedri($yid, $fid, $planid, $did, $dd->disciplinenameid, $agroups);
                        /*
                        $options = array('action'=> 'set1', 'yid' => $yid, 'fid' => $fid, 'pid' => $planid, 'did' => $did, 'tab' => $tab, 'sesskey' => sesskey());
                        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), 'Поставить по умолчанию первую кафедру', 'get', $options).'</center><br>';
            
                        
                        $options = array('action'=> 'set2', 'yid' => $yid, 'fid' => $fid, 'pid' => $planid, 'did' => $did, 'tab' => $tab, 'sesskey' => sesskey());
                        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), 'Поставить по умолчанию вторую кафедру', 'get', $options).'</center><br>';
                        */
                    }   else {
                        notify ('Для одной кафедры распределение групп и подгрупп не выполняется.');
                    } 
            break;        
        }        
    }

    echo $OUTPUT->footer();

// печать вкладок
function print_tabs_actions($scriptname, $currtab, $did, $yid)
{
    global $DB, $DISCIPSUBDEPS;
    
    $toprow = array();
    //if (count ($DISCIPSUBDEPS)>1)   {
        $toprow[] = new tabobject('chargekafedri', $scriptname."&tab=chargekafedri", 'Распределение по кафедрам');
    // }    
    $toprow[] = new tabobject('setkafedri', $scriptname."&tab=setkafedri", 'Назначение кафедр');

    $tabs = array($toprow);
    print_tabs($tabs, $currtab, NULL, NULL);
}


// сохранение изменного списка кафедр, закрепеленных за дисциплиной
function savedata($yid, $fid, $pid, $did, $specialityid)
{
    global $CFG, $DB, $OUTPUT, $USER, $faculty;
    
    $discipline = $DB->get_record_select('bsu_discipline', "id = $did", null, 'id, disciplinenameid');
    $disciplinenameid = $discipline->disciplinenameid;

    $link = "subdepdiscipline.php?yid=$yid&fid=$fid&pid=$pid&did=$did";
	if ($frm = data_submitted())   {
	   // print_object($frm); exit();
	   foreach($frm as $fieldname => $newsubdep)	{
    		if ($newsubdep != '')	{
                $mask = substr($fieldname, 0, 2);
                if ($mask == 'k_')	{
                    $ids = explode('_', $fieldname);
                    $id = $ids[1]; 
                    $oldsubdep = $ids[2]; 
                    if ($id > 0) {
                        if ($newsubdep == 0) {
                            $DB->delete_records_select('bsu_discipline_subdepartment', "id = $id");
                            // проверяем назначена ли кафедра на дисциплину. Если нет, то удаляем нагрузку
                            if (!$data = $DB->get_records_select('bsu_discipline_subdepartment', "disciplineid=$did and yearid=$yid"))   {
                                delete_discipline_charge($yid, $did, $pid);
                                notify('Нагрузка по дисциплине удалена.', 'notifysuccess');
                            } else {
                                if (count($data) == 1)  {
                                    $DB->delete_records_select('bsu_discipline_subdepartment_groups', "disciplineid=$did and yearid=$yid");
                                }
                            }
                            // обновляем кафедру и передаем нагрузку на другую кафедру
                         } if ($newsubdep > 0 && $oldsubdep != $newsubdep) {                        
                            $DB->set_field_select('bsu_discipline_subdepartment', 'subdepartmentid', $newsubdep, "id = $id");
                            change_subdepartment_discipline_in_charge($yid, $pid, $did, $oldsubdep, $newsubdep);
                            notify('Нагрузка по дисциплине переведена на другую кафедру.', 'notifysuccess');
                        }
                    } else if ($id == 0)    {
                        if ($newsubdep > 0) {
                            if ($data = $DB->get_record_select('bsu_discipline_subdepartment', "disciplineid=$did and yearid=$yid AND subdepartmentid = $newsubdep")) { // AND specialityid=$specialityid AND subdepartmentid=$addsubdepid"
                                echo $OUTPUT->notification("Кафедра $newsubdep уже зарегистрирована на дисциплину.");
                            } else {
                                $rec = new stdClass();
                                $rec->disciplineid = $did;
                                $rec->specialityid = $specialityid;            
                    			$rec->disciplinenameid = $disciplinenameid;
                                $rec->subdepartmentid = $newsubdep;
                                $rec->yearid = $yid;
                                if (!$DB->insert_record('bsu_discipline_subdepartment', $rec))	{
                                    print_error('Error insert in bsu_discipline_subdepartment', $link);
                                }
                                // создаем нагрузку по дисциплине
                                $subdepsids = $DB->get_records_select_menu('bsu_discipline_subdepartment', "yearid=$yid AND disciplineid=$did", null, '', 'id, subdepartmentid');
                                // print_object($subdepsids);
                                $cntsubdep = count($subdepsids);
                                // echo $cntsubdep . '<br />';
                                $strgroups = get_plan_groups($pid);
                                if (!empty($strgroups)) {    
                                    $agroups = explode ('<br>', $strgroups);
                                    delete_discipline_charge($yid, $did, $pid);
                                    if ($cntsubdep > 1)    {
                                        // print_object($agroups);
                                        update_bsu_discipline_subdepartment_groups($yid, $fid, $pid, $did, $disciplinenameid, $agroups);
                                        create_edworks_for_discipline($yid, $pid, $did, $agroups);
                                    }  else {
                                        create_edworks_for_discipline($yid, $pid, $did, $agroups);
                                    }
                                    notify('Нагрузка по дисциплине сгенерирована.', 'notifysuccess');
                                    // restore_teachingload($yid, $did, $planid);   
                                }     
                            }    
                        } 
                    } 
                }
            }
        }                   
    }
    return $frm;
}


// отображение листбоксов со списком кафедр
function changelistsubdepartment($yid, $fid, $pid, $did, $specialityid, $frm)
{        
    global $CFG, $DB, $OUTPUT, $faculty;

    $allkaf = get_list_box_all_kaf($yid, $fid);
    
    $table = new html_table();
    $table->head  = array ('Кафедра'); 
   	$table->align = array ("left"); 
    
    $sql = "SELECT id, subdepartmentid FROM {bsu_discipline_subdepartment}
            WHERE yearid = $yid and disciplineid=$did";      
    if ($discipsubdeps = $DB->get_records_sql($sql))  {
        foreach ($discipsubdeps as $discipsubdep)  {           
            $strselect = html_writer::select($allkaf, 'k_'. $discipsubdep->id . '_' . $discipsubdep->subdepartmentid, $discipsubdep->subdepartmentid, array());
            $table->data[] = array ($strselect);
        }
    }  
    
    $strselect = html_writer::select($allkaf, 'k_0_0', 0, array());
    $table->data[] = array ($strselect);


    echo  '<form name="addform" method="post" action="subdepdiscipline.php">';
    echo  '<input type="hidden" name="action" value="save">';
    echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
    echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
    echo  '<input type="hidden" name="pid" value="' .  $pid . '">';
    echo  '<input type="hidden" name="sid" value="' . $specialityid . '"/>';
    echo  '<input type="hidden" name="did" value="' .  $did . '"/>';
    echo  '<input type="hidden" name="sesskey" value="' .  sesskey() . '"/>';
    echo  '<input type="hidden" name="tab" value="setkafedri"/>';
    
    echo'<center>'.html_writer::table($table).'</center>';
    echo  '<div align="center">';
    echo  '<input type="submit" name="savepoints" value="'. get_string('savechanges') . '"></div>';
    echo  '</form>';
    echo  '<p>&nbsp;</p>';
}
  
  
function get_list_streams_for_edworkkind($yid, $planid, $termsids, $disciplinenameid, $edworkkindid, $edworkkind)
{
    global $DB;
    $sql = "SELECT s.id as ssid, s.streammaskid, sm.planid, sm.disciplinenameid, sm.term,
                    sm.edworkkindid, s.groupid, s.subgroupid, s.numstream  
                    FROM {bsu_discipline_stream} s 
                    INNER JOIN {bsu_discipline_stream_mask} sm ON sm.id=s.streammaskid               
                    where  sm.planid=$planid AND sm.disciplinenameid=$disciplinenameid AND 
                    sm.term in ($termsids) AND yearid=$yid AND sm.edworkkindid=$edworkkindid"; 
    // echo $sql;                
    $astream = array();
    if ($streams = $DB->get_records_sql($sql))   {
        // print_object($streams);
        foreach ($streams as $stream)   {
            $edname = $edworkkind[$edworkkindid];
            $index = $stream->groupid . '_' . $stream->subgroupid . '_' . $stream->streammaskid . '_' . $stream->numstream;
            // $index = $stream->streammaskid . '_' . $stream->numstream;
            $astream[$index] = 'Поток ' . $stream->numstream . " ({$stream->term}-й сем., $edname):<br />";
        }
        foreach ($streams as $stream)   {
            $index = $stream->groupid . '_' . $stream->subgroupid . '_' . $stream->streammaskid . '_' . $stream->numstream;
            $strgroup = $DB->get_field_select('bsu_ref_groups', 'name', "id = $stream->groupid");
            $astream[$index] .= $strgroup;
            if ($stream->subgroupid > 0)    {
                $strsubgroup = $DB->get_field_select('bsu_discipline_subgroup', 'name', "id = $stream->subgroupid");
                $astream[$index] .=  '->' . $strsubgroup . '<br>';
            } else {
                $astream[$index] .=  '<br>';
            }
            
        }    
    }
    
    // print_object($astream);
    
    
    return $astream; 
} 


function get_name_stream($streammaskid, $numstream, $edworkkind)
{
    global $DB;
    
    $sql = "SELECT s.id as ssid, s.streammaskid, sm.planid, sm.disciplinenameid, sm.term,
                    sm.edworkkindid, s.groupid, s.subgroupid, s.numstream  
                    FROM {bsu_discipline_stream} s 
                    INNER JOIN {bsu_discipline_stream_mask} sm ON sm.id=s.streammaskid               
                    where  sm.id=$streammaskid AND s.numstream=1"; 
    // echo $sql;                
    $astream = array();
    if ($streams = $DB->get_records_sql($sql))   {
        // print_object($streams);
        foreach ($streams as $stream)   {
            $edname = $edworkkind[$stream->edworkkindid];
            $index = $stream->streammaskid . '_' . $stream->numstream;
            $astream[$index] = 'Поток ' . $stream->numstream . " ($stream->term семестр, $edname):<br />";
        }
        foreach ($streams as $stream)   {
            $index = $stream->streammaskid . '_' . $stream->numstream;
            $strgroup = $DB->get_field_select('bsu_ref_groups', 'name', "id = $stream->groupid");
            $astream[$index] .= $strgroup;
            if ($stream->subgroupid > 0)    {
                $strsubgroup = $DB->get_field_select('bsu_discipline_subgroup', 'name', "id = $stream->subgroupid");
                $astream[$index] .=  '->' . $strsubgroup . '<br>';
            } else {
                $astream[$index] .=  '<br>';
            }
            
        }    
    }
    
    // print_object($astream);
    
    
    return $astream; 
    
}


function savecharge($yid, $fid, $pid, $did)
{
    global $CFG, $DB, $OUTPUT, $USER, $faculty;
    
    $discipline = $DB->get_record_select('bsu_discipline', "id = $did", null, 'id, disciplinenameid');
    $disciplinenameid = $discipline->disciplinenameid;

    $link = "subdepdiscipline.php?yid=$yid&fid=$fid&pid=$pid&did=$did";
	if ($frm = data_submitted())   {
        // print_object($frm);
        $dissubdeps = array();
        if (isset($frm->remove)) {
            foreach($frm->removeselect as $index)	{
                $ids2 = explode('~', $index);
                $ids = explode('_', $ids2[1]);
                
                $field = $ids[0];
                $dissubdeps[$field] = new stdClass();
                $dissubdeps[$field]->id = $ids[0]; 
                $dissubdeps[$field]->subdepartmentid = $frm->sid2;
            }
       } else if (isset($frm->add)) {
            foreach($frm->addselect as $index)	{
                $ids2 = explode('~', $index);
                $ids = explode('_', $ids2[1]);
                
                $field = $ids[0];
                $dissubdeps[$field] = new stdClass();
                $dissubdeps[$field]->id = $ids[0]; 
                $dissubdeps[$field]->subdepartmentid = $frm->sid1;
            }
       }
       // print_object($dissubdeps);
       
       foreach ($dissubdeps as $dissubdep)  {
            if (!$DB->update_record('bsu_discipline_subdepartment_groups', $dissubdep))  {
                notify ('ERROR update in bsu_discipline_subdepartment_groups');
            }
       }        
	}   
} 

// изменение кафедр в таблицах нагрузки
function change_subdepartment_discipline_in_charge($yid, $pid, $did, $oldsubdep, $newsubdep)
{
    global $DB;
    
    $sql = "SELECT id, edworkmaskid FROM mdl_bsu_edwork
            WHERE yearid=$yid and disciplineid = $did and subdepartmentid=$oldsubdep";            
    if ($edworks = $DB->get_records_sql($sql))  {
        $edw = array();
        $edm = array();
        foreach ($edworks as $edwork)   {
            $edw[] = $edwork->id;
            $edm[] = $edwork->edworkmaskid;
        }
        
        $stredw = implode(',', $edw);
        $stredm = implode(',', $edm);
        
        $sql = "UPDATE mdl_bsu_edwork set subdepartmentid=$newsubdep WHERE id in ($stredw)";
        // echo $sql;
        $DB->Execute($sql);
        $DB->set_field_select('bsu_edwork_mask', 'subdepartmentid', $newsubdep, "id in ($stredm) and planid = $pid");
        
        if ($edms = $DB->get_records_select('bsu_edwork_mask', "id in ($stredm) and planid = $pid"))    {
            foreach ($edms as $edm) {
                $DB->set_field_select('bsu_edwork', 'subdepartmentid', $newsubdep, "edworkmaskid=$edm->id");
                $DB->delete_records_select('bsu_teachingload', "edworkmaskid=$edm->id");
            }
        }
    }   
}      
  
  
  

function chargekafedri($yid, $fid, $planid, $did, $disciplinenameid, $agroups)
{
    global $CFG, $DB, $action, $DISCIPSUBDEPS, $OUTPUT;
    
    $edworkkind = $DB->get_records_select_menu('bsu_ref_edworkkind', '', null, 'id', 'id, ir_name'); //description, ir_name name
  
    if (count($DISCIPSUBDEPS)>2)    {
        notify ('Количество кафедр больше двух. Распределение невозможно.');
        return;
    }
    
    $subdep1 = reset($DISCIPSUBDEPS);
    $subdep2 = end($DISCIPSUBDEPS);
    $sid1 = $subdep1->subdepartmentid;
    $sid2 = $subdep2->subdepartmentid;
    
    $subdepnames = array();
    $subdepnames[$sid1] = $DB->get_field_select('bsu_vw_ref_subdepartments', "name", "id = $sid1");
    $subdepnames[$sid2] = $DB->get_field_select('bsu_vw_ref_subdepartments', "name", "id = $sid2");

    $listbox = array();
    $listbox[$sid1] = array();
    $listbox[$sid2] = array();
    $sizelistbox = 0;    
    // проверяем распределение групп по кафедрам 
    foreach ($agroups as $agroup)   {
        $sizelistbox++;
        $groupid = $DB->get_field_select('bsu_ref_groups', 'id', "name = '$agroup'");
        $conditions = array('yearid' => $yid, 'disciplineid' => $did, 'groupid' => $groupid, 
                            'subgroupid' => 0, 'streammaskid' => 0, 'numstream' => 0);
        if ($subdepgroup = $DB->get_record('bsu_discipline_subdepartment_groups', $conditions))  {
           if ($subdepgroup->subdepartmentid == 0)   {
                $DB->set_field_select('bsu_discipline_subdepartment_groups', 'subdepartmentid', $sid1, "id = $subdepgroup->id");
                $subdepgroup->subdepartmentid = $sid1;
           }
           $index = 's~'.$subdepgroup->id.'_'.$did. '_' . $groupid . '_0_0_0';
           $listbox[$subdepgroup->subdepartmentid][$index] = $agroup;          
        } else {
           $conditions['subdepartmentid'] = $sid1;
           $rec = (object)$conditions;
           $subdepgrid = $DB->insert_record('bsu_discipline_subdepartment_groups', $rec);
           $index = 's~'.$subdepgrid.'_'.$did. '_' . $groupid . '_0_0_0';
           $listbox[$sid1][$index] = $agroup;
        }
    }

    // проверяем распределение подгрупп по кафедрам
    $sql = "SELECT id, groupid, name, shortname, countstud, notusing 
            FROM {bsu_discipline_subgroup}
    	    WHERE yearid=$yid and disciplineid=$did AND notusing=0";
    if ($subgroups = $DB->get_records_sql($sql)) {   
        foreach ($subgroups as $subgroup)   {
            $sizelistbox++;
            $groupname = $DB->get_field_select('bsu_ref_groups', 'name', "id = $subgroup->groupid");
            $itemname = $groupname . '->' . $subgroup->name . " ($subgroup->countstud ст.)";         
            
            $conditions = array('yearid' => $yid, 'disciplineid' => $did, 'groupid' => $subgroup->groupid, 
                                'subgroupid' => $subgroup->id, 'streammaskid' => 0, 'numstream' => 0);
            if ($subdepgroup = $DB->get_record('bsu_discipline_subdepartment_groups', $conditions))  {
               if ($subdepgroup->subdepartmentid == 0)   {
                    $DB->set_field_select('bsu_discipline_subdepartment_groups', 'subdepartmentid', $sid1, "id = $subdepgroup->id");
                    $subdepgroup->subdepartmentid = $sid1;
               }
               $index = 's~'. $subdepgroup->id .'_'.$did. '_'. $subgroup->groupid . '_'. $subgroup->id . '_0_0';
               $listbox[$subdepgroup->subdepartmentid][$index] = $itemname;          
            } else {
                $conditions['subdepartmentid'] = $sid1;
                $rec = (object)$conditions;
                
                $subdepgrid = $DB->insert_record('bsu_discipline_subdepartment_groups', $rec);
                $index = 's~'. $subdepgrid .'_'.$did. '_'. $subgroup->groupid . '_'. $subgroup->id . '_0_0';
                $listbox[$sid1][$index] = $itemname;
            }
        }
    }         

    // проверяем распределение потоков по кафедрам
    $alledworkkinds = array(1,2,3,4,5,10,39);
    $terms = get_terms_group($yid, $agroups);
    $atermsids = array();
    foreach ($terms as $term)   {
        foreach ($term as $t)   {
            $atermsids[] = $t;
        }    
    } 
    $atermsids = array_unique($atermsids);
    // print_object($terms);    echo '<hr>';
    $termsids = implode (',', $atermsids);    
        
    foreach ($alledworkkinds as $edkk)  {
        $astreams = get_list_streams_for_edworkkind($yid, $planid, $termsids, $disciplinenameid, $edkk, $edworkkind);
        // print_object($astreams);
        foreach ($astreams as $indexstream => $itemname)   {
            $sizelistbox++;
            
            list($groupid, $subgroupid, $streammaskid, $numstream) = explode ('_', $indexstream);         
            $conditions = array('yearid' => $yid, 'disciplineid' => $did, 'groupid' => $groupid, 
                                'subgroupid' => $subgroupid, 'streammaskid' => $streammaskid, 'numstream' => $numstream);
            if ($subdepgroup = $DB->get_record('bsu_discipline_subdepartment_groups', $conditions))  {
               if ($subdepgroup->subdepartmentid == 0)   {
                    $DB->set_field_select('bsu_discipline_subdepartment_groups', 'subdepartmentid', $sid1, "id = $subdepgroup->id");
                    $subdepgroup->subdepartmentid = $sid1;
               }
               $index = 's~' . $subdepgroup->id . '_'.$did. '_'. $indexstream;               
               $listbox[$subdepgroup->subdepartmentid][$index] = $itemname;
            } else {
                /*
                $conditions['subdepartmentid'] = $sid1;
                $rec = (object)$conditions;
                $subdepgrid = $DB->insert_record('bsu_discipline_subdepartment_groups', $rec);
                $index = 's~' . $subdepgrid . '_'.$did. '_'. $indexstream;
                $listbox[$sid1][$index] = $itemname;
                */
            }
        }
    }
    
    $link1 = $CFG->wwwroot . "/blocks/bsu_charge/disciplinecharge.php?fid=$fid&pid=$planid&yid=$yid&did=$did&sid=$sid1";
    $strlinkupdate1 = "<a target=\"_blank\" href='$link1'><img class='icon' title='Посмотреть нагрузку по дисциплине' src='".$OUTPUT->pix_url('i/charge')."'>Открыть нагрузку по 1-й кафедре</a>";

    $link2 = $CFG->wwwroot . "/blocks/bsu_charge/disciplinecharge.php?fid=$fid&pid=$planid&yid=$yid&did=$did&sid=$sid2";
    $strlinkupdate2 = "<a target=\"_blank\" href='$link2'><img class='icon' title='Посмотреть нагрузку по дисциплине' src='".$OUTPUT->pix_url('i/charge')."'>Открыть нагрузку по 2-й кафедре</a>";

?>


<form name="enrolform" id="enrolform" method="post" action="subdepdiscipline.php">
<input type="hidden" name="yid" value="<?php echo $yid ?>" />
<input type="hidden" name="fid" value="<?php echo $fid ?>" />
<input type="hidden" name="pid" value="<?php echo $planid ?>" />
<input type="hidden" name="did" value="<?php echo $did ?>" />
<input type="hidden" name="sid1" value="<?php echo $sid1 ?>" />
<input type="hidden" name="sid2" value="<?php echo $sid2 ?>" />
<input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
  <table align="center" border="0" cellpadding="5" cellspacing="0" >
    <tr>
      <td valign="top">
          <?php
              echo '<b>' . $subdepnames[$sid1] . '</b>';
          ?>
      </td>
      <td></td>
      <td valign="top">
          <?php
              echo '<b>' . $subdepnames[$sid2] . '</b>';
          ?>
      </td>
    </tr>
    <tr>
      <td valign="top" align="right">
          <select name="removeselect[]" size="<?php echo $sizelistbox; ?>" id="removeselect"  multiple
                  onFocus="document.enrolform.add.disabled=true;
                           document.enrolform.remove.disabled=false;
                           document.enrolform.addselect.selectedIndex=-1;" />
          <?php
          if (!empty($listbox[$sid1]))	{
              foreach ($listbox[$sid1] as $key => $value) {
                  echo "<option value=\"$key\">" . $value . "</option>\n";
              }
          }
          ?>
          </select></td>
      <td id="buttonscell" align="center" >
          <div id="addcontrols" width="100px">
              <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.' В первую кафедру '; ?>" title="В первую кафедру" /><br />
          </div>
          <div id="removecontrols">
              <input name="remove" id="remove" type="submit" value="<?php echo ' Во вторую кафедру' .'&nbsp;'.$OUTPUT->rarrow();; ?>" title="Во вторую кафедру" />
          </div>
          
      </td>
      <td valign="top">
          <select name="addselect[]" size="<?php echo $sizelistbox; ?>" id="addselect"  multiple
                  onFocus="document.enrolform.add.disabled=false;
                           document.enrolform.remove.disabled=true;
                           document.enrolform.removeselect.selectedIndex=-1;">
          <?php
          if (!empty($listbox[$sid2]))	{
              foreach ($listbox[$sid2] as $key => $value) {
                  echo "<option value=\"$key\">" . $value . "</option>\n";
              }
          }
          ?>
         </select>
       </td>
    </tr>
    <tr>
      <td valign="top">
          <?php
              echo $strlinkupdate1;
          ?>
      </td>
      <td></td>
      <td valign="top">
          <?php
              echo $strlinkupdate2;
          ?>
      </td>
    </tr>    
  </table>
</form>
<?php             

}


function update_bsu_discipline_subdepartment_groups($yid, $fid, $planid, $did, $disciplinenameid, $agroups)
{
    global $DB;
    
    $edworkkind = $DB->get_records_select_menu('bsu_ref_edworkkind', '', null, 'id', 'id, ir_name');
    
    $discipsubdeps2 = $DB->get_records_sql("SELECT id, subdepartmentid FROM {bsu_discipline_subdepartment}
                                           WHERE yearid = $yid and disciplineid=$did"); 
    if (count($discipsubdeps2)>2)    {
        notify ('Количество кафедр больше двух. Распределение невозможно.');
        return;
    }
    
    $subdep1 = reset($discipsubdeps2);
    $subdep2 = end($discipsubdeps2);
    $sid1 = $subdep1->subdepartmentid;
    $sid2 = $subdep2->subdepartmentid;
    
    $sql = "SELECT id, concat(yearid, '_', disciplineid, '_', groupid, '_', subgroupid, '_', streammaskid, '_', numstream) as idx
           FROM mdl_bsu_discipline_subdepartment_groups
           where yearid = $yid and disciplineid=$did";
    $existrecords = $DB->get_records_sql_menu($sql);

    $newrecords = array();
    // проверяем распределение групп по кафедрам 
    foreach ($agroups as $agroup)   {
        $groupid = $DB->get_field_select('bsu_ref_groups', 'id', "name = '$agroup'");
        $newrecords[] = $yid.'_'.$did. '_' . $groupid . '_0_0_0';
    }     

    // проверяем распределение подгрупп по кафедрам
    $sql = "SELECT id, groupid, name, shortname, countstud, notusing 
            FROM {bsu_discipline_subgroup}
    	    WHERE yearid=$yid and disciplineid=$did AND notusing=0";
    if ($subgroups = $DB->get_records_sql($sql)) {   
        foreach ($subgroups as $subgroup)   {
            $newrecords[] = $yid .'_'.$did. '_'. $subgroup->groupid . '_'. $subgroup->id . '_0_0';            
        }
    }         

    // проверяем распределение потоков по кафедрам
    $alledworkkinds = array(1,2,3,4,5,10,39);
    $terms = get_terms_group($yid, $agroups);
    $atermsids = array();
    foreach ($terms as $term)   {
        foreach ($term as $t)   {
            $atermsids[] = $t;
        }    
    } 
    $atermsids = array_unique($atermsids);
    // print_object($terms);    echo '<hr>';
    $termsids = implode (',', $atermsids);    
        
    foreach ($alledworkkinds as $edkk)  {
        $astreams = get_list_streams_for_edworkkind($yid, $planid, $termsids, $disciplinenameid, $edkk, $edworkkind);
        // print_object($astreams);
        foreach ($astreams as $indexstream => $itemname)   {
            list($groupid, $subgroupid, $streammaskid, $numstream) = explode ('_', $indexstream);         
            $newrecords[] =  $yid . '_'.$did. '_'. $indexstream;                         
        }
    }
    // print_object($newrecords);
    // print_object($existrecords);
    
    $diffs = array_diff($newrecords, $existrecords);
    
    // print_object($diffs);

    if (!empty($diffs)) {
        $dissubdeps2 = array();
        foreach($diffs as $index)	{
            $ids = explode('_', $index);
            $dissubdeps2[$index] = new stdClass();
            $dissubdeps2[$index]->subdepartmentid = $sid1;
            $dissubdeps2[$index]->yearid = $ids[0];
            $dissubdeps2[$index]->disciplineid = $ids[1];    
            $dissubdeps2[$index]->groupid = $ids[2];
            $dissubdeps2[$index]->subgroupid = $ids[3];
            $dissubdeps2[$index]->streammaskid = $ids[4];
            $dissubdeps2[$index]->numstream = $ids[5];
        }            
        // print_object($dissubdeps);
       
        foreach ($dissubdeps2 as $dissubdep)  {
            if (!$DB->insert_record('bsu_discipline_subdepartment_groups', $dissubdep))  {
                notify ('ERROR insert in bsu_discipline_subdepartment_groups');
            }
        }        
    }

    $diffs = array_diff($existrecords, $newrecords);
    // print '<hr>';   print_object($diffs);
    if (!empty($diffs)) {
        foreach($diffs as $index)	{
            $ids = explode('_', $index);
            
            $conditions = array('yearid' => $ids[0], 'disciplineid' => $ids[1], 'groupid' => $ids[2], 
                                'subgroupid' => $ids[3], 'streammaskid' => $ids[4], 'numstream' => $ids[5]);
            if ($subdepgroup = $DB->get_record('bsu_discipline_subdepartment_groups', $conditions))  {
                $DB->delete_records_select('bsu_discipline_subdepartment_groups', "id = $subdepgroup->id");
                // notify (" delete_records_select bsu_discipline_subdepartment_groups id = $subdepgroup->id");
            }
        }
    }            
}             
?>