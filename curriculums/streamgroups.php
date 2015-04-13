<?php   // by shtifanov 01/12/2012

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../lib_report.php");
    require_once("../../bsu_schedule/lib_schedule.php");

    $yid = optional_param('yid', 0, PARAM_INT);			// ed yearid
    $fid = required_param('fid', PARAM_INT);
    $planid = optional_param('pid', 0, PARAM_INT);      // plan id    
    $term = optional_param('term', 1, PARAM_INT);
	$did = optional_param('did', 0, PARAM_INT);			// Discipline id (courseid)
	// $eid = optional_param('eid', 0, PARAM_INT);			// edworkkindid
    $fid2 = optional_param('fid2', $fid, PARAM_INT);
    $ns = optional_param('ns', 1, PARAM_INT);           // Num stream
    $tab = optional_param('tab', 0, PARAM_INT);           // TAB
    $plantab = optional_param('plantab', 'plan', PARAM_TEXT);
    $action = optional_param('action', '', PARAM_ALPHA);    // new, add, edit, update
    
    if ($ns == 0 ) $ns = 1;
        
    if ($tab > 0 )   $eid = $tab;   

    if ($yid == 0)   {
        $yid = get_current_edyearid();
    }

    
    require_login();
    
    
    $time0 = time();
    $select = "timestart<$time0 and timeend>$time0 and LOCATE('$yid', editplan)>0";
    $ACCESS_USER = $DB->get_records_select_menu('bsu_operator_dean', $select, null, '', 'id, userid');
    

    if ($action === "new") $strscript = 'Создать поток'; // get_string('createsubgroups', 'block_bsu_plan');
	else                    $strscript = 'Изменить поток';// get_string('changesubgroups', 'block_bsu_plan');
    
    $redirlink = "streamgroups.php?fid=$fid&pid=$planid&term=$term&did=$did";
    
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
    $PAGE->navbar->add($strtitle2, new moodle_url("curriculums.php", array('yid' => $yid, 'fid' => $fid, 'tab' => $plantab)));
    $PAGE->navbar->add($strtitle3, new moodle_url("disciplines.php", array('yid' => $yid, 'fid' => $fid, 'pid' => $planid, 
                                                                           'term' => $term, 'tab' => $plantab)));
    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();
