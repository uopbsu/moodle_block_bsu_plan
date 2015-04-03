<?PHP // $Id: listdiscnextyear.php,v 1.8 2011/10/20 12:29:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");

    $fid = optional_param('fid', 0, PARAM_INT);
    $yid = optional_param('yid', 0, PARAM_INT);	    // current year    
    $term = optional_param('term', 1, PARAM_INT);
	$cid = optional_param('cid', 0, PARAM_INT);			// Kurs number
	$eid = optional_param('eid', 0, PARAM_INT);			// edworkkindid
    $kid = optional_param('kid', 0, PARAM_INT);			        // id subdepartment
    $sid = optional_param('sid', 0, PARAM_INT);			        // id speciality
    $action = optional_param('action', '', PARAM_ACTION);		// action
    $tab = optional_param('tab', 'setteacher', PARAM_ACTION);		// action

    require_login();

    if ($yid == 0)   {
        $yid = get_current_edyearid();
    }
    
	$scriptname = basename($_SERVER['PHP_SELF']);	// echo '<hr>'.basename(me());
	$arrscriptname = explode('.', $scriptname);
    $scriptname = $arrscriptname[0];
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = get_string('disciplines', 'block_bsu_plan');
    $strscript = get_string($scriptname, 'block_bsu_plan');    
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strscript);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->navbar->add($strtitle,  new moodle_url("$CFG->BSU_PLAN/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

    $scriptname .= '.php';
	$strlistfaculties =  listbox_department($scriptname, $fid);
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	
    
    $kp = false;
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    list_box_year($scriptname, $yid);
    if ($yid > 0)   {
        echo $strlistfaculties;
        if($fid > 0)  {
            $kp = true;
        }
    }                 
    echo '</table>';
   
    if ($kp) {
        echo $OUTPUT->heading('Рабочие учебные планы, в которых отсутствуют дисциплины следующего учебного года.');  
        $table = table_discipline_next_year($yid, $fid);
        echo '<center>'.html_writer::table($table).'</center>';
    }

    echo $OUTPUT->footer();



function table_discipline_next_year($yid, $fid)
{
    global $CFG, $DB, $OUTPUT;
    
    /*
    $asubdepartments = array();
    $asubdepartmentcodes = array();
    if ($subdepartments = $DB->get_records('bsu_vw_ref_subdepartments'))    {
        // print_object($subdepartments);
        foreach ($subdepartments as $subdepartment) {
            $index0 = mb_substr($subdepartment->name, 0, 6);
            $index = (int)$index0;
            // echo $index . '<br>'; 
            $asubdepartments[$index] = $subdepartment->id;
            $asubdepartmentcodes[$subdepartment->id] = $subdepartment->name; // $index0;      
        } 
    } else {
        echo 'Not found!';
    }
    */

    $aspecyals = array();
    $aspecyalcodes = array();
    if ($specyals = $DB->get_records_select('bsu_tsspecyal', "KodSpecyal>0", null, '', 'idSpecyal, Specyal, KodSpecyal'))    {
        // print_object($subdepartments);
        foreach ($specyals as $specyal) {
            $index0 = mb_substr($specyal->kodspecyal, 0, 6);
            $index = (int)$index0;
            // echo $index . '<br>'; 
            $aspecyals[$index] = $specyal->idspecyal;
            $aspecyalcodes[$specyal->idspecyal] = $specyal->specyal; // $index0;      
        } 
    } else {
        echo 'Not found!';
    }
    
    $kvalif_ref = $DB->get_records_menu('bsu_tskvalifspec', null, '', 'idKvalif, Kvalif');
    $edform = $DB->get_records_select_menu('bsu_tsotdelenie', '', null, '', "idotdelenie, otdelenie");
    $max_semestr = array();
    $max_semestr['2_2'] = 8; // бакалавр очная
    $max_semestr['2_3'] = 8;
    $max_semestr['2_4'] = 8;
    $max_semestr['3_2'] = 10; // специалист очная
    $max_semestr['3_3'] = 12;
    $max_semestr['3_4'] = 12;
    $max_semestr['4_2'] = 12; // магистр очная
    $max_semestr['4_3'] = 12;
    $max_semestr['4_4'] = 12;
    
            
    $table = new html_table();  // get_string('ksrh', 'block_bsu_plan'),
    
    $table->head = array (get_string('group', 'block_bsu_plan'),
                          get_string('curriculum', 'block_bsu_plan'),
                          get_string('speciality', 'block_bsu_plan'),
                          get_string('kvalif', 'block_bsu_plan'),
                          get_string('edform', 'block_bsu_plan'),
                          get_string('term', 'block_bsu_plan'),
                          get_string('disciplines', 'block_bsu_plan')
                   );
    $table->align = array ('center', 'left', 'left', 'left', 'center', 'center', 'center');
    /*
    $table->width = "80%";
    $table->columnwidth = array (7, 10, 60, 10, 10, 10, 16, 15, 15, 15,15,15);
	// $table->class = 'userenrolment';
    // $table->attributes['class'] = 'userenrolment';

   	$table->titlesrows = array(30, 30);
    $table->titles = array();
    $table->titles[] = get_string('curriculum', 'block_bsu_plan') . ' ' . $plan->pname; 
	$table->titles[] = get_string('speciality', 'block_bsu_plan') . ' ' . $plan->sname;
    $table->downloadfilename = "discipline_{$fid}_{$planid}_{$term}";
    $table->worksheetname = $table->downloadfilename;
    */
 
    $alldisciplineids = array();
    if ($plans = $DB->get_records_select('bsu_plan', "departmentcode=$fid", null)) {
        foreach ($plans as $plan)   {
            // echo "<hr>";
            $strgroup = get_plan_groups($plan->id);
            if ($strgroup != '')    {
                $agroups = explode ('<br>', $strgroup);
                foreach ($agroups as $group)    {
                    // $table->data[] = array($group, $plan->name, $aspecyalcodes[$plan->specialityid], $kvalif_ref[$plan->kvalif], $edform[$plan->edformid]);
                    // echo "<strong>$group</strong><br>";
                    // $ids = get_disciplines_group_polug($yid, $polug, $plan->id, $group);
                    $notfound = array();
                    for($i=1; $i<=2; $i++)  {
                        $term = get_term_group($yid, $group, $i);
                        $term += 2;
                        $sql = "SELECT d.id as did, n.name as nname, d.cyclename   
                                    FROM {bsu_discipline_semestr} s
                                    INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
                                    INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                                    WHERE d.planid=$plan->id and s.numsemestr=$term
                                    order by n.name";
                        if ($disciplines = $DB->get_records_sql($sql))  {
                            /*
                            foreach ($disciplines as $discipline)   {
                                    $table->data[] = array('', '', '', '', '', $term, $discipline->nname, $discipline->cyclename);            
                            }
                            */
                        } else {
                            $index = $plan->kvalif . '_' . $plan->edformid;
                            // echo $index . '<br>'; 
                            $maxsrok = $max_semestr[$index];
                            if ($term <= $maxsrok)  {  
                                // $table->data[] = array('', '', '', '', '', $term, '<strong>дисциплины не найдены</strong>', '');
                                $notfound[$term] = '<strong>дисциплины не найдены</strong>';
                            }    
                        }
                    }
                    
                    if (!empty($notfound))  {

                        $strterm = $strerror = '';
                        foreach ($notfound as $term1 => $error)  {
                            $strterm .= $term1 . '<br>';
                            $strerror .= $error . '<br>';
                        }
                        $table->data[] = array($group, "<a href=\"..\curriculums\disciplines.php?fid=$fid&pid=$plan->id\">" . $plan->name . '</a>', 
                                                $aspecyalcodes[$plan->specialityid], $kvalif_ref[$plan->kvalif], 
                                                $edform[$plan->edformid], $strterm, $strerror);
                    }
                } 
             }
        }
   }                     

    // print_object($table);
    return $table;    
}
  
           
?>