<?php  // setsubdepartment.php

	require_once("../../../config.php");
	require_once("../lib_plan.php");
	require_once('../lib_report.php');

	require_login();

	$fid = optional_param('fid', 0, PARAM_INT);
	$pid = optional_param('pid', 0, PARAM_INT);					// plan id
	$did = optional_param('did', 0, PARAM_INT);					// disciplineid id
	$sdid = optional_param('sdid', 0, PARAM_INT);				// subdepartment id
	$god = optional_param('god', 0, PARAM_INT);
	$tab = optional_param('tab', 'v', PARAM_TEXT);
	$yid = optional_param('yid', 0, PARAM_INT);					// current year
	$eid = optional_param('edformid', 0, PARAM_INT);			// edformid
	$sid = optional_param('sid', 0, PARAM_INT);
	$sdid = optional_param('sdid', 0, PARAM_INT);
	$sym = optional_param('sym', 1, PARAM_INT);
	$dnid = optional_param('dnid', 0, PARAM_INT);				// edformid
	$action = optional_param('action', '', PARAM_TEXT);     	// action
	$id = optional_param('id', 0, PARAM_INT);					// id record from table bsu_discipline_subdepartment_zav

	if ($yid == 0)   {
		$yid = get_current_edyearid(); // true
		// $yid++;
	}
	$god = $yid;

	if($action == 'delete') {
		$update = new stdClass();
		$update->id = $id;
		$update->deleted = 1;
		$update->timemodified = time();
		$update->usermodified = $USER->id;
		$DB->update_record('bsu_discipline_subdepartment_zav', $update);
	}

	if($action == 'complete') {
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
//print $sql;		       
		if(!$DB->get_records_sql($sql)) {
            $sql = "SELECT id FROM {bsu_discipline_subdepartment}
					WHERE
						yearid=$yid AND
						disciplineid=$did AND
						specialityid=$sid AND
						disciplinenameid=$d";
//print $sql.'<br>';
            if($ver = $DB->get_records_sql($sql))
                foreach($ver as $k=>$v) {
                    $DB->delete_records_select('bsu_discipline_subdepartment', "id=$v->id");
                }

			$sql = "SELECT id FROM {bsu_discipline_subdepartment}
					WHERE
						yearid=$yid AND
						disciplineid=$did AND
						specialityid=$sid AND
						disciplinenameid=$d AND
						subdepartmentid=1
				   ";
			$ver = $DB->get_record_sql($sql);
			
			if(!$ver) {
				$DB->insert_record('bsu_discipline_subdepartment', $new_record);
			} else {
				$new_record->id = $ver->id;
				$DB->update_record('bsu_discipline_subdepartment', $new_record);
			}
		}
		$update = new stdClass();
		$update->id = $id;
		$update->deleted = 1;
		$update->timemodified = time();
		$update->usermodified = $USER->id;
		$DB->update_record('bsu_discipline_subdepartment_zav', $update);
	}

	if($action == 'subdep') {
		$d = $DB->get_record_sql("SELECT disciplinenameid FROM {bsu_discipline} WHERE id=$did");
		$specialityid = $DB->get_record_sql("SELECT specialityid FROM {bsu_plan} WHERE id=$pid");

		$subdep = new stdClass();
		$subdep->yearid = $yid;
		$subdep->disciplineid = $did;
		$subdep->disciplinenameid = $d->disciplinenameid;
		$subdep->subdepartmentid = $sdid;
		$subdep->specialityid = $specialityid->specialityid;
		$subdep->usermodified = $USER->id;
		$subdep->timemodified =time();

		$verify = $DB->get_records_sql("SELECT id FROM {bsu_discipline_subdepartment_zav} WHERE
					yearid=$yid AND
					disciplineid=$did AND
					disciplinenameid=$d->disciplinenameid AND
					subdepartmentid IN ($sdid) AND
					specialityid=$specialityid->specialityid AND
					deleted=0
					");

		if(!$verify) {
			$DB->insert_record('bsu_discipline_subdepartment_zav', $subdep);
		}
	}


	if($action == 'excel') {
		switch($tab) {
			case 'r':
				if ($fid == 0) $action = 'allupdate';
				$table = table_discipline_for_prikaz($sid, $yid, $fid, $action);
				print_table_to_excel($table);
			break;
			case 'r1':
	            $subdeps_zav_a = array();
	            $subdeps_a = array();

	            $sql = "SELECT * FROM {bsu_discipline_subdepartment} WHERE yearid=$yid";
	            $subdeps = $DB->get_records_sql($sql);
	            foreach($subdeps as $subdep) {
	                if(!isset($subdeps_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"])) {
	                    $subdeps_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"] = $subdep->subdepartmentid;
	                } else {
	                    $subdeps_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"] .= '#'.$subdep->subdepartmentid;
	                }
	            }

	            $sql = "SELECT * FROM {bsu_discipline_subdepartment_zav} WHERE yearid=$yid AND deleted=0";
	            $subdeps = $DB->get_records_sql($sql);
	            foreach($subdeps as $subdep) {
	                if(!isset($subdeps_zav_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"])) {
	                    $subdeps_zav_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"] = $subdep->subdepartmentid;
	                } else {
	                    $subdeps_zav_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"] .= '#'.$subdep->subdepartmentid;
	                }
	            }
	            
				$table = table_discipline_coincidence($subdeps_a, $subdeps_zav_a);
				print_table_to_excel($table);
			break;
		}
		exit();
	} else if ($action == 'copysub') {
		copy_discipline_subdepartment_one_department($yid-1, $fid);
	} else if($action == 'report2_excel')   {
		if ($fid == 0) $action = 'report2_all';
		$table = table_variative_discipline($yid, $fid, $action, $sym);
		print_table_to_excel($table);
		exit();
	}

	$scriptname = 'setsubdepartment.php';

	$strtitle = get_string('pluginname', 'block_bsu_plan');
	$strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
	$strtitle3 = get_string('disciplines', 'block_bsu_plan');
	$strscript = 'Назначение кафедр';

	$PAGE->set_url('/blocks/bsu_plan/index.php');
	$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
	$PAGE->set_heading($strscript);
	$PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
	$PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
	$PAGE->navbar->add($strscript);
	echo $OUTPUT->header();

	ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
	@set_time_limit(0);
	@ob_implicit_flush(true);
	@ob_end_flush();
	@raise_memory_limit("512");
	if (function_exists('apache_child_terminate')) {
		@apache_child_terminate();
	}

	include('access_users.php');

	$sql = "SELECT id, contextid FROM {role_assignments} WHERE roleid=24 AND userid=$USER->id";
    $role = $DB->get_records_sql_menu($sql);

	if (!in_array($USER->id, $ACCESS_USER) && !is_siteadmin() && !$role)  {
		// notice(get_string('permission', 'block_bsu_plan'), '../index.php');
		notice('Доступ закрыт. Для закрепления дисциплины за кафедрой обратитесь к Забниной Галине Геннадьевне, т.30-14-81.', '../index.php');
	}

	$toprow[] = new tabobject('v', $scriptname."?tab=v&fid=$fid&yid=$yid&eid=$eid", 'Закрепление дисциплин');
	$toprow[] = new tabobject('r', $scriptname."?tab=r&fid=$fid&yid=$yid&eid=$eid", 'Отчет о зареплении дисциплин');
	$toprow[] = new tabobject('r1', $scriptname."?tab=r1&fid=$fid&yid=$yid&eid=$eid", 'Отчет о не совпадении кафедр');
	$toprow[] = new tabobject('o', $scriptname."?tab=o&fid=$fid&yid=$yid&eid=$eid", 'Закрепить 1 дисциплину за 1 кафедрой');
	$toprow[] = new tabobject('r2', $scriptname."?tab=r2&fid=$fid&yid=$yid&eid=$eid", 'Отчет о зареплении дисциплин по выбору');
	$tabs = array($toprow);
	print_tabs($tabs, $tab, NULL, NULL);

	if ($tab != 'o'&&$tab != 'r'&&$tab!='r1')	{
		echo $OUTPUT->box_start('generalbox sitetopic');
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_fid($scriptname."?tab={$tab}&yid=$yid", $fid);
		listbox_year($scriptname."?tab={$tab}&fid=$fid", $yid);
		listbox_edform($scriptname."?tab={$tab}&fid=$fid&yid=$yid&sid=$sid", $eid);
		echo '</table>';
		echo $OUTPUT->box_end();
	}
	else
	if ($tab == 'r')  {
		echo $OUTPUT->box_start('generalbox sitetopic');
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_fid($scriptname."?tab={$tab}&yid=$yid", $fid);
		listbox_year($scriptname."?tab={$tab}&fid=$fid", $yid);
		listbox_edform($scriptname."?tab={$tab}&fid=$fid&yid=$yid&sid=$sid", $eid);
		if ($fid>0){
		   listbox_subdepartment($scriptname."?tab={$tab}&fid=$fid&yid=$yid&eid=$eid", $sid, $fid, true, $yid);
		}
		echo '</table>';
		echo $OUTPUT->box_end();
	}
	else 
		if($tab != 'r1')	{
			echo $OUTPUT->box_start('generalbox sitetopic');
			echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
			listbox_fid($scriptname."?tab={$tab}&yid=$yid", $fid);
			listbox_year($scriptname."?tab={$tab}&fid=$fid", $yid);
			echo '</table>';
			echo $OUTPUT->box_end();
		}

	if (($USER->id == 106008 || $USER->id == 52652) && $fid < 19000)    {
		notify ('<strong>Выберите, пожалуйста, своё подразделение.</strong>');
		echo $OUTPUT->footer();
		exit();
	}


	if ($USER->id == 72982 && $fid != 10305)    {
		notify ('<strong>Выберите, пожалуйста, своё подразделение.</strong>');
		echo $OUTPUT->footer();
		exit();
	}

	$subdeparts='';

	if ($frm = data_submitted())   {
		// print_object($frm);
		if ($action == 'setone'&&isset($frm->savepoints))    {
			if ($DB->record_exists_select('bsu_discipline_subdepartment', "yearid=$yid and disciplinenameid=$frm->dnid"))    {
				$select = "set subdepartmentid=$frm->sid where yearid=$yid and disciplinenameid=$frm->dnid";

				$sql = "update mdl_bsu_discipline_subdepartment " . $select;
				$DB->Execute($sql);

				$sql = "update mdl_bsu_edwork_mask " . $select;
				$DB->Execute($sql);

				$sql = "update mdl_bsu_edwork " . $select;
				$DB->Execute($sql);
				notify("<strong>Дисциплина  $frm->dnid закреплена.</strong>", 'notifysuccess');
			}  else {
				notify("<strong>Дисциплина $frm->dnid не найдена. Дисциплина не закреплена.</strong>");
			}
		} else if ($action == 'setdepone'&&isset($frm->savepoints))    {
			// print_object($frm);
			if ($DB->record_exists_select('bsu_discipline_subdepartment', "yearid=$yid and disciplinenameid=$frm->dnid"))    {

				$sql = "create temporary table temp
						SELECT id FROM mdl_bsu_discipline_subdepartment m
						where yearid=$yid and disciplineid in
						(SELECT d.id FROM mdl_bsu_discipline d
						inner join mdl_bsu_plan p on p.id=d.planid
						where p.notusing=0 and departmentcode=$fid and d.disciplinenameid=$frm->dnid)";
				$DB->Execute($sql);

				$sql = "update (select id from temp) as t1,  mdl_bsu_discipline_subdepartment as t2 set t2.subdepartmentid=$frm->sid where t1.id=t2.id";

				$DB->Execute($sql);
				notify("<strong>Дисциплина  $frm->dnid закреплена за факультетом $fid.</strong>", 'notifysuccess');
			}
		} else {
			   $sql="SELECT id, subdepartmentid FROM mdl_bsu_discipline_subdepartment m
			   where disciplineid in
			   (SELECT d.id FROM mdl_bsu_discipline d
			   inner join mdl_bsu_plan p on p.id=d.planid
			   where p.notusing=0 and d.disciplinenameid=$frm->dnid)
			   group by subdepartmentid";
			   if ($subdeps = $DB->get_records_sql_menu($sql))
				  $subdeparts='and id in ('.implode(',',$subdeps).')';
		}
	}

	$SUBDEPSNAMES = $DB->get_records_select_menu('bsu_vw_ref_subdepartments', "yearid=$yid $subdeparts", null, 'name', 'id, name');
	$SUBDEPSNAMES[1] = '<strong>НЕ ОПРЕДЕЛЕНА</strong>';

	switch($tab){
		case 'v':
				echo $OUTPUT->heading("$strscript:", 2, 'headingblock header');
				echo $OUTPUT->box_start('generalbox sitetopic');

					if($fid <> 0){
						ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
						@set_time_limit(0);
						@ob_implicit_flush(true);
						@ob_end_flush();

						echo html_writer::table(withoutchairs_view($yid, $fid, $eid));
					}

				echo $OUTPUT->box_end();
		break;
		case 'r1':
            echo '<div = align="center">';
            $options = array('yid' => $yid, 'fid' => $fid, 'tab' => 'r1', "action"=>"excel");
            echo $OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options);
            echo '</div>';

            $subdeps_zav_a = array();
            $subdeps_a = array();

            $sql = "SELECT * FROM {bsu_discipline_subdepartment} WHERE yearid=$yid";
            $subdeps = $DB->get_records_sql($sql);
            foreach($subdeps as $subdep) {
                if(!isset($subdeps_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"])) {
                    $subdeps_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"] = $subdep->subdepartmentid;
                } else {
                    $subdeps_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"] .= '#'.$subdep->subdepartmentid;
                }
            }

            $sql = "SELECT * FROM {bsu_discipline_subdepartment_zav} WHERE yearid=$yid AND deleted=0";
            $subdeps = $DB->get_records_sql($sql);
            foreach($subdeps as $subdep) {
                if(!isset($subdeps_zav_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"])) {
                    $subdeps_zav_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"] = $subdep->subdepartmentid;
                } else {
                    $subdeps_zav_a["$subdep->disciplineid~$subdep->disciplinenameid~$subdep->specialityid"] .= '#'.$subdep->subdepartmentid;
                }
            }

            $table = table_discipline_coincidence($subdeps_a, $subdeps_zav_a);
            echo '<div = align="center">'.html_writer::table($table) . '</div>';
            echo '<div = align="center">';
            echo $OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options);
            echo '</div>';
		break;
		case 'r':
				echo '<table border=0 cellspacing="0" cellpadding="0" align="center">';
				echo '<tr> <td>';
				$options = array('yid' => $yid, 'fid' => $fid, 'tab' => 'r', "action"=>"allupdate");
				echo $OUTPUT->single_button(new moodle_url($scriptname, $options), 'Показать отчет для всех факультетов', 'get', $options);
				echo '</td><td>';
				$options = array('yid' => $yid, 'fid' => $fid, 'tab' => 'r', "action"=>"excel");
				echo $OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options);
				echo '</td></tr>';
				echo '</table>';

				if($fid > 0 || $action == 'allupdate')    {
					$table = table_discipline_for_prikaz($sid, $yid, $fid, $action);
					echo '<div = align=center>'.html_writer::table($table) . '</div>';

					echo '<table border=0 cellspacing="0" cellpadding="0" align="center">';
					echo '<tr> <td>';
					$options = array('yid' => $yid, 'fid' => $fid, 'tab' => 'r', "action"=>"allupdate");
					echo $OUTPUT->single_button(new moodle_url($scriptname, $options), 'Показать отчет для всех факультетов', 'get', $options);
					echo '</td><td>';
					$options = array('yid' => $yid, 'fid' => $fid, 'tab' => 'r', "action"=>"excel");
					echo $OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options);
					if ($yid >=15)  {
						echo '</td><td>';
						$options = array('yid' => $yid, 'fid' => $fid, 'tab' => 'r', "action"=>"copysub");
						echo $OUTPUT->single_button(new moodle_url($scriptname, $options), 'Скопировать закрепление дисциплин с прошлого уч. года', 'get', $options);
					}
					echo '</td></tr>';
					echo '</table>';
				}
		break;

		case 'o':
				if ((in_array($USER->id, $ACCESS_USER) ||  is_siteadmin()))   {
				   echo  '<form name="setonesubdep" method="post" action="setsubdepartment.php">';
				   if ($fid == 0)  {
					   echo  '<input type="hidden" name="action" value="setone">';
				   } else {
					   echo  '<input type="hidden" name="action" value="setdepone">';
				   }
				   echo  '<input type="hidden" name="tab" value="' .  $tab . '">';
				   echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
				   echo  '<input type="hidden" name="fid" value="' .  $fid . '">';

					echo '<table border=0 cellspacing="0" cellpadding="0" align="center">';
					//echo html_writer::select($options, 'dnid', $dnid);
					listbox_all_univ_discipline($scriptname."?tab={$tab}&fid=$fid&yid=$yid", $dnid, $yid);
 
					echo '<tr> <td>за кафедрой:';
					echo '</td><td>';
					echo html_writer::select($SUBDEPSNAMES, 'sid', $sid);
					echo '</td></tr>';

					echo '</table>';
					if ($fid == 0)  {
						echo  '<div align="center">';
						echo  '<input type="submit" name="savepoints" value="Закрепить кафедру за дисциплиной для всех факультетов"></div>';
					} else {
						echo  '<div align="center">';
						echo  '<input type="submit" name="savepoints" value="Закрепить кафедру за дисциплиной по ФАКУЛЬТЕТУ"></div>';
					}
					echo  '</form>';
					echo  '<p>&nbsp;</p>';
				} else {
					notice('Доступ закрыт. Для закрепления дисциплины за кафедрой обратитесь к Забниной Галине Геннадьевне, т.30-14-81.', '../index.php');
				}
		break;

		case 'r2':
				echo '<table border=0 cellspacing="0" cellpadding="0" align="center">';
				echo '<tr> <td colspan=2>';
				listbox_symbol_variative($scriptname."?tab={$tab}&fid=$fid&yid=$yid&edformid=$eid", $sym);
				echo '</td></tr><tr> <td>';
				$options = array('yid' => $yid, 'fid' => $fid, 'tab' => $tab, 'sym' => $sym, "action"=>"report2_all");
				echo $OUTPUT->single_button(new moodle_url($scriptname, $options), 'Показать отчет для всех факультетов', 'get', $options);
				echo '</td><td>';
				$options = array('yid' => $yid, 'fid' => $fid, 'tab' => $tab, 'sym' => $sym, "action"=>"report2_excel");
				echo $OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options);
				echo '</td></tr>';
				echo '</table>';

				if($fid > 0 || $action == 'report2_all')    {
					$table = table_variative_discipline($yid, $fid, $action, $sym);
					echo '<div = align=center>'.html_writer::table($table) . '</div>';
				}
		break;

	}

	echo $OUTPUT->footer();
 
