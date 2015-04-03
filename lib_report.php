<?PHP // $Id: prakticecharge.php, v 1.1 shtifanov Exp $


function get_list_subgroup($data, $iterms)
{
    global $DB;
 // print_object($iterms);
    $asubgr = array();
    if ($subgroups = $DB->get_records_select('bsu_discipline_subgroup', "disciplineid = $data->did"))   {
        foreach ($subgroups as $subgroup)   {
            // print_object($subgroup);
            if (!isset($iterms[$subgroup->groupid]) && is_siteadmin()) {
                notify ("ВНИМАНИЕ! Группа $subgroup->groupid не привязана к плану (для подгруппы $subgroup->name)");
                continue;
            }
            if (in_array($data->numsemestr, $iterms[$subgroup->groupid] ))   {
                $strgroup = $DB->get_field_select('bsu_ref_groups', 'name', "id = $subgroup->groupid");
                $asubgr[] = $strgroup . '->' . $subgroup->shortname . "($subgroup->countstud)";
            }    
        }    
    }

    return $asubgr;    
}


function get_list_streams_for_edworkkind($yid, $planid, $data, $edworkkindid)
{
    global $DB;
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
    } 
    
    return $strstream; 
}        


// Display list all faculty as popup_form without role
function listbox_all_facultys($scriptname, $fid)
{
  global $CFG, $OUTPUT, $DB;

  $facultymenu = array();
  $facultymenu[0] = get_string('selectafaculty', 'block_bsu_plan').'...';

  if($allfacs = $DB->get_records_sql("SELECT id, departmentcode, name, shortname FROM {bsu_ref_department}
                                      WHERE DepartmentCode>10000
                                      ORDER BY DepartmentCode"))   {
		foreach ($allfacs as $faculty) 	{
            $facultymenu[$faculty->departmentcode] = $faculty->name;
		}
  }

  echo '<tr align="left"> <td align=right>'.get_string('faculty', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'fid', $facultymenu, $fid, null, 'switchfacall');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}       

function table_practice($yid, $fid, $plan, $agroups, $copy_in_term = 0)
{
    global $CFG, $DB, $OUTPUT, $USER, $ACCESS_USER, $currsubdeps, $perehodsubdep, $editcapability;
    
    
        
    $planid = $plan->id;
    $table = new html_table();
    $table->head = array (get_string('npp', 'block_bsu_plan'),
                          get_string('practicetype', 'block_bsu_plan'),
                          get_string('practicename', 'block_bsu_plan'),
                          get_string('term', 'block_bsu_plan'),
                          get_string('week', 'block_bsu_plan'),
                          get_string('formula', 'block_bsu_plan'),
                          'Кафедра(ы)',
                          'Группа(ы)',
                          'Количество<br> подгрупп',
                          'Кол-во<br> студентов',
                          'Всего часов<br> нагрузки',
                          get_string('actions'));
    $table->align = array ('center', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'center', 'center','center', 'center');
    // $table->width = "70%";
    // id, planid, practicetypeid, name, term, week
    
    $practicetype = $DB->get_records_select_menu('bsu_ref_practice_type', "", null, '', 'id, name');
    $practicetype[0] = '<b>не определен тип практики</b>'; 
    $practiceformula = $DB->get_records_select_menu('bsu_ref_practice_type', "", null, '', 'id, formula');
    $practiceformula[0] = '<b>формула не определена</b>';
    $practiceissubgroup = $DB->get_records_select_menu('bsu_ref_practice_type', "", null, '', 'id, issubgroup');
    $practiceissubgroup[0] = 0;
    $practicecalcfunc = $DB->get_records_select_menu('bsu_ref_practice_type', "", null, '', 'id, calcfunc');
    
    $terms = get_terms_group($yid, $agroups);
    $countstudents = get_count_students_groups($agroups);
    
    if ($copy_in_term>0) {
        $yid2=$yid+$copy_in_term/2;
        $newsubdep = $DB->get_records_sql_menu("SELECT id2, id FROM mdl_bsu_vw_ref_subdepartments where yearid=$yid2 and id2>0");
    }
    
    if ($practices = $DB->get_records_select('bsu_plan_practice', "planid = $planid AND edworkkindid=13", null, 'term'))  {
        $i = 1;
        foreach ($practices as $practice)   {
            
            $strgroups = '';
            foreach ($agroups as $group)    {
                if (in_array($practice->term, $terms[$group] ))   {
                    $strgroups .= $group . '<br>';
                }
            }
            
            if (empty($strgroups))   continue;      
            
            if ($copy_in_term>0) {
                $data=clone $practice;
                $data->term=(int)$data->term+$copy_in_term;
                $id=$DB->insert_record('bsu_plan_practice', $data);
                //print_object($data);
            }
            
            $link = "yid=$yid&fid=$fid&pid=$plan->id&prid={$practice->id}&term=100";

            $strsubgroups = '';
            if ($cnt = $DB->count_records_select('bsu_plan_practice_subgroup', "practiceid={$practice->id}"))  {
                $title = get_string('changesubgroups', 'block_bsu_plan');
                $strsubgroups = "<a href=\"subgroupspractice.php?action=edit&{$link}\"> $title ($cnt)</a>";
            } else {
                $title = get_string('createsubgroups', 'block_bsu_plan');
                $strsubgroups = "<a href=\"subgroupspractice.php?action=new&{$link}\">$title</a>";
            }

            
            $sql = "SELECT ps.id as psid, sd.id as sdid, sd.name, ps.yearid, ps.practiceid, ps.countsubgroups, ps.subdepartmentid, ps.hours, ps.podtype, ps.countstudents
                    FROM mdl_bsu_plan_practice_subdep ps
                    inner join mdl_bsu_vw_ref_subdepartments sd on sd.id=ps.subdepartmentid
                    where ps.yearid = $yid AND ps.practiceid = $practice->id";
            $strsubdep = $strsubgroup = $strstud = '';
            $kid = 0;
            $allkid = array();    
            $hoursindb = '';    
            if ($kafedri = $DB->get_records_sql($sql)) {
                foreach ($kafedri as $kaf) {
                    if ($yid >= 15) { 
                        if (!in_array($kaf->sdid, $currsubdeps))   {
                            if (isset($perehodsubdep[$kaf->sdid]))  {
                                $newkafid = $perehodsubdep[$kaf->sdid];
                                notify ("id = $kaf->psid: старая кафедра $kaf->sdid ==> новая кафедра $newkafid");
                                $DB->set_field_select('bsu_plan_practice_subdep', 'subdepartmentid', $newkafid, "id = $kaf->psid"); 
                            }     
                        }
                    }  
                    if ($copy_in_term>0) {
                        $data = new stdClass;
                        $data->practiceid=$id;
                        $data->yearid=$kaf->yearid+$copy_in_term/2;
                        $data->countsubgroups=$kaf->countsubgroups;
                        if ($kaf->subdepartmentid>1) {
                           $data->subdepartmentid=$newsubdep[$kaf->subdepartmentid]; 
                        } else {
                           $data->subdepartmentid=1; 
                        }
                        $data->hours=$kaf->hours;
                        $data->podtype=$kaf->podtype;
                        $data->countstudents=$kaf->countstudents;
                        $DB->insert_record('bsu_plan_practice_subdep', $data);
                    }  
                    $strsubdep .= $kaf->sdid . '. '. $kaf->name . '<br>';
                    $kid =  $kaf->sdid;
                    
                    $allkid[] = $kid;
                    
                    if ($kaf->countsubgroups == 0) {
                        $cntgrup = 0;
                        foreach ($agroups as $group)    {
                            if (in_array($practice->term, $terms[$group] ))   {
                                $cntgrup++;
                            }
                        }                        
                        $DB->set_field_select('bsu_plan_practice_subdep', 'countsubgroups', $cntgrup, "id=$kaf->psid");
                        $kaf->countsubgroups = $cntgrup;
                    }
                    
                    if ($kaf->countstudents == 0) {
                        $cntstud = 0;
                        foreach ($agroups as $group)    {
                            if (in_array($practice->term, $terms[$group] ))   {
                                $cntstud += $countstudents[$group];
                            }
                        }
                     
                        $DB->set_field_select('bsu_plan_practice_subdep', 'countstudents', $cntstud, "id=$kaf->psid");
                        $kaf->countstudents = $cntstud;
                    }
                    
                    if ($kaf->hours > 0)    {
                        $hoursindb .= $kaf->hours . '<br>';
                    }
                    
                    $strsubgroup .= $kaf->countsubgroups . '<br>'; 
                    $strstud .= $kaf->countstudents . '<br>';
                    
                }
            } else {
                $strsubdep = get_string('kafedranotset', 'block_bsu_plan');    
            }
            
            
            if ($practiceissubgroup[$practice->practicetypeid] > 0) {
                $strstud = 'не учитывается';
            } else {
                $strsubgroup  = 'не учитывается';
            }

            /*        
            if ($edworks = $DB->get_records_select('bsu_edwork', "practiceid = $practice->id AND edworkkindid=13")) { // AND subdepartmentid = $kid 
                foreach ($edworks as $edwork) {
                    if (!in_array($edwork->subdepartmentid, $allkid)) {
                        notify($planid. '. Кафедра удалена из практики '. $edwork->subdepartmentid); 
                    }     
                }
            }
            */ 
            
            
            $strlinkupdate = '';
            // if (is_siteadmin() || $USER->id == 59502 || $USER->id == 66281 ) { // Жидких и Маматов || $USER->id == 1677  66281 - Бондаренко

            if ((strpos($CFG->editplanopenedforyid, "$yid") !== false && $editcapability) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  { 
            
                $strsubdep = "<a href=\"subdeppractice.php?action=new&{$link}&kid=$kid\">$strsubdep</a>";
                $strlinkupdate .= "<a href='editpractice.php?action=edit&{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";                
            	$strlinkupdate .= "<a href='editpractice.php?action=delete&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                $link2 = $CFG->wwwroot . "/blocks/bsu_charge/practicecharge.php?fid=$fid&pid=$planid&yid=$yid&prid={$practice->id}&sid=$kid";
                $strlinkupdate .= "<a href='$link2'><img class='icon' title='Посмотреть нагрузку по практике' src='".$OUTPUT->pix_url('i/charge')."'></a>";

                $strlinkupdate .= '<br>';
            }
            
            
            // print $planid . ': ' . $practice->id . ':' . $hoursindb; 
            if (empty($hoursindb))      {
                // print $plan->id . ': create_edworks_for_practice_new <br />';
                if ($edworkmask = create_edworks_for_practice_new($yid, $plan->id, $practice->id, $agroups))    {
                    $em = reset($edworkmask);
    
                    // if (!isset($em->hours)) print_object($edworkmask);
                    $hours = $em->hours;
                    if (count($em->edwork)>1) {
                        $hh = array();
                        foreach ($em->edwork as $edw)   {
                           $hh[] = $edw->hours;
                        }
                        $hours .= '<br>(' . implode(';', $hh) . ')';
                    }
                } else {
                    $hours = '<strong>???</strong>'; 
                }        
                
            } else {
                $hours = $hoursindb;
                $edworkmask = create_edworks_for_practice_new($yid, $plan->id, $practice->id, $agroups);
                // print_object($edworkmask);
            }    

            $table->data[] = array($i++. '.', $practicetype[$practice->practicetypeid], $practice->name, 
                                    $practice->term, $practice->week, $practiceformula[$practice->practicetypeid],
                                    $strsubdep, $strgroups, $strsubgroup, $strstud,
                                    $hours,
                                     $strlinkupdate); // $strsubgroups, 
        }
        
    }
    

    return $table;  
}



function table_specvidrabot($yid, $fid, $plan, $agroups, $edworkid = 0, $copy_in_term = 0)
{
    global $CFG, $DB, $OUTPUT, $USER, $ACCESS_USER, $currsubdeps, $perehodsubdep, $ASPIRANTPLAN, $editcapability;  
    
    $edworkkind = $DB->get_records_menu('bsu_ref_edworkkind', null, '', 'id, name');
    $edworkkind[10] = 'КОНСгак'; 
    $edworkkind[100] = 'РМР';
      
    $planid = $plan->id;
    $table = new html_table();
    $table->head = array (get_string('npp', 'block_bsu_plan'),
                          'Вид работы',
                          'Название',
                          get_string('term', 'block_bsu_plan'),
                          get_string('formula', 'block_bsu_plan'),
                          'Кафедра(ы)',
                          'Группа(ы)',                          
                          'Кол-во<br>студентов',
                          'Всего часов<br>нагрузки',
                          get_string('actions'));
    $table->align = array ('center', 'center', 'left', 'center', 'center', 'center',  'center', 'center',  'center', 'center');
    // $table->width = "70%";
    // id, planid, practicetypeid, name, term, week
    $terms = get_terms_group($yid, $agroups);
    $countstudents = get_count_students_groups($agroups);    
    
    if ($edworkid>0) {
        $sql_edw = "AND edworkkindid=$edworkid";
    } else {
        $sql_edw = "";  
    }
    
    if ($copy_in_term>0) {
        $yid2=$yid+$copy_in_term/2;
        $newsubdep = $DB->get_records_sql_menu("SELECT id2, id FROM mdl_bsu_vw_ref_subdepartments where yearid=$yid2 and id2>0");
    }
                     
    if ($specvidrabots = $DB->get_records_select('bsu_plan_practice', "planid = $planid AND edworkkindid<>13 $sql_edw", null, 'term, edworkkindid'))  {
        $i = 1;
        foreach ($specvidrabots as $specvidrabot)   {           
    
            $strgroups = '';
            foreach ($agroups as $group)    {
                if (in_array($specvidrabot->term, $terms[$group] ))   {
                    $strgroups .= $group . '<br>';
                }
            }   
            if (empty($strgroups)  && !in_array($planid, $ASPIRANTPLAN)) continue;

            if ($yid == 15) { 
                /*
                if ($specvidrabot->edworkkindid == 6 || $specvidrabot->edworkkindid == 7 || $specvidrabot->edworkkindid == 8 || $specvidrabot->edworkkindid == 36)  {
                    // print_object($specvidrabot);
                   	$DB->delete_records_select('bsu_plan_practice', "id=$specvidrabot->id");
                    $DB->delete_records_select('bsu_plan_practice_subdep', "practiceid=$specvidrabot->id");
                    // $yid = get_current_edyearid();
                    delete_pactice_charge($yid, $specvidrabot->id);
                    notify("Спец.вида работа '{$specvidrabot->name}' удалена.");        
                    continue;
                    //print "$plan->id. Delete $specvidrabot->id. $specvidrabot->name<br />";
                } 
                */
            }    
            if ($copy_in_term>0) {
                $data=clone $specvidrabot;
                $data->term=(int)$data->term+$copy_in_term;
                //print_object($data);
                $id = $DB->insert_record('bsu_plan_practice', $data);
            }
            
            $link = "yid=$yid&fid=$fid&pid=$plan->id&prid={$specvidrabot->id}&term=101&eid=$specvidrabot->edworkkindid";

            
            $sql = "SELECT ps.id as psid, sd.id as sdid, sd.name, ps.yearid, ps.practiceid, ps.countsubgroups, ps.subdepartmentid, ps.hours, ps.podtype, ps.countstudents
                    FROM mdl_bsu_plan_practice_subdep ps
                    inner join mdl_bsu_vw_ref_subdepartments sd on sd.id=ps.subdepartmentid
                    where ps.yearid = $yid AND  ps.practiceid = $specvidrabot->id";
            $strsubdep = $strsubgroup = $strstud = '';
            $kid = 0;        
            if ($kafedri = $DB->get_records_sql($sql)) {
                foreach ($kafedri as $kaf) {
                   if ($yid == 15) {  
                       if (!in_array($kaf->sdid, $currsubdeps))   {
                            if (isset($perehodsubdep[$kaf->sdid]))  {
                                $newkafid = $perehodsubdep[$kaf->sdid];
                                notify ("id = $kaf->psid: старая кафедра $kaf->sdid ==> новая кафедра $newkafid");
                                $DB->set_field_select('bsu_plan_practice_subdep', 'subdepartmentid', $newkafid, "id = $kaf->psid"); 
                            }     
                        }
                    }
                    if ($copy_in_term>0) {
                        $data = new stdClass;
                        $data->practiceid=$id;
                        $data->yearid=$kaf->yearid+$copy_in_term/2;
                        $data->countsubgroups=$kaf->countsubgroups;
                        if ($kaf->subdepartmentid>1) {
                           $data->subdepartmentid=$newsubdep[$kaf->subdepartmentid]; 
                        } else {
                           $data->subdepartmentid=1; 
                        }
                        $data->hours=$kaf->hours;
                        $data->podtype=$kaf->podtype;
                        $data->countstudents=$kaf->countstudents;
                        $DB->insert_record('bsu_plan_practice_subdep', $data);
                    }
                    $strsubdep .= $kaf->sdid . '. '. $kaf->name . '<br>';
                        
                    // $strsubdep .= $kaf->name . '<br>';
                    $kid =  $kaf->sdid;
                    
                    if ($kaf->countstudents == 0) {
                        $cntstud = 0;
                        foreach ($agroups as $group)    {
                            if (in_array($specvidrabot->term, $terms[$group] ))   {
                                $cntstud += $countstudents[$group];
                            }
                        }
                     
                        $DB->set_field_select('bsu_plan_practice_subdep', 'countstudents', $cntstud, "id=$kaf->psid");
                        $kaf->countstudents = $cntstud;
                    }
                    
                    $strsubgroup .= $kaf->countsubgroups . '<br>'; 
                    $strstud .= $kaf->countstudents . '<br>';
                }
            } else {
                $strsubdep = get_string('kafedranotset', 'block_bsu_plan');    
            }

            $strlinkupdate = '';
            
            // if (is_siteadmin() || $USER->id == 59502 || $USER->id == 66281) { // Жидких и Маматов || $USER->id == 1677 66281 - Бондаренко
            if ((strpos($CFG->editplanopenedforyid, "$yid") !== false && $editcapability) || is_siteadmin() || in_array($USER->id, $ACCESS_USER))  {
                $strsubdep = "<a href=\"subdeppractice.php?action=new&{$link}&kid=$kid\">$strsubdep</a>";
                $strlinkupdate .= "<a href='editpractice.php?action=editspec&{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
            	$strlinkupdate .= "<a href='editpractice.php?action=deletespec&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                $link2 = $CFG->wwwroot . "/blocks/bsu_charge/practicecharge.php?fid=$fid&pid=$planid&yid=$yid&prid={$specvidrabot->id}&sid=$kid";
                $strlinkupdate .= "<a href='$link2'><img class='icon' title='Посмотреть нагрузку по данному виду работы' src='".$OUTPUT->pix_url('i/charge')."'></a>";

                $strlinkupdate .= '<br>';
            }
            
            
            $edworkmask = create_edworks_for_specvidrabot($yid, $plan->id, $specvidrabot->id, $agroups);
            $em = reset($edworkmask);
            
            // print_object($em);
            
            $hours = $em->hours;
                if (count($em->edwork)>1) {
                    $hh = array();
                    foreach ($em->edwork as $edw)   {
                       $hh[] = $edw->hours;
                    }
                    $hours .= '<br>(' . implode(';', $hh) . ')';
                }    
            
            $formula = get_calcfunc_text($specvidrabot->edworkkindid); 

            // $practiceformula[$specvidrabot->edworkkindid]
            $table->data[] = array($i++. '.', $edworkkind[$specvidrabot->edworkkindid], $specvidrabot->name,
                                    $specvidrabot->term,  $formula, $strsubdep, $strgroups, $strstud,
                                    $hours, 
                                    $strlinkupdate); // $strsubgroups, 
        }
        
    }
    

    return $table;  
}


function get_count_plan_groups($fid)
{
    global $DB;
    
    $countplan = $DB->count_records_select('bsu_plan', "departmentcode=$fid");

    $countplangr = 0; 
    $sql = "SELECT groupid FROM mdl_bsu_plan_groups
            where planid in (SELECT id FROM mdl_bsu_plan where departmentcode=$fid)";
    if ($groups = $DB->get_records_sql($sql))  {
        $countplangr = count($groups);
    }    
     
    $countgroups = $DB->count_records_select('bsu_ref_groups', "departmentcode=$fid"); 
    
    $data = array ($countplan, $countplangr, $countgroups);
    
    return $data;    
} 


function report_plan_faculty($fid)
{
    global $CFG, $DB, $OUTPUT;
    
    $table = new html_table();
    $table->head = array (get_string('kolzagrplanov', 'block_bsu_plan'),
                          get_string('kolprivgroup', 'block_bsu_plan'),
                          get_string('vsegogroups', 'block_bsu_plan'));
    $table->align = array ('center', 'center', 'center');
    // $table->width = "70%";
    $table->columnwidth = array (7, 10, 10);
	$table->class = 'moutable';

   	$table->titlesrows = array(30);
    $table->titles = array();
    $table->titles[] = ''; 
    $table->downloadfilename = "report_{$fid}";
    $table->worksheetname = $table->downloadfilename;

   	$table->data[] = get_count_plan_groups($fid);

    return $table;
}


function report_plan_university()
{
    global $CFG, $DB, $OUTPUT;
    
    $table = new html_table();
    $table->head = array (get_string('name'),
                          get_string('kolzagrplanov', 'block_bsu_plan'),
                          get_string('kolprivgroup', 'block_bsu_plan'),
                          get_string('vsegogroups', 'block_bsu_plan'));
    $table->align = array ('left', 'center', 'center', 'center');
    // $table->width = "70%";
    $table->columnwidth = array (60, 10, 10, 10);
	$table->class = 'moutable';

   	$table->titlesrows = array(30);
    $table->titles = array();
    $table->titles[] = ''; 
    $table->downloadfilename = "report_all";
    $table->worksheetname = $table->downloadfilename;
    
	$allfacs = $DB->get_records_sql("SELECT departmentcode, name FROM {bsu_ref_department}
                                     WHERE  departmentnumber>0
                                     ORDER BY name");
	if ($allfacs)	{
        foreach ($allfacs as $faculty) 	{
            $data = get_count_plan_groups($faculty->departmentcode);  
        	$table->data[] = array ($faculty->name, $data[0], $data[1], $data[2]);
        }
    }             

    return $table;
}




function table_plan_report($yid, $fid, $plan, $agroups, $delemiter = '<br>')
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
    $table->head = array ('ID',
                          get_string('cyclename', 'block_bsu_plan'),
                          get_string('discipline', 'block_bsu_plan'),
                          'Кафедра',
                          'Подгруппы',
                          get_string('term', 'block_bsu_plan'),
                          get_string('formskontrol', 'block_bsu_plan'),
                          get_string('lection', 'block_bsu_plan'),
                          'Поток для лек.',
                          'Пр. зан.',
                          'Поток для пр. зан.',
                          get_string('lab', 'block_bsu_plan'),
                          'Поток для лаб.',

                          // get_string('ksr', 'block_bsu_plan'),
                          // get_string('srs', 'block_bsu_plan'),
                          // get_string('mustlearning', 'block_bsu_plan'),                          
                           get_string('actions')
                          );
    $table->align = array ('center', 'left', 'left', 'left', 'center', 'center', 'center', 'center', 
                            'center', 'center', 'center', 'center', 'center', 'center');
    $table->width = "80%";
    $table->columnwidth = array (7, 14, 50, 60,  14, 10, 11, 8, 14, 8, 14, 12, 4, 11);
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
    $table->downloadfilename = "plan_{$fid}_{$planid}";
    $table->worksheetname = $table->downloadfilename;
    
    if (empty($terms)) return $table;    
   
    $sql = "SELECT s.id as sdid, d.id as did, s.numsemestr, d.disciplinenameid, n.name as nname, d.cyclename, 
                d.identificatordiscipline, d.semestrexamen, d.semestrzachet, d.semestrdiffzach,  
                d.semestrkursovik, d.mustlearning, p.id as pid, s.lection, s.praktika, s.lab
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
            
            $subdep = '-';
            /*
            if ($subdepid = $DB->get_field_select('bsu_discipline_subdepartment', 'subdepartmentid', "yearid=$yid AND disciplineid=$data->did"))    {
                $subdep = $DB->get_field_select('bsu_vw_ref_subdepartments', 'Name', "Id = $subdepid");
            } 
            */
            $s = array();
            if ($subdeps = $DB->get_records_select('bsu_discipline_subdepartment', "yearid = $yid AND disciplineid = $data->did"))  {
                foreach ($subdeps as $subdep)   {
                    $s[] = $DB->get_field_select('bsu_vw_ref_subdepartments', 'Name', "Id = $subdep->subdepartmentid");
                }    
                $subdep = implode(', <br />', $s);
            }    
            

            $l = $data->lection;
            $p = $data->praktika;
            $lab = $data->lab;
            $fk = get_formskontrol($data->semestrexamen, $data->semestrzachet, $data->semestrkursovik, $data->semestrdiffzach, $data->numsemestr);
            
            $asubgr = get_list_subgroup($data, $iterms);
            
            $lstream = get_list_streams_for_edworkkind($yid, $planid, $data, 1);
            
            $prstream = get_list_streams_for_edworkkind($yid, $planid, $data, 3);
            
            $labstream = get_list_streams_for_edworkkind($yid, $planid, $data, 2);
                            
        	$table->data[] = array($data->did, 
                                   $data->cyclename, 
                                   $data->nname, 
                                   $subdep,
                                   implode(';<br>', $asubgr),
                                   $data->numsemestr, 
                                   $fk, 
                                   $l, 
                                   $lstream,
                                   $p,
                                   $prstream, 
                                   $lab,
                                   $labstream,
                                   
                                   ''
                                   );
        }
    }
    
    
    $tablep = table_practice($yid, $fid, $plan, $agroups);
    // print_object($tablep);
    
    $indexes = array(0, 1, 2, 6, 7, 3, 5, 4, 8, 9, 10);
    $tabledata = array();    
    foreach ($indexes as $i)    {
        $tabledata[] = '<strong>'. $tablep->head[$i] . '</strong>';   
    }
    for ($j=1; $j<=3; $j++)    $tabledata[] ='';

    $table->data[] = $tabledata;
    
    if (isset($tablep->data))
    foreach ($tablep->data as $td)  {
        $tabledata = array();    
        foreach ($indexes as $i)    {
            $tabledata[] = strip_tags ($td[$i], '<br><br />');   
        }
        for ($j=1; $j<=3; $j++)    $tabledata[] ='';
        $table->data[] = $tabledata;
    }

    $tablep = table_specvidrabot($yid, $fid, $plan, $agroups);
    // print_object($tablep);
   
    $indexes = array(0, 1, 2, 5, 6, 3, 4, 7, 8);
    $tabledata = array();    
    foreach ($indexes as $i)    {
        $tabledata[] = '<strong>'. $tablep->head[$i] . '</strong>';   
    }
    for ($j=1; $j<=5; $j++)    $tabledata[] ='';
    
    $table->data[] = $tabledata;
    
    if (isset($tablep->data))
    foreach ($tablep->data as $td)  {
        $tabledata = array();    
        foreach ($indexes as $i)    {
            $tabledata[] = strip_tags ($td[$i], '<br><br />');   
        }
        for ($j=1; $j<=5; $j++)    $tabledata[] ='';
        $table->data[] = $tabledata;
    }


    return $table;
}


