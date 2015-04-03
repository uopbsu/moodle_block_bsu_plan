<?PHP // $Id: synonym.php,v 1.2 2012/04/06 09:51:45 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");

    $action = optional_param('action', '', PARAM_ALPHA);    // new, add, edit, update
    $yid = optional_param('yid', 0, PARAM_INT);			// ed yearid
    $fid = required_param('fid', PARAM_INT);
    $pid = optional_param('pid', 0, PARAM_INT);            // plan id    
    $term = optional_param('term', 1, PARAM_INT);
	$did = optional_param('did', 0, PARAM_INT);			// Discipline id (courseid)
    $tab = optional_param('tab', 1, PARAM_INT);
    $spid = optional_param('spid', 0, PARAM_INT);            // plan id    
    $sterm = optional_param('sterm', 0, PARAM_INT);
	$sdid = optional_param('sdid', 0, PARAM_INT);			// Discipline id (courseid)
    $id = optional_param('id', 0, PARAM_INT);			// Synonym id
    $plantab = optional_param('plantab', 'plan', PARAM_TEXT); 
    
    require_login();
    
    if ($yid == 0)   {
        $yid = get_current_edyearid();
    }

    $strscript = get_string('synonyms', 'block_bsu_plan');
	    
    // $redirlink = "disciplines.php?fid=$fid&pid=$pid&gid=$gid&term=$term";
    $redirlink = "synonym.php?fid=$fid&pid=$pid&term=$term&did=$did&tab=$tab&plantab=$plantab";
    
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
                                                                           'term' => $term, 'tab' => $plantab)));
    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

    if ($action == 'add')   {
        $condition = array('disciplineid' => $did, 's_planid' => $spid, 's_term' => $sterm, 's_disciplineid' => $sdid, 'yearid' => $yid);                        
        if (!$DB->record_exists('bsu_discipline_synonym', $condition))	{
            $discipline = $DB->get_record_select('bsu_discipline', "id = $did", null, 'id, disciplinenameid');
            $sdiscipline = $DB->get_record_select('bsu_discipline', "id = $sdid", null, 'id, disciplinenameid');      
            $rec = (object)$condition;
            $rec->planid = $pid;
            $rec->disciplinenameid = $discipline->disciplinenameid;
            $rec->term = $term;  
            $rec->s_disciplinenameid = $sdiscipline->disciplinenameid;
            $rec->yearid= $yid; 
	    	if (!$DB->insert_record('bsu_discipline_synonym', $rec)){
	    	    print_object($rec);
	    		print_error('Error in adding synonym! (rec)');
	    	}
            $rec2 = new stdClass();
            $rec2->planid = $spid;
            $rec2->disciplineid = $sdid;
            $rec2->disciplinenameid = $sdiscipline->disciplinenameid;
            $rec2->term = $sterm;   
            $rec2->s_planid = $pid;
            $rec2->s_disciplineid = $did;
            $rec2->s_disciplinenameid = $discipline->disciplinenameid;
            $rec2->s_term = $term;
            $rec2->yearid= $yid;
	    	if (!$DB->insert_record('bsu_discipline_synonym', $rec2)){
	    	    print_object($rec2);  
	    		print_error('Error in adding synonym (rec2)!');
	    	}
        }    
    } else if ($action == 'del')   {
        if ($synonym = $DB->get_record_select('bsu_discipline_synonym', "id = $id", null, 'id, disciplineid, s_disciplineid'))   {
            if ($DB->record_exists_select('bsu_schedule_mask', "disciplineid=$synonym->s_disciplineid"))	{
                echo $OUTPUT->notification('Для дисциплины-синонима уже создано расписание. Дисциплина-синоним не может быть удалена.');
            } else {
                $DB->delete_records_select('bsu_discipline_synonym', "id = $id");
                $DB->delete_records_select('bsu_discipline_synonym', "disciplineid = $synonym->s_disciplineid AND s_disciplineid = $synonym->disciplineid");
                echo $OUTPUT->notification('Дисциплина-синоним удалена.', 'notifysuccess');
            }
        }    
    } 
    
    $time0 = time();
    $select = "editplan=1 and timestart<$time0 and timeend>$time0";
    $ACCESS_USER = $DB->get_records_select_menu('bsu_operator_dean', $select, null, '', 'id, userid');
    
    
    $scriptname = "synonym.php";        
    $strlistfaculties =  listbox_department($scriptname, $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

    echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
    echo $strlistfaculties;

    $kp = false;
    if($fid > 0){
        listbox_plan($scriptname."?yid=$yid&plantab=$plantab&fid=$fid", $fid, $pid);
        if ($pid > 0)   {
            listbox_term($scriptname."?yid=$yid&plantab=$plantab&fid=$fid&pid=$pid", $fid, $pid, $term);
            if ($term > 0) {
                listbox_discipline($scriptname."?yid=$yid&plantab=$plantab&fid=$fid&pid=$pid&term=$term", $fid, $pid, $term, $did);
                if ($did > 0) {
                        $kp = true;
                }
            }
        }
     }
     echo '</table>';        

    if ($kp)    {
        $link = "yid=$yid&plantab=$plantab&fid=$fid&pid=$pid&term=$term&did=$did";
        $toprow[] = new tabobject('1', $scriptname.'?tab=1&'.$link, get_string('listsynonym', 'block_bsu_plan'));
    	$toprow[] = new tabobject('2', $scriptname.'?tab=2&'.$link, get_string('addsynonym', 'block_bsu_plan'));
    	$tabs = array($toprow);
        print_tabs($tabs, $tab, NULL, NULL);

        if ($tab == 1)  {
            $table = table_synonyms($fid, $pid, $term, $did);
            echo'<center>'.html_writer::table($table).'</center>';
        } else if ($tab == 2)   {
            if ((strpos($CFG->editplanopenedforyid, "$yid") !== false)|| is_siteadmin() || in_array($USER->id, $ACCESS_USER)) {
            } else {
                notify (get_string('accessdenied', 'block_bsu_plan'));
                echo $OUTPUT->footer();
                exit();
            }   
            
            $link .= "&tab=$tab";
        	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
            listbox_plan_synonym($scriptname. "?" .$link, $fid, $spid);
            if ($spid > 0)   {
                if ($sterm == 0) $sterm = $term;
                listbox_term_synonym($scriptname."?spid=$spid&".$link, $fid, $spid, $sterm);
                if ($sterm > 0) {
                    listbox_discipline_synonym($scriptname."?spid=$spid&sterm=$sterm&".$link, $fid, $spid, $sterm, $sdid);
                    if ($sdid > 0)  {
                        echo '</table><br>';
                        $options = array('yid' => $yid, 'fid' => $fid, 'plantab' => $plantab,
                                         'pid' => $pid, 'term' => $term, 'did' => $did,
                                         'spid' => $spid, 'sterm' => $sterm, 'sdid' => $sdid, 
                                         "action"=> "add");
                        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), 'Добавить дисциплину-синоним', 'get', $options).'</center>';

                    } else {
                       echo '</table>'; 
                    }
                }
            }        
          	echo '</table>';
        }      
    }
    
    echo $OUTPUT->footer();