/*
    echo $OUTPUT->notification('Функция формирования потоков временно отключена. Идет добавление выбора учебного года и потоки надо будет создавать для каждого учебного года.');
    echo $OUTPUT->Footer();
    exit();
/**/        
    
	if ($frm = data_submitted())   {
        if ($frm->ns == 0) $frm->ns = 1;
	    save_bsu_discipline_groups_stream($frm);
	}

    $scriptname = "streamgroups.php";
    $strlistfaculties =  listbox_department($scriptname, $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

    echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
    echo $strlistfaculties;

    $kp = false;
    if($fid > 0){
        listbox_plan($scriptname."?yid=$yid&plantab=$plantab&fid=$fid", $fid, $planid);
        if ($planid > 0)   {
            listbox_term($scriptname."?yid=$yid&plantab=$plantab&fid=$fid&pid=$planid", $fid, $planid, $term);
            if ($term > 0) {
                listbox_discipline($scriptname."?yid=$yid&plantab=$plantab&fid=$fid&pid=$planid&term=$term", $fid, $planid, $term, $did);
                if ($did > 0) {
                    // list_box_year($scriptname."?yid=$yid&plantab=$plantab&fid=$fid&pid=$planid&term=$term&did=$did", $yid);
                    echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left"><b>';
                    $edyear = $DB->get_record_select('bsu_ref_edyear', "id = $yid");
                    echo $edyear->edyear;
                    echo '</b></td></tr>';
                    
                    if ($yid > 0) {
                        $strgroups = get_plan_groups($planid);
                        if ($strgroups != '')   {
                            echo '<tr> <td align=right>'.get_string('groups', 'block_bsu_plan').': </td><td>';
                            $strgroups = str_replace('<br>', ', ', $strgroups);
                            echo '<b>'.$strgroups.'</b>'; // $plan->scode . '. ' .
                            echo '</td></tr>';
                        }                            
                        $kp = true;
                    }        
                }
            }
        }
     }
     echo '</table>';
     
     if ($kp) {
        if (is_siteadmin()) {
            $a = "1,2,3,4,5,39,10,40";
        } else {     
            $a = get_edworkkid($did, $term);
        }                            
        $strsql = "SELECT id, description FROM {bsu_ref_edworkkind}	WHERE id IN ($a) and id <> 14";
        if ($tab == 0)  {
           $ids = explode (',', $a);
           // print_object($ids); 
           $tab = $ids[1];
           $eid = $tab; 
        }

        // listbox_edworkkind($scriptname."?fid=$fid&pid=$planid&term=$term&did=$did&yid=$yid", , $eid);
        $link = "?yid=$yid&plantab=$plantab&fid=$fid&pid=$planid&term=$term&did=$did&eid=$eid";
        $dnid = $DB->get_field_select('bsu_discipline', 'disciplinenameid', "id = $did");
        
        
        $toprow0 = array();
   	    if ($options = $DB->get_records_sql($strsql)) 	{
  		    foreach ($options as $option) {
               $sql = "SELECT s.id  
               FROM mdl_bsu_discipline_stream s 
               INNER JOIN mdl_bsu_discipline_stream_mask sm ON sm.id=s.streammaskid
               where sm.yearid = $yid AND sm.planid=$planid AND sm.disciplinenameid=$dnid 
                    AND sm.term=$term AND sm.edworkkindid=$option->id
               group by sm.id, s.numstream"; 
               // echo $sql . '<br />';      
               if($allgr = $DB->get_records_sql($sql))   {
                    // print_object($allgr);
                    $cnt = count($allgr);
               } else {
                    $cnt = 0;
               }       		        
               $toprow0[] = new tabobject($option->id, $scriptname.$link."&tab=$option->id", $option->description . " ($cnt п.)");
            }   
        }  
    
        $tabs0 = array($toprow0);
        print_tabs($tabs0, $tab, NULL, NULL);
        
        
        print_tabs_streams($scriptname.$link."&tab=$eid", $ns, $fid, $did, $yid, $planid, $eid, $term);

        if (!empty($strgroups)) {
            // echo $strgroups;
            $agroups = explode (', ', $strgroups);
            check_existing_stream($yid, $planid, $eid, $term, $did, $agroups);
        }    

        display_enrolpage_stream($yid, $fid, $planid, $term, $did, $eid, $fid2, $ns, $tab, $plantab);
     }        

    echo $OUTPUT->footer();




function display_enrolpage_stream($yid, $fid, $planid, $term, $did, $eid, $fid2, $ns, $tab, $plantab)
{
    global $CFG, $DB, $OUTPUT, $scriptname, $ACCESS_USER, $USER;
    
    $discipline = $DB->get_record_select('bsu_discipline', "id = $did", null, 'id, disciplinenameid');
    $disciplinenameid = $discipline->disciplinenameid;
    
    $egroupids = array();
    $enrolgroupsmenu = get_stream_enrolgroups($yid, $planid, $eid, $term, $disciplinenameid, $egroupids, $ns);
    $countenrol = count($enrolgroupsmenu);

    $unenrolgroupsmenu = get_stream_unenrolgroups($yid, $planid, $fid2, $did, $term, $disciplinenameid, $egroupids);
    $countunenrol = count($unenrolgroupsmenu);

?>
  <table align="center" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td valign="top">
          <?php
              echo get_string('disciplinestream', 'block_bsu_plan') . ' (' . $countenrol. ')' ;
          ?>
      </td>
      <td></td>
      <td valign="top">
          <?php
              echo  'Группы и подгруппы  (' . $countunenrol. ')<br>';
              listbox_department_shortname($scriptname."?yid=$yid&fid=$fid&pid=$planid&term=$term&did=$did&eid=$eid&tab=$tab&plantab=$plantab&ns=$ns", $fid2, $yid);
          ?>
      </td>
    </tr>

    <form name="enrolform" id="enrolform" method="post" action="streamgroups.php">
    <tr>
      <td valign="top">

            <input type="hidden" name="yid" value="<?php echo $yid ?>" />
            <input type="hidden" name="fid" value="<?php echo $fid ?>" />
            <input type="hidden" name="pid" value="<?php echo $planid ?>" />
            <input type="hidden" name="term" value="<?php echo $term ?>" />
            <input type="hidden" name="did" value="<?php echo $did ?>" />
            <input type="hidden" name="dnid" value="<?php echo $disciplinenameid  ?>" />            
            <input type="hidden" name="eid" value="<?php echo $eid ?>" />
            <input type="hidden" name="fid2" value="<?php echo $fid2 ?>" />
            <input type="hidden" name="ns" value="<?php echo $ns ?>" />
            <input type="hidden" name="tab" value="<?php echo $tab ?>" />            
            <input type="hidden" name="plantab" value="<?php echo $plantab ?>" />            
            <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />

          <select name="removeselect[]" size="30" id="removeselect"  multiple
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
          <br />
          <br />  
          <br />
          <br />  
          <br />
          <br />  
          <div id="addcontrols">
          <?php if ((strpos($CFG->editplanopenedforyid, "$yid") !== false)|| is_siteadmin() || in_array($USER->id, $ACCESS_USER)) {?>
              <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('enrolgroup', 'block_bsu_plan'); ?>" title="<?php print_string('add'); ?>" /><br />
          <?php 
            } else {
                notify (get_string('accessdenied', 'block_bsu_plan'));
           }   
            ?>
          </div>
          <br />
          <br />  
          <div id="removecontrols">
            <?php if ((strpos($CFG->editplanopenedforyid, "$yid") !== false)|| is_siteadmin() || in_array($USER->id, $ACCESS_USER))  { ?>          
              <input name="remove" id="remove" type="submit" value="<?php echo get_string('unenrolgroup', 'block_bsu_plan').'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" />
          </div>
              (при отписывании группы или подгруппы <br> из потока расписание потока остается<br>без изменений)
          <?php 
                } 
            ?>
          
              
      </td>
      <td valign="top">
          <select name="addselect[]" size="30" id="addselect"  multiple
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
   </form>
  </table>


<?php
}

/*
function save_bsu_discipline_groups_stream($frm)
{
    global $DB, $OUTPUT;
    
    // print_object($frm);
	if (!empty($frm->add) and !empty($frm->addselect) and confirm_sesskey()) {
		foreach ($frm->addselect as $groupid_subgrid) {
		    list($groupid, $subgrid) = explode ('_', $groupid_subgrid);
            $conditions = array ('planid' => $frm->pid, 'disciplineid' => $frm->did, 'term' => $frm->term,
                                 'groupid' => $groupid, 'subgroupid' => $subgrid, 'edworkkindid' => $frm->eid); 
		    if ($DB->record_exists('bsu_discipline_stream', $conditions))	{
                  echo $OUTPUT->notification("Группа/подгруппа уже подписана на поток дисциплины.");
		    } else {
		    	if (!$DB->insert_record('bsu_discipline_stream', $conditions)){
                   echo $OUTPUT->notification("Ошибка добавления записи в таблицу.");
		    	}
		    }
        }
	} else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {
		foreach ($frm->removeselect as $groupid_subgrid) {
		    list($groupid, $subgrid) = explode ('_', $groupid_subgrid);
            $conditions = array ('planid' => $frm->pid, 'disciplineid' => $frm->did, 'term' => $frm->term,
                                 'groupid' => $groupid, 'subgroupid' => $subgrid, 'edworkkindid' => $frm->eid); 
			$DB->delete_records('bsu_discipline_stream', $conditions);
		}
	}
}
*/

function  check_exist_stream($frm)
{
    global $DB;
    
    foreach ($frm->addselect as $groupid_subgrid)   {
        list($groupid, $subgrid) = explode ('_', $groupid_subgrid);
    }
    
    
}        

function save_bsu_discipline_groups_stream($frm)
{
    global $DB, $OUTPUT;
    
	if (!empty($frm->add) and !empty($frm->addselect) and confirm_sesskey()) {
		foreach ($frm->addselect as $groupid_subgrid) {
		    list($groupid, $subgrid) = explode ('_', $groupid_subgrid);

            if($frm->term % 2 == 1) $pol = 1; else $pol = 2;
            $cur_term = get_term($groupid, $pol);
            $pid = $DB->get_record_select('bsu_plan_groups', "groupid=$groupid", null, 'planid');
            
            if($frm->term == $cur_term) {
                $where = "planid=$pid->planid AND disciplineid=$frm->did AND term=$frm->term";
            } else {
                $where = "planid=$pid->planid AND s_disciplineid=$frm->did AND s_term=$frm->term";
            }

            if($disciplineid = $DB->get_record_select('bsu_discipline_synonym', $where, null, 'disciplineid, term, s_disciplineid, s_term')) {
                $cur_did = $disciplineid->s_disciplineid;
                $this_did = $disciplineid->disciplineid; 
                $this_term = $disciplineid->s_term;
            } else {
                $this_did = $frm->did;
                $cur_did = $frm->did;
                $this_term = $frm->term;
            }

            $conditions = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplinenameid' => $frm->dnid, 
                                 'term' => $frm->term, 'edworkkindid' => $frm->eid);
            
            add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);
           
            // если поток создается для практики или лаб работ, то добавляем зачеты и экзамены
            if ($frm->eid == 2 || $frm->eid == 3)   {
                $semestrzachet = $DB->get_field_select('bsu_discipline', 'semestrzachet', "id = $frm->did");
                $termhex = dechex($frm->term);
                $termhex = strtoupper($termhex);
                $pos = strpos($semestrzachet, $termhex);
                if (!($pos === false))  {
                    $conditions['edworkkindid'] = 5; // зачет
                    add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);                    
                }
                
                // semestrdiffzach
                $semestrdiffzach = $DB->get_field_select('bsu_discipline', 'semestrdiffzach', "id = $frm->did");
                $termhex = dechex($frm->term);
                $termhex = strtoupper($termhex);
                $pos = strpos($semestrdiffzach, $termhex);
                if (!($pos === false))  {
                    $conditions['edworkkindid'] = 39; // дифф. зачет
                    add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);                    
                }
                
                $semestrexamen = $DB->get_field_select('bsu_discipline', 'semestrexamen', "id = $frm->did");
                $termhex = dechex($frm->term);
                $termhex = strtoupper($termhex);
                $pos = strpos($semestrexamen, $termhex);
                if (!($pos === false))  {
                    $conditions['edworkkindid'] = 4; // экзамен
                    add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);
                    $conditions['edworkkindid'] = 10; // консультация
                    add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);                              
                }

            } else if ($frm->eid == 4)  { // экзамен
                $conditions['edworkkindid'] = 10; // консультация
                add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);                              
            }
            

            // анализируем следующий семестр и если есть, то добавляем
            if($frm->term % 2 == 1)    {
                $nextterm = $frm->term + 1;
                if ($DB->record_exists_select('bsu_discipline_semestr', "disciplineid=$frm->did AND numsemestr=$nextterm")) {
                    $frm->term++;
                    $pol = 2;
                    $cur_term = get_term($groupid, $pol);
            
                    if($nextterm == $cur_term) {
                        $where = "planid=$pid->planid AND disciplineid=$frm->did AND term=$frm->term";
                    } else {
                        $where = "planid=$pid->planid AND s_disciplineid=$frm->did AND s_term=$frm->term";
                    }

                    if($disciplineid = $DB->get_record_select('bsu_discipline_synonym', $where, null, 'disciplineid, term, s_disciplineid, s_term')) {
                        $cur_did = $disciplineid->s_disciplineid;
                        $this_did = $disciplineid->disciplineid; 
                        $this_term = $disciplineid->s_term;
                    } else {
                        $this_did = $frm->did;
                        $cur_did = $frm->did;
                        $this_term = $frm->term;
                    }

                    $conditions = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplinenameid' => $frm->dnid, 
                                         'term' => $nextterm, 'edworkkindid' => $frm->eid);
            
                    add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);
               
                    // если поток создается для практики или лаб работ, то добавляем зачеты и экзамены
                    if ($frm->eid == 2 || $frm->eid == 3)   {
                        // $semestrzachet = $DB->get_field_select('bsu_discipline', 'semestrzachet', "id = $frm->did");
                        $termhex = dechex($frm->term);
                        $termhex = strtoupper($termhex);
                        $pos = strpos($semestrzachet, $termhex);
                        if (!($pos === false))  {
                            $conditions['edworkkindid'] = 5; // зачет
                            add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);                    
                        }
                        
                        // semestrdiffzach
                        // $semestrdiffzach = $DB->get_field_select('bsu_discipline', 'semestrdiffzach', "id = $frm->did");
                        $termhex = dechex($frm->term);
                        $termhex = strtoupper($termhex);
                        $pos = strpos($semestrdiffzach, $termhex);
                        if (!($pos === false))  {
                            $conditions['edworkkindid'] = 39; // дифф. зачет
                            add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);                    
                        }
                        
                        $semestrexamen = $DB->get_field_select('bsu_discipline', 'semestrexamen', "id = $frm->did");
                        $termhex = dechex($frm->term);
                        $termhex = strtoupper($termhex);
                        $pos = strpos($semestrexamen, $termhex);
                        if (!($pos === false))  {
                            $conditions['edworkkindid'] = 4; // экзамен
                            add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);
                            $conditions['edworkkindid'] = 10; // консультация
                            add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);                              
                        }
        
                    } else if ($frm->eid == 4)  { // экзамен
                        $conditions['edworkkindid'] = 10; // консультация
                        add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term);                              
                    }
                    $frm->term--;    
                }
            } // if($frm->term % 2 == 1) {             
        }
	} else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {
		foreach ($frm->removeselect as $groupid_subgrid) {
		    list($groupid, $subgrid) = explode ('_', $groupid_subgrid);

            if($frm->term % 2 == 1) $pol = 1; else $pol = 2;
            $cur_term = get_term($groupid, $pol);
            if ($pid = $DB->get_record_select('bsu_plan_groups', "groupid=$groupid", null, 'planid'))   {
                $where = "planid=$pid->planid AND s_disciplineid=$frm->did AND term=$cur_term";
                if($disciplineid = $DB->get_record_select('bsu_discipline_synonym', $where, null, 'disciplineid, s_disciplineid')) {
                    $cur_did = $disciplineid->disciplineid;
                } else {
                    $cur_did = $frm->did;
                }
            } else {
                $cur_did = $frm->did;
            }    
            
            $conditions = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplinenameid' => $frm->dnid,
                                 'term' => $frm->term, 'edworkkindid' => $frm->eid);
            delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
            if ($frm->eid == 2 || $frm->eid == 3)   {
                $conditions['edworkkindid'] = 5;
                delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
                $conditions['edworkkindid'] = 39;
                delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
                $conditions['edworkkindid'] = 4;
                delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
                $conditions['edworkkindid'] = 10;
                delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
            } else if ($frm->eid == 4)  {
                $conditions['edworkkindid'] = 10;
                delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);                              
            }   
            
            // анализируем следующий семестр и если есть, то удаляем
            if($frm->term % 2 == 1)    {
                $oldterm = $frm->term; 
                $nextterm = $frm->term + 1;
                if ($DB->record_exists_select('bsu_discipline_semestr', "disciplineid=$frm->did AND numsemestr=$nextterm")) {
                    $frm->term = $nextterm;
                    $pol = 2;
                    $cur_term = get_term($groupid, $pol);
                    if ($pid = $DB->get_record_select('bsu_plan_groups', "groupid=$groupid", null, 'planid'))   {
                        $where = "planid=$pid->planid AND s_disciplineid=$frm->did AND term=$cur_term";
                        if($disciplineid = $DB->get_record_select('bsu_discipline_synonym', $where, null, 'disciplineid, s_disciplineid')) {
                            $cur_did = $disciplineid->disciplineid;
                        } else {
                            $cur_did = $frm->did;
                        }
                    } else {
                        $cur_did = $frm->did;
                    }    
            
                    $conditions = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplinenameid' => $frm->dnid,
                                         'term' => $frm->term, 'edworkkindid' => $frm->eid);
                    delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
                    if ($frm->eid == 2 || $frm->eid == 3)   {
                        $conditions['edworkkindid'] = 5;
                        delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
                        $conditions['edworkkindid'] = 39;
                        delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
                        $conditions['edworkkindid'] = 4;
                        delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
                        $conditions['edworkkindid'] = 10;
                        delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);
                    } else if ($frm->eid == 4)  {
                        $conditions['edworkkindid'] = 10;
                        delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did);                              
                    }
                    
                    $frm->term = $oldterm;
                }
            }               
		}
	}
}

