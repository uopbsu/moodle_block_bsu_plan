<?php

require_once ("../../../config.php");
require_once("../import/lib_import.php");

$fid = optional_param('fid', -1, PARAM_INT);					// department code    
$eid = optional_param('edformid', 1, PARAM_INT);			// edformid 
$kvalifid = optional_param('kvalifid', 1, PARAM_INT);          // Kvalifiction id


$PAGE->set_url('/blocks/bsu_plan/index.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

$PAGE->set_title('Патчик для графика');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_focuscontrol('');
$PAGE->requires->js('/blocks/bsu_plan/curriculums/graphicup.js', true);
//$PAGE->requires->js('/lib/jquery/js/jquery-1.9.1.js', true);

$PAGE->navbar->add('Домой в блок', new moodle_url("{$CFG->BSU_PLAN}/index.php"));
$PAGE->navbar->add('График учебного процесса');

echo $OUTPUT->header();

    $grafik = array(); // Строковый массив графиков УП
    $grafik[] = 'ТТТТТТТТТТТТТТТТТТТЭЭЭЭЭЭЭССССТТТТТТТТТТТТТТТТТТТЭЭЭ';
    $grafik[] = 'ЭЭТТТТТТТТТТТТТТТТТЭЭЭЭЭЭЭССССТТТТТТТТТТТТТТТТТТТЭЭЭ';
    
    $arr_grafik=array();
    
    // Разбиение строкового массива на двумерный массив графиков УП
    foreach($grafik as $_grafik){
        $arr_grafik[] = str_split_php4_utf8($_grafik);
    }
    
    $count_week_schet = 0; // переменная для подсчета колличества "Т"
    $course_index = 1; // переменная для хранения номера курса
    
    foreach($arr_grafik as $_arr_grafik){
        
        for($i=0; $i<52; $i++){
            if($_arr_grafik[$i]!='Т'){
                if($i!=0 && $_arr_grafik[$i-1]=='Т'){ 
                    $count_week[$course_index][] = $count_week_schet;
                    $count_week_schet=0;
                }
                continue;
            }else{
                $count_week_schet++;
            }
        }
        $course_index++;
    }
    
    print_object($count_week); // массив для хранения количества недель по курсам
    

    
    
    //print_object($grafik);
    
    
    
echo $OUTPUT->footer();
?>