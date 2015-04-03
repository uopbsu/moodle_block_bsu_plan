<?PHP // $Id: prakticecharge.php, v 1.1 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");
    require_once("../../bsu_charge/lib_charge.php");
    require_once("../../bsu_charge/lib_charge_spec.php");
    require_once("../lib_report.php");
    // require_once("lib.php");  

    $yid = optional_param('yid', 0, PARAM_INT);					// current year
    $fid = optional_param('fid', 0, PARAM_INT);	
    $planid = optional_param('pid', 0, PARAM_INT);      // plan id    
    $term = optional_param('term', 1, PARAM_INT);
	$prakid = optional_param('prid', 0, PARAM_INT);			// Discipline id (courseid)
	$eid = optional_param('eid', 0, PARAM_INT);			// edworkkindid
    $sid = optional_param('sid', 0, PARAM_INT);			        // id subdepartment    
    $action = optional_param('action', '', PARAM_ACTION);		// action
    $tab = optional_param('tab', 'setteacher', PARAM_ACTION);		// action

    require_login();
    if ($yid == 0)   {
        $yid = get_current_edyearid();
        // $yid++;
    }

	$scriptname = basename($_SERVER['PHP_SELF']);	// echo '<hr>'.basename(me());
	$arrscriptname = explode('.', $scriptname);
    // $scriptname = $arrscriptname[0];
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strscript = 'Отчет по иностранному языку';// get_string($arrscriptname[0], 'block_bsu_charge');
    
    $strsearch         = get_string("search");
    $strsearchresults  = get_string("searchresults");
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strscript);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->navbar->add($strtitle, new moodle_url("$CFG->BSU_PLAN/index.php", array()));
    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

    $scriptname = 'specreport.php';
	$strlistfaculties =  listbox_department($scriptname, $fid);
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;
        echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left">';
    echo '13/14';
    echo '</td></tr>';
    echo '</table>';

    if($fid > 0)    {
        
        ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
        @set_time_limit(0);
        @ob_implicit_flush(true);
        @ob_end_flush();
        
        echo '<center>'. html_writer::table(specreport_view($yid, $fid)) . '</center>'; 
   }
   
    echo $OUTPUT->footer();
   
   
function specreport_view($yid, $fid)
{
    global $DB;

        $table = new html_table();
        // $table->width = '100%' ;
        $table->head  = array('№', 'План', 'Cостояние');
        // $table->rowclasses = array();
        $table->align = array ('left', 'left', 'left');
        $table->size = array ('5%', '40%', '70%');
        $table->data = array();
        
        $textsqlplan = "SELECT id, name, lastshifr, specialityid, profileid, edformid, kvalif
                        FROM {bsu_plan}
                        WHERE (departmentcode = {$fid}) AND (deleted = 0) AND (notusing = 0) AND edformid = 2";
        if( $plans = $DB->get_records_sql($textsqlplan)) {
            foreach($plans AS $plan){
                
                // if ($plan->edformid != 2) continue;
                
                $strgroups = get_plan_groups($plan->id);
                if ($strgroups != '')   {
                    $agroups = explode ('<br>', $strgroups);
                } else {
                    $agroups = array();
                }   
                $field0 = $plan->id;
                /*
                $field1 = '<b>Название плана :</b>'.$plan->name."<br>".
                          '<br /><b>Шифр :</b>'.$plan->lastshifr."<br>".
                          '<br /><b>Специальность :</b>'.withoutchairs_speciality($plan->specialityid)."<br>".
                          '<br /><b>Профиль :</b>'.withoutchairs_profiles($plan->profileid)."<br>".
                          '<br /><b>Форма обучения :</b>'.withoutchairs_edform($plan->edformid)."<br>".
                          '<br /><b>Квалификация :</b>'.withoutchairs_kvalif($plan->kvalif).
                          '<br /><b>Группы :</b><br />'.$strgroups;
                */
                $field1 = withoutchairs_speciality($plan->specialityid);
                $field1 .= ', ' . withoutchairs_profiles($plan->profileid);
                $field1 .= ', ' . withoutchairs_kvalif($plan->kvalif);
                $field1 .= ', ' . withoutchairs_edform($plan->edformid);
          
                $table1 = table_injaz($yid, $fid, $plan, $agroups);               
                 
                 $field2 =  '-';
                 if (isset($table1->data) && !empty($table1->data)) {
                    $field2 =  html_writer::table($table1) . '<br />';   
                 }
                   
         
                $table->data[] =   array($field0, $field1, $field2);
                
                //$table->data[] =   array('', '',  );
            }
        }

        return $table;
}   



