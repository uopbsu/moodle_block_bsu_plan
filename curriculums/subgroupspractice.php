<?PHP // $Id: subgroupspractice.php,v 1.2 2012/04/06 09:51:45 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");

    $action = optional_param('action', '', PARAM_ALPHA);    // new, add, edit, update
    $fid = required_param('fid', PARAM_INT);
    $pid = optional_param('pid', 0, PARAM_INT);            // plan id    
    $term = optional_param('term', 100, PARAM_INT);
	$prid = optional_param('prid', 0, PARAM_INT);			// Practece id
    $gid = optional_param('gid', 0, PARAM_INT);
    $subgr = optional_param('subgr', 0, PARAM_INT);			// subgroup id
    $tab = optional_param('tab', 1, PARAM_INT);
    
    require_login();

    if ($action === "new") $strscript = get_string('createsubgroups', 'block_bsu_plan');
	else                    $strscript = get_string('changesubgroups', 'block_bsu_plan');
    
    // $redirlink = "disciplines.php?fid=$fid&pid=$pid&gid=$gid&term=$term";
    $redirlink = "subgroupspractice.php?fid=$fid&pid=$pid&gid=$gid&term=$term&prid=$prid&subgr=$subgr";
    
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = 'Практики';
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strscript);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3, new moodle_url("disciplines.php", array('fid' => $fid, 'pid' => $pid, 'gid' => $gid, 'term' => $term)));
    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

    if ($action == 'add')   {
        if (!$cntsubgroup = $DB->count_records_select('bsu_plan_practice_subgroup', "practiceid = $prid AND groupid=$gid")) {
            $cntsubgroup = 0;
        }
        
        /*
        $half = 0;
        if ($cnt = $DB->count_records_select('bsu_group_members', "groupid=$gid"))  {
            $half = $cnt / 2;
        }
        */


        for ($i=$cntsubgroup+1; $i<=$cntsubgroup+2; $i++)     {

            $newrec = new stdClass(); 
            $newrec->departmentcode = $fid;
            $newrec->groupid = $gid;
            $newrec->practiceid = $prid;
            $newrec->name = $i . ' подгруппа';
            $newrec->shortname = $i;
            $newrec->countstud = 1;

            if (!$DB->insert_record('bsu_plan_practice_subgroup', $newrec))	{
                print_object($newrec);
            	print_error('Ошибка при добавлении подгруппы');
            } else {
                echo $OUTPUT->notification('Добавлена подгруппа.', 'notifysuccess');
            }
       }
    } else if ($action == 'del')   {
        if (check_use_subgroup($subgr))   {
            echo $OUTPUT->notification('Для подгруппы уже создано расписание. Подгруппа не может быть удалена.');
        } else {
            $DB->delete_records_select('bsu_plan_practice_subgroup', "id = $subgr");
            echo $OUTPUT->notification('Подгруппа удалена. Удален список студентов подгруппы. Удалено расписание подгруппы.', 'notifysuccess');
        }
    }  else if ($action == 'save')  {
        if ($recs = data_submitted())   {
            save_subgroups($recs, $fid, $gid, $prid);
            redirect($redirlink, 'Данные успешно сохранены.', 3);
        }    
    } else if ($action == 'savestud')  {
        if ($recs = data_submitted())   {
            save_subgroups_students($recs);
            redirect($redirlink . "subgr=$subgr&tab=2", 'Данные успешно сохранены.', 3);
        }    
    } else if ($action == 'copy')  {
        if ($recs = data_submitted())   {
            copy_subgroups($recs);
            redirect($redirlink, 'Данные успешно скопированы.', 3);
        }    
    }


    $scriptname = "subgroupspractice.php";        
    $strlistfaculties =  listbox_department($scriptname, $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

    echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
    echo $strlistfaculties;

    $kp = false;
    if($fid > 0){
        listbox_plan($scriptname."?fid=$fid", $fid, $pid);
        if ($pid > 0)   {
                listbox_practice($scriptname."?fid=$fid&pid=$pid&term=$term", $pid, $prid);
                if ($prid > 0) {
                    listbox_groups_plan($scriptname."?fid=$fid&pid=$pid&term=$term&prid=$prid", $fid, $pid, $gid);
                    if ($gid > 0) {
                        $kp = true;
                    }    
                }
        }
     }
     echo '</table>';        

    if ($kp)    {
        $link = "fid=$fid&pid=$pid&term=$term&prid=$prid&gid=$gid";
        $toprow[] = new tabobject('1', 'subgroupspractice.php?tab=1&'.$link, get_string('subgroupspractice', 'block_bsu_plan'));
    	$toprow[] = new tabobject('2', 'subgroupspractice.php?tab=2&'.$link, get_string('subgroupsstudents', 'block_bsu_plan'));
        // $toprow[] = new tabobject('3', 'subgroupspractice.php?tab=3&'.$link, get_string('copysubgroups', 'block_bsu_plan'));
    	$tabs = array($toprow);
        print_tabs($tabs, $tab, NULL, NULL);

        if ($tab == 1)  {
            // print_discipline_header($fid, $gid, $prid);
            $table = table_subgroups($fid, $pid, $term, $gid, $prid);
            // echo $OUTPUT->heading(get_string('disciplinesubgroups','block_bsu_plan'), 3);
            $options = array('fid' => $fid, 'pid' => $pid, 'gid' => $gid, 'term' => $term, 'prid' => $prid, "action"=> "add");
            echo '<center>'.$OUTPUT->single_button(new moodle_url("subgroupspractice.php", $options), get_string('addsubgroups', 'block_bsu_plan'), 'get', $options).'</center>';
    
            echo  '<form name="addform" method="post" action="subgroupspractice.php">';
        	echo  '<input type="hidden" name="action" value="save">';
        	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
        	echo  '<input type="hidden" name="pid" value="' .  $pid . '">';
        	echo  '<input type="hidden" name="term" value="' .  $term . '">';                
        	echo  '<input type="hidden" name="gid" value="' .  $gid . '">';
        	echo  '<input type="hidden" name="prid" value="' .  $prid . '">';
            echo'<center>'.html_writer::table($table).'</center>';
        	echo  '<div align="center">';
        	echo  '<input type="submit" name="savepoints" value="'. get_string('savechanges') . '"></div>';
        	echo  '</form>';
            echo  '<p>&nbsp;</p>';
        } else if ($tab == 2)   {
        	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
            listbox_discipline_subgroup('subgroupspractice.php?tab=2&'.$link, $gid, $prid, $subgr);
          	echo '</table>';
            if ($subgr > 0)   {
                display_enrol_subgroup($fid, $pid, $term, $gid, $prid, $subgr);
            }
        }  else if ($tab == 3)   {
            $table = table_copy_subgroups($fid, $pid, $term, $gid, $prid);
            if (isset($table->data))   {                    
                echo  '<form name="addform" method="post" action="subgroupspractice.php">';
            	echo  '<input type="hidden" name="action" value="copy">';
            	echo  '<input type="hidden" name="fid" value="' .  $fid . '">';
            	echo  '<input type="hidden" name="pid" value="' .  $pid . '">';
            	echo  '<input type="hidden" name="term" value="' .  $term . '">';                
            	echo  '<input type="hidden" name="gid" value="' .  $gid . '">';
            	echo  '<input type="hidden" name="prid" value="' .  $prid . '">';
                echo'<center>'.html_writer::table($table).'</center>';
            	echo  '<div align="center">';
            	echo  '<input type="submit" name="savepoints" value="Выполнить копирование"></div>';
            	echo  '</form>';
                echo  '<p>&nbsp;</p>';
            }    
                
        }    
    }
    
    echo $OUTPUT->footer();


function print_discipline_header($fid, $gid, $prid)
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

    $sql = "SELECT d.id as prid, d.Name as nname FROM {bsu_practice} d 
            WHERE d.id=$prid";              
    if ($discipline = $DB->get_record_sql($sql))   {
        echo '<tr> <td align=right>'.get_string('discipline', 'block_bsu_plan').': </td><td>';
        echo '<b>'.$discipline->nname.'</b>';
        echo '</td></tr>';    
    }
              
    echo '</table>';
}


