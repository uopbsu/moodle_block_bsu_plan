<?php   

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");  
    require_once("../../bsu_charge/lib_charge.php"); 
    require_once("../../bsu_charge/lib_charge_spec.php");
    require_once("../lib_report.php");
    // require_once("lib.php");      

    $yid = optional_param('yid', 14, PARAM_INT);			// ed yearid
    // $fid = optional_param('fidall', 0, PARAM_INT);					// faculty id
    $fid = optional_param('fid', 0, PARAM_INT);					// faculty id
    $pid = optional_param('pid', 0, PARAM_INT);                 // plan id
    $eid = optional_param('edformid', 2, PARAM_INT);
    $sid = optional_param('sid', 0, PARAM_TEXT);                 // 
    $pfid = optional_param('pfid', 0, PARAM_TEXT);                 // 
    $kurs = optional_param('kurs', 1, PARAM_TEXT);                 // 
    $level = optional_param('level', 'r', PARAM_TEXT);					// discipline  id
    $term = optional_param('term', 1, PARAM_INT);
    $action = optional_param('action', '', PARAM_ACTION);		// action
    
    require_login();

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = get_string('reports', 'block_bsu_plan');
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);

    
    if($action == 'excel') {
        $plan = $DB->get_record_select('bsu_plan', "id = $pid", null, 'id, name as pname, specialityid, profileid, edformid, kvalif, lastshifr ');                   
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
        $plan->kurs = $kurs;
        if ($strgroups != '')   {
            $table = table_plan_report_forms($yid, $fid, $plan, $agroups);
            $lastcol = 2;
            print_table_to_excel($table, $lastcol);
        }

        exit();
    }

    //$PAGE->set_button('$nbsp;');
    $PAGE->set_title($strtitle3);
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    // $PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3);
    echo $OUTPUT->header();

    
    include('tabs.php');
        
    $scriptname = "report2.php";
    $context = get_context_instance(CONTEXT_SYSTEM);
    $editcapability_system = has_capability('block/bsu_plan:importplan', $context);
    $editcapability_faculty = false;    


    $fid2 = $fid;
    $strlistfaculties =  listbox_department($scriptname, $fid2);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	} else {
	   $editcapability_faculty = true;
	}
 	
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    // echo $strlistfaculties;
    // listbox_all_department($scriptname, $fidall);
    listbox_all_facultys($scriptname."?level=$level", $fid);  
    if ($fid > 0)   {
        $faculty = $DB->get_record_select('bsu_ref_department', "departmentcode = $fid", null, 'id, departmentcode, name, shortname');

        // listbox_plan($scriptname."?fid=$fid&term=$term&level=$level", $fid, $pid);
        // if ($pid > 0)   {
        $options = get_speciality_array($faculty->id);
        echo '<tr align="left"> <td align=right>'.get_string('speciality', 'block_bsu_plan').': </td><td align="left">';
        echo $OUTPUT->single_select($scriptname."?fid=$fid", 'sid', $options, $sid, null, 'switchspecyality');
        echo '</td></tr>';
        
        if ($sid > 0)   {
            $options = array();
            $options[0] = get_string('selectprofile', 'block_bsu_plan').'...';
            
            $sql = "select id, name from mdl_bsu_ref_profiles where id in (
                    SELECT distinct profileid FROM mdl_bsu_plan
                    where departmentcode = $fid AND profileid>0)";
            // if ($specialitys = $DB->get_records_select_menu ('bsu_ref_profiles', "idfaculty=$faculty->id AND name <> '-'", null, 'name', 'id, name'))   {
            if ($specialitys = $DB->get_records_sql_menu($sql)) {     
                $options  += $specialitys; 
            }
            echo '<tr align="left"> <td align=right>'.get_string('profile', 'block_bsu_plan').': </td><td align="left">';
            echo $OUTPUT->single_select($scriptname."?fid=$fid&sid=$sid", 'pfid', $options, $pfid, null, 'switchprofile');
            echo '</td></tr>';
            
            $options = array(1 => 1, 2, 3, 4, 5, 6, 7, 8);
            echo '<tr align="left"> <td align=right>Курс: </td><td align="left">';
            echo $OUTPUT->single_select($scriptname."?fid=$fid&sid=$sid&pfid=$pfid", 'kurs', $options, $kurs, null, 'switchkurs');
            echo '</td></tr>';
            
            list_box_year($scriptname."?fid=$fid&sid=$sid&pfid=$pfid&kurs=$kurs", $yid);
                        
        	$ayid = $DB->get_record_sql("SELECT god FROM {$CFG->prefix}bsu_ref_edyear WHERE id=$yid");
        	$byid = substr($ayid->god, 2, 2);
        	if($kurs != 1) $byid = $byid - $kurs + 1;
        	if(strlen($byid) == 1) $byid = '0'.$byid;
        	$like = $byid;
            // echo $like;
            
            listbox_plan_some($scriptname."?fid=$fid&sid=$sid&pfid=$pfid&kurs=$kurs", $fid, $sid, $pfid, $kurs, $pid, $like);
            
            if($plan = $DB->get_record_select('bsu_plan', "id = $pid", null, 'id, name as pname, specialityid, profileid, edformid, kvalif, lastshifr ')) {
                
                if ($specyal = $DB->get_record_select('bsu_tsspecyal', "idSpecyal = $plan->specialityid", null, 'Specyal  as sname, KodSpecyal as scode')) {
                    $sname = $specyal->sname;
                } else {
                    $sname = 'СПЕЦИАЛЬНОСТЬ НЕ НАЙДЕНА';
                }     
                $plan->sname = $sname; 
                /*                
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
                */

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
                 
                $plan->kurs = $kurs;
                
                $strgroups = get_plan_groups($pid);
                if ($strgroups != '')   {
                    $agroups = explode ('<br>', $strgroups);
                    $table = table_plan_report_forms($yid, $fid, $plan, $agroups);
                } else {
                    notify ('К данному рабочему учебному плану не привязана ни одна группа.');
                }  
            }
        }
    } 
    echo '</table>';

    if (!empty($table)) {
        $options = array('action'=> 'excel', 'level' => $level, 'yid' => $yid, 'fid' => $fid,
                          'pid' => $pid, 'spid' => $spid, 'term' => $term, 'sesskey' => sesskey());
        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center>';        
        if ($level != 's') echo'<center>'.html_writer::table($table).'</center>';
        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center>';
    }

    echo $OUTPUT->footer();