function table_injaz($yid, $fid, $plan, $agroups)
{
    global $CFG, $DB, $OUTPUT, $USER;  
      
    $planid = $plan->id;
    $table = new html_table();
    $table->head = array (get_string('cyclename', 'block_bsu_plan'),
                          get_string('identificatordiscipline', 'block_bsu_plan'),
                          get_string('discipline', 'block_bsu_plan'));
    $table->align = array ('left', 'left', 'left');
    // $table->width = "70%";

    $numweeks = array();
    if ($grafiks = $DB->get_records_select('bsu_plan_grafikuchprocess', "planid = $planid")) {
        foreach ($grafiks as $grafik)   {
            $i1 = $grafik->numkurs * 2 - 1;
            $i2 = $grafik->numkurs * 2;
            $numweeks[$i1] = $grafik->numweekspring; 
            $numweeks[$i2] = $grafik->numweekautumn;
        }
    }


    $disciplinenameids = array(494, 10459, 505, 504, 3062, 10231, 10233, 10685, 1732, 9506, 9554, 483, 343, 9553, 7463, 10727, 9663, 5328, 12028, 7441, 12482, 4273, 9583, 5265, 14, 3188, 8832, 10129, 10123, 8378, 10755, 10675, 9590, 11879, 9948, 3335, 13502, 10900, 5264, 11246, 8381, 10220, 10221, 10222, 7649, 7650, 10902, 11932, 13042, 7519, 8328, 12708, 13199, 8326, 13202, 13135, 13203, 6723, 3685, 11405, 13133, 7669, 13204, 9885, 8798, 13082, 13087, 13124, 13109, 2564, 9011, 1874, 11126, 10876, 12024, 13132, 3500, 13316, 13317, 13084, 11712, 10943, 12084, 11766, 13101, 13251, 9304, 5950, 9381, 7318, 9486, 10702, 7470, 7468, 7467, 7990, 3754, 10295, 2558, 6041, 7471, 7469, 9628, 10310, 8291, 8290, 11994, 11995, 3589, 9382, 2974, 11281, 5961, 7314, 7703, 7460, 7313, 8649, 5362, 7379, 7378, 1923, 7380, 9383, 2905, 11933, 7269, 8182, 503, 11888, 3314, 12819, 12755, 5143, 11966, 484, 7478, 456, 7476, 461, 12574, 3867, 4139, 5919, 10684, 491, 7483, 7481, 2993, 5810, 9502, 9503, 3317, 9592, 3318, 9596, 9595, 10704, 1922, 7464, 7479, 7477, 5989, 7917, 482, 2079, 333, 2562, 4228, 3316, 9598, 9597, 7993, 7994, 8292, 7014, 12047, 11026, 501, 10100, 5440, 10715, 2903, 12520, 11993, 9632, 2904, 6951, 9303, 6046, 6839, 13444, 8647, 481, 500, 11024, 9302, 325, 5267, 10297, 9273, 5301, 5960, 9647, 4686, 4093, 7392, 7482, 457, 7480, 8845, 8197, 8967, 8968, 7609);
    
    $select = implode (',', $disciplinenameids); 
    
    $sql = "SELECT s.id as sdid, d.id as did, s.numsemestr, d.disciplinenameid, n.name as nname, d.cyclename, 
                d.identificatordiscipline, d.semestrexamen, d.semestrzachet, d.semestrkursovik,
                d.semestrdiffzach, d.mustlearning, p.id as pid, s.lection, s.praktika, s.lab
                FROM mdl_bsu_discipline_semestr s
                INNER JOIN mdl_bsu_discipline d ON d.id=s.disciplineid 
                INNER JOIN mdl_bsu_plan p ON p.id=d.planid
                INNER JOIN mdl_bsu_ref_disciplinename n ON n.id=d.disciplinenameid
                WHERE d.planid=$planid and d.disciplinenameid in ($select) and d.notusing=0
                ORDER BY d.cyclename";
    //            ORDER BY s.numsemestr, n.name";
      // echo $sql; 
    $terms =array();
    
    if ($datas = $DB->get_records_sql($sql))  {
        
        foreach ($datas as $discipline) {
            $terms[] = $discipline->numsemestr;
        } 
        
        $terms = array_unique($terms);
        sort($terms);
        
        $itogo = array();
        $hoursweek = array();
        foreach ($terms as $term) {
            if (isset($numweeks[$term])) {
                $nw = $numweeks[$term];
            } else {
                $nw = 0;    
            }
            $table->head[] = $term . '/' .$nw;
            $table->align[] = 'center';
            $itogo[$term] = 0;
            $hoursweek[$term] = 0; 
        }
        

        $tableitogo = array('', '', 'Всего');
        $tableweek = array('', '', 'Часов в нед.');
                
        foreach ($datas as $discipline) {
            $tabledata = array($discipline->cyclename, $discipline->identificatordiscipline, $discipline->nname);
            foreach ($terms as $term) {
                $sum = 0;
                if ($discipline->numsemestr == $term)   {
                    $sum = $discipline->lection + $discipline->praktika + $discipline->lab;
                    $tabledata[] = $sum;
                } else {
                    $tabledata[] = '-';
                }
                $itogo[$term] += $sum;  
            }
            $table->data[] = $tabledata;    
        }
        
        foreach ($terms as $term) {
            
            $tableitogo[] = $itogo[$term];
            if ($numweeks[$term] >  0) {
                $tableweek[] = round ($itogo[$term]/$numweeks[$term], 2);
            } else {
                $tableweek[] = '-';
            }     
        }    
        $table->data[] = $tableitogo;
        $table->data[] = $tableweek;
    }
    
    
    return $table;    
}        
?>