// Display list all faculty as popup_form without role
function listbox_department_shortname($scriptname, $fid2, $yid)
{
  global $CFG, $OUTPUT, $DB;

  $facultymenu = array();
  $facultymenu[0] = get_string('selectafaculty', 'block_bsu_plan').'...';

  $sql = "SELECT rd.id, rd.departmentcode, rd.name, rd.shortname  FROM  mdl_bsu_ref_department rd
                inner join mdl_bsu_ref_department_year rdy using(departmentcode)
                where rdy.yearid=$yid
                order by rd.departmentcode";

  if($allfacs = $DB->get_records_sql($sql))   {
		foreach ($allfacs as $faculty) 	{
		    $pos = strpos($faculty->name, '.');
		    if ($pos !== false) {
                // $facultymenu[$faculty->departmentcode] = mb_substr($faculty->name, 0, $pos, 'UTF-8') .  '. ' . $faculty->shortname;
                $facultymenu[$faculty->departmentcode] = $faculty->name;
            }    
		}
  }

  echo $OUTPUT->single_select($scriptname, 'fid2', $facultymenu, $fid2, null, 'switchfacshort');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  return 1;
}



function get_stream_enrolgroups($yid, $planid, $eid, $term, $disciplinenameid, &$egroupids, $ns)
{    
    global $DB;
    /*
    $strsql = "SELECT s.id, s.planid, s.disciplineid, s.groupid, s.subgroupid, s.edworkkindid, g.name as gname  
               FROM {bsu_discipline_stream} s
               INNER JOIN {bsu_ref_groups} g ON g.id=s.groupid
               where  s.disciplineid=$did AND s.edworkkindid=$eid
               order by gname";
    */           

    $strsql = "SELECT s.id, sm.yearid, sm.planid, sm.disciplinenameid, sm.edworkkindid, s.groupid, s.subgroupid, s.numstream, g.name as gname  
               FROM {bsu_discipline_stream} s 
               INNER JOIN {bsu_discipline_stream_mask} sm ON sm.id=s.streammaskid
               INNER JOIN {bsu_ref_groups} g ON g.id=s.groupid
               where sm.yearid = $yid AND sm.planid=$planid AND sm.disciplinenameid=$disciplinenameid AND sm.term=$term AND sm.edworkkindid=$eid
               order by gname"; 
    // echo $strsql . '<br />';    
    $enrolgroupsmenu = array();               
    if($allgroups = $DB->get_records_sql($strsql))   {
        // print_object($allgroups);
		foreach ($allgroups as $group) 	{
		    // $egroupids[] = $group->groupid;
		    if ($group->subgroupid > 0)   {
		        $subgr = $DB->get_record_select('bsu_discipline_subgroup', "id = $group->subgroupid", null, 'id, name');
                $index = $group->groupid . '_' . $subgr->id;
                $egroupids[] = $index;
		    } else {
		        $index = $group->groupid . '_0';
                $egroupids[] = $index;
		    }
            
		    if ($group->numstream != $ns) continue;
		    
		    if ($group->subgroupid > 0)   {
		        $subgr = $DB->get_record_select('bsu_discipline_subgroup', "id = $group->subgroupid", null, 'id, name');
                // $index = $group->groupid . '_' . $subgr->id;
                // $enrolgroupsmenu[$index] = $group->gname . '->'.$subgr->name . ' (' . get_count_students_subgroup($subgr->id) . ' ст.)';;
                $index = $group->groupid . '_' . $group->subgroupid;
                // $enrolgroupsmenu[$index] = $index . '. ' . $group->gname . '->'.$subgr->name . ' (' . get_count_students_subgroup($group->subgroupid) . ' ст.)';
                $enrolgroupsmenu[$index] = $group->gname . '->'.$subgr->name . ' (' . get_count_students_subgroup($group->subgroupid) . ' ст.)';
		    } else {
		        $index = $group->groupid . '_0';
                // $enrolgroupsmenu[$index] = $index . '. ' . $group->gname . ' (' . get_count_students_group2($group->groupid) . ' ст.)';
                $enrolgroupsmenu[$index] = $group->gname . ' (' . get_count_students_group2($group->groupid) . ' ст.)';
		    }
		}
    }
    
    $egroupids = array_unique($egroupids);
    
    return $enrolgroupsmenu;
}    



