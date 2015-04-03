<?php   // by zagorodnyuk 5/09/2012

    
function adminis($userid=0) {
    global $USER, $CFG;

    $context = get_context_instance(CONTEXT_SYSTEM);
    return has_capability('moodle/site:config', $context, $USER->id, true);
}

// class bsu_function { }
function get_items_menu_bsu_plan (&$items)
{
    global $CFG, $DB, $USER, $OUTPUT;
    
	$index_items = array();
    if (is_siteadmin() || $USER->id==67543) {
        // $index_items = array(1, 2, 3, 4, 5); 'subgroups', 'streamgroups',
		$index_items = array('curriculums', 'disciplines',  
                             'importshp', 
                             // 'updateshp', 'cloneplan', 'movegroup', 
                             'speccharge', 'syncpractice',
                             'searchprepod', 'searchplan', 
                             'setsubdepartment', 
                             'reports', 
                             // 'listdiscnextyear', 'pathzaochindex',
                             'curriculumpegas',
                             'dis51user'     
                             ); // 'consolidationdisc', 'setka', 'syncdisckaf', 'syncp2t' 
    } else {
        $strsql = "SELECT a.id, roleid, contextid, contextlevel, instanceid, path, depth 
                   FROM {role_assignments} a RIGHT JOIN {context} ctx ON a.contextid=ctx.id
                   WHERE userid={$USER->id}";
        if ($ctxs = $DB->get_records_sql($strsql))	{
            foreach($ctxs as $ctx1) {
                $context = get_context_instance_by_id($ctx1->contextid);
                //print_object($context); 
                //print_object($USER->access);               
                switch ($ctx1->contextlevel)	{
                    case CONTEXT_SYSTEM:
                        if(has_capability('block/bsu_plan:viewcurriculum', $context) || 
                          (has_capability('block/bsu_plan:editcurriculum', $context))) {
                                $index_items[] = 'curriculums';
                                $index_items[] = 'disciplines';
                                // $index_items[] = 'subgroups';
                                // $index_items[] = 'streamgroups';
                                $index_items[] = 'reports';
                                $index_items[] = 'searchprepod';
                                // $index_items[] = 'listdiscnextyear';
                        }
                        if(has_capability('block/bsu_plan:importplan', $context))   {
                                $index_items[] = 'importshp';
                                // $index_items[] = 'cloneplan'; $index_items[] = 'movegroup'; $index_items[] = 'updateshp';
                                
                                $index_items[] = 'speccharge';
                                $index_items[] = 'setsubdepartment';
                                $index_items[] = 'searchplan';                   
                        }
                    break;
                    case CONTEXT_UNIVERSITY:
                    case CONTEXT_FACULTY:
                        if(has_capability('block/bsu_plan:viewcurriculum', $context) || 
                          (has_capability('block/bsu_plan:editcurriculum', $context))) {
                                $index_items[] = 'curriculums';
                                $index_items[] = 'disciplines';
                                // $index_items[] = 'subgroups';
                                // $index_items[] = 'streamgroups';
                                $index_items[] = 'searchprepod';
                                // $index_items[] = 'listdiscnextyear';
                        }
                        if(has_capability('block/bsu_plan:importplan', $context))   {
                            
                                $index_items[] = 'importshp';
                                // $index_items[] = 'cloneplan'; $index_items[] = 'movegroup';  $index_items[] = 'updateshp';
                                $index_items[] = 'speccharge';
                                $index_items[] = 'reports';
                                // $index_items[] = 'searchplan'; 
                        }
                    break;
                    case CONTEXT_SUBDEPARTMENT:
                        if(has_capability('block/bsu_plan:viewcurriculum', $context)) {
                                $index_items[] = 'reports';
                                $index_items[] = 'curriculums';
                                $index_items[] = 'disciplines';
                                $index_items[] = 'searchprepod';
                        }
                        if(has_capability('block/bsu_plan:editlinksubdepartment', $context))   {                        
                        		$index_items[] = 'setsubdepartment';
                        }
                    break;
                    
                }
            }
        }
    }
    
    

    $index_items = array_unique($index_items);
    if (!empty($index_items))   {
        $index_items[] = 'instruction_infobelgu2013_rup';
    }    
    
    // 106008 Ковалев
    // 72982 Бондарева
    // 52652 Дворяшина
    // 100315 Палышева
    // 66281 Бондаренко
    if ($USER->id == 106008 || $USER->id == 72982 || $USER->id == 52652 || $USER->id == 100315 || $USER->id == 66381)    { // || $USER->id == 61848
        $index_items[] = 'setsubdepartment';
    }
    
    // 6702 Шевченко 
    // 66340 Гончарук
    if( $USER->id == 6702 || $USER->id == 66340)    {
        $index_items[] = 'dis51user';
    }
    
    
	$folder = "/curriculums/";
	$name = 'curriculums';    
	$icons = '<img src="'.$OUTPUT->pix_url('i/wikiicon').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'disciplines';
	$icons = '<img src="'.$OUTPUT->pix_url('i/journal').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'subgroups';
	$icons = '<img src="'.$OUTPUT->pix_url('i/group').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php?fid=0">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'streamgroups';
	$icons = '<img src="'.$OUTPUT->pix_url('i/stream').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php?fid=0">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$folder = "/report/";
    $name = 'reports';
	$icons = '<img src="'.$OUTPUT->pix_url('i/table').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php?fid=0">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$folder = '/import/';
	$name = 'importshp';
	$icons = '<img src="'.$OUTPUT->pix_url('i/table_import').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'updateshp';
	$icons = '<img src="'.$OUTPUT->pix_url('i/restore').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';
    
	$name = 'syncdisckaf';
	$icons = '<img src="'.$OUTPUT->pix_url('i/enrolmentsuspended').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'syncp2t';
	$icons = '<img src="'.$OUTPUT->pix_url('i/enrolmentsuspended').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'searchprepod';
	$icons = '<img src="'.$OUTPUT->pix_url('i/search').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'cloneplan';
	$icons = '<img src="'.$OUTPUT->pix_url('i/copyplan').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'movegroup';
	$icons = '<img src="'.$OUTPUT->pix_url('i/btn_move').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';
    
	$name = 'consolidationdisc';
	$icons = '<img src="'.$OUTPUT->pix_url('i/configlock').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$folder = '/import/';
	$name = 'syncpractice';
	$icons = '<img src="'.$OUTPUT->pix_url('t/portfolioadd').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$folder = "/report/";
	$name = 'listdiscnextyear';    
	$icons = '<img src="'.$OUTPUT->pix_url('i/forward_next').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'report1';
	$icons = '<img src="'.$OUTPUT->pix_url('i/table').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.'reports.php?fid=0">'.$icons.get_string('reports', 'block_bsu_plan').'</a>';

	$folder = "/curriculums/";
	$name = 'setsubdepartment';    
	$icons = '<img src="'.$OUTPUT->pix_url('t/manual_item').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';

	$name = 'speccharge';    
	$icons = '<img src="'.$OUTPUT->pix_url('i/report').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.'Практики и доп. виды работ</a>';
    
    /*
	$folder = '/import/';
	$name = 'importusers';
	$icons = '<img src="'.$OUTPUT->pix_url('i/restore').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.get_string($name, 'block_bsu_plan').'</a>';
    */
    
   	$folder = '/pathzaoch/';
	$name = 'pathzaochindex';    
	$icons = '<img src="'.$OUTPUT->pix_url('i/patch').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.'index.php">'.$icons.'Патчик</a>';

	$folder = "/curriculums/";
	$name = 'searchplan';    
	$icons = '<img src="'.$OUTPUT->pix_url('i/search').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.'Поиск плана</a>';
    
    $name = 'instruction_infobelgu2013_rup';
	$icons = '<img src="'.$OUTPUT->pix_url('f/pdf').'" class="icon" alt="" />&nbsp;';
	// $items[0] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_diploma/'.$name.'.pdf">'.$icons.get_string($name,'block_bsu_diploma').'</a>';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/f.php/1/'.$name.'.pdf">'.$icons.'Руководство пользователя (в.1.0)</a>';

   	$folder = '/deantopegas/';
	$name = 'curriculumpegas';    
	$icons = '<img src="'.$OUTPUT->pix_url('i/pegas').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.'УМК в СЭО "Пегас"</a>';

	$folder = "/report/";
	$name = 'dis51user';    
	$icons = '<img src="'.$OUTPUT->pix_url('i/flagged').'" class="icon" alt="" />&nbsp;';
	$items[$name] = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan'.$folder.$name.'.php">'.$icons.'Подгруппы по Физ.культуре</a>';


	return $index_items;
}

?>