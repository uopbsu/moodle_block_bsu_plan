<?PHP // $Id: subgroups.php,v 1.2 2012/04/06 09:51:45 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");
    require_once("../../bsu_charge/lib_charge.php");
    require_once("../../bsu_charge/lib_charge_spec.php");

    $action = optional_param('action', '', PARAM_ALPHA);    // new, add, edit, update
    $fid = required_param('fid', PARAM_INT);
    $pid = optional_param('pid', 0, PARAM_INT);            // plan id    
    $term = optional_param('term', 1, PARAM_INT);
	$did = optional_param('did', 0, PARAM_INT);			// Discipline id (courseid)
    $gid = optional_param('gid', 0, PARAM_INT);
    $subgr = optional_param('subgr', 0, PARAM_INT);			// subgroup id
    $tab = optional_param('tab', 1, PARAM_INT);
    $plantab = optional_param('plantab', 'plan', PARAM_TEXT);
    $yid = optional_param('yid', 0, PARAM_INT);			// ed yearid
    $eid = optional_param('eid', '0', PARAM_TEXT);			// edworkkindid
    
    if ($yid == 0)   {
        $yid = get_current_edyearid();
    }
    
    
    require_login();
    
    $time0 = time();
    $select = "timestart<$time0 and timeend>$time0 and LOCATE('$yid', editplan)>0";
    $ACCESS_USER = $DB->get_records_select_menu('bsu_operator_dean', $select, null, '', 'id, userid');


    if ($action === "new") $strscript = get_string('createsubgroups', 'block_bsu_plan');
	else                    $strscript = get_string('changesubgroups', 'block_bsu_plan');
    
    // $redirlink = "disciplines.php?fid=$fid&pid=$pid&gid=$gid&term=$term";
    $redirlink = "subgroups.php?yid=$yid&fid=$fid&pid=$pid&gid=$gid&term=$term&did=$did&subgr=$subgr&plantab=$plantab&";
    
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = get_string('disciplines', 'block_bsu_plan');
    
    $course = get_site();
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strscript);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3, new moodle_url("disciplines.php", array('yid' => $yid, 'fid' => $fid, 'pid' => $pid, 
                                                                           'gid' => $gid, 'term' => $term, 'tab' => $plantab)));
    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

    switch($action)   {
        case  'add':
                if (!$cntsubgroup = $DB->count_records_select('bsu_discipline_subgroup', "yearid=$yid AND disciplineid = $did AND groupid=$gid")) {
                    $cntsubgroup = 0;
                }
                
                $half = 0;
                if ($cnt = $DB->count_records_select('bsu_group_members', "groupid=$gid and deleted=0"))  {
                    $half = $cnt / 2;
                }
        
                $newsubgroupids = array();
                for ($i=$cntsubgroup+1; $i<=$cntsubgroup+2; $i++)     {
        
                    $newrec = new stdClass(); 
                    $newrec->yearid = $yid;
                    $newrec->departmentcode = $fid;
                    $newrec->groupid = $gid;
                    $newrec->disciplineid = $did;
                    $newrec->name = $i . ' подгруппа';
                    $newrec->shortname = $i . 'пгр.';
                    $newrec->countstud = $half;
        
                    if ($newsubgroupids[] = $DB->insert_record('bsu_discipline_subgroup', $newrec))	{
                        echo $OUTPUT->notification('Добавлена подгруппа.', 'notifysuccess');
                    } else {
                        print_object($newrec);
                    	print_error('Ошибка при добавлении подгруппы');
                    }
               }
               
               if (!empty($newsubgroupids))    {
                    update_charge_add_new_subgroups($yid, $fid, $gid, $did, $newsubgroupids, $pid);
                    // exit();
               }	
        break;
        case 'del':
                $msg = check_use_subgroup($yid, $subgr);
                if ($msg != '')   {
                    echo $OUTPUT->heading('<font color=red> ВНИМАНИЕ!!! '.$msg . '</font>', 2);
                } else {
                    $DB->delete_records_select('bsu_discipline_subgroup', "id = $subgr");
                    $DB->delete_records_select('bsu_discipline_subgroup_members', "subgroupid = $subgr");
                    $DB->delete_records_select('bsu_schedule', "subgroupid = $subgr");
                    $DB->delete_records_select('bsu_schedule_mask', "subgroupid = $subgr");
                    $DB->delete_records_select('bsu_discipline_stream', "subgroupid = $subgr");
                    delete_subgroups_from_charge($yid, $fid, $gid, $did, $subgr, $pid);
                    echo $OUTPUT->heading('Подгруппа удалена. Удален список студентов подгруппы. Удалено расписание подгруппы.', 2);
                }
        break;
        case 'save':
                if ($recs = data_submitted())   {
                    save_subgroups($recs, $yid, $fid, $gid, $did, $pid, $eid);
                    redirect($redirlink, 'Данные успешно сохранены.', 0);
                }
        break;
        case 'savestud':
                if ($recs = data_submitted())   {
                    save_subgroups_students($recs);
                    redirect($redirlink . "&subgr=$subgr&tab=2", 'Данные успешно сохранены.', 0);
                }    
        break;
        case 'copy':
                if ($recs = data_submitted())   {
                    copy_subgroups($recs);
                    redirect($redirlink, 'Данные успешно скопированы.', 0);
                }    
        break;
        case 'notusing':
                $subgroup1 = $DB->get_record_select('bsu_discipline_subgroup', "id = $subgr");
                $DB->set_field_select('bsu_discipline_subgroup', 'notusing', 1 - $subgroup1->notusing, "id = $subgr");
        break;
        case 'copyfromprev':
                $sql = "SELECT * FROM {bsu_discipline_subgroup}
                	    WHERE yearid = ($yid-1) AND disciplineid=$did AND notusing=0";
                if ($subgroups = $DB->get_records_sql($sql)) {
                    foreach ($subgroups as $subgroup)   {
                        if (!$DB->record_exists_select("bsu_discipline_subgroup", "yearid=$yid and groupid=$subgroup->groupid and disciplineid=$did and name='$subgroup->name'")) {
                            
                            $students = $DB->get_records_select('bsu_discipline_subgroup_members', "subgroupid=$subgroup->id and deleted=0");
                            $subgroup->yearid = $yid;    
                            if ($newid = $DB->insert_record('bsu_discipline_subgroup', $subgroup))	{
                                echo $OUTPUT->notification('Добавлена подгруппа '. $subgroup->name, 'notifysuccess');
                                if ($students)  {
                                    foreach ($students as $student) {
                                        $student->subgroupid = $newid; 
                                        $DB->insert_record('bsu_discipline_subgroup_members', $student);
                                    }
                                }     
                            } else {
                                print_object($newrec);
                            	print_error('Ошибка при добавлении подгруппы');
                            }
                        }
                    }    
                }    
        
        break;
    }            


    $scriptname = "subgroups.php";        
    $strlistfaculties =  listbox_department($scriptname, $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

    echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
    echo $strlistfaculties;

    $kp = false;
    $countstud = 0;
    if($fid > 0){
        listbox_plan($scriptname."?yid=$yid&plantab=$plantab&fid=$fid", $fid, $pid);
        if ($pid > 0)   {
            listbox_term($scriptname."?yid=$yid&plantab=$plantab&fid=$fid&pid=$pid", $fid, $pid, $term);
            if ($term > 0) {
                listbox_discipline($scriptname."?yid=$yid&plantab=$plantab&fid=$fid&pid=$pid&term=$term", $fid, $pid, $term, $did);
                if ($did > 0) {
                    // list_box_year($scriptname."?yid=$yid&plantab=$plantab&fid=$fid&pid=$pid&term=$term&did=$did", $yid);
                    echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left"><b>';
                    $edyear = $DB->get_record_select('bsu_ref_edyear', "id = $yid");
                    echo $edyear->edyear;
                    echo '</b></td></tr>';
                    if ($yid > 0) {
                        $countstud = listbox_groups_plan($scriptname."?yid=$yid&plantab=$plantab&fid=$fid&pid=$pid&term=$term&did=$did&yid=$yid", $fid, $pid, $gid);
                        if ($gid > 0) {
                            $kp = true;
                            /*
                            echo '<tr align="left"> <td align="right">Количество студентов: </td><td align="left">';
                            echo $countstud;
                            echo '</td></tr>';
                            */
                        }
                    }    
                }
            }
        }
     }
     echo '</table>';   
     
    if ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
        $prevterm = $term-1;
        
        if ($yid >= 15 && ($term%2 == 1) && $DB->record_exists_select('bsu_discipline_semestr', "disciplineid=$did and numsemestr=$prevterm")) {
            notify ('<strong>ВНИМАНИЕ!!! Можно скопировать подгруппы с прошлого учебного года.</strong>', 'notifysuccess');
            $options = array('yid' => $yid, 'fid' => $fid, 'pid' => $pid, 'gid' => $gid, 'term' => $term, 'did' => $did, "action"=> "copyfromprev", "plantab" => $plantab);
            echo '<center>'.$OUTPUT->single_button(new moodle_url("subgroups.php", $options), 'СКОПИРОВАТЬ ПОДГРУППЫ С ПРОШЛОГО УЧЕБНОГО ГОДА', 'get', $options).'</center>';
        }    
    }    
          

    if ($kp)    {

        $link = "yid=$yid&plantab=$plantab&fid=$fid&pid=$pid&term=$term&did=$did&gid=$gid";
        $toprow[] = new tabobject('1', 'subgroups.php?tab=1&'.$link, get_string('disciplinesubgroups', 'block_bsu_plan'));
    	$toprow[] = new tabobject('2', 'subgroups.php?tab=2&'.$link, get_string('subgroupsstudents', 'block_bsu_plan'));
        // $toprow[] = new tabobject('3', 'subgroups.php?tab=3&'.$link, get_string('copysubgroups', 'block_bsu_plan'));
    	$tabs = array($toprow);
        print_tabs($tabs, $tab, NULL, NULL);

        $edworkkindid = check_kursovik_in_($did, $term);
        if ($tab == 1)  {
            notify ('<strong>ВНИМАНИЕ!!! В полном имени подгруппы сначала надо указать любое числовое значение. Например, 1 подгруппа, 1 английская, 1 СМГ, 1 основная и т.п.
            <br />Это необходимо для корректного отображения имени подгруппы на центральном сайте университета в подсистеме отображения расписания занятий.<br />
            Короткое имя подгруппы может быть любым.</strong>');  

            // print_discipline_header($fid, $gid, $did);
            $table = table_subgroups($yid, $fid, $pid, $term, $gid, $did, $countstud, $plantab, $edworkkindid);
            // echo $OUTPUT->heading(get_string('disciplinesubgroups','block_bsu_plan'), 3);
            if ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                $options = array('yid' => $yid, 'fid' => $fid, 'pid' => $pid, 'gid' => $gid, 'term' => $term, 'did' => $did, "action"=> "add", "plantab" => $plantab);
                echo '<center>'.$OUTPUT->single_button(new moodle_url("subgroups.php", $options), get_string('addsubgroups', 'block_bsu_plan'), 'get', $options).'</center>';
            }    
    
            echo  '<form name="addform" method="post" action="subgroups.php">';
        	echo  '<input type="hidden" name="action" value="save">';
            echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
        	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
        	echo  '<input type="hidden" name="pid" value="' .  $pid . '">';
        	echo  '<input type="hidden" name="term" value="' .  $term . '">';                
        	echo  '<input type="hidden" name="gid" value="' .  $gid . '">';
        	echo  '<input type="hidden" name="did" value="' .  $did . '">';
            echo  '<input type="hidden" name="plantab" value="' .  $plantab . '">';
            echo  '<input type="hidden" name="eid" value="' . $edworkkindid . '">';
            echo'<center>'.html_writer::table($table).'</center>';
        	echo  '<div align="center">';
            if ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
        	   echo  '<input type="submit" name="savepoints" value="'. get_string('savechanges') . '"></div>';
            } else {
                notify (get_string('accessdenied', 'block_bsu_plan'));
            }   
        	echo  '</form>';
            echo  '<p>&nbsp;</p>';
            
        } else if ($tab == 2)   {
        	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
            // listbox_discipline_subgroup('subgroups.php?tab=2&'.$link, $yid, $gid, $did, $subgr);
            $subgr = print_tabs_discipline_subgroup('subgroups.php?tab=2&'.$link, $yid, $gid, $did, $subgr);
          	echo '</table>';
            if ($subgr > 0)   {
                display_enrol_subgroup($yid, $fid, $pid, $term, $gid, $did, $subgr, $plantab, $edworkkindid);
            }
        }  else if ($tab == 3)   {
            $table = table_copy_subgroups($yid, $fid, $pid, $term, $gid, $did);
            if (isset($table->data))   {                    
                echo  '<form name="addform" method="post" action="subgroups.php">';
            	echo  '<input type="hidden" name="action" value="copy">';
                echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
            	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
            	echo  '<input type="hidden" name="pid" value="' .  $pid . '">';
            	echo  '<input type="hidden" name="term" value="' .  $term . '">';                
            	echo  '<input type="hidden" name="gid" value="' .  $gid . '">';
            	echo  '<input type="hidden" name="did" value="' .  $did . '">';
                echo  '<input type="hidden" name="plantab" value="' .  $plantab . '">';                
                echo'<center>'.html_writer::table($table).'</center>';
            	echo  '<div align="center">';
            	echo  '<input type="submit" name="savepoints" value="Выполнить копирование"></div>';
            	echo  '</form>';
                echo  '<p>&nbsp;</p>';
            }    
        }    
    }
    
    echo $OUTPUT->footer();


