<?php   // by zagorodnyuk 5/09/2012

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");  
    require_once("../../bsu_charge/lib_charge.php"); 
    require_once("../../bsu_charge/lib_charge_spec.php");
    require_once("../lib_report.php");
    // require_once("lib.php");      

    $yid = optional_param('yid', 0, PARAM_INT);			// ed yearid
    // $fid = optional_param('fidall', 0, PARAM_INT);					// faculty id
    $fid = optional_param('fid', 0, PARAM_INT);					// faculty id
    $pid = optional_param('pid', 0, PARAM_INT);                 // plan id
    $eid = optional_param('edformid', 2, PARAM_INT);
    $spid = optional_param('spid', 0, PARAM_TEXT);                 // svodni plan id
    $level = optional_param('level', 'r', PARAM_TEXT);					// discipline  id
    $term = optional_param('term', 1, PARAM_INT);
    $action = optional_param('action', '', PARAM_ACTION);		// action
    
    if ($yid == 0)   {
        $yid = get_current_edyearid(true);
        // $yid++;
    }
    
    require_login();
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);

    
    if($action == 'excel') {
        switch ($level) {
            case 's':
                    if ($spid > 0)   {
                        $planids = get_planids_from_specialityid_edformid_kvalif_profileid($fid);
                        if(isset($planids[$spid]))  {
                            $planids = explode (',', $planids[$spid]);
                            
                            // сортируем планы по номеру группы
                            $aplansids = array();
                            foreach ($planids as $planid)  {
                                $strgroups = get_plan_groups($planid);
                                $agroups = explode ('<br>', $strgroups);
                                foreach ($agroups as $agroup)   {
                                     $aplansids[$agroup] = $planid;
                                }
                            } 
                            ksort($aplansids);
                            $planids = array();
                            foreach ($aplansids as $group => $planid) {
                                $planids[] = $planid;
                            }
                            $planids = array_unique($planids);
                            // print_object($planids);
         
                            // выводим планы в подтаблице       
                            $plannames = '<table class="generalbox" border=1 cellspacing="0" cellpadding="0">';
                            
                            $agroups = array();
                            
                            foreach ($planids as $planid)  {
                                $plan = $DB->get_record_select('bsu_plan', "id = $planid", null, 'id, name as pname, specialityid, profileid, edformid, kvalif');
                                $plannames .= '<tr> <td align=left>'.$plan->id.'. </td>';
                                $plannames .= '<td align=left>'.$plan->pname.'<br />'.' </td>';
                                $bgroups = get_plan_groups_with_count_stud($planid);
                                $plannames .= ' <td align=left>'.implode(',', $bgroups).'</td>';
                                $cgroups = array();
                                foreach ($bgroups as $bgroup)   {
                                    $agroups[] = substr($bgroup, 0, 6);
                                    $cgroups[] = substr($bgroup, 0, 6);
                                    $aplansids[$bgroup] = $planid;
                                }      
                                $cterms = get_unique_terms_groups($yid, $cgroups);
                                /*
                                foreach ($cterms as $i => $v)  {
                                    $cterms[$i] .= ' семестр';
                                } 
                                */ 
                                $plannames .= ' <td align=left>'.implode (',', $cterms).' семестры</td>';
                                $plannames .= '</tr>'; 
                            }
                            $plannames .= '</table>';
                            /*
                            echo '<tr> <td align=right>'.get_string('curriculums', 'block_bsu_plan').': </td><td>';
                            echo '<b>'.$plannames.'</b>'; // $plan->scode . '. ' .
                            echo '</td></tr>';
                            */                            
                                                      
                            $table = table_svodplan_report2($yid, $fid, $planids, $agroups, 1);
                            $lastcol = 0;
                            
                            $title = get_fullname_svod_plan($spid);
                            $yearname = get_edyear_name($yid); 
                            $html = '<center><b>Дата печати: ' . date('d.m.Y G:i:s') . '</b></center>';
                            $html .= "Утверждаю<br>";
                            $html .= "Проректор по учебной работе и информатизации ___________________ Маматов А.В.";                   
                            $html .= "<center><h5>Рабочий учебный план по направлению " . $title . ' на ' . $yearname . ' уч. год</h5></center>';

                            $html .= print_svod_report($table);
                            $html .= "<br><br>";
                            $html .= "<p>Декан факультета _______________________</p>";
                            $html .= "<p>Начальник УОП _______________________ Немцев А.Н.</p>";
                            $html .= "<p>Исполнитель _______________________</p>";
                        }
                           
                    }
            break;
            case 'r':  case 'p':          
                    $plan = $DB->get_record_select('bsu_plan', "id = $pid", null, 'id, name as pname, specialityid, profileid, edformid, kvalif');                   
                    if ($specyal = $DB->get_record_select('bsu_tsspecyal', "idSpecyal = $plan->specialityid", null, 'Specyal  as sname, KodSpecyal as scode')) {
                        $sname = $specyal->sname;
                    } else {
                        $sname = 'СПЕЦИАЛЬНОСТЬ НЕ НАЙДЕНА';
                    }     
                    $plan->sname = $sname; 
                    $plan->profile = '-';
                    if (!$plan->profile = $DB->get_field('bsu_ref_profiles', 'name', array('id' => $plan->profileid))) {
                        $plan->profile = '-';
                    }                    
                    $plan->edform = $DB->get_field('bsu_tsotdelenie', 'Otdelenie', array('idOtdelenie' => $plan->edformid ));
                    $plan->kvalif = $DB->get_field('bsu_tskvalifspec', 'Kvalif', array('idKvalif' =>  $plan->kvalif));
                    $strgroups = get_plan_groups($pid);
                    if ($strgroups != '')   {
                        $agroups = explode ('<br>', $strgroups);  
                        $plan->strgroups = str_replace('<br>', ', ', $strgroups);                    
                    }
                    
                    if ($strgroups != '')   {
                        if ($level == 'r')  {
                            $table = table_plan_report($yid, $fid, $plan, $agroups);
                            $lastcol = 1;
                        } else  {
                            $table = table_plan_report2($yid, $fid, $plan, $agroups);
                            $lastcol = 0;
                        } 
                    }
    
            
            break;
            case 'f':
                    $table = report_plan_faculty($fid);
                    $lastcol = 0;
            break;
            case 'u':
                    $table = report_plan_university(); 
                    $lastcol = 0;       
            break;                
        }            
        // print_object($table);
        
        if ($level == 's')  {
            /*$header = '
                <html xmlns:v="urn:schemas-microsoft-com:vml"
                xmlns:o="urn:schemas-microsoft-com:office:office"
                xmlns:x="urn:schemas-microsoft-com:office:excel"
                xmlns="http://www.w3.org/TR/REC-html40">
                
                <head>
                <meta name="Excel Workbook Frameset">
                <meta http-equiv=Content-Type content="text/html; charset=utf-8">
                <meta name=ProgId content=Excel.Sheet>
                <meta name=Generator content="Microsoft Excel 14">
                </head>
                <body>'.$html.'</body></html>'.*/
    		    $fn = 'rup' . $fid.'-'.$spid.'.xls';
    			header("Content-type: application/vnd.ms-excell");
    			header("Content-Disposition: attachment; filename=\"{$fn}\"");
    			header("Expires: 0");
    			header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    			header("Pragma: public");
			print $html;
            exit();            
            
        } else {
            print_table_to_excel($table, $lastcol);
        }
        exit();
    }


    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = get_string('reports', 'block_bsu_plan');

    //$PAGE->set_button('$nbsp;');
    $PAGE->set_title($strtitle3);
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    // $PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3);
    echo $OUTPUT->header();

    include('tabs.php');        
    $scriptname = "reports.php";
    
    switch ($level) {
        case 'i':
        	$strlistfaculties =  listbox_department($scriptname."?level=$level", $fid);
           	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
            echo $strlistfaculties;
                echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left">';
            echo '13/14';
            echo '</td></tr>';
            echo '</table>';
            if($fid > 0)    {
                echo '<center>'. html_writer::table(specreport_view($yid, $fid)) . '</center>';
            }    
            echo $OUTPUT->footer();
            exit();
        break;
        case 's':
               	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
                // echo $strlistfaculties;
                // listbox_all_department($scriptname, $fidall);
                list_box_year($scriptname. "?level=$level", $yid); // ."?fid=$fid&pid=$pid&term=$term&level=$level"
                listbox_all_facultys($scriptname."?level=$level&yid=$yid", $fid);  
                if ($fid > 0)   {
                    listbox_edform($scriptname."?level=$level&yid=$yid&fid=$fid", $eid);

                    listbox_svod_plan($scriptname."?level=$level&yid=$yid&fid=$fid&term=$term&edformid=$eid", $fid, $spid, $eid);
                    if ($spid > 0)   {
                        $planids = get_planids_from_specialityid_edformid_kvalif_profileid($fid);
                        // print_object($planids);
                        if(isset($planids[$spid]))  {
                            $planids = explode (',', $planids[$spid]);
                            // print_object($planids);
                            // сортируем планы по номеру группы
                            $aplansids = array();
                            foreach ($planids as $planid)  {
                                $strgroups = get_plan_groups($planid);
                                $agroups = explode ('<br>', $strgroups);
                                foreach ($agroups as $agroup)   {
                                     $aplansids[$agroup] = $planid;
                                }
                            } 
                            ksort($aplansids);
                            $planids = array();
                            foreach ($aplansids as $group => $planid) {
                                $planids[] = $planid;
                            }
                            $planids = array_unique($planids);
                            // print_object($planids);
         
                            // выводим планы в подтаблице       
                            $plannames = '<table class="generalbox" border=1 cellspacing="0" cellpadding="0">';
                            
                            $agroups = array();
                            
                            foreach ($planids as $index => $planid)  {
                                // print $planid . '<br />';
                                $plan = $DB->get_record_select('bsu_plan', "id = $planid", null, 'id, name as pname, specialityid, profileid, edformid, kvalif');                              
                                $bgroups = get_plan_groups_with_count_stud($planid);
                                // print_object($bgroups);
                                if (empty($bgroups)) continue;
                                $cgroups = array();
                                foreach ($bgroups as $bgroup)   {
                                    $agroups[] = substr($bgroup, 0, 8);
                                    $cgroups[] = substr($bgroup, 0, 8);
                                    $aplansids[$bgroup] = $planid;
                                }      
                                $cterms = get_unique_terms_groups($yid, $cgroups);
                                /*
                                foreach ($cterms as $i => $v)  {
                                    $cterms[$i] .= ' семестр';
                                } 
                                */ 
                                $termsids = implode (',', $cterms);
                                $sql = "SELECT s.id as sdid
                                        FROM mdl_bsu_discipline_semestr s
                                        INNER JOIN mdl_bsu_discipline d ON d.id=s.disciplineid 
                                        INNER JOIN mdl_bsu_plan p ON p.id=d.planid                                        
                                        WHERE d.planid=$planid and s.numsemestr in ($termsids) and d.notusing=0";
        
                                if ($datas = $DB->get_records_sql($sql))  {
                                    $href = "disciplines.php?fid=$fid&pid=$planid";
                                    $plannames .= '<tr> <td align=left><a href="'.$href.'">'.$plan->id.'. </a></td>';
                                    $plannames .= '<td align=left><a href="'.$href.'">'.$plan->pname.'. </a><br />'.' </td>';
                                    $plannames .= ' <td align=left>'.implode(',', $bgroups).'</td>';
                                    $plannames .= ' <td align=left>'.$termsids.' семестры</td>';
                                } else {
                                    $href = "disciplines.php?fid=$fid&pid=$planid";
                                    $plannames .= '<tr> <td align=left><a href="'.$href.'">'.$plan->id.'. </a></td>';
                                    // $plannames .= '<tr> <td align=left>'.$plan->id.'. </td>';
                                    $plannames .= '<td align=left><a href="'.$href.'">'.$plan->pname.'. </a><br />'.' </td>';
                                    $plannames .= ' <td align=left>'.implode(',', $bgroups).'</td>';
                                    $plannames .= ' <td align=left>'.$termsids.' семестры</td>';
                                    
                                    $planids[$index] = 0;
                                    
                                }   
                                $plannames .= '</tr>'; 
                            }
                            $plannames .= '</table>';
                            echo '<tr> <td align=right>'.get_string('curriculums', 'block_bsu_plan').': </td><td>';
                            echo '<b>'.$plannames.'</b>'; // $plan->scode . '. ' .
                            echo '</td></tr>';
                            
                            $table = table_svodplan_report2($yid, $fid, $planids, $agroups, 0);
                     }
                           
                    }
                }
                echo '</table>';
                if (!empty($table)) {
                    $options = array('action'=> 'excel', 'level' => $level, 'yid' => $yid, 'fid' => $fid, 'edformid' => $eid,
                                    'pid' => $pid, 'spid' => $spid, 'term' => $term, 'sesskey' => sesskey());
                    echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center><br /><br />';
                    
                    $title = get_fullname_svod_plan($spid);
                    $yearname = get_edyear_name($yid);                    
                    echo '<center><h3>Рабочий учебный план по направлению ' . $title . ' на ' . $yearname . ' уч. год</h3></center>';
                    echo  print_svod_report($table);
                }    

        break;                

        case 'r': case 'p':
               	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
                // echo $strlistfaculties;
                // listbox_all_department($scriptname, $fidall);
                listbox_all_facultys($scriptname."?level=$level", $fid);  
                if ($fid > 0)   {

                    listbox_plan($scriptname."?fid=$fid&term=$term&level=$level", $fid, $pid);
                    if ($pid > 0)   {
                        if($plan = $DB->get_record_select('bsu_plan', "id = $pid", null, 'id, name as pname, specialityid, profileid, edformid, kvalif')) {
                            
                            if ($specyal = $DB->get_record_select('bsu_tsspecyal', "idSpecyal = $plan->specialityid", null, 'Specyal  as sname, KodSpecyal as scode')) {
                                $sname = $specyal->sname;
                            } else {
                                $sname = 'СПЕЦИАЛЬНОСТЬ НЕ НАЙДЕНА';
                            }     
                            $plan->sname = $sname; 
                            
                            echo '<tr> <td align=right>'.get_string('speciality', 'block_bsu_plan').': </td><td>';
                            echo '<b>'.$plan->sname.'</b>'; // $plan->scode . '. ' .
                            echo '</td></tr>';

                            if ($plan->profileid > 0) {
                                echo '<tr> <td align=right>'.get_string('profile', 'block_bsu_plan').': </td><td>';
                                $plan->profile = '-';
                                if (!$plan->profile = $DB->get_field('bsu_ref_profiles', 'name', array('id' => $plan->profileid))) {
                                    $plan->profile = '-';
                                }
                                echo '<b>'.$plan->profile.'</b>'; // $plan->scode . '. ' .
                                echo '</td></tr>';
                            }

                            echo '<tr> <td align=right>'.get_string('edform', 'block_bsu_plan').': </td><td>';
                            $plan->edform = $DB->get_field('bsu_tsotdelenie', 'Otdelenie', array('idOtdelenie' => $plan->edformid ));
                            echo '<b>'.$plan->edform.'</b>'; // $plan->scode . '. ' .
                            echo '</td></tr>';
                            
                            echo '<tr> <td align=right>'.get_string('kvalif', 'block_bsu_plan').': </td><td>';
                            $plan->kvalif = $DB->get_field('bsu_tskvalifspec', 'Kvalif', array('idKvalif' =>  $plan->kvalif));
                            echo '<b>'.$plan->kvalif.'</b>'; // $plan->scode . '. ' .
                            echo '</td></tr>';
                            
                            $bgroups = get_plan_groups_with_count_stud($pid);
                            echo '</td><tr> <td align=right>'.get_string('groups', 'block_bsu_plan').': </td><td>';
                            $plan->strgroups = implode(',', $bgroups);
                            echo '<b>'.$plan->strgroups.'</b>';
                            echo '</td></tr>'; 

                            list_box_year($scriptname."?fid=$fid&pid=$pid&term=$term&level=$level", $yid);
                            
                            $strgroups = get_plan_groups($pid);
                            if ($strgroups != '')   {
                                $agroups = explode ('<br>', $strgroups);
                                if ($level == 'r')
                                   $table = table_plan_report($yid, $fid, $plan, $agroups);
                                else  
                                   $table = table_plan_report2($yid, $fid, $plan, $agroups); 
                            } else {
                                notify ('К данному рабочему учебному плану не привязана ни одна группа.');
                            }  
                            
                        }
                    }
                } 
                echo '</table>';
        break;        
        case 'f':
               	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
                // echo $strlistfaculties;
                $fname = $DB->get_field_select('bsu_ref_department', 'name', "departmentcode = $fid");
                // $fid = $f->departmentcode;
		 	    $ret =  '<tr><td align=right>'.get_string('faculty', 'block_bsu_plan').':</td><td>';
  			    $ret .=  "<b>$fname</b>";
  			    $ret .= '</td></tr>';
                echo $ret;
                echo '</table>';
                if ($fid > 0)   {
                    $table = report_plan_faculty($fid);
                }        
        break;
        case 'u':
                $table = report_plan_university();        
        break;                
    }    

    if (!empty($table)) {
        
        if ($level != 's') echo'<center>'.html_writer::table($table).'</center>';
        $options = array('action'=> 'excel', 'level' => $level, 'yid' => $yid, 'fid' => $fid,
                          'pid' => $pid, 'spid' => $spid, 'term' => $term, 'sesskey' => sesskey());
        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center>';
    }

    echo $OUTPUT->footer();