function table_subgroups($fid, $pid, $term, $gid, $prid)
{
    global $CFG, $DB, $OUTPUT;
    
    $strsubgroup = '';
    $strshort = '';
    
    $table = new html_table();
    $table->head  = array (	get_string('fullname', 'block_bsu_plan'), get_string('shortnamesubgroup', 'block_bsu_plan'),
                            'Кол-во студентов',  get_string('action'));
   	$table->align = array ("center", "center", "center", "center");
    // $table->class = 'moutable';
    // $table->width = '40%';
	// $table->size = array ('10%', '10%');

    $sql = "SELECT id, name, shortname, countstud FROM {bsu_plan_practice_subgroup}
    	    WHERE practiceid=$prid AND groupid=$gid";
    if ($subgroup = $DB->get_records_sql($sql)) {
        $title = get_string('delsubgroup', 'block_bsu_plan');
        foreach ($subgroup as $sub){
            $link = "<a title=\"$title\" href=\"subgroupspractice.php?action=del&fid=$fid&pid=$pid&term=$term&prid=$prid&gid=$gid&subgr=$sub->id\">";
            $link .= '<img src="'.$OUTPUT->pix_url('i/cross_red_small').'" alt="'. $title . '" /></a>';
            $table->data[] = array ("<input type=text name=num_f_{$sub->id} size=20 value=\"$sub->name\">",
                                    "<input type=text name=num_s_{$sub->id} size=20 value=\"$sub->shortname\">",
                                    "<input type=text name=num_z_{$sub->id} size=5 value=\"$sub->countstud\">",
                                    $link);
        }
    } else {    
        $table->data[] = array("<input type=text name=num_a_0 size=20 value=''>", 
                               "<input type=text name=num_b_0 size=20 value=''>",
                               "<input type=text name=num_k_0 size=5 value='0'>",'');
                                                              // '' , '');
        $table->data[] = array("<input type=text name=num_c_0 size=20 value=''>",
                                "<input type=text name=num_d_0 size=20 value=''>",
                                "<input type=text name=num_l_0 size=5 value='0'>", '');
        $table->data[] = array("<input type=text name=num_e_0 size=20 value=''>",
                                "<input type=text name=num_g_0 size=20 value=''>",
                                "<input type=text name=num_m_0 size=5 value='0'>",'');
    }    
    $table->data[] = array("<input type=text name=num_f_0 size=20 value=''>",
                           "<input type=text name=num_s_0 size=20 value=''>",
                           "<input type=text name=num_z_0 size=5 value='0'>",'');

  
    return $table;    
}



