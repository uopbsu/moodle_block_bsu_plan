<?php // $Id: __odbc.php,v 1.11 2012/11/29 06:15:39 shtifanov Exp $
   
    require_once("../../config.php");
    require_once("lib_plan.php");

    /*
    $table = table_all_discipline_with_cycle();
    print_table_to_excel($table);
    exit();
    */

    require_login();
    $yid = 15;
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->navbar->add('Автоматическое распределение студентов по подгруппам на основе таблицы mdl_bsu_discipline_fizra_subgroup.');
    echo $OUTPUT->header();

   if (!is_siteadmin()) {
        notify('Access denied.');
        exit();
   } 

   
    ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
    @set_time_limit(0);
    @ob_implicit_flush(true);
    @ob_end_flush();
    @raise_memory_limit("256M");
    if (function_exists('apache_child_terminate')) {
        @apache_child_terminate();
    } 
    
    view_subgroup_in_fizra();

/*
    $DNID = 51;

    // получаем все планы и группы
    $sql = "SELECT id, planid, groupid  FROM dean.mdl_bsu_discipline_fizra_subgroup group by planid, groupid";
    if ($datas = $DB->get_records_sql($sql))    {
        foreach ($datas as $data)   {
            if ($disciplineid = $DB->get_field_select('bsu_discipline', 'id', "planid = $data->planid and disciplinenameid = $DNID")) {
                clear_sostav_subgroup($disciplineid, $data->groupid);
            
                print_object($data);
                $osn1 = array();
                $osn2 = array();
                get_subgroups_usernames($data->planid, $data->groupid, 'основная', $osn1, $osn2);
                print_object($osn1);
                print_object($osn2);
                print '<hr>';
                
                if (!empty($osn1))  {
                    distribution_students_in_subgroup_fizra($disciplineid, $data->groupid, '1 основная', $osn1);    
                }
    
                if (!empty($osn2))  {
                    distribution_students_in_subgroup_fizra($disciplineid, $data->groupid, '2 основная', $osn2);    
                }
                
                $smg1 = array();
                $smg2 = array();
                get_subgroups_usernames($data->planid, $data->groupid, 'СМГ', $smg1, $smg2);
                print_object($smg1);
                print_object($smg2);
                print '<hr>';
                print '<hr>';                        
    
                if (!empty($smg1))  {
                    distribution_students_in_subgroup_fizra($disciplineid, $data->groupid, '1 СМГ', $smg1);    
                }
    
                if (!empty($smg2))  {
                    distribution_students_in_subgroup_fizra($disciplineid, $data->groupid, '2 СМГ', $smg2);    
                }
            }           
        }
    }        
     */     
    
    $OUTPUT->footer();


function get_subgroups_usernames($planid, $groupid, $subgroupname, &$osn1, &$osn2)
{
    global $DB;
    
    // получаем студентов основной группы
    $sql = "SELECT @i := @i + 1 AS i, username FROM dean.mdl_bsu_discipline_fizra_subgroup, (select @i:=0) AS z
            where planid=$planid AND groupid=$groupid AND subgroupname = '$subgroupname'
            order by fio";
    print $sql . '<br />';            
    if ($osnovnie = $DB->get_records_sql_menu($sql))    {
        $cnt = count ($osnovnie);
        if ($cnt < 15)  {
            foreach ($osnovnie as $id => $osn) 
                $osn1[$id] = $osn;  
        } else { 
            for($i=1;  $i<=15;  $i++)  $osn1[$i] = $osnovnie[$i];
            for($i=16, $j=1; $i<=$cnt; $i++, $j++)  
                $osn2[$j] = $osnovnie[$i];  
        }
    } else {
        notify ('Not found!');
    }
}


