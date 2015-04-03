<?php
    require_once("../../../config.php");
    require_once('lib.php');

    require_login();
    //$context = get_context_instance(CONTEXT_SYSTEM, 0);
    //if(!has_capability('block/bsu_reiting:editsynchronization', $context)){
    //    print_error( 'permission', 'bsu_reiting');
    //}
    
    $planid = optional_param('planid', 0, PARAM_INT);
    //$kid = optional_param('kid', 0, PARAM_INT);
    //$col = optional_param('col', 0, PARAM_INT);
    $tab = optional_param('tab', 'v', PARAM_TEXT);
    
    $action = optional_param('action', null, PARAM_TEXT);
    
    if($action == "ispravlenie" ){
        
        ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
        @set_time_limit(0);
        @ob_implicit_flush(true);
        @ob_end_flush(); 
        
        pathzaoch_action_ispravlenie();

    }    
    
    $PAGE->set_url('/blocks/bsu_reiting//index.php');
    $PAGE->set_context(null);
    $PAGE->set_heading('Исправление планов заочников');
    $PAGE->navbar->add('Навигация', $CFG->wwwroot.'/blocks/bsu_plan/index.php');
    $PAGE->navbar->add('Исправление планов заочников');

    echo $OUTPUT->header();
    
    if($planid <> 0){
        echo $planid;
        pathzaoch_action_pathzaoch_discipline($planid);    
    }
    
    /*
    $planids = array (1973, 646, 1964,1963, 679, 1968, 1967, 677, 1972, 1969, 652, 1966, 1965, 678, 696);
    foreach ($planids as $planid)   {
        echo '<hr>';
        echo $planid . '<br /><br />';
        pathzaoch_action_pathzaoch_discipline($planid);
    }
    */
    
  /*   
    switch($tab){
        case 'v':
            echo $OUTPUT->heading("Исправление планов заочников :", 2, 'headingblock header');
            echo $OUTPUT->box_start('generalbox sitetopic');
            
                ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
                @set_time_limit(0);
                @ob_implicit_flush(true);
                @ob_end_flush(); 
                
                echo html_writer::table(list_pathzaoch()); 
            echo $OUTPUT->box_end();
            
            echo $OUTPUT->heading("Исправить :", 2, 'headingblock header');
            echo $OUTPUT->box_start('generalbox sitetopic');
                echo pathzaoch_ispravlenie();
            echo $OUTPUT->box_end();
        break;
    }
   */     
    echo $OUTPUT->footer();
?>