function table_discipline_coincidence($subdeps_a, $subdeps_zav_a) {
    GLOBAL $DB, $yid;

	$SUBDEPSNAMES = $DB->get_records_select_menu('bsu_vw_ref_subdepartments', "yearid=$yid", null, 'name', 'id, name');

    $table = new html_table();
    $table->head = array('Институт/факультет', 'Наименование кафедры', 'Претенденты', 'Направление подготовки, специальность', 'Наименование дисциплин', 'Цикл', 'ID плана', 'План');
    $table->align = array ('left','left', 'left', 'left','left', 'left', 'left');
    $table->columnwidth = array (40, 40, 50, 40, 40, 10, 10, 40);
    $table->titlesrows = array(20); // , 20,20, 20,20, 20);
    $table->titles = array();
    $table->titlesalign = 'center';
    $table->titles[] = 'О закреплении дисциплин учебных планов 20' . $DB->get_field_select('bsu_ref_edyear', 'edyear', "id=$yid") . ' учебного года';
    $table->downloadfilename = "disciplines_coincidence";
    $table->worksheetname = $table->downloadfilename;

    foreach($subdeps_a as $key=>$value) {
        $temp = explode('~', $key);
        $disciplineid = $temp[0];
        $sql = "SELECT
                    p.id AS planid,
                    p.name AS planname,
                    brd.name AS department,
                    brdisc.name AS disciplinename,
                    bt.specyal AS speciality,
                    bd.cyclename AS cyclename
                FROM {bsu_plan} p
                INNER JOIN {bsu_discipline} bd ON p.id=bd.planid
                INNER JOIN {bsu_tsspecyal} bt ON bt.idspecyal=p.specialityid
                INNER JOIN {bsu_ref_disciplinename} brdisc ON bd.disciplinenameid=brdisc.id
                INNER JOIN {bsu_ref_department} brd ON brd.departmentcode=p.departmentcode
                WHERE bd.id=$disciplineid";

        $data = $DB->get_record_sql($sql);

        $pretendent = $f_s = $f_e = '';
        if(isset($subdeps_zav_a[$key])) {
            $list = explode('#', $subdeps_zav_a[$key]);
            foreach($list AS $key1=>$value1) {
                $pretendent.= $SUBDEPSNAMES[$value1].'<br>';

                if(($SUBDEPSNAMES[$value] != $SUBDEPSNAMES[$value1])||(count($list) >= 2)) {
                    $f_s = '<font color="#ee0000">';
                    $f_e = '</font>';
                }
            }
        }

        if($f_s != '') {
	        $table->data[] = array ($data->department,
	                                $SUBDEPSNAMES[$value],
	                                $pretendent,
	                                $data->speciality,
	                                $data->disciplinename,
	                                $data->cyclename,
	                                $data->planid,
	                                $data->planname,);
		}
    }

    return $table;
}