function table_plan_report_forms($yid, $fid, $plan, $agroups, $delemiter = '<br>')
{
    global $CFG, $DB, $OUTPUT, $USER;
    
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
    //echo $termsids . '<br />';
    
    $iterms = array();
    foreach ($terms as $group => $term) {
        $iterms[(int)$group] = $term;
    }
    // print_object($iterms);    echo '<hr>';
    
    $planid = $plan->id;
    $table = new html_table();
    $table->head = array ('Предмет', 
                          'Форма занятий', 
                          'Количество обучающихся<br /> на занятии', 
                          'Продолжительность<br />(мин.)',
                          'Тип необходимого помещения',  
                          'Семестр',
                          'Поток');
    $table->align = array ('left', 'left', 'center', 'center', 'left', 'center', 'left');
    $table->width = "80%";
    $table->columnwidth = array (50, 25, 18, 30, 16, 10);
	// $table->class = 'userenrolment';
    // $table->attributes['class'] = 'userenrolment';

   	$table->titlesrows = array(20, 20,20, 20,20, 20);
    $table->titles = array();
    $table->titlesalign = 'left';
    // $table->titles[] = 
    $table->titles[] = get_string('curriculum', 'block_bsu_plan') . ' ' . $plan->pname; 
	$table->titles[] = get_string('speciality', 'block_bsu_plan') . ' ' . $plan->sname;
    if (isset($plan->profile))  {
        $table->titles[] = get_string('profile', 'block_bsu_plan'). ' ' . $plan->profile;
    }
    
    $table->titles[] = get_string('edform', 'block_bsu_plan') . ' ' .  $plan->edform;
    $table->titles[] = get_string('kvalif', 'block_bsu_plan') . ' ' . $plan->kvalif;
    $table->titles[] = get_string('groups', 'block_bsu_plan'). ' ' . $plan->strgroups;
    //$table->titles[] = 
    //$sname = $plan->sname;
    // print_object($plan);
    $lastshifr = $plan->lastshifr;
    $k = $plan->kurs;
    $table->downloadfilename = $planid . '_' . $lastshifr . '_' . $k;
    // echo $table->downloadfilename;
    $table->worksheetname = $table->downloadfilename;
    
    // подсчет общего количества студентов
    foreach ($agroups as $agroup)   {            
        if ($group = $DB->get_record_select('bsu_ref_groups', "name = '$agroup'"))  {
            // print_object($group);
            $table->countstud[$group->id] = $group->countstud;
            $table->terms[$group->id] = array();
            for($i=1; $i<=2; $i++)  {
                $table->terms[$group->id][] = get_term_group($yid, $group->name, $i);
            }  
            foreach ($table->terms[$group->id] as $t)   {
                $table->termcntstud[$t] = 0;
            }
        }    
    }

    // подсчет количества студентов по семестрам
    foreach ($table->terms as $grid => $groups)   {
        foreach ($groups as  $t) {
            $table->termcntstud[$t] = 0;
        }
    }
    
    foreach ($table->terms as $grid => $groups)   {
        foreach ($groups as  $t) {
            $table->termcntstud[$t] += $table->countstud[$grid];
        }
    }
    
   
    $sql = "SELECT s.id as sdid, d.id as did, s.numsemestr, d.disciplinenameid, n.name as nname, d.cyclename, 
                d.identificatordiscipline, d.semestrexamen, d.semestrzachet, d.semestrdiffzach, d.semestrkursovik,
                d.mustlearning, p.id as pid, s.lection, s.praktika, s.lab
                FROM mdl_bsu_discipline_semestr s
                INNER JOIN mdl_bsu_discipline d ON d.id=s.disciplineid 
                INNER JOIN mdl_bsu_plan p ON p.id=d.planid
                INNER JOIN mdl_bsu_ref_disciplinename n ON n.id=d.disciplinenameid
                WHERE d.planid=$planid and s.numsemestr in ($termsids) and d.notusing=0
                ORDER BY s.numsemestr, n.name";
    /*
    $sql = "SELECT s.id as ssid, d.id as did   
                FROM {bsu_discipline_semestr} s
                INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
                WHERE d.planid=$planid and s.numsemestr in ($termsids) and d.notusing=0
                group by d.id";
    */             
    // echo $sql; 
    if ($datas = $DB->get_records_sql($sql))  {
        foreach($datas as $data) {
            
            if ($data->lection > 0) {
                $lstream = get_list_streams_for_edworkkind($yid, $planid, $data, 1);

                $count_students = get_count_students_streams_for_edworkkind($yid, $planid, $data, 1);
                if ($count_students == 0)   {
                    $count_students = $table->termcntstud[$data->numsemestr];
                }   
                if ($count_students > 25)   {
                    $aud = 'лекционный зал';
                }  else {
                    $aud = 'аудитория';
                } 
        	    $table->data[] = array($data->nname, 'лекция', $count_students, 45*$data->lection, $aud, $data->numsemestr, $lstream);  
            }
            
                        
            if ($data->praktika > 0)    {
                
                $prstream = get_list_streams_for_edworkkind($yid, $planid, $data, 3);

                $count_students = get_count_students_streams_for_edworkkind($yid, $planid, $data, 3);    

                if ($count_students == 0)   {
                    $count_students = $table->termcntstud[$data->numsemestr];
                }
                $aud = 'аудитория';
                /*   
                if ($count_students > 25)   {
                    $aud = 'лекционный зал';
                }  else {
                    $aud = 'аудитория';
                }
                */
                $table->data[] = array($data->nname, 'практика', $count_students, 45*$data->praktika, $aud, $data->numsemestr, $prstream); 
            }            
            
            
            if ($data->lab > 0) {
                $labstream = get_list_streams_for_edworkkind($yid, $planid, $data, 2);
                                
                $count_students = get_count_students_streams_for_edworkkind($yid, $planid, $data, 2);    

                if ($count_students == 0)   {
                    $count_students = $table->termcntstud[$data->numsemestr];
                }   
                /*
                if ($count_students > 25)   {
                    $aud = 'лекционный зал';
                }  else {
                    $aud = 'аудитория';
                }
                */
                $aud = 'аудитория';
                $table->data[] = array($data->nname, 'лабораторная', $count_students, 45*$data->lab, $aud, $data->numsemestr, $labstream); 
            }
            
            /*
            $strfks = get_formskontrol($data->semestrexamen, $data->semestrzachet, $data->semestrkursovik, $data->semestrdiffzach, $data->numsemestr);
            $fks = explode (',', $strfks);
            foreach ($fks as $fk)   {
                if ($fk == 'экз.')  {
                    $count_students = $table->termcntstud[$data->numsemestr];
                    $table->data[] = array($data->nname, 'экзамен', $count_students, 0.33*45*$count_students, $aud, $data->numsemestr, '');
                } else if ($fk == 'зач.')  {
                    $count_students = $table->termcntstud[$data->numsemestr];
                    $table->data[] = array($data->nname, 'зачет', $count_students, 0.25*45*$count_students, $aud, $data->numsemestr , '');
                }
            }
            */
            
        }
    }
    
    return $table;
}


