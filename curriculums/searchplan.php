<?php

    require_once("../../../config.php");
    require_once("../lib_plan.php");

    require_login();
    
    $fid = optional_param('fid', 0, PARAM_INT);					// faculty id
    $tab  = optional_param('tab', 'v', PARAM_TEXT);
    $searchname = optional_param('searchname', null, PARAM_TEXT);
    $searchnamegroup = optional_param('searchnamegroup', null, PARAM_TEXT);
    $action  = optional_param('action', null, PARAM_TEXT);

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = 'Поиск плана';

    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strtitle3);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    //$PAGE->set_button('$nbsp;');
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3);
    echo $OUTPUT->header();

    $context = get_context_instance(CONTEXT_SYSTEM);

    $context = get_context_instance(CONTEXT_SYSTEM);
    $editcapability_system = has_capability('block/bsu_plan:importplan', $context);


	if (!$editcapability_system)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	} 
    /*
    if(!is_siteadmin($USER)){
        notice('Нет доступа!', 'index.php');
    }
    */

    switch($tab){
        case 'v':
            echo $OUTPUT->heading("Найти план : ", 2, 'headingblock header');
            echo $OUTPUT->box_start('generalbox sitetopic');
                echo "<center>".html_writer::start_tag('form', array( 'method' => 'post'));
                    echo html_writer::table(searchplan__formsearch($searchname, $searchnamegroup));
                    echo "<br> ";
                    echo "<input type='submit' value='Найти'>";
                echo html_writer::end_tag('form')."</center>";
            echo $OUTPUT->box_end();
            
            // print $searchname . '<br />' . $searchnamegroup;
            if($searchname <> null || $searchnamegroup <> null){
                echo "<center>".html_writer::table(searchplan_result($searchname, $searchnamegroup))."</center>";
            }

        break;
    }
    
    echo $OUTPUT->footer(); 


	
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///                                                                                                  ///
    ///     Функция выводящая форму поиска                                                               ///
    ///                                                                                                  ///
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    function searchplan__formsearch($searchname, $searchnamegroup)  {
    global $DB;
        
        $table = new html_table();
        //$table->width = '100%' ;
        $table->align = array('left', 'left');
        $table->size  = array('30%', '70%');
        $table->rowclasses = array();
        $table->data = array();
        
        //Имя поиска searchname
        $field1 = "<b>Найти план с ID:</b>";
        if (empty($searchname)) {
            $field2 = html_writer::empty_tag('input', array( 'name' => 'searchname', 'size' => '45'));
        } else {
            $field2 = html_writer::empty_tag('input', array( 'name' => 'searchname', 'size' => '45', 'value' => $searchname));
        }    
        $table->data[] =   array($field1, $field2);

        //Имя поиска searchname
        $field1 = "<b>Найти план по номеру группы:</b>";
        if (empty($searchnamegroup)) {
            $field2 = html_writer::empty_tag('input', array( 'name' => 'searchnamegroup', 'size' => '45'));
        } else {
            $field2 = html_writer::empty_tag('input', array( 'name' => 'searchnamegroup', 'size' => '45', 'value' => $searchnamegroup));
        }    
        $table->data[] =   array($field1, $field2);

        return $table;
    }
    
    ///////////////////////////////////////////////////////////////////////
    ///
    ///
    ///
    //////////////////////////////////////////////////////////////////////
    function searchplan_result($searchname, $searchnamegroup)   {
        global $DB, $OUTPUT;
        
        $table = new html_table();
        // $table->width = '100%' ;
        $table->head = array('id', 'Шифр', 'Название', 'Факультет', 'Форма обучения', 'Квалификация',  
                             'Группы', 'Статус', 'Удалённый', 'Действия');
        //$table->align = array('left', 'left');
        //$table->size  = array('30%', '70%');
        $table->rowclasses = array();
        $table->data = array();
        
        if ($searchnamegroup <> null)   {
            $textsql = "SELECT p.id, plantypeid, edyearid, specialityid, profileid, edformid, p.departmentcode, 
                               checksum, p.name, shortname, lastshifr, gosdate, sertificatedate, 
                               zetinyear, hourinzet, zetinweek, zettotal, kvalif, deleted, modifierid, vidplana,
                               kodlevel, uroven, termsinkurs, elementovinweek, gostype, ksr_iz, iga_zetinweek, 
                               iga_hoursinzet, period, timenorm, notusing
                        FROM mdl_bsu_plan p
                        inner join mdl_bsu_plan_groups pg on p.id=pg.planid
                        inner join mdl_bsu_ref_groups rg on rg.id=pg.groupid
                        WHERE rg.name ='{$searchnamegroup}'";
        } else {
            $textsql = "SELECT id, plantypeid, edyearid, specialityid, profileid, edformid, departmentcode, 
                               checksum, name, shortname, lastshifr, startyear, gosdate, sertificatedate, 
                               zetinyear, hourinzet, zetinweek, zettotal, kvalif, deleted, modifierid, vidplana,
                               kodlevel, uroven, termsinkurs, elementovinweek, gostype, ksr_iz, iga_zetinweek, 
                               iga_hoursinzet, period, timenorm, notusing
                        FROM mdl_bsu_plan
                        WHERE id = {$searchname}
                        ";
        } 
        
        if($search = $DB->get_record_sql($textsql) ){
            $planid = $search->id;
            $fid = $search->departmentcode; 
            $field2 = $search->lastshifr;
            $field3 = "<a href=\"disciplines.php?fid={$search->departmentcode}&pid=$search->id\">" . $search->name . '</a>';             

            if( $departmentcode = $DB->get_record_sql("SELECT Id, DepartmentCode, Name FROM {bsu_ref_department} WHERE DepartmentCode = {$search->departmentcode} ") ){
                $field4 = "<a href=\"curriculums.php?fid={$search->departmentcode}\">" . $departmentcode->name . '</a>';
            }else{
                $field4 = "-";
            }

            if( $edform = $DB->get_record_sql("SELECT idOtdelenie, Otdelenie FROM {bsu_tsotdelenie} WHERE idOtdelenie = {$search->edformid} ") ){
                $field5 = $edform->otdelenie;
            }else{
                $field5 = "-";
            }
            
            if( $kvalif = $DB->get_record_sql("SELECT idKvalif, Kvalif FROM {bsu_tskvalifspec} WHERE idKvalif = {$search->kvalif} ") ){
                $field6 = $kvalif->kvalif;
            }else{
                $field6 = "-";
            }
            
            if($search->notusing == 0){
                $field7 = "Действующий";
            }else{
                $field7 = "<strong><font color=red>В АРХИВЕ</font></strong>";
            }
            
            if ($search->deleted)   {
                $field3 = $search->name;
                $field8 = "<strong><font color=red>УДАЛЕН</font></strong>";
            } else {
                $field8 = 'нет';
            }    
            
            $groups = get_plan_groups_with_link($fid, $planid);
            
            if($search->notusing == 0 && $search->deleted == 0) {
                $action_href = "<a href='enrolgroups.php?pid=$planid&fid=$fid&action=edit'>
                            <img class='icon' title='".get_string('enrolgroups', 'block_bsu_plan')."' src='".$OUTPUT->pix_url('i/group')."'></a>";                                
            // if ($isplancreate)  {
            	$action_href .= "<a href='curriculums.php?id=$planid&fid=$fid&action=edit'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";
            	$action_href .= "<a href='curriculums.php?id=$planid&fid=$fid&action=delete'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
            // }     
                $title = get_string('cloneplan', 'block_bsu_plan');        
                $action_href .= "<br /><a href='../import/cloneplan.php?pid=$planid&fid=$fid'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/copyplan')."'></a>";
                $title = get_string('movegroup', 'block_bsu_plan');                            
                $action_href .= "<a href='../import/movegroup.php?pid=$planid&fid=$fid'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/btn_move')."'></a>";
                $title = get_string('updateshp', 'block_bsu_plan');                            
                $action_href .= "<a href='../import/updateshp.php?pid=$planid&fid=$fid'><img class='icon' title='".$title."' src='".$OUTPUT->pix_url('i/restore')."'></a>";
            } else {
                $action_href = '-';
            }    

            
            $table->data[] =  array($planid, $field2, $field3, $field4, $field5, $field6, $groups, $field7, $field8, $action_href);    
        }

        return $table;
    }
      
?>