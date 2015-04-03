<?php // $Id: __odbc.php,v 1.11 2012/11/29 06:15:39 shtifanov Exp $


    define('CLI_SCRIPT', true);

    require(dirname(dirname(dirname(__FILE__))).'/config.php');
    require_once($CFG->libdir.'/clilib.php');      // cli only functions    
    require_once("lib_plan.php");
    
    set_time_limit(0);
    ini_set('memory_limit', -1);


/*
    require_once("../../config.php");
    require_once("lib_plan.php");

    require_login();
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->navbar->add('Move mstudents marks');
    echo $OUTPUT->header();

    ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
    @set_time_limit(0);
    @ob_implicit_flush(true);
    @ob_end_flush();
    @raise_memory_limit("256M");
    if (function_exists('apache_child_terminate')) {
        @apache_child_terminate();
    } 

   if (!is_siteadmin()) {
        notify('Access denied.');
        exit();
   } 
*/

    $yid = get_current_edyearid();
    // $yid++;
    
    // update_ds_plan($yid, 673);
    
    
    $facultys = $DB->get_records_select_menu('bsu_ref_department', "DepartmentCode >= 10100", null, 'DepartmentCode', 'DepartmentCode, Name');
    foreach ($facultys as $fid => $name)    { 
        notify ("Обновление факультета $fid. $name начато.", 'notifysuccess');
        update_ds_faculty($yid, $fid);
        notify ("Обновление факультета $fid. $name завершено.", 'notifysuccess');    
    }

    echo 'Выполнено!';   
    // $OUTPUT->footer();
    


function update_ds_faculty($yid, $fid)
{
    global $DB;
    
    
    $groups = $DB->get_records_select('bsu_ref_groups', "yearid=$yid and departmentcode=$fid", null, '', 'id, name'); // AND notusing=0
    foreach ($groups as $group)   {
        update_ds_group($yid, $group->id);    
        notify ("Группа {$group->id}. $group->name обработана.", 'notifysuccess');
    }
    
}

function update_ds_group($yid, $groupid)    
{
    global $DB;
              
    $planid = $DB->get_field_select('bsu_plan_groups', 'planid', "groupid=$groupid");  
                  
    $sql = "SELECT username FROM mdl_bsu_group_members d
            WHERE d.groupid=$groupid and deleted=0";
    // echo $sql . '<br />';                // and disciplinenameid=51
    if ($datas = $DB->get_records_sql($sql))  {
		foreach ($datas as $data) 	{
            $sql = "SELECT gm.groupid, pg.planid FROM mdl_bsu_group_members gm
                    inner join mdl_bsu_plan_groups pg using(groupid) 
                    where yearid=$yid and username='$data->username' and deleted=1 
                    order by timemodified desc";
            if ($oldgroups = $DB->get_records_sql($sql))    {
                foreach ($oldgroups as $oldgroup)   { 
                    if (copy_marks_to_new_plan($groupid, $planid, $oldgroup->groupid, $oldgroup->planid, $data->username))  {
                        notify ("copy_marks_to_new_plan $groupid  $planid  $oldgroup->groupid  $oldgroup->planid $data->username");
                        break;           
                    }
                }
            }    
		}  
    }
}


function copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,$edwork)
{
    global $DB;
    if (isset($disciplines_src[$dnameid][$sem][$edwork]))
    {
    $olddid=$disciplines_src[$dnameid][$sem][$edwork];
    $strsql = "SELECT *
           FROM {bsu_marksheet_students}
           WHERE departmentcode=$oldfid and groupid=$oldgid and pol=$pol and disciplineid=$olddid and cid=$cid and codephysperson=$codephysperson and edwork=$edwork";         
    if ($new = $DB->get_record_sql($strsql)) 
    {  
    $new->deleted=2;    
    $DB->update_record('bsu_marksheet_students', $new, false); 
    $data = new stdClass(); 
    $data->departmentcode=$newfid;
    $data->groupid=$newgid;
    $data->disciplineid=$newdid;
    $data->pol = $pol;
    $data->cid = $cid;
    $data->CodePhysPerson=$codephysperson;
    $data->edwork = $edwork;
    $data->mark = $new->mark;
    $data->date=$new->date;
    $data->secondmark = $new->secondmark; 
    $data->seconddate=$new->seconddate;
    $data->comissionmark = $new->comissionmark;
    $data->comissiondate=$new->comissiondate;   
    $data->aheadmark = $new->aheadmark;
    $data->aheaddate=$new->aheaddate;
    $data->remark = $new->remark;
    $data->redate=$new->redate;
    $data->deleted=0; 
    $data->timemodified=0;
    $data->modifierid=0;
    $strsql = "SELECT *
           FROM {bsu_marksheet_students}
           WHERE departmentcode=$newfid and groupid=$newgid and pol=$pol and disciplineid=$newdid and cid=$cid and codephysperson=$codephysperson and edwork=$edwork";  
    if ($found = $DB->get_record_sql($strsql)) 
    {
    if ($found->mark!=0)
    {
    $data->mark = $found->mark;
    $data->date=$found->date;      
    }
    if ($found->secondmark!=0)
    {
    $data->secondmark = $found->secondmark;
    $data->seconddate=$found->seconddate;      
    }
    if ($found->comissionmark!=0)
    {
    $data->comissionmark = $found->comissionmark;
    $data->comissiondate=$found->comissiondate;      
    } 
    if ($found->aheadmark!=0)
    {
    $data->aheadmark = $found->aheadmark;
    $data->aheaddate=$found->aheaddate;      
    }      
    if ($found->remark!=0)
    {
    $data->remark = $found->remark;
    $data->redate=$found->redate;      
    } 
    $data->id=$found->id;
    $DB->update_record('bsu_marksheet_students', $data, false); 
    }
    else
    $DB->insert_record('bsu_marksheet_students', $data);    
    }         
    }    
}

 function  copy_marks_to_new_plan($newgid, $newplanid, $oldgid, $oldplanid, $codephysperson)
 {
    global $DB;
    $success=0;
    $specid=array();
    $plsql = "SELECT specialityid,kvalif
    FROM {bsu_plan} 
    WHERE id=$newplanid";   
    if ($pl = $DB->get_record_sql($plsql)) 
    {
    $idspec=$pl->specialityid;  
    $sqrl="SELECT guid FROM mdl_bsu_tsspecyal WHERE idspecyal=$idspec";
    if ($spec = $DB->get_record_sql($sqrl)) 
    {
    $sqrl="SELECT idspecyal FROM mdl_bsu_tsspecyal WHERE guid='$spec->guid'";
    if ($specs = $DB->get_records_sql($sqrl)) 
    foreach($specs as $spec)
    $specid[]=$spec->idspecyal;
    }
    $idkvalif=$pl->kvalif;  
    }
    $plsql = "SELECT specialityid,kvalif
    FROM {bsu_plan} 
    WHERE id=$oldplanid"; 
    if ($pl = $DB->get_record_sql($plsql))
    {
    if (in_array($pl->specialityid,$specid)&&$idkvalif=$pl->kvalif) 
    {
    $sql_plan_data="SELECT distinct concat(d.id,s.numsemestr), d.id as did, d.semestrzachet,d.semestrexamen,d.semestrkursovik,d.semestrkp,d.semestrdiffzach, d.disciplinenameid, s.numsemestr
    FROM mdl_bsu_discipline d
    left join mdl_bsu_discipline_semestr s on d.id=s.disciplineid
    where d.planid=$oldplanid
    order by 1";    
    $disciplines_src=array();
    if ($plan_datas=$DB->get_records_sql($sql_plan_data))
    foreach($plan_datas as $plan_data)
    {
    if (isset($plan_data->numsemestr)&&!empty($plan_data->numsemestr))
    {
    $necesssem=strtoupper(strval(dechex($plan_data->numsemestr))); 
    if (strpos(strval($plan_data->semestrexamen),$necesssem)!==false)
    $disciplines_src[$plan_data->disciplinenameid][$plan_data->numsemestr][0]=$plan_data->did;
    if (strpos(strval($plan_data->semestrzachet),$necesssem)!==false)
    $disciplines_src[$plan_data->disciplinenameid][$plan_data->numsemestr][3]=$plan_data->did;
    if (strpos(strval($plan_data->semestrdiffzach),$necesssem)!==false)
    $disciplines_src[$plan_data->disciplinenameid][$plan_data->numsemestr][5]=$plan_data->did;     
    if (strpos(strval($plan_data->semestrkursovik),$necesssem)!==false||strpos(strval($plan_data->semestrkp),$necesssem)!==false)
    $disciplines_src[$plan_data->disciplinenameid][$plan_data->numsemestr][2]=$plan_data->did;     
    }
    else
    {
    for($sem=1;$sem<16;$sem++)  
    {
    $necesssem=strtoupper(strval(dechex($sem)));   
    if (strpos(strval($plan_data->semestrexamen),$necesssem)!==false)
    $disciplines_src[$plan_data->disciplinenameid][$sem][0]=$plan_data->did;
    if (strpos(strval($plan_data->semestrzachet),$necesssem)!==false)
    $disciplines_src[$plan_data->disciplinenameid][$sem][3]=$plan_data->did;
    if (strpos(strval($plan_data->semestrdiffzach),$necesssem)!==false)
    $disciplines_src[$plan_data->disciplinenameid][$sem][5]=$plan_data->did;     
    if (strpos(strval($plan_data->semestrkursovik),$necesssem)!==false||strpos(strval($plan_data->semestrkp),$necesssem)!==false)
    $disciplines_src[$plan_data->disciplinenameid][$sem][2]=$plan_data->did; 
    }
    }
    }    
    $sql_plan_data="SELECT p.id as did, concat(t.name,' ',p.name) as name,term FROM dean.mdl_bsu_plan_practice p
    inner join mdl_bsu_ref_practice_type t on t.id=p.practicetypeid
    where p.planid=$oldplanid and p.edworkkindid=13";
    if ($plan_datas=$DB->get_records_sql($sql_plan_data))
    foreach($plan_datas as $plan_data)
    {
    $disciplines_src[$plan_data->name][$plan_data->term][1]=$plan_data->did;     
    }
    
    $sql_plan_data="SELECT distinct concat(d.id,s.numsemestr), d.id as did, d.semestrzachet,d.semestrexamen,d.semestrkursovik, d.semestrkp, d.semestrdiffzach, d.disciplinenameid, s.numsemestr,
    round (numsemestr / 2,0) as cid, FLOOR(numsemestr/round (numsemestr / 2,0) ) as pol,
    (SUBSTRING(rg.name, 5, 2) +  round (numsemestr / 2,0)) as yid
    FROM mdl_bsu_discipline d
    left join mdl_bsu_discipline_semestr s on d.id=s.disciplineid
    inner join mdl_bsu_plan_groups pg using(planid)
    inner join mdl_bsu_ref_groups rg on rg.id=pg.groupid
    where d.planid=$newplanid and pg.groupid=$newgid
    order by 1";
    if ($plan_datas=$DB->get_records_sql($sql_plan_data))
    foreach($plan_datas as $plan_data)
    {   	
    $strsql = "SELECT id, departmentcode, olddepcode, oldname FROM {bsu_ref_groups}
    where id=$newgid";  
    if ($grf = $DB->get_record_sql($strsql))
    {
    if ($plan_data->yid<14) 
    {          
    $newgid=(int)$grf->oldname;
    $newfid=$grf->olddepcode;
    }
    else
    $newfid=$grf->departmentcode; 
    }
    $strsql = "SELECT id, departmentcode, olddepcode, oldname FROM {bsu_ref_groups}
    where id=$oldgid";  
    if ($grf = $DB->get_record_sql($strsql))
    {
    if ($plan_data->yid<14) 
    {          
    $oldgid=(int)$grf->oldname;
    $oldfid=$grf->olddepcode;
    }
    else
    $oldfid=$grf->departmentcode; 
    }
    $newdid=$plan_data->did;
    $dnameid=$plan_data->disciplinenameid;
    $sem='';
    if (isset($plan_data->numsemestr))
    $sem=$plan_data->numsemestr;
    if (!empty($sem))
    {
    $necesssem=strtoupper(strval(dechex($sem))); 
    $pol=$plan_data->pol;
    $cid=$plan_data->cid;
    if (strpos(strval($plan_data->semestrexamen),$necesssem)!==false)
    {
    copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,0);
    }
    if (strpos(strval($plan_data->semestrzachet),$necesssem)!==false)
    {
    copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,3);  
    }
    if (strpos(strval($plan_data->semestrdiffzach),$necesssem)!==false)
    {
    copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,5); 
    }  
    if (strpos(strval($plan_data->semestrkursovik),$necesssem)!==false||strpos(strval($plan_data->semestrkp),$necesssem)!==false)
    {
    copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,2);   
    }
    }
    else
    {
    for($sem=1;$sem<16;$sem++)  
    {
    $necesssem=strtoupper(strval(dechex($sem)));   
    if ($sem%2==0) $pol=2; else $pol=1;
    if ($pol==1) $n=1; else $n=0;   
    $cid=($sem+$n)/2;
    if (strpos(strval($plan_data->semestrexamen),$necesssem)!==false)
    {
    copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,0);    
    }
    if (strpos(strval($plan_data->semestrzachet),$necesssem)!==false)
    {
    copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,3);      
    }
    if (strpos(strval($plan_data->semestrdiffzach),$necesssem)!==false)
    {
    copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,5);      
    }  
    if (strpos(strval($plan_data->semestrkursovik),$necesssem)!==false||strpos(strval($plan_data->semestrkp),$necesssem)!==false)
    {
    copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,2);      
    }
    }         
    }
    }
    $sql_plan_data="SELECT p.id as did, concat(t.name,' ',p.name) as name,term,
    round (term / 2,0) as cid, FLOOR(term/round (term / 2,0) ) as pol,
    (SUBSTRING(rg.name, 5, 2) +  round (term / 2,0)) as yid 
    FROM dean.mdl_bsu_plan_practice p
    inner join mdl_bsu_ref_practice_type t on t.id=p.practicetypeid
    inner join mdl_bsu_plan_groups pg using(planid)
    inner join mdl_bsu_ref_groups rg on rg.id=pg.groupid
    where p.planid=$newplanid and p.edworkkindid=13 and pg.groupid=$newgid";
    if ($plan_datas=$DB->get_records_sql($sql_plan_data))
    foreach($plan_datas as $plan_data)
    {
    $strsql = "SELECT id, departmentcode, olddepcode, oldname FROM {bsu_ref_groups}
    where id=$newgid";  
    if ($grf = $DB->get_record_sql($strsql))
    {
    if ($plan_data->yid<14) 
    {          
    $newgid=(int)$grf->oldname;
    $newfid=$grf->olddepcode;
    }
    else
    $newfid=$grf->departmentcode; 
    }
    $strsql = "SELECT id, departmentcode, olddepcode, oldname FROM {bsu_ref_groups}
    where id=$oldgid";  
    if ($grf = $DB->get_record_sql($strsql))
    {
    if ($plan_data->yid<14) 
    {          
    $oldgid=(int)$grf->oldname;
    $oldfid=$grf->olddepcode;
    }
    else
    $oldfid=$grf->departmentcode; 
    }
    $newdid=$plan_data->did;
    $dnameid=$plan_data->name;
    $sem='';
    if (isset($plan_data->term))
    $sem=$plan_data->term; 
    if (!empty($sem))
    { 
    $pol=$plan_data->pol;
    $cid=$plan_data->cid;
    copy_discipline_marks($disciplines_src,$codephysperson,$oldfid,$newfid,$oldgid,$newgid,$newdid,$dnameid,$sem,$pol,$cid,1);    
    }
    }
    $success=1;
    }
    }
 return $success;
}
?>