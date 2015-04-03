<?php  // $Id: curriculums.php,v 1.8 2012/10/20 12:29:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_ref/lib_ref.php");    
    
    $fid = optional_param('fid', -1, PARAM_INT);				// department code    
    $id = optional_param('id', -1, PARAM_INT);					// id plan
    $action = optional_param('action', '', PARAM_ACTION);		// action
    $confirm = optional_param('confirm', 0, PARAM_INT);			// confirm
    $eid = optional_param('edformid', 1, PARAM_INT);			// edformid 
    $yid = optional_param('yid', 0, PARAM_INT);					// yearid
    $kvalifid = optional_param('kvalifid', 1, PARAM_INT);       // Kvalifiction id
    $sid = optional_param('sid', 1, PARAM_INT);                 // Specyality id
    $sort = optional_param('sort', 'id', PARAM_TEXT);			// sort
    $tab = optional_param('tab', 'active', PARAM_ACTION);		// action
    
    require_login();

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strbuilding = get_string('area', 'block_bsu_area');

    $redirlink = "curriculums.php?id=$id&fid=$fid";
    
    if($confirm == 1 && confirm_sesskey()) {
        $rec = new stdClass();
    	$rec->id = $id;
        $rec->departmentcode = 0;
    	$rec->deleted = 1;
        $rec->modifierid = $USER->id;
    	$DB->update_record('bsu_plan', $rec);

        $curryid = get_current_edyearid();
        $sql = "yearid = $curryid AND planid = $id";
        if ($ids = $DB->get_records_select_menu('bsu_edwork_mask', $sql,  null, '', 'id as id1, id as id2')) {
            $strids = implode (',', $ids);
            $DB->delete_records_select('bsu_teachingload', "edworkmaskid in ($strids)");
            $DB->delete_records_select('bsu_edwork_mask', $sql);
            $DB->delete_records_select('bsu_edwork', $sql);
        }        
        // redirect($redirlink, get_string('plandeleted', 'block_bsu_plan'), 0); 
    }
    
    $PAGE->set_url('/blocks/area/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strtitle);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    if ($action <> '')  {
        $PAGE->navbar->add($strtitle2, new moodle_url("$CFG->BSU_PLAN/curriculums/curriculums.php", array()));
        $PAGE->navbar->add(get_string($action));
    }  else {
        $PAGE->navbar->add($strtitle2);
    }
    echo $OUTPUT->header();

    $faculty = $DB->get_record_select('bsu_ref_department', "departmentcode = $fid", null, 'id, departmentcode, name, shortname');
    $plan = $DB->get_record_select('bsu_plan', "id=$id");
    /*        
    $isplancreate = false;
    if ($DB->record_exists_select('bsu_ref_department_config', "departmentcode=$fid AND name='isplancreate' AND value='1'")) {
        $isplancreate = true;
    }    
    */
    switch($action) {
	   case 'edit':
            // id, plantypeid, edyearid, specialityid, edformid, departmentcode, checksum, name, shortname, lastshifr, startyear, gosdate, sertificatedate, zetinyear, hourinzet, zetinweek, zettotal
    		// $plan = $DB->get_record_sql("SELECT * FROM {$CFG->prefix}bsu_plan WHERE id=$id");
            $studidspecyal = 0;    
            $sql = "SELECT group_concat(groupid) as grpoupids FROM dean.mdl_bsu_plan_groups where planid=$id";              
            if ($grpoupids = $DB->get_field_sql($sql))  {
            
                $strsql = "SELECT g.id,  g.username, g.idstudent FROM mdl_bsu_group_members  g
                           where g.groupid in ($grpoupids)"; // g.deleted=0 and 
                // print $strsql;                                            
                $ids = array();
                $ids2 = array();
                if ($students = $DB->get_records_sql($strsql))    {
                    // print_object($students);
                    foreach($students as $student) {
                        $ids[] = $student->idstudent;
                        $ids2[] = $student->username;
                    } 
                    $strids =implode (',', $ids);
                    $strids2 =implode (',', $ids2);
                    
                    // print "CodePhysPerson in ($strids2) and idstudent in ($strids)";
                    // CodePhysPerson in ($strids2) and 
                    $sql = "SELECT idSpecyal, count(idSpecyal) as cnt FROM dean.mdl_bsu_students
                                                        where CodePhysPerson in ($strids2) and idstudent in ($strids) 
                                                        group by idSpecyal 
                                                        order by 2 desc";
                    // print $sql;                                                        
                    if ($idspecyals = $DB->get_records_sql($sql))   {
                        // print_object($idspecyals);
                        $idspecyal = reset($idspecyals);
                        $studidspecyal = $idspecyal->idspecyal; 
                    }
                }
            }
            if ($studidspecyal > 0) {
                $tsspecyual = $DB->get_field_select('bsu_tsspecyal', 'Specyal', "idSpecyal = $studidspecyal"); 
                $sql = "SELECT group_concat(name) as names FROM mdl_bsu_ref_groups where id in ($grpoupids)";
                $grpoupsname = $DB->get_field_sql($sql);
                $msg = "<b>ВНИМАНИЕ!!! Студенты групп(ы) $grpoupsname, подписанные на данный план, в базе данных отдела кадров<br />
                зачислены на специальность  '{$studidspecyal}. {$tsspecyual}'. Точно такая же специальность должна быть установлена у данного<br />
                рабочего учебного плана, иначе студенты групп(ы) будут отчислены из состава групп(ы). Отчисление также может произойти,<br />
                если у студентов и у плана не будут совпадать форма обучения и квалификация.<br />
                Если в названии специальности уже добавлено название профиля, то поле 'Профиль' можно оставить пустым.<br /></b>";   
                notify($msg);
            }    
                
    
    		$planform = new curriculum_form('curriculums.php');
    		$plan->action = $action;
    		$planform->set_data($plan);

            /*
            if ($planform->is_cancelled()) {
                    redirect($redirlink, '', 0);
            } else 
            */
            if ($plannew = $planform->get_data()) {
                
                /*
                if ($studidspecyal > 0 && $plannew->specialityid != $studidspecyal) {
                    $plannew->specialityid = $studidspecyal;
                } 
                */   
                    
    			if($plannew->id == 0) {
    			    // $agroupnew->id = (int)$agroupnew->name; 
    				$DB->insert_record('bsu_plan', $plannew);
    			} else {
    				$DB->update_record('bsu_plan', $plannew);
                }    
    			redirect($redirlink, get_string('changessaved'), 0);
    		}
    
    		echo '<table align="center" width="50%"><tr><td>';
    		$planform->display();
    		echo '</td></tr></table>';
	   break;
	   case 'delete':
            if ($DB->record_exists_select('bsu_plan_groups', "planid = $plan->id"))   {
                // echo $OUTPUT->notification('Нельзя удалить план, на который подписаны группы. Необходимо сначала отписать группы.');
                notice('Нельзя удалить план, на который подписаны группы. Необходимо сначала отписать группы.', "curriculums.php?fid=$fid");
            } else {
                $strdelete = get_string('deleteplan', 'block_bsu_plan', $plan->name); 
                echo $OUTPUT->confirm($strdelete, "curriculums.php?id=$id&fid=$fid&confirm=1&sesskey=".sesskey(), $redirlink);
            }    
	   break;
	   case 'archive':
            $curryid = get_current_edyearid();
            $nextyearid = $curryid + 1; 
            if ($DB->record_exists_select('bsu_edwork_mask', "yearid in ($curryid, $nextyearid) and planid=$plan->id"))   {
                // echo $OUTPUT->notification('Нельзя удалить план, на который подписаны группы. Необходимо сначала отписать группы.');
                notice('<b>ВНИМАНИЕ!!! Нельзя перевести план в архив, т.к. у него есть нагрузка в текущем учебном году.</b>', "curriculums.php?fid=$fid");
            } else {
                $DB->set_field_select('bsu_plan', 'notusing', 1, "id = $plan->id");
                redirect("curriculums.php?fid=$fid", 'Учебный план перемещен в архив.', 5);
                // $strdelete = get_string('deleteplan', 'block_bsu_plan', $plan->name); 
                // echo $OUTPUT->confirm($strdelete, "curriculums.php?id=$id&fid=$fid&confirm=1&sesskey=".sesskey(), $redirlink);
            }    
	   break;
       
	   default:
            echo $OUTPUT->heading($strtitle2, 2);
            $scriptname = "curriculums.php";
	        $strlistfaculties =  listbox_department($scriptname, $fid);                        
        	if (!$strlistfaculties)   { 
        		// print_error(get_string('permission', 'block_bsu_plan'), 'block_plan', '../index.php');
                notice(get_string('permission', 'block_bsu_plan'), '../index.php');
        	}	
           	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
            echo $strlistfaculties;
        
            if($fid > 0)    {
                
                $eid = listbox_edform($scriptname."?fid=$fid&yid=$yid", $eid, $fid);
                // print $fid;
                listbox_kvalification($scriptname."?fid=$fid&yid=$yid&edformid=$eid", $kvalifid);
                listbox_year($scriptname."?fid=$fid&edformid=$eid&kvalifid=$kvalifid", $yid, 'Год поступления:');
                listbox_specyality($scriptname."?fid=$fid&edformid=$eid&kvalifid=$kvalifid&yid=$yid", $fid, $sid);
                echo '</table>';
                
                $link = "?fid=$fid&yid=$yid&edformid=$eid&kvalifid=$kvalifid";
                print_tabs_curriculum($scriptname.$link, $tab);
                
                if ($tab == 'active')   {
                    $table = table_curriculum($fid, $eid, $yid, $sort, $kvalifid, $sid);
                } else {
                    $table = table_curriculum_archive($fid, $eid, $yid, $sort, $kvalifid, $sid);
                }    
                
                echo'<center>'.html_writer::table($table).'</center>';
                
                $options = array('fid' => $fid, "edformid"=>$eid, "kvalif"=>$kvalifid, "yid"=>$yid);
                echo '<center>'.$OUTPUT->single_button(new moodle_url("addcurriculum.php", $options), get_string('addcurriculum', 'block_bsu_plan'), 'get', $options).'</center>';    

                /*
                if ($isplancreate)  {
                    $options = array('fid' => $fid, "action"=>"edit");
                    echo '<center>'.$OUTPUT->single_button(new moodle_url("curriculums.php", $options), get_string('addcurriculum', 'block_bsu_plan'), 'get', $options).'</center>';    
                }*/
            } else {
                echo '</table>';
            }
    }            

    echo $OUTPUT->footer();
   


