<?php
	require_once("../../../config.php");
    
    $planid = optional_param('planid', 0, PARAM_INT);
    
    if($planid <> 0 ){
        pathzaoch_action_iziatiezachetov($planid);
    }
    
    /////////////////////////////////////////////////////////////////////////////////////
    ///                                                                               ///
    ///     изьятие зачётов                                                           ///  
    ///     $planid - (id таблицы bsu_plan) план                                      ///
    ///                                                                               ///
    /////////////////////////////////////////////////////////////////////////////////////
    function pathzaoch_action_iziatiezachetov($planid){
    global $DB;
        
        $textsql = "SELECT id, planid, disciplinenameid,  semestrexamen, semestrzachet, semestrkursovik
                    FROM {bsu_discipline}
                    WHERE (planid = {$planid})";
        if($zaochs = $DB->get_records_sql($textsql) ){
            foreach($zaochs AS $zaoch){
                if($disciplinesemestrs = $DB->get_records('bsu_discipline_semestr', array('disciplineid' => $zaoch->id ) ) ){
                    foreach($disciplinesemestrs AS $disciplinesemestr){
                        if( ($disciplinesemestr->lection > 0) AND ($disciplinesemestr->praktika == 0) AND ($disciplinesemestr->lab == 0) AND ($disciplinesemestr->srs == 0) ){
                            echo $zaoch->id."<br>";
                            $objectupd = new stdClass();
                            $objectupd->id = $zaoch->id;
                            $objectupd->semestrzachet = str_replace($disciplinesemestr->numsemestr, "", $zaoch->semestrzachet);
                            $objectupd->semestrexamen = str_replace($disciplinesemestr->numsemestr, "", $zaoch->semestrexamen);
                            $DB->update_record('bsu_discipline', $objectupd);
                        }
                    }        
                }
            }
        }
        
        return 1;
    }
    
?>