function print_discipline_header($fid, $gid, $did)
{
    global $DB;
    
    echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
    
    if ($faculty = $DB->get_record_select('bsu_ref_department', "DepartmentCode = $fid"))   {
        echo '<tr> <td align=right>'.get_string('faculty', 'block_bsu_plan').': </td><td>';
        echo '<b>'.$faculty->name.'</b>';
        echo '</td></tr>';    
    }
            
    $sql = "SELECT p.id, p.name as pname, s.Name as sname, s.KodSpecyal as scode 
                    FROM {bsu_plan} p
                    INNER JOIN {bsu_plan_groups} g ON p.id=g.planid 
                    inner join {bsu_tsspecyal} s ON s.idSpecyal=p.specialityid
                    where p.departmentcode=$fid and g.groupid=$gid";        
    if($plan = $DB->get_record_sql($sql))   {
        echo '<tr> <td align=right>'.get_string('curriculum', 'block_bsu_plan').': </td><td>';
        echo '<b>'.$plan->pname.'</b>';
        echo '</td></tr>';    
        echo '<tr> <td align=right>'.get_string('speciality', 'block_bsu_plan').': </td><td>';
        echo '<b>'.$plan->scode . '. ' . $plan->sname.'</b>';
        echo '</td></tr>';
    }    

    $sql = "SELECT d.id as did, n.Name as nname, d.semestrkursovik, d.semestrkp 
            FROM {bsu_discipline} d 
            INNER JOIN {bsu_ref_disciplinename} n ON n.Id=d.disciplinenameid
            WHERE d.id=$did";              
    if ($discipline = $DB->get_record_sql($sql))   {
        echo '<tr> <td align=right>'.get_string('discipline', 'block_bsu_plan').': </td><td>';
        echo '<b>'.$discipline->nname.'</b>';
        echo '</td></tr>'; 
    }
              
    echo '</table>';
}


