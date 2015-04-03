<?php
	
    require_once("../../../config.php");
    require_once("../../../config_pegas.php");
    
    $yid = optional_param('yid', 0, PARAM_INT);                 // ed yearid
    $fid = optional_param('fid', 0, PARAM_INT);					// departmentcode
    $pid = optional_param('pid', 0, PARAM_INT);                 // plan id

    $strtitle0 = get_string('pluginname', 'block_bsu_plan');
    $strtitle = 'Назначение курса "Пегаса"';
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strtitle);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_cacheable(true);
    $PAGE->navbar->add($strtitle0, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add('Рабочие учебные планы ', new moodle_url("curriculumpegas.php", array('fid' => $fid)));
    $PAGE->navbar->add('Список дисциплин плана', new moodle_url("disciplinepegas.php", array('fid' => $fid, 'pid' => $pid, 'yid' => $yid)));
    $PAGE->navbar->add($strtitle);
    echo $OUTPUT->header();
    
    if($frm = data_submitted()) {
        // print_object($frm);
        $i = 1;
        foreach($frm as $name => $courseid) {
            
            if($name == 'yid' || $name == 'pid' || $name == 'fid' || empty($courseid)) {
                continue;
            }

            list ($id_kurs, $n, $disciplineid) = explode('_',$name);
                        
            if ($courseid < 0)  {
                $DB->delete_records('bsu_discipline_pegas_course', array('id'=>$id_kurs));
                continue;
            }    

            if (!$DBPEGAS->get_field_select('course', 'fullname', "id=$courseid"))   {
                notify ("Курс с id=$courseid не найден в СЭО ПЕГАС!");
                continue;
            }
            
            if( $id_kurs < 1000000 )    {
                //  Делаем Update
                if($DB->set_field('bsu_discipline_pegas_course','courseid',$courseid, array('id'=>$id_kurs))) {
                    // print '<b>' . $i . '</b>' . ' - Id записи - ' . $id_kurs . ', Id дисциплины - ' . $disciplineid . ', Новое значение COURSEID - ' . $courseid . '<br />';
                }
            }
            else {
                if ($courseid > 0)  {
                    //  Делаем Insert
                    $obj = new stdClass();
                    $obj->planid = $pid;
                    $obj->disciplineid = $disciplineid;
                    $obj->courseid = $courseid;
                    if($DB->insert_record('bsu_discipline_pegas_course',$obj)) {
                        //echo 'Запись Добавлена ' . $courseid;
                        // print '<b>' . $i . '</b>' . ' - Id записи - ' . $id_kurs . ', Id дисциплины - ' . $disciplineid . ', Добавлен курс - ' . $courseid . '<br />';
                    }
                    else {
                        notify ('ОШИБКА при добавлении записи в  bsu_discipline_pegas_course!');
                    }
                }    
            }
            $i++;
        }
        redirect("disciplinepegas.php?yid=$yid&fid=$fid&pid=$pid", 'Данные обновлены.', 1);
    }else    {
        redirect("disciplinepegas.php?yid=$yid&fid=$fid&pid=$pid", 'Ошибка при передаче данных', 1);
    }
    
        
    echo $OUTPUT->footer();
   
?>