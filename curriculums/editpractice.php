<?PHP // $Id: editpractice.php,v 1.2 2012/12/06 09:51:45 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");
    require_once("../../bsu_charge/lib_charge.php"); 
    require_once("../../bsu_charge/lib_charge_spec.php");

    // $yid = required_param('yid', PARAM_INT);
    $yid = optional_param('yid', 0, PARAM_INT);            //
    $fid = required_param('fid', PARAM_INT);
    $pid = optional_param('pid', 0, PARAM_INT);            // plan id    
	$prid = optional_param('prid', 0, PARAM_INT);			// Practece id
    $eid = optional_param('eid', 13, PARAM_INT);			// edworkkindid
    $tab = optional_param('tab', 'plan', PARAM_TEXT);        
    $confirm = optional_param('confirm', 0, PARAM_INT);			// confirm
    $action = optional_param('action', 'edit', PARAM_ALPHA);    // 
    $wherereturn = optional_param('wret', 'disciplines.php', PARAM_ALPHA);    
    
    if ($yid == 0)  {
        $yid = get_current_edyearid();
    }

    // $term = optional_param('term', 100, PARAM_INT);
    if ($eid == 13) {
        $term = 100;
    } else {
        $term = 101;
    }
    
    require_login();

    $redirlink = "$wherereturn?yid=$yid&fid=$fid&pid=$pid&prid=$prid&tab=$tab&term=$term"; // 
    
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    if ($eid == 13) {
        $strtitle3 = 'Практики';
        $strscript = 'Редактирование практики';
    } else {
        $strtitle3 = 'Спец. вида работа';
        $strscript = 'Редактирование спец. вида работа';
        
    }    
    
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
                                                                           'term' => $term, 'tab' => $tab)));

    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

    $scriptname = "editpractice.php";        
    $strlistfaculties =  listbox_department($scriptname."?eid=$eid", $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

    if($confirm == 1 && confirm_sesskey() && $action == 'del') {
        $prevyid = $yid - 1;
        if ($edworks =$DB->get_records_select('bsu_edwork', "yearid = $prevyid AND practiceid = $prid", null, '', 'id, planid'))   {
            notify ('Нельзя удалить практику, т.к. для неё сформирована нагрузка.');
        } else {    
            $practice = $DB->get_record_select('bsu_plan_practice', "id=$prid");
        	$DB->delete_records_select('bsu_plan_practice', "id=$prid");
            $DB->delete_records_select('bsu_plan_practice_subdep', "practiceid=$prid");
            // $yid = get_current_edyearid();
            delete_pactice_charge(0, $prid);        
            // add_to_log(1, 'practice', 'delete', "planid=$pid&practice=$prid&practicetypeid=$practice->practicetypeid", $practice->name, $practice->edworkkindid, $USER->id);
            add_to_bsu_plan_log('practice:delete', $pid, $prid, "planid=$pid&practice=$prid&practicetypeid=$practice->practicetypeid&edworkkindid={$practice->edworkkindid}&term={$practice->term}", $practice->name);                        
            // $redirlink .= "&term=$term";
            redirect($redirlink, 'Запись удалена.', 0); 
        }    
    }

    echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
    echo $strlistfaculties;
    $kp = false;
    if($fid > 0){
        // listbox_plan($scriptname."?yid=$yid&tab=$tab&fid=$fid&eid=$eid", $fid, $pid);
        
        if ($pid > 0)   {
            echo '<tr align="left"> <td align=right>'.get_string('curriculum', 'block_bsu_plan').': </td><td align="left">';
            echo '<strong>' . $DB->get_field_select('bsu_plan', 'name', "id = $pid") . '</strong>';
            echo '</td></tr>';
            
            $kp = true;
            /*
            if ($eid == 13) {
                listbox_practice($scriptname."?eid=$eid&fid=$fid&pid=$pid&term=$term", $pid, $prid);
            } else {
                listbox_specvidrabot($scriptname."?eid=$eid&fid=$fid&pid=$pid&term=$term", $pid, $prid);
            } 
               
            if ($prid > 0) {
                    $kp = true;
            }
            */
        }
     }
     echo '</table>';        
    
    
 
    if ($kp)    {
       $edformid = $DB->get_field_select('bsu_plan', 'edformid', "id = $pid");
       switch($action) {
        
    	   case 'add':
                if ($frm = data_submitted())    {
                    $eid = $frm->edworkkindid;
                }
                 
                $practice = new stdClass();
                $practice->id = 0;
                $practice->edworkkindid = $eid;
        		$form = new practice_form('editpractice.php');
                // $form->set_data($practice);
                
                if ($form->is_cancelled()) {
                     // $redirlink .= "&term=100";
                     redirect($redirlink, '', 0);
                } else {
                    if ($prnew = $form->get_data()) {
                        $prnew->planid = $pid;
                        $prnew->edworkkindid = $eid;
             			$prnew->timemodified = time();
                        $prnew->modifierid = $USER->id;

                        // print_object($prnew);
                        
                        if ($practice->edworkkindid == 13)  {
            				if (!$prid = $DB->insert_record('bsu_plan_practice', $prnew))   {
            				    print_object($prnew);
            				    print_error('Not insert', 'block_bsu_plan');
            				}
                            // $redirlink .= "&term=100";
                        } else {
            				if (!$prid = $DB->insert_record('bsu_plan_practice', $prnew))   {
            				    print_object($prnew);
            				    print_error('Not insert', 'block_bsu_plan');
            				}
                            // $redirlink .= "&term=101";
                            
                        }    
                        redirect($redirlink, get_string('changessaved'), 0);
                    }    
        		}        		
        
        		echo '<table align="center" width="50%"><tr><td>';
        		$form->display();
        		echo '</td></tr></table>';
    	   break;
        
    	   case 'edit':
           case 'editspec':
                // id, planid, practicetypeid, name, term, week
        		$practice = $DB->get_record_select('bsu_plan_practice', "id=$prid");
                // print_object($practice);
        
        		$form = new practice_form('editpractice.php');
        		$form->set_data($practice);
    
                
                if ($form->is_cancelled()) {
                     // $redirlink .= "&term=100";
                     redirect($redirlink, '', 0);
                } else {
                    if ($prnew = $form->get_data()) {
                        $prnew->id = $prid;
             			$prnew->timemodified = time();
                        $prnew->modifierid = $USER->id;
                        $prnew->week = str_replace(',', ".", $prnew->week);
                        //print_object($prnew);
        				$DB->update_record('bsu_plan_practice', $prnew);
                        update_practice_charge($yid, $practice, $prnew, $action); 
                        // $redirlink .= "&term=100";
                        redirect($redirlink, get_string('changessaved'), 0);
                    }    
        		}	
        		
        
        		echo '<table align="center" width="50%"><tr><td>';
        		$form->display();
        		echo '</td></tr></table>';
                $sql = "SELECT FROM_UNIXTIME(d.timemodified, '%d.%m.%Y %h:%i') as timemodified, d.modifierid, concat (u.lastname, ' ', u.firstname) as fullname
                        FROM mdl_bsu_plan_practice d
                        inner join mdl_user u on u.id=d.modifierid
                        where d.id=$prid";
                if ($whomodifierd = $DB->get_record_sql($sql))  {
                    notify("Запись редактировалась $whomodifierd->timemodified. Пользователь: $whomodifierd->fullname", 'notifysuccess');
                }        

                
    	   break;
           
           
    	   case 'delete':
                $redirlink .= "&eid=$eid"; // &term=100
                /*if ($DB->record_exists_select('bsu_plan_groups', "planid = $plan->id"))   {
                    // echo $OUTPUT->notification('Нельзя удалить план, на который подписаны группы. Необходимо сначала отписать группы.');
                    notice('Нельзя удалить план, на который подписаны группы. Необходимо сначала отписать группы.', "curriculums.php?fid=$fid");
                } else {
                */    
                $sql = "SELECT p.id, concat (pt.name, ' ', p.name) as nname FROM mdl_bsu_plan_practice p
                         inner join mdl_bsu_ref_practice_type pt on pt.id = p.practicetypeid
                        where p.id=$prid ";
                if ($pr = $DB->get_record_sql($sql))    {
                    
                    $strdelete = 'Удалить практику "' . $pr->nname . '"? <strong>ВНИМАНИЕ!!! Будет удалена нагрузка за все учебные года.</strong>'; 
                    $link = "editpractice.php?action=del&yid=$yid&fid=$fid&pid=$pid&term=100&eid=13&prid=$prid&tab=$tab&confirm=1&sesskey=".sesskey();
                    echo $OUTPUT->confirm($strdelete, $link, $redirlink);
                }                                      
                   
    	   break;
           

    	   case 'deletespec':
                $redirlink .= "&eid=$eid"; // &term=101
                $sql = "SELECT p.id, p.name FROM mdl_bsu_plan_practice p
                         where p.id=$prid ";
                if ($pr = $DB->get_record_sql($sql))    {
                    $strdelete = 'Удалить спец. вида работу: "' . $pr->name . '"? <strong>ВНИМАНИЕ!!! Будет удалена нагрузка за все учебные года.</strong>'; 
                    $link = "editpractice.php?action=del&yid=$yid&fid=$fid&pid=$pid&term=101&eid=$eid&prid=$prid&tab=$tab&confirm=1&sesskey=".sesskey();
                    echo $OUTPUT->confirm($strdelete, $link, $redirlink);
                }                                      
                   
    	   break;
           
        }
     }   

    $link = "speccharge.php?fid=$fid";
    echo '<div align=center>'. "<a href=\"$link\">Вернуться на страницу просмотра практик</a>"; 
       
    echo $OUTPUT->footer();



function update_practice_charge($yid, $practice, $prnew, $action)
{
    global $DB;

    $allterms = array();
    $strgroups = get_plan_groups($practice->planid);
    if (!empty($strgroups)) {
        $agroups = explode ('<br>', $strgroups);
        $terms = get_terms_group($yid, $agroups);            
        foreach ($terms as $term)   {
            foreach ($term as $t) {
                $allterms[] = $t;
            }
        }
    }    
    
    // print_object($practice);
    // print_object($prnew);
    
    if ($edmaskpr  = $DB->get_record_select('bsu_edwork_mask', "yearid=$yid and practiceid= $practice->id"))  {
        // проверяем изменились ли семестры
        if ($practice->term != $prnew->term)    {
            if (in_array($prnew->term, $allterms))  {
                $DB->set_field_select('bsu_edwork_mask', 'term', $prnew->term, "yearid=$yid and practiceid = $practice->id"); 
                $DB->set_field_select('bsu_edwork', 'term', $prnew->term, "yearid=$yid and practiceid = $practice->id");
            } else {
                delete_pactice_charge($yid, $practice->id); 
            }    
        }    
        
        // проверяем изменлся ли вид учебной работы
        if ($practice->edworkkindid != $prnew->edworkkindid)    {
            $DB->set_field_select('bsu_edwork_mask', 'edworkkindid', $prnew->edworkkindid, "yearid=$yid and practiceid = $practice->id"); 
            $DB->set_field_select('bsu_edwork', 'edworkkindid', $prnew->edworkkindid, "yearid=$yid and practiceid = $practice->id");
            if ($prnew->edworkkindid == 13)    {
                create_edworks_for_practice_new($yid, $practice->planid, $practice->id, $agroups);
            } else {
                create_edworks_for_specvidrabot($yid, $practice->planid, $practice->id, $agroups);
            }     
        }
        
        // если это практика проверяем изменение кол-ва недель и типа практики
        if ($practice->edworkkindid == 13)    {
            if (($practice->week != $prnew->week) || ($practice->practicetypeid != $prnew->practicetypeid))    {
                create_edworks_for_practice_new($yid, $practice->planid, $practice->id, $agroups);
            }    
        }    
    } else {
        if (!empty($strgroups)) {
            if ($practice->edworkkindid == 13)    {
                create_edworks_for_practice_new($yid, $practice->planid, $practice->id, $agroups);
            } else {
                create_edworks_for_specvidrabot($yid, $practice->planid, $practice->id, $agroups);
            }     
        }    
    }    
    
}    

?>