function get_stream_unenrolgroups($yid, $planid, $fid2, $did, $term, $disciplinenameid, $egroupids) 
{
    global $CFG, $DB, $OUTPUT;

    
/*
    $strsql = "SELECT pg.groupid as gid, g.name as gname  
               FROM {bsu_plan_groups} pg   
               INNER JOIN {bsu_ref_groups} g ON g.id=pg.groupid
               LEFT JOIN {bsu_discipline_stream} s ON pg.groupid=s.groupid
               where pg.planid=$planid AND s.groupid is null 
               order by gname";
*/
/*               
    $strsql = "SELECT pg.groupid as gid, g.name as gname  
               FROM {bsu_plan_groups} pg   
               INNER JOIN {bsu_ref_groups} g ON g.id=pg.groupid
               where pg.planid=$planid 
               order by gname";

    $strsql = "SELECT g.id as gid, g.name as gname  
               FROM {bsu_ref_groups} g 
               where departmentcode=$fid2 
               order by gname";
*/

    // d. disciplinenameid, s.numsemestr
    /*
    echo "SELECT p.id as pid, p.id as pid2 FROM mdl_bsu_plan p
               inner join mdl_bsu_discipline d on p.id=d.planid
               inner join mdl_bsu_discipline_semestr s on d.id=s.disciplineid
               where departmentcode = $fid2 and disciplinenameid= $disciplinenameid and s.numsemestr = $term";
    */               

    if ($term%2 == 0)   {
        $listsemestr = '2,4,6,8,10,12,14';
    } else {
        $listsemestr = '1,3,5,7,9,11,13';
    }

    $strsql = "SELECT distinct p.id as pid FROM mdl_bsu_plan p
               inner join mdl_bsu_discipline d on p.id=d.planid
               inner join mdl_bsu_discipline_semestr s on d.id=s.disciplineid
               where departmentcode=$fid2 and disciplinenameid=$disciplinenameid and s.numsemestr in ($listsemestr) and p.notusing=0 and d.notusing=0";
    // echo $strsql;
    $arr_plan = array();
    if ($aplans = $DB->get_records_sql($strsql))   {
        foreach ($aplans as $ap)   {
            $arr_plan[] = $ap->pid;
        }
    }                
    // print_object($arr_plan);
    
    /*
    $strsql = "SELECT p.id as pid, p.id as pid2 FROM mdl_bsu_plan p
               inner join mdl_bsu_discipline d on p.id=d.planid
               inner join mdl_bsu_discipline_semestr s on d.id=s.disciplineid
               where departmentcode = ? and disciplinenameid= ? and s.numsemestr in (?)";
    if ($arr_group = $DB->get_records_sql_menu($strsql, array($fid2, $disciplinenameid, $listsemestr))) {
         // print_object($arr_group);
         $arr_group[$planid] = $planid;     
    } else {
         $arr_group = array($planid);
        // $planids = 0;
    }
    */
    $is_synonym = false;
    if ($synonyms = $DB->get_records_select('bsu_discipline_synonym', "disciplineid = $did", null, '', 'id, s_planid')) {
        foreach ($synonyms as $synonym) {
            $arr_plan[] = $synonym->s_planid;
            $is_synonym = true;
        }
    }
    
    if (empty($arr_plan))   {
        $planids = $planid;    
    } else {
        $planids = implode(',', $arr_plan);
    }
    
    
    // print_object($arr_group);
    // echo $planids;
    

    $strsql = "SELECT pg.groupid as gid, g.name as gname  
               FROM {bsu_plan_groups} pg   
               INNER JOIN {bsu_ref_groups} g ON g.id=pg.groupid
               where pg.planid in ($planids) 
               order by gname";

    // $course_groups = get_course_groups($fid2, $term);
    // print_object($course_groups);
    // print_object($egroupids);

    $unenrolgroupsmenu = array();
    if ($arr_group = $DB->get_records_sql ($strsql)) 	{
    	foreach ($arr_group as $gr) {
    	   // echo $gr->gid . '<br>';
    	   // if (!in_array($gr->gid, $course_groups))  continue;
           $index = $gr->gid . '_0';
           if (in_array($index, $egroupids)) continue; 
    	   // if ($DB->record_exists_select('bsu_discipline_stream', "groupid = $gr->gid AND disciplineid=$did AND edworkkindid=$eid")) continue;
                       
    		// $unenrolgroupsmenu[$gr->gid . '_0'] = $gr->gid . '_0 .'  . $gr->gname . ' (' . get_count_students_group2($gr->gid) . ' ст.)';
            $unenrolgroupsmenu[$gr->gid . '_0'] = $gr->gname . ' (' . get_count_students_group2($gr->gid) . ' ст.)';
            
            /*
            if ($subgroups = $DB->get_records_select('bsu_discipline_subgroup', "groupid = $gr->gid AND disciplineid=$did", null, 'name', 'id, name'))    {
                foreach ($subgroups as $subgroup)   {
                    $unenrolgroupsmenu[$gr->gid . '_' . $subgroup->id] = $gr->gname . '->' . $subgroup->name;
                }
            }
            */
            // if ($subgroups = $DB->get_records_select('bsu_discipline_subgroup', "groupid = $gr->gid", null, 'name', 'id, disciplineid, name'))    {
            if ($subgroups = $DB->get_records_select('bsu_discipline_subgroup', "yearid = $yid and groupid = $gr->gid", null, 'name', 'id, disciplineid, name'))    {
                foreach ($subgroups as $subgroup)   {
                    if ($disc = $DB->get_record_select('bsu_discipline', "id = $subgroup->disciplineid", null, 'id, planid, disciplinenameid')) {
                        // $depcode = $DB->get_field_select('bsu_plan', 'departmentcode', "id=$disc->planid");
                        // if ($depcode == 0) continue;
                        $bsu_plan = $DB->get_record_select('bsu_plan', "id=$disc->planid", null, 'id, departmentcode, deleted, notusing');
                        if ($bsu_plan->departmentcode == 0 || $bsu_plan->deleted == 1 || $bsu_plan->notusing == 1) continue;
                        
                        if ($disc->disciplinenameid == $disciplinenameid)   {
                            $index = $gr->gid . '_' . $subgroup->id;
                            if (in_array($index, $egroupids)) continue;
                            // $unenrolgroupsmenu[$index] = $index . '. '. $gr->gname . '->' . $subgroup->name . ' (' . get_count_students_subgroup($subgroup->id) . ' ст.)';
                            $unenrolgroupsmenu[$index] = $gr->gname . '->' . $subgroup->name . ' (' . get_count_students_subgroup($subgroup->id) . ' ст.)';
                        }  else if ($is_synonym)    {
                            if ($DB->record_exists_select('bsu_discipline_synonym', "yearid=$yid and disciplineid = $did and disciplinenameid = $disciplinenameid and s_disciplinenameid = $disc->disciplinenameid"))   {
                                $index = $gr->gid . '_' . $subgroup->id;
                                if (in_array($index, $egroupids)) continue;
                                // $unenrolgroupsmenu[$index] = $index . '. '. $gr->gname . '->' . $subgroup->name . ' (' . get_count_students_subgroup($subgroup->id) . ' ст.)';
                                $unenrolgroupsmenu[$index] = $gr->gname . '->' . $subgroup->name . ' (' . get_count_students_subgroup($subgroup->id) . ' ст.)';
                            }
                        }  
                    }   
                }
            }
            
    	}
    }
    // print_object($unenrolgroupsmenu);
    
    return $unenrolgroupsmenu;
}    


