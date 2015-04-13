<?PHP // $Id: prakticecharge.php, v 1.1 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");
    require_once("../../bsu_charge/lib_charge.php");
    require_once("../../bsu_charge/lib_charge_spec.php");
    require_once("../lib_report.php");  

    $yid = optional_param('yid', 0, PARAM_INT);					// current year
    $fid = optional_param('fid', 0, PARAM_INT);	
    $planid = optional_param('pid', 0, PARAM_INT);      // plan id    
    $term = optional_param('term', 1, PARAM_INT);
	$prakid = optional_param('prid', 0, PARAM_INT);			// Discipline id (courseid)
    $eid = optional_param('edformid', 1, PARAM_INT);					// edformid
    $sid = optional_param('sid', 0, PARAM_INT);			        // id subdepartment    
    $action = optional_param('action', '', PARAM_ACTION);		// action
    $tab = optional_param('tab', 'setteacher', PARAM_ACTION);		// action
    $edworkid = optional_param('edworkid', 0, PARAM_INT);
    
    $copy_in_term =0;
    
    require_login();
    if ($yid == 0)   {
        $yid = get_current_edyearid(true);
        // $yid++;
    }

	$scriptname = basename($_SERVER['PHP_SELF']);	// echo '<hr>'.basename(me());
	$arrscriptname = explode('.', $scriptname);
    // $scriptname = $arrscriptname[0];
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strscript = get_string($arrscriptname[0], 'block_bsu_charge');
    
    $strsearch         = get_string("search");
    $strsearchresults  = get_string("searchresults");
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title('Практики и доп. виды работ');
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->navbar->add($strtitle, new moodle_url("$CFG->BSU_PLAN/index.php", array()));
    $PAGE->navbar->add('Практики и доп. виды работ');
    echo $OUTPUT->header();

    $scriptname = 'speccharge.php';
	$strlistfaculties =  listbox_department($scriptname."?yid=$yid&eid=$eid&edworkid=$edworkid", $fid);
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	
    echo  '<form name="copy_in_term" method="post" action="speccharge.php">';
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;
    $eid = listbox_edform($scriptname."?yid=$yid&fid=$fid&edworkid=$edworkid", $eid, $fid);
    /*
    echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left">';
    echo '13/14';
    echo '</td></tr>';
    */
    if ($yid >= 15) {
        $ASPIRANTPLAN = array(3082, 3083, 3084, 3085);
        $currsubdeps   = $DB->get_records_sql_menu("SELECT id as id1, id as id2 FROM mdl_bsu_vw_ref_subdepartments where yearid=$yid");
        $perehodsubdep = $DB->get_records_sql_menu("SELECT id2, id FROM mdl_bsu_vw_ref_subdepartments where yearid=$yid and id2>0");
    } else {
        $ASPIRANTPLAN = array(2130, 2131, 2132, 2133, 2134);
    }   
    
    if($fid > 0 ) {// && $eid > 0)    {
        $context = get_context_instance(CONTEXT_FACULTY, $fid);
        $editcapability = has_capability('block/bsu_plan:editcurriculum', $context);
        
        if ($frm = data_submitted())   {
           if (isset($frm->copy_in_term)) {
              $copy_in_term = 2;
           }
        }
        
        list_box_year($scriptname."?fid=$fid&edformid=$eid&edworkid=$edworkid", $yid);
               
        list_box_all_edwork($scriptname."?yid=$yid&fid=$fid&edformid=$eid", $edworkid, $eid, $fid, $yid);
               
        echo '</table>';
        if($yid > 0)    {
            ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
            @set_time_limit(0);
            @ob_implicit_flush(true);
            @ob_end_flush();
            
            echo html_writer::table(allpractice_view($yid, $fid, $eid, $edworkid, $copy_in_term));
            
            if ($fid==19900) {
              echo  '<div align="center">';
              echo  '<input type="submit" name="copy_in_term" value="Скопировать все виды на следующий учебный год"></div>';
            }
            
        }     
   } else {
        echo '</table>';
   }
    echo  '</form>';
    echo $OUTPUT->footer();
   
   