function table_svodplan_report2($yid, $fid, $planids, $agroups, $excel=0)
{
    global $CFG, $DB, $OUTPUT, $USER;

    // echo '<hr>'; print_object($planids);
    
    $table = new html_table();
    
    $table->countstud = array();
    $table->cntstudbud = array(); 
    $table->cntstudk = array();
    $table->termcntstudbud = array(); 
    $table->termcntstudk = array();
    $table->terms = array();
    $table->numweeks = array();
    
    $atermsids = array();
    $plans = array();
    
    foreach ($planids as $planid)   {
        
        $numweeks = array();
        if ($grafiks = $DB->get_records_select('bsu_plan_grafikuchprocess', "planid = $planid")) {
            foreach ($grafiks as $grafik)   {
                $i1 = $grafik->numkurs * 2 - 1;
                $i2 = $grafik->numkurs * 2;
                $numweeks[$i1] = $grafik->numweekautumn; 
                $numweeks[$i2] = $grafik->numweekspring;
            }
        }

        
        $strgroups = get_plan_groups($planid);
        $agroups = explode ('<br>', $strgroups);    
        $atermsids1 = get_unique_terms_groups($yid, $agroups);
        $termsids = implode (',', $atermsids1);
        
        foreach ($atermsids1 as $t)    {
            $table->numweeks[$t]  = $numweeks[$t];  
        }
        
        // подсчет общего количества студентов
        foreach ($agroups as $agroup)   {            
            if ($group = $DB->get_record_select('bsu_ref_groups', "name = '$agroup'"))  {
                // print_object($group);
                $table->countstud[$group->id] = $group->countstud;
                $table->cntstudbud[$group->id] = $group->cntstudbud;
                $table->cntstudk[$group->id] = $group->cntstudk;
                $table->terms[$group->id] = array();
                for($i=1; $i<=2; $i++)  {
                    $table->terms[$group->id][] = get_term_group($yid, $group->name, $i);
                }  
                foreach ($table->terms[$group->id] as $t)   {
                    $table->termcntstudbud[$t] = 0;
                    $table->termcntstudk[$t] = 0;
                }
            }    
        }

        // подсчет количества студентов по семестрам
        foreach ($table->terms as $grid => $groups)   {
            foreach ($groups as  $t) {
                $table->termcntstudbud[$t] = 0;
                $table->termcntstudk[$t] = 0;
            }
        }
        
        foreach ($table->terms as $grid => $groups)   {
            foreach ($groups as  $t) {
                $table->termcntstudbud[$t] += $table->cntstudbud[$grid];
                $table->termcntstudk[$t] += $table->cntstudk[$grid];
            }
        }
        
        
        // print_object($table);
                 
        $sql = "SELECT s.id as sdid, d.id as did, s.numsemestr, d.disciplinenameid, n.name as nname, d.cyclename, 
                    d.identificatordiscipline, d.semestrexamen, d.semestrzachet, d.semestrkursovik, d.semestrkp,
                    d.semestrdiffzach, d.mustlearning, p.id as pid, s.lection, s.praktika, s.lab
                    FROM mdl_bsu_discipline_semestr s
                    INNER JOIN mdl_bsu_discipline d ON d.id=s.disciplineid 
                    INNER JOIN mdl_bsu_plan p ON p.id=d.planid
                    INNER JOIN mdl_bsu_ref_disciplinename n ON n.id=d.disciplinenameid
                    WHERE d.planid=$planid and s.numsemestr in ($termsids) and d.notusing=0
                    ORDER BY d.identificatordiscipline, d.cyclename";
        //            ORDER BY s.numsemestr, n.name";
          // echo $sql; 
        $disciplines =array();
    
        if ($datas = $DB->get_records_sql($sql))  {
            
            foreach ($atermsids1 as $termsid) {
                $atermsids[] = $termsid;    
            }
          
            foreach($datas as $data) {
                if (empty($data->identificatordiscipline))  {
                    $cyclename[$data->disciplinenameid] = $data->cyclename;
                } else {
                    $cyclename[$data->disciplinenameid] = $data->identificatordiscipline;
                }    
                // $discnameids[] = $data->disciplinenameid;
                if(!isset($disciplines[$data->disciplinenameid])) {
                    $disciplines[$data->disciplinenameid] = new stdClass();
                    $disciplines[$data->disciplinenameid]->did = $data->did;
                    $disciplines[$data->disciplinenameid]->cyclename = $data->cyclename;
                    $disciplines[$data->disciplinenameid]->nname = $data->nname; 
                    $disciplines[$data->disciplinenameid]->subdep = '-';
                    $disciplines[$data->disciplinenameid]->subdepids = array(); 
                    /*
                    if ($subdepid = $DB->get_field_select('bsu_discipline_subdepartment', 'subdepartmentid', "yearid=$yid AND disciplineid=$data->did"))    {
                       $disciplines[$data->disciplinenameid]->subdep = $DB->get_field_select('bsu_vw_ref_subdepartments', 'Name', "Id = $subdepid");
                    } 
                    */
                    $s = array();
                    if ($subdeps = $DB->get_records_select('bsu_discipline_subdepartment', "yearid = $yid AND disciplineid = $data->did"))  {
                        foreach ($subdeps as $subdep)   {
                            $disciplines[$data->disciplinenameid]->subdepids[] = $subdep->subdepartmentid;
                            $subdepname = $DB->get_field_select('bsu_vw_ref_subdepartments', 'Name', "Id = $subdep->subdepartmentid");
                            $href =  $CFG->wwwroot. "/blocks/bsu_charge/subdepcharge.php?fid=0&&yid=$yid&tab=1&sid={$subdep->subdepartmentid}";
                            if (!$excel)    {
                                $s[] = "<a href = \"$href\"> $subdepname </a>";
                            } else {
                                $s[] = $subdepname; 
                            }    
                        }    
                        $disciplines[$data->disciplinenameid]->subdep = implode(', ', $s);
                    }
                    
                    if ($data->semestrdiffzach != '0')  {
                        $data->semestrzachet .= $data->semestrdiffzach; 
                    }    
                         
                    if ($data->semestrexamen != '0') {
                        $disciplines[$data->disciplinenameid]->semestrexamen = $data->semestrexamen; 
                    } else {
                        $disciplines[$data->disciplinenameid]->semestrexamen = '';
                    }
                    if ($data->semestrzachet != '0') {
                        $disciplines[$data->disciplinenameid]->semestrzachet = $data->semestrzachet;
                    } else {
                        $disciplines[$data->disciplinenameid]->semestrzachet = '';
                    }
                    if ($data->semestrkursovik != '0') {
                        $disciplines[$data->disciplinenameid]->semestrkursovik = $data->semestrkursovik;
                    } else {
                        $disciplines[$data->disciplinenameid]->semestrkursovik = '';
                    }
                    if ($data->semestrkp != '0') {
                        $disciplines[$data->disciplinenameid]->semestrkp = $data->semestrkp;
                    } else {
                        $disciplines[$data->disciplinenameid]->semestrkp = '';
                    }                   
                }
                
                if(!isset($disciplines[$data->disciplinenameid]->lection)) {
                    $disciplines[$data->disciplinenameid]->lection = array();
                    $disciplines[$data->disciplinenameid]->praktika = array();
                    $disciplines[$data->disciplinenameid]->lab = array();
                }     
                
                $disciplines[$data->disciplinenameid]->lection[$data->numsemestr] =  $data->lection;
                $disciplines[$data->disciplinenameid]->praktika[$data->numsemestr] = $data->praktika;
                $disciplines[$data->disciplinenameid]->lab[$data->numsemestr] = $data->lab;
                /*
                $lstream = get_list_streams_for_edworkkind($yid, $planid, $data, 1);
                if ($lstream != '') {
                    $disciplines[$data->disciplinenameid]->lection[$data->numsemestr] .= '<br><small>'.$lstream.'</small>';
                }    
                $prstream = get_list_streams_for_edworkkind($yid, $planid, $data, 3);
                if ($prstream != '') {
                    $disciplines[$data->disciplinenameid]->praktika[$data->numsemestr] .= '<br><small>'.$prstream.'</small>';
                }    
                
                $labstream = get_list_streams_for_edworkkind($yid, $planid, $data, 2);
                if ($labstream != '') {
                    $disciplines[$data->disciplinenameid]->lab[$data->numsemestr] .= '<br><small>'.$labstream.'</small>';
                } 
                */   
                                
            }
            // print_object($disciplines);
        }
        
        $plans[$planid] = new stdClass();
        $plans[$planid]->disciplines = $disciplines;
    } // plansids    
    
    // print_object($cyclename);
    asort($cyclename);
    
    // $discnameids = array();
    // foreach ()
    // print_object($cyclename);
    // $discnameids = array_unique($discnameids);
    // print_object($discnameids);
    
    /*
    $terms = get_terms_group($yid, $agroups);
    
    $atermsids = array();
    foreach ($terms as $term)   {
        foreach ($term as $t)   {
            $atermsids[] = $t;
        }    
    } 
    */
    
    $atermsids = array_unique($atermsids);
    sort($atermsids);


    $planid = current($planids);

    $table->head = get_tablehead_for_report($atermsids); 
    $table->align = array ('center', 'left', 'left', 'left', 'left', 'center', 'center', 'center'); 
    $table->columnwidth = array (7, 14, 50, 60, 14, 6, 6, 6);
    $table->atermsids = $atermsids;
                           // 'center', 'center', 'center', 'center', 'center', 'center');
    $i=0;
    foreach ($table->head as $h)    {
        if ($i>7) {            
            $table->align[] = 'center';
            $table->columnwidth[] = 14;
        }    
        $i++;
    }                       
                           
    $table->width = "80%";
    
	// $table->class = 'userenrolment';
    // $table->attributes['class'] = 'userenrolment';

   	$table->titlesrows = array(20); // , 20,20, 20,20, 20);
    $table->titles = array();
    $table->titlesalign = 'left';
    $table->titles[] = get_string('curriculum', 'block_bsu_plan'); 
    /*
	$table->titles[] = get_string('speciality', 'block_bsu_plan') . ' ' . $plan->sname;
    if (isset($plan->profile))  {
        $table->titles[] = get_string('profile', 'block_bsu_plan'). ' ' . $plan->profile;
    }
    
    $table->titles[] = get_string('edform', 'block_bsu_plan') . ' ' .  $plan->edform;
    $table->titles[] = get_string('kvalif', 'block_bsu_plan') . ' ' . $plan->kvalif;
    $table->titles[] = get_string('groups', 'block_bsu_plan'). ' ' . $plan->strgroups;
*/    
    $table->downloadfilename = "plan_{$fid}_{$planid}";
    $table->worksheetname = $table->downloadfilename;
    
    $table->itogoaudhours = array();
    $table->itogoexamen = array();
    $table->itogozachet = array();
    $table->itogokurs = array();
    foreach ($atermsids as $aterm)  {
        $table->itogoaudhours[$aterm] = 0;
        $table->itogoexamen[$aterm] = 0;
        $table->itogozachet[$aterm] = 0;
        $table->itogokurs[$aterm] = 0;
    }    
        
  

    $h = 0;
    // foreach ($discnameids as $discnameid)   {
    foreach($cyclename as $discnameid => $v)  {
        
        $cyclename = array();
        $nname = array();
        $subdeps = array();
        $subdepsids = array();
        $semestrexamen = array();
        $semestrzachet = array();
        $semestrkursovik = array();
        $lection = array();
        $praktika = array();
        $lab = array();
        foreach ($atermsids as $aterm)  {
            $lection[$aterm] = array();
            $praktika[$aterm] = array();
            $lab[$aterm] = array();
            $clection[$aterm] = array();
            $cpraktika[$aterm] = array();
            $clab[$aterm] = array();
        }    
            
        foreach ($planids as $planid)   {
            if (isset($plans[$planid]->disciplines[$discnameid]))   {
                $discipline = $plans[$planid]->disciplines[$discnameid];
                
                $cyclename[] = $discipline->cyclename;
                $nname[] = $discipline->nname;
                if ($discipline->subdep != '-')  {
                    $subdeps[] = $discipline->subdep;
                    foreach ($discipline->subdepids as $_sid)    {
                        $subdepsids[] = $_sid;
                    }    
                }         
                /*
                $semestrexamen[] = implode(',', str_split($discipline->semestrexamen));
                $semestrzachet[] = implode(',', str_split($discipline->semestrzachet));
                $semestrkursovik[] = implode(',', str_split($discipline->semestrkursovik));
                */
                $examens = get_active_form_kontrol($discipline->semestrexamen, $atermsids);
                $semestrexamen[] = implode(',', $examens);

                $zachet = get_active_form_kontrol($discipline->semestrzachet, $atermsids);
                $semestrzachet[] = implode(',', $zachet);

                $kursovik = get_active_form_kontrol($discipline->semestrkursovik, $atermsids);
                $kp = get_active_form_kontrol($discipline->semestrkp, $atermsids);
                $allkursoviki = $kursovik + $kp;  
                $semestrkursovik[] = implode(',', $allkursoviki);
                
                 
                foreach ($atermsids as $aterm)  {
                    /*                    
                    if (in_array($aterm, $examens))  {  
                        $table->itogoexamen[$aterm]++;
                    }    

                    if (in_array($aterm, $zachet))  {  
                        $table->itogozachet[$aterm]++;
                    }    

                    if (in_array($aterm, $kursovik))  {  
                        $table->itogokurs[$aterm]++;
                    } 
                    */   
                    
                    if (isset($discipline->lection[$aterm]))    {
                        if ($discipline->lection[$aterm]>0) {
                            $lection[$aterm][] = $discipline->lection[$aterm];
                            $sql = "SELECT sum(bem.hours) as hours 
                                    FROM {bsu_edwork_mask} bem 
                                    WHERE yearid = $yid AND planid=$planid AND disciplinenameid=$discnameid AND term=$aterm AND edworkkindid=1"; // subdepartmentid 
                            if ($edm = $DB->get_record_sql($sql))   {
                                if (empty($edm->hours))  {
                                    $hours = 0;// 'п';    
                                } else {
                                    $hours = $edm->hours;
                                }     
                            }
                            if (isset($subdepsids[0])) {
                                $_sid = $subdepsids[0];
                            }
                            $href =  $CFG->wwwroot. "/blocks/bsu_charge/disciplinecharge.php?fid=0&pid=$planid&yid=$yid&did=$discipline->did&sid=$_sid";
                            $clection[$aterm][] = "<a href = \"$href\"> $hours </a>"; 
                        }    
                        //else $lection[$aterm][] = '';    
                        if ($discipline->praktika[$aterm]>0) {
                            $praktika[$aterm][] = $discipline->praktika[$aterm];
                            $sql = "SELECT sum(bem.hours) as hours 
                                    FROM {bsu_edwork_mask} bem 
                                    WHERE yearid = $yid AND planid=$planid AND disciplinenameid=$discnameid AND term=$aterm AND edworkkindid=3"; // subdepartmentid 
                            if ($edm = $DB->get_record_sql($sql))   {
                                if (empty($edm->hours))  {
                                    $hours = 0;// 'п';    
                                } else {
                                    $hours = $edm->hours;
                                }     
                            } 
                            if (isset($subdepsids[0])) {
                                $_sid = $subdepsids[0];
                            }
                            $href =  $CFG->wwwroot. "/blocks/bsu_charge/disciplinecharge.php?fid=0&pid=$planid&yid=$yid&did=$discipline->did&sid=$_sid";
                            $cpraktika[$aterm][] = "<a href = \"$href\"> $hours </a>"; 
                        }
                            
                        //else $praktika[$aterm][] = ''; 
                        if ($discipline->lab[$aterm]>0) {
                            $lab[$aterm][] = $discipline->lab[$aterm];
                            $sql = "SELECT sum(bem.hours) as hours 
                                    FROM {bsu_edwork_mask} bem 
                                    WHERE yearid = $yid AND planid=$planid AND disciplinenameid=$discnameid AND term=$aterm AND edworkkindid=2"; // subdepartmentid 
                            if ($edm = $DB->get_record_sql($sql))   {
                                if (empty($edm->hours))  {
                                    $hours = 0;// 'п';    
                                } else {
                                    $hours = $edm->hours;
                                }     
                            } 
                            if (isset($subdepsids[0])) {
                                $_sid = $subdepsids[0];
                            }
                            $href =  $CFG->wwwroot. "/blocks/bsu_charge/disciplinecharge.php?fid=0&pid=$planid&yid=$yid&did=$discipline->did&sid=$_sid";
                            $clab[$aterm][] = "<a href = \"$href\"> $hours </a>"; 
                            
                        }    
                        //else $lab[$aterm][] = ''; 
                    } else {
                        /*
                        $lection[$aterm][] = ''; 
                        $praktika[$aterm][] = '';
                        $lab[$aterm][] = '';
                        */
                    }    
                }   
            }    
        }   
        
        $cyclename = array_unique($cyclename);
        $nname = array_unique($nname);
        $subdeps = array_unique($subdeps);
        $semestrexamen = array_unique($semestrexamen);
        $semestrzachet = array_unique($semestrzachet);
        $semestrkursovik = array_unique($semestrkursovik);
    
        foreach ($semestrexamen as $t)  {
            $ats = explode(',', $t);
            foreach ($ats as $tt) if ($tt > 0) $table->itogoexamen[$tt]++;    
        }
        foreach ($semestrzachet as $t)  {
            $ats = explode(',', $t);
            foreach ($ats as $tt) if ($tt > 0) $table->itogozachet[$tt]++;   
        }
        foreach ($semestrkursovik as $t)  {
            $ats = explode(',', $t);
            foreach ($ats as $tt) if ($tt > 0) $table->itogokurs[$tt]++;
        }
        
        $tabledata = array();
        $tabledata[] = implode(',<br />', $cyclename);
        $tabledata[] = implode(',<br />', $nname);
        $tabledata[] = implode(',<br />', $subdeps);
        $tabledata[] = implode(',<br />', $semestrexamen);
        $tabledata[] = implode(',<br />', $semestrzachet);
        $tabledata[] = implode(',<br />', $semestrkursovik);
        foreach ($atermsids as $aterm)  {
            $lection[$aterm] = array_unique($lection[$aterm]);
            $praktika[$aterm] = array_unique($praktika[$aterm]);
            $lab[$aterm] = array_unique($lab[$aterm]);
            
            // echo $v . '<br />';
            $pos = mb_strpos($v, 'ФТД', 0, 'UTF-8');
            if ($discnameid != 51 && $pos === false)  {
                $table->itogoaudhours[$aterm] += reset($lection[$aterm]);            
                $table->itogoaudhours[$aterm] += reset($praktika[$aterm]);
                $table->itogoaudhours[$aterm] += reset($lab[$aterm]);
            }    
            
            $td1 = implode(',<br />', $lection[$aterm]); 
            if (!$excel)    {
                if (!empty($clection[$aterm]))   {
                    $clection[$aterm] = array_unique($clection[$aterm]);
                    $td1 .= '<br />('. implode(',<br />', $clection[$aterm]) .')' ;
                }    
            }
            $tabledata[] = $td1;

            $td2 = implode(',<br />', $praktika[$aterm]); 
            if (!$excel)    {
                if (!empty($cpraktika[$aterm]))   {
                    $cpraktika[$aterm] = array_unique($cpraktika[$aterm]);
                    $td2 .= '<br />('. implode(',<br />', $cpraktika[$aterm]) .')' ;
                }    
            }
            $tabledata[] = $td2;
            
            $td3 = implode(',<br />', $lab[$aterm]); 
            if (!$excel)    {
                if (!empty($clab[$aterm]))   {
                    $clab[$aterm] = array_unique($clab[$aterm]);
                    $td3 .= '<br />('. implode(',<br />', $clab[$aterm]) .')' ;
                }    
            }
            $tabledata[] = $td3;
        }    


               
        $table->data[] = $tabledata;  
        $h++;            

        
        // planids
     /*
        if ($h > 7) {
            $h = 0; 
            $table->data[] = get_tablehead_for_report($atermsids, true);    
        }
     */
    }
    // print_object($table);
    return $table;
   
}