function  get_plan_groups_with_count_stud($planid)
{
    $bgroups = array();
    
    $strgroups = get_plan_groups($planid);
    // print $strgroups . '<br />';
    
    if ($strgroups != '')   {
        $agroups = explode ('<br>', $strgroups);
        $cntstuds = get_count_students_groups($agroups);
        foreach ($agroups as $i => $agroup)   {
            $bgroups[$i] = $agroup  . ' (' . $cntstuds[$agroup] . ' ст.)';   
        }
    }
    
    return $bgroups;
}    


function table_plan_report2($yid, $fid, $plan, $agroups, $delemiter = '<br>')
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
    $table->head = get_tablehead_for_report($atermsids); 
    $table->align = array ('center', 'left', 'left', 'left', 'left', 'center', 'center', 'center'); 
                           // 'center', 'center', 'center', 'center', 'center', 'center');
    $i=0;
    foreach ($table->head as $h)    {
        if ($i>7) $table->align[] = 'center';
        $i++;
    }                       
    $table->width = "80%";
    $table->columnwidth = array (7, 14, 50, 60,  14, 10, 11, 8, 14, 8, 14, 12, 4, 11);
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
    $table->downloadfilename = "plan_{$fid}_{$planid}";
    $table->worksheetname = $table->downloadfilename;
    
    if (empty($terms)) return $table;  
   
    $sql = "SELECT s.id as sdid, d.id as did, s.numsemestr, d.disciplinenameid, n.name as nname, d.cyclename, 
                d.identificatordiscipline, d.semestrexamen, d.semestrzachet, d.semestrdiffzach,
                d.semestrkursovik, d.mustlearning, p.id as pid, s.lection, s.praktika, s.lab
                FROM mdl_bsu_discipline_semestr s
                INNER JOIN mdl_bsu_discipline d ON d.id=s.disciplineid 
                INNER JOIN mdl_bsu_plan p ON p.id=d.planid
                INNER JOIN mdl_bsu_ref_disciplinename n ON n.id=d.disciplinenameid
                WHERE d.planid=$planid and s.numsemestr in ($termsids) and d.notusing=0
                ORDER BY d.cyclename";
    //            ORDER BY s.numsemestr, n.name";
    /*
    $sql = "SELECT s.id as ssid, d.id as did   
                FROM {bsu_discipline_semestr} s
                INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
                WHERE d.planid=$planid and s.numsemestr in ($termsids) and d.notusing=0
                group by d.id";
    */             
    // echo $sql; 
    $disciplines =array();
    
    if ($datas = $DB->get_records_sql($sql))  {
       
        foreach($datas as $data) {
            if(!isset($disciplines[$data->did])) {
                $disciplines[$data->did] = new stdClass();
                $disciplines[$data->did]->cyclename = $data->cyclename;
                $disciplines[$data->did]->nname = $data->nname; 
                $disciplines[$data->did]->subdep = '-';
                $s = array();
                if ($subdeps = $DB->get_records_select('bsu_discipline_subdepartment', "yearid = $yid AND disciplineid = $data->did"))  {
                    foreach ($subdeps as $subdep)   {
                        $s[] = $DB->get_field_select('bsu_vw_ref_subdepartments', 'Name', "Id = $subdep->subdepartmentid");
                    }    
                    $disciplines[$data->did]->subdep = implode(', <br />', $s);
                }    

                /*
                if ($subdepid = $DB->get_field_select('bsu_discipline_subdepartment', 'subdepartmentid', "yearid=$yid AND disciplineid=$data->did"))    {
                   $disciplines[$data->did]->subdep = $DB->get_field_select('bsu_vw_ref_subdepartments', 'Name', "Id = $subdepid");
                } 
                */
                
                $disciplines[$data->did]->asubgr = implode('<br>', get_list_subgroup($data, $iterms));


                $disciplines[$data->did]->semestrexamen = $data->semestrexamen; 
                $disciplines[$data->did]->semestrzachet = $data->semestrzachet; 
                $disciplines[$data->did]->semestrdiffzach = $data->semestrdiffzach;
                $disciplines[$data->did]->semestrkursovik = $data->semestrkursovik;                
            }
            
            if(!isset($disciplines[$data->did]->lection)) {
                $disciplines[$data->did]->lection = array();
                $disciplines[$data->did]->praktika = array();
                $disciplines[$data->did]->lab = array();
            }     
            
            $disciplines[$data->did]->lection[$data->numsemestr] =  $data->lection;
            $disciplines[$data->did]->praktika[$data->numsemestr] = $data->praktika;
            $disciplines[$data->did]->lab[$data->numsemestr] = $data->lab;
            
            $lstream = get_list_streams_for_edworkkind($yid, $planid, $data, 1);
            if ($lstream != '') {
                $disciplines[$data->did]->lection[$data->numsemestr] .= '<br><small>'.$lstream.'</small>';
            }    
            $prstream = get_list_streams_for_edworkkind($yid, $planid, $data, 3);
            if ($prstream != '') {
                $disciplines[$data->did]->praktika[$data->numsemestr] .= '<br><small>'.$prstream.'</small>';
            }    
            
            $labstream = get_list_streams_for_edworkkind($yid, $planid, $data, 2);
            if ($labstream != '') {
                $disciplines[$data->did]->lab[$data->numsemestr] .= '<br><small>'.$labstream.'</small>';
            }    
                            
        }
        // print_object($disciplines);
    }
    
     $h = 0;
    foreach ($disciplines as $id => $discipline)   {
        $tabledata = array();
        $tabledata[] = $id;
        $tabledata[] = $discipline->cyclename;
        $tabledata[] = $discipline->nname;
        $tabledata[] = $discipline->subdep;
        $tabledata[] = '<small>'.$discipline->asubgr.'</small>';
        
        if (!empty($discipline->semestrexamen))  $tabledata[] = $discipline->semestrexamen;
        else $tabledata[] = '';  
        if (!empty($discipline->semestrzachet))  $tabledata[] = $discipline->semestrzachet;
        else $tabledata[] = '';  
        if (!empty($discipline->semestrdiffzach))  $tabledata[] = $discipline->semestrdiffzach;
        else $tabledata[] = '';  
        if (!empty($discipline->semestrkursovik)) $tabledata[] = $discipline->semestrkursovik;
        else $tabledata[] = '';    
        
        foreach ($atermsids as $aterm)  {
            if (isset($discipline->lection[$aterm]))    {
                if ($discipline->lection[$aterm]>0) $tabledata[] = $discipline->lection[$aterm];
                else $tabledata[] = '';    
                if ($discipline->praktika[$aterm]>0) $tabledata[] = $discipline->praktika[$aterm];
                else $tabledata[] = ''; 
                if ($discipline->lab[$aterm]>0) $tabledata[] = $discipline->lab[$aterm];
                else $tabledata[] = ''; 
            } else {
                $tabledata[] = '';
                $tabledata[] = '';
                $tabledata[] = '';
            }    
        }
        
        $table->data[] = $tabledata;
        $h++; 
        if ($h == 5) {
            $h = 0; 
            $table->data[] = get_tablehead_for_report($atermsids, true);    
        }   
    }

    return $table;
}