function table_discipline_for_prikaz($sid, $yid, $fid, $action)    
{
	global $DB, $USER;

	$select = '';

	$predyid = $yid-1;
  
	if($fid > 0) {
		$select = "p.departmentcode = $fid";
	}
	if($action == 'allupdate') {
		$select = "p.departmentcode > 10000";
	}

	$sql = "CREATE TEMPORARY TABLE temp_bsu_plan_terms
			SELECT distinct p.id as planid, (2*($yid - SUBSTRING(rg.name, 5, 2)) - 1) as term1, 2*($yid - SUBSTRING(rg.name, 5, 2)) as term2
			FROM mdl_bsu_plan p
			inner join mdl_bsu_plan_groups pg on p.id=pg.planid
			inner join mdl_bsu_ref_groups rg on rg.id=pg.groupid
			where $select and p.notusing=0 and p.deleted=0 and rg.yearid in ($yid,$predyid)
			order by planid";
	// print $sql;
	$DB->Execute($sql);

	$sql = "CREATE TEMPORARY TABLE temp_bsu_plan_disciplines
			SELECT distinct d.id, ds.subdepartmentid, d.disciplinenameid, d.cyclename, pt.planid
			FROM temp_bsu_plan_terms pt
			inner join mdl_bsu_discipline d using(planid)
			inner join mdl_bsu_discipline_semestr s on d.id=s.disciplineid
			left join mdl_bsu_discipline_subdepartment ds on d.id=ds.disciplineid
			where s.numsemestr in (pt.term1, pt.term2) and d.notusing=0 and (ds.yearid=$yid or ds.yearid is null)";
	$DB->Execute($sql);

	$sql = "update temp_bsu_plan_disciplines set subdepartmentid = 1 where subdepartmentid is null";
	$DB->Execute($sql);
	if ($sid>0) {
		$sub="WHERE subdepartmentid=$sid";
	}
	else {
		$sub="";
	}

	$sql = "CREATE TEMPORARY TABLE temp_bsu_prikaz
			SELECT pd.id, pd.subdepartmentid, rd.name as fakultet, rs.name as subdepartmentname,
				   pd.disciplinenameid, n.Name as disciplinename, pd.cyclename,
				   pd.planid, bp.name as planname,
				   bts.kodspecyal as codespecyal, btk.kvalifkod as kvalifkod, btk.kvalif as kval, bts.specyal,
				   bto.otdelenie, bp.specialityid, bp.profileid, rp.name as profilename, bp.kvalif, bp.edformid
			from temp_bsu_plan_disciplines pd
			inner join mdl_bsu_plan bp on bp.id=pd.planid
			INNER JOIN mdl_bsu_tsspecyal bts ON bp.specialityid=bts.idspecyal
			INNER JOIN mdl_bsu_tskvalifspec btk ON bp.kvalif=btk.idkvalif
			INNER JOIN mdl_bsu_tsotdelenie bto ON bp.edformid=bto.idotdelenie
			INNER JOIN mdl_bsu_ref_disciplinename n ON n.Id=pd.disciplinenameid
			inner join mdl_bsu_ref_department rd on rd.departmentcode=bp.departmentcode
			LEFT JOIN mdl_bsu_ref_profiles rp on rp.id=bp.profileid
			left join mdl_bsu_vw_ref_subdepartments rs on pd.subdepartmentid=rs.id";

	$DB->Execute($sql);

	$table = new html_table();
	// $table->head = array('Код факультета',''Факультет-заказчик','Специальность в системе','Специальность/направление','Курс в след. уч.году','Название планов в системе','Дисциплины','Кафедры');
	$table->head = array('Институт/факультет', 'Наименование кафедры', 'Направление подготовки, специальность',
						 'Наименование дисциплин',	'Цикл', 'ID плана', 'План');
	$table->align = array ('left','left', 'left', 'left','left', 'left', 'left');
	$table->columnwidth = array (40, 40, 50, 40, 10, 10, 40);
	$table->titlesrows = array(20); // , 20,20, 20,20, 20);
	$table->titles = array();
	$table->titlesalign = 'center';
	$table->titles[] = 'О закреплении дисциплин учебных планов 20' . $DB->get_field_select('bsu_ref_edyear', 'edyear', "id=$yid") . ' учебного года';
	$table->downloadfilename = "disciplines_prikaz_{$fid}";
	$table->worksheetname = $table->downloadfilename;

	$sql = "SELECT concat (id, '_', subdepartmentid) as id0, fakultet,subdepartmentid, subdepartmentname,
				  codespecyal,kvalifkod,kval, specyal, profilename, otdelenie, disciplinename, disciplinenameid, cyclename, planid, planname
			FROM temp_bsu_prikaz
			$sub
			group by subdepartmentid, specialityid, profileid, edformid, kvalif, disciplinenameid
			order by disciplinename"; // subdepartmentname

	if ($datas = $DB->get_records_sql($sql))  {
		foreach ($datas as $discipline) 	{
			$kvalifnum=$discipline->kvalifkod;
			$kvaltext="";
			$specyal=explode(" ",$discipline->specyal);
			$specyalnum = $discipline->codespecyal;
			if (strpos($specyalnum,".")===false&&is_numeric($specyalnum)) {
			if (substr($specyalnum, strlen($specyalnum)-1, 1)!='.') {
			   $specyalnum.=".".$kvalifnum;
			}
			else {
			   $specyalnum.=$kvalifnum;
			}
			}
			else {
			  $kvaltext=" ($discipline->kval)";
			}
			$specyalname="";
			foreach($specyal as $id=>$spec)
			if ($id>0) $specyalname.=" ".$spec;
			$specyal = $specyalnum .  $specyalname;

			$planname =  $specyal;
			if (!empty($discipline->profilename))  {
				$planname .= ' (' . $discipline->profilename . ') ';
			}
			$planname .= $kvaltext.', ' . $discipline->otdelenie;

			if ($discipline->subdepartmentid==1) {
			   $subdepartment="<font color=red>".$discipline->subdepartmentname."</font>";
			}
			else {
			   $subdepartment=$discipline->subdepartmentname;
			}

			$table->data[] = array ($discipline->fakultet, $subdepartment, $planname,
									$discipline->disciplinename, $discipline->cyclename, $discipline->planid,
									$discipline->planname);
		}
	}

	return $table;
}
 
 