function print_svod_report($table, $excel = 0)
{
    $border = 'border:1px solid #000000;';
    if($excel) 
         //  $border = 'border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;';
        $border = ''; // 'border-width: thin;';// 'border:thin solid windowtext;';
        
    $html = '<table align="center">
                <tr style="position: absolute:1000%">
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;width:50pt" rowspan="3">Всего студентов</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;width:600pt" colspan="2">В том числе</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center" colspan="3" >Распределение по семестрам</td>';
    foreach ($table->atermsids as $aterm)  { 
       $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center" colspan="3" >'. $aterm . ' семестр</td>';
    }
                    
    $html .=   '</tr> <tr>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;width:300pt" rowspan="2">Бюджет</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;width:300pt" rowspan="2">Договор</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;width:40pt;mso-rotate:90;writing-mode:tb-rl" rowspan="4">Экзамен</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;width:60pt;mso-rotate:90;writing-mode:tb-rl" rowspan="4">Зачет</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;width:20pt;mso-rotate:90;writing-mode:tb-rl" rowspan="4">Курс.раб.</td>';
    foreach ($table->atermsids as $aterm)  { 
       $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" colspan="2" >Недель</td>';
       $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" >'.$table->numweeks[$aterm].'</td>';
    }
    $html .=   '</tr><tr>';
    
    foreach ($table->atermsids as $aterm)  {
       if ($aterm%2 == 1) {
            $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" colspan="2" >Бюджет</td>';
            if (isset($table->termcntstudbud[$aterm]))  {
                $cntstud = $table->termcntstudbud[$aterm];
            }  else  $cntstud = 0;
            $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left">'.$cntstud.'</td>';
       } else {
            $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" colspan="2" >Договор</td>';
            if (isset($table->termcntstudk[$aterm]))  {
                $cntstud = $table->termcntstudk[$aterm];
            }  else  $cntstud = 0;
            $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left">'.$cntstud.'</td>';
        
       }     
    }
    
    $countstud = 0;
    foreach ($table->countstud as $cnt) {
        $countstud += $cnt;     
    } 
    $cntstudbud = 0;
    foreach ($table->cntstudbud as $cnt) {
        $cntstudbud += $cnt;     
    } 
    $cntstudk = 0;
    foreach ($table->cntstudk as $cnt) {
        $cntstudk += $cnt;     
    } 

    $html .=   '</tr><tr>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">'.$countstud.'</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">'.$cntstudbud.'</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">'.$cntstudk.'</td>  
    ';
    
    foreach ($table->atermsids as $aterm)  {
       if ($aterm%2 == 1) {
            $cntgr = 0;
            foreach ($table->terms as $groupterms)  {
                if (in_array($aterm, $groupterms)) $cntgr++;   
            }
            $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" colspan="2" >Групп</td>';
            $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" colspan="4">'.$cntgr.'</td>';
       } 
    }
    $html .=   '</tr><tr>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">Циклы</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;width:100pt">Наименование дисциплин</td>
                    <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;width:100pt">Кафедры</td>  
    ';

    foreach ($table->atermsids as $aterm)  { 
       $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;mso-rotate:90;writing-mode:tb-rl;width:30pt" >Лекции</td>';
       $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;mso-rotate:90;writing-mode:tb-rl;width:30pt" >Практич.</td>';
       $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center;mso-rotate:90;writing-mode:tb-rl;width:30pt" >Лаб.</td>';
    }
    $html .=   '</tr><tr>';
    
    for ($i=1; $i<=6; $i++) {
        $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center" >'.$i.'</td>';
    } 
    
    foreach ($table->atermsids as $aterm)  { 
        for ($j=1; $j<=3; $j++) {
            $html .= '<td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center" >'.(++$i).'</td>';
        }    
    } 
    $html .=   '</tr>';
    
    foreach ($table->data as $tabledata)    {
        $html .=   '<tr>'; 
        foreach ($tabledata as $i => $td) {
            if ($i <= 2) $align = 'left';
            else  $align = 'center';
            $html .= '<td bgcolor="#ffffff" style="'.$border."vertical-align:center;text-align:$align\" >".$td.'</td>';    
        }   
        $html .=   '</tr>'; 
    }
    
    $html .= '<tr>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" colspan=3>Всего аудиторных часов в неделю (без физкультуры и ФТД)</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">&nbsp;</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">&nbsp;</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">&nbsp;</td>
              ';
    foreach ($table->itogoaudhours as $aterm => $td) {
        if ($table->numweeks[$aterm]  != 0) {
            $avg = round($td/$table->numweeks[$aterm], 0); 
        } else {
            $avg = '-';
        }
        $html .= '<td bgcolor="#ffffff" style="'.$border."vertical-align:center;text-align:right\" colspan=3>".$avg.'</td>';    
    }   
    $html .=   '</tr>'; 
  

    $cntfk = 0;
    foreach ($table->itogoexamen as  $td) {
        $cntfk += $td;
    }    
    $html .= '<tr>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" colspan=3>Экзаменов</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">'.$cntfk.'</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">&nbsp;</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">&nbsp;</td>
              ';
    foreach ($table->itogoexamen as $aterm => $td) {
        $html .= '<td bgcolor="#ffffff" style="'.$border."vertical-align:center;text-align:right\" colspan=3>".$td.'</td>';    
    }   
    $html .=   '</tr>'; 

    $cntfk = 0;
    foreach ($table->itogozachet as  $td) {
        $cntfk += $td;
    }    
    $html .= '<tr>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" colspan=3>Зачетов</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">&nbsp;</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">'.$cntfk.'</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">&nbsp;</td>
              ';
    foreach ($table->itogozachet as $aterm => $td) {
        $html .= '<td bgcolor="#ffffff" style="'.$border."vertical-align:center;text-align:right\" colspan=3>".$td.'</td>';    
    }   
    $html .=   '</tr>'; 

    $cntfk = 0;
    foreach ($table->itogokurs as  $td) {
        $cntfk += $td;
    }    
    $html .= '<tr>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:left" colspan=3>Курсовых работ</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">&nbsp;</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">&nbsp;</td>
              <td bgcolor="#ffffff" style="'.$border.'vertical-align:center;text-align:center">'.$cntfk.'</td>
              ';
    foreach ($table->itogokurs as $aterm => $td) {
        $html .= '<td bgcolor="#ffffff" style="'.$border."vertical-align:center;text-align:right\" colspan=3>".$td.'</td>';    
    }   
    $html .=   '</tr>'; 
  
  
  
    $html .=   '</table>';  
    
    return $html; 
}