function get_course_groups($fid, $term)
{
	global $CFG, $OUTPUT, $DB;

    $yid = get_current_edyearid();
    if ($term % 2 != 0) {
        $term++;
    }
    $course = $term/2;

	$like = substr($fid, 1, 2);
	$yid = $DB->get_record_sql("SELECT god FROM {$CFG->prefix}bsu_ref_edyear WHERE id=$yid");
	$yid = substr($yid->god, -4, 2);

	if($course != 1) $yid = $yid - $course + 1;
	if(strlen($yid) == 1) $yid = '0'.$yid;
	$like.= $yid;

    if($course != 0) {
    	$strsql = "SELECT id, departmentcode, idedform, name FROM {bsu_ref_groups}
    	where departmentcode = $fid AND name LIKE '$like%'
    	ORDER BY name DESC";
    } else {
        $strsql = "SELECT id, departmentcode, idedform, name FROM {bsu_ref_groups}
    	where departmentcode = $fid 
    	ORDER BY name DESC";
    }

	$groupmenu = array();    
	if ($arr_group = $DB->get_records_sql ($strsql)) 	{
		foreach ($arr_group as $gr) {
			// $groupmenu[$gr->id] = $gr->name;
            $groupmenu[] = $gr->id;
		}
	}
    
    return $groupmenu;
}

// (при отписывании группы или подгруппы <br> будет удалено расписание дисциплины <br>для данного типа занятия)


function print_tabs_streams($scriptname, $currtab, $fid, $did, $yid, $planid, $eid, $term)
{
	GLOBAL $DB, $USER;
    
    $dnid = $DB->get_field_select('bsu_discipline', 'disciplinenameid', "id = $did");
    
    $MAXSTREAM = 12;
    
    if ($fid == 10600)    {
        $MAXSTREAM = 25;
    }
    if ($fid == 10301)    {
        $MAXSTREAM = 25;
    }
    if ($dnid == 51)    {
        $MAXSTREAM = 21;
    }
    
	$strterm = 'stream';
    $currtab = $strterm.$currtab;

    $toprow = array();
    $starti = 1;
    if ($USER->id == 3)   {
        $starti = 0;
    }
    for ($i=$starti; $i<=$MAXSTREAM; $i++)   {
        // if ($fid == 110)    {
               $sql = "SELECT s.id  
               FROM mdl_bsu_discipline_stream s 
               INNER JOIN mdl_bsu_discipline_stream_mask sm ON sm.id=s.streammaskid
               where sm.yearid = $yid AND sm.planid=$planid AND sm.disciplinenameid=$dnid 
                    AND sm.term=$term AND sm.edworkkindid=$eid AND numstream=$i"; 
               // echo $sql . '<br />';      
               if($allgr = $DB->get_records_sql($sql))   {
                    // print_object($allgr);
                    $cnt = count($allgr);
               } else {
                    $cnt = 0;
               }     
               $toprow[] = new tabobject($strterm.$i , $scriptname."&ns=$i", 'Поток №'.$i . " ($cnt)");
        /* } else {
            $toprow[] = new tabobject($strterm.$i , $scriptname."&ns=$i", 'Поток №'.$i);
        }  
        */  
    }
    $tabs = array($toprow);
    print_tabs($tabs, $currtab, NULL, NULL);
}


function get_count_students_group2($groupid)
{
    global $DB;
    
    if (!$cnt = $DB->get_field_select('bsu_ref_groups', 'countstud', "id = $groupid"))    {
       $cnt = 0;
    }    
    
    return $cnt; 
}


function get_count_students_subgroup($subgroupid)
{
    global $DB;
    
    $cnt = $DB->count_records_select('bsu_discipline_subgroup_members', "subgroupid=$subgroupid and deleted=0");
    
    if ($cnt==0) {
       if (!$cnt = $DB->get_field_select('bsu_discipline_subgroup', 'countstud', "id = $subgroupid"))    {
         $cnt = 0;
       }   
    }
    

    return $cnt; 
    
}


function check_existing_stream($yid, $planid, $eid, $term, $did, $agroups)
{
    global $DB, $fid;
    
    $discipline = $DB->get_record_select('bsu_discipline', "id = $did", null, 'id, disciplinenameid');
    $disciplinenameid = $discipline->disciplinenameid;

    $table = new html_table();
    $table->head = array ('Группа', 'План', 'Семестр', 'Потоки');
    // $table->align = array ('center', 'center');
            
    $alreadyin = array();
    foreach ($agroups as $agroup)   {
        // echo $agroup .'<br />';       
        if (!$groupid = $DB->get_field_select('bsu_ref_groups', 'id', "name = '$agroup'")) return;
        $sql = "SELECT s.id, s.groupid, s.subgroupid, s.numstream, sm.planid, sm.disciplinenameid, sm.term as numsemestr
               FROM mdl_bsu_discipline_stream s 
               INNER JOIN mdl_bsu_discipline_stream_mask sm ON sm.id=s.streammaskid
               where sm.yearid = $yid AND sm.disciplinenameid=$disciplinenameid 
                    AND sm.term=$term AND sm.edworkkindid=$eid AND s.groupid=$groupid";
        // echo $sql . '<br />';  
        if($allgrs = $DB->get_records_sql($sql))   {
            foreach ($allgrs as $allgr) {
                if ($allgr->planid != $planid && !in_array($allgr->planid, $alreadyin))  {
                    $alreadyin[] = $allgr->planid; 
                    $notusing = $DB->get_field_select('bsu_plan', 'notusing', "id = $allgr->planid");
                    if ($notusing == 0) {
                        $plan = $DB->get_record_select('bsu_plan', "id = $allgr->planid"); 
                        $disciplineid = $DB->get_field_select('bsu_discipline', 'id', "planid=$allgr->planid AND disciplinenameid=$disciplinenameid");
                        $link = "<a href = \"streamgroups.php?yid=$yid&eid=$eid&fid={$plan->departmentcode}&pid={$allgr->planid}&term={$term}&tab=$eid&did=$disciplineid\">";
                        $prstream = get_list_streams_for_edworkkind($yid, $allgr->planid, $allgr, $eid);
                        $table->data[] = array('<b>' . $agroup . '</b>', $link . $allgr->planid. '. ' . $plan->name . '</a>', $term, $link . $prstream . '</a>'); 
                    }        
                }
            }    
        }
    }
    
    if (isset($table->data))    {
        $msg = "<strong>ВНИМАНИЕ! <br />По данной дисциплине уже существуют потоки в других планах. <br />
                ДУБЛИРОВАТЬ СУЩЕСТВУЮЩИЕ ПОТОКИ В ДАННОМ ПЛАНЕ НЕЛЬЗЯ!<br />
                Можно создавать только те потоки, которые ПОЛНОСТЬЮ отличаются <br />
                от уже существующих и созданных  в других планах.</strong>";
        notify($msg);
        echo '<center>'. html_writer::table($table) . '</center>';
    }
}



