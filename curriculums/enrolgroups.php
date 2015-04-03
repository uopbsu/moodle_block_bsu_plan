<?php   // $Id: enrolgroups.php,v 1.2 2012/12/06 09:51:45 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_charge/lib_delcharge.php");

    $fid = optional_param('fid', 0, PARAM_INT);					// faculty id
    $planid = optional_param('pid', 0, PARAM_INT);
    $action = optional_param('action', '', PARAM_ACTION);		// action
    $admin = optional_param('admin', '', PARAM_ACTION);		// action

    require_login();

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = get_string('groups', 'block_bsu_plan');

    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strtitle);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3);
    echo $OUTPUT->header();

	if ($frm = data_submitted())   {
	   save_bsu_ref_groups($frm, $fid, $planid);
	}

    echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
    $scriptname = "enrolgroups.php";
    //$scriptname2 = "rupview.php?fid=$fid";
    listbox_department($scriptname, $fid);

    if($fid > 0){
        listbox_plan("enrolgroups.php?fid=$fid", $fid, $planid);
        echo '</table>';
        if($planid > 0){
            echo $OUTPUT->heading(get_string('enrolgroups', 'block_bsu_plan'), 2);
            // echo $OUTPUT->heading('ВНИМАНИЕ!!! При отписывании группы будет удалено её расписание.<br /> Для того, чтобы сохранить расписание надо воспользоваться функцией "Перевод группы на другой РУП".' , 1);
            
            display_enrolpage($fid, $planid);
        } 
    } else {
        echo '</table>';
    }

    echo $OUTPUT->footer();


function delete_group_from_all_tables($groupid) 
{
    global $DB, $OUTPUT;
    
    $DB->delete_records('bsu_plan_groups', array ('groupid' => $groupid));
	$DB->delete_records('bsu_discipline_subgroup', array ('groupid' => $groupid));
    $DB->delete_records('bsu_schedule_mask', array ('groupid' => $groupid));
    $DB->delete_records('bsu_schedule', array ('groupid' => $groupid));
    $DB->delete_records('bsu_discipline_stream', array ('groupid' => $groupid));
    
    $sql = "SELECT id, edworkmaskid, edworkkindid, groupid, streammaskid 
            FROM mdl_bsu_edwork 
            WHERE groupid=$groupid";
    if ($datas = $DB->get_records_sql($sql))  {
        // print_object($datas);
        foreach ($datas as $data)   {
            switch ($data->edworkkindid)    {
                case 1: case 10: recalc_no_sum($data);          
                break; 

                case 2: case 3: case 4: case 5: case 6: case 7: case 8:
                        recalc_with_sum($data);                   
                break;
            }
        }
    }    
    
}


function save_bsu_ref_groups($frm, $fid, $planid)
{
    global $DB, $OUTPUT;
    
	if (!empty($frm->add) and !empty($frm->addselect) and confirm_sesskey()) {
		foreach ($frm->addselect as $groupid) {
		    if ($DB->record_exists_select('bsu_plan_groups', "planid = $planid AND groupid = $groupid"))	{
                  echo $OUTPUT->notification("Группа уже подписана на учебный план.");
		    } else {
		        $rec = new stdClass();  
		        $rec->planid  = $planid;
				$rec->groupid = $groupid;
		    	if (!$DB->insert_record('bsu_plan_groups', $rec)){
                   echo $OUTPUT->notification("Ошибка добавления записи в таблицу.");
		    	}
		    }
        }
	} else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {
		foreach ($frm->removeselect as $groupid) {
		    if (is_siteadmin()) {
                delete_group_from_all_tables($groupid);
            } else {    
                // if ($DB->get_records_sql("SELECT id FROM mdl_bsu_schedule where groupid = $groupid"))    {
                if ($DB->record_exists_select('bsu_schedule', "groupid = $groupid"))    {
                    echo $OUTPUT->notification('<strong>Группа не может быть отписана, т.к. для неё задано расписание. Для того, чтобы сохранить расписание надо воспользоваться функцией "Перевод группы на другой РУП".</strong>');
                } else  if ($DB->record_exists_select('bsu_edwork', "groupid = $groupid"))    {    
                    echo $OUTPUT->notification('<strong>Группа не может быть отписана, т.к. для неё задана нагрузка. Для того, чтобы сохранить нагрузку надо воспользоваться функцией "Перевод группы на другой РУП".</strong>');
                } else {
                    delete_group_from_all_tables($groupid);
                }
            }    
		}
	}
    
    if (!$DB->record_exists_select('bsu_plan_groups', "planid = $planid"))	{
        $yid = get_current_edyearid(true);
        delete_plan_charge($yid, $planid);
    }
        
}


