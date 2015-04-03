<?php
	
    require_once("../../../config.php");
    require_once("../lib_plan.php");
    //require_once("../../bsu_ref/lib_ref.php");
    
    $fid = optional_param('fid', -1, PARAM_INT);					// department code
    $kvalifid = optional_param('kvalifid', 1, PARAM_INT);     // Kvalifiction id
    $yid = optional_param('yid', 0, PARAM_INT);					// yearid 
    $sort = optional_param('sort', 'id', PARAM_TEXT);					// sort
    $eid = optional_param('edformid', 1, PARAM_INT);			// edformid 
    
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strbuilding = get_string('area', 'block_bsu_area');

    $PAGE->set_url('/blocks/area/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strtitle);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add("УМК в СЭО \"Пегас\"");

    
    echo $OUTPUT->header();
    
    echo $OUTPUT->heading('Рабочие учебные планы');
    
    $scriptname = "curriculumpegas.php";
    
    $strlistfaculties =  listbox_department($scriptname, $fid);                        
	if (!$strlistfaculties)   { 
		// print_error(get_string('permission', 'block_bsu_plan'), 'block_plan', '../index.php');
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;
    
    if($fid > 0)    {
        $eid = listbox_edform($scriptname."?fid=$fid&yid=$yid", $eid, $fid);
        listbox_kvalification($scriptname."?fid=$fid&yid=$yid&edformid=$eid", $kvalifid);
        listbox_year($scriptname."?fid=$fid&edformid=$eid&kvalifid=$kvalifid", $yid, 'Год поступления:');
        echo '</table>';
        
        $table = table_curriculum($fid, $eid, $yid, $sort, $kvalifid);
        echo'<center>'.html_writer::table($table).'</center>';
    }    

    echo $OUTPUT->footer();


function table_curriculum($fid, $eid = 1, $yid = 1, $sort ='id', $kvalifid = 1)
{        
    global $CFG, $DB, $OUTPUT, $scriptname;
    
    //$context = get_context_instance(CONTEXT_SYSTEM);
    //$editcapability = has_capability('block/bsu_plan:editcurriculum', $context);
    
    $link = $scriptname."?fid=$fid&edformid=$eid&kvalifid=$kvalifid&";
     
    $table = new html_table();
    $table->align = array ('center', 'left', 'center', 'left', 'center','center', 'center');
    //$table->size = array ();
    $table->width = "80%"; // get_string('npp', 'block_bsu_plan'),
    $table->head = array (  '<a href="' . $link . "sort=id\">№ ID</a>", 
                            '<a href="' . $link . "sort=name\">" . get_string('name', 'block_bsu_plan') . '</a>',
                            '<a href="' . $link . "sort=name\">" . get_string('lastshifr', 'block_bsu_plan') . '</a>', 
                            get_string('speciality', 'block_bsu_plan'),
                            get_string('profile', 'block_bsu_plan'),
                            '<a href="' . $link . "sort=edformid\">" . get_string('edform', 'block_bsu_plan') . '</a>',
                            '<a href="' . $link . "sort=kvalif\">" . get_string('kvalif', 'block_bsu_plan') . '</a>',
                            get_string('groups', 'block_bsu_plan'),
                            'Кол-во дисциплин',
                            'Кол-во курсов "Пегаса"');

    
//  INNER JOIN {bsu_plan_groups} g ON p.id=g.planid
//  INNER JOIN {bsu_ref_groups} rg ON rg.id=g.groupid
    $strselect = ''; 
    if ($eid > 1)   {
        $strselect .= " AND edformid = $eid ";
    }

    if ($kvalifid > 1)   {
        $strselect .= " AND p.kvalif = $kvalifid ";
    }
    
    $stryear = '';
    if ($yid > 1)   {
        $year = $DB->get_record_select('bsu_ref_edyear', "id=$yid", null, 'edyear');
        $ay = explode ('/',  $year->edyear);
        //print_object($ay);
        $god = $ay[0];
            
        $sql = "SELECT id, planid FROM mdl_bsu_plan_groups
                where groupid in (SELECT id FROM mdl_bsu_ref_groups where name like '%{$god}__')";
        if ($planids = $DB->get_records_sql_menu($sql)) {
            $strplanids = implode(',' , $planids);
        } else {
            $strplanids = 0;
        }   
        $strselect .= " AND p.id in ($strplanids)";                  
    }

    $sql = "SELECT p.id, p.name as pname, f.Otdelenie as fname, p.shortname as pshortname, 
            p.lastshifr, s.Specyal as sname, p.zetinyear, p.zetinweek, p.zettotal, k.kvalif, p.profileid 
            FROM {bsu_plan} p
            INNER JOIN {bsu_ref_edyear} e ON e.id=p.edyearid
            LEFT JOIN  {bsu_tsspecyal} s ON s.idSpecyal=p.specialityid
            LEFT JOIN  {bsu_tsotdelenie} f ON f.IdOtdelenie=p.edformid
            LEFT JOIN  {bsu_tskvalifspec} k ON k.IdKvalif=p.kvalif
            where p.departmentcode=$fid and p.deleted=0 and p.notusing=0 $strselect
            order by p.{$sort}";                            
    if ($plans = $DB->get_records_sql($sql))    {

        $i = 1;
        foreach($plans as $plan) {
            if ($plan->profileid > 0)   {
                $profile = $DB->get_field_select('bsu_ref_profiles', 'name', "id = $plan->profileid");
            } else {
                $profile = 'не задан';
            }
            $countdisicpline = $DB->count_records_select('bsu_discipline', "planid=$plan->id");
            $countcoursepegas = $DB->count_records_select('bsu_discipline_pegas_course', "planid=$plan->id");
        	//$tabledata = array($plan->id.'.', "<a href=\"deanPegasUmk.php?pid=$plan->id\" title=\"Посмотреть УМК для плана\">" . $plan->pname . '</a>',
            $strgroups = get_plan_groups_with_link($fid, $plan->id, 0, '/blocks/bsu_plan/deantopegas/disciplinepegas.php');
            $tabledata = array($plan->id.'.', "<a href=\"disciplinepegas.php?fid=$fid&pid=$plan->id\" title=\"Дисциплины плана\">" . $plan->pname . '</a>',
                                $plan->lastshifr, 
                                $plan->sname,
                                $profile,
                                $plan->fname,
                                $plan->kvalif,
                                $strgroups,
                                $countdisicpline,
                                $countcoursepegas);

            $table->data[] = $tabledata;
        	$i++;
        }
        
    }    
    return $table;    
    
}       
?>