// Display list plan as popup_form
function listbox_plan_synonym($scriptname, $fid, $pid)
{
  global $CFG, $OUTPUT, $DB;

  $planmenu = array();
  $planmenu[0] = get_string('selectcurriculum', 'block_bsu_plan').'...';

  if($allplans = $DB->get_records_sql("SELECT id, name FROM {bsu_plan}
                                      WHERE departmentcode=$fid
                                      ORDER BY id"))   {
		foreach ($allplans as $plan) 	{
            $planmenu[$plan->id] = $plan->id . '. ' . $plan->name;
		}
  }

  echo '<tr align="left"> <td align=right>'.get_string('curriculum', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'spid', $planmenu, $pid, null, 'switchplansyn');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}


function listbox_term_synonym($scriptname, $fid, $planid, $term)
{
	GLOBAL $DB, $OUTPUT;

    $termmenu = array();
   
	$sql = "SELECT 1 as i, max(b.numsemestr) as maxsem FROM {bsu_discipline} a
            inner join {bsu_discipline_semestr} b on a.id=b.disciplineid
            where a.planid=$planid";
    if ($max = $DB->get_records_sql($sql))  {
         $maxsem = $max[1]->maxsem;
    } else {
         $maxsem = 0;
    }

    $toprow = array();
    for ($i=1; $i<=$maxsem; $i++)   {
        $termmenu [$i] = $i;
    }

  echo '<tr align="left"> <td align=right>'.get_string('term', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'sterm', $termmenu , $term, null, 'switchtermsyn');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}


function listbox_discipline_synonym($scriptname, $fid, $planid, $term, $did)
{
  global $CFG, $OUTPUT, $DB;

  $planmenu = array();
  $planmenu[0] = get_string('selectdiscipline', 'block_bsu_plan').'...';

  $sql = "SELECT d.id as did, n.Name as nname, d.cyclename
            FROM {bsu_discipline_semestr} s
            INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
            INNER JOIN {bsu_plan} p ON p.id=d.planid
            INNER JOIN {bsu_ref_disciplinename} n ON n.Id=d.disciplinenameid
            WHERE p.id=$planid and s.numsemestr=$term
            ORDER BY n.Name";
   if ($datas = $DB->get_records_sql($sql))  {
		foreach ($datas as $plan) 	{
            $planmenu[$plan->did] = $plan->nname . ' (' . $plan->cyclename . ')';
		}
  }

  echo '<tr align="left"> <td align=right>'.get_string('discipline', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'sdid', $planmenu, $did, null, 'switchdiscsyn');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}


function table_synonyms($fid, $pid, $term, $did)
{
    global $CFG, $DB, $OUTPUT;
    
    $strsubgroup = '';
    $strshort = '';
    $title = get_string('delsynonym', 'block_bsu_plan');
    
    $table = new html_table();
    $table->head  = array (get_string('npp', 'block_bsu_plan'),
                          get_string('curriculum', 'block_bsu_plan'),
                           get_string('groups', 'block_bsu_plan'),
                          get_string('term', 'block_bsu_plan'),
                          get_string('discipline', 'block_bsu_plan'),
                           get_string('actions'));
   	$table->align = array ("center", "left", "center","center", "left", "center");
    // $table->class = 'moutable';
    // $table->width = '40%';
	// $table->size = array ('10%', '10%');

    $sql = "SELECT d.id, disciplineid, s_planid, s_disciplineid, s_disciplinenameid, s_term, n.name as nname, p.name as pname
            FROM {bsu_discipline_synonym} d
            INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.s_disciplinenameid
            INNER JOIN {bsu_plan} p ON p.id=d.s_planid
    	    WHERE disciplineid=$did";
    if ($sdisciplines = $DB->get_records_sql($sql)) {
        $i=0;      
        foreach ($sdisciplines as $sd)  {
            $link = "<a title=\"$title\" href=\"synonym.php?action=del&fid=$fid&pid=$pid&term=$term&did=$did&id=$sd->id\">";
            $link .= '<img src="'.$OUTPUT->pix_url('i/cross_red_small').'" alt="'. $title . '" /></a>';

            $table->data[] = array (++$i . '.', $sd->s_planid . '. ' . $sd->pname,  get_plan_groups($sd->s_planid), $sd->s_term, $sd->nname, $link);
        }
    } 
    
    return $table;    
}

?>