function table_curriculum($fid, $eid = 1, $yid = 1, $sort ='id', $kvalifid = 1, $sid = 1)
{        
    global $CFG, $DB, $OUTPUT, $scriptname;
    
    $context = get_context_instance(CONTEXT_FACULTY, $fid);
    $editcapability = has_capability('block/bsu_plan:editcurriculum', $context);
    
    $link = $scriptname."?fid=$fid&edformid=$eid&kvalifid=$kvalifid&";
    
    $table = new html_table();
    $table->align = array ('center', 'left', 'center', 'left', 'center','center', 'center', 'center', 'center', 'center');
    //$table->size = array ();
    $table->width = "80%"; // get_string('npp', 'block_bsu_plan'),
    $table->head = array (  '<a href="' . $link . "sort=id\">№ ID</a>", 
                            '<a href="' . $link . "sort=name\">" . get_string('name', 'block_bsu_plan') . '</a>',
                            '<a href="' . $link . "sort=name\">" . get_string('lastshifr', 'block_bsu_plan') . '</a>', 
                            get_string('speciality', 'block_bsu_plan'),
                            get_string('profile', 'block_bsu_plan'),
                            '<a href="' . $link . "sort=edformid\">" . get_string('edform', 'block_bsu_plan') . '</a>',
                            '<a href="' . $link . "sort=kvalif\">" . get_string('kvalif', 'block_bsu_plan') . '</a>',
                            get_string('allzetinone', 'block_bsu_plan'),
                            get_string('groups', 'block_bsu_plan'),
                            get_string('actions'));
    
//  INNER JOIN {bsu_plan_groups} g ON p.id=g.planid
//  INNER JOIN {bsu_ref_groups} rg ON rg.id=g.groupid
    $strselect = ''; 
    if ($eid > 1)   {
        $strselect .= " AND edformid = $eid ";
    }
    if ($kvalifid > 1)   {
        $strselect .= " AND p.kvalif = $kvalifid ";
    }
    
    if ($sid > 1)   {
        $strselect .= " AND p.specialityid = $sid ";
    }
    
    $stryear = '';
    if ($yid > 1)   {
        /*
        $year = $DB->get_record_select('bsu_ref_edyear', "id=$yid", null, 'edyear');
        $ay = explode ('/',  $year->edyear);
        //print_object($ay);
        $god = $ay[0];
            
        $sql = "SELECT id, planid FROM mdl_bsu_plan_groups
                where groupid in (SELECT id FROM mdl_bsu_ref_groups where name like '%{$god}__' AND departmentcode = $fid)";
        */        
        
        $god = $DB->get_field_select('bsu_ref_edyear', 'god', "id=$yid", null);
        $sql = "SELECT id, planid FROM mdl_bsu_plan_groups
                where groupid in (SELECT id FROM mdl_bsu_ref_groups where startyear=$god AND departmentcode = $fid)";
        if ($planids = $DB->get_records_sql_menu($sql)) {
            $strplanids = implode(',' , $planids);
        } else {
            $strplanids = 0;
        }   
        $strselect .= " AND p.id in ($strplanids)";                  
    }

    $sql = "SELECT p.id, p.name as pname, f.Otdelenie as fname, p.shortname as pshortname, 
            p.lastshifr, s.Specyal as sname, p.zetinyear, p.zetinweek, p.zettotal, k.kvalif, p.profileid 
            FROM {bsu_plan} p
            INNER JOIN {bsu_ref_edyear} e ON e.id=p.edyearid
            LEFT JOIN  {bsu_tsspecyal} s ON s.idSpecyal=p.specialityid
            LEFT JOIN  {bsu_tsotdelenie} f ON f.IdOtdelenie=p.edformid
            LEFT JOIN  {bsu_tskvalifspec} k ON k.IdKvalif=p.kvalif
            where p.departmentcode=$fid and p.deleted=0 and p.notusing=0 $strselect
            order by p.{$sort}";  
    
    // print $sql . '<br />';                                      
    if ($plans = $DB->get_records_sql($sql))    {

        $i = 1;
        foreach($plans as $plan) {
            if ($plan->profileid > 0)   {
                $profile = $DB->get_field_select('bsu_ref_profiles', 'name', "id = $plan->profileid");
            } else {
                $profile = 'не задан';
            }
            
        	$tabledata = array($plan->id.'.', "<a href=\"disciplines.php?fid=$fid&pid=$plan->id\">" . $plan->pname . '</a>',
                                $plan->lastshifr, 
                                $plan->sname,
                                $profile,
                                $plan->fname,
                                $plan->kvalif,
                                // $data->pshortname,
    							$plan->zetinweek . '; ' . $plan->zetinyear . '; ' . $plan->zettotal);
            $tabledata[] = get_plan_groups_with_link($fid, $plan->id);
            if ($editcapability)    {                                
                $action_href = "<a href='enrolgroups.php?pid=$plan->id&fid=$fid&action=edit'>
                                <img class='icon' title='".get_string('enrolgroups', 'block_bsu_plan')."' src='".$OUTPUT->pix_url('i/group')."'></a>";                                
                // if ($isplancreate)  {
                	$action_href .= "<a href='curriculums.php?id=$plan->id&fid=$fid&action=edit'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
                	$action_href .= "<a href='curriculums.php?id=$plan->id&fid=$fid&action=delete'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                // }     
                $title = get_string('cloneplan', 'block_bsu_plan');        
                $action_href .= "<br /><a href='../import/cloneplan.php?pid=$plan->id&fid=$fid'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/copyplan')."'></a>";
                $title = get_string('movegroup', 'block_bsu_plan');                            
                $action_href .= "<a href='../import/movegroup.php?pid=$plan->id&fid=$fid'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/btn_move')."'></a>";
                $title = get_string('updateshp', 'block_bsu_plan');                            
                $action_href .= "<a href='../import/updateshp.php?pid=$plan->id&fid=$fid'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/restore')."'></a>";
                $title = 'Перевести план в архив';                            
                $action_href .= "<a href='curriculums.php?id=$plan->id&fid=$fid&action=archive'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/archive')."'></a>";
                
                $tabledata[] = $action_href;
            } else {
                $tabledata[] = '';
            }    
            $table->data[] = $tabledata;                                     
        	$i++;
        }
        
    }    
    return $table;    
    
}