function get_tablehead_for_report($atermsids, $bold = false)
{
    $tablehead = array ('ID',
                          get_string('cyclename', 'block_bsu_plan'),
                          get_string('discipline', 'block_bsu_plan'),
                          'Кафедра',
                          'Подгруппы',
                          'экз.', 'зач.', 'дифф.зач.', 'к.р.');
    foreach ($atermsids as $aterm)  {
        $tablehead[] =  $aterm . '&nbsp;сем. <hr> лекции&nbsp;&nbsp;';
        $tablehead[] =  $aterm . '&nbsp;сем. <hr> пр.';
        $tablehead[] =  $aterm . '&nbsp;сем. <hr> лаб.';    
    }  
    
    if ($bold)  {
       foreach ($tablehead as $i => $th)    {
            $tablehead[$i] = '<b>' . $tablehead[$i] . '</b>';
       } 
    }                            

    return $tablehead;
}


// Display list plan as popup_form
function listbox_svod_plan($scriptname, $fid, $spid, $eid=0)
{
    global $CFG, $OUTPUT, $DB;
    
    $kvalif = $DB->get_records_menu('bsu_tskvalifspec', null, '', 'idKvalif, Kvalif');
    $edform = $DB->get_records_menu('bsu_tsotdelenie', null, 'idotdelenie', "idotdelenie, otdelenie");

/*
    $strsql = "SELECT g.groupid, concat (p.specialityid, '_',  p.edformid, '_', p.kvalif, '_', p.profileid) as i
                FROM mdl_bsu_plan p
                INNER JOIN mdl_bsu_plan_groups g ON p.id=g.planid
                where p.departmentcode=$fid";
    $groups = $DB->get_records_sql_menu($strsql);
    print_object($groups);
    $groupids = array();
    foreach ($groups as $gid => $index)    {
        $groupids[$index] = '';
    }
    foreach ($groups as $gid => $index)    {
        $groupids[$index] .= $gid . ',';
    }
    foreach ($groupids as $gid => $index)    {
        $groupids[$gid] = substr($groupids[$gid], 0, strlen($groupids[$gid]) - 1);
    }
    print_object($groupids); echo '<hr>';
*/
    $planids = get_planids_from_specialityid_edformid_kvalif_profileid($fid);
    

    $planmenu = array();
    $planmenu[0] = 'Выберите сводный РУП ...';
    
    foreach ($planids as $index => $planid) {
        $ids = explode ('_', $index);
        
        $specyal = $DB->get_record_select('bsu_tsspecyal', "idSpecyal=$ids[0]", null, 'idSpecyal, Specyal, KodSpecyal');
        $name = $specyal->specyal;
        $kod  = trim($specyal->kodspecyal); 
        $len = strlen($kod);
        // $kod = mb_substr($name, 0, 6);
        if ($len == 6)  {
            $nname = mb_substr($name, $len);
            $name = $kod . '.' . substr ($kvalif[$ids[2]], 0, 2) . ' ' . trim($nname);
        }     
        // $name .= ', ' . $kvalif[$ids[2]];
        if ($ids[3] > 0)    {
            $name .= ', ' . $DB->get_field_select('bsu_ref_profiles', 'name', "id=$ids[3]");
        } 
        if ($eid == 0)  $name .= ', ' . $edform[$ids[1]];
        else {
            if ($ids[1] != $eid) continue;
            else $name .= ', ' . $edform[$ids[1]];
        }
        $planmenu[$index] = $name;
    }
    
    echo '<tr align="left"> <td align=right>Сводный РУП: </td><td align="left">';
    echo $OUTPUT->single_select($scriptname, 'spid', $planmenu, $spid, null, 'switchplansvod');
    // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
    echo '</td></tr>';
    
    return 1;
}