function table_subgroups($yid, $fid, $pid, $term, $gid, $did, $countstudgroup, $plantab, $edworkkindid='0')
{
    global $CFG, $DB, $OUTPUT, $USER, $ACCESS_USER;
    
    $strsubgroup = '';
    $strshort = '';
    $edworkkindids=array();
    
    $table = new html_table();
    $table->head  = array (	get_string('fullname', 'block_bsu_plan'), get_string('shortnamesubgroup', 'block_bsu_plan'),
                            'Кол-во студентов',  get_string('action'));
   	$table->align = array ("center", "center", "center", "center");

    if ($edworkkindid != '0')    {
        $table->head[]  = 'Учебная работа';
        $table->align[] =  'center';
        $edworkkindids=explode(",",$edworkkindid);
        $nameedworkkinds=array();
        foreach($edworkkindids as $edworkid) {
            $nameedworkkinds[$edworkid] = $DB->get_field_select('bsu_ref_edworkkind', 'ir_name', "id=$edworkid");
        }   
    }
    // $table->class = 'moutable';
    // $table->width = '40%';
	// $table->size = array ('10%', '10%');

    $sql = "SELECT id, name, shortname, countstud, notusing, edworkkindid FROM {bsu_discipline_subgroup}
    	    WHERE yearid = $yid AND disciplineid=$did AND groupid=$gid";
    if ($subgroup = $DB->get_records_sql($sql)) {
        $title = get_string('delsubgroup', 'block_bsu_plan');
        foreach ($subgroup as $sub){
            if ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                $link = "yid=$yid&fid=$fid&pid=$pid&term=$term&did=$did&gid=$gid&subgr=$sub->id&plantab=$plantab";
                $title = 'Удалить подгруппу';
                $strlinkupdate = "<a title=\"$title\" href=\"subgroups.php?action=del&{$link}\">&nbsp;";
                $strlinkupdate .= '<img src="'.$OUTPUT->pix_url('i/cross_red_small').'" alt="'. $title . '" />&nbsp;</a>';
                if ($yid == 14) { 
                    if ($sub->notusing)    {
                            $icon = 'completion-auto-fail';
                            $title = 'Не использовать подгруппу в нагрузке следующего уч.года';
                    } else {
                            $icon = 'completion-auto-pass';// 'completion-manual-n';
                            $title = 'Использовать подгруппу в нагрузке следующего уч.года';
                    }
                    $strlinkupdate .= "<a href='subgroups.php?action=notusing&{$link}'><img class='icon' title='$title' src='".$OUTPUT->pix_url("i/$icon")."'></a>";
                }
            } else {
                $strlinkupdate = '';
            }                
            
            if ($countstudgroup == 0)   { //  || $yid == 15
                $strcountstud = "<input type=text name=num_z_{$sub->id} size=5 value=\"$sub->countstud\">";
            } else {
                $cnt = $DB->count_records_select('bsu_discipline_subgroup_members', "subgroupid=$sub->id and deleted=0");
                if ($cnt==0) {
                   $cnt="<input type=text name=num_z_{$sub->id} size=5 value=\"$sub->countstud\">";
                }
                $strcountstud = $cnt;
            }
            $tabledata = array ("<input type=text name=num_f_{$sub->id} size=30 value=\"$sub->name\">",
                                    "<input type=text name=num_s_{$sub->id} size=10 value=\"$sub->shortname\">",
                                    $strcountstud,
                                    $strlinkupdate);
            if (count($edworkkindids)>0)    {
                $listedworkkind = array('для всех видов');
                foreach($nameedworkkinds as $edworkid => $nameedworkkind) {
                   $listedworkkind[$edworkid] = 'только для ' . $nameedworkkind;
                }
                $tabledata[] = html_writer::select($listedworkkind, "num_w_{$sub->id}", $sub->edworkkindid, array()); // выбор преподавателя 
            }    
            
            $table->data[] = $tabledata; 
        }
    } else {    
        $tabledata = array("<input type=text name=num_a_0 size=30 value=''>", 
                               "<input type=text name=num_b_0 size=10 value=''>",
                               "<input type=text name=num_k_0 size=5 value='0'>",'');
                               // '' , '');
        if (count($edworkkindids)>0)    {
            $listedworkkind = array('для всех видов');
            foreach($nameedworkkinds as $edworkid => $nameedworkkind) {
                   $listedworkkind[$edworkid] = 'только для ' . $nameedworkkind;
                }
            $tabledata[] = html_writer::select($listedworkkind, "num_q_0", 0, array()); // выбор преподавателя 
        }    
        $table->data[] = $tabledata;
         
        $tabledata = array("<input type=text name=num_c_0 size=30 value=''>",
                                "<input type=text name=num_d_0 size=10 value=''>",
                                "<input type=text name=num_l_0 size=5 value='0'>", '');
                                                                // '' , '');
        if (count($edworkkindids)>0)    {
            $listedworkkind = array('для всех видов');
            foreach($nameedworkkinds as $edworkid => $nameedworkkind) {
                   $listedworkkind[$edworkid] = 'только для ' . $nameedworkkind;
            }
            $tabledata[] = html_writer::select($listedworkkind, "num_r_0", 0, array()); // выбор преподавателя 
        }    
        $table->data[] = $tabledata; 

        $tabledata = array("<input type=text name=num_e_0 size=30 value=''>",
                                "<input type=text name=num_g_0 size=10 value=''>",
                                "<input type=text name=num_m_0 size=5 value='0'>",'');
                                // '' , '');
        if (count($edworkkindids)>0)    {
            $listedworkkind = array('для всех видов');
            foreach($nameedworkkinds as $edworkid => $nameedworkkind) {
                   $listedworkkind[$edworkid] = 'только для ' . $nameedworkkind;
            }
            $tabledata[] = html_writer::select($listedworkkind, "num_t_0", 0, array()); // выбор преподавателя 
        }    
        $table->data[] = $tabledata;                                 
                                
    }    
    $tabledata = array("<input type=text name=num_f_0 size=30 value=''>",
                           "<input type=text name=num_s_0 size=10 value=''>",
                           "<input type=text name=num_z_0 size=5 value='0'>",'');
                           // '' , '');
    if (count($edworkkindids)>0)    {
        $listedworkkind = array('для всех видов');
        foreach($nameedworkkinds as $edworkid => $nameedworkkind) {
            $listedworkkind[$edworkid] = 'только для ' . $nameedworkkind;
        }
        $tabledata[] = html_writer::select($listedworkkind, "num_w_0", 0, array()); // выбор преподавателя 
    }  
      
    $table->data[] = $tabledata; 

  
    return $table;    
}