function table_curriculum_archive($fid, $eid = 1, $yid = 1, $sort ='id', $kvalifid = 1, $sid = 1)
{        
    global $CFG, $DB, $OUTPUT, $scriptname;
    
    $context = get_context_instance(CONTEXT_FACULTY, $fid);
    $editcapability = has_capability('block/bsu_plan:editcurriculum', $context);
    
    $link = $scriptname."?fid=$fid&edformid=$eid";
    
    $table = new html_table();
    $table->align = array ('center', 'left', 'center', 'left', 'center','center', 'center', 'center', 'center', 'center');
    //$table->size = array ();
    $table->width = "80%"; // get_string('npp', 'block_bsu_plan'),
    $table->head = array (  '<a href="' . $scriptname."?fid=$fid&edformid=$eid&sort=id\">№ ID</a>", 
                            '<a href="' . $scriptname."?fid=$fid&edformid=$eid&sort=name\">" . get_string('name', 'block_bsu_plan') . '</a>',
                            '<a href="' . $scriptname."?fid=$fid&edformid=$eid&sort=name\">" . get_string('lastshifr', 'block_bsu_plan') . '</a>', 
                            get_string('speciality', 'block_bsu_plan'),
                            get_string('profile', 'block_bsu_plan'),
                            '<a href="' . $scriptname."?fid=$fid&edformid=$eid&sort=edformid\">" . get_string('edform', 'block_bsu_plan') . '</a>',
                            '<a href="' . $scriptname."?fid=$fid&edformid=$eid&sort=kvalif\">" . get_string('kvalif', 'block_bsu_plan') . '</a>',
                            get_string('allzetinone', 'block_bsu_plan'),
                            get_string('groups', 'block_bsu_plan'),
                            get_string('actions'));
    
//  INNER JOIN {bsu_plan_groups} g ON p.id=g.planid
//  INNER JOIN {bsu_ref_groups} rg ON rg.id=g.groupid
    $strselect = ''; 
    if ($eid > 1)   {
        $strselect .= " AND edformid = $eid ";
    }
    if ($kvalifid > 1)   {
        $strselect .= " AND p.kvalif = $kvalifid ";
    }
    if ($sid > 1)   {
        $strselect .= " AND p.specialityid = $sid ";
    }
    
    $stryear = '';
    if ($yid > 1)   {
        /*
        $year = $DB->get_record_select('bsu_ref_edyear', "id=$yid", null, 'edyear');
        $ay = explode ('/',  $year->edyear);
        //print_object($ay);
        $god = $ay[0];
            
        $sql = "SELECT id, planid FROM mdl_bsu_plan_groups
                where groupid in (SELECT id FROM mdl_bsu_ref_groups where name like '%{$god}__' AND departmentcode = $fid)";
        */
        $god = $DB->get_field_select('bsu_ref_edyear', 'god', "id=$yid", null);
        $sql = "SELECT id, planid FROM mdl_bsu_plan_groups
                where groupid in (SELECT id FROM mdl_bsu_ref_groups where startyear=$god AND departmentcode = $fid)";
                        
        if ($planids = $DB->get_records_sql_menu($sql)) {
            $strplanids = implode(',' , $planids);
        } else {
            $strplanids = 0;
        }   
        $strselect .= " AND p.id in ($strplanids)";                  
    }

    $sql = "SELECT p.id, p.name as pname, f.Otdelenie as fname, p.shortname as pshortname, 
            p.lastshifr, s.Specyal as sname, p.zetinyear, p.zetinweek, p.zettotal, k.kvalif, p.profileid 
            FROM {bsu_plan} p
            INNER JOIN {bsu_ref_edyear} e ON e.id=p.edyearid
            LEFT JOIN  {bsu_tsspecyal} s ON s.idSpecyal=p.specialityid
            LEFT JOIN  {bsu_tsotdelenie} f ON f.IdOtdelenie=p.edformid
            LEFT JOIN  {bsu_tskvalifspec} k ON k.IdKvalif=p.kvalif
            where p.departmentcode=$fid and p.deleted=0 and p.notusing=1
            order by p.{$sort}";                                            //  $strselect
    if ($plans = $DB->get_records_sql($sql))    {

        $i = 1;
        foreach($plans as $plan) {
            if ($plan->profileid > 0)   {
                $profile = $DB->get_field_select('bsu_ref_profiles', 'name', "id = $plan->profileid");
            } else {
                $profile = 'не задан';
            }
                                               // "<a href=\"disciplines.php?fid=$fid&pid=$plan->id\">" . $plan->pname . '</a>' 
        	$tabledata = array($plan->id.'.', $plan->pname,
                                $plan->lastshifr, 
                                $plan->sname,
                                $profile,
                                $plan->fname,
                                $plan->kvalif,
                                // $data->pshortname,
    							$plan->zetinweek . '; ' . $plan->zetinyear . '; ' . $plan->zettotal);
            $tabledata[] = get_plan_groups($plan->id);
            /*
            if ($editcapability)    {                                
                $action_href = "<a href='enrolgroups.php?pid=$plan->id&fid=$fid&action=edit'>
                                <img class='icon' title='".get_string('enrolgroups', 'block_bsu_plan')."' src='".$OUTPUT->pix_url('i/group')."'></a>";                                
                // if ($isplancreate)  {
                	$action_href .= "<a href='curriculums.php?id=$plan->id&fid=$fid&action=edit'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
                	$action_href .= "<a href='curriculums.php?id=$plan->id&fid=$fid&action=delete'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                // }     
                $title = get_string('cloneplan', 'block_bsu_plan');        
                $action_href .= "<br /><a href='../import/cloneplan.php?pid=$plan->id&fid=$fid'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/copyplan')."'></a>";
                $title = get_string('movegroup', 'block_bsu_plan');                            
                $action_href .= "<a href='../import/movegroup.php?pid=$plan->id&fid=$fid'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/btn_move')."'></a>";
                $title = get_string('updateshp', 'block_bsu_plan');                            
                $action_href .= "<a href='../import/updateshp.php?pid=$plan->id&fid=$fid'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/restore')."'></a>";
                
                $tabledata[] = $action_href;
            } else {
                $tabledata[] = '';
            } 
            */   
            $tabledata[] = '';
            $table->data[] = $tabledata;                                     
        	$i++;
        }
        
    }    
    return $table;    
    
}


function print_tabs_curriculum($scriptname, $currtab)
{

    $toprow = array();
    $toprow[] = new tabobject('active', $scriptname."&tab=active", 'Действующие РУП');
    $toprow[] = new tabobject('archive' , $scriptname."&tab=archive", 'Архивные РУП');

    $tabs = array($toprow);
    print_tabs($tabs, $currtab, NULL, NULL);
}


    
?>