<?php
    require_once("../../../config.php");
    require_once("../lib_plan.php");

    require_login();

    $yid = optional_param('yid', 0, PARAM_INT);            //
    $fid = required_param('fid', PARAM_INT);
    // $yid = required_param('yid', PARAM_INT);
    $planid = optional_param('pid', 0, PARAM_INT);            // plan id
    $term = optional_param('term', 0, PARAM_INT);    
    $disc = optional_param('disc', '', PARAM_TEXT);
    $tab = optional_param('tab', 'plan', PARAM_TEXT);
    
    if ($yid == 0)  {
        $yid = get_current_edyearid();
    }
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->requires->css('/lib/jquery/development-bundle/themes/ui-lightness/jquery-ui.css');
    $PAGE->requires->js('/lib/jquery/js/jquery-1.9.1.js', true);
    $PAGE->requires->js('/lib/jquery/js/jquery-ui-1.10.3.custom.js', true);
    $PAGE->requires->js('/blocks/bsu_plan/curriculums/addiscipline.js', true);

    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = get_string('disciplines', 'block_bsu_plan');

    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strtitle3);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3, new moodle_url("disciplines.php", array('yid' => $yid, 'fid' => $fid, 'pid' => $planid, 'term' => $term, 'tab' => $tab )));
    $PAGE->navbar->add('Добавление дисциплины.');
    echo $OUTPUT->header();
  
    $scriptname = "addiscipline.php";        
    $strlistfaculties =  listbox_department($scriptname, $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

    if(!empty($disc)) {

        $disc = trim($disc);
        
        $current_disc = $DB->get_records("bsu_ref_disciplinename",array('name'=>$disc),"","id, name");
        
        $disciplinenameid = 0;
        foreach($current_disc as $cd){
            if($cd->name == $disc){
                $disciplinenameid=$cd->id; 
                break;//поиск совпаденя введенной дисциплины с имеющимися в БД
            }
        }
        
        if($disciplinenameid == 0) {
            //удачное добавление дисциплины
            $discipline = new stdClass();
            $discipline->name = $disc;
            $disciplinenameid = $DB->insert_record("bsu_ref_disciplinename", $discipline);
            
        }

        /*
        id, planid, disciplinenameid, subdepartmentid, cyclename, identificatorvidaplana, gos, sr, semestrexamen, semestrzachet, semestrkursovik, semestrdiffzach, competition, hoursinter, creditov, mustlearning, identificatordiscipline, razdel, semestrkp, newcyclename, newidentificatordiscipline, perezachetekz, perezachetzachet, perezachetfiddzach, perezachetkp, perezachetkr, perezachethour, competitioncode, hoursinzet, notusing, timemodified, modifierid
        */
        
        if ($disciplines = $DB->get_records_select('bsu_discipline', "planid = $planid AND disciplinenameid = $disciplinenameid"))   {
            $discipline = reset($disciplines);
            $disciplineid = $discipline->id; 
            if (count($disciplines) > 1) {
                notify ("ВНИМАНИЕ! В плане присутствует несколько дисциплин с одним и тем же именем: $disc !!!");  
            }
        }   else {
            $dnew = new stdClass();
            $dnew->planid = $planid;
            $dnew->disciplinenameid = $disciplinenameid;
            $dnew->subdepartmentid = 0;
            $dnew->cyclename = '';
            $dnew->timemodified = time();
            $dnew->modifierid = $USER->id;
            $disciplineid = $DB->insert_record('bsu_discipline', $dnew);
            // notify($disciplinenameid);
            
        } 
        $redirlink = "editd.php?yid=$yid&fid=$fid&pid=$planid&did=$disciplineid&term=$term&plantab=$tab";
        redirect($redirlink, get_string('changessaved'), 0);

        //*******************************************************************//      
    }
    
    echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
    echo $strlistfaculties;
    if($fid > 0){
        // listbox_plan($scriptname."?fid=$fid", $fid, $planid);
          echo '<tr align="left"> <td align=right>'.get_string('curriculum', 'block_bsu_plan').': </td><td align="left">';
          echo $DB->get_field_select('bsu_plan', 'name', "id=$planid");
          echo '</td></tr>';
        
         echo '</table>';
        if ($planid > 0)   {
            $input_disc = " <div align=center>
                                <form id='add_disc' method='post' action='addiscipline.php'>
                                      <label for='disc'>Название дисциплины: </label>
                                      <input type='hidden' name='yid' value=$yid>
                                      <input type='hidden' name='fid' value=$fid>
	                                  <input type='hidden' name='pid' value=$planid>
                                      <input type='hidden' name='term' value=$term>
                                      <input type='hidden' name='tab' value=$tab>
                                      <input type='text' id='disc' name='disc' size='100' /> &nbsp;&nbsp;&nbsp;
                                      <input type='submit' value='Продолжить -->' />
                                </form>
                            </div>  ";
            echo $input_disc;
        }
     } else {
         echo '</table>';
     }
           

    echo $OUTPUT->footer();
?>