function save_subgroups($recs, $yid, $fid, $gid, $did, $pid, $edworkkindid='0')
{
	global $CFG, $DB;

	$arrsubgroupids = array();
    $newsubgroupids = array();
    $flag=true;
	foreach($recs as $fieldname => $profilename)	{

		if ($profilename != '')	{
            $mask = substr($fieldname, 0, 4);
            if ($mask == 'num_')	{
            	$ids = explode('_', $fieldname);
            	$f_s = $ids[1];
            	$subgroupid = $ids[2];
            	
            	if ($subgroupid != 0)	{
            		$arrsubgroupids[] = $subgroupid;
	            	if ($DB->record_exists_select('bsu_discipline_subgroup', "id = $subgroupid"))	{
	            		if ($f_s == 'f')	{
		           			$DB->set_field_select('bsu_discipline_subgroup', 'name', $profilename, "id = $subgroupid");
		           		} else 	if ($f_s == 's')	{
                            $DB->set_field_select('bsu_discipline_subgroup', 'shortname', $profilename, "id = $subgroupid");
		           		} else 	if ($f_s == 'z')	{
                            $DB->set_field_select('bsu_discipline_subgroup', 'countstud', $profilename, "id = $subgroupid");
                            if ($edworkkindid == '0') {
                                recalculate_zachet_and_examen_for_subgroup($yid, $fid, $gid, $did, $subgroupid, $profilename);    
                            } else if ($edworkkindid != '2,3') {
                                recalculate_kursovik_for_subgroup($yid, $fid, $gid, $did, $subgroupid, $cntstud, $edworkkindid);
                            }    
		           		} else 	if ($f_s == 'w')	{
                            $DB->set_field_select('bsu_discipline_subgroup', 'edworkkindid', $profilename, "id = $subgroupid");
		           		}
	            	}
	            } else {
	            	if ($flag)	{
	            		$name_f = array('num_a_0', 'num_c_0', 'num_e_0', 'num_f_0');
	            		$name_s = array('num_b_0', 'num_d_0', 'num_g_0', 'num_s_0');
                        $name_z = array('num_k_0', 'num_l_0', 'num_m_0', 'num_z_0');
                        $name_w = array('num_q_0', 'num_r_0', 'num_t_0', 'num_w_0');

	            		for ($i = 0; $i < 4; $i++)	{
		            		if (isset($recs->{$name_f[$i]}) && !empty($recs->{$name_f[$i]}) &&
			            		isset($recs->{$name_s[$i]}) && !empty($recs->{$name_s[$i]}))	{

                                $newrec = new stdClass();
                                $newrec->yearid = $yid;
                                $newrec->facultyid = $fid;
			            		$newrec->groupid = $gid;
			            		$newrec->disciplineid = $did;
			            		$newrec->name = $recs->{$name_f[$i]};
		            		    $newrec->shortname = $recs->{$name_s[$i]};
                                $newrec->countstud = $recs->{$name_z[$i]};
                                if ($edworkkindid != '0')  {
                                    $newrec->edworkkindid = $recs->{$name_w[$i]};
                                }  else {
                                    $newrec->edworkkindid = 0;
                                }    

						       if ($newid = $DB->insert_record('bsu_discipline_subgroup', $newrec))	{
								    $arrsubgroupids[] = $newid;
                                    $newsubgroupids[] = $newid;
							   } else {
					               	print_error(get_string('errorinaddingcomponent', 'block_bsu_plan'), $redirlink);
							   }
							}
					   }
					   $flag=false;
					}

            	}
            }

        }
	}

	// print_object($arrsubgroupids); echo '<hr>'; 
	if ($dissubgroups =  $DB->get_records_select('bsu_discipline_subgroup', "yearid=$yid AND disciplineid = $did and groupid = $gid"))	{
		foreach ($dissubgroups  as $dissubgroup)	{
			if (!in_array($dissubgroup->id, $arrsubgroupids))	{
				// print_object($dissubgroup); echo '<hr>';
			    $DB->delete_records_select('bsu_discipline_subgroup', "id = $dissubgroup->id");  
			}
		}
	}
    
    if (!empty($newsubgroupids) && $edworkkindid == '0')    {
        update_charge_add_new_subgroups($yid, $fid, $gid, $did, $newsubgroupids, $pid);
    }	
 }


