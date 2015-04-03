<?php
require_once ("../../../config.php");

/*
$PAGE->set_url('/blocks/bsu_plan/index.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_title('График УП');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_focuscontrol('');
$PAGE->requires->js('/blocks/bsu_plan/curriculums/graphicup.js', true);
$PAGE->requires->js('/lib/jquery/js/jquery-1.9.1.js', true);
$PAGE->navbar->add('Домой в блок', new moodle_url("{$CFG->BSU_PLAN}/index.php"));
$PAGE->navbar->add('График учебного процесса');

echo $OUTPUT->header();*/

$planid = optional_param('planid', 0, PARAM_INT);
$grafik = optional_param('graphik', '2ТТТТТТТТТТТТТТТТТЭЭЭККТТТТТТТТТТТТТТТТТЭЭУУУУКККККК', PARAM_TEXT);
$id_kurs = optional_param('id_kurs', '1', PARAM_TEXT);
$numweekspring = optional_param('numweekspring', '17', PARAM_TEXT);
$numweekautumn = optional_param('numweekautumn', '18', PARAM_TEXT);


 

$status = 'none';

$bool_grafik = $DB->set_field('bsu_plan_grafikuchprocess', 'grafik', $grafik, array('planid' => $planid, 'numkurs' => $id_kurs));
$bool_numweekspring = $DB->set_field('bsu_plan_grafikuchprocess', 'numweekspring', $numweekspring, array('planid' => $planid, 'numkurs' => $id_kurs));
$bool_numweekautumn = $DB->set_field('bsu_plan_grafikuchprocess', 'numweekautumn', $numweekautumn, array('planid' => $planid, 'numkurs' => $id_kurs));

if ($bool_grafik || $bool_numweekspring || $bool_numweekautumn){
    
    //$obj_status = $DB->get_record('bsu_plan_grafikuchprocess', array('planid' =>$planid, 'numkurs' => $id_kurs), 'grafik, numweekspring, numweekautumn');
    //$status = $obj_status->grafik . '_' . $obj_status->numweekspring . '_' . $obj_status->numweekautumn;
    //print_object($status);
    $status1 = $grafik . '_' . $numweekspring . '_' . $numweekautumn;
    //echo $status1;
}
/*
* Упаковываем данные с помощью JSON
*/
print json_encode($status1);

//echo $OUTPUT->footer();
?>