function get_planids_from_specialityid_edformid_kvalif_profileid($fid)
{
    global $DB;
    
    $sql = "SELECT p.id as planid, concat (p.specialityid, '_',  p.edformid, '_',  p.kvalif, '_', p.profileid) as i
            FROM mdl_bsu_plan p
            where p.departmentcode=$fid and p.notusing=0
            group by p.id
            order by p.id";
    $plans = $DB->get_records_sql_menu($sql);
    //print_object($plans);
    $planids = array();
    $alldepstudcount = 0;
    foreach ($plans as $pid => $index)    {
        $planids[$index] = '';
    }
    foreach ($plans as $pid => $index)    {
        $planids[$index] .= $pid . ',';
    }
    foreach ($planids as $pid => $index)    {
        $planids[$pid] = substr($planids[$pid], 0, strlen($planids[$pid]) - 1);
    }
  
   //  print_object($planids);
   return $planids;
}


function get_unique_terms_groups($yid, $agroups)  
{          
    $terms = get_terms_group($yid, $agroups);
    
    $atermsids = array();
    foreach ($terms as $term)   {
        foreach ($term as $t)   {
            $atermsids[] = $t;
        }    
    } 
    
    $atermsids = array_unique($atermsids);
    
    return $atermsids;
}



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


    //$disciplinenameids = array(494, 10459, 505, 504, 3062, 10231, 10233, 10685, 1732, 9506, 9554, 483, 343, 9553, 7463, 10727, 9663, 5328, 12028, 7441, 12482, 4273, 9583, 5265, 14, 3188, 8832, 10129, 10123, 8378, 10755, 10675, 9590, 11879, 9948, 3335, 13502, 10900, 5264, 11246, 8381, 10220, 10221, 10222, 7649, 7650, 10902, 11932, 13042, 7519, 8328, 12708, 13199, 8326, 13202, 13135, 13203, 6723, 3685, 11405, 13133, 7669, 13204, 9885, 8798, 13082, 13087, 13124, 13109, 2564, 9011, 1874, 11126, 10876, 12024, 13132, 3500, 13316, 13317, 13084, 11712, 10943, 12084, 11766, 13101, 13251, 9304, 5950, 9381, 7318, 9486, 10702, 7470, 7468, 7467, 7990, 3754, 10295, 2558, 6041, 7471, 7469, 9628, 10310, 8291, 8290, 11994, 11995, 3589, 9382, 2974, 11281, 5961, 7314, 7703, 7460, 7313, 8649, 5362, 7379, 7378, 1923, 7380, 9383, 2905, 11933, 7269, 8182, 503, 11888, 3314, 12819, 12755, 5143, 11966, 484, 7478, 456, 7476, 461, 12574, 3867, 4139, 5919, 10684, 491, 7483, 7481, 2993, 5810, 9502, 9503, 3317, 9592, 3318, 9596, 9595, 10704, 1922, 7464, 7479, 7477, 5989, 7917, 482, 2079, 333, 2562, 4228, 3316, 9598, 9597, 7993, 7994, 8292, 7014, 12047, 11026, 501, 10100, 5440, 10715, 2903, 12520, 11993, 9632, 2904, 6951, 9303, 6046, 6839, 13444, 8647, 481, 500, 11024, 9302, 325, 5267, 10297, 9273, 5301, 5960, 9647, 4686, 4093, 7392, 7482, 457, 7480, 8845, 8197, 8967, 8968, 7609);
  
    $disciplinenameids = array(1732,12028,14,8832,10129,10123,8378,9948,3335,10900,3500,8967,5264);
      
    $select = implode (',', $disciplinenameids); 
    
    $sql = "SELECT s.id as sdid, d.id as did, s.numsemestr, d.disciplinenameid, n.name as nname, d.cyclename, 
                d.identificatordiscipline, d.semestrexamen, d.semestrzachet, d.semestrdiffzach, d.semestrkursovik,
                d.mustlearning, p.id as pid, s.lection, s.praktika, s.lab
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



    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция формирует таблицу планов с отображением проблем в последнем стобце                          ///
    ///     $god - год , $fid - (departmentcode таблицы bsu_ref_department) факультет                           ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_view($god, $fid, $eid){
    global $DB, $SUBDEPSNAMES;

        $table = new html_table();
        $table->width = '100%' ;
        $table->head  = array('№', 'План', 'Cостояние');
        $table->rowclasses = array();
        $table->data = array();
        
        if ($eid == 0)  {
            $textsqlplan = "SELECT id, name, lastshifr, specialityid, profileid, edformid, kvalif
                            FROM {bsu_plan}
                            WHERE (departmentcode = {$fid}) AND (deleted = 0) AND (notusing=0)";
        } else {
            $textsqlplan = "SELECT id, name, lastshifr, specialityid, profileid, edformid, kvalif
                            FROM {bsu_plan}
                            WHERE (departmentcode = {$fid}) AND (deleted = 0) AND (notusing=0) and edformid=$eid";
            
        }                            
        if( $plans = $DB->get_records_sql($textsqlplan)) {
            foreach($plans AS $plan){
                $field0 = $plan->id;
                $strgroups = get_plan_groups($plan->id);
                if ($strgroups != '')   {
                    $agroups = explode ('<br>', $strgroups);
                }    
                $field1 = '<b>Название плана :</b>'.$plan->name."<br>".
                          '<b>Шифр :</b>'.$plan->lastshifr."<br>".
                          '<b>Специальность :</b>'.withoutchairs_speciality($plan->specialityid)."<br>".
                          '<b>Профиль :</b>'.withoutchairs_profiles($plan->profileid)."<br>".
                          '<b>Форма обучения :</b>'.withoutchairs_edform($plan->edformid)."<br>".
                          '<b>Квалификация :</b>'.withoutchairs_kvalif($plan->kvalif).
                          '<br /><b>Группы :</b><br />'.$strgroups;
                $field2 = html_writer::table( withoutchairs_planproblem($god, $plan->id, $plan->specialityid) );
                
                $field0 = "<a href='disciplines.php?yid=$god&fid=$fid&pid=$plan->id&term=99'>$plan->id</a>";
                
                $table->data[] =   array($field0, $field1, $field2);
            }
        }

        return $table;
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция возвращает гг год начала обучения группы                                                    ///
    ///     $namegroup - наименование группы                                                                    ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_god_start_group($namegroup){
        
        $result = 0;
        $namegroup = (string)$namegroup;
        if( strlen ($namegroup) >= 4 ){
            $textgod = substr($namegroup, -4, -2);
            $result = (int)$textgod;   
        }

        return $result;
       
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///   Функция выводящая спадающий список выбора факультет                                                   ///
    ///   $scriptname - url перехода, $fid -   $fid - (departmentcode таблицы bsu_ref_department) факультет     ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function listbox_fid($scriptname, $fid){
    global $CFG, $DB, $OUTPUT;

        $outmenu = array();
        $outmenu[0] = 'Выберите факультет...';
        if( $facultys = $DB->get_records_select('bsu_ref_department', "DepartmentCode>10000", null, 'DepartmentCode') ){
            foreach($facultys AS $faculty){
                $outmenu[$faculty->departmentcode] = $faculty->name;    
            }
            
        }  

        echo '<tr><td>Факультет : </td><td>';        
        echo $OUTPUT->single_select($scriptname, 'fid', $outmenu, $fid, null, 'switchfid');
        echo '</td></tr>';

        return 1;
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция возвращает наименование специальности                                                       ///
    ///     id - (idSpecyal таблицы bsu_tsspecyal) специальности                                                ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_speciality($id){
    global $DB;    
        $result = "<font color = red> -? </font>";
        
        if($id == 0){
            $result = "-";
        }
        
        if($name = $DB->get_record('bsu_tsspecyal', array('idSpecyal' => $id ), "idSpecyal, Specyal"  ) ){
            $result = $name->specyal;
        }

        return $result;
                
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция возвращает наименование кафедры                                                             ///
    ///     id - (Id таблицы bsu_vw_ref_subdepartments) факультета                                              ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_subdepartments($id, $yid=14){
    global $DB;    
        $result = "<font color = red> -? </font>";
        
        if($id == 0){
            $result = "-";
        }
        
        if($name = $DB->get_record('bsu_vw_ref_subdepartments', array('Id' => $id, 'yearid' => $yid), "Id, Name"  ) ){
            $result = $name->name;
        }

        return $result;
                
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция возвращает наименование профиля                                                             ///
    ///     id - (id таблицы bsu_ref_profiles) профиля                                                          ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_profiles($id){
    global $DB;    
        $result = "<font color = red> -? </font>";
        
        if($id == 0){
            $result = "-";
        }
        
        if($name = $DB->get_record('bsu_ref_profiles', array('id' => $id ), "id, name"  ) ){
            $result = $name->name;
        }

        return $result;
                
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция возвращает наименование формы обучения                                                      ///
    ///     id - (idOtdelenie таблицы bsu_tsotdelenie) формы обучения                                           ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_edform($id){
    global $DB;    
        $result = "<font color = red> -? </font>";
        
        if($id == 0){
            $result = "-";
        }
        
        if($name = $DB->get_record('bsu_tsotdelenie', array('idOtdelenie' => $id ), "idOtdelenie, Otdelenie"  ) ){
            $result = $name->otdelenie;
        }

        return $result;
                
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция возвращает наименование квалификации                                                        ///
    ///     id - (idKvalif таблицы bsu_tskvalifspec) квалификации                                               ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_kvalif($id){
    global $DB;    
        $result = "<font color = red> -? </font>";
        
        if($id == 0){
            $result = "-";
        }
        
        if($name = $DB->get_record('bsu_tskvalifspec', array('idKvalif' => $id ), "idKvalif, Kvalif"  ) ){
            $result = $name->kvalif;
        }

        return $result;
                
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция возвращает наименование дисциплины                                                       ///
    ///     id - (id таблицы bsu_ref_disciplinename) дисциплина                                               ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_disciplinename($id){
    global $DB;    
        $result = "<font color = red> -? </font>";
        
        if($id == 0){
            $result = "-";
        }
        
        if($name = $DB->get_record('bsu_ref_disciplinename', array('id' => $id ), "id, name"  ) ){
            $result = $name->name;
        }

        return $result;
                
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция возвращает id кафедры                                                                       ///
    ///     id - (id таблицы bsu_ref_disciplinename) дисциплина                                                 ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_idkafedr($id){
    global $DB;    
        $result = 0;
        
        if($id == 0){
            $result = 0;
        }
        
        if($name = $DB->get_record('bsu_ref_disciplinename', array('id' => $id ), "id, subdepartmentid"  ) ){
            $result = $name->subdepartmentid;
        }

        return $result;
                
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                         ///
    ///     Функция формирует таблицу планов с отображением проблем в последнем стобце                          ///
    ///     $god - год ,  
    ///     $planid - id план                             ///
    ///                                                                                                         ///
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function withoutchairs_planproblem($god, $planid, $planspecialityid ){
    global $DB, $fid, $SUBDEPSNAMES, $OUTPUT, $USER;
    
        $yid = $god;    

        $table = new html_table();
        $table->width = '100%' ;
        $table->head  = array('№', 'Цикл',  'Семестр', 'Дисциплина', 'Кафедра', 'Претендующая кафедра'); // , 'Действия' , 'Cостояние'
        $table->align  = array('left', 'left', 'center', 'left', 'left', 'center'); 
        $table->rowclasses = array();
        $table->data = array();
        
        $terms = array();
        $strgroup = get_plan_groups($planid);
        if ($strgroup != '')    {
            $agroups = explode ('<br>', $strgroup);
            foreach ($agroups as $group)    {
                  $term = get_term_group($yid, $group, 1);
                  $terms[] = $term; 
                  $terms[] = $term+1;
            }
        }
        if (empty($terms)) return $table;    

        $terms = array_unique($terms);
        $idsterms = implode(',', $terms);      
		
        if ($god == 13) {
            $sql = "SELECT s.id as ssid, d.id as did, n.name as nname, d.cyclename, s.numsemestr, 
                    d.semestrexamen, d.semestrzachet, d.semestrdiffzach, d.semestrkursovik,
                    n.id AS disciplinenameid
                    FROM {bsu_discipline_semestr} s
                    INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
                    INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                    WHERE d.planid=$planid and s.numsemestr in ($idsterms) and d.notusing=0
                    order by n.name";
        } else {
            $sql = "SELECT s.id as ssid, d.id as did, n.name as nname, d.cyclename, s.numsemestr, 
                    d.semestrexamen, d.semestrzachet, d.semestrdiffzach, d.semestrkursovik,
                    n.id AS disciplinenameid
                    FROM {bsu_discipline_semestr} s
                    INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
                    INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                    WHERE d.planid=$planid and s.numsemestr in ($idsterms) and d.notusing=0
                    GROUP BY d.id
                    order by n.name";                     // 
        }            
        if ($disciplines = $DB->get_records_sql($sql))  {
//			$departments = $DB->get_records_sql_menu("SELECT id, name FROM {bsu_vw_ref_subdepartments} WHERE yearid=$yid");        	

            // echo count($disciplines) . '<br />';
            // $existsdid = array();
            foreach($disciplines AS $discipline)    {
                // if (in_array($discipline->did, $existsdid)) continue;
                //$existsdid[] = $discipline->did;
                 
                if ($god == 13) {
                    if ($discipline->numsemestr%2 == 1) continue;
                }
                
                $fk = get_formskontrol($discipline->semestrexamen,   $discipline->semestrzachet, 
                                       $discipline->semestrkursovik, $discipline->semestrdiffzach, 
                                       $discipline->numsemestr);
                
                if ($god == 13) {
                    if ($fk == '-') continue;
                }                            
                
                $link = "yid=$yid&fid=$fid&pid=$planid&did={$discipline->did}&term=$discipline->numsemestr";
                $textsql = "SELECT id, subdepartmentid
                            FROM {bsu_discipline_subdepartment}
                            WHERE (yearid = {$yid})  AND (disciplineid = {$discipline->did})";
                $kid = 0;            
            	$subdepids = array();
            	$subdepids[] = 0;

                if($subdeps =  $DB->get_records_sql($textsql) ){
                    $field0 = $discipline->did;
                    $field1 = $discipline->nname;
                    $field2 = '';
                    foreach ($subdeps as $subdep)   {
                        // $field2 .= withoutchairs_subdepartments($subdep->subdepartmentid) . '<br />';
                        if (isset($SUBDEPSNAMES [$subdep->subdepartmentid])) {
                        	$subdepids[] = $subdep->subdepartmentid;
                            $field2 .= $SUBDEPSNAMES [$subdep->subdepartmentid] . '<br />';
                        } else {
                            $field2 .= '-<br />';
                        }    
                    }    
                    $field3 = "-";                    
                } else {
                    $field0 = $discipline->did;
                    $field1 = $discipline->nname;
                    $field2 = '<b>' . get_string('kafedranotset', 'block_bsu_plan') . '</b>';
                    $field3 = "-";                    
                }
                
               // if (is_siteadmin()) {
                    $field2 = "<a href=\"subdepdiscipline.php?action=new&{$link}&kid=$kid\" target='_blank'>$field2</a>";
               //  }                    
              	// $strlinkupdate = "<a href='disciplines.php?action=delete&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";

				$subdepartment_pret = '';
				$sql = "SELECT id, contextid FROM {role_assignments} WHERE roleid=24 AND userid=$USER->id";
				$role = $DB->get_records_sql_menu($sql);
				if($role) {
					$contextid = implode(',', $role);
					$sql = "SELECT c.id as i, bvrs.id FROM {context} c 
							INNER JOIN {bsu_vw_ref_subdepartments} bvrs ON c.instanceid=bvrs.id
							WHERE c.id in ($contextid) AND bvrs.yearid=$yid";

							
					if($subdepid = $DB->get_records_sql_menu($sql)) {

						$subdepid = implode(',', $subdepid);

						$sql = "SELECT id, subdepartmentid FROM {bsu_discipline_subdepartment_zav} WHERE
							yearid=$yid AND 
							disciplinenameid=$discipline->disciplinenameid AND
							disciplineid=$discipline->did AND
				   			subdepartmentid IN ($subdepid) AND deleted=0";
						$verify = $DB->get_records_sql($sql);

                        $dep = '';
						if(!$verify) {
							$sql = "SELECT id FROM {bsu_discipline_subdepartment} WHERE 
									yearid=$yid AND 
									disciplinenameid=$discipline->disciplinenameid AND
									disciplineid=$discipline->did AND
				       				subdepartmentid IN ($subdepid)
				       				";
							$verify = $DB->get_records_sql($sql);
				       	} else {
                            $dep = '<br>';
                            foreach($verify as $record) {
                                $ids = explode(',', $record->subdepartmentid);
                                    foreach($ids AS $key=>$value) {
                                        $dep.='<font size="1px">'.$SUBDEPSNAMES[$value].'</font>';
                                    }
                            }
                        }

						if(!$verify) {
							$link2 = "setsubdepartment.php?tab=v&fid=$fid&yid=$yid&pid=$planid&action=subdep&did=$discipline->did&sdid=$subdepid";
				            $subdepartment_pret = "<a target='_blank' href='$link2'><img class='icon' title='Сопоставить дисциплину' src='".$OUTPUT->pix_url('t/switch_plus')."'></a>";
						} else {
							$sql = "SELECT id FROM {bsu_discipline_subdepartment_zav} WHERE 
								yearid=$yid AND 
								disciplinenameid=$discipline->disciplinenameid AND
								disciplineid=$discipline->did AND 
					   			subdepartmentid IN ($subdepid) AND deleted=0";
//print "$sql<br>";
							$id = 0;
							if($verify = $DB->get_record_sql($sql)) $id = $verify->id;

							if($id != 0) {
								$link2 = "setsubdepartment.php?tab=v&fid=$fid&yid=$yid&pid=$planid&action=delete&did=$discipline->did&sdid=$subdepid&id=$id";
								$subdepartment_pret = "<a target='_blank' href='$link2'><img class='icon' title='Отменить сопоставление дисциплины' src='".$OUTPUT->pix_url('t/switch_minus')."'></a>";
							} else {
								$subdepartment_pret = '';
							}

                            $subdepartment_pret.=$dep;
						}
					}
				}						

		        $strsql = "SELECT a.id, roleid, contextid, contextlevel, instanceid, path, depth 
		                   FROM {role_assignments} a RIGHT JOIN {context} ctx ON a.contextid=ctx.id
		                   WHERE userid={$USER->id}";

		        if ($ctxs = $DB->get_records_sql($strsql))	{
		            foreach($ctxs as $ctx1) {
		                $context = get_context_instance_by_id($ctx1->contextid);

		                switch ($ctx1->contextlevel)	{
		                    case CONTEXT_SYSTEM:
		                        if(has_capability('block/bsu_plan:complinksubdepartment', $context)) {
									$d = $discipline->disciplinenameid;

									$sql = "SELECT id, subdepartmentid FROM {bsu_discipline_subdepartment_zav} 
										WHERE 
											disciplinenameid=$d AND
											disciplineid=$discipline->did AND
											yearid=$yid AND 
											deleted=0
									   ";
									   
									if($records = $DB->get_records_sql($sql)) {
										$table1 = '<table border="1px"><tr><td border="1">Кафедра</td><td align="center">Действие</td></tr>';

									    foreach($records as $record) {
									    	$ids = explode(',', $record->subdepartmentid);
									    	foreach($ids AS $key=>$value) {
									    		$f_s = $f_e = '';
									    		if(!in_array($value, $subdepids)) {
													$f_s = '<font color="#ee0000">';
													$f_e = '</font>';
												}
									    		
									    		
									    		$href = "<a target='_blank' href='setsubdepartment.php?tab=v&fid=$fid&yid=$yid&pid=$planid&action=complete&did=$discipline->did&sdid=$value&sid=$planspecialityid&id=$record->id'>Утвердить</a>";
												$table1.='<tr><td>'.$f_s.$SUBDEPSNAMES[$value].$f_e.'</td><td>'.$href.'</td></tr>';
											}
										}
										$table1.='</table>';
										$subdepartment_pret = $table1;
									}
							}
						}
					}
				}

                $table->data[] =   array($field0, $discipline->cyclename, $discipline->numsemestr , 
                                        $field1 . " ($fk)", $field2, $subdepartment_pret); // , $field3
                unset($subdepids);
            }
         }   
               
        return $table;
    }

?>