function get_fullname_svod_plan($index)
{
    global $DB;
    
    $ids = explode ('_', $index);
    $specyal = $DB->get_record_select('bsu_tsspecyal', "idSpecyal=$ids[0]", null, 'idSpecyal, Specyal, KodSpecyal');
    $name = $specyal->specyal;
    $kod  = trim($specyal->kodspecyal); 
    $len = strlen($kod);
    // $kod = mb_substr($name, 0, 6);
    if ($len == 6)  {
        // $kvalif = $DB->get_field_select('bsu_tskvalifspec', 'Kvalif', "idKvalif={$ids[2]}");
        $kvalifkod = $DB->get_field_select('bsu_tskvalifspec', 'KvalifKod', "idKvalif={$ids[2]}");
        
        $nname = mb_substr($name, $len);
        // $name = $kod . '.' . substr ($kvalif[$ids[2]], 0, 2) . ' ' . trim($nname);
        $name = $kod . '.' . $kvalifkod . ' ' . trim($nname);
    }     
    
    if ($ids[3] > 0)    {
        if ($ids[2] == 4)   {
            $profilename = 'магистерская программа';
        } else {
            $profilename = 'профиль';
        }
        $name .= ', ' . $profilename . ' ' . $DB->get_field_select('bsu_ref_profiles', 'name', "id=$ids[3]");
    } 
    $edform = $DB->get_field_select('bsu_tsotdelenie', 'otdelenie', "idotdelenie={$ids[1]}");
    $name .= ' (' . $edform . ' форма обучения)';
    
    return $name;
}


function get_active_form_kontrol($formkontrol, $atermsids)
{
    $newsemestrs = array();    
    
    if (empty($formkontrol)) return $newsemestrs;
    
    $semestrs = str_split($formkontrol);
    foreach ($semestrs as $i => $v) {
        $semestrs[$i] = hexdec($v);
    }

    foreach ($semestrs as $semestr) {
        if (in_array($semestr, $atermsids)) {
            $newsemestrs[] = $semestr; 
        }
    }
    
    return $newsemestrs; 
}

?>