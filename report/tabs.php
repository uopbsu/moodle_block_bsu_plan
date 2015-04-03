<?php

    $scriptname = "reports.php";
    $context = get_context_instance(CONTEXT_SYSTEM);
    $editcapability_system = has_capability('block/bsu_plan:importplan', $context);
    $editcapability_faculty = false;    

    $DEPS = array();
    $fid2 = $fid;
    $strlistfaculties =  listbox_department($scriptname, $fid2);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	} else {
	   $editcapability_faculty = true;
       $DEPS = $DB->get_records_sql_menu("SELECT c.id, c.instanceid FROM mdl_role_assignments ra inner join mdl_context c on c.id=ra.contextid 
                                        where c.contextlevel = 1021 and ra.userid={$USER->id} AND ra.roleid in (10,11,12) group by c.instanceid");
	}
    
 	
    $toprow = array();
    if ($editcapability_system || in_array(10305, $DEPS))    {
        $toprow[] = new tabobject('s', $scriptname."?level=s&fid=$fid", 'Сводный отчет по РУП');    
    }    
    
    if ($editcapability_system || $editcapability_faculty)    {
        $toprow[] = new tabobject('r', $scriptname."?level=r&fid=$fid", 'Отчет по РУП');
        $toprow[] = new tabobject('p', $scriptname."?level=p&fid=$fid", 'Отчет по РУП 2');
        $toprow[] = new tabobject('f', $scriptname."?level=f&fid=$fid", 'Отчет по факультету');
    }    
    if ($editcapability_system)    {
       	$toprow[] = new tabobject('u', $scriptname."?level=u&fid=$fid", 'Отчет по университету');
        $toprow[] = new tabobject('i', $scriptname."?level=i&fid=$fid", 'Отчет по ин.языку');
        $toprow[] = new tabobject('m', "report2.php?level=m&fid=$fid", 'Отчет для Москвы');
        $toprow[] = new tabobject('d', "reportdisname.php?level=d&fid=$fid", 'Отчет по дисциплинам');
    }    
	$tabs = array($toprow);
	print_tabs($tabs, $level, NULL, NULL);


?>