function add_one_group_to_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did, $this_did, $this_term)  
{
    global $DB, $OUTPUT, $USER;
    
    // print_object($frm);    
    $whatedworkkindid =  $conditions['edworkkindid'];    
    $fullname = fullname($USER);     
    
    // проверяем, существует ли уже поток, в которую добавляем группу
    if ($streammask = $DB->get_record('bsu_discipline_stream_mask', $conditions)) {
          $conditions2 = array ('groupid' => $groupid, 'subgroupid' => $subgrid, 'streammaskid' => $streammask->id, 'numstream' => $frm->ns);
          if ($DB->record_exists('bsu_discipline_stream', $conditions2))	{
            echo $OUTPUT->notification("Группа/подгруппа уже подписана на поток дисциплины.");
          } else {
            $conditions3 = array ('streammaskid' => $streammask->id, 'numstream' => $frm->ns);
//print $streammask->id;                     
            if($streams = $DB->get_records_sql_menu("SELECT id, groupid FROM {bsu_discipline_stream} WHERE streammaskid=$streammask->id AND numstream=$frm->ns")) {
                $gid = reset($streams);
                $groupname = get_group_name($gid);
                $term = get_term($groupname, $pol);
                
                $did_name = $DB->get_record_select('bsu_discipline', "id=$frm->did", null, 'disciplinenameid');
                $did_name = $did_name->disciplinenameid;
                
                // проверяем наличие расписания у группы    
                if($schedules = $DB->get_records_select('bsu_schedule', "disciplinenameid=$did_name AND term=$term AND edworkindid=$frm->eid AND groupid=$gid AND yearid=$frm->yid", null, '', 'id, schedulemaskid, timestart, timeend, weekday, datestart, periodweek')) { 
                    $insert = true;
                    $id = 0;
                    foreach($schedules as $m) {
                        if ($verify = verify_group_pair($m->timestart, $m->timeend, $groupid, $m->weekday, $m->datestart, $m->periodweek, $m->datestart, $subgrid, $frm->yid))  {
                            $insert = false;
                        }
                        $id = $m->schedulemaskid;                                                 
                    }
                    if($insert) {
                        $departmentcode = $DB->get_record_select('bsu_ref_groups', "id=$groupid", null, 'departmentcode');
                        $departmentcode = $departmentcode->departmentcode;
                        $m = $DB->get_record_select('bsu_schedule_mask', "id=$id", null, '*');
                        
                        unset($m->id);
                        $schedule = new stdClass();                                                        
                        $schedule = $m;
                        
                        $schedule->groupid = $groupid;
                        $schedule->subgroupid = $subgrid;
                        $schedule->disciplineid = $this_did;
                        $schedule->term = $cur_term;
                        $schedule->departmentcode = $departmentcode;
                        $schedule->yearid = $frm->yid;

                        $schedulemaskid = $DB->insert_record('bsu_schedule_mask', $schedule);
                        $schedule->schedulemaskid = $schedulemaskid;
//print_object($schedule);
                        insert_record_schedule($schedule);
                        
                        if ($DB->insert_record('bsu_discipline_stream', $conditions2)){
                            add_to_bsu_plan_log('stream:add', $frm->pid, $cur_did, "planid=$frm->pid&groupid=$groupid&subgroupid=$subgrid&numstream={$frm->ns}&edworkkindid={$whatedworkkindid}&term={$frm->term}", $fullname);
                            update_charge_add_new_stream($frm, $conditions2, $whatedworkkindid);
                        } else {
                            echo $OUTPUT->notification("Ошибка добавления записи в таблицу bsu_discipline_stream.");
                        }
                    } else {
                        $table = '';
                        if($verify) {
                            $verify = implode(',', $verify);
                            $table = loaded_schedule($verify, 'schedulemaskid');
                        }
//print_object($verify);                            
                        $groupname_print = get_group_name("$groupid,$subgrid");
                        echo $OUTPUT->notification("Нельзя добавить группу ($groupname_print) в поток, т.к. у группы уже есть занятие.");
                        if($table) {
                            echo'<center>'.html_writer::table($table).'</center>';
                            unset($table);
                        }
                    }
                } else {
                    if ($DB->insert_record('bsu_discipline_stream', $conditions2)){
                        add_to_bsu_plan_log('stream:add', $frm->pid, $cur_did, "planid=$frm->pid&groupid=$groupid&subgroupid=$subgrid&numstream={$frm->ns}&edworkkindid={$whatedworkkindid}&term={$frm->term}", $fullname);
                        update_charge_add_new_stream($frm, $conditions2, $whatedworkkindid);
                    }  else {
                        echo $OUTPUT->notification("Ошибка добавления записи в таблицу bsu_discipline_stream.");
                    }
//print "NO SCHEDULE";
//print $OUTPUT->notification("Нельзя добавить группу в поток, т.к. у группы уже есть занятие на эту пару.");
                }
            } else {
//                        
//        		    	if ($newid = $DB->insert_record('bsu_discipline_stream_mask', $conditions)) {
 	                $conditions2 = array ('groupid' => $groupid, 'subgroupid' => $subgrid, 'streammaskid' => $streammask->id, 'numstream' => $frm->ns);
    		    	if ($DB->insert_record('bsu_discipline_stream', $conditions2)){
    		    	    // add_to_log(1, 'stream', 'add', "planid=$pid->planid&groupid=$groupid&subgroupid=$subgrid&numstream={$frm->ns}", $fullname, $cur_did, $USER->id);
                        add_to_bsu_plan_log('stream:add', $frm->pid, $cur_did, "planid=$frm->pid&groupid=$groupid&subgroupid=$subgrid&numstream={$frm->ns}&edworkkindid={$whatedworkkindid}&term={$frm->term}", $fullname);
                        update_charge_add_new_stream($frm, $conditions2, $whatedworkkindid);
    		    	}  else {
                       echo $OUTPUT->notification("Ошибка добавления записи в таблицу bsu_discipline_stream.");
                    }
/*                            
		    	} else {
		    	     echo $OUTPUT->notification("Ошибка добавления записи в таблицу bsu_discipline_stream_mask.");
		    	}
*/                        
            }
        }
    // иначе создаем новый поток, в которую добавляем группу        
    } else { 
    	if ($newid = $DB->insert_record('bsu_discipline_stream_mask', $conditions)) {
            $conditions2 = array ('groupid' => $groupid, 'subgroupid' => $subgrid, 'streammaskid' => $newid, 'numstream' => $frm->ns);
	    	if (!$DB->insert_record('bsu_discipline_stream', $conditions2)){
               echo $OUTPUT->notification("Ошибка добавления записи в таблицу bsu_discipline_stream.");
	    	}  else {
	    	    // add_to_log(1, 'stream', 'add', "planid=$pid->planid&groupid=$groupid&subgroupid=$subgrid&numstream={$frm->ns}", $fullname, $cur_did, $USER->id);
                add_to_bsu_plan_log('stream:add', $frm->pid, $cur_did, "planid=$frm->pid&groupid=$groupid&subgroupid=$subgrid&numstream={$frm->ns}&edworkkindid={$whatedworkkindid}&term={$frm->term}", $fullname);
                update_charge_add_new_stream($frm, $conditions2, $whatedworkkindid);
            }
    	} else {
    	     echo $OUTPUT->notification("Ошибка добавления записи в таблицу bsu_discipline_stream_mask.");
    	}
    }
} // end function