function get_count_students_streams_for_edworkkind($yid, $planid, $data, $edworkkindid)
{
    global $DB;
    
    $count_students = 0;
    
    $sql = "SELECT s.id as ssid, s.streammaskid, sm.planid, sm.disciplinenameid, sm.edworkkindid, s.groupid, s.subgroupid, s.numstream  
                    FROM {bsu_discipline_stream} s 
                    INNER JOIN {bsu_discipline_stream_mask} sm ON sm.id=s.streammaskid               
                    where  sm.planid=$planid AND sm.disciplinenameid=$data->disciplinenameid AND 
                    sm.term=$data->numsemestr AND yearid=$yid AND sm.edworkkindid=$edworkkindid"; 
    $astream = array();
    $cntstream = array();
    if ($streams = $DB->get_records_sql($sql))   {
        foreach ($streams as $stream)   {
            $astream[$stream->numstream] = '';
            $cntstream[$stream->numstream] = 0;
        }
        foreach ($streams as $stream)   {
            // $strgroup = $DB->get_field_select('bsu_ref_groups', 'name', "id = $stream->groupid");
            $strgroup = $DB->get_record_select('bsu_ref_groups', "id = $stream->groupid");
            $astream[$stream->numstream] .= $strgroup->name;
            if ($stream->subgroupid > 0)    {
                // $strsubgroup = $DB->get_field_select('bsu_discipline_subgroup', 'shortname', "id = $stream->subgroupid");
                $strsubgroup = $DB->get_record_select('bsu_discipline_subgroup',  "id = $stream->subgroupid");
                $astream[$stream->numstream] .=  '->' . $strsubgroup->shortname . "&nbsp;($strsubgroup->countstud)" . '<br>';
                $cntstream[$stream->numstream] += $strsubgroup->countstud;
            } else {
                $astream[$stream->numstream] .=  "&nbsp;($strgroup->countstud)" . ';<br>';
                $cntstream[$stream->numstream] += $strgroup->countstud;
            }    
        }    
    }
    $strstream = '';
    $cnts = count($astream);
    foreach ($astream as $numstream => $as)   {
        if ($cnts>1) {
            $strstream .= 'П-к '.$numstream . ':<br>';
        }
        $strstream .= $as;
        $strstream .= 'Всего: '.$cntstream[$numstream] . '<br>';
        $count_students += $cntstream[$numstream];
    } 
    
    // return $strstream;
    return  $count_students;
}        