function check_use_subgroup($yid, $subgr)
{
    global $DB;
    
    $msg = '';
    if ($DB->record_exists_select('bsu_schedule', "subgroupid=$subgr && deleted=0")) {
        $msg = 'Для подгруппы уже создано расписание. Подгруппа не может быть удалена.<br /><br />';
        // return true;
    }

    if ($DB->record_exists_select('bsu_schedule_mask', "subgroupid=$subgr && deleted=0")) {
        $msg = 'Для подгруппы уже создано расписание. Подгруппа не может быть удалена.<br /><br />';
        // return true;
    }
    
    // $DB->get_records_select('bsu_discipline_stream', "subgroupid = "))   {
    $sql = "SELECT distinct planid, term FROM dean.mdl_bsu_discipline_stream ds
            inner join  mdl_bsu_discipline_stream_mask sm on sm.id=ds.streammaskid
            where subgroupid = $subgr";
        
    if ($astreams = $DB->get_records_sql($sql)) {
        $s = '';
        foreach ($astreams as $astream) {
            $s .= $astream->planid . ", семестр $astream->term;<br />"; 
        }
        $msg .= " Подгруппа есть в потоке в планах:<br /> $s. <br />Подгруппа не может быть удалена.<br /><br />";    
    }
    
    return $msg;
}


function listbox_discipline_subgroup($scriptname, $yid, $gid, $did, $subgr)
{
  global $CFG, $DB, $OUTPUT;
 
  $disciplinemenu = array();
  $disciplinemenu[0] = get_string('sbselect', 'block_bsu_plan') . '...';;

  if ($subgroups = $DB->get_records_select ('bsu_discipline_subgroup', "yearid = $yid AND groupid = $gid AND disciplineid=$did", null, 'name'))   {
    foreach ($subgroups as $sg) {
	   $disciplinemenu[$sg->id] = $sg->name;
    }
  }

  echo '<tr><td>'.get_string('subgroup','block_bsu_plan').':</td><td>';
  echo $OUTPUT->single_select($scriptname, 'subgr', $disciplinemenu, $subgr, null, 'switchsubgr');
  echo '</td></tr>';
  return 1;
}


function print_tabs_discipline_subgroup($scriptname, $yid, $gid, $did, $subgr)
{
    global $CFG, $DB, $OUTPUT;
 
    $toprow = array();

    if ($subgroups = $DB->get_records_select ('bsu_discipline_subgroup', "yearid = $yid AND groupid = $gid AND disciplineid=$did", null, 'name'))   {
        foreach ($subgroups as $sg) {
           // $disciplinemenu[$sg->id] = $sg->name;
           $cnt = $DB->count_records_select('bsu_discipline_subgroup_members', "subgroupid=$sg->id and deleted=0");
           if ($cnt==0) {
               $cnt=$sg->countstud;
           }
           $toprow[] = new tabobject($sg->id, $scriptname."&subgr=$sg->id", $sg->name . " ($cnt)");
        }    
        if ($subgr == 0)    {
            $sg = reset($subgroups);
            $subgr = $sg->id;
        }
        $tabs = array($toprow);
        print_tabs($tabs, $subgr, NULL, NULL);
    }
    return $subgr;
}


