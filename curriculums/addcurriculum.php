<?php

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

    require_login();

    $fid = required_param('fid', PARAM_INT);					// department code
    // $departmentcode  = optional_param('departmentcode', 0, PARAM_INT);
    $specialityid    = optional_param('specialityid', 0, PARAM_INT);
    $kvalif          = optional_param('kvalif', 0, PARAM_INT);
    $edformid        = optional_param('edformid', 0, PARAM_INT);
    $profileid       = optional_param('profileid', 0, PARAM_INT);
    $action          = optional_param('action', null, PARAM_TEXT);
    $yid             = optional_param('yid', 14, PARAM_INT);			// ed yearid
    

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = 'Создание нового плана';// get_string('disciplines', 'block_bsu_plan');

    $PAGE->set_title($strtitle3);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3);
    echo $OUTPUT->header();

    $scriptname = "addcurriculum.php";
    $strlistfaculties =  listbox_department($scriptname, $fid);                        
	if (!$strlistfaculties)   { 
		// print_error(get_string('permission', 'block_bsu_plan'), 'block_plan', '../index.php');
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

    if($action == 'add' && $frm = data_submitted()) {
        $frm->departmentcode = $fid;
        $ayid = $yid - 1; 
        $frm->startyear = '20'. $ayid; 
        $id=$DB->insert_record('bsu_plan', $frm);
        $data=new stdClass();
        $data->departmentcode=$frm->departmentcode;
        $data->planid=$id;
        $data->yearid=get_current_edyearid();
        $DB->insert_record('bsu_plan_department_year', $data);
        
        redirect( "curriculums.php?fid=$fid", "Новый план успешно создан.", 3);
    }

    
    //echo $OUTPUT->heading($strtitle3, 2, 'headingblock header');
    //echo $OUTPUT->box_start('generalbox sitetopic');
        

   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;
        
    if( $fid > 0 ) {
        echo html_writer::start_tag('tr');
        echo html_writer::start_tag('td', array( 'width' => '300' ,  'valign' => 'top', 'align' => 'right') );
        echo "<b>Форма обучения:</b>";
        echo html_writer::end_tag('td');
        echo html_writer::start_tag('td', array( 'valign' => 'top', 'align' => 'left' ) );
        
        $outmenuedform = array();                
        $outmenuedform[0] = 'Выберите форму обучения ...';
        $textsql = " SELECT idOtdelenie AS id, Otdelenie
                     FROM {bsu_tsotdelenie}
                    ";
        if($structures = $DB->get_records_sql($textsql)) { 
            foreach($structures as $structure) {
                $outmenuedform[$structure->id] = $structure->otdelenie;
            }
        }
        echo $OUTPUT->single_select( "$scriptname?fid={$fid}", 'edformid', $outmenuedform, $edformid, 0, null, 'selectedformid');
        
        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');
        
        if ($edformid > 0)  {
            echo html_writer::start_tag('tr');
            echo html_writer::start_tag('td', array( 'width' => '300' ,  'valign' => 'top', 'align' => 'right') );
            echo "<b>Квалификация:</b>";
            echo html_writer::end_tag('td');
            echo html_writer::start_tag('td', array( 'valign' => 'top', 'align' => 'left' ) );
            
            $outmenukvalif = array();                
            $outmenukvalif[0] = 'Выберите квалификацию ...';
            $textsql = " SELECT idKvalif AS id, Kvalif
                         FROM {bsu_tskvalifspec}
                         ORDER BY Kvalif 
                        ";
            if($structures = $DB->get_records_sql($textsql)) { 
                foreach($structures as $structure) {
                    $outmenukvalif[$structure->id] = $structure->kvalif;
                }
            }
            echo $OUTPUT->single_select( "$scriptname?fid={$fid}&edformid={$edformid}", 'kvalif', $outmenukvalif, $kvalif, 0, null, 'selectkvalif');
            
            echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
            
            if ($kvalif > 0)    {

                echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '300' ,  'valign' => 'top', 'align' => 'right') );
                echo "<b>Специальность:</b>";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top', 'align' => 'left' ) );
                
                $outmenuspecyal = array();                
                $outmenuspecyal[0] = get_string('selectspecyal', 'block_bsu_plan').'...';
                $textsql = " SELECT sp.idSpecyal AS id, sp.Specyal
                             FROM {bsu_tsspecyal} AS sp
                             INNER JOIN {bsu_ref_department} AS rdep
                             ON rdep.id = sp.idFakultet
                             WHERE rdep.DepartmentCode = {$fid}
                             ORDER BY sp.Specyal ";
                if($structures = $DB->get_records_sql($textsql)) { 
                    foreach($structures as $structure) {
                        $outmenuspecyal[$structure->id] = $structure->id . '. ' . $structure->specyal;
                    }
                }
                echo $OUTPUT->single_select( "$scriptname?fid={$fid}&edformid={$edformid}&kvalif={$kvalif}", 'specialityid', $outmenuspecyal, $specialityid, 0, null, 'selectspecialityid');
                
                echo html_writer::end_tag('td');
                echo html_writer::end_tag('tr');
            }
        }
    }
        

    if($fid > 0 && $specialityid > 0 && $kvalif > 0 && $edformid > 0){
            echo html_writer::start_tag('form', array( 'method' => 'post', 'enctype' => 'multipart/form-data'));
            echo html_writer::empty_tag('input', array( 'type'=> 'hidden', 'name' => 'action',         'value' => 'add' ));
            echo html_writer::empty_tag('input', array( 'type'=> 'hidden', 'name' => 'fid',            'value' => $fid));
            echo html_writer::empty_tag('input', array( 'type'=> 'hidden', 'name' => 'specialityid',   'value' => $specialityid ));
            echo html_writer::empty_tag('input', array( 'type'=> 'hidden', 'name' => 'kvalif',         'value' => $kvalif ));
            echo html_writer::empty_tag('input', array( 'type'=> 'hidden', 'name' => 'edformid',       'value' => $edformid ));
            echo html_writer::empty_tag('input', array( 'type'=> 'hidden', 'name' => 'profileid',      'value' => $profileid ));

            if( $objdep = $DB->get_record_sql("SELECT Id, DepartmentCode, DepartmentNumber, Name
                                               FROM {bsu_ref_department}
                                               WHERE DepartmentCode = {$fid}
                                               ") ){
            $facultyid = $objdep->id;
                echo html_writer::start_tag('tr');
                    echo html_writer::start_tag('td', array( 'width' => '300' ,  'valign' => 'top', 'align' => 'right') );
                        echo "<b>Профиль:</b>";
                    echo html_writer::end_tag('td');
                    echo html_writer::start_tag('td', array( 'valign' => 'top', 'align' => 'left' ) );
                    
                    $outmenu = array();      
                    
                    $outmenu = get_profile_array($facultyid, $edformid, $kvalif);
                    /*
                    echo $OUTPUT->single_select( "$scriptname?fid={$fid}&specialityid={$specialityid}&kvalif={$kvalif}&edformid={$edformid}",
                                                 'profileid', $outmenu, $profileid, 0, null, 'selectprofileid');
                    */
                    echo html_writer::select($outmenu, 'profileid', '', null);                             
                    
                    echo html_writer::end_tag('td');
                echo html_writer::end_tag('tr');    
            }

            /*
            echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '250' ,  'valign' => 'top', 'align' => 'right') );
                    echo "<b>Тип плана:</b>";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top', 'align' => 'left' ) );
                    $options = $DB->get_records_menu('bsu_ref_plantype', null , 'name', 'id, name');
                    echo html_writer::select($options, 'plantypeid');
                echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr'); 
            */
            echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '250' ,  'valign' => 'top', 'align' => 'right') );
                    echo "<b>Год начала обучения:</b>";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top', 'align' => 'left' ) );
                    $options = $DB->get_records_menu('bsu_ref_edyear', null , 'EdYear', 'Id, EdYear');
                    echo html_writer::select($options, 'edyearid', $yid);
                echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');  

            // формируем название плана по умолчанию
            $specyalname = explode('.', $outmenuspecyal[$specialityid]);
            $specyalname = trim ($specyalname[1]);
            $lastshifr = mb_substr($specyalname, 0, 6);
            $nname = mb_substr($specyalname, 6);
            $shortname = $lastshifr . '.' . substr ($outmenukvalif[$kvalif], 0, 2);
            $name = $shortname . ' ' . trim($nname) . ' (' . $outmenuedform[$edformid] . ' форма обучения)';
            $shortname .= ' (' . $outmenuedform[$edformid] . ')';

            echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '300', 'valign' => 'top', 'align' => 'right') );
                    echo "<b>Полное название плана:</b>";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top') );
                    echo html_writer::empty_tag('input', array( 'name' => 'name', 'size' => '100', 'value'=>$name));
                echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
            
            echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '300', 'valign' => 'top', 'align' => 'right') );
                    echo "<b>Короткое название плана:</b>";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top') );
                    echo html_writer::empty_tag('input', array( 'name' => 'shortname', 'size' => '62', 'value'=>$shortname));
                echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
            
            
            echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '300', 'valign' => 'top', 'align' => 'right') );
                $strlastshifr = get_string('lastshifr', 'block_bsu_plan');
                    echo "<b> $strlastshifr:</b>";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top') );
                    echo html_writer::empty_tag('input', array( 'name' => 'lastshifr', 'size' => '62', 'value' => $lastshifr));
                echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
            
            
            $obj = get_period_and_timenorm($kvalif, $edformid);
            
            echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '250' ,  'valign' => 'top', 'align' => 'right') );
                    echo "<b>Срок обучения:</b>";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top', 'align' => 'left' ) );
                    $options = array();
                    $options['2 года'] = '2 года'; // магистр очная
                    $options['4 года'] = '4 года'; // бакалавр очная
                    $options['5 лет'] = '5 лет'; // специалист очная
                    $options['5,5 лет'] = '5,5 лет';
                    $options['6 лет'] = '6 лет';
                    echo html_writer::select($options, 'period', $obj->period);
                echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');  
            
            echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '250' ,  'valign' => 'top', 'align' => 'right') );
                    echo "<b>Время обучения:</b>";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top', 'align' => 'left' ) );
                    $options = array();
                    $options[2] = 2; // магистр очная
                    $options['2.5'] = 2.5;
                    $options[4] = 4; // бакалавр очная
                    $options[5] = 5;
                    $options['5.5'] = 5.5;        
                    $options[6] = 6;
                    echo html_writer::select($options, 'timenorm', $obj->timenorm);
                echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
            
            $fields = array('hourinzet', 'zetinweek', 'zetinyear', 'zettotal');
            $values = array(36, 1.5, 60, 60*$obj->timenorm);
            foreach ($fields as $i => $field) {
                echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '300', 'valign' => 'top', 'align' => 'right') );
                $str = get_string($field, 'block_bsu_plan');
                echo "<b>$str:</b>";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top') );
                echo html_writer::empty_tag('input', array( 'name' => $field, 'size' => '7', 'value' => $values[$i]));
                echo html_writer::end_tag('td');
                echo html_writer::end_tag('tr');
            }      

            //???Поля которые не понятно использование : 
            //???  startyear, gosdate, sertificatedate, zetinyear, hourinzet, zetinweek, zettotal,  deleted, modifierid, 
            //???  vidplana, kodlevel, uroven, termsinkurs, elementovinweek, gostype, ksr_iz, iga_zetinweek, iga_hoursinzet
            
            echo html_writer::start_tag('tr');
                echo html_writer::start_tag('td', array( 'width' => '300', 'valign' => 'top' , 'align' => 'right') );
                    echo " ";
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td', array( 'valign' => 'top') );
                    echo html_writer::empty_tag('input', array( 'type'=> 'submit', 'value' => 'Создать новый план' ));
                echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('form');    
    } 
        
    echo html_writer::end_tag('table');    
        
    //echo $OUTPUT->box_end();        
    
    echo $OUTPUT->footer();