function allpractice_view($yid, $fid, $eid, $edworkid = 0, $copy_in_term = 0)
{
    global $DB, $ACCESS_USER, $ASPIRANTPLAN;
    
    $time0 = time();
    $select = "timestart<$time0 and timeend>$time0 and LOCATE('$yid', editplan)>0";
    $ACCESS_USER = $DB->get_records_select_menu('bsu_operator_dean', $select, null, '', 'id, userid');
       
        $table = new html_table();
        $table->width = '100%' ;
        $table->head  = array('№', 'План', 'Cостояние');
        $table->rowclasses = array();
        $table->data = array();
        
        if ($eid > 1)   {
            $select = "AND edformid=$eid";
        } else {
            $select = ''; 
        }
       // $str_plans = array();
        $textsqlplan = "SELECT id, name, lastshifr, specialityid, profileid, edformid, kvalif
                        FROM {bsu_plan}
                        WHERE (departmentcode = {$fid}) AND (deleted = 0) AND (notusing = 0) $select";
        if( $plans = $DB->get_records_sql($textsqlplan)) {
            // print_object($plans);
            foreach($plans AS $plan){
             //  $str_plans[]=$plan->id;
                $strgroups = get_plan_groups($plan->id);
                // echo $strgroups . '<br />';
                if ($strgroups != '')   {
                    $agroups = explode ('<br>', $strgroups);
                } else {
                    $agroups = array();
                    if (!in_array($plan->id, $ASPIRANTPLAN))  continue;
                }
                /*
                $terms = array();
                foreach ($agroups as $group)    {        
                    for($i=1; $i<=2; $i++)  {
                        $terms[] = get_term_group($yid, $group, $i);
                    }
                }      
                
                $terms = array_unique($terms);
                $maxtermgroup = max($terms);
                if ($maxtermpractice = $DB->get_field_sql("SELECT max(term) FROM mdl_bsu_plan_practice where planid=$plan->id"))    {
                    if ($maxtermpractice%2 == 1)    {
                        $maxtermpractice++;
                    } 
                    if ($maxtermgroup > $maxtermpractice) {
                        // notify ("$plan->id. $maxtermgroup > $maxtermpractice ");
                        continue;
                    }     
                }
                */
                                   
                $field0 = $plan->id;
                $field1 = '<b>Название плана :</b>'.$plan->name."<br>".
                          '<br /><b>Шифр :</b>'.$plan->lastshifr."<br>".
                          '<br /><b>Специальность :</b>'.withoutchairs_speciality($plan->specialityid)."<br>".
                          '<br /><b>Профиль :</b>'.withoutchairs_profiles($plan->profileid)."<br>".
                          '<br /><b>Форма обучения :</b>'.withoutchairs_edform($plan->edformid)."<br>".
                          '<br /><b>Квалификация :</b>'.withoutchairs_kvalif($plan->kvalif).
                          '<br /><b>Группы :</b><br />'.$strgroups;
                if ($edworkid==0||$edworkid==13) $table1 = table_practice($yid, $fid, $plan, $agroups, $copy_in_term);               
                 
                 $table2 = table_specvidrabot($yid, $fid, $plan, $agroups, $edworkid, $copy_in_term);
                 
                 $field2 =  '-';
                 if (isset($table1->data) && !empty($table1->data)) {
                    $field2 =  html_writer::table($table1) . '<br />';   
                 }
                   
                 if (isset($table2->data) && !empty($table2->data)) {
                    $field2 .=   html_writer::table($table2);   
                 }  
                 
                if ($field2!='-') {       
                  $table->data[] =   array($field0, $field1, $field2);
                }
                
                //$table->data[] =   array('', '',  );
            }
           // print implode(",",$str_plans);
        }

        return $table;
}   


?>