function display_enrol_subgroup($yid, $fid, $pid, $term, $gid, $did, $subgr, $plantab, $edworkkindid = '0')
{
    global $CFG, $DB, $OUTPUT;

    // студенты, уже зачисленные в подгруппы
    $strsql = "SELECT username FROM mdl_bsu_discipline_subgroup s
               inner join mdl_bsu_discipline_subgroup_members m on s.id=m.subgroupid
               where s.yearid=$yid AND s.groupid=$gid AND s.disciplineid=$did and m.deleted=0";
    
    $astudent = array();           
    if ($astudents = $DB->get_records_sql($strsql))	{
        foreach ($astudents as $astud)  { 
            $astudent[] = $astud->username;
        }
    }   
    // print_object($astudent);     
        
    //  студенты группы
    $strsql = "SELECT distinct g.username, s.name FROM mdl_bsu_group_members  g
                INNER JOIN mdl_bsu_students s  ON s.codephysperson = g.username
                where g.groupid=$gid and g.deleted=0
                ORDER BY s.name";
    // echo $strsql;                                         
    $gstudentscount = 0;
    $groupmenu = array();
    if ($gstudents = $DB->get_records_sql($strsql))	{
        foreach ($gstudents as $gstudent)   {
       	    if (!in_array($gstudent->username, $astudent))	{
                $groupmenu[$gstudent->username] = $gstudent->name;
            }    
        }
    	$gstudentscount = count($groupmenu);             
    }
    // print_object($groupmenu);

    $strsql = "SELECT distinct g.username, s.name FROM mdl_bsu_discipline_subgroup_members g
                INNER JOIN mdl_bsu_students s  ON s.codephysperson = g.username
                where g.subgroupid=$subgr and g.deleted=0
                ORDER BY s.name";                            
    
    $idsstudents  = array();
    $dstudentmenu = array();
    $dstudentscount = 0;
 	if ($dstudents = $DB->get_records_sql($strsql))	{
 	    $dstudentscount = count($dstudents);
        // $gstudentscount -= $dstudentscount; 
 		foreach ($dstudents as $dstud)	{
            $dstudentmenu[$dstud->username] = $dstud->name;
            $idsstudents[] = $dstud->username;
 		}
 	} 

    ?>
    <form name="formpoint" id="formpoint" method="post" action="subgroups.php">
    <input type="hidden" name="yid" value="<?php echo $yid ?>" />
    <input type="hidden" name="fid" value="<?php echo $fid ?>" />
    <input type="hidden" name="pid" value="<?php echo $pid ?>" />    
    <input type="hidden" name="gid" value="<?php echo $gid ?>" />
    <input type="hidden" name="term" value="<?php echo $term ?>" />
    <input type="hidden" name="did" value="<?php echo $did ?>" />
    <input type="hidden" name="subgr" value="<?php echo $subgr ?>" />        
    <input type="hidden" name="plantab" value="<?php echo $plantab ?>" />
    <input type="hidden" name="eid" value="<?php echo $edworkkindid ?>" />
    <input type="hidden" name="tab" value="2" />
    
    <input type="hidden" name="action" value="savestud" />
    <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />

    <table align="center" border="0" cellpadding="5" cellspacing="0">
        <tr>
          <td valign="top"> <?php echo get_string('subgroupstudents', 'block_bsu_plan')  . ' (' . $dstudentscount . ')' ; ?>  </td>
          <td></td>
          <td valign="top"> <?php  echo get_string('groupstudents', 'block_bsu_plan') . ' (' . $gstudentscount . ')'; ?> </td>
        </tr>
        <tr>
          <td valign="top">
              <select name="removeselect[]" size="20" id="removeselect"  multiple
                      onFocus="document.formpoint.add.disabled=true;
                               document.formpoint.remove.disabled=false;
                               document.formpoint.addselect.selectedIndex=-1;" />
              <?php
              if (!empty($dstudentmenu))	{
                  foreach ($dstudentmenu as $key => $pm) {
                      echo "<option value=\"$key\">" . $pm . "</option>\n";
                  }
              }
              ?>
             </select>
          </td>
          <td valign="top">
            <br />
            <input name="add" type="submit" id="add" value="&larr; Добавить" />
            <br />
            <br />
            <input name="remove" type="submit" id="remove" value="Удалить &rarr;" />
            <br />
          </td>
          <td valign="top">
              <select name="addselect[]" size="20" id="addselect"  multiple
                      onFocus="document.formpoint.add.disabled=false;
                               document.formpoint.remove.disabled=true;
                               document.formpoint.removeselect.selectedIndex=-1;">
              <?php
              if (!empty($groupmenu))	{
                  foreach ($groupmenu as $key => $sm) {
                  	if (!in_array($key, $idsstudents))	{
                      echo "<option value=\"$key\">" . $sm . "</option>\n";
                    }
                  }
              }
              ?>
             </select>
           </td>
        </tr>
      </table>
    </form>
    
    <?php
}


function save_subgroups_students($frm)
{
    global $CFG, $DB, $OUTPUT;
    
    // print_object ($frm);
	if (!empty($frm->add) and !empty($frm->addselect) and confirm_sesskey()) {
		foreach ($frm->addselect as $addpupil) {
	        if ($existstud = $DB->get_record_select('bsu_discipline_subgroup_members', "username = $addpupil AND subgroupid = $frm->subgr"))	{
	              // print_object($existstud); 
	              if ($existstud->deleted == 1)    {
	                   $DB->set_field_select('bsu_discipline_subgroup_members', 'deleted', 0, "id = $existstud->id");
	              } else { 
                    // notify('Error in adding student in subgroup!');
                  }  
		    } else {
		        $rec = new stdClass();  
				$rec->username 	= $addpupil;
		        $rec->subgroupid = $frm->subgr;
                $rec->deleted = 0; 
		    	if (!$DB->insert_record('bsu_discipline_subgroup_members', $rec)){
		    		print_error('Error in adding student in subgroup!');
		    	}
		    }
        }
	} else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {
		foreach ($frm->removeselect as $removepupil) {
		    $DB->set_field('bsu_discipline_subgroup_members', 'deleted', 1,  array ('username' => $removepupil, 'subgroupid' => $frm->subgr));
            /*
            $sql = "UPDATE mdl_bsu_marksheet_students set timemodified=$time, modifierid=$USER->id, deleted=1
                        where  CodePhysPerson=$removepupil and groupid=$gid and deleted=0";
                $DB->Execute($sql);
            */    
			// $DB->delete_records('bsu_discipline_subgroup_members', array ('username' => $removepupil, 'subgroupid' => $frm->subgr));
		}
	} 
    
    if (!$cntstud = $DB->count_records_select('bsu_discipline_subgroup_members', "subgroupid = $frm->subgr and deleted=0")) {
        $cntstud = 0;
    } 

    $DB->set_field_select('bsu_discipline_subgroup', 'countstud', $cntstud, "id=$frm->subgr");
    if ($frm->eid == '0') {
        recalculate_zachet_and_examen_for_subgroup($frm->yid, $frm->fid, $frm->gid, $frm->did, $frm->subgr, $cntstud);    
    } else  if ($frm->eid != '2,3') {
        recalculate_kursovik_for_subgroup($frm->yid, $frm->fid, $frm->gid, $frm->did, $frm->subgr, $cntstud, $frm->eid);
    }    
    
}


