<?php   // $Id: lib_disciplines.php,v 1.8 2012/10/20 12:29:28 shtifanov Exp $


function table_disciplines($yid, $fid, $plan, $term, $agroups, $showkaf, $tab = 'plan', $excel = 0)
{
    global $CFG, $DB, $OUTPUT, $USER, $ACCESS_USER;
    
    $positionshort = $DB->get_records_menu('bsu_staffpositions', null, '', 'id, ir_name');
    
    $context = get_context_instance(CONTEXT_FACULTY, $fid);
    $editcapability = has_capability('block/bsu_plan:editcurriculum', $context);    
    $planid = $plan->id;
    $table = new html_table();  // get_string('ksrh', 'block_bsu_plan'),
/*    
    $table->head = array (get_string('npp', 'block_bsu_plan'),
                          get_string('cyclename', 'block_bsu_plan') . ' (Блок)',
                          get_string('discipline', 'block_bsu_plan'),
                          get_string('formskontrol', 'block_bsu_plan'),
                          get_string('lectionh', 'block_bsu_plan'),
                          get_string('praktika', 'block_bsu_plan'),
                          get_string('labh', 'block_bsu_plan'),
                          get_string('srsh', 'block_bsu_plan'),
                          get_string('mustlearning', 'block_bsu_plan'),                          
                          get_string('disciplinesubgroups', 'block_bsu_plan'),
                          // get_string('disciplinestream', 'block_bsu_plan'),
                          'Потоки', 
                          get_string('teachersdiscipline', 'block_bsu_plan'),
                          get_string('subdepartments', 'block_bsu_plan'),
                          get_string('actions'));
*/
    $table->head = array (get_string('npp', 'block_bsu_plan'),
                          get_string('cyclename', 'block_bsu_plan'), 'Блок',
                          get_string('discipline', 'block_bsu_plan'), 
                          'Лек', 'Лаб', 'Пр', 'КСР', 'Ауд', 'СРС', 'Изуч',  'Контроль', // 'ЭКЗ', 'ВСЕГО',  
                          get_string('disciplinesubgroups', 'block_bsu_plan'),
                          'Потоки', 
                          get_string('teachersdiscipline', 'block_bsu_plan'),
                          get_string('subdepartments', 'block_bsu_plan'),
                          get_string('actions'));

    // $table->headspan = array(1,2,1,1,1,1,1,1,1,1,1,1,1,1);                          
    $table->align = array ('center', 'left', 'left', 'left', 
                           'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', // 'center', 'center',
                           'center', 'center',
                           'left', 'left', 'center');
    $table->width = "80%";
    $table->columnwidth = array (4, 9, 12, 60, 6, 6, 6, 6, 6,  6, 8, 11, 15, 15, 15, 35); // 6, 6,
	// $table->class = 'userenrolment';
    // $table->attributes['class'] = 'userenrolment';

   	$table->titlesrows = array(30, 30);
    $table->titles = array();
    $table->titles[] = get_string('curriculum', 'block_bsu_plan') . ' ' . $plan->id . '. ' .$plan->pname; 
	$table->titles[] = get_string('speciality', 'block_bsu_plan') . ' ' . $plan->sname;
    $table->downloadfilename = "discipline_{$fid}_{$planid}_{$term}";
    $table->worksheetname = $table->downloadfilename;
    
    if ($term == 0) {
        $sql = "SELECT d.id as did, d.disciplinenameid, n.name as nname, d.cyclename, d.semestrexamen, 
                d.semestrzachet, d.semestrkursovik, d.mustlearning, d.identificatordiscipline, 
                d.subdepartmentid, d.semestrdiffzach, d.semestrkp, d.notusing, d.semestrref, d.semestrkontr,
                s.lection, s.praktika, s.lab, s.ksr, s.srs, s.numsemestr, s.examenhours, p.id as pid, p.specialityid
                FROM mdl_bsu_discipline_semestr s
                RIGHT JOIN mdl_bsu_discipline d ON d.id=s.disciplineid
                INNER JOIN mdl_bsu_plan p ON p.id=d.planid
                INNER JOIN mdl_bsu_ref_disciplinename n ON n.id=d.disciplinenameid
                WHERE p.id=$planid and s.disciplineid is null
                ORDER BY n.name";
        $datas = $DB->get_records_sql($sql);          
   } else if ($term == 99)  {             
        $sql = "SELECT s.id as ssid, d.id as did, d.disciplinenameid, n.name as nname, d.cyclename, d.semestrexamen,
                d.semestrzachet, d.semestrkursovik, d.subdepartmentid, d.semestrdiffzach, d.semestrkp, d.notusing, 
                d.mustlearning, d.identificatordiscipline, d.semestrref, d.semestrkontr,
                s.lection, s.praktika, s.lab, s.ksr, s.srs, s.numsemestr, s.examenhours, 
                p.id as pid, p.specialityid
                FROM {bsu_discipline_semestr} s
                LEFT JOIN {bsu_discipline} d ON d.id=s.disciplineid
                INNER JOIN {bsu_plan} p ON p.id=d.planid
                INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                WHERE p.id=$planid
                ORDER BY n.name";
        $datas = $DB->get_records_sql($sql);                  
    } else { 
        $sql = "SELECT d.id as did, d.disciplinenameid, n.name as nname, d.cyclename, d.semestrexamen, d.semestrzachet, 
                d.semestrkursovik, d.subdepartmentid, d.semestrdiffzach, d.semestrkp, d.notusing, d.mustlearning, 
                d.identificatordiscipline, d.semestrref, d.semestrkontr,
                s.lection, s.praktika, s.lab, s.ksr, s.srs, s.numsemestr, s.examenhours,   
                p.id as pid, p.specialityid
                FROM {bsu_discipline_semestr} s
                INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
                INNER JOIN {bsu_plan} p ON p.id=d.planid
                INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                WHERE p.id=$planid and s.numsemestr=$term
                ORDER BY n.name";
        $datas1 = $DB->get_records_sql($sql);                
        
        $hterm = dechex($term);
        $sql = "SELECT d.id as did, d.disciplinenameid, n.name as nname, d.cyclename, d.semestrexamen, d.semestrzachet, 
                d.semestrkursovik, d.semestrdiffzach, d.semestrkp, d.notusing, d.mustlearning, d.identificatordiscipline, d.semestrref, d.semestrkontr
                FROM mdl_bsu_discipline d
                INNER JOIN mdl_bsu_ref_disciplinename n ON n.id=d.disciplinenameid
                WHERE d.planid=$planid and (d.semestrexamen like '%{$hterm}%' or d.semestrzachet like '%{$hterm}%' 
                     or  d.semestrkursovik like '%{$hterm}%' or d.semestrdiffzach like '%{$hterm}%' or  d.semestrkp like '%{$hterm}%'
                     or  d.semestrref like '%{$hterm}%' or d.semestrkontr like '%{$hterm}%')
                ORDER BY n.name";
        $datas2 = $DB->get_records_sql($sql);
        
        $f = array();
        foreach($datas1 as $data) {
           $f[] = $data->did;
        }    
        $s = array();
        foreach($datas2 as $data) {
           $s[] = $data->did;
        }    
        $r = array_diff($s, $f);
        if (!empty($r)) {
            // print_object($r);
            foreach ($r as $r1) {
                // $data[$r1] = new stdClass(); 
                //print_object($datas2[$r1]);
                $datas1[$r1] = $datas2[$r1];
                $datas1[$r1]->lection = 0;
                $datas1[$r1]->praktika = 0;
                $datas1[$r1]->lab = 0;
                $datas1[$r1]->ksr = 0;
                $datas1[$r1]->srs = 0;
                $datas1[$r1]->numsemestr = 0;
                $datas1[$r1]->examenhours = 0;
            }
            
            $disname = array();
            foreach($datas1 as $data) {
               $disname[$data->did] = $data->nname;
            }    
            
            asort($disname);
            
            $datas = array();
            foreach($disname as $index => $nname) {
                $datas[$index] = $datas1[$index];                
            }                        
        } else {
            $datas = $datas1;
                    
        }
        // print_object($datas);
        
    }   
    // echo $sql;
    /*
*/
                 
    if ($datas)  {
        
        $sql = "SELECT 1 as i, max(b.numsemestr) as maxsem FROM {bsu_discipline} a
                 inner join {bsu_discipline_semestr} b on a.id=b.disciplineid
                 where a.planid=$planid";
        if ($max = $DB->get_records_sql($sql))  {
            $maxsem = $max[1]->maxsem;
        } else {
            $maxsem = 0;
        }

        $i = 1;
        foreach($datas as $data) {

/*
            $strsubgroups = '';
          	$subgroups = $DB->get_records_sql("SELECT id, name FROM {bsu_discipline_subgroup}
                                               WHERE disciplineid={$data->did}");
            if ($subgroups) {
             	foreach ($subgroups as $subgroup){
        			$title = get_string('delsubgroup', 'block_bsu_plan');
                    $link = "<a title=\"$title\" href=\"disciplines.php?action=del&fid=$fid&pid=$planid&did={$data->did}&subgr=$subgroup->id\">";
                    $link .= '<img src="'.$OUTPUT->pix_url('i/cross_red_small').'" alt="'. $title . '" /></a>';

            		$strsubgroups .= '* '. $subgroup->name . $link . '<br>';
             	}
            }
			$title = get_string('addsubgroups', 'block_bsu_plan');
            $strlinkupdate = "<a title=\"$title\" href=\"disciplines.php?action=add&fid=$fid&did={$data->did}\">";
            $strlinkupdate .= '<img src="'.$OUTPUT->pix_url('t/addfile').'" class="icon" alt="'. $title . '" />';

			$title = get_string('editsubgroups', 'block_bsu_plan');
			$strlinkupdate .= "<a title=\"$title\" href=\"addiscipline.php?mode=edit&amp;fid=$fid&did={$data->did}\">";
            $strlinkupdate .= '<img src="'.$OUTPUT->pix_url('i/edit').'" class="icon" alt="'. $title . '" />';
*/
            $strsubgroups = '';
            $strstreamgroups = '';
            $link = "yid=$yid&fid=$fid&pid=$planid&did={$data->did}&term=$term&plantab=$tab";
            if ($editcapability)    {
                if ($cnt = $DB->count_records_select('bsu_discipline_subgroup', "yearid=$yid AND disciplineid={$data->did}"))  {
                    $title = get_string('changesubgroups', 'block_bsu_plan');
                    $strsubgroups = "<a href=\"subgroups.php?action=edit&{$link}\"> $title ($cnt)</a>";
                } else {
                    $title = get_string('createsubgroups', 'block_bsu_plan');
                    $strsubgroups = "<a href=\"subgroups.php?action=new&{$link}\">$title</a>";
                }
                
                $sql = "SELECT s.id FROM mdl_bsu_discipline_stream_mask sm
                        inner join mdl_bsu_discipline_stream s ON  sm.id=s.streammaskid
                        where yearid=$yid AND planid=$planid AND disciplinenameid={$data->disciplinenameid} AND sm.term=$term
                        group by streammaskid, numstream";
                if ($streamscnt = $DB->get_records_sql($sql))   {         
                    $cnt = count($streamscnt);
                // if ($cnt = $DB->count_records_select('bsu_discipline_stream', "disciplineid={$data->did}"))  {
                // if ($cnt = $DB->count_records_select('bsu_discipline_stream_mask', "yearid=$yid AND planid=$planid AND disciplinenameid={$data->disciplinenameid} AND term=$term"))  {                
                    $title = get_string('changestream', 'block_bsu_plan');
                    $strstreamgroups = "<a href=\"streamgroups.php?action=edit&{$link}\"> $title ($cnt)</a>"; //  ($cnt)
                } else {
                    $title = get_string('createstream', 'block_bsu_plan');
                    $strstreamgroups = "<a href=\"streamgroups.php?action=new&{$link}\">$title</a>";
                }
            }    
            
            $strsubdep = '';
            $kid = 0;
            $twokid = array();
            $editcapabilitysubdep = false;
            if ($showkaf)   {     
                $discipsubdeps = $DB->get_records_sql("SELECT id, subdepartmentid FROM {bsu_discipline_subdepartment}
                                                        WHERE disciplineid={$data->did} AND yearid=$yid"); // !!!!!!!!!!!!!!!!!!!!
                                                       // WHERE disciplinenameid={$data->disciplinenameid} AND specialityid = {$data->specialityid}");
                if ($discipsubdeps)  {
                    foreach ($discipsubdeps as $discipsubdep)  {
                        if ($sd = $DB->get_record_select('bsu_vw_ref_subdepartments', "id = $discipsubdep->subdepartmentid")) {
                            $strsubdep .= ' ' . $sd->name . '<br>';
                            $kid = $sd->id;
                            $twokid[] = $kid;
                            
                            if (!$editcapabilitysubdep) {
                                if ($context = get_context_instance(CONTEXT_SUBDEPARTMENT, $kid))   {
                                    $editcapabilitysubdep = has_capability('block/bsu_charge:editcharge', $context);    
                                }
                            }
                            
                        }  else {
                            $strsubdep .= 'КАФЕДРА НЕ НАЙДЕНА (id = ' . $sd->subdepartmentid . ')<br>';// get_string('kafedranotset', 'block_bsu_plan');
                        }
                    }    
                } else {
                    $strsubdep = get_string('kafedranotset', 'block_bsu_plan');
                }
                if (is_siteadmin() || count($discipsubdeps)>1 || $USER->id == 7886 || $USER->id == 6702) {
                    $strsubdep = "<a href=\"subdepdiscipline.php?action=new&{$link}&kid=$kid\">$strsubdep</a>";
                }    
            }            


            $strteachers = $strselect1 = '';
            $sid = 0;
            // "SELECT id, teacherid, subdepartmentid FROM {bsu_discipline_teacher} WHERE disciplineid={$data->did}"
            $sql = "SELECT distinct teacherid FROM mdl_bsu_edwork e
                    inner join mdl_bsu_teachingload t on e.edworkmaskid=t.edworkmaskid
                    where e.disciplineid=$data->did AND e.yearid=$yid";

       	 	$teachers = $DB->get_records_sql($sql);
            if ($teachers)  {
                $ateachers = array();
                foreach ($teachers as $teach)  {
                    $user = $DB->get_record_sql("SELECT id, lastname, firstname FROM {user} WHERE id=$teach->teacherid");
                    
                    if ($kid > 0)   {
                        $strselect1 = " AND subdepartmentid=$kid";
                    }
                    $sn1 = '-';
                    if ($teachers = $DB->get_records_select('bsu_teacher_subdepartment', "teacherid=$teach->teacherid $strselect1", null, '', 'id, positionid'))    {
                        $teacher1 = reset($teachers);
                	    if (isset($positionshort[$teacher1->positionid])) {
                  		    $sn1 = $positionshort[$teacher1->positionid];
                        } 
                    }
                    $ateachers[] = fullname($user) . ', ' . $sn1;
                }
                // $strteachers .= ' ' . $fullname . '<br>';
                $strteachers = implode (', <br /><br />', $ateachers); 
            } else {
            	$strteachers = '-'; // get_string('teachernotset', 'block_bsu_plan');
         	}
            // $strteachers = "<a href=\"teacherdiscipline.php?action=new&{$link}&sid=$sid\">$strteachers</a>";

            
            // $allhours = $data->lection + $data->praktika + $data->lab + $data->ksr + $data->srs;
            $allhours = $data->mustlearning;
            $fk = get_formskontrol($data->semestrexamen, $data->semestrzachet, 
                                   $data->semestrkursovik, $data->semestrdiffzach, 
                                   $term, $data->semestrkp, $allhours, $data->semestrref, $data->semestrkontr);

            $strlinkupdate = '';
            if ($editcapability)    {
              
                // $strlinkupdate .= "<a href='synonym.php?{$link}'><img class='icon' title='".get_string('synonyms', 'block_bsu_plan')."' src='".$OUTPUT->pix_url('i/synonym')."'></a>";
                
                if ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                    $strlinkupdate .= "<a href='editd.php?{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
                }
               
                $strlinkupdate .= "<a href='synonym.php?{$link}'><img class='icon' title='".get_string('synonyms', 'block_bsu_plan')."' src='".$OUTPUT->pix_url('i/synonym')."'></a>";

                if ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                    $strlinkupdate .= "<a href='disciplines.php?action=delete&{$link}&tab=$tab'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                }

                if (is_siteadmin() )  {
                    $strlinkupdate .= "<a href='disciplines.php?action=edit&{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('t/editstring')."'></a>";
                }    
                
                if ($strsubdep != '')   {
                    $link2 = $CFG->wwwroot . "/blocks/bsu_charge/disciplinecharge.php?fid=$fid&pid=$planid&yid=$yid&did={$data->did}&sid";
                    $strlinkupdate .= "<a target=\"_blank\" href='$link2=$kid'><img class='icon' title='Посмотреть нагрузку по дисциплине' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                    //print_object($twokid);
                    if (count($twokid) == 2)    {
                        if ($kid != $twokid[1]) $kid2=$twokid[1];
                        else $kid2=$twokid[0];
                        $strlinkupdate .= "<strong>2:</strong><a target=\"_blank\" href='$link2=$kid2'><img class='icon' title='Посмотреть нагрузку по дисциплине по второй кафедре' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                    }
                    
                }    
                
            }  else if ($editcapabilitysubdep)  {

                if ($strsubdep != '')   {
                    $link2 = $CFG->wwwroot . "/blocks/bsu_charge/disciplinecharge.php?fid=$fid&pid=$planid&yid=$yid&did={$data->did}&sid=$kid";
                    $strlinkupdate .= "<a target=\"_blank\" href='$link2'><img class='icon' title='Посмотреть нагрузку по дисциплине' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                }    
                
            }  

            $pos = mb_strpos($data->cyclename, 'ДВ', 0, 'UTF-8');
            $pos2 = mb_strpos($data->cyclename, '.В', 0, 'UTF-8');
            $pos3 = mb_strpos($data->cyclename, 'ФТД', 0, 'UTF-8');
            // $pos3 = mb_strpos($discipline->cyclename, '.В', 0, 'UTF-8');
            if (!($pos === false) || !($pos2 === false) || !($pos3 === false))  {            
                if ($data->notusing)    {
                    $icon = 'completion-auto-fail';
                } else {
                    $icon = 'completion-auto-pass';// 'completion-manual-n';
                }
                if ($editcapability && ((strpos($CFG->editplanopenedforyid, "$yid") !== false) || is_siteadmin() || in_array($USER->id, $ACCESS_USER)))    {
                    $strlinkupdate .= "<a href='disciplines.php?action=notusing&{$link}&tab=$tab'><img class='icon' title='Включить/отключить дисциплину по выбору' src='".$OUTPUT->pix_url("i/$icon")."'></a>";
                } else {
                    $strlinkupdate .= "<img class='icon' title='Включить/отключить дисциплину по выбору' src='".$OUTPUT->pix_url("i/$icon")."'>";
                }    
            }
            
            if ($CFG->stopeditingplan)  {
                $strlinkupdate = "<img class='icon' title='Редактирование запрещено, т.к. дисциплина уже пройдена группами старших курсов' src='".$OUTPUT->pix_url("i/unlock")."'>";
                // if (is_siteadmin() )  {
                    $link2 = $CFG->wwwroot . "/blocks/bsu_charge/disciplinecharge.php?fid=$fid&pid=$planid&yid=$yid&did={$data->did}&sid";
                    $strlinkupdate .= "<a target=\"_blank\" href='$link2=$kid'><img class='icon' title='Посмотреть нагрузку по дисциплине' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                    // print_object($twokid);
                    if (count($twokid) == 2)    {
                        if ($kid != $twokid[1]) $kid2=$twokid[1];
                        else $kid2=$twokid[0];
                        $strlinkupdate .= "<strong>2:</strong><a target=\"_blank\" href='$link2=$kid2'><img class='icon' title='Посмотреть нагрузку по дисциплине по второй кафедре' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                    }
                // }    
            }
 
            $aud = $data->lection + $data->lab + $data->praktika + $data->ksr;
            $mustlearning = $aud + $data->srs;
            $vsego = $mustlearning + $data->examenhours;  
            // $vsego = $data->mustlearning + $data->examenhours; 

/*
            $sql = "SELECT id, contextid FROM {role_assignments} WHERE roleid=24 AND userid=$USER->id";
            $role = $DB->get_records_sql_menu($sql);
            if($role) {
//            	$strlinkupdate = '';
				$contextid = implode(',', $role);
				$sql = "SELECT c.id as i, bvrs.id FROM {context} c 
						INNER JOIN {bsu_vw_ref_subdepartments} bvrs ON c.instanceid=bvrs.id
						WHERE c.id in ($contextid) AND bvrs.yearid=$yid";
						
				if($subdepid = $DB->get_records_sql_menu($sql)) {
					$d = $DB->get_record_sql("SELECT disciplinenameid FROM {bsu_discipline} WHERE id={$data->did}");
					$specialityid = $DB->get_record_sql("SELECT specialityid FROM {bsu_plan} WHERE id=$planid");		
		
					$subdepid = implode(',', $subdepid);
    	        	$link2 = "disciplines.php?yid=$yid&fid=$fid&pid=$planid&tab=$tab&term=$term&action=subdep&did={$data->did}&sid=$subdepid";
        	    	
					$verify = $DB->get_records_sql("SELECT id FROM {bsu_discipline_subdepartment_zav} WHERE 
						yearid=$yid AND 
						disciplinenameid=$d->disciplinenameid AND
		       			subdepartmentid IN ($subdepid) AND 
		       			specialityid=$specialityid->specialityid");

					if(!$verify) {
						$verify = $DB->get_records_sql("SELECT id FROM {bsu_discipline_subdepartment} WHERE 
							yearid=$yid AND 
							disciplinenameid=$d->disciplinenameid AND
			       			subdepartmentid IN ($subdepid) AND 
			       			specialityid=$specialityid->specialityid");
			       	}
		       			
					if(!$verify) {
		                $strlinkupdate.= "<a href='$link2'><img class='icon' title='Сопоставить дисциплину' src='".$OUTPUT->pix_url('t/switch')."'></a>";
					}
				}
			}
/**/			

            
            if ($data->notusing )    {
                if ($excel == 0)    {
                	$table->data[] = array('<font color=gray>'.$i.'</font>', '<font color=gray>'.$data->cyclename.'</font>', 
                                            '<font color=gray>'.$data->identificatordiscipline.'</font>', '<font color=gray>'.$data->nname.'</font>', 
                                            '<font color=gray>'.$data->lection.'</font>', '<font color=gray>'.$data->lab.'</font>', 
                                            '<font color=gray>'.$data->praktika.'</font>',  '<font color=gray>'.$data->ksr.'</font>', '<font color=gray>'.$aud.'</font>', 
                                            '<font color=gray>'.$data->srs.'</font>', '<font color=gray>'.$mustlearning.'</font>', 
                                            // $data->examenhours, $vsego, 
                                            '<font color=gray>'.$fk.'</font>',                         
                                            $strsubgroups, $strstreamgroups, '', '', $strlinkupdate); // $strteachers, $strsubdep
                }                                            
            } else {    
            	$table->data[] = array($i, $data->cyclename, $data->identificatordiscipline, $data->nname, 
                                        $data->lection, $data->lab, $data->praktika,  $data->ksr, $aud, $data->srs, 
                                        $mustlearning, 
                                        // $data->examenhours, $vsego, 
                                        $fk,                         
                                        $strsubgroups, $strstreamgroups, $strteachers, $strsubdep, $strlinkupdate); // $strteachers, $strsubdep
            }                            
            $i++;
        }
    }

    return $table;
}

function shift_content($fid, $pid, $shift=1)
{
    global $CFG, $DB, $OUTPUT;
    
    $success_disc = 1;
    $success_pract = 1;
    
    if ($shift>0)   {
        $where = ' вправо';
    } else {
        $where = ' влево';
    }
       
    $sql = "SELECT min(numsemestr) as min 
            FROM mdl_bsu_discipline d
            inner join mdl_bsu_discipline_semestr ds on d.id=ds.disciplineid
            WHERE planid=$pid";
    if ($semestrs = $DB->get_record_sql($sql))  {
        if ($semestrs->min+$shift<=0) {
           echo '<center>'.$OUTPUT->error_text("Смещение не может быть произведено, так как в некоторых дисциплинах при попытке смещения значение семестра становится меньше 1.").'</center><br>';         
           $success_disc = 0; 
        }  
     } else {
        $success_disc = 0;
     }
    
    $sql = "SELECT min(term) as min 
            FROM mdl_bsu_plan_practice d
            WHERE planid=$pid";
    if ($semestrs = $DB->get_record_sql($sql))  {
        if ($semestrs->min+$shift<=0) {
            $success_pract = 0;
            echo '<center>'.$OUTPUT->error_text("Смещение не может быть произведено, так как в некоторых практиках или спец. видах при попытке смещения значение семестра становится меньше 1.").'</center><br>';         
        } 
     } else {
        $success_pract = 0;
     }

     if ($success_disc&&$success_pract) {   
       $sql = "update mdl_bsu_discipline a inner join mdl_bsu_discipline_semestr b on a.id=b.disciplineid
                SET b.numsemestr=b.numsemestr+$shift
                WHERE planid=$pid";
        if ($DB->Execute($sql)) {
            echo $OUTPUT->notification("Дисциплины смещены на " . abs($shift) . " семестр (-а/-ов) $where.", 'notifysuccess');        
        } else {
            echo $OUTPUT->notification('Ошибка при выполнении запроса: '. $sql);
        }           
       
        $disc_ids = $pract_ids = array();
        
        $sql = "SELECT id, semestrexamen, semestrzachet, semestrdiffzach, semestrkursovik, semestrkp
                FROM mdl_bsu_discipline
                WHERE planid=$pid";
        if ($disciplines = $DB->get_records_sql($sql))  {
            foreach ($disciplines as $discipline)   {
                $disc_ids[] = $discipline->id;
                if (!empty($discipline->semestrexamen))    {
                    $newex = shift_semestrexamenzachet($discipline->semestrexamen, $shift);
                    $DB->set_field_select('bsu_discipline', 'semestrexamen', $newex, "id = $discipline->id");
                }
                if (!empty($discipline->semestrzachet))    {
                    $newex = shift_semestrexamenzachet($discipline->semestrzachet, $shift);
                    $DB->set_field_select('bsu_discipline', 'semestrzachet', $newex, "id = $discipline->id");
                }
                if (!empty($discipline->semestrdiffzach))    {
                    $newex = shift_semestrexamenzachet($discipline->semestrdiffzach, $shift);
                    $DB->set_field_select('bsu_discipline', 'semestrdiffzach', $newex, "id = $discipline->id");
                }
                if (!empty($discipline->semestrkursovik))    {
                    $newex = shift_semestrexamenzachet($discipline->semestrkursovik, $shift);
                    $DB->set_field_select('bsu_discipline', 'semestrkursovik', $newex, "id = $discipline->id");
                }
                if (!empty($discipline->semestrkp))    {
                    $newex = shift_semestrexamenzachet($discipline->semestrkp, $shift);
                    $DB->set_field_select('bsu_discipline', 'semestrkp', $newex, "id = $discipline->id");
                }
            }
        } 
        
        $disc_ids = implode(",",$disc_ids);
        
        $sql = "SELECT id
                FROM mdl_bsu_plan_practice
                WHERE planid=$pid";
        if ($practices = $DB->get_records_sql($sql))  {
            foreach ($practices as $practice)   {
               $pract_ids[] = $practice->id; 
            }
        }
        
        $pract_ids = implode(",",$pract_ids);
        
        $sched_mask_ids = array();
        
        $sql="SELECT id
              FROM mdl_bsu_schedule_mask
              WHERE disciplineid in ($disc_ids)";
         if ($shed_masks = $DB->get_records_sql($sql))  { 
            foreach ($shed_masks as $shed_mask)   {
               $sched_mask_ids[] = $shed_mask->id;
            }
         }
         
        $sched_mask_ids = implode(",",$sched_mask_ids);
         
        $sql = "UPDATE mdl_bsu_schedule
                SET term=term+$shift
                WHERE schedulemaskid in ($sched_mask_ids)";
        if ($DB->Execute($sql)) {
            $sql = "UPDATE mdl_bsu_schedule_mask
                    SET term=term+$shift
                    WHERE disciplineid in ($disc_ids)";
            if ($DB->Execute($sql)) {    
               echo $OUTPUT->notification("Расписание дисциплин смещено на " . abs($shift) . " семестр (-а/-ов) $where.", 'notifysuccess');        
            } else {
                echo $OUTPUT->notification('Ошибка при выполнении запроса: '. $sql);
            }  
        } else {
            echo $OUTPUT->notification('Ошибка при выполнении запроса: '. $sql);
        }   
        
        $sql = "UPDATE mdl_bsu_discipline_synonym
                SET term=term+$shift
                WHERE planid=$pid";
        if ($DB->Execute($sql)) {
            $sql = "UPDATE mdl_bsu_discipline_synonym
                    SET s_term=s_term+$shift
                    WHERE s_planid=$pid";
            if ($DB->Execute($sql)) {    
               echo $OUTPUT->notification("Синонимы дисциплин смещены на " . abs($shift) . " семестр (-а/-ов) $where.", 'notifysuccess');        
            } else {
                echo $OUTPUT->notification('Ошибка при выполнении запроса: '. $sql);
            }  
        } else {
            echo $OUTPUT->notification('Ошибка при выполнении запроса: '. $sql);
        } 
        
        $sql = "UPDATE mdl_bsu_plan_practice
                SET term=term+$shift
                WHERE planid=$pid";
        if ($DB->Execute($sql)) {
            echo $OUTPUT->notification("Практики и спец. виды смещены на " . abs($shift) . " семестр (-а/-ов) $where.", 'notifysuccess');        
        } else {
            echo $OUTPUT->notification('Ошибка при выполнении запроса: '. $sql);
        } 
        
        $group_ids = array();
        
        $sql_group="SELECT groupid FROM dean.mdl_bsu_plan_groups where planid=$pid";
        if ($groups = $DB->get_records_sql($sql_group))  { 
            foreach ($groups as $group)   {
                $group_ids[] = $group->groupid;
            }
        }
        
        $group_ids = implode(",",$group_ids);
        
        $shift_cid = (int) floor($shift / 2);
        $shift_pol = $shift - $shift_cid*2;
       
        $sql = "UPDATE mdl_bsu_marksheet_students
                SET pol=pol+$shift_pol, cid=cid+$shift_cid
                WHERE (disciplineid in ($disc_ids) and edwork!=1) or  (disciplineid in ($pract_ids) and edwork=1)";
        if ($DB->Execute($sql)) {
           $sql = "UPDATE mdl_bsu_marksheet
                SET pol=pol+$shift_pol, cid=cid+$shift_cid
                WHERE (disciplineid in ($disc_ids) and edwork!=1) or (disciplineid in ($pract_ids) and edwork=1) or (groupid in ($group_ids))";
            if ($DB->Execute($sql)) {    
               echo $OUTPUT->notification("Данные по успеваемости по дисциплинам смещены на " . abs($shift) . " семестр (-а/-ов) $where.", 'notifysuccess');        
            } else {
                echo $OUTPUT->notification('Ошибка при выполнении запроса: '. $sql);
            }  
        } else {
            echo $OUTPUT->notification('Ошибка при выполнении запроса: '. $sql);
        } 

        /*all*/
        //bsu_plan_practice_semestr_shacht - ?????
        //bsu_plan_practice_shacht - ????
        
        $sql="SELECT table_name, COLUMN_NAME
              FROM INFORMATION_SCHEMA.COLUMNS
              WHERE (table_name like '%bsu_plan_weeks_hours%' or table_name like '%bsu_schedule_practice%' or table_name like '%bsu_schedule_term%' or table_name like '%bsu_edwork%' or table_name like '%bsu_discipline_stream_mask%')
              AND table_schema = 'dean' and (COLUMN_NAME like '%term%' or COLUMN_NAME like '%sem%')";
        $success = 1;
        if ($table_fields = $DB->get_records_sql($sql))  {
            foreach ($table_fields as $table_field)   {
              $sql_update="UPDATE $table_field->table_name 
                           SET $table_field->column_name=$table_field->column_name+$shift
                           WHERE planid=$pid";
              if (!$DB->Execute($sql_update)) {
                $success = 0;
              }              
            }
        }
        if ($success) {
            echo $OUTPUT->notification("Потоки, нагрузка и другие данные плана смещены на " . abs($shift) . " семестр (-а/-ов) $where.", 'notifysuccess');        
        } else {
            echo $OUTPUT->notification('Ошибка при выполнении запроса: '. $sql);
        } 
   }
}


function shift_semestrexamenzachet($str, $shift=1)
{
    $arr = str_split($str);

    foreach ($arr as $i => $sym)    {
        if (!empty($sym))  {
            // echo "$i => $sym<br>";
            $d = hexdec($sym);
            $d += $shift;
            $arr[$i] = strtoupper(dechex($d));
            // echo "$i =>" .  $arr[$i] . '<br><br>';
        }    
    }
    
    $ret = implode('', $arr);
    // echo $ret . '<br>';
    return $ret; 

}


function table_practice_old($fid, $plan)
{
    global $CFG, $DB, $OUTPUT;  
      
    $planid = $plan->id;
    $table = new html_table();
    $table->head = array (get_string('npp', 'block_bsu_plan'),
                          get_string('vid', 'block_bsu_plan'),
                          get_string('kurs', 'block_bsu_plan'),
                          get_string('term', 'block_bsu_plan'),
                          get_string('week', 'block_bsu_plan'),
                          get_string('hours', 'block_bsu_plan'),
                          get_string('vidnormativa', 'block_bsu_plan'),
                          get_string('actions'));
    $table->align = array ('center', 'left', 'center', 'center', 'center', 'center', 'center', 'center');
    // $table->width = "70%";
    
    $vidnormativa = get_vidnormativa();
    
    if ($practices = $DB->get_records_select('bsu_plan_practice', "planid = $planid", null, 'term'))  {
        $i = 1;
        foreach ($practices as $practice)   {
            $strlinkupdate = '';
            /*
            if ($practice->isnew && $practice->week == 0)  {
                if ($ps = $DB->get_records_select('bsu_plan_practice_semestr_shacht', "practiceid = $practice->id"))  {
                    foreach ($ps as $p) {
                        $practice->term = $p->term;
                        $practice->week = $p->planweek;
                        $practice->hours = $p->planhour;  
                    }    
                }    
                
            }
            */            
            
            // choose_from_menu()
            $selectvid = html_writer::select($vidnormativa, 'vidnormativa_'.$practice->id, $practice->vidnormativa);
            $table->data[] = array($i++. '.', "<input type=text name=vid_{$practice->id} size=70 value=\"$practice->vid\">",
                                    "<input type=text name=kurs_{$practice->id} size=3 value=\"$practice->kurs\">",
                                    "<input type=text name=term_{$practice->id} size=3 value=\"$practice->term\">",
                                    "<input type=text name=week_{$practice->id} size=3 value=\"$practice->week\">",
                                    "<input type=text name=hours_{$practice->id} size=3 value=\"$practice->hours\">",
                                    $selectvid, $strlinkupdate);
                                    // $vidnormativa[$practice->vidnormativa], $strlinkupdate); 
            
        }
        
    }
    
    $selectvid = html_writer::select($vidnormativa, 'vidnormativa_0', 0);
    $table->data[] = array('', "<input type=text name=vid_0 size=70 value=\"\">",
                        "<input type=text name=kurs_0 size=3 value=\"0\">",
                        "<input type=text name=term_0 size=3 value=\"0\">",
                        "<input type=text name=week_0 size=3 value=\"0\">",
                        "<input type=text name=hours_0 size=3 value=\"0\">",
                        $selectvid, '');

    $selectvid = html_writer::select($vidnormativa, 'vidnormativa_a', 0);
    $table->data[] = array('', "<input type=text name=vid_a size=70 value=\"\">",
                        "<input type=text name=kurs_a size=3 value=\"0\">",
                        "<input type=text name=term_a size=3 value=\"0\">",
                        "<input type=text name=week_a size=3 value=\"0\">",
                        "<input type=text name=hours_a size=3 value=\"0\">",
                        $selectvid, '');

    return $table;  
}



function get_vidnormativa($what='array')
{
    $a = array();
    if ($what == 'array')   {
        $a[0] = 'не задан';
        $a[1] = 'на студента';
        $a[2] = 'на студента в неделю';
        $a[3] = 'на (под)группу';
        $a[4] = 'на (под)группу в неделю';
        return $a;
    }
}    


function table_practice0($yid, $fid, $plan, $agroups, $currtab)
{
    global $CFG, $DB, $OUTPUT, $USER, $ACCESS_USER;
    
    $context = get_context_instance(CONTEXT_FACULTY, $fid);
    $editcapability = has_capability('block/bsu_plan:editcurriculum', $context);

    $currtab2 = substr($currtab, 0, 4);
    $positionshort = $DB->get_records_menu('bsu_staffpositions', null, '', 'id, ir_name');  
      
    $planid = $plan->id;
    $table = new html_table();
    if ($currtab2 == 'plan')    {
        $table->head = array (get_string('npp', 'block_bsu_plan'),
                              get_string('practicetype', 'block_bsu_plan'),
                              get_string('practicename', 'block_bsu_plan'),
                              get_string('term', 'block_bsu_plan'),
                              get_string('week', 'block_bsu_plan'),
                              get_string('formula', 'block_bsu_plan'),
                              get_string('actions'));
        $table->align = array ('center', 'left', 'left', 'center', 'center', 'left');
        $table->columnwidth = array (4, 25, 39, 10, 10, 22, 27);        
    } else {
        $table->head = array (get_string('npp', 'block_bsu_plan'),
                              get_string('practicetype', 'block_bsu_plan'),
                              get_string('practicename', 'block_bsu_plan'),
                              get_string('term', 'block_bsu_plan'),
                              get_string('week', 'block_bsu_plan'),
                              get_string('formula', 'block_bsu_plan'),
                              'Кафедра(ы)',
                              'Преподаватели',
                              'Группа(ы)',
                              'Количество<br />подгрупп',
                              'Кол-во<br />студентов',
                              'Всего часов<br>нагрузки',
                              get_string('actions'));
        $table->align = array ('center', 'left', 'left', 'center', 'center', 'left',  'left', 
                                'left', 'center', 'center', 'center', 'center');
        $table->columnwidth = array (4, 25, 39, 10, 10, 22, 27, 30, 17, 9, 8, 10, 5);                                
        
    }
    
	// $table->class = 'userenrolment';
    // $table->attributes['class'] = 'userenrolment';

   	$table->titlesrows = array(30, 30);
    $table->titles = array();
    $table->titles[] = get_string('curriculum', 'block_bsu_plan') . ' ' . $plan->id . '. '. $plan->pname; 
	$table->titles[] = get_string('speciality', 'block_bsu_plan') . ' ' . $plan->sname;
    $table->downloadfilename = "practice_{$fid}_{$plan->id}";
    $table->worksheetname = $table->downloadfilename;
    
    // $table->width = "70%";
    // id, planid, practicetypeid, name, term, week
    
    $practicetype = $DB->get_records_select_menu('bsu_ref_practice_type', "", null, '', 'id, name');
    $practiceformula = $DB->get_records_select_menu('bsu_ref_practice_type', "", null, '', 'id, formula');
    
    $terms = get_terms_group($yid, $agroups);
        
    if ($practices = $DB->get_records_select('bsu_plan_practice', "planid = $planid AND edworkkindid=13", null, 'term'))  {
        $i = 1;
        foreach ($practices as $practice)   {

            $link = "yid=$yid&fid=$fid&pid=$plan->id&prid={$practice->id}&term=100&tab=$currtab";
                        
            if ($currtab2 == 'kurs')    {
                $kurs = substr($currtab, 4, 1);
                $termodd = 2*$kurs - 1;
                $termeven = 2*$kurs;
                if ($practice->term != $termodd && $practice->term != $termeven) continue;
    
                $strsubgroups = '';
                if ($cnt = $DB->count_records_select('bsu_plan_practice_subgroup', "practiceid={$practice->id}"))  {
                    $title = get_string('changesubgroups', 'block_bsu_plan');
                    $strsubgroups = "<a href=\"subgroupspractice.php?action=edit&{$link}\"> $title ($cnt)</a>";
                } else {
                    $title = get_string('createsubgroups', 'block_bsu_plan');
                    $strsubgroups = "<a href=\"subgroupspractice.php?action=new&{$link}\">$title</a>";
                }
    
                
                $sql = "SELECT sd.id, sd.name, ps.countsubgroups, ps.countstudents
                        FROM mdl_bsu_plan_practice_subdep ps
                        inner join mdl_bsu_vw_ref_subdepartments sd on sd.id=ps.subdepartmentid
                        where ps.yearid = $yid AND ps.practiceid = $practice->id
                        group by sd.id";
                // echo $sql . '<br />';        
                $strsubdep = $strsubgroup = $strstud = '';
                $kid = 0;        
                if ($kafedri = $DB->get_records_sql($sql)) {
                    foreach ($kafedri as $kaf) {
                        $strsubdep .= $kaf->name . '<br />';
                        $kid =  $kaf->id;
                        $strsubgroup .= $kaf->countsubgroups . '<br />'; 
                        $strstud .= $kaf->countstudents . '<br />';
                    }
                } else {
                    $strsubdep = get_string('kafedranotset', 'block_bsu_plan');    
                }
                
                $strgroups = '';
                foreach ($agroups as $group)    {
                    if (in_array($practice->term, $terms[$group] ))   {
                        $cntstud = $DB->get_field_select('bsu_ref_groups', 'countstud', "name = '$group'");
                        $strgroups .= $group . "({$cntstud}ст.)<br />";
                    }
                }        
                
                $strlinkupdate = '';
                if ((strpos($CFG->editplanopenedforyid, "$yid") !== false && $editcapability) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                    $strsubdep = "<a href=\"subdeppractice.php?action=new&{$link}&kid=$kid\">$strsubdep</a>";
                    $strlinkupdate .= "<a href='editpractice.php?action=edit&{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
                	$strlinkupdate .= "<a href='editpractice.php?action=delete&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                    if (!empty($strgroups)) {
                        if (count($kafedri) > 1)   {
                            $numkaf=1;
                            foreach ($kafedri as $kaf) {
                                $link2 = $CFG->wwwroot . "/blocks/bsu_charge/practicecharge.php?{$link}&sid=$kaf->id";
                                $strlinkupdate .= "<br />$numkaf:<a href='$link2'><img class='icon' title='Посмотреть нагрузку по практике по $numkaf-й кафедре' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                                $numkaf++;
                            }
                        } else {
                            $link2 = $CFG->wwwroot . "/blocks/bsu_charge/practicecharge.php?{$link}&sid=$kid";
                            $strlinkupdate .= "<a href='$link2'><img class='icon' title='Посмотреть нагрузку по практике' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                        }    
                    }
                    $strlinkupdate .= '<br>';
                    if (!empty($strstud))   {
                        $strstud = "<b><a href=\"subdeppractice.php?action=new&{$link}&kid=$kid\">$strstud</a></b>";
                    }
                    if (!empty($strsubgroup))   {
                        $strsubgroup = "<b><a href=\"subdeppractice.php?action=new&{$link}&kid=$kid\">$strsubgroup</a></b>";
                    }
                } else {
                    if (!empty($strgroups)) {
                        $link2 = $CFG->wwwroot . "/blocks/bsu_charge/practicecharge.php?{$link}&sid=$kid";
                        $strlinkupdate .= "<a href='$link2'><img class='icon' title='Посмотреть нагрузку по практике' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                    }
                }

                if ($CFG->stopeditingplan)  {
                    $strlinkupdate = "<img class='icon' title='Редактирование запрещено, т.к. практика уже пройдена группами старших курсов' src='".$OUTPUT->pix_url("i/unlock")."'>";
                }
                
                $strteachers = $strselect1 = '';
                $sql = "SELECT distinct teacherid FROM mdl_bsu_edwork e
                        inner join mdl_bsu_teachingload t on e.edworkmaskid=t.edworkmaskid
                        where e.practiceid=$practice->id AND e.yearid=$yid";
    
           	 	$teachers = $DB->get_records_sql($sql);
                if ($teachers)  {
                    $ateachers = array();
                    foreach ($teachers as $teach)  {
                        $user = $DB->get_record_sql("SELECT id, lastname, firstname FROM {user} WHERE id=$teach->teacherid");
                        
                        /*
                        if ($kid > 0)   {
                            $strselect1 = " AND subdepartmentid=$kid";
                        }
                        */
                        
                        $sn1 = '-';
                        if ($teachers = $DB->get_records_select('bsu_teacher_subdepartment', "teacherid=$teach->teacherid $strselect1", null, '', 'id, positionid'))    {
                            $teacher1 = reset($teachers);
                    	    if (isset($positionshort[$teacher1->positionid])) {
                      		    $sn1 = $positionshort[$teacher1->positionid];
                            } 
                        }
                        $ateachers[] = fullname($user) . ' (' . $sn1 . ')';
                    }
                    // $strteachers .= ' ' . $fullname . '<br>';
                    $strteachers = implode (', <br /><br />', $ateachers); 
                } else {
                	$strteachers = '-'; // get_string('teachernotset', 'block_bsu_plan');
             	}
                
                $hours = get_hours_practice_from_charge($yid, $plan->id, $practice->id);
                
                $table->data[] = array($i++. '.', $practicetype[$practice->practicetypeid], $practice->name, 
                                        $practice->term, $practice->week, $practiceformula[$practice->practicetypeid],
                                        $strsubdep, $strteachers, $strgroups, $strsubgroup, $strstud, $hours, $strlinkupdate); // $strsubgroups,
            } else {
                
                if (is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                    $strlinkupdate = "<a href='editpractice.php?action=edit&{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
                	$strlinkupdate .= "<a href='editpractice.php?action=delete&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                    $strlinkupdate .= '<br>';
                }
                
                $table->data[] = array($i++. '.', $practicetype[$practice->practicetypeid], $practice->name, 
                                        $practice->term, $practice->week, $practiceformula[$practice->practicetypeid], $strlinkupdate); // $strsubgroups,
                
            } 
        }
        
    }
    

    return $table;  
}



function table_specvidrabot0($yid, $fid, $plan, $agroups, $currtab)
{
    global $CFG, $DB, $OUTPUT, $USER, $ACCESS_USER;  
    
    $context = get_context_instance(CONTEXT_FACULTY, $fid);
    $editcapability = has_capability('block/bsu_plan:editcurriculum', $context);
    
    $currtab2 = substr($currtab, 0, 4);
    $positionshort = $DB->get_records_menu('bsu_staffpositions', null, '', 'id, ir_name');    
    
    $edworkkind = $DB->get_records_menu('bsu_ref_edworkkind', null, '', 'id, name');
    $edworkkind[10] = 'КОНСгак'; 
    $edworkkind[100] = 'РМР';
      
    $planid = $plan->id;
    $table = new html_table();
    
    if ($currtab2 == 'plan')    {
        $table->head = array (get_string('npp', 'block_bsu_plan'),
                              'Вид работы',
                              'Название',
                              get_string('term', 'block_bsu_plan'),
                              get_string('formula', 'block_bsu_plan'),
                              get_string('actions'));
        $table->align = array ('center', 'center', 'left', 'center', 'center');
        $table->columnwidth = array (4, 14, 50, 10, 10, 10);
    } else {
        $table->head = array (get_string('npp', 'block_bsu_plan'),
                              'Вид работы',
                              'Название',
                              get_string('term', 'block_bsu_plan'),
                              get_string('formula', 'block_bsu_plan'),
                              'Кафедра(ы)',
                              'Преподаватели', 
                              'Группа(ы)',                          
                              'Кол-во<br />студентов',
                              'Всего часов<br>нагрузки',
                              get_string('actions'));
        $table->align = array ('center', 'center', 'left', 'center', 'center', 'left', 'left', 'center',  'center', 'center', 'center');
        $table->columnwidth = array (4, 14, 50, 10, 10, 22, 27, 30, 17, 9, 8, 10, 5);
        
    }
    
   	$table->titlesrows = array(30, 30);
    $table->titles = array();
    $table->titles[] = get_string('curriculum', 'block_bsu_plan') . ' ' . $plan->id . '. '. $plan->pname; 
	$table->titles[] = get_string('speciality', 'block_bsu_plan') . ' ' . $plan->sname;
    $table->downloadfilename = "specvidrabot_{$fid}_{$plan->id}";
    $table->worksheetname = $table->downloadfilename;
    
    // $table->width = "70%";
    // id, planid, practicetypeid, name, term, week
    $terms = get_terms_group($yid, $agroups);    
      
    if ($specvidrabots = $DB->get_records_select('bsu_plan_practice', "planid = $planid AND edworkkindid<>13", null, 'term, edworkkindid'))  {
        $i = 1;
        foreach ($specvidrabots as $specvidrabot)   {
            
           $formula = get_calcfunc_text($specvidrabot->edworkkindid); 
           $link = "yid=$yid&fid=$fid&pid=$plan->id&prid={$specvidrabot->id}&term=101&eid=$specvidrabot->edworkkindid&tab=$currtab";

           if ($currtab2 == 'kurs')    {
                $kurs = substr($currtab, 4, 1);
                $termodd = 2*$kurs - 1;
                $termeven = 2*$kurs;
                if ($specvidrabot->term != $termodd && $specvidrabot->term != $termeven) continue;
            
                $sql = "SELECT sd.id, sd.name, ps.countsubgroups, ps.countstudents
                        FROM mdl_bsu_plan_practice_subdep ps
                        inner join mdl_bsu_vw_ref_subdepartments sd on sd.id=ps.subdepartmentid
                        where ps.yearid = $yid AND  ps.practiceid = $specvidrabot->id";
                $strsubdep = $strsubgroup = $strstud = '';
                $kid = 0;        
                if ($kafedri = $DB->get_records_sql($sql)) {
                    foreach ($kafedri as $kaf) {
                        $strsubdep .= $kaf->name . '<br />';
                        $kid =  $kaf->id;
                        $strsubgroup .= $kaf->countsubgroups . '<br />'; 
                        $strstud .= $kaf->countstudents . '<br />';
                    }
                } else {
                    $strsubdep = get_string('kafedranotset', 'block_bsu_plan');    
                }
    
                $strgroups = '';
                foreach ($agroups as $group)    {
                    if (in_array($specvidrabot->term, $terms[$group] ))   {
                        $cntstud = $DB->get_field_select('bsu_ref_groups', 'countstud', "name = '$group'");
                        $strgroups .= $group . "({$cntstud}ст.)<br />";
                    }
                }        
                   
                
                $strlinkupdate = '';
                if ((strpos($CFG->editplanopenedforyid, "$yid") !== false && $editcapability) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                    $strsubdep = "<a href=\"subdeppractice.php?action=new&{$link}&kid=$kid\">$strsubdep</a>";
                    $strlinkupdate .= "<a href='editpractice.php?action=editspec&{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
                	$strlinkupdate .= "<a href='editpractice.php?action=deletespec&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                    if (!empty($strgroups)) {
                        if (count($kafedri) > 1)   {
                            $numkaf=1;
                            foreach ($kafedri as $kaf) {
                                $link2 = $CFG->wwwroot . "/blocks/bsu_charge/practicecharge.php?{$link}&sid=$kaf->id";
                                $strlinkupdate .= "<br />$numkaf:<a href='$link2'><img class='icon' title='Посмотреть нагрузку по данному виду работы по $numkaf-й кафедре' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                                $numkaf++;
                            }
                        } else {
                            $link2 = $CFG->wwwroot . "/blocks/bsu_charge/practicecharge.php?{$link}&sid=$kid";
                            $strlinkupdate .= "<a href='$link2'><img class='icon' title='Посмотреть нагрузку по данному виду работы' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                        }    
                    }    
                    $strlinkupdate .= '<br>';
                    if (!empty($strstud))   {
                        $strstud = "<b><a href=\"subdeppractice.php?action=new&{$link}&kid=$kid\">$strstud</a></b>";
                    }
                    if (!empty($strsubgroup))   {
                        $strsubgroup = "<b><a href=\"subdeppractice.php?action=new&{$link}&kid=$kid\">$strsubgroup</a></b>";
                    }
                    
                } else {
                    if (!empty($strgroups)) {
                        $link2 = $CFG->wwwroot . "/blocks/bsu_charge/practicecharge.php?{$link}&sid=$kid";
                        $strlinkupdate .= "<a href='$link2'><img class='icon' title='Посмотреть нагрузку по данному виду работы' src='".$OUTPUT->pix_url('i/charge')."'></a>";
                    }
                }        
                
    
                if ($CFG->stopeditingplan)  {
                    $strlinkupdate = "<img class='icon' title='Редактирование запрещено, т.к. данный вид работы уже пройден группами старших курсов' src='".$OUTPUT->pix_url("i/unlock")."'>";
                }
    
                $strteachers = $strselect1 = '';
                $sql = "SELECT distinct teacherid FROM mdl_bsu_edwork e
                        inner join mdl_bsu_teachingload t on e.edworkmaskid=t.edworkmaskid
                        where e.practiceid=$specvidrabot->id AND e.yearid=$yid";
    
           	 	$teachers = $DB->get_records_sql($sql);
                if ($teachers)  {
                    $ateachers = array();
                    foreach ($teachers as $teach)  {
                        $user = $DB->get_record_sql("SELECT id, lastname, firstname FROM {user} WHERE id=$teach->teacherid");
                        
                        /*
                        if ($kid > 0)   {
                            $strselect1 = " AND subdepartmentid=$kid";
                        }
                        */
                        
                        $sn1 = '-';
                        if ($teachers = $DB->get_records_select('bsu_teacher_subdepartment', "teacherid=$teach->teacherid $strselect1", null, '', 'id, positionid'))    {
                            $teacher1 = reset($teachers);
                    	    if (isset($positionshort[$teacher1->positionid])) {
                      		    $sn1 = $positionshort[$teacher1->positionid];
                            } 
                        }
                        $ateachers[] = fullname($user) . ' (' . $sn1 . ')';
                    }
                    // $strteachers .= ' ' . $fullname . '<br>';
                    $strteachers = implode (', <br /><br />', $ateachers); 
                } else {
                	$strteachers = '-'; // get_string('teachernotset', 'block_bsu_plan');
             	}   
                
                $hours = get_hours_practice_from_charge($yid, $plan->id, $specvidrabot->id);
   
                // $practiceformula[$specvidrabot->edworkkindid]
                $table->data[] = array($i++. '.', $edworkkind[$specvidrabot->edworkkindid], $specvidrabot->name,
                                        $specvidrabot->term,  $formula, $strsubdep, $strteachers, 
                                        $strgroups, $strstud, $hours, $strlinkupdate); // $strsubgroups,
            } else {
                
                if (is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                    $strlinkupdate = "<a href='editpractice.php?action=editspec&{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
                	$strlinkupdate .= "<a href='editpractice.php?action=deletespec&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                    $strlinkupdate .= '<br>';
                }
                
                $table->data[] = array($i++. '.', $edworkkind[$specvidrabot->edworkkindid], $specvidrabot->name, $specvidrabot->term, $formula, $strlinkupdate); 
                
            } 
        }
        
    }
    

    return $table;  
}


function table_semestr_kontrol($yid, $fid, $plan, $agroups)
{
    global $DB;
    
    $planid = $plan->id;

    $numweeks = array();
    if ($grafiks = $DB->get_records_select('bsu_plan_grafikuchprocess', "planid = $planid")) {
        foreach ($grafiks as $grafik)   {
            $i1 = $grafik->numkurs * 2 - 1;
            $i2 = $grafik->numkurs * 2;
            $numweeks[$i1] = $grafik->numweekautumn; 
            $numweeks[$i2] = $grafik->numweekspring;
        }
    }
 
 	$sql = "SELECT 1 as i, max(b.numsemestr) as maxsem FROM {bsu_discipline} a
            inner join {bsu_discipline_semestr} b on a.id=b.disciplineid
            where a.planid=$planid";
    if ($max = $DB->get_records_sql($sql))  {
         $maxsem = $max[1]->maxsem;
    } else {
         $maxsem = 0;
    }

    $table = new html_table();
    $table->dblhead = new stdClass();
    $table->dblhead->head1  = array ('Обязательные элементы / Семестры <br /> <small> (без учета Физической культуры)</small>');
    $table->dblhead->span1  = array ("rowspan=2"); // , "rowspan=2", "colspan=5", "rowspan=2"
    $table->dblhead->head2  = array ();
    
    /*
    $table->dblhead->head2  = array ( get_string ('nameappointmnt','block_mou_att'), get_string ('qqualify','block_mou_att') . ',' . get_string("qqualify_date", 'block_mou_att'),  
                                      get_string ('qualifynow','block_mou_att'), get_string ('ddatecertifiable','block_mou_att'),  
                                      get_string('total_mark', 'block_mou_att'));
	$table->align = array ('center', 'left', 'left', 'center', 'center', 'center', 'center', 'center');
    $table->size = array ('5%', '20%', '15%', '10%', '10%', '5%', '5%', '5%');
	$table->columnwidth = array (5, 20, 10, 10, 10, 10, 10, 10);
    $table->class = 'moutable';
   	$table->width = '80%';
    */
    
    
    $table->align = array('left');

    $toprow = array();
    for ($i=1; $i<=$maxsem; $i++)   {
        $table->dblhead->head1[] = '<b>'.$i.'-й сем.</b>';
        $table->dblhead->span1[] = "colspan=3";
        $table->dblhead->head2[] = 'min';         $table->align[] = 'center';
        $table->dblhead->head2[] = 'max';         $table->align[] = 'center';       
        $table->dblhead->head2[] = 'план';        $table->align[] = 'center';
    }


   $maxhours = $DB->get_records_select_menu('bsu_plan_weeks_hours', "planid = $planid", null, '', 'term, maxh');
   $minhours = $DB->get_records_select_menu('bsu_plan_weeks_hours', "planid = $planid", null, '', 'term, minh');

   $sql = "SELECT s.id as ssid, d.id as did, d.disciplinenameid, 
            s.numsemestr, s.lection, s.praktika, s.lab   
            FROM mdl_bsu_discipline_semestr s
            INNER JOIN mdl_bsu_discipline d ON d.id=s.disciplineid
            WHERE d.planid=$planid and d.notusing=0";
                // group by d.id";
    // print $sql . '<br />';                
    if ($datas = $DB->get_records_sql($sql))  {
        // print_object($datas);

        $tabledata = array('Обязательных уч. часов в неделю <br />(всего часов / кол. нед.)');
        // if ($term=2)    {
        for ($term=1; $term<=$maxsem; $term++)   {
            $cnlek = $cntlab = $cntpr = 0;
    		foreach ($datas as $discipline) 	{
    		    // print "$discipline->numsemestr == $term<br />"; 
                if ($discipline->numsemestr == $term && $discipline->disciplinenameid != 51)   {
                    // notify ("$discipline->numsemestr == $term!!!!!!!!");
                    $cnlek += $discipline->lection;
                    $cntlab += $discipline->lab;
                    $cntpr += $discipline->praktika; 
                }
    		}
            $all = $cnlek + $cntlab + $cntpr;
            
            if (isset($numweeks[$term])) {
                $nw = $numweeks[$term];
            } else {
                $nw = 0;    
            } 
            
            if ($nw > 0)    {
                $hw = round ($all/$nw);
            } else {
                $hw = '-';
            }

            $hoursmin = 0;
            if (!empty($minhours))  {
                $hoursmin = $minhours[$term];    
            }
            $tabledata[] = "<input type=text name=minh_{$term} size=1 value=\"$hoursmin\">";

            $hoursmax = 0;
            if (!empty($maxhours))  {
                $hoursmax = $maxhours[$term];    
            }
            $tabledata[] = "<input type=text name=maxh_{$term} size=1 value=\"$hoursmax\">";
            
            if ($hoursmin <= $hw && $hw <= $hoursmax)  {
                $str = '<font color=green><b>'.$hw.'</b></font>';
            }   else {
                $str = '<font color=red><b>'.$hw.'</b></font>';
            }  
            
            $tabledata[] = $str . " <small>($all/$nw)</small>";
        }
        $table->data[] = $tabledata;
    }    
                
    $sql = "SELECT d.id as did, d.disciplinenameid, d.semestrexamen, d.semestrzachet, d.semestrkursovik, d.semestrkursovik, 
                   d.semestrdiffzach, d.semestrkp, d.semestrref, d.semestrkontr
            FROM mdl_bsu_discipline d
            WHERE d.planid=$planid and d.notusing=0";
    // print $sql . '<br />';                
    if ($datas = $DB->get_records_sql($sql))  {
        $tabledata = get_count_form_kontrol_in_disicpline('Обязательных экзаменов', $datas, $maxsem, 'semestrexamen');
        $table->data[] = $tabledata;        

        $tabledata = get_count_form_kontrol_in_disicpline('Обязательных зачетов', $datas, $maxsem, 'semestrzachet');
        $table->data[] = $tabledata;        

        $tabledata = get_count_form_kontrol_in_disicpline('Обязательных зачетов с оценкой', $datas, $maxsem, 'semestrdiffzach');
        $table->data[] = $tabledata;        

        $tabledata = get_count_form_kontrol_in_disicpline('Обязательных курсовых работ', $datas, $maxsem, 'semestrkursovik');
        $table->data[] = $tabledata;        

        $tabledata = get_count_form_kontrol_in_disicpline('Обязательных курсовых проектов', $datas, $maxsem, 'semestrkp');
        $table->data[] = $tabledata;        
    }
   
    return $table;    
}




function get_count_form_kontrol_in_disicpline($title, $disciplines, $maxsem, $semestrkontrol)
{
    $tabledata = array($title);
    // if ($term=2) {
    for ($term=1; $term<=$maxsem; $term++)   {
        $cntex = 0;
		foreach ($disciplines as $discipline) 	{
            // if ($discipline->numsemestr == $term && $discipline->disciplinenameid != 51)   {
            if ($discipline->disciplinenameid != 51)   {                    
                // print $discipline->semestrexamen . '<br />';
                if ($count = is_formskontrol_in_term($discipline->{$semestrkontrol}, $term))   {
                    $cntex +=$count;
                }        
            }
		}
        $tabledata[] = '';
        $tabledata[] = '';
        $tabledata[] = $cntex;
    }
    
    return $tabledata;
} 


function plan_table_grafikuchprocess($planid)
{ 
    global $DB;

    $table = new html_table();
    $table->head  = array('Номер курса', 'Кол-во недель (осенний семестр)', 'Кол-во недель (весенний семестр)', 'График уч. процесса');
    $table->data = array();

    $textsql = "SELECT id, numkurs, numweekspring, numweekautumn, grafik
                FROM {bsu_plan_grafikuchprocess} 
                WHERE planid = {$planid}";

    if($grafikuchprocess = $DB->get_records_sql($textsql)){
        foreach($grafikuchprocess AS $grafikuchproces){
            $field1 = $grafikuchproces->numkurs;
            $field2 = $grafikuchproces->numweekautumn;
            $field3 = $grafikuchproces->numweekspring;
            $field4 = $grafikuchproces->grafik ;
            $table->data[] =   array($field1, $field2, $field3, $field4);
        }
    }
    
    return $table;
        
}


function table_all_disciplines($yid, $fid, $plan, $delemiter = '<br>')
{
    global $CFG, $DB, $OUTPUT, $USER, $ACCESS_USER;
    
    $table = new html_table();
    $table->head = array (get_string('npp', 'block_bsu_plan'),
                          get_string('cyclename', 'block_bsu_plan'),
                          'Блок', // get_string('identificatordiscipline', 'block_bsu_plan'),
                          get_string('discipline', 'block_bsu_plan'),
                          get_string('term', 'block_bsu_plan'),
                          get_string('formskontrol', 'block_bsu_plan'),
                          get_string('lection', 'block_bsu_plan'),
                          get_string('praktika', 'block_bsu_plan'),
                          get_string('lab', 'block_bsu_plan'),
                          get_string('ksr', 'block_bsu_plan'),
                          get_string('srs', 'block_bsu_plan'),
                          'СР (всего)',
                          get_string('mustlearning', 'block_bsu_plan'),
                           'ЗЕТ',                          
                          get_string('actions'));
    $table->align = array ('center', 'left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->width = "80%";
    $table->columnwidth = array (4, 10, 11, 60,  10, 10, 5, 5, 5, 5, 5, 5, 10, 5);
	// $table->class = 'userenrolment';
    // $table->attributes['class'] = 'userenrolment';

   	$table->titlesrows = array(30, 30);
    $table->titles = array();
    $table->titles[] = get_string('curriculum', 'block_bsu_plan') . ' ' . $plan->id . '. '. $plan->pname; 
	$table->titles[] = get_string('speciality', 'block_bsu_plan') . ' ' . $plan->sname;
    $table->downloadfilename = "plan_{$fid}_{$plan->id}";
    $table->worksheetname = $table->downloadfilename;
    $table->data = array();
    $table->errormsg = '';
    
    switch ($plan->kvalif)    {
        case 2: $fs = 'Б';
                $cycles = array($fs.'1', $fs.'2', $fs.'3', $fs.'3+', $fs.'4', 'ФТД');
                $cycles2 = array($fs.'1', $fs.'2', $fs.'3');
        break;
        
        case 3: $fs = 'С';
                $cycles = array($fs.'1', $fs.'2', $fs.'3', $fs.'3+', $fs.'4', 'ФТД');
                $cycles2 = array($fs.'1', $fs.'2', $fs.'3');
        break;
        case 4: $fs = 'М';
                $cycles = array($fs.'1', $fs.'2', 'НИР', 'ФТД');
                $cycles2 = array($fs.'1', $fs.'2');
        break;
        default: $fs = '';
        break;
        
    }
   
    $flag  = false;
    $sql = "SELECT d.id as did, d.cyclename FROM mdl_bsu_discipline d INNER JOIN mdl_bsu_plan p ON p.id=d.planid
            WHERE p.id={$plan->id} and d.cyclename = '{$fs}1'";
    if ($DB->record_exists_sql($sql))   {
        $flag  = true;
    } else {
        $sql = "SELECT d.id as did, d.cyclename FROM mdl_bsu_discipline d INNER JOIN mdl_bsu_plan p ON p.id=d.planid
                WHERE p.id={$plan->id} and d.cyclename = '{$fs}1.Б'";
        if ($DB->record_exists_sql($sql))   {
            $flag  = true;
         }    
    } 
    
    if ($flag)  {
        $flag = check_empty_identificatordiscipline($plan->id);
    }   
        
    if ($flag) {    
        
        $select = "planid = $plan->id AND cyclename <> 'ИГА' AND cyclename <> 'Практики'"; 
        if ($planminzet = $DB->get_records_select_menu('bsu_plan_minmaxzet', $select, null, 'cyclename', 'cyclename, minzet')) {
            // ksort($planminzet);
            $minzet = array();       
            foreach ($planminzet as $cycl => $pzet)  {
                $index = str_replace('_', '.', $cycl);
                $minzet[$index] = $pzet;                
            }
            // print_object($planminzet);
        }  else {
            $minzet = $DB->get_records_menu('bsu_ref_minmaxzet', array('kvalifid' => $plan->kvalif), 'id', 'cyclename, minzet');            
        }      
        // print_object($minzet);

        if ($planminzet = $DB->get_records_select_menu('bsu_plan_minmaxzet', $select, null, 'cyclename', 'cyclename, maxzet')) {
            // ksort($planminzet);
            $maxzet = array();
            foreach ($planminzet as $cycl => $pzet)  {
                $index = str_replace('_', '.', $cycl);
                $maxzet[$index] = $pzet;                
            }
            // print_object($planminzet);
        }  else {
            $maxzet = $DB->get_records_menu('bsu_ref_minmaxzet', array('kvalifid' => $plan->kvalif), 'id', 'cyclename, maxzet');            
        }      
        // print_object($maxzet);

        
        
        $sql = "create temporary table mdl_temp_discipline
                SELECT d.id as did, p.id as pid, d.disciplinenameid, n.name as nname, d.cyclename, d.identificatordiscipline, d.sr,
                d.semestrexamen, d.semestrzachet, d.semestrkursovik, d.semestrdiffzach, d.semestrkp, d.notusing, d.semestrref, d.semestrkontr,
                d.mustlearning, d.creditov, round (d.mustlearning/36) as zetcalc
                FROM mdl_bsu_discipline d
                INNER JOIN mdl_bsu_plan p ON p.id=d.planid
                INNER JOIN mdl_bsu_ref_disciplinename n ON n.id=d.disciplinenameid
                WHERE p.id={$plan->id}
                ORDER BY d.identificatordiscipline, d.cyclename";
        // echo $sql . '<br />';                
        $DB->Execute($sql);
 
        $sumzetallcycles = get_sumzetallcycles($cycles2);
        // print_object($sumzetallcycles); 
        foreach ($cycles as $cycle) {
            
            $itogo = array();
            $itogo[$cycle] = new stdClass();
            $itogo[$cycle]->sumhours = 0;
            $itogo[$cycle]->sumzet = 0;
            
            if ($cycle == 'ФТД')  {
                    $sql = "select * from mdl_temp_discipline
                            where identificatordiscipline like '{$cycle}%'";
                    // echo $sql . '<br />';        
                    if ($datas = $DB->get_records_sql($sql))  {
                        $tabledata = get_table_data_for_all_plan($yid, $datas, $fid, $plan, $delemiter);                               
                        $sql = "select sum(mustlearning) as sumhours, sum(creditov) as sumzet
                                from mdl_temp_discipline where notusing=0 and identificatordiscipline like '{$cycle}%'";
                        if ($sums = $DB->get_record_sql($sql))  {
                           // echo $sql . '<br />';
                           if(empty($sums->sumzet)) { 
                                $sums->sumzet = 0;
                                $sums->sumhours = 0;
                           }     
                           // print_object($sums);  
                           
                           $strzet = get_print_string_minmaxzet($cycle, $sums->sumzet, $minzet, $maxzet, $table);
                	       $tabledata[] = array('', '', '', '<strong>Всего по ' . $cycle . '</strong>', 
                                                '',                        '',                       '',                       '', 
                                                '',                        '',                       '',                       '', 
                                                '<strong>'.$sums->sumhours.'</strong>', '<strong>'.$sums->sumzet.'</strong>', $strzet);
                        }                        
                        foreach ($tabledata as $td) {
                            $table->data[] = $td;
                        }    
                    }                                   
            } else {            
            
                if ($uniquesecondsymbols = get_unique_identificatordiscipline($cycle))  {
                    // print_object($uniquesecondsymbols);  
                    foreach ($uniquesecondsymbols as $us => $value) {
                        $mask = $cycle . '.' . $us . '.';
                        $itogo[$mask] = new stdClass();
                        $itogo[$mask]->sumhours = 0;
                        $itogo[$mask]->sumzet = 0;
                        $sql = "select * from mdl_temp_discipline
                                where identificatordiscipline like '{$mask}%'"; // 
                        // echo $sql . '<br />';                                 
                        if ($datas = $DB->get_records_sql($sql))  {
                            $tabledata = get_table_data_for_all_plan($yid, $datas, $fid, $plan, $delemiter);                               
                        
                            if ($us == 'Б' || $us == 'В')   {
                                $sql = "select sum(mustlearning) as sumhours, sum(creditov) as sumzet
                                        from mdl_temp_discipline where notusing=0 and identificatordiscipline like '{$mask}%'";
                            } else {
                                $sql = "select mustlearning as sumhours, creditov as sumzet
                                        from mdl_temp_discipline where notusing=0 and identificatordiscipline like '{$mask}%' limit 1";
                            }            

                            if ($sums = $DB->get_record_sql($sql))  {
                               if ($us == 'В' )   {
                                    $m = $cycle . '.ВДВ';
                                    // notify ($m);
                                    if (isset($sumzetallcycles[$m]))    {
                                        // notify ($sumzetallcycles[$m]->sumzet);
                                        $strzet = get_print_string_minmaxzet($mask, $sumzetallcycles[$m]->sumzet, $minzet, $maxzet, $table);
                                    }
                               } else {
                                    $strzet = get_print_string_minmaxzet($mask, $sums->sumzet, $minzet, $maxzet, $table);
                               }       
                               $itogo[$mask]->sumhours = $sums->sumhours;
                               $itogo[$mask]->sumzet = $sums->sumzet;  
                    	       $tabledata[] = array('', '', '', '<strong>Всего по ' . $mask . '</strong>', 
                                                    '',                        '',                       '',                       '', 
                                                    '',                        '',                       '',                       '', 
                                                    '<strong>'.$sums->sumhours.'</strong>', '<strong>'.$sums->sumzet.'</strong>', $strzet);
    
                            }
                            foreach ($tabledata as $td) {
                                $table->data[] = $td;
                            }    
                        }                   
                    }  
                } 
                // print_object ($itogo);
                foreach ($itogo as $itg)    {
                    $itogo[$cycle]->sumhours += $itg->sumhours;  
                    $itogo[$cycle]->sumzet += $itg->sumzet;
                }     
                $strzet = get_print_string_minmaxzet($cycle, $itogo[$cycle]->sumzet, $minzet, $maxzet, $table);
    	       $table->data[] =  array('', '', '', '<strong>ИТОГО ПО ЦИКЛУ ' . $cycle . '</strong>', 
                                    '',                        '',                       '',                       '', 
                                    '',                        '',                       '',                       '', 
                                    '<strong>'.$itogo[$cycle]->sumhours.'</strong>', '<strong>'.$itogo[$cycle]->sumzet.'</strong>', $strzet);
                
                /*
                $sql = "select sum(mustlearning) as sumhours, sum(creditov) as sumzet
                        from mdl_temp_discipline where identificatordiscipline like '{$cycle}%'";
                if ($sums = $DB->get_record_sql($sql))  {
        	       $table->data[] =  array('', '', '', '<strong>ИТОГО ПО ЦИКЛУ ' . $cycle . '</strong>', 
                                        '',                        '',                       '',                       '', 
                                        '',                        '',                       '',                       '', 
                                        '<strong>'.$sums->sumhours.'</strong>', '<strong>'.$sums->sumzet.'</strong>', '');
                } 
                */                       
            }
        }
    }  else {
    
    
        $sql = "SELECT d.id as did, d.disciplinenameid, n.name as nname, d.cyclename, d.identificatordiscipline, d.sr, 
                       d.semestrexamen, d.semestrzachet, d.semestrkursovik, d.semestrdiffzach, d.semestrkp, d.notusing, d.semestrref, d.semestrkontr,
                       d.mustlearning, p.id as pid, d.creditov
                    FROM {bsu_discipline} d 
                    INNER JOIN {bsu_plan} p ON p.id=d.planid
                    INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                    WHERE p.id={$plan->id}
                    ORDER BY d.identificatordiscipline, d.cyclename";
              
        if ($datas = $DB->get_records_sql($sql))  {
            
            
            /*
            $basedisc = array();
            $vardisc = array(); 
            $masks = array ('В', 'ФТД', '.Р');
            foreach($datas as $data) {
                $flag = true;
                foreach ($masks as $mask)   {
                    $pos = mb_strpos($data->cyclename, $mask, 0, 'UTF-8'); 
                    if ($pos === false) continue;
                    $vardisc[] = $data;
                    $flag = false;
                    break;
                }
                if ($flag)  {
                    $basedisc[] = $data;
                }
            } 
            */   
            if ($fid == 18300 && $plan->edformid == 3 && $plan->kvalif == 3)    {
                
                foreach ($datas as $i => $d)    {
                    $datas[$i]->isfind = 0; 
                }
                $datas1 = array();
                $masks = array ('С1.Б.', 'С2.Б.', 'С3.Б.', 'С1.В.', 'С2.В.', 'С3.В.', 'С1.ДВ', 'С3.ДВ', 'С3+.Б.', 'РП.Б.', 'ФТД.');
                foreach ($masks as $mask)   {
                    foreach($datas as $i => $data) {
                        $flag = true;
                        $pos = mb_strpos($data->identificatordiscipline, $mask, 0, 'UTF-8'); 
                        if ($pos === false) continue;
                        $datas1[] = $data;
                        $datas[$i]->isfind = 1;
                    }    
                }
                foreach ($datas as $i => $d)    {
                    if ($datas[$i]->isfind == 0)    {
                        $datas1[] = $d;
                    } 
                }
            } else {
                $datas1 = $datas;
            }
            
               /*
                if ($k==1) $datas1 = $basedisc;
                else $datas1 = $vardisc;
                */
                    
            $table->data = get_table_data_for_all_plan($yid, $datas1, $fid, $plan, $delemiter);
        }
    }
    return $table; 
}




function get_table_data_for_all_plan($yid, $datas1, $fid, $plan, $delemiter)
{
    global $CFG, $DB, $OUTPUT, $USER, $ACCESS_USER;
    
    $tabledata = array();

    $i = 1;
    // print_object($datas1);
    
    foreach($datas1 as $data) {
        
        /*
        $indexes = array();
        for ($m=3; $m<=6; $m++)   {
            $indexes[] = mb_substr($data->identificatordiscipline, 0, $m, 'UTF-8');
        }
        */
        // print_object($indexes);
        /*
        foreach ($indexes as $cycle)   {
            if (isset($zettotal[$cycle]))   {
                if (!$data->notusing)   {
                    $zettotal[$cycle] += $data->mustlearning;
                }    
                // echo $cycle . '<br />';
            } 
        }
        */
        
        $strlinkupdate = '';
        $strterms = $l = $p = $lab = $ksr = $srs = $fk = $m = array();
        if ($semestrs = $DB->get_records_select('bsu_discipline_semestr', "disciplineid = $data->did", null, 'numsemestr')) {
            if ($disgrugoiplans =  $DB->get_records_select('bsu_discipline', "planid=$plan->id AND disciplinenameid=$data->disciplinenameid", null, 'id, cyclename, identificatordiscipline,disciplinenameid')) {
                if (count($disgrugoiplans) > 1) {
                    // echo "planid=$plan->id AND disciplinenameid=$data->disciplinenameid<br />";
                    foreach ($disgrugoiplans as $disgrugoiplan) {
                        if ($disgrugoiplan->id == $data->did) continue;
                            $datanname = $DB->get_field_select('bsu_ref_disciplinename', 'name', "id = $disgrugoiplan->disciplinenameid");
                            $link = "yid=$yid&fid=$fid&pid={$plan->id}&did={$disgrugoiplan->id}";
                            $strlinkupdate .= "<a href='disciplines.php?action=deldid&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";

            	            $tabledata[] = array('-', $disgrugoiplan->cyclename, $disgrugoiplan->identificatordiscipline, $datanname, 
                                                '-', 0, 0, 0, 0, 0, 0, 0, 0, 0, $strlinkupdate);
                            $strlinkupdate = '';                     

                    }
                    /*
                    $semestr0 = new stdClass();
                    $semestr0->numsemestr = 0;
                    $semestr0->lection = 0;
                    $semestr0->praktika = 0;
                    $semestr0->lab = 0;  
                    $semestr0->ksr = 0;
                    $semestr0->srs = 0;
                    $semestrs[] = $semestr0;
                    */
                }
            }    
        } else {
            // echo "disciplineid = $data->did<br />";
            /*
            $semestrs = array();
            $semestr0 = new stdClass();
            $semestr0->numsemestr = 0;
            $semestr0->lection = 0;
            $semestr0->praktika = 0;
            $semestr0->lab = 0;  
            $semestr0->ksr = 0;
            $semestr0->srs = 0;
            $semestrs[0] = $semestr0;
            */
        }       
                
        if ($semestrs)   {
            foreach ($semestrs as $semestr) {
                $strterms[] .= $semestr->numsemestr;
                // id, disciplineid, numsemestr, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz, prlecinweek, prlabinweek, prprinweek, przet
                $l[] = $semestr->lection;
                $p[] = $semestr->praktika;
                $lab[] = $semestr->lab;
                $ksr[] = $semestr->ksr;
                $srs[] = $semestr->srs;
                $m[] = $semestr->srs; 
                $allhours = $semestr->lection + $semestr->praktika + $semestr->lab + $semestr->ksr + $semestr->srs;  
                $fk[] = get_formskontrol($data->semestrexamen, $data->semestrzachet, 
                                   $data->semestrkursovik, $data->semestrdiffzach, 
                                   $semestr->numsemestr, $data->semestrkp, $allhours, $data->semestrref, $data->semestrkontr);


                // (strpos($CFG->editplanopenedforyid, "$yid") !== false)
                if (is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                    $link = "yid=$yid&fid=$fid&pid={$plan->id}&did={$data->did}&term=$semestr->numsemestr";
                    $strlinkupdate .= "<a href='editd.php?{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
                	$strlinkupdate .= "<a href='disciplines.php?action=delete&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                    if (is_siteadmin() )  {
                        $strlinkupdate .= "<a href='disciplines.php?action=edit&{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('t/editstring')."'></a>";
                    }    
                    $strlinkupdate .= '<br>';
                }
                
            }        
        }  else {
            if (is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                $link = "yid=$yid&fid=$fid&pid={$plan->id}&did={$data->did}";
                $strlinkupdate .= "<a href='disciplines.php?action=deldid&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
            }    
        }
        
       if (!empty($data->semestrexamen)) {
            $arr1 = str_split($data->semestrexamen);
            foreach ($arr1 as  $arr)    {
                $arr = hexdec($arr);
                if (!in_array($arr, $strterms)) {
                    
                    $strterms[] .= $arr;
                    // id, disciplineid, numsemestr, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz, prlecinweek, prlabinweek, prprinweek, przet
                    $l[] = 0;
                    $p[] = 0;
                    $lab[] = 0;
                    $ksr[] = 0;
                    $srs[] = 0;
                    $m[] = 0; 
                    $allhours = 0;  
                    $fk[] = 'экз.';
                    /*
                    foreach ($indexes as $cycle)   {
                        if (isset($zettotal[$cycle]))   {
                            $zettotal[$cycle] += 36;
                        } 
                    }
                    */
                }    
            }
        }            
                

       if (!empty($data->semestrzachet)) {
            $arr1 = str_split($data->semestrzachet);
            foreach ($arr1 as  $arr)    {
                $arr = hexdec($arr);
                if (!in_array($arr, $strterms)) {
                    
                    $strterms[] .= $arr;
                    // id, disciplineid, numsemestr, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz, prlecinweek, prlabinweek, prprinweek, przet
                    $l[] = 0;
                    $p[] = 0;
                    $lab[] = 0;
                    $ksr[] = 0;
                    $srs[] = 0;
                    $m[] = 0; 
                    $allhours = 0;  
                    $fk[] = 'зач.';
                }    
            }
        }            
       
        if (!empty($data->semestrkursovik)) {
            $arr1 = str_split($data->semestrkursovik);
            foreach ($arr1 as  $arr)    {
                $arr = hexdec($arr);
                if (!in_array($arr, $strterms)) {
                    $strterms[] .= $arr;
                    // id, disciplineid, numsemestr, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz, prlecinweek, prlabinweek, prprinweek, przet
                    $l[] = 0;
                    $p[] = 0;
                    $lab[] = 0;
                    $ksr[] = 0;
                    $srs[] = 0;
                    $m[] = 0; 
                    $allhours = 0;  
                    $fk[] = 'курс.раб.';
                }
            }        
        }

        if (!empty($data->semestrkp)) {
            $arr1 = str_split($data->semestrkp);
            foreach ($arr1 as  $arr)    {
                $arr = hexdec($arr);
                if (!in_array($arr, $strterms)) {
                    $strterms[] .= $arr;                
                    // id, disciplineid, numsemestr, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz, prlecinweek, prlabinweek, prprinweek, przet
                    $l[] = 0;
                    $p[] = 0;
                    $lab[] = 0;
                    $ksr[] = 0;
                    $srs[] = 0;
                    $m[] = 0; 
                    $allhours = 0;  
                    $fk[] = 'к.п.';
                }
           }         
        }
        
        // print_object($table->data);
        /*
        $enddata = end($table->data);
        // print_object($enddata);
        

        $endindex6 = mb_substr($enddata[2], 0, 6, 'UTF-8');
        $currindex6 = mb_substr($data->identificatordiscipline, 0, 6, 'UTF-8');
        $tabledata6 = array();
        if ($currindex6 != $endindex6 && isset($zettotal[$endindex6])) {
        	   $tabledata6 = array('', '', '', '<strong>Всего по ' . $endindex6 . '</strong>', 
               '',                        '',                       '',                       '', 
               '',                        '',                       '',                       '', 
               '<strong>'.$zettotal[$endindex6].'</strong>', '');
        }         

        $endindex5 = mb_substr($enddata[2], 0, 5, 'UTF-8');
        $currindex5 = mb_substr($data->identificatordiscipline, 0, 5, 'UTF-8');
        $tabledata5 = array();
        if ($currindex5 != $endindex5 && isset($zettotal[$currindex5]) && isset($zettotal[$endindex5])) {
        	   $tabledata5 = array('', '', '', '<strong>Всего по ' . $endindex5 . '</strong>', 
               '',                        '',                       '',                       '', 
               '',                        '',                       '',                       '', 
               '<strong>'.$zettotal[$endindex5].'</strong>', '');
        }
        
        $endindex3 = mb_substr($enddata[2], 0, 3, 'UTF-8');
        $currindex3 = mb_substr($data->identificatordiscipline, 0, 3, 'UTF-8');
        // echo "$currindex3 != $endindex3 <br />";
        $tabledata3 = array();
        if ($currindex3 != $endindex3 && isset($zettotal[$currindex3]) && isset($zettotal[$endindex3])) {
        	   $tabledata3 = array('', '', '', '<strong>Итого по циклу ' . $endindex3 . '</strong>', 
               '',                        '',                       '',                       '', 
               '',                        '',                       '',                       '', 
               '<strong>'.$zettotal[$endindex3].'</strong>', '');
        } 
        
        if (!empty($tabledata6)) {
            $table->data[] = $tabledata6; 
        }
        if (!empty($tabledata5)) {
            $table->data[] = $tabledata5; 
        }             
        if (!empty($tabledata3)) {
            $table->data[] = $tabledata3; 
        }
        */
       
        if (!$data->notusing)   {
    	   $tabledata[] = array($i, $data->cyclename, $data->identificatordiscipline, $data->nname, 
                               implode ($delemiter, $strterms), 
                               implode ($delemiter,$fk), 
                               implode ($delemiter,$l), 
                               implode ($delemiter,$p), 
                               implode ($delemiter,$lab), 
                               implode ($delemiter,$ksr), 
                               implode ($delemiter,$srs),
                               $data->sr, 
                               $data->mustlearning, 
                               $data->creditov,
                               $strlinkupdate);
        } else {
    	   $tabledata[] = array('<font color=gray>'.$i . '</font>', '<font color=gray>'.$data->cyclename. '</font>', 
                               '<font color=gray>'.$data->identificatordiscipline. '</font>', '<font color=gray>'.$data->nname . '</font>', 
                               '<font color=gray>'.implode ($delemiter, $strterms). '</font>', 
                               '<font color=gray>'.implode ($delemiter,$fk). '</font>', 
                               '<font color=gray>'.implode ($delemiter,$l). '</font>', 
                               '<font color=gray>'.implode ($delemiter,$p). '</font>', 
                               '<font color=gray>'.implode ($delemiter,$lab). '</font>', 
                               '<font color=gray>'.implode ($delemiter,$ksr). '</font>', 
                               '<font color=gray>'.implode ($delemiter,$srs). '</font>',
                               '<font color=gray>'.$data->sr. '</font>', 
                               '<font color=gray>'.$data->mustlearning. '</font>',
                               '<font color=gray>'.$data->creditov. '</font>', 
                               $strlinkupdate);
        }                                       
        $i++;
    }
    /*
    if (isset($zettotal[$currindex3])) {
        	   $table->data[] = array('', '', '', '<strong>Итого по циклу ' . $endindex3 . '</strong>', 
               '',                        '',                       '',                       '', 
               '',                        '',                       '',                       '', 
               '<strong>'.$zettotal[$endindex3].'</strong>', '');
    }
    print_object($zettotal);
    
	$table->data[] = array('<hr>', '<hr>', '<hr>', '<hr>', '<hr>', '<hr>',
                           '<hr>', '<hr>', '<hr>',  '<hr>', '<hr>', 
                           '<hr>', '<hr>');

    */
    return $tabledata;            
}
  
  
function get_unique_identificatordiscipline($cycle)
{
    global $DB;
/*    
    $sql = "select distinct SUBSTRING_INDEX(SUBSTRING_INDEX(identificatordiscipline, '.', -2),  '.', 1) as b
            from mdl_temp_discipline
            where identificatordiscipline like '{$cycle}%'";
          
    $lists = $DB->get_records_sql($sql);
*/
    $sql = "select distinct identificatordiscipline
            from mdl_temp_discipline
            where identificatordiscipline like '{$cycle}%'";
          
    $alists = $DB->get_records_sql($sql);
    $lists = array();
    foreach ($alists as $al)    {
        $b = explode('.', $al->identificatordiscipline);
        if (isset($b[1]))   {
            $lists[$b[1]] = $b[1];  
        }   
    }

         
    // print_object($lists);

    return $lists;
}    


function get_print_string_minmaxzet($mask, $sumzet, $minzet, $maxzet, &$table) 
{
 
    $strzet = '';
    
    if (isset($minzet[$mask]))   {
      /// echo $minzet[$mask] . '<br />';
      if ($minzet[$mask] == $sumzet)    {
           $strzet = '<font color=green><b>' .  "$minzet[$mask] = $sumzet" . '</b></font>';
      } else if ($sumzet ==  $maxzet[$mask])  {
           $strzet = '<font color=green><b>' .  "$maxzet[$mask] = $sumzet" . '</b></font>';
      } else if ($minzet[$mask] <= $sumzet && $sumzet <=  $maxzet[$mask])  {
           $strzet = '<font color=green><b>' .  "$minzet[$mask] &le; $sumzet &le; $maxzet[$mask]" . '</b></font>';
      }  else {
            if ($sumzet < $minzet[$mask] )    {
                $strzet = '<font color=red><b>' .  "$sumzet < $minzet[$mask]  " . '</b></font>';
                $table->errormsg .= "Итого по $mask: $strzet<br />";   
            } else if ($sumzet > $maxzet[$mask] )    {
                $strzet = '<font color=red><b>' .  "$sumzet > [{$minzet[$mask]} .. {$maxzet[$mask]}]" . '</b></font>';
                $table->errormsg .= "Итого по $mask: $strzet<br />";
            }    
      }
   }  
   
   return $strzet;
    
}


function get_sumzetallcycles($cycles)
{
    global $DB;
  
    $itogo = array();
    
    foreach ($cycles as $cycle) {

        $itogo[$cycle] = new stdClass();
        $itogo[$cycle]->sumhours = 0;
        $itogo[$cycle]->sumzet = 0;
 
        if ($uniquesecondsymbols = get_unique_identificatordiscipline($cycle))  {
                        // print_object($uniquesecondsymbols);  
            foreach ($uniquesecondsymbols as $us => $value) {
                $mask = $cycle . '.' . $us . '.';
                $itogo[$mask] = new stdClass();
                $itogo[$mask]->sumhours = 0;
                $itogo[$mask]->sumzet = 0;
                
                if ($us == 'Б' || $us == 'В')   {
                    $sql = "select sum(mustlearning) as sumhours, sum(creditov) as sumzet
                            from mdl_temp_discipline where notusing=0 and identificatordiscipline like '{$mask}%'";
                } else {
                    $sql = "select mustlearning as sumhours, creditov as sumzet
                            from mdl_temp_discipline where notusing=0 and identificatordiscipline like '{$mask}%' limit 1";
                }            
        
                if ($sums = $DB->get_record_sql($sql))  {
                    $itogo[$mask]->sumhours = $sums->sumhours;
                    $itogo[$mask]->sumzet = $sums->sumzet;
                    
                    $itogo[$cycle]->sumhours += $sums->sumhours;  
                    $itogo[$cycle]->sumzet += $sums->sumzet;
                          
                }
            }
        }
   }
   
   foreach ($cycles as $cycle) {
        $maskB = $cycle;   
        $maskBB = $cycle . '.Б.';
        $maskBDV = $cycle . '.ВДВ';
        if (isset($itogo[$maskB]) && isset($itogo[$maskB]))    {
            $itogo[$maskBDV] = new stdClass();
            $itogo[$maskBDV]->sumhours = $itogo[$maskB]->sumhours - $itogo[$maskBB]->sumhours;
            $itogo[$maskBDV]->sumzet = $itogo[$maskB]->sumzet - $itogo[$maskBB]->sumzet;
        }
   }    
   
   $cycle = 'ФТД';
   $itogo[$cycle] = new stdClass();
   $itogo[$cycle]->sumhours = 0;
   $itogo[$cycle]->sumzet = 0;

   $sql = "select did from mdl_temp_discipline where identificatordiscipline like '{$cycle}%'";
   //  print $sql;        
   if ($datas = $DB->get_records_sql($sql))  {
        $sql = "select sum(mustlearning) as sumhours, sum(creditov) as sumzet
                from mdl_temp_discipline where notusing=0 and identificatordiscipline like '{$cycle}%'";
        if ($sums = $DB->get_record_sql($sql))  {
            $itogo[$cycle]->sumhours = $sums->sumhours;
            $itogo[$cycle]->sumzet = $sums->sumzet;
        }
   }         

   return $itogo;    
}



function check_empty_identificatordiscipline($planid)
{
    global $DB;
    
    if ($disciplines = $DB->get_records_select_menu('bsu_discipline', "planid=$planid", null, '', 'id, identificatordiscipline'))    {
        $cntall = $cntempty = 0;
        foreach ($disciplines as $ident)   {
            $cntall++;
            if (empty($ident))  {
                $cntempty++;
            }
        }
        if ($cntempty > $cntall/2)  return false; 
    }
    
    return true;
}



function delete_disicpline_from_system($yid, $pid, $did, $term)
{
    global $DB, $USER;
    
    if (!$DB->record_exists_select('bsu_discipline_semestr', "disciplineid=$did")) {
        $tables = array( 'bsu_discipline_creditovkurs', 'bsu_discipline_subgroup', 
                         'bsu_discipline_synonym'); // 'bsu_discipline_teacher'
        foreach ($tables as $table) {
            $DB->delete_records_select($table, "disciplineid=$did");
        }
    
        $disciplinenameid = $DB->get_field_select('bsu_discipline', 'disciplinenameid', "id = $did");
        $sql = "SELECT id FROM {bsu_discipline_stream_mask}
                  where planid=$pid AND disciplinenameid = $disciplinenameid";
    
        if( $streammasks = $DB->get_records_sql($sql) ){
                foreach($streammasks AS $streammask){
                    if($DB->Execute("DELETE FROM {bsu_discipline_stream} WHERE streammaskid = {$streammask->id} ") ){
                        // echo "Удалено в stream {$streammask->id} <br>";
                    }       
                    if($DB->Execute("DELETE FROM {bsu_discipline_stream_mask} WHERE id = {$streammask->id} ") ){
                        // echo "Удалено в stream_mask {$streammask->id} <br>";
                    }
            }
        }                
    
        $DB->delete_records_select('bsu_discipline', "id=$did");
        
        $disname = $DB->get_field_select('bsu_ref_disciplinename', 'name', "id=$disciplinenameid");
        
        // add_to_log(1, 'discipline', 'delete', "planid=$pid&disciplinenameid=$disciplinenameid", $disname, $did, $USER->id);
        add_to_bsu_plan_log('discipline:delete', $pid, $did, "planid=$pid&disciplinenameid=$disciplinenameid", $disname);
            
        delete_discipline_charge($yid, $did, $pid);
        
        if($DB->Execute("DELETE FROM mdl_bsu_schedule WHERE disciplineid=$did") ){
            // echo "Удалено в bsu_schedule <br>";
        }       
        if($DB->Execute("DELETE FROM mdl_bsu_schedule_mask WHERE disciplineid=$did") ){
            // echo "Удалено в bsu_schedule_mask <br>";
        }
    }
}



function get_hours_practice_from_charge($yid, $planid, $practiceid)
{
    global $DB;
                 
    $hours = ''; 
    if ($edworkmasks = $DB->get_records_select('bsu_edwork_mask', "yearid = $yid  AND planid = $planid AND practiceid = $practiceid", null, '', 'id, hours'))   {
        $em = reset($edworkmasks);
        $hours = $em->hours;
        if ($edworks = $DB->get_records_select('bsu_edwork', "yearid = $yid  AND planid = $planid AND practiceid = $practiceid", null, '', 'id, hours')) {
            if (count($edworks)>1) {
                $hh = array();
                foreach ($edworks as $edw)   {
                    $hh[] = $edw->hours;
                }
                $hours .= '<br>(' . implode(';', $hh) . ')';
            }    
        }
    }
    
    return $hours;
}
 


function table_svod_zet($yid, $fid, $plan, $agroups)
{
    global $DB;
    
    $sql = "create temporary table mdl_temp_discipline
            SELECT d.id as did, p.id as pid, d.disciplinenameid, n.name as nname, d.cyclename, d.identificatordiscipline, d.sr,
            d.semestrexamen, d.semestrzachet, d.semestrkursovik, d.semestrdiffzach, d.semestrkp, d.notusing, d.semestrref, d.semestrkontr,
            d.mustlearning, d.creditov, round (d.mustlearning/36) as zetcalc
            FROM mdl_bsu_discipline d
            INNER JOIN mdl_bsu_plan p ON p.id=d.planid
            INNER JOIN mdl_bsu_ref_disciplinename n ON n.id=d.disciplinenameid
            WHERE p.id={$plan->id} and d.notusing=0 
            ORDER BY d.identificatordiscipline, d.cyclename";
    // print $sql . '<br />';            
    $DB->Execute($sql);
    
     switch ($plan->kvalif)    {
        case 2: $fs = 'Б';
                $cycles = array($fs.'1', $fs.'2', $fs.'3', $fs.'3+', $fs.'4', 'ФТД');
                $cycles2 = array($fs.'1', $fs.'2', $fs.'3');
        break;
        
        case 3: $fs = 'С';
                $cycles = array($fs.'1', $fs.'2', $fs.'3', $fs.'3+', $fs.'4', 'ФТД');
                $cycles2 = array($fs.'1', $fs.'2', $fs.'3');
        break;
        case 4: $fs = 'М';
                $cycles = array($fs.'1', $fs.'2', 'НИР', 'ФТД');
                $cycles2 = array($fs.'1', $fs.'2');
        break;
        default: $fs = '';
        break;
    }    

    $flag  = false;
    $sql = "SELECT d.id as did, d.cyclename FROM mdl_bsu_discipline d INNER JOIN mdl_bsu_plan p ON p.id=d.planid
            WHERE p.id={$plan->id} and d.cyclename = '{$fs}1'";
    if ($DB->record_exists_sql($sql))   {
        $flag  = true;
    } else {
        $sql = "SELECT d.id as did, d.cyclename FROM mdl_bsu_discipline d INNER JOIN mdl_bsu_plan p ON p.id=d.planid
                WHERE p.id={$plan->id} and d.cyclename = '{$fs}1.Б'";
        if ($DB->record_exists_sql($sql))   {
            $flag  = true;
        }    
    } 
    
    if (!$flag)  {    
        notify ('<strong>Сводная таблица и распределение зачетных единиц по циклам формируется только для планов ФГОС ВПО 3-го поколения.</strong>');
        return false;
    }     


    $table = new html_table();
    $table->dblhead = new stdClass();
    $table->dblhead->head1  = array ('<small>Часть\Учебный цикл (раздел)</small>');
    $table->dblhead->span1  = array ("rowspan=2"); // , "rowspan=2", "colspan=5", "rowspan=2"
    $table->align = array('left');
        

    $table->dblhead->head2  = array ();
    
    $i = 1;
    foreach ($cycles2 as $cycle)    {
        $table->dblhead->head1[] = $i++ . ' ' . $cycle;
        $table->dblhead->span1[] = "colspan=3";
        $table->dblhead->head2[] = 'min';         $table->align[] = 'center';
        $table->dblhead->head2[] = 'max';         $table->align[] = 'center';       
        $table->dblhead->head2[] = 'План';        $table->align[] = 'center';
   } 

    $table->dblhead->head1[] = $i++ . ' Физкультура';
    $table->dblhead->span1[] = "colspan=3";
    $table->dblhead->head2[] = 'min';         $table->align[] = 'center';
    $table->dblhead->head2[] = 'max';         $table->align[] = 'center';       
    $table->dblhead->head2[] = 'План';        $table->align[] = 'center';

    $table->dblhead->head1[] = $i++ . ' Практики и НИР';
    $table->dblhead->span1[] = "colspan=3";
    $table->dblhead->head2[] = 'min';         $table->align[] = 'center';
    $table->dblhead->head2[] = 'max';         $table->align[] = 'center';       
    $table->dblhead->head2[] = 'План';        $table->align[] = 'center';

    $table->dblhead->head1[] = $i++ . ' ИГА';
    $table->dblhead->span1[] = "colspan=3";
    $table->dblhead->head2[] = 'min';         $table->align[] = 'center';
    $table->dblhead->head2[] = 'max';         $table->align[] = 'center';       
    $table->dblhead->head2[] = 'План';        $table->align[] = 'center';

    $table->dblhead->head1[] = $i++ . ' Факультативы';
    $table->dblhead->span1[] = "colspan=2";
    $table->dblhead->head2[] = 'max';         $table->align[] = 'center';       
    $table->dblhead->head2[] = 'План';        $table->align[] = 'center';

    $table->dblhead->head1[] = 'Всего';
    $table->dblhead->span1[] = "colspan=3";
    $table->dblhead->head2[] = 'min';         $table->align[] = 'center';
    $table->dblhead->head2[] = 'max';         $table->align[] = 'center';       
    $table->dblhead->head2[] = 'План';        $table->align[] = 'center';


    $minzet = $DB->get_records_select_menu('bsu_plan_minmaxzet', "planid = $plan->id", null, '', 'cyclename, minzet');
    $maxzet = $DB->get_records_select_menu('bsu_plan_minmaxzet', "planid = $plan->id", null, '', 'cyclename, maxzet');
    
    $sumzetallcycles = get_sumzetallcycles($cycles2);
    $sumzetallcycles[$fs.'4'] = new stdClass(); 
    $sumzetallcycles[$fs.'4']->sumzet = 2;
    
    $sumzetallcycles['Практики'] = new stdClass();
    if ($praczet = $DB->get_field_sql("SELECT round(sum(week)*1.5, 1) as practicezet FROM mdl_bsu_plan_practice where planid=$plan->id"))   {
        $sumzetallcycles['Практики']->sumzet = $praczet;  
    } else {
        $sumzetallcycles['Практики']->sumzet = 0;
    }

    $sumzetallcycles['ИГА'] = new stdClass();
    $grafik = $DB->get_field_sql("SELECT grafik FROM mdl_bsu_plan_grafikuchprocess 
              where planid= $plan->id and (grafik like '%Д%' or grafik like '%Г%')");
              
    if ($plan->kvalif == 4)    {              
        $sumzetallcycles['ИГА']->sumzet = 1.5 * mb_substr_count($grafik, 'Г');            
        $sumzetallcycles['Практики']->sumzet += 1.5 * mb_substr_count($grafik, 'Д');
        $sql = "select sum(mustlearning) as sumnir from mdl_temp_discipline 
                where identificatordiscipline like 'НИР.%'";
        // echo $sql . '<br />';        
        if ($nir = $DB->get_field_sql($sql))    {
            // echo $nir . '<br />';
            $sumzetallcycles['Практики']->sumzet += round($nir/36, 0);            
        }   
    } else {
        $sumzetallcycles['ИГА']->sumzet = 1.5 * mb_substr_count($grafik, 'Г');            
        $sumzetallcycles['ИГА']->sumzet +=  1.5 * mb_substr_count($grafik, 'Д');
    }    
     
    foreach ($cycles2 as $cycl) {
        $m = $cycl . '.ВДВ';
        // notify ($m);
        if (isset($sumzetallcycles[$m]))    {
            $sumzetallcycles[$cycl . '.В.'] = $sumzetallcycles[$m];
        }     
    }
    
    
    // print_object($sumzetallcycles);


   $table->data[] = get_tabledata_for_part_of_svod('Базовая', '_Б_', $cycles2, $minzet, $maxzet, $sumzetallcycles);
   $table->data[] = get_tabledata_for_part_of_svod('Вариативная', '_В_', $cycles2, $minzet, $maxzet, $sumzetallcycles);  
   
   $vsegozetmin = $vsegozetmax = $vsegoplan = 0; 
   $tabledata = array('Итого');
    
   foreach ($cycles2 as $cycle)    {
        if (!empty($minzet))  {
            $zetmin = $minzet[$cycle];
            $vsegozetmin += $zetmin;    
        } else {
            $zetmin = 0;
        }
        
        // $tabledata[] = $zetmin;
        $tabledata[] = "<input type=text name=minzet~{$cycle} size=2 value=\"$zetmin\">";

        if (!empty($maxzet))  {
            $zetmax = $maxzet[$cycle];
            $vsegozetmax += $zetmax;    
        } else {
            $zetmax = 0;
        }
        // $tabledata[] = $zetmax;
        $tabledata[] = "<input type=text name=maxzet~{$cycle} size=2 value=\"$zetmax\">";
        
        
        if ($zetmin <= $sumzetallcycles[$cycle]->sumzet && $sumzetallcycles[$cycle]->sumzet <= $zetmax)  {
            $strzet = '<font color=green><b>'.$sumzetallcycles[$cycle]->sumzet.'</b></font>';
        }   else {
            $strzet = '<font color=red><b>'.$sumzetallcycles[$cycle]->sumzet.'</b></font>';
        }  
        $vsegoplan += $sumzetallcycles[$cycle]->sumzet;
        $tabledata[] = $strzet;
        
   }     
   
   // Физкультура
   if ($plan->kvalif != 4) {
        $index = $fs.'4';
        $zetmin = 0;
        if (!empty($minzet))  {
            $zetmin = $minzet[$index]; 
            $vsegozetmin += $zetmin;   
        }
        $tabledata[] = "<input type=text name=minzet~{$index} size=1 value=\"$zetmin\">";
        
        $zetmax = 0;
        if (!empty($maxzet))  {
            $zetmax = $maxzet[$index];
            $vsegozetmax += $zetmax;    
        }
        
        $tabledata[] = "<input type=text name=maxzet~{$index} size=1 value=\"$zetmax\">";
       
        if ($zetmin <= $sumzetallcycles[$index]->sumzet && $sumzetallcycles[$index]->sumzet <= $zetmax)  {
            $strzet = '<font color=green><b>'.$sumzetallcycles[$index]->sumzet.'</b></font>';
        }   else {
            $strzet = '<font color=red><b>'.$sumzetallcycles[$index]->sumzet.'</b></font>';
        }
        $vsegoplan += $sumzetallcycles[$index]->sumzet;  
        $tabledata[] = $strzet;
    }   else {
        $tabledata[] = '-';
        $tabledata[] = '-';
        $tabledata[] = '-';
    } 

   // Практики и НИР
    $index = 'Практики';
    $zetmin = 0;
    if (!empty($minzet))  {
        $zetmin = $minzet[$index];
        $vsegozetmin += $zetmin;    
    }
    $tabledata[] = "<input type=text name=minzet~{$index} size=1 value=\"$zetmin\">";
    
    $zetmax = 0;
    if (!empty($maxzet))  {
        $zetmax = $maxzet[$index];
        $vsegozetmax += $zetmax;    
    }
    
    $tabledata[] = "<input type=text name=maxzet~{$index} size=1 value=\"$zetmax\">";
   
    $index = 'Практики'; 
    if ($zetmin <= $sumzetallcycles[$index]->sumzet && $sumzetallcycles[$index]->sumzet <= $zetmax)  {
        $strzet = '<font color=green><b>'.$sumzetallcycles[$index]->sumzet.'</b></font>';
    }   else {
        $strzet = '<font color=red><b>'.$sumzetallcycles[$index]->sumzet.'</b></font>';
    }  
    $vsegoplan += $sumzetallcycles[$index]->sumzet;
    $tabledata[] = $strzet;
   
   // ИГА
    $index = 'ИГА';
    $zetmin = 0;
    if (!empty($minzet))  {
        $zetmin = $minzet[$index];
        $vsegozetmin += $zetmin;    
    }
    $tabledata[] = "<input type=text name=minzet~{$index} size=1 value=\"$zetmin\">";
    
    $zetmax = 0;
    if (!empty($maxzet))  {
        $zetmax = $maxzet[$index];
        $vsegozetmax += $zetmax;    
    }
    
    $tabledata[] = "<input type=text name=maxzet~{$index} size=1 value=\"$zetmax\">";
   
    if ($zetmin <= $sumzetallcycles[$index]->sumzet && $sumzetallcycles[$index]->sumzet <= $zetmax)  {
        $strzet = '<font color=green><b>'.$sumzetallcycles[$index]->sumzet.'</b></font>';
    }   else {
        $strzet = '<font color=red><b>'.$sumzetallcycles[$index]->sumzet.'</b></font>';
    }  
    $vsegoplan += $sumzetallcycles[$index]->sumzet;
    
    $tabledata[] = $strzet;
   
   // Факультативы
    $index = 'ФТД';
    $zetmax = 0;
    if (!empty($maxzet))  {
        $zetmax = $maxzet[$index];
        $vsegozetmax += $zetmax;    
    }
    $tabledata[] = "<input type=text name=maxzet~{$index} size=1 value=\"$zetmax\">";
   
    if ($sumzetallcycles[$index]->sumzet <= $zetmax)  {
        $strzet = '<font color=green><b>'.$sumzetallcycles[$index]->sumzet.'</b></font>';
    }   else {
        $strzet = '<font color=red><b>'.$sumzetallcycles[$index]->sumzet.'</b></font>';
    }  
    $vsegoplan += $sumzetallcycles[$index]->sumzet;
    $tabledata[] = $strzet;

    // Всего
   $tabledata[] = "<strong>$vsegozetmin</strong>";
   $tabledata[] = "<strong>$vsegozetmax</strong>";
   
    if ($vsegozetmin <= $vsegoplan && $vsegoplan <= $vsegozetmax)  {
        $strzet = '<font color=green><b>'.$vsegoplan.'</b></font>';
    }   else {
        $strzet = '<font color=red><b>'.$vsegoplan.'</b></font>';
    }  
   $tabledata[] = $strzet;
    
   $table->data[] =  $tabledata;            

   return $table;    
}




function get_tabledata_for_part_of_svod($title, $symbol, $cycles2, $minzet, $maxzet, $sumzetallcycles)
{

    $tabledata = array($title);
    
    $vsegozetmin = $vsegozetmax = $vsegoplan = 0;
    foreach ($cycles2 as $cycle)    {

        $index = $cycle . $symbol;
        $zetmin = 0;
        if (!empty($minzet))  {
            $zetmin = $minzet[$index];
            $vsegozetmin += $zetmin;     
        }
        
        $tabledata[] = "<input type=text name=minzet~{$index} size=2 value=\"$zetmin\">";

        $zetmax = 0;
        if (!empty($maxzet))  {
            $zetmax = $maxzet[$index];
            $vsegozetmax += $zetmax;     
        }
        
        $tabledata[] = "<input type=text name=maxzet~{$index} size=2 value=\"$zetmax\">";
        
        $index2 = str_replace('_', '.', $index);
        
        if ($zetmin <= $sumzetallcycles[$index2]->sumzet && $sumzetallcycles[$index2]->sumzet <= $zetmax)  {
            $strzet = '<font color=green><b>'.$sumzetallcycles[$index2]->sumzet.'</b></font>';
        }   else {
            $strzet = '<font color=red><b>'.$sumzetallcycles[$index2]->sumzet.'</b></font>';
        }
        $vsegoplan += $sumzetallcycles[$index2]->sumzet;  
        $tabledata[] = $strzet;

   }     
   
   // Физкультура
   $tabledata[] = '';
   $tabledata[] = '';
   $tabledata[] = '';

   // Практики и НИР
   $tabledata[] = '';
   $tabledata[] = '';
   $tabledata[] = '';
   
   // ИГА
   $tabledata[] = '';
   $tabledata[] = '';
   $tabledata[] = '';
   
   // Факультативы
   $tabledata[] = '';
   $tabledata[] = '';
   
    // Всего
   $tabledata[] = "<strong>$vsegozetmin</strong>";
   $tabledata[] = "<strong>$vsegozetmax</strong>";
   
    if ($vsegozetmin <= $vsegoplan && $vsegoplan <= $vsegozetmax)  {
        $strzet = '<font color=green><b>'.$vsegoplan.'</b></font>';
    }   else {
        $strzet = '<font color=red><b>'.$vsegoplan.'</b></font>';
    }  
   $tabledata[] = $strzet;    
    
   return $tabledata;
}





function table_svod_hours($yid, $fid, $plan, $agroups)
{
    global $DB;
    
 
    $table = new html_table();
    $table->dblhead = new stdClass();
    $table->dblhead->head1  = array ('Курс', 'Итого АЧ', 'ЗЕТ', 'CrECTs', 
                                      'Итоговая аттестация, <br />выпускные экзамены (А)', 
                                      'Практики (У,П), <br />НИР (П)', 
                                      'Выпускная работа, <br />Диссертация (Д)', 
                                      'Государственные экзамены, <br />защиты (Г)', 'АЧ', 'ЗЕТ', 'CrECTS');
    $table->dblhead->span1  = array ("rowspan=2", "colspan=5", "rowspan=2",  "rowspan=2",
                                     "colspan=4", "colspan=4", "colspan=4", "colspan=4", "rowspan=2", "rowspan=2", "rowspan=2");

    $table->dblhead->head2  = array ('Ауд','СРС','Изуч','Экз','Всего',
                                     'Нед', 'АЧ', 'ЗЕТ', 'CrECTS',
                                     'Нед', 'АЧ', 'ЗЕТ', 'CrECTS',
                                     'Нед', 'АЧ', 'ЗЕТ', 'CrECTS',
                                     'Нед', 'АЧ', 'ЗЕТ', 'CrECTS');

    $table->align = array();
    
    // 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center');
    for ($i=1; $i<=27; $i++)    {
        $table->align[] = 'center';
    }

    $maxsem = get_maxsemestr_plan($plan->id);
    $maxkurs = round($maxsem / 2); 
    
    $vsegotabledata = array('Всего');
    for ($i=1; $i<=26; $i++)    {
        $vsegotabledata [] = 0;
    }
    
    for($k=1; $k<=$maxkurs; $k++)  {
        $vsegoAH = $vsegoZET = 0;
        $tabledata = array($k);
        for ($i=1; $i<=26; $i++)    {
            $tabledata [] = '';
        }
        
        $term1 = 2*$k - 1;
        $term2 = 2*$k;
        $sql = "SELECT sum(lection) as slection, sum(praktika) as spraktika, sum(lab) as slab, sum(ksr) as sksr, 
                sum(examenhours) as sexamen, sum(srs) as ssrs
                FROM mdl_bsu_discipline d
                inner join mdl_bsu_discipline_semestr ds on d.id=ds.disciplineid
                WHERE planid=$plan->id and d.notusing=0 and numsemestr in ($term1, $term2)";
        if ($summa = $DB->get_record_sql($sql)) {
            $aud = $summa->slection + $summa->spraktika + $summa->slab + $summa->sksr;
            $tabledata[1] = $aud; 
            $tabledata[2] = $summa->ssrs;
            $izuch = $aud + $summa->ssrs;
            $tabledata[3] = $izuch;
            $tabledata[4] = $summa->sexamen;
            $tabledata[5] = $izuch + $summa->sexamen;
            
            $vsegotabledata[1] += $aud;
            $vsegotabledata[2] += $summa->ssrs;
            $vsegotabledata[3] += $izuch; 
            $vsegotabledata[4] += $summa->sexamen;
            $vsegotabledata[5] += $izuch + $summa->sexamen;
            
            $vsegoAH = $izuch + $summa->sexamen;  
            
            $termhex1 = dechex($term1);
            $termhex1 = strtoupper($termhex1);
            $termhex2 = dechex($term2);
            $termhex2 = strtoupper($termhex2);

            $sql = "SELECT id, semestrexamen FROM mdl_bsu_discipline
                    WHERE planid=$plan->id and notusing=0 and (semestrexamen like '%$termhex1%' or semestrexamen like '%$termhex2%')";
            $countexamen = 0;        
            if ($fks = $DB->get_records_sql($sql)) {
                foreach ($fks as $fk)   {
                    $countexamen += is_formskontrol_in_term($fk->semestrexamen, $term1);        
                    $countexamen += is_formskontrol_in_term($fk->semestrexamen, $term2);
                }
            }  
            
            $sql = "SELECT (sum(lection) + sum(praktika) + sum(lab) + sum(ksr) + sum(srs)) as fizra
                    FROM mdl_bsu_discipline d
                    inner join mdl_bsu_discipline_semestr ds on d.id=ds.disciplineid
                    WHERE planid=$plan->id and d.disciplinenameid=51 and numsemestr in ($term1, $term2)";
            if ($fizra = $DB->get_field_sql($sql))  {
                $izuch -= $fizra;
                if ($term1 == 1 || $term1 == 3) $zet = 1;
                else  $zet = 0;
            } else {
                $zet = 0; 
            }      
            
            $zet += round($izuch/36, 0);
            
            // print "$zet + $countexamen<br />";  
            $zet += $countexamen;
            $tabledata[6] = $zet;
            $vsegotabledata[6] += $zet; 
            $vsegoZET = $zet;
            
            $tabledata[7] = '';
            
            $sql = "SELECT sum(week) as `week`, sum(week)*54 as ah, round(sum(week)*1.5, 1) as zet
                    FROM mdl_bsu_plan_practice
                    where planid=$plan->id and term in ($term1, $term2)";
            if ($praczet = $DB->get_record_sql($sql))   {
                $tabledata[12] = $praczet->week;  
                $tabledata[13] = $praczet->ah;
                $tabledata[14] = $praczet->zet;
                
                $vsegotabledata[12] += $praczet->week;                
                $vsegotabledata[13] += $praczet->ah;
                $vsegotabledata[14] += $praczet->zet;
                
                $vsegoAH += $praczet->ah;
                $vsegoZET += $praczet->zet; 
            } 

            if ($grafik = $DB->get_field_sql("SELECT grafik FROM mdl_bsu_plan_grafikuchprocess 
                                              where planid= $plan->id and numkurs = $k and (grafik like '%Д%' or grafik like '%Г%')")) {
                if ($countIGA = mb_substr_count($grafik, 'Д'))  {
                    $tabledata[16] = $countIGA;  
                    $tabledata[17] = $countIGA*54;
                    $tabledata[18] = $countIGA*1.5;
                    
                    $vsegotabledata[16] += $countIGA;                
                    $vsegotabledata[17] += $countIGA*54;
                    $vsegotabledata[18] += $countIGA*1.5;
                    
                    $vsegoAH += $tabledata[17];
                    $vsegoZET += $tabledata[18]; 
                    
                }
                if ($countIGA = mb_substr_count($grafik, 'Г'))  {
                    $tabledata[20] = $countIGA;  
                    $tabledata[21] = $countIGA*54;
                    $tabledata[22] = $countIGA*1.5;
                    
                    $vsegotabledata[20] += $countIGA;                
                    $vsegotabledata[21] += $countIGA*54;
                    $vsegotabledata[22] += $countIGA*1.5;

                    $vsegoAH += $tabledata[21];
                    $vsegoZET += $tabledata[22]; 

                }            
                
             }          
        }
        $tabledata[24] = "<b>$vsegoAH</b>";
        $tabledata[25] = "<b>$vsegoZET</b>";
        
        $vsegotabledata[24] += $vsegoAH;                
        $vsegotabledata[25] += $vsegoZET;

        $table->data[] =  $tabledata;
    }
   
   $tabledata = array();
   foreach ($vsegotabledata as $td) {
        if ($td == 0)   {
            $tabledata[] = "";
        } else {
            $tabledata[] = "<b>$td</b>";    
        }    
        
   }    

   $table->data[] = $tabledata;  

   return $table;    
}



function okrugvniz($x)
{
    $y = fmod($x, 1);
    if($y==0){
    }elseif($y<=0.5){
           $x = floor($x);
    }elseif($y>0.5){
           $x = floor($x)+0.5;
    }
    return $x;
}

?>