function display_enrolpage($fid, $planid)
{
    global $CFG, $DB, $OUTPUT;
    
    $strsql = "SELECT g.name as gname, p.groupid, p.id
                FROM {bsu_ref_groups} g
                INNER JOIN {bsu_plan_groups} p ON p.groupid=g.id
                where p.planid=$planid
                order by gname";
                // g.departmentcode=$fid AND
    if($allgroups = $DB->get_records_sql($strsql))   {
		foreach ($allgroups as $group) 	{
            $enrolgroupsmenu[$group->groupid] = $group->gname;
		}
    }
    if (!empty($enrolgroupsmenu))	{
        $countenrol = count($enrolgroupsmenu);
    } else {
        $countenrol = 0;
    }    
    
    $strsql = "SELECT a.id as gid, a.departmentcode, a.name FROM {bsu_ref_groups} a
             LEFT JOIN {bsu_plan_groups}  b ON b.groupid = a.id
             where (a.departmentcode = $fid) AND (b.groupid is null)
             ORDER BY a.name DESC";
    // echo $strsql . '<br>';
    if ($arr_group = $DB->get_records_sql ($strsql)) 	{
    	foreach ($arr_group as $gr) {
    		$unenrolgroupsmenu[$gr->gid] = $gr->name;
    	}
    } else {
        $unenrolgroupsmenu = array();
    } 
    $countunenrol = count($unenrolgroupsmenu);

?>


<form name="enrolform" id="enrolform" method="post" action="enrolgroups.php">
<input type="hidden" name="fid" value="<?php echo $fid ?>" />
<input type="hidden" name="pid" value="<?php echo $planid ?>" />
<input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
  <table align="center" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td valign="top">
          <?php
              echo get_string('enroledgroups', 'block_bsu_plan') . ' (' . $countenrol. ')' ;
          ?>
      </td>
      <td></td>
      <td valign="top">
          <?php
              echo get_string("groups") . ' (' . $countunenrol. ')';
          ?>
      </td>
    </tr>
    <tr>
      <td valign="top">
          <select name="removeselect[]" size="20" id="removeselect"  multiple
                  onFocus="document.enrolform.add.disabled=true;
                           document.enrolform.remove.disabled=false;
                           document.enrolform.addselect.selectedIndex=-1;" />
          <?php
          if (!empty($enrolgroupsmenu))	{
              foreach ($enrolgroupsmenu as $key => $pm) {
                  echo "<option value=\"$key\">" . $pm . "</option>\n";
              }
          }
          ?>
          </select></td>
      <td id="buttonscell" align="center">
          <div id="addcontrols">
              <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('enrolgroup', 'block_bsu_plan'); ?>" title="<?php print_string('add'); ?>" /><br />
          </div>
          <br />
          <br />  
          <div id="removecontrols">
              <input name="remove" id="remove" type="submit" value="<?php echo get_string('unenrolgroup', 'block_bsu_plan').'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" />
          </div>
          (Если у группы задано расписание или нагрузка, <br/> то группа не будет отписана. <br/> 
            Для того, чтобы сохранить расписание и нагрузку надо <br/> 
            воспользоваться функцией <br/>"Перевод группы на другой РУП".<br />
            Отписать группу с удалением расписания и нагрузки <br />может только системный администратор.)
      </td>
      <td valign="top">
          <select name="addselect[]" size="20" id="addselect"  multiple
                  onFocus="document.enrolform.add.disabled=false;
                           document.enrolform.remove.disabled=true;
                           document.enrolform.removeselect.selectedIndex=-1;">
          <?php
          if (!empty($unenrolgroupsmenu))	{
              foreach ($unenrolgroupsmenu as $key => $sm) {
              	//if (!in_array($key, $idsteachers))	{
                  echo "<option value=\"$key\">" . $sm . "</option>\n";
                //}
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



function recalc_no_sum($data)
{
    global $DB;

    $sql = "SELECT id, edworkmaskid FROM mdl_bsu_edwork where edworkmaskid=$data->edworkmaskid";
     if ($edworks = $DB->get_records_sql($sql))  {
        $cnt = count ($edworks);
        if ($cnt == 1)   {
            $DB->delete_records_select('bsu_edwork', "id = $data->id");
            $DB->delete_records_select('bsu_edwork_mask', "id = $data->edworkmaskid");
        } else {
            $DB->delete_records_select('bsu_edwork', "id = $data->id");
        }
     }              
}


function recalc_with_sum($data)
{
    global $DB;
    
    $sql = "SELECT id, edworkmaskid FROM mdl_bsu_edwork where edworkmaskid=$data->edworkmaskid";
     if ($edworks = $DB->get_records_sql($sql))  {
        $cnt = count ($edworks);
        if ($cnt == 1)   {
            $DB->delete_records_select('bsu_edwork', "id = $data->id");
            $DB->delete_records_select('bsu_edwork_mask', "id = $data->edworkmaskid");
        } else {
            $DB->delete_records_select('bsu_edwork', "id = $data->id");
            if ($data->streammaskid == 0)   {
                // суммируем часы по новой и записываем в bsu_edwork_mask
                $sql = "SELECT sum(hours) as hours FROM {bsu_edwork} WHERE edworkmaskid=$data->edworkmaskid";
                if ($hour = $DB->get_record_sql($sql))  {
                    $DB->set_field_select('bsu_edwork_mask', 'hours', $hour->hours, "id = $data->edworkmaskid");
                }
            } else {
                // часики в bsu_edwork_mask не меняются
            }         
        }
     }
}

?>