function save_subgroups($recs, $fid, $gid, $prid)
{
	global $CFG, $DB;

	$arrsubgroupids = array();
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
	            	if ($DB->record_exists_select('bsu_plan_practice_subgroup', "id = $subgroupid"))	{
	            		if ($f_s == 'f')	{
		           			$DB->set_field_select('bsu_plan_practice_subgroup', 'name', $profilename, "id = $subgroupid");
		           		} else 	if ($f_s == 's')	{
                            $DB->set_field_select('bsu_plan_practice_subgroup', 'shortname', $profilename, "id = $subgroupid");
		           		} else 	if ($f_s == 'z')	{
                           $DB->set_field_select('bsu_plan_practice_subgroup', 'countstud', $profilename, "id = $subgroupid");
		           		}
	            	}
	            } else {
	            	if ($flag)	{
	            		$name_f = array('num_a_0', 'num_c_0', 'num_e_0', 'num_f_0');
	            		$name_s = array('num_b_0', 'num_d_0', 'num_g_0', 'num_s_0');
                        $name_z = array('num_k_0', 'num_l_0', 'num_m_0', 'num_z_0');

	            		for ($i = 0; $i < 4; $i++)	{
		            		if (isset($recs->{$name_f[$i]}) && !empty($recs->{$name_f[$i]}) &&
			            		isset($recs->{$name_s[$i]}) && !empty($recs->{$name_s[$i]}))	{

                                $newrec = new stdClass();
                                $newrec->facultyid = $fid;
			            		$newrec->groupid = $gid;
			            		$newrec->practiceid = $prid;
			            		$newrec->name = $recs->{$name_f[$i]};
		            		    $newrec->shortname = $recs->{$name_s[$i]};
                                $newrec->countstud = $recs->{$name_z[$i]};

						       if (!$arrsubgroupids[] = $DB->insert_record('bsu_plan_practice_subgroup', $newrec))	{
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
	if ($dissubgroups =  $DB->get_records_select('bsu_plan_practice_subgroup', "practiceid = $prid and groupid = $gid"))	{
		foreach ($dissubgroups  as $dissubgroup)	{
			if (!in_array($dissubgroup->id, $arrsubgroupids))	{
				// print_object($dissubgroup); echo '<hr>';
			    $DB->delete_records_select('bsu_plan_practice_subgroup', "id = $dissubgroup->id");  
			}
		}
	}	
 }


function check_use_subgroup($subgr)
{
    global $DB;
    
    if ($DB->record_exists_select('bsu_schedule', "subgroupid=$subgr")) {
        return true;
    }

    if ($DB->record_exists_select('bsu_schedule_mask', "subgroupid=$subgr")) {
        return true;
    }
    
    return false;
}


function listbox_discipline_subgroup($scriptname, $gid, $prid, $subgr)
{
  global $CFG, $DB, $OUTPUT;
 
  $disciplinemenu = array();
  $disciplinemenu[0] = get_string('sbselect', 'block_bsu_plan') . '...';;

  if ($subgroups = $DB->get_records_select ('bsu_plan_practice_subgroup', "groupid = $gid AND practiceid=$prid", null, 'name'))   {
    foreach ($subgroups as $sg) {
	   $disciplinemenu[$sg->id] = $sg->name;
    }
  }

  echo '<tr><td>'.get_string('subgroup','block_bsu_plan').':</td><td>';
  echo $OUTPUT->single_select($scriptname, 'subgr', $disciplinemenu, $subgr, null, 'switchsubgr');
  echo '</td></tr>';
  return 1;
}


function display_enrol_subgroup($fid, $pid, $term, $gid, $prid, $subgr)
{
    global $CFG, $DB, $OUTPUT;

    // студенты, уже зачисленные в подгруппы
    $strsql = "SELECT username FROM mdl_bsu_plan_practice s
               inner join mdl_bsu_plan_practice_members m on s.id=m.subgroupid
               where s.groupid=$gid AND s.practiceid=$prid";
    
    $astudent = array();           
    if ($astudents = $DB->get_records_sql($strsql))	{
        foreach ($astudents as $astud)  { 
            $astudent[] = $astud->username;
        }
    }   
    // print_object($astudent);     
        
    //  студенты группы
    $strsql = "SELECT distinct g.username, s.name FROM mdl_bsu_group_members  g
                INNER JOIN mdl_bsu_students_2013 s  ON s.codephysperson = g.username
                where g.groupid=$gid
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

    $strsql = "SELECT distinct g.username, s.name FROM mdl_bsu_plan_practice_members g
                INNER JOIN mdl_bsu_students_2013 s  ON s.codephysperson = g.username
                where g.subgroupid=$subgr
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
    <form name="formpoint" id="formpoint" method="post" action="subgroupspractice.php">
    <input type="hidden" name="fid" value="<?php echo $fid ?>" />
    <input type="hidden" name="pid" value="<?php echo $pid ?>" />    
    <input type="hidden" name="gid" value="<?php echo $gid ?>" />
    <input type="hidden" name="term" value="<?php echo $term ?>" />
    <input type="hidden" name="prid" value="<?php echo $prid ?>" />
    <input type="hidden" name="subgr" value="<?php echo $subgr ?>" />        
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
	        if ($DB->record_exists_select('bsu_plan_practice_members', "username = $addpupil AND subgroupid = $frm->subgr"))	{
                  notify('Error in adding student in subgroup!');
		    } else {
		        $rec = new stdClass();  
				$rec->username 	= $addpupil;
		        $rec->subgroupid = $frm->subgr;
		    	if (!$DB->insert_record('bsu_plan_practice_members', $rec)){
		    		print_error('Error in adding student in subgroup!');
		    	}
		    }
        }
	} else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {
		foreach ($frm->removeselect as $removepupil) {
			$DB->delete_records('bsu_plan_practice_members', array ('username' => $removepupil, 'subgroupid' => $frm->subgr));
		}
	} 
    
    if ($cntstud = $DB->count_records_select('bsu_plan_practice_members', "subgroupid = $frm->subgr")) {
        $DB->set_field_select('bsu_plan_practice_subgroup', 'countstud', $cntstud, "id=$frm->subgr");
    }
}


function table_copy_subgroups($fid, $pid, $term, $gid, $prid)
{
    global $CFG, $DB, $OUTPUT;
    
    $strsubgroup = '';
    $strshort = '';
    
    $table = new html_table();
    $table->head  = array (	'Копировать подгруппы', 'на дисциплины');
   	$table->align = array ("left", "left");

    $strsubgr = '';
    if ($subgroups = $DB->get_records_select ('bsu_plan_practice_subgroup', "groupid = $gid AND practiceid=$prid", null, 'name'))   {
        foreach ($subgroups as $sg) {
    	   $strsubgr .= $sg->name . ':<br>';
           $strsql = "SELECT distinct g.username, s.name FROM mdl_bsu_plan_practice_members g
            INNER JOIN mdl_bsu_students_2013 s  ON s.codephysperson = g.username
            where g.subgroupid=$sg->id
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
   $sql = "SELECT d.id as prid, d.Name as nname
            FROM {bsu_discipline_semestr} s
            INNER JOIN {bsu_practice} d ON d.id=s.practiceid
            INNER JOIN {bsu_plan} p ON p.id=d.planid
            WHERE p.id=$pid and s.numsemestr=$term and d.id<>$prid 
            ORDER BY n.Name";
    if ($disciplines = $DB->get_records_sql($sql))  {
        foreach ($disciplines as $discipline)   {
            $strdis .= '<input type="checkbox" name="d_' .$discipline->prid . '" id="d_' .$discipline->prid . '" value="1"/>';
            $strdis .= ' <label for="d_' .$discipline->prid . '">'. $discipline->nname .'</label><br>';
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
    
    

    if ($subgroups = $DB->get_records_select ('bsu_plan_practice_subgroup', "groupid = $frm->gid AND practiceid=$frm->prid", null, 'name'))   {
        foreach ($subgroups as $sg) {
            $members = $DB->get_records_select('bsu_plan_practice_members', "subgroupid = $sg->id");
            foreach ($disciplineids as $newdid) {
                $conditions = array ('practiceid' => $newdid, 'groupid' => $frm->gid, 'name' => $sg->name); 
    		    if ($DB->record_exists('bsu_plan_practice_subgroup', $conditions))	{
                      echo $OUTPUT->notification("Подгруппа '$sg->name' уже создана для дисциплины $newdid.");
    		    } else {
                    $sg->practiceid = $newdid;
    		    	if ($newsgid = $DB->insert_record('bsu_plan_practice_subgroup', $sg))   {
    		    	    if ($members)    {
    		    	         foreach($members as $member)    {
    		    	             $member->subgroupid = $newsgid;
                                 if (!$DB->insert_record('bsu_plan_practice_members', $member))    {
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
?>