<?php   // $Id: index.php,v 1.1.1 2012/10/07 12:48:33 shtifanov Exp $

    require_once("../../config.php");
    require_once('lib_menu_plan.php');
    // require_once("../../config_pegas.php");
    
    require_login();
    
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strtitle);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->navbar->add($strtitle);
    echo $OUTPUT->header();

    $index_items = get_items_menu_bsu_plan ($items);
    $table = new html_table();
    $table->align = array ('left', 'left');
    
    if (!empty($index_items))	{
    	foreach ($index_items as $index_item)	{
    	    $table->data[] = array("<strong>{$items[$index_item]}</strong>" ,
                                   get_string ('description_'.$index_item, 'block_bsu_plan'));
    	}
    }
    echo '<center>'.html_writer::table($table).'</center>';
    /*
    for ($i=0; $i<100; $i++)    {
        $guid = com_create_guid();
        echo $guid . strlen($guid) . '<br />';
    }     
    */


    // $speciality = $DBPEGAS->get_records('dean_speciality');
    // print_object($speciality);

    echo $OUTPUT->footer();
    
?>