function get_period_and_timenorm($kvalif, $edformid)
{
    $periods = array();
    $periods['1_2'] = '5 лет'; // ??
    $periods['2_2'] = '4 года'; // бакалавр очная
    $periods['2_3'] = '4 года';
    $periods['2_4'] = '4 года';
    $periods['3_2'] = '5 лет'; // специалист очная
    $periods['3_3'] = '6 лет';
    $periods['3_4'] = '5,5 лет';
    $periods['4_2'] = '2 года'; // магистр очная
    $periods['4_3'] = '2 года';
    $periods['4_4'] = '2 года';

    $timenorms = array();
    $timenorms['1_2'] = 5; // ??
    $timenorms['2_2'] = 4; // бакалавр очная
    $timenorms['2_3'] = 5;
    $timenorms['2_4'] = 4.5;
    $timenorms['3_2'] = 5; // специалист очная
    $timenorms['3_3'] = 6;
    $timenorms['3_4'] = 5.5;
    $timenorms['4_2'] = 2; // магистр очная
    $timenorms['4_3'] = 2.5;
    $timenorms['4_4'] = 2.5;

    $index = $kvalif . '_' . $edformid;
    // echo $index . '<br>'; 
    $obj = new stdClass();
    $obj->period =  $periods[$index];
    $obj->timenorm  =  $timenorms[$index]; 
    
    return $obj;
}

?>