function listbox_plan_some($scriptname, $fid, $sid, $pfid, $kurs, $pid, $like)
{
  global $CFG, $OUTPUT, $DB;

  $planmenu = array();
  $planmenu[0] = get_string('selectcurriculum', 'block_bsu_plan').'...';

  // $sql = "SELECT id, name FROM {bsu_plan} WHERE departmentcode=$fid ";
  $sql = "SELECT id as id1, id as id2 FROM {bsu_plan} WHERE departmentcode=$fid ";
  
  if ($sid > 0) {
        $sql .=  " AND specialityid = $sid";  
  }

  if ($pfid > 0) {
        $sql .=  " AND profileid = $pfid";  
  }
  
  // echo $sql;  
  $cntplan = 0;
  if($planids = $DB->get_records_sql_menu($sql))   {
    $strids = implode(',', $planids);
    // echo $strids;
    $sql = "SELECT distinct p.id, p.name FROM mdl_bsu_plan p
            inner join mdl_bsu_plan_groups pg on p.id=pg.planid
            inner join mdl_bsu_ref_groups rf on rf.id = pg.groupid
            where p.id in ($strids) and rf.name like '____{$like}__'";
      if($allplans = $DB->get_records_sql($sql))   {
            $cntplan = count ($allplans);
    		foreach ($allplans as $plan) 	{
                $planmenu[$plan->id] = $plan->id . '. ' . $plan->name;
    		}
      }
  }    
  $planmenu[0] .= " ($cntplan планов)"; 

  echo '<tr align="left"> <td align=right>'.get_string('curriculum', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'pid', $planmenu, $pid, null, 'switchplan');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}


?>