function table_copy_subgroups($yid, $fid, $pid, $term, $gid, $did)
{
    global $CFG, $DB, $OUTPUT;
    
    $strsubgroup = '';
    $strshort = '';
    
    $table = new html_table();
    $table->head  = array (	'Копировать подгруппы', 'на дисциплины');
   	$table->align = array ("left", "left");

    $strsubgr = '';
    if ($subgroups = $DB->get_records_select ('bsu_discipline_subgroup', "yearid = $yid AND groupid = $gid AND disciplineid=$did", null, 'name'))   {
        foreach ($subgroups as $sg) {
    	   $strsubgr .= $sg->name . ':<br>';
           $strsql = "SELECT distinct g.username, s.name FROM mdl_bsu_discipline_subgroup_members g
            INNER JOIN mdl_bsu_students s  ON s.codephysperson = g.username
            where g.subgroupid=$sg->id and g.deleted=0
            ORDER BY s.name";                            
           if ($dstudents = $DB->get_records_sql($strsql))	{
         		foreach ($dstudents as $dstud)	{
         		     $strsubgr .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                     $strsubgr .=  $dstud->name . '<br>';
                }
           }  else {
              notify('Студенты не распределены по подгруппам. Копирование невозможно');
              return $table;
           }         
           $strsubgr .= '<p>&nbsp;</p>';
        }
    } else {
         notify('Подгруппы не созданы. Копирование невозможно');
         return $table;
    }
    
   $strdis = ''; 
   $sql = "SELECT d.id as did, n.Name as nname
            FROM {bsu_discipline_semestr} s
            INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
            INNER JOIN {bsu_plan} p ON p.id=d.planid
            INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
            WHERE p.id=$pid and s.numsemestr=$term and d.id<>$did 
            ORDER BY n.Name";
    if ($disciplines = $DB->get_records_sql($sql))  {
        foreach ($disciplines as $discipline)   {
            $strdis .= '<input type="checkbox" name="d_' .$discipline->did . '" id="d_' .$discipline->did . '" value="1"/>';
            $strdis .= ' <label for="d_' .$discipline->did . '">'. $discipline->nname .'</label><br>';
        }
    } else {
         notify('Список дисциплин не найден. Копирование невозможно');
         return $table;
    }
    
    $table->data[] = array ($strsubgr, $strdis);
    
    return $table;    
}


function copy_subgroups($frm)
{
    global $CFG, $DB, $OUTPUT;
  
    $disciplineids = array();
	foreach($frm as $fieldname => $value){
		$mask = substr($fieldname, 0, 2);
        if ($mask == 'd_')	{
        	$ids = explode('_', $fieldname);
        	$disciplineids[] = $ids[1];
        }
	}
    
    

    if ($subgroups = $DB->get_records_select ('bsu_discipline_subgroup', "groupid = $frm->gid AND disciplineid=$frm->did", null, 'name'))   {
        foreach ($subgroups as $sg) {
            $members = $DB->get_records_select('bsu_discipline_subgroup_members', "subgroupid = $sg->id and deleted=0");
            foreach ($disciplineids as $newdid) {
                $conditions = array ('disciplineid' => $newdid, 'groupid' => $frm->gid, 'name' => $sg->name); 
    		    if ($DB->record_exists('bsu_discipline_subgroup', $conditions))	{
                      echo $OUTPUT->notification("Подгруппа '$sg->name' уже создана для дисциплины $newdid.");
    		    } else {
                    $sg->disciplineid = $newdid;
    		    	if ($newsgid = $DB->insert_record('bsu_discipline_subgroup', $sg))   {
    		    	    if ($members)    {
    		    	         foreach($members as $member)    {
    		    	             $member->subgroupid = $newsgid;
                                 if (!$DB->insert_record('bsu_discipline_subgroup_members', $member))    {
                                    echo $OUTPUT->notification("Ошибка добавления записи в таблицу подгрупп студентов.");
                                 } 
    		    	         }
    		    	    } 
    		    	} else {
    		    	     echo $OUTPUT->notification("Ошибка добавления записи в таблицу подгрупп.");
    		    	}
    		    }
                
            }
        }
    }        
  
   // print_object ($frm);
}


function update_charge_add_new_subgroups($yid, $fid, $gid, $did, $newsubgroupids, $planid)
{
    global $DB, $OUTPUT;
  
    // проверяем $yid на текущий учебный год или следующий за ним   
    $curryid = get_current_edyearid();  
    if ($yid < $curryid) return false;
    
    // есть ли нагрузка в $yid учебном году 
    if ($DB->record_exists_select('bsu_edwork', "yearid=$yid AND disciplineid=$did"))   {
        delete_discipline_charge($yid, $did, $planid, true);
        $strgroups = get_plan_groups($planid);
        if (!empty($strgroups)) {    
            $agroups = explode ('<br>', $strgroups);
            create_edworks_for_discipline($yid, $planid, $did, $agroups);
            restore_teachingload($yid, $did, $planid);   
        }     
    // иначе создаем нагрузку в $yid учебном году      
    }  else {
        $strgroups = get_plan_groups($planid);
        if (!empty($strgroups)) {    
            $agroups = explode ('<br>', $strgroups);
            create_edworks_for_discipline($yid, $planid, $did, $agroups);
        }     
    }
    
    return true;
}