function delete_one_group_from_stream($frm, $conditions, $pid, $groupid, $subgrid, $pol, $cur_term, $cur_did)
{
    global $OUTPUT, $DB, $USER;

    // print_object($frm);
    // print_object($conditions);
    $whatedworkkindid =  $conditions['edworkkindid'];    
    $fullname = fullname($USER);     
    
    if ($streammasks = $DB->get_records('bsu_discipline_stream_mask', $conditions))	{
      foreach ($streammasks as $streammask)   {
          $conditions2 = array ('groupid' => $groupid, 'subgroupid' => $subgrid, 
                                'streammaskid' => $streammask->id, 'numstream' => $frm->ns);
          if ($DB->record_exists('bsu_discipline_stream', $conditions2)) {
              // add_to_log(1, 'stream', 'delete', "planid=$pid->planid&groupid=$groupid&subgroupid=$subgrid&numstream={$frm->ns}", $fullname, $cur_did, $USER->id);
              add_to_bsu_plan_log('stream:delete', $frm->pid, $cur_did, "planid=$frm->pid&groupid=$groupid&subgroupid=$subgrid&numstream={$frm->ns}&edworkkindid={$whatedworkkindid}&term={$frm->term}", $fullname);
		      // ===== изменяем потоки
              $DB->delete_records('bsu_discipline_stream', $conditions2);
             
              if (!$DB->record_exists_select('bsu_discipline_stream', "streammaskid = $streammask->id"))    {
                    $DB->delete_records_select('bsu_discipline_stream_mask', "id = $streammask->id");                
              }
              
              // ===== изменяем расписание
              // $DB->delete_records('bsu_schedule_mask', array ('yearid' => $frm->yid, 'groupid' => $groupid, 'disciplineid' => $cur_did, 'term' => $cur_term, 'edworkindid' => $frm->eid));
              // $DB->delete_records('bsu_schedule', array ('yearid' => $frm->yid, 'groupid' => $groupid, 'disciplineid' => $cur_did, 'term' => $cur_term, 'edworkindid' => $frm->eid));
              $conditions3 = array ('yearid' => $frm->yid, 'groupid' => $groupid, 'disciplineid' => $cur_did, 'term' => $cur_term, 'edworkindid' => $frm->eid);
              // print_object($conditions3);
              $DB->set_field('bsu_schedule_mask', 'deleted', 1, $conditions3);
              $DB->set_field('bsu_schedule', 'deleted', 1, $conditions3);
              /*
              if ($schedules = $DB->get_records('bsu_schedule_mask', array ('yearid' => $frm->yid, 'groupid' => $groupid, 'disciplineid' => $cur_did, 'term' => $cur_term, 'edworkindid' => $frm->eid), '', 'id'))    {
                    foreach ($schedules as $schedule)   {
                        
                        $schedule
                    }  
              }
              */
              
              // ===== изменяем нагрузку
              if ($edwork = $DB->get_record('bsu_edwork', $conditions2))	{
                    // проверяем сколько записей в потоке
                    $sql = "SELECT count(*) as cnt FROM mdl_bsu_edwork where edworkmaskid=$edwork->edworkmaskid";
                    $cntedworks = $DB->get_field_sql($sql);
                    if ($cntedworks > 1) {
                        $sql = "SELECT edm.id FROM dean.mdl_bsu_edwork_mask edm
                                inner join dean.mdl_bsu_edwork e on edm.id=e.edworkmaskid
                                where edm.yearid= $frm->yid and edm.planid = $edwork->planid and  edm.disciplinenameid = $edwork->disciplinenameid
                                and edm.term = $edwork->term and edm.edworkkindid = $edwork->edworkkindid 
                                and e.subdepartmentid = $edwork->subdepartmentid and e.streammaskid=0";
                        // print $sql . '<br />';
                        // если уже есть нагрузка без потока, то переводим  туда
                        if ($edwmasks = $DB->get_records_sql($sql))	{
                            $edwmask = reset($edwmasks);
                            $edwork->edworkmaskid = $edwmask->id;
                            $edwork->streammaskid = 0;
                            $edwork->numstream = 0;
                            if (!$DB->update_record('bsu_edwork', $edwork)) {
                                print_object($edwork);
                                $OUTPUT->notification('Ошибка обновления в bsu_edwork.');
                            }
                            if ($edwork->edworkkindid != 1)  {
                                $sql = "SELECT sum(hours) as hours FROM {bsu_edwork} WHERE edworkmaskid=$edwmask->id";
                                if ($hour = $DB->get_record_sql($sql))  {
                                    $DB->set_field_select('bsu_edwork_mask',  'hours', $hour->hours, "id=$edwmask->id");
                                }
                            }
                            
                            
                        } else { // иначе выводим группу (подгруппу) из потока и создаем отдельную нагрузку
                            $edworkmask = new stdClass();
                            $edworkmask->yearid = $edwork->yearid;
                            $edworkmask->planid = $edwork->planid;
                            $edworkmask->disciplinenameid = $edwork->disciplinenameid;
                            $edworkmask->term = $edwork->term;  
                            $edworkmask->edworkkindid = $edwork->edworkkindid; 
                            $edworkmask->hours = $edwork->hours;
                            $edworkmask->practiceid = 0;
                            $edworkmask->subdepartmentid = $edwork->subdepartmentid;
                            if ($edworkmaskid = $DB->insert_record('bsu_edwork_mask', $edworkmask)) {
                               $edwork->edworkmaskid = $edworkmaskid;
                               $edwork->streammaskid = 0;
                               $edwork->numstream = 0;
                               if (!$DB->update_record('bsu_edwork', $edwork)) {
                                    print_object($edwork);
                                    $OUTPUT->notification('Ошибка обновления в bsu_edwork.');
                               }
                            }
                        }        
                    } else if ($cntedworks == 1) {
                        // находим запись без потока
                        $conditions4 = array ('yearid' => $edwork->yearid, 'planid' => $edwork->planid, 
                                'disciplineid' => $edwork->disciplineid, 'term' => $edwork->term, 
                                'edworkkindid' => $edwork->edworkkindid, 'streammaskid' => 0, 'subdepartmentid' =>  $edwork->subdepartmentid);
                        if ($edworkexist = $DB->get_record('bsu_edwork', $conditions4))	{
                            
                            // удаляем маску, связанную с потоком
                            $DB->delete_records_select('bsu_edwork_mask', "id = $edwork->edworkmaskid");
                            
                            // если была уже распределена нагрузка, то переводим её на существующую edworkmaskid, а не удаляем
                            // $DB->delete_records_select('bsu_teachingload', "edworkmaskid = $edwork->edworkmaskid"); 
                            $sql = "SELECT id FROM mdl_bsu_teachingload 
                                    where edworkmaskid=$edwork->edworkmaskid";
                            if ($tl = $DB->get_record_sql($sql)) {
                             
                               $tl->edworkmaskid = $edworkexist->edworkmaskid;
                               $tl->streammaskid = 0;
                               $tl->numstream = 0;
                               if (!$DB->update_record('bsu_teachingload', $tl)) {
                                    print_object($tl);
                                    $OUTPUT->notification('Ошибка обновления в bsu_teachingload.');
                               }
                            }   
                            
                            // переводим нагрузку под существующую маску без потока
                            $edwork->edworkmaskid = $edworkexist->edworkmaskid;
                            $edwork->streammaskid = 0;
                            $edwork->numstream = 0;
                            if (!$DB->update_record('bsu_edwork', $edwork)) {
                                print_object($edwork);
                                $OUTPUT->notification('Ошибка обновления в bsu_edwork.');
                            }
                            
                            
                            if ($edwork->edworkkindid != 1)  {
                                $sql = "SELECT sum(hours) as hours FROM {bsu_edwork} WHERE edworkmaskid=$edworkexist->edworkmaskid";
                                if ($hour = $DB->get_record_sql($sql))  {
                                    $DB->set_field_select('bsu_edwork_mask',  'hours', $hour->hours, "id=$edworkexist->edworkmaskid");
                                }
                            }
                        } else {    
                            // обновляем существующие записи и в маске и в edwork
                            if ($edmask = $DB->get_record_select('bsu_edwork_mask', "id = $edwork->edworkmaskid")) {
                                // print_object($edmask);
                                $edmask->hours =  $edwork->hours;
                                $edmask->planid =  $edwork->planid;
                                $DB->update_record('bsu_edwork_mask', $edmask);
                                
                                // $DB->set_field_select('bsu_edwork', 'streammaskid',  0, "id = $edwork->id");
                                $edwork->streammaskid = 0;
                                $edwork->numstream = 0;
                                if (!$DB->update_record('bsu_edwork', $edwork)) {
                                    print_object($edwork);
                                    $OUTPUT->notification('Ошибка обновления в bsu_edwork.');
                                }
                                
                                if ($teachingloadings = $DB->get_records_select('bsu_teachingload', "edworkmaskid = $edwork->edworkmaskid")) {
                                    foreach ($teachingloadings as $teachingloading) {
                                        $teachingloading->streammaskid = 0;
                                        $teachingloading->numstream = 0;
                                        if (!$DB->update_record('bsu_teachingload', $teachingloading)) {
                                            print_object($edwork);
                                            $OUTPUT->notification('Ошибка обновления в bsu_teachingload.');
                                        }
                                    }
                                }
                            }
                        }    
                    } else {
                        // print $sql . '<br />???';
                        // ???
                    }      
              }      
          }    
       }   
    }  
}  // end function