function  copy_discipline_subdepartment_one_department($yid, $fid)
{
	global $DB, $CFG;

	$nextyid = $yid+1;

	$sql = "CREATE TEMPORARY TABLE temp_bsu_plan_terms0
			SELECT distinct p.id as planid, (2*($yid - SUBSTRING(rg.name, 5, 2)) - 1) as term1, 2*($yid - SUBSTRING(rg.name, 5, 2)) as term2
			FROM mdl_bsu_plan p
			inner join mdl_bsu_plan_groups pg on p.id=pg.planid
			inner join mdl_bsu_ref_groups rg on rg.id=pg.groupid
			where p.departmentcode = $fid and p.notusing=0 and p.deleted=0 and rg.yearid in ($yid,$nextyid)
			order by planid";
	$DB->Execute($sql);

	$sql = "CREATE TEMPORARY TABLE temp_bsu_plan_disciplines0
			SELECT distinct d.id, ds.subdepartmentid, d.disciplinenameid, d.cyclename, pt.planid, ds.yearid, ds.specialityid
			FROM mdl_bsu_discipline d
			left join mdl_bsu_discipline_subdepartment ds on d.id=ds.disciplineid
			left join temp_bsu_plan_terms0 pt using(planid)
			left join mdl_bsu_discipline_semestr s on d.id=s.disciplineid
			where s.numsemestr in (pt.term1, pt.term2) and d.notusing=0 and (ds.yearid=$yid or ds.yearid is null)";
	$DB->Execute($sql);

	$sql = "CREATE TEMPORARY TABLE temp_bsu_plan_terms2
			SELECT distinct p.id as planid, (2*($nextyid - SUBSTRING(rg.name, 5, 2)) - 1) as term1, 2*($nextyid - SUBSTRING(rg.name, 5, 2)) as term2, p.specialityid
			FROM mdl_bsu_plan p
			inner join mdl_bsu_plan_groups pg on p.id=pg.planid
			inner join mdl_bsu_ref_groups rg on rg.id=pg.groupid
			where p.departmentcode = $fid and p.notusing=0 and p.deleted=0 and rg.yearid in ($yid,$nextyid)
			order by planid";
	$DB->Execute($sql);

	$sql = "CREATE TEMPORARY TABLE temp_bsu_2
			SELECT distinct d.id as did, d.disciplinenameid, d.cyclename, pt.planid, pt.specialityid
			FROM temp_bsu_plan_terms2 pt
			inner join mdl_bsu_discipline d using(planid)
			inner join mdl_bsu_discipline_semestr s on d.id=s.disciplineid
			where s.numsemestr in (0, pt.term1, pt.term2) and d.notusing=0";
	$DB->Execute($sql);
/*
insert into mdl_bsu_discipline_subdepartment (yearid, disciplineid, disciplinenameid, subdepartmentid, specialityid);
select 15 as yearid, did as disciplineid, d.disciplinenameid, d.subdepartmentid, d.specialityid
from temp_bsu_plan_disciplines0 d
inner join temp_bsu_2 t on d.id=t.did and d.subdepartmentid is not null;
*/
	$k=0;
	$newsubdeps = $DB->get_records_select_menu('bsu_vw_ref_subdepartments', "yearid=$nextyid", null, '', 'id2, id');
	$dep_codes = $DB->get_records_select_menu('bsu_vw_ref_subdepartments', "yearid=$nextyid", null, '', 'id2, DepartmentCode');
	$newsubdeps[1] = 1;
	$filials=array(19100,19206,19207,10305);
	$sql = "select did as disciplineid, t.disciplinenameid, d.subdepartmentid, t.specialityid, t.planid
			from temp_bsu_2 t
			left join temp_bsu_plan_disciplines0 d on d.id=t.did";
	if ($datas = $DB->get_records_sql($sql))  {
		foreach ($datas as $discipline) 	{
			$sqrl="SELECT guid,idspecyal3,idFakultet FROM mdl_bsu_tsspecyal WHERE idspecyal=$discipline->specialityid";
			$specid=array();
			$specid2=array();
			if ($spec2 = $DB->get_record_sql($sqrl))
			{
			$guid=array();
			if ($spec2->idspecyal3>1) {
				$sqrl="SELECT idspecyal, guid FROM mdl_bsu_tsspecyal WHERE idspecyal3=$spec2->idspecyal3";
				if ($specs1 = $DB->get_records_sql($sqrl))
				foreach($specs1 as $spec1)
				$guid[]="'$spec1->guid'";
			}
			else {
				$guid[]="'$spec2->guid'";
			}
				$guid=implode(',',$guid);
				$sqrl="SELECT idspecyal FROM mdl_bsu_tsspecyal WHERE guid in ($guid)";
				if ($specs3 = $DB->get_records_sql($sqrl))
				foreach($specs3 as $spec3)
				$specid[]=$spec3->idspecyal;
				$sqrl="SELECT idspecyal FROM mdl_bsu_tsspecyal WHERE idFakultet=$spec2->idfakultet";
				if ($specs3 = $DB->get_records_sql($sqrl))
				foreach($specs3 as $spec3)
				$specid2[]=$spec3->idspecyal;
			}
			$spec_sql="";
			if (count($specid)>0) {
				$specid=implode(',',$specid);
				$spec_sql="and specialityid in ($specid)";
			}
			$spec_sql2="";
			if (count($specid2)>0) {
				$specid2=implode(',',$specid2);
				$spec_sql2="and specialityid in ($specid2)";
			}

			if (empty($discipline->subdepartmentid)||!isset($discipline->subdepartmentid)) 	{
				$subdepid = 1;
				$sql = "SELECT subdepartmentid FROM mdl_bsu_discipline_subdepartment m
						where disciplinenameid=$discipline->disciplinenameid and yearid=$yid and subdepartmentid>1 $spec_sql
						group by subdepartmentid";
				if ($subdeps = $DB->get_records_sql($sql))  {
					$cnt = 0;
					foreach($subdeps as $subdep) {
						   if (!in_array($fid,$filials)) {
							  if (!in_array($dep_codes[$subdep->subdepartmentid],$filials)) {
								 $cnt++;
								 $subdepid=$subdep->subdepartmentid;
							  }
						   }
						   else {
							  if (in_array($dep_codes[$subdep->subdepartmentid],$filials)) {
								 $cnt++;
								 $subdepid=$subdep->subdepartmentid;
							  }
						   }
						}
					if ($cnt != 1)  {
						$subdepid = 1;
						// notify($discipline->subdepartmentid);
					} else {
						// $discipline->subdepartmentid = 1;
						// echo $sql . '<br />';
						// notify($cnt);
					}
				}
				if ($subdepid==1) {
					$sql = "SELECT subdepartmentid FROM mdl_bsu_discipline_subdepartment m
						where disciplinenameid=$discipline->disciplinenameid and yearid=$yid and subdepartmentid>1 $spec_sql2
						group by subdepartmentid";
						if ($subdeps = $DB->get_records_sql($sql))  {
						   $cnt = 0;
						   foreach($subdeps as $subdep) {
							 if (!in_array($fid,$filials)) {
								if (!in_array($dep_codes[$subdep->subdepartmentid],$filials)) {
								   $cnt++;
								   $subdepid=$subdep->subdepartmentid;
								}
							 }
							 else {
							   if (in_array($dep_codes[$subdep->subdepartmentid],$filials)) {
								  $cnt++;
								  $subdepid=$subdep->subdepartmentid;
							   }
							 }
						   }
						   if ($cnt != 1)  {
							  $subdepid = 1;
						   }
						}
				}
				if ($subdepid==1) {
					$sql = "SELECT subdepartmentid FROM mdl_bsu_discipline_subdepartment m
						where disciplinenameid=$discipline->disciplinenameid and yearid=$yid and subdepartmentid>1
						group by subdepartmentid";
						if ($subdeps = $DB->get_records_sql($sql))  {
						   $cnt = 0;
						   foreach($subdeps as $subdep) {
							 if (!in_array($fid,$filials)) {
								if (!in_array($dep_codes[$subdep->subdepartmentid],$filials)) {
								   $cnt++;
								   $subdepid=$subdep->subdepartmentid;
								}
							 }
							 else {
							   if (in_array($dep_codes[$subdep->subdepartmentid],$filials)) {
								  $cnt++;
								  $subdepid=$subdep->subdepartmentid;
							   }
							 }
						   }
						   if ($cnt != 1)  {
							  $subdepid = 1;
						   }
						}
				}
				if ($subdepid!=1) {
				   $discipline->subdepartmentid = $subdepid;
				}
			}

			if (!$DB->record_exists_select('bsu_discipline_subdepartment', "yearid=$nextyid and disciplineid=$discipline->disciplineid"))    {
				if (empty($discipline->subdepartmentid)) 	{
					$discipline->subdepartmentid = 1;
				} else {
				   $discipline->subdepartmentid = $newsubdeps[$discipline->subdepartmentid];
				}
				if (empty($discipline->subdepartmentid) || !isset($discipline->subdepartmentid)) 	{
					$discipline->subdepartmentid = 1;
				}
				$discipline->yearid= $nextyid;
				$DB->insert_record('bsu_discipline_subdepartment', $discipline);
				// print_object($discipline);
			}  else {
				// notify ('Already!');
			}
		}
	}
}    
 
 
 