function insert_record_for_subgroup_in_bsu_edwork($edwork, $newsubgroupids, $edworkkindid)
{
    global $DB, $OUTPUT;
    
    // print_object($newsubgroupids);
    $aedwork = clone $edwork;
    foreach($newsubgroupids as $newsubgroupid)  {
        $aedwork->subgroupid = $newsubgroupid;
        if ($edworkkindid == 5) {
            $cntstud = $DB->get_field_select('bsu_discipline_subgroup', 'countstud', "id = $aedwork->subgroupid");
            $aedwork->hours = 0.25*$cntstud;
        }
        if (!$DB->insert_record('bsu_edwork', $aedwork)) {
            print_object($aedwork);
            $OUTPUT->notification('Ошибка записи в bsu_edwork.');
        } 
        // print_object($edwork);                            
    }
}


/**
 * Функция перерасчитывает нагрузку по экзаменам и зачетам
 * @param $yid - id года (14)
 * @param $fid - код института/факультета (10100)
 * @param $gid - id группы
 * @param $did - id дисциплины
 * @param $subgroupid  - id подгруппы
 * @param $cntstud - количество студентов в подгруппе
 * @return no
 */
function recalculate_zachet_and_examen_for_subgroup($yid, $fid, $gid, $did, $subgroupid, $cntstud)
{
    global $DB;
         
    $edworkkindids = array(4, 5);
    // $onestudhours = array(0.33, 0.25);
    $onestudhours = get_bsu_ref_edworkkind_hours($yid);
    foreach ($edworkkindids as $index => $edworkkindid)   {
        if ($edworks = $DB->get_records_select('bsu_edwork', "yearid=$yid AND disciplineid=$did AND subgroupid=$subgroupid AND edworkkindid=$edworkkindid"))  {
            foreach ($edworks as $edwork)   { 
                $edworkhours = $onestudhours[$edworkkindid]*$cntstud;
                $DB->set_field_select('bsu_edwork',  'hours', $edworkhours, "id=$edwork->id");
                $sql = "SELECT sum(hours) as hours FROM {bsu_edwork} WHERE edworkmaskid=$edwork->edworkmaskid";
                if ($hour = $DB->get_record_sql($sql))  {
                    $DB->set_field_select('bsu_edwork_mask',  'hours', $hour->hours, "id=$edwork->edworkmaskid");
                }
           }
        }
    }            
}


function delete_subgroups_from_charge($yid, $fid, $gid, $did, $subgroupid, $planid)
{
    global $DB, $OUTPUT;


    // проверяем $yid на текущий учебный год или следующий за ним   
    $curryid = get_current_edyearid();  
    if ($yid < $curryid) return false;
    
    // есть ли нагрузка в $yid учебном году 
    if ($DB->record_exists_select('bsu_edwork', "yearid=$yid AND disciplineid=$did"))   {
        delete_discipline_charge($yid, $did, $planid, true);
        $strgroups = get_plan_groups($planid);
        if (!empty($strgroups)) {    
            $agroups = explode ('<br>', $strgroups);
            create_edworks_for_discipline($yid, $planid, $did, $agroups);
            restore_teachingload($yid, $did, $planid);   
        }     
    // иначе создаем нагрузку в $yid учебном году      
    }  else {
        $strgroups = get_plan_groups($planid);
        if (!empty($strgroups)) {    
            $agroups = explode ('<br>', $strgroups);
            create_edworks_for_discipline($yid, $planid, $did, $agroups);
        }     
    }
    
    return true;                          

}


function check_kursovik_in_($did, $term)
{
    global $DB;
    
    $termhex = dechex($term);
    $termhex = strtoupper($termhex);

    $edworkkindid = '0';
      
    $sql = "SELECT d.id as did, d.semestrkursovik, d.semestrkp 
            FROM {bsu_discipline} d 
            WHERE d.id=$did";              
    if ($discipline = $DB->get_record_sql($sql))   {
        if (!empty($discipline->semestrkursovik)) {
            $arr1 = str_split($discipline->semestrkursovik);
            foreach ($arr1 as  $arr)    {
                if ($arr == $termhex)  $edworkkindid = '6';    
            }
        }    
        if (!empty($discipline->semestrkp)) {
            $arr1 = str_split($discipline->semestrkp);
            foreach ($arr1 as  $arr)    {
                if ($arr == $termhex)  $edworkkindid = '36';    
            }
        }
    $sql = "SELECT *
            FROM {bsu_discipline_semestr} d 
            WHERE disciplineid=$did and lab!=0";  
      if ($discs = $DB->get_records_sql($sql))   {
         $sql = "SELECT *
         FROM {bsu_discipline_semestr} d 
         WHERE disciplineid=$did and praktika!=0";  
         if ($discs = $DB->get_records_sql($sql))   { 
          $edworkkindid = '2,3'; 
         }
      }
    }
              
    return $edworkkindid;
}


function recalculate_kursovik_for_subgroup($yid, $fid, $gid, $did, $subgroupid, $cntstud, $edworkkindid)
{
    global $DB;
    
    if ($edworkkindid == '6') {
        $onestudhours = 3;
    } else if ($edworkkindid == '36') {    
        $onestudhours = 4;
    }
    if ($edworks = $DB->get_records_select('bsu_edwork', "yearid=$yid AND disciplineid=$did AND groupid=$gid AND edworkkindid=$edworkkindid"))  {
        foreach ($edworks as $edwork)   {
            if ($edwork->subgroupid == 0)    {
                $DB->set_field_select('bsu_edwork', 'subgroupid', $subgroupid, "id = $edwork->id");
            }
            $edworkhours = $onestudhours*$cntstud;
            $DB->set_field_select('bsu_edwork',  'hours', $edworkhours, "id=$edwork->id");
            $sql = "SELECT sum(hours) as hours FROM {bsu_edwork} WHERE edworkmaskid=$edwork->edworkmaskid";
            if ($hour = $DB->get_record_sql($sql))  {
                $DB->set_field_select('bsu_edwork_mask',  'hours', $hour->hours, "id=$edwork->edworkmaskid");
            }
       }
    }
}

?>