function distribution_students_in_subgroup_fizra($disciplineid, $groupid, $subgroupname, $students)
{
    global $DB, $DNID, $yid;

    // находим дисциплину
   
        $subgr = NULL;
        // находим подгруппу с заданным именем
        $select = "yearid=$yid and groupid=$groupid and disciplineid=$disciplineid and name = '$subgroupname'";
        if ($subgrs = $DB->get_records_select('bsu_discipline_subgroup', $select))    {
            if (count($subgrs) == 1) {
                print $select;
                print_object($subgrs);
                $subgr = reset($subgrs);
            } else {
                notify ('Найдено более 1 подгруппы с именем ' . $subgroupname);
            }
        } else { 
            // создаем новую подгруппу
            $newsubgr = new stdClass();
            $newsubgr->yearid = $yid;
            $newsubgr->groupid = $groupid;
            $newsubgr->disciplineid = $disciplineid;
            $newsubgr->name = $subgroupname;
            $newsubgr->shortname = $subgroupname;
            $newsubgr->countstud = count($students);
            $newsubgr->cntstudbud = 0;
            $newsubgr->cntstudk = 0;
            $newsubgr->notusing = 0;
            $newsubgr->edworkkindid = 0;
            if ($newsubgrid = $DB->insert_record('bsu_discipline_subgroup', $newsubgr)) {
                $subgr = $DB->get_record_select('bsu_discipline_subgroup', "id = $newsubgrid");
                notify ("Подгруппа $subgroupname успешно создана!", 'notifysuccess'); 
            }
        }    
        
        if (isset($subgr->id))  {
            // удаляем студентов, находящихся в подгруппе
            $DB->delete_records_select('bsu_discipline_subgroup_members', "subgroupid = $subgr->id");
            // зачисляем студентов в подгруппу
            foreach ($students as $username) {
                if (!$DB->record_exists_select('bsu_discipline_subgroup_members', "username = '$username' AND subgroupid = $subgr->id"))	{
                    $rec = new stdClass();
                    $rec->username = $username;
                    $rec->subgroupid = $subgr->id;
                    $rec->deleted = 0;
                    if ($DB->insert_record('bsu_discipline_subgroup_members', $rec)) {
                        notify ("Студент успешно добавлен в подгруппу '$subgroupname'!", 'notifysuccess');
                        
                    } else {
                        notify ('Error insert into  bsu_discipline_subgroup_members.');
                    }
                }        
            }
            
            $DB->set_field_select('bsu_discipline_subgroup', 'countstud', count($students), "id = $subgr->id");
        } 

}     

function clear_sostav_subgroup($disciplineid, $groupid)
{
    global $DB, $yid;
    
    $select = "yearid=$yid and disciplineid=$disciplineid and groupid=$groupid";
    if ($subgrs = $DB->get_records_select('bsu_discipline_subgroup', $select))    {    
        foreach ($subgrs as $subgr) {
             $DB->delete_records_select('bsu_discipline_subgroup_members', "subgroupid = $subgr->id");
             $DB->set_field_select('bsu_discipline_subgroup', 'countstud', 0, "id = $subgr->id");
        }
    }
} 


function view_subgroup_in_fizra()
{
    global $DB;
    // drop temporary table if exists temp;
    $sql = "
create temporary table temp
SELECT  ds.id as subgroupid, p.id as planid, rg.id as groupid, d.id as did, d.disciplinenameid, rg.name as groupname, ds.name
FROM mdl_bsu_ref_groups rg
inner join mdl_bsu_plan_groups pg on rg.id=pg.groupid
inner join mdl_bsu_plan p on p.id=pg.planid
inner join mdl_bsu_discipline d on p.id=d.planid
inner join mdl_bsu_discipline_subgroup ds on d.id=ds.disciplineid and ds.groupid=rg.id
where rg.yearid=15 and rg.idedform=2 and (rg.startyear=2014 or rg.name like '____14__')  and d.disciplinenameid=51 and ds.yearid=15";
    $DB->Execute($sql);

    
    $sql = "select * from temp order by groupname";    
    if ($subgroups = $DB->get_records_sql($sql))    {
        $table = new html_table();
        foreach ($subgroups as $subgroup)   {
            $fid = $DB->get_field_select('bsu_plan', 'departmentcode', "id = $subgroup->planid");
            $subgroupname = "<a href=\"curriculums/subgroups.php?yid=15&plantab=kurs1&fid=$fid&pid=$subgroup->planid&term=1&did=$subgroup->did&gid=$subgroup->groupid\">{$subgroup->name}</a>";
            $table->data[] = array($subgroup->subgroupid, $subgroup->planid, $subgroup->groupid, $subgroup->did, $subgroup->groupname, $subgroupname);
        }
    }
    
    echo'<center>'.html_writer::table($table).'</center>';    
    
}   
?>