function update_charge_add_new_stream($frm, $conditions2, $whatedworkkindid)
{
    global $DB, $OUTPUT;
    
    // print_object($frm);
    // print_object($conditions2);
    $streammaskid = $conditions2['streammaskid'];
    $numstream = $conditions2['numstream'];
    
    $edwork = false;
    // находим существующую нагрузку
    $conditions4 = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplineid' => $frm->did, 'term' => $frm->term, 
                          //'edworkkindid' => $frm->eid, 
                          'edworkkindid' => $whatedworkkindid,
                          'groupid' => $conditions2['groupid'], 'subgroupid' => $conditions2['subgroupid']);
    // print_object($conditions4);                          
    if (!$edwork = $DB->get_record('bsu_edwork', $conditions4))	{
        // если нагрузку не нашли, может быть группа из другого плана
        $conditions5 = array ('yearid' => $frm->yid, 'term' => $frm->term, 'edworkkindid' => $whatedworkkindid, 'disciplinenameid' => $frm->dnid,
                              'groupid' => $conditions2['groupid'], 'subgroupid' => $conditions2['subgroupid']);
        // print_object($conditions5); print '<hr />';                          
        if (!$edwork = $DB->get_record('bsu_edwork', $conditions5))	{
            // print_object($edwork);
            $edwork = false;
        } else {
            // print_object($edwork);
        }
    }        
        
    
    if ($edwork)    {    
        // print_object($edwork);
        $oldedworkmaskid = $edwork->edworkmaskid;
        // проверяем есть ли нагрузка для потока        
        $sql = "SELECT edworkmaskid FROM mdl_bsu_edwork 
                where streammaskid=$streammaskid and numstream = $numstream
                limit 1";
        if ($edworkmaskid = $DB->get_field_sql($sql)) {
            $edwork->edworkmaskid = $edworkmaskid;
            $edwork->streammaskid = $streammaskid; 
            $edwork->numstream = $numstream; // $conditions2['numstream'];
           
            if (!$DB->update_record('bsu_edwork', $edwork)) {
                print_object($edwork);
                $OUTPUT->notification('Ошибка обновления в bsu_edwork.');
            }
            
        }  else {
            // вводим группу (подгруппу) в поток и создаем отдельную нагрузку
            $edworkmask = new stdClass();
            $edworkmask->yearid = $edwork->yearid;
            $edworkmask->planid = $frm->pid;// $edwork->planid -- это ОШИБКА!;
            $edworkmask->disciplinenameid = $edwork->disciplinenameid;
            $edworkmask->term = $edwork->term;  
            $edworkmask->edworkkindid = $edwork->edworkkindid; 
            $edworkmask->hours = $edwork->hours;
            $edworkmask->practiceid = 0;
            $edworkmask->subdepartmentid = $edwork->subdepartmentid;
            if ($edworkmaskid = $DB->insert_record('bsu_edwork_mask', $edworkmask)) {
               // $edwork->planid = $edwork->planid;
               $edwork->edworkmaskid = $edworkmaskid;
               $edwork->streammaskid = $streammaskid;
               $edwork->numstream = $conditions2['numstream'];
               if (!$DB->update_record('bsu_edwork', $edwork)) {
                    print_object($edwork);
                    $OUTPUT->notification('Ошибка обновления в bsu_edwork.');
               }
            }  
        }  
        
        if ($whatedworkkindid <> 1) {
            // если была уже распределена нагрузка, то переводим её на новый edworkmaskid 
            $sql = "SELECT id FROM mdl_bsu_teachingload 
                    where edworkmaskid=$oldedworkmaskid and groupid=$edwork->groupid and subgroupid=$edwork->subgroupid";
            if ($tl = $DB->get_record_sql($sql)) {
             
               $tl->edworkmaskid = $edworkmaskid;
               $tl->streammaskid = $streammaskid;
               $tl->numstream = $conditions2['numstream'];
               if (!$DB->update_record('bsu_teachingload', $tl)) {
                    print_object($tl);
                    $OUTPUT->notification('Ошибка обновления в bsu_teachingload.');
               }
            }
         }      
        
        // если у старой маски не осталось подчиненных записей, то даляем маску
        if (!$DB->record_exists_select('bsu_edwork', "edworkmaskid = $oldedworkmaskid"))    {
            // echo "delete_records_select bsu_edwork_mask id = $oldedworkmaskid<br />";
            $DB->delete_records_select('bsu_edwork_mask', "id = $oldedworkmaskid");
            $DB->delete_records_select('bsu_teachingload', "edworkmaskid = $oldedworkmaskid");                                        
        } else {
            if ($whatedworkkindid != 1)  {
                $sql = "SELECT sum(hours) as hours FROM {bsu_edwork} WHERE edworkmaskid=$oldedworkmaskid";
                if ($hour = $DB->get_record_sql($sql))  {
                    $DB->set_field_select('bsu_edwork_mask',  'hours', $hour->hours, "id=$oldedworkmaskid");
                }
            }
            
        }
   
    }
}        
?>