function table_variative_discipline($yid, $fid, $action, $sym = 1)    
{
	global $DB;

	$select = '';

	if($fid > 0) {
		$select = "p.departmentcode = $fid";
	}
	if($action == 'report2_all') {
		$select = "p.departmentcode > 10000";
	}

	$sql = "CREATE TEMPORARY TABLE temp_bsu_plan_terms
			SELECT distinct p.id as planid, (2*($yid - SUBSTRING(rg.name, 5, 2)) - 1) as term1, 2*($yid - SUBSTRING(rg.name, 5, 2)) as term2
			FROM mdl_bsu_plan p
			inner join mdl_bsu_plan_groups pg on p.id=pg.planid
			inner join mdl_bsu_ref_groups rg on rg.id=pg.groupid
			where $select and p.notusing=0 and p.deleted=0 and rg.yearid=$yid
			order by planid";
	// print $sql;
	$DB->Execute($sql);

	if ($sym == 1)  {
		$strselectsym = "and (d.cyclename like '%В%' or d.cyclename like '%ФТД%')";
	} else if ($sym == 2)  {
		$strselectsym = "and (d.cyclename like '%ДВ%' or d.cyclename like '%ФТД%')";
	} else if ($sym == 3)  {
		$strselectsym = "and d.cyclename like '%ФТД%'";
	} else {
		$strselectsym = "and (d.cyclename like '%В%' or d.cyclename like '%ФТД%')";
	}

	$sql = "CREATE TEMPORARY TABLE temp_bsu_plan_disciplines
			SELECT distinct d.id, pt.planid, ds.subdepartmentid, d.disciplinenameid, d.cyclename, d.mustlearning,
					d.semestrexamen, d.semestrzachet, s.numsemestr, s.lection, s.praktika, s.lab
			FROM temp_bsu_plan_terms pt
			inner join mdl_bsu_discipline d using(planid)
			inner join mdl_bsu_discipline_semestr s on d.id=s.disciplineid
			left join mdl_bsu_discipline_subdepartment ds on d.id=ds.disciplineid
			where s.numsemestr in (pt.term1, pt.term2) and d.notusing=0 and (ds.yearid=$yid or ds.yearid is null) $strselectsym";
	$DB->Execute($sql);

	$sql = "update temp_bsu_plan_disciplines set subdepartmentid = 1 where subdepartmentid is null";
	$DB->Execute($sql);

	$sql = "CREATE TEMPORARY TABLE temp_bsu_prikaz
			SELECT pd.id, pd.subdepartmentid, rd.name as fakultet, rs.name as subdepartmentname,
				   pd.disciplinenameid, n.Name as disciplinename, pd.cyclename,
				   pd.planid, bp.name as planname, bts.kodspecyal as codespecyal, bts.specyal, btk.Kvalif as kvalifname,
				   bto.otdelenie, bp.specialityid, bp.profileid, rp.name as profilename, bp.kvalif, bp.edformid,
				   pd.mustlearning, pd.semestrexamen, pd.semestrzachet, pd.numsemestr, pd.lection, pd.praktika, pd.lab
			from temp_bsu_plan_disciplines pd
			inner join mdl_bsu_plan bp on bp.id=pd.planid
			INNER JOIN mdl_bsu_tsspecyal bts ON bp.specialityid=bts.idspecyal
			INNER JOIN mdl_bsu_tskvalifspec btk ON bp.kvalif=btk.idkvalif
			INNER JOIN mdl_bsu_tsotdelenie bto ON bp.edformid=bto.idotdelenie
			INNER JOIN mdl_bsu_ref_disciplinename n ON n.Id=pd.disciplinenameid
			inner join mdl_bsu_ref_department rd on rd.departmentcode=bp.departmentcode
			LEFT JOIN mdl_bsu_ref_profiles rp on rp.id=bp.profileid
			left join mdl_bsu_vw_ref_subdepartments rs on pd.subdepartmentid=rs.id";
	$DB->Execute($sql);

	$table = new html_table();
	// $table->head = array('Код факультета',''Факультет-заказчик','Специальность в системе','Специальность/направление','Курс в след. уч.году','Название планов в системе','Дисциплины','Кафедры');
	$table->head = array('Институт/факультет', 'Id плана',  'Направление подготовки, специальность', 'Квалификация', 'Профиль',
						 'Форма обучения', 'Наименование дисциплин', 'Цикл', 'Семестр',
						 'Кол-во часов <br />всего по уч.плану', 'Лек.', 'Прак.', 'Лаб.', 'Форма контроля', 'Кафедра');
	$table->align = array ('left', 'center', 'left', 'left', 'left', 'center', 'left', 'left',
							'center', 'center', 'center', 'center', 'center', 'center', 'left');
	$table->columnwidth = array (40, 10, 40, 17, 50, 11, 50, 10, 10, 10, 5, 5, 5, 10, 40);
	$table->titlesrows = array(20); // , 20,20, 20,20, 20);
	$table->titles = array();
	$table->titlesalign = 'center';
	$table->titles[] = 'О закреплении дисциплин по выбору учебных планов 20' . $DB->get_field_select('bsu_ref_edyear', 'edyear', "id=$yid") . ' учебного года';
	$table->downloadfilename = "disciplines_variative_{$fid}";
	$table->worksheetname = $table->downloadfilename;
	$table->attributes['class'] = 'plantable';

	$sql = "SELECT concat (id, '_', subdepartmentid) as id0, fakultet, subdepartmentname,
				  codespecyal, specyal, kvalifname, profilename, otdelenie, disciplinename,  cyclename, planid, planname,
				  mustlearning, semestrexamen, semestrzachet, numsemestr, lection, praktika, lab
			FROM temp_bsu_prikaz
			group by subdepartmentid, specialityid, profileid, edformid, kvalif, disciplinenameid
			order by subdepartmentid"; // subdepartmentname

	if ($datas = $DB->get_records_sql($sql))  {
		foreach ($datas as $discipline) 	{
			/*
			$planname = $discipline->codespecyal;
			$planname .=  ' ' . mb_substr($discipline->specyal, 6);
			if (!empty($discipline->profilename))  {
				$planname .= ' (' . $discipline->profilename . ') ';
			}
			$planname .=  ', ' . $discipline->otdelenie;
			*/
			$fk = array();
			$h = dechex($discipline->numsemestr);
			// echo $h;
			$pos = strpos($discipline->semestrexamen, $h);
			if ($pos === false) {
				$h = strtoupper($h);
				$pos = strpos($discipline->semestrexamen, $h);
				if ($pos === false) {
				} else {
					$fk[] = 'экзамен';
				}

			} else {
				$fk[] = 'экзамен';
			}
			$pos = strpos($discipline->semestrzachet, "$h");
			if ($pos === false) {
				$h = strtoupper($h);
				$pos = strpos($discipline->semestrzachet, "$h");
				if ($pos === false) {
				} else {
					$fk[] = 'зачет';
				}
			} else {
				$fk[] = 'зачет';
			}

			$strformkontrol = implode(',', $fk);

			$table->data[] = array ($discipline->fakultet, $discipline->planid, $discipline->specyal, $discipline->kvalifname, $discipline->profilename,
								$discipline->otdelenie, $discipline->disciplinename, $discipline->cyclename,
								$discipline->numsemestr, $discipline->mustlearning, $discipline->lection,
								$discipline->praktika, $discipline->lab, $strformkontrol,
								$discipline->subdepartmentname);
		}
	}

	return $table;
}
 
 

function listbox_symbol_variative($scriptname, $sym, $fid = 0)
{
	global $CFG, $OUTPUT, $DB;

	$symmenu = array(1 => 'В, ДВ и ФТД', 2 => 'ДВ и ФТД', 3 => 'ФТД');
	echo '<tr align="left"> <td align=right>Символы циклов: </td><td align="left">';
	echo $OUTPUT->single_select($scriptname, 'sym', $symmenu, $sym, null, 'switchsymform');
	echo '</td></tr>';

	return $sym;
}
 
?>