<?php   
/**
 * lib_plan.php - Библиотека функций для подсистемы рабочих учебных планов системы "ИнфоБелГУ:Учебный процесс"
 *
 * @copyright  2013 БелГУ
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot.'/lib/formslib.php');

define('CURRENT_YEARID', 15);
define('LAST_MONTH_IN_EDUYEAR', 7);  // номер последнего месяца в учебном году. Например, если номер = 7, то с 1 августа начнется следующий учебный год

/// ФУНКЦИИ ДЛЯ СОЗДАНИЯ НИЗПОДАЮЩИХ СПИСКОВ ////////////////////////////////////////////////////

/**
 * Данная функция создает низподающий список факультетов. Сначала идет новая структура, затем старая.
 * @param string  $scriptname строка адреса скрипта, использующего список
 * @param int     $fid код факультета из поля DepartmentCode
 * @return string HTML-код для отображения низподающего списка
 */
function listbox_department($scriptname, &$fid, $isshortname=false)
{
	global $DB, $CFG, $USER, $OUTPUT, $yid;

	$ret = false;
  
  	$listsfid = '';
    if (is_siteadmin()) {
        $listsfid = -1;
    } else {
    	$strsql = "SELECT a.id, roleid, contextid, contextlevel, instanceid, path, depth 
    			   FROM mdl_role_assignments a	RIGHT JOIN mdl_context ctx ON a.contextid=ctx.id
    			   WHERE userid={$USER->id}
                   order by roleid desc";
//print $strsql.'<hr>';
        $arrroleid  = get_array_roleid(); 
//print_object($arrroleid); 
    	if ($ctxs = $DB->get_records_sql($strsql))	{
    	 		// print_object($ctxs);

    			foreach($ctxs as $ctx1)	{
//print "cl=$ctx1->contextlevel CS=".CONTEXT_FACULTY.'<br>';
    				switch ($ctx1->contextlevel)	{
    					case CONTEXT_SYSTEM: if ($ctx1->roleid == $arrroleid['manager'] || 
                                                 $ctx1->roleid == $arrroleid['boss'] || 
                                                 $ctx1->roleid == $arrroleid['prorektorohr'] ||
                                                 $ctx1->roleid == $arrroleid['metodisto'] ||
                                                 $ctx1->roleid == $arrroleid['metodistz'] ||
                                                 $ctx1->roleid == $arrroleid['zavkaf']
                                                 )	{ 
    											$listsfid = -1;
    										 }
    										 break;	
    										 				 	
//zavkaf    							 	
        				case CONTEXT_UNIVERSITY:  
                                            if ($ctx1->roleid == $arrroleid['boss'] || 
                                                $ctx1->roleid == $arrroleid['prorektorohr']) {
        										$listsfid = -1;
    										}
//    										if($ctx1->roleid == )
    								 		 break;
    								 		 
    					case CONTEXT_FACULTY:
    										if($ctx1->roleid == $arrroleid['zavkaf']) {
    											$listsfid = -1;							
											} else {
                                                if($listsfid == -1) $listsfid = '';
												$listsfid .= $ctx1->instanceid . ',';
											}
    										 break;
 
                        case CONTEXT_SUBDEPARTMENT:                                            
    										if($ctx1->roleid == $arrroleid['zavkaf']) {
    											$listsfid = -1;							
											} else {
												$contexts = explode('/', $ctx1->path);
												//  print_object($contexts); 
												//  print_object($ctx1);    
												$d = $contexts[$ctx1->depth - 1];
												$ctxsubdep = $DB->get_record_select('context', "id = $d");
                                                if($listsfid == -1) $listsfid = '';
												$listsfid .= $ctxsubdep->instanceid . ',';
											}
    	 			}
//    	 			print_object($listsfid);
    	 			//if 	($listsfid == -1) break;
    			}
                
    	 }		 
	 }
     
	 if ($listsfid == '') 	{
	 	return false;
	 } else if 	($listsfid == -1) 	{
	   /*
	 	$strsql = "SELECT  id, departmentcode, name FROM  {bsu_ref_department}
                   WHERE DepartmentCode>10000
			       ORDER BY departmentcode";
	 	$strsql2 = "SELECT  id, departmentcode, name FROM  {bsu_ref_department}
                   WHERE DepartmentCode>0 and DepartmentCode<10000
			       ORDER BY departmentcode";
        */
        if (!isset($yid) || $yid <= 13)   { // empty($yid)
            // $yid = CURRENT_YEARID;
            $curryid = get_current_edyearid();
        } else {
            $curryid = $yid;
        }   
        if ($isshortname) {
            $strfield = "CONCAT (rd.shortname, ' ', rd.name) as name";
        } else {
            $strfield = 'rd.name';
        }
        $strsql = "SELECT rd.id, rd.departmentcode, $strfield FROM  mdl_bsu_ref_department rd
                inner join mdl_bsu_ref_department_year rdy using(departmentcode)
                where rdy.yearid=$curryid
                order by rd.departmentcode";
        $strsql2 = "SELECT rd.id, rd.departmentcode, $strfield  FROM mdl_bsu_ref_department_year rdy
                    inner join mdl_bsu_ref_department rd using(departmentcode)
                    where departmentcode not in (SELECT departmentcode FROM mdl_bsu_ref_department_year where yearid=$curryid)
                    order by rd.departmentcode";
        // print $strsql2 . '<br />';                     

	 } else {	
	    $select = '';
	    if (isset($yid))   {
	       $select =  "and s.yearid = $yid";
	    }   
	 	$listsfid .= '0';
        $sql = "SELECT distinct s.id, s.departmentcode FROM mdl_bsu_teacher_subdepartment ts
                inner join mdl_bsu_vw_ref_subdepartments s on s.id=ts.subdepartmentid
                where teacherid=$USER->id $select";
        // print $sql  . '<br />';           
        if ($ts = $DB->get_records_sql_menu($sql))  {
            // print_object($ts);
            // $strts = implode(',', $ts);
            if ($fid == 0)  {
                $ts1 = reset($ts);
                $fid = $ts1;   
            } 
            // echo $strts . '<br />';
            if (!empty($strts)) {
                $listsfid .= ',' . $strts;
            }
        }          
        // print $listsfid . '<br />';
	 	$strsql = "SELECT  id, departmentcode, name FROM  {bsu_ref_department}
		 			 WHERE departmentcode in ($listsfid)
   					 ORDER BY departmentcode";
        $strsql2 = '';                     
	 }
 
 
	$facultymenu = array();
    $facultymenu2 = array();
	// print $strsql . '<hr>';
    if ($afaculties = $DB->get_records_sql($strsql))	{
    	if (count($afaculties) > 1) {
            if ($fid == -1) $fid = 0;    	   
	   		$facultymenu[0] = get_string('selectafaculty', 'block_bsu_plan') . '...';
	  		foreach ($afaculties as $f) {
				$facultymenu[$f->departmentcode] = $f->name;
			}
            if ($strsql2 != '') {
                $afaculties2 = $DB->get_records_sql($strsql2);
    	  		foreach ($afaculties2 as $f) {
    				$facultymenu2[$f->departmentcode] = $f->name;
			    }
                $facultymenu = array(array('Новая структура'=>$facultymenu), array('Старая структура'=>$facultymenu2));
            }
		 	$ret =  '<tr><td align=right>'.get_string('faculty', 'block_bsu_plan').':</td><td>';
  			//$ret .=  popup_form($scriptname, $facultymenu, 'switchfaculty', $fid, '', '', '', true);
            $ret .= $OUTPUT->single_select($scriptname, 'fid', $facultymenu, $fid, null, 'switchfac');
  			$ret .= '</td></tr>';
		} else {
  			$f = current($afaculties);
  			// $schoolmenu[$school->id] = $school->name;
  			$fid = $f->departmentcode;
		 	$ret =  '<tr><td align=right>'.get_string('faculty', 'block_bsu_plan').':</td><td>';
  			$ret .=  "<b>$f->name</b>";
  			$ret .= '</td></tr>';
		} 
  	} else {
  		$ret = false;
  	}
	  
  return $ret;
}


/**
 * Данная функция создает низподающий список факультетов без учета роли и структуры.
 * @param string  $scriptname строка адреса скрипта, использующего список
 * @param int     $fid код факультета из поля DepartmentCode
 * @return string HTML-код для отображения низподающего списка
 */
function listbox_all_department($scriptname, $fidall, $yid = CURRENT_YEARID)
{
  global $CFG, $OUTPUT, $DB;

  $facultymenu = array();
  $facultymenu[0] = get_string('selectafaculty', 'block_bsu_plan').'...';
  $facultymenu[1] = 'ВСЕ ФАКУЛЬТЕТЫ';

  /*
  $strsql = "SELECT id, departmentcode, name, shortname FROM {bsu_ref_department}
  WHERE DepartmentCode>10000
  ORDER BY DepartmentCode";
  */
  /*
  if (is_siteadmin())   {
    $strfield = "CONCAT (rd.shortname, ' ', rd.name) as name";
  } else {
    $strfield = 'rd.name';
  }
  */
   
  $strfield = 'rd.name'; 
  $strsql = "SELECT rd.id, rd.departmentcode, $strfield FROM  dean.mdl_bsu_ref_department rd
  inner join dean.mdl_bsu_ref_department_year rdy using(departmentcode)
  where rdy.yearid=$yid
  order by rd.departmentcode";
    
  if($allfacs = $DB->get_records_sql($strsql))   {
		foreach ($allfacs as $faculty) 	{
            $facultymenu[$faculty->departmentcode] = $faculty->name;
		}
  }

  echo '<tr align="left"> <td align=right>'.get_string('facultys', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'fidall', $facultymenu, $fidall, null, 'switchfacall');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}


/**
 * Данная функция создает низподающий список рабочих учебных планов
 * @param string  $scriptname строка адреса скрипта, использующего список
 * @param int     $fid код факультета из поля DepartmentCode
 * @param int     $pid код выбранного плана 
 * @return string HTML-код для отображения низподающего списка
 */
function listbox_plan($scriptname, $fid, $pid)
{
  global $CFG, $OUTPUT, $DB;

  $planmenu = array();
  $planmenu[0] = get_string('selectcurriculum', 'block_bsu_plan').'...';

  $sql = "SELECT id, name FROM {bsu_plan} WHERE departmentcode=$fid ORDER BY id";
  // echo $sql;  
  if($allplans = $DB->get_records_sql($sql))   {
		foreach ($allplans as $plan) 	{
            $planmenu[$plan->id] = $plan->id . '. ' . $plan->name;
		}
  }

  echo '<tr align="left"> <td align=right>'.get_string('curriculum', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'pid', $planmenu, $pid, null, 'switchplan');
  echo '</td></tr>';
  return 1;
}



/**
 * Данная функция создает низподающий семестров
 * @param string  $scriptname строка адреса скрипта, использующего список
 * @param int     $fid код факультета из поля DepartmentCode
 * @param int     $planid код выбранного плана 
 * @param int     $term номер выбранного семестра
 * @return string HTML-код для отображения низподающего списка
 */
function listbox_term($scriptname, $fid, $planid, $term)
{
	GLOBAL $DB, $OUTPUT;

    $termmenu = array();
   
	$sql = "SELECT 1 as i, max(b.numsemestr) as maxsem FROM {bsu_discipline} a
            inner join {bsu_discipline_semestr} b on a.id=b.disciplineid
            where a.planid=$planid";
    if ($max = $DB->get_records_sql($sql))  {
         $maxsem = $max[1]->maxsem;
    } else {
         $maxsem = 0;
    }

    $toprow = array();
    for ($i=1; $i<=$maxsem; $i++)   {
        $termmenu [$i] = $i;
    }

  echo '<tr align="left"> <td align=right>'.get_string('term', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'term', $termmenu , $term, null, 'switchterm');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}




function listbox_discipline($scriptname, $fid, $planid, $term, $did)
{
  global $CFG, $OUTPUT, $DB;

  $planmenu = array();
  $planmenu[0] = get_string('selectdiscipline', 'block_bsu_plan').'...';

  $sql = "SELECT d.id as did, n.Name as nname, d.cyclename
            FROM {bsu_discipline_semestr} s
            INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
            INNER JOIN {bsu_plan} p ON p.id=d.planid
            INNER JOIN {bsu_ref_disciplinename} n ON n.Id=d.disciplinenameid
            WHERE p.id=$planid and s.numsemestr=$term
            ORDER BY n.Name";
   if ($datas = $DB->get_records_sql($sql))  {
		foreach ($datas as $plan) 	{
            $planmenu[$plan->did] = $plan->nname . ' (' . $plan->cyclename . ')';
		}
  }

  echo '<tr align="left"> <td align=right>'.get_string('discipline', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'did', $planmenu, $did, null, 'switchdisc');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}

function list_box_all_edwork($scriptname, $edworkid, $eid, $fid, $yid) 
{
    GLOBAL $DB, $OUTPUT, $ASPIRANTPLAN;

    $options = array (0 => "--не выбран--");
    $time0 = time();
    if ($eid > 1)   {
        $select = "AND edformid=$eid";
    } else {
        $select = ''; 
    }
    
    $textsqlplan = "SELECT id, name, lastshifr, specialityid, profileid, edformid, kvalif
                    FROM {bsu_plan}
                    WHERE (departmentcode = {$fid}) AND (deleted = 0) AND (notusing = 0) $select";

    if( $plans = $DB->get_records_sql($textsqlplan)) {
        foreach($plans AS $plan){
          $strgroups = get_plan_groups($plan->id);
          if ($strgroups != '' || in_array($plan->id, $ASPIRANTPLAN))   {
            
            $agroups = explode ('<br>', $strgroups);
            $terms = get_terms_group($yid, $agroups);
            $sql="SELECT pp.id, re.id as idd, re.name, pp.term FROM dean.mdl_bsu_ref_edworkkind re
            inner join mdl_bsu_plan_practice pp on pp.edworkkindid=re.id
            where pp.planid=$plan->id
            order by re.id";
            
            if ($specvidrabots = $DB->get_records_sql($sql)) {
               foreach ($specvidrabots as $specvidrabot)   {
                 $strgroups = '';
                 foreach ($agroups as $group)    {
                   if (in_array($specvidrabot->term, $terms[$group] ))   {
                      $strgroups .= $group . '<br>';
                   }
                 }   
                 if (!empty($strgroups)|| in_array($plan->id, $ASPIRANTPLAN)) {
                    $options[$specvidrabot->idd]=$specvidrabot->name;
                 }
               }   
            }  
          } 
        }
    }
    
    echo '<tr align="left"> <td align=right>' . "Вид работы:"  . '</td><td align="left">';
    echo $OUTPUT->single_select($scriptname, 'edworkid', $options, $edworkid, null, 'switchedwork');
    echo '</td></tr>';

    return 1;
}

function listbox_all_discipline($scriptname, $planid, $did)
{
  global $CFG, $OUTPUT, $DB;

  $planmenu = array();
  $planmenu[0] = get_string('selectdiscipline', 'block_bsu_plan').'...';

  $sql = "SELECT d.id as did, d.disciplinenameid, n.Name as nname, d.cyclename 
            FROM mdl_bsu_discipline d
            LEFT JOIN mdl_bsu_ref_disciplinename n ON n.id=d.disciplinenameid
            where planid=$planid
            order by nname";
   if ($datas = $DB->get_records_sql($sql))  {
		foreach ($datas as $plan) 	{
            $planmenu[$plan->did] = $plan->nname . ' (' . $plan->cyclename . ')';
		}
  }

  echo '<tr align="left"> <td align=right>'.get_string('discipline', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'did', $planmenu, $did, null, 'switchdiscall');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}


// Display list groups as popup_form
function listbox_groups($scriptname, $fid, $gid, $eid=0, $yid=0)
{
    global $CFG, $OUTPUT, $DB;
    
    $groupmenu = array();
    $groupmenu[0] = get_string('selectagroup', 'block_bsu_plan') . ' ...';
    
    if ($eid == 0 || $eid == 1) {
        $otdelenie = get_list_otdelenie($fid);    
    } else {
        $otdelenie = $eid;
    }
    
    if ($yid == 0) {
        $selectyear = '';
    } else {
        $startyear = $DB->get_field_select('bsu_ref_edyear', 'god', "id=$yid");
        $selectyear = " AND startyear=$startyear ";
    }
        
    
    $strsql = "SELECT id, departmentcode, name FROM {$CFG->prefix}bsu_ref_groups
             where departmentcode = $fid AND idedform in ($otdelenie) $selectyear
             ORDER BY name DESC";
    // print $strsql . '<br />';
    if ($arr_group = $DB->get_records_sql ($strsql)) 	{
    	foreach ($arr_group as $gr) {
    		$groupmenu[$gr->id] = $gr->name;
    	}
    }
    
    echo '<tr><td align=right>'.get_string('group').':</td><td align="left">';
    echo $OUTPUT->single_select($scriptname, 'gid', $groupmenu, $gid, null, 'switchgroup');
    // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
    echo '</td></tr>';
    return 1;
}


// Display list groups as popup_form
function listbox_groups_plan($scriptname, $fid, $planid, &$gid)
{
  global $CFG, $OUTPUT, $DB;

  $groupmenu = array();
  $groupmenu[0] = get_string('selectagroup', 'block_bsu_plan').'...';

  $otdelenie = get_list_otdelenie($fid);
  if (empty($otdelenie))    {
        $otdelenie .= '0';
  }      
   
  $sql = "SELECT g.name as gname, p.groupid, p.id, g.countstud
          FROM {bsu_ref_groups} g
          INNER JOIN {bsu_plan_groups} p ON p.groupid=g.id
          where p.planid = $planid AND g.idedform in ($otdelenie) 
          order by gname";
    
  // echo $sql;     
  $ggid = 0; 
  $countstuds = array(0);
  if($allgroups = $DB->get_records_sql($sql))   {
       foreach ($allgroups as $group) 	{
            $groupmenu[$group->groupid] = $group->gname . " ({$group->countstud} ст.)";
            $ggid = $group->groupid;
            $countstuds[$group->groupid] = $group->countstud;
       }     
  }
  
  if ($gid == 0 && count($allgroups) == 1) {
        $gid = $ggid;
  }

  echo '<tr align="left"> <td align="right">'.get_string('group', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'gid', $groupmenu, $gid, null);  // , 'switchgroup');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return $countstuds[$gid];
}

function listbox_subdepartment($scriptname, &$sid, $fid, $isshowall=true, $yid=14) 
{
    GLOBAL $DB, $OUTPUT, $USER;
    
    $allsid = array();
    $select = "roleid=24 and userid=$USER->id and contextid in (SELECT id FROM mdl_context 
               where contextlevel=1031 and instanceid in (SELECT id FROM mdl_bsu_vw_ref_subdepartments where yearid=$yid))";
    
    if ($zavkafs = $DB->get_records_select('role_assignments', $select, null, '', 'id, contextid')) {
        // print_object($zavkafs);
        foreach ($zavkafs as $zavkaf)   {
           $allsid[] =  $DB->get_field_select('context', 'instanceid', "id=$zavkaf->contextid");
        }
    }    
    
    if ($sid == 0)  {
        if ($zavkafs) {
            $zavkaf = reset($zavkafs);
            $sid = $DB->get_field_select('context', 'instanceid', "id=$zavkaf->contextid"); 
        } 
    }    
    
    $where = '';
    if($fid > 0) {
        // array(array('Odd'=>array(1=>'One', 3=>'Three)), array('Even'=>array(2=>'Two')))
        $sql = "SELECT id, name FROM {bsu_vw_ref_subdepartments} WHERE id>1 AND departmentcode=$fid and yearid=$yid ORDER BY name";
        // print $sql . '<br />';
        $menu1 = $DB->get_records_sql_menu($sql, null);
        if ($isshowall) {
            $sql = "SELECT id, name FROM {bsu_vw_ref_subdepartments} 
                    WHERE id>1 AND departmentcode<>$fid AND departmentcode>10000 and yearid=$yid
                    ORDER BY name";
            // print $sql . '<br />';                    
            $menu2 = $DB->get_records_sql_menu($sql, null);
            
        } else {
            $menu2 = array();
        }
        
    } else {
        $menu1 = $DB->get_records_sql_menu("SELECT id, name FROM {bsu_vw_ref_subdepartments} 
                                            WHERE departmentcode>10000 and yearid=$yid 
                                            ORDER BY name", null);
        $menu2 = array();
    }

    $menu11 = array();
    foreach ($menu1 as $subdepid => $s) {
        $menu11[$subdepid] = $s;
    }
    
    if (!empty($menu2)) {
        // ksort($menu1);
        // ksort($menu2);
        $menu = array(0=>get_string('selectsubdepartment', 'block_bsu_schedule') . '...', 1=>"_Не определена", array('Кафедры факультета'=>$menu11), array('Другие кафедры'=>$menu2));  
    } else {
        $menu11[0] = get_string('selectsubdepartment', 'block_bsu_schedule') . '...';
        $menu = $menu11;
        ksort($menu);
    }
    
    echo '<tr align="left"> <td align=right>'.get_string('subdepartment', 'block_bsu_schedule').': </td><td align="left">';
    echo $OUTPUT->single_select($scriptname, 'sid', $menu, $sid, null, 'switchsubdepartment');
    echo '</td></tr>';
    return 1;
}


function listbox_subdepartment_with_role($scriptname, $sid, $fid, $isshowall=true, $yid=14) 
{
    GLOBAL $DB, $OUTPUT, $USER;

  	$listsfid = '';
    if (is_siteadmin()) {
        $listsfid = -1;
    } else {
    	$strsql = "SELECT a.id, roleid, contextid, contextlevel, instanceid, path, depth 
    				FROM mdl_role_assignments a	RIGHT JOIN mdl_context ctx ON a.contextid=ctx.id
    			   WHERE userid={$USER->id}";
    	// echo $strsql . '<hr>';
        $arrroleid  = get_array_roleid(); 
        // print_object($arrroleid); 
    	if ($ctxs = $DB->get_records_sql($strsql))	{
    	 		// print_object($ctxs);
                //print CONTEXT_SYSTEM;                
    			foreach($ctxs as $ctx1)	{
    			    $context = get_context_instance_by_id($ctx1->contextid);
    				switch ($ctx1->contextlevel)	{
    					case CONTEXT_SYSTEM:
                            if(has_capability('block/bsu_plan:viewcurriculum', $context) || 
                              has_capability('block/bsu_plan:editcurriculum', $context)||
                              has_capability('block/bsu_charge:viewcharge', $context) || 
                              has_capability('block/bsu_charge:editcharge', $context)) 
                              {
                                $listsfid = -1;
//                                $ctx1->contextlevel                                                                
                              }
                        break;     							 	
        				case CONTEXT_UNIVERSITY:  
                            if(has_capability('block/bsu_plan:viewcurriculum', $context) || 
                              has_capability('block/bsu_plan:editcurriculum', $context)||
                              has_capability('block/bsu_charge:viewcharge', $context) || 
                              has_capability('block/bsu_charge:editcharge', $context)) {
                                $listsfid = -1;                               
//                                $listsfid .= $ctx1->instanceid . ',';                                                                
                              }
                        break;     							 	
    					case CONTEXT_FACULTY:
                            $listsfid = -1;
                        break;
 
                        case CONTEXT_SUBDEPARTMENT:
                         if(has_capability('block/bsu_plan:viewcurriculum', $context) || 
                              has_capability('block/bsu_plan:editcurriculum', $context)||
                              has_capability('block/bsu_charge:viewcharge', $context) || 
                              has_capability('block/bsu_charge:editcharge', $context)) {
                                $listsfid = -1;                               
//                                $listsfid .= $ctx1->instanceid . ',';                                                                
                              }
                            // $listsfid .= $ctx1->instanceid . ',';
                        break;                                            
    	 			}
    	 			
    	 			if 	($listsfid == -1) break;
    			}
    	 }		 
	 }

    $sids = 0;
    if($listsfid == '') {
        $sids = get_teacher_departmentid($USER->username);
        $listsfid = -1;
    }
// print "listsfid=$listsfid<br />";
    if ($listsfid == '') 	{
	 	return false;
	 } else if 	($listsfid == -1) 	{
        if($sids == 0) {
            $nsids = get_teacher_departmentid($USER->username, $yid);
            $select = '';
            if (!empty($nsids)) $select = " or (id in ($nsids))" ; //  and yearid=$yid 
            if($fid > 0) {
                // array(array('Odd'=>array(1=>'One', 3=>'Three)), array('Even'=>array(2=>'Two')))
                $menu1 = $DB->get_records_sql_menu("SELECT id, name FROM {bsu_vw_ref_subdepartments} WHERE id>1 AND departmentcode=$fid and yearid=$yid $select ORDER BY name", null);
                if ($isshowall) {
                    $menu2 = $DB->get_records_sql_menu("SELECT id, name FROM {bsu_vw_ref_subdepartments} WHERE id>1 AND departmentcode<>$fid and yearid=$yid $select ORDER BY name", null);
                } else {
                    $menu2 = array();
                }
                
            } else {
                $menu1 = $DB->get_records_sql_menu("SELECT id, name FROM {bsu_vw_ref_subdepartments} WHERE id>1 and yearid=$yid $select ORDER BY name", null);
                $menu2 = array();
            }
        } else {
            $s = explode(',', $sids);
            if(count($s) == 0 && $sid == 0) {
                $sid = $sids;
            }
            $menu1 = $DB->get_records_sql_menu("SELECT id, name FROM {bsu_vw_ref_subdepartments} WHERE id IN ($sids) and yearid=$yid ORDER BY name", null);            
        }
        
        $menu11 = array();
        $menu11[0] = get_string('selectsubdepartment', 'block_bsu_schedule') . '...';
        foreach ($menu1 as $subdepid => $s) {
            $menu11[$subdepid] = $s;
        }        
        
        if (!empty($menu2)) {
            $menu = array(array('Кафедры факультета'=>$menu11), array('Другие кафедры'=>$menu2));  
        } else {
            $menu = $menu11;
        }

        echo '<tr align="left"> <td align=right>'.get_string('subdepartment', 'block_bsu_schedule').': </td><td align="left">';
        echo $OUTPUT->single_select($scriptname, 'sid', $menu, $sid, null, 'switchsubdepartment');
        echo '</td></tr>';
    } else {
	 	$listsfid .= '0';
	 	$strsql = "SELECT  id, departmentcode, name FROM  {bsu_vw_ref_subdepartments}
		 			 WHERE id in ($listsfid) and yearid=$yid
   					 ORDER BY name";
        
        
    }    
    return 1;
}

function listbox_empty($scriptname, $vid)
{
  global $CFG, $OUTPUT, $DB;  
  echo '<tr align="left"> <td align=right>Выбрать пустые: </td><td align="left">';
  $emptymenu [0] = 'Нет';
  $emptymenu [1] = 'Да';
  echo $OUTPUT->single_select($scriptname, 'vid', $emptymenu, $vid, null, 'switchempty');
  echo '</td></tr>';

  return 1;
}

function listbox_percent($scriptname, $pid)
{
  global $CFG, $OUTPUT, $DB;  
  echo '<tr align="left"> <td align=right>Процент дохода более: </td><td align="left">';
  $percent=array();
  for($i=0;$i<101;$i++) {
    $percent[$i]=$i.'%';       
  }  
  echo $OUTPUT->single_select($scriptname, 'percentid', $percent, $pid, null, 'switchpercent');
  echo '</td></tr>';

  return 1;
}



function listbox_edform($scriptname, $edformid, $fid = 0)
{
    global $CFG, $OUTPUT, $DB;

    // $edformmenu[0] = get_string('selectafaculty', 'block_bsu_plan').'...';
    if ($fid != 0) {
        $context = get_context_instance(CONTEXT_FACULTY, $fid);
        $otdelenie = array();
        if($editcapability = has_capability('block/bsu_schedule:editscheduleo', $context)) $otdelenie[] = 2;
        if($editcapability = has_capability('block/bsu_schedule:editschedulez', $context)) $otdelenie[] = 3;
        if($editcapability = has_capability('block/bsu_schedule:editscheduleoz', $context)) $otdelenie[] = 4;
        if (count($otdelenie) == 3) $otdelenie[] = 1;
        if (empty($otdelenie))  {
            // if(has_capability('block/bsu_plan:viewcurriculum', $context)) 
            $otdelenie = array (1,2,3,4);
        }    
    } else {
        $otdelenie = array (1,2,3,4);
    }
    
    if (is_siteadmin()) {
        $otdelenie = array (1,2,3,4);
    }         
    
    $strotdelenie = implode(',', $otdelenie);
    $edformmenu = $DB->get_records_select_menu('bsu_tsotdelenie', "idotdelenie in ($strotdelenie)", null, 'idotdelenie', "idotdelenie, otdelenie");
    if (isset($edformmenu[1])) $edformmenu[1] = 'Все формы обучения';

    
    if (count($otdelenie) == 2 && $edformid == 1) $edformid = current($otdelenie);
    
    echo '<tr align="left"> <td align=right>'.get_string('edform', 'block_bsu_plan').': </td><td align="left">';
    if (count($otdelenie) == 1) {
        $edformid = current($otdelenie);
        echo '<b>' . $edformmenu[$edformid] . '<b>'; 
    } else {
        echo $OUTPUT->single_select($scriptname, 'edformid', $edformmenu, $edformid, null, 'switchedform');
    }    
    echo '</td></tr>';
    
    return $edformid;
}


function listbox_kvalification($scriptname, $kvalifid)
{
    global $CFG, $OUTPUT, $DB;

    $edformmenu = $DB->get_records_menu('bsu_tskvalifspec', null, 'idKvalif', "idKvalif, Kvalif");
    $edformmenu[1] = 'Все квалификации'; 


    echo '<tr align="left"> <td align=right>Квалификация: </td><td align="left">';
    echo $OUTPUT->single_select($scriptname, 'kvalifid', $edformmenu, $kvalifid, null, 'switchkvalif');
    echo '</td></tr>';
}


function listbox_all_univ_discipline($scriptname, $dnid, $yid, $title = 'Закрепить дисциплину:') 
{
    GLOBAL $DB, $OUTPUT;

    $sql = "SELECT id, name FROM mdl_bsu_ref_disciplinename
                            where id in (SELECT distinct disciplinenameid FROM mdl_bsu_discipline_subdepartment where yearid=$yid) 
                            order by name";
    $options = $DB->get_records_sql_menu($sql);
                    
    echo '<tr align="left"> <td align=right>' .  $title . '</td><td align="left">';
    echo $OUTPUT->single_select($scriptname, 'dnid', $options, $dnid, null, 'switchdisc');
    echo '</td></tr>';

    return 1;
}



function listbox_year($scriptname, $yid, $title = 'Учебный год:') 
{
    GLOBAL $DB, $OUTPUT;
    
    $menu = $DB->get_records_sql_menu("SELECT id, edyear FROM {bsu_ref_edyear}", null);
    
    $menu[1] = 'Все';
    ksort($menu);
    echo '<tr align="left"> <td align=right>' .  $title . '</td><td align="left">';
    echo $OUTPUT->single_select($scriptname, 'yid', $menu, $yid, null, 'switchedyear');
    echo '</td></tr>';

    return 1;
}


function listbox_practice($scriptname, $planid, $prid)
{
  global $CFG, $OUTPUT, $DB;

  $planmenu = array();
  $planmenu[0] = get_string('selectpractice', 'block_bsu_plan').'...';


    $sql = "SELECT p.id, concat (pt.name, ' ', p.name) as nname FROM mdl_bsu_plan_practice p
            inner join mdl_bsu_ref_practice_type pt on pt.id = p.practicetypeid
            where p.planid=$planid ";
    if (!$prmenu = $DB->get_records_sql_menu($sql))    {
        $prmenu = array();
    }
    
    $planmenu += $prmenu; 
    
  echo '<tr align="left"> <td align=right>'.get_string('practice', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'prid', $planmenu, $prid, null, 'switchpractice');
  echo '</td></tr>';
  return 1;
}


/**
 * This function is used to print roles column in user profile page.
 * @param int userid
 * @param object context
 * @return string
 */
function get_user_roles_in_context($userid, $context, $view=true){
    global $CFG, $USER, $DB;

    $rolestring = '';
    $SQL = 'select * from '.$CFG->prefix.'role_assignments ra, '.$CFG->prefix.'role r where ra.userid='.$userid.' and ra.contextid='.$context->id.' and ra.roleid = r.id';
    $rolenames = array();
    if ($roles =$DB->get_records_sql($SQL)) {
        foreach ($roles as $userrole) {
            $rolenames[$userrole->roleid] = $userrole->name;
            load_role_access_by_context($userrole->roleid, $context, $USER->access);
        }
    }
    return $rolenames;
}

function has_capability_bsu($capability, context $context) { // , array &$accessdata
    global $CFG, $USER;

    if (is_siteadmin($USER->id)) {
            return true;
    }
    // Build $paths as a list of current + all parent "paths" with order bottom-to-top
    $path = $context->path;
    $paths = array($path);
    while($path = rtrim($path, '0123456789')) {
        $path = rtrim($path, '/');
        if ($path === '') {
            break;
        }
        $paths[] = $path;
    }

    $roles = array();
    $roles = get_user_roles_in_context($USER->id, $context);
    // print_object($roles);
    if (!isset($USER->access)) {
        load_all_capabilities();
    }    
    $accessdata = $USER->access;
    // print_object($accessdata);

    $allowed = false;
    // exit();
    foreach ($roles as $roleid => $ignored) {
        foreach ($paths as $path) {
            // print_object($path);
            // echo "$path:$roleid [$capability]<br>";
            if (isset($accessdata['rdef']["{$path}:$roleid"][$capability])) {
                $perm = (int)$accessdata['rdef']["{$path}:$roleid"][$capability];
                if ($perm === CAP_ALLOW) {
                    return true;
                }
            }
        }
    }
 
    return $allowed;
}


function get_array_roleid()
{
    global $DB;
    // boss metodistz metodisto
    $roles =$DB->get_records_select('role', '');
    $arrroleid = array();
    foreach ($roles as $role)   {
        $arrroleid[$role->shortname] = $role->id;
    }
    return $arrroleid;
}


function get_kurs($groupname)
{
	$start->year = '20'.substr($groupname, -4, 2);
    print $start->year;
	if ($start->year < 2000)  return 0;
	$start->month = 9;
	$start->day = 1;
	$startime = make_timestamp($start->year, $start->month, $start->day);
	$nowtime = time();
	$days = floor(($nowtime -  $startime)/DAYSECS);
	if ($days < 0)  {
		$kurs = 0;
	} else {
		$kurs = floor($days/356) + 1;
	}
	return $kurs;
}


function get_current_edyearid($is_return_next_yearid=false)
{
    global $DB;

    $year = date("Y");
    $m = date("n");
    if(($m >= 1) && ($m <= LAST_MONTH_IN_EDUYEAR)) {
		$y = $year-1;
    } else {
		$y = $year;
    }
    
    if ($is_return_next_yearid) {
        $y++;
    }

	if ($year = $DB->get_record_select('bsu_ref_edyear', "God = $y", null, 'id'))	{
  		return $year->id;
	} else if ($year = $DB->get_record_sql("SELECT max(id) as id FROM mdl_bsu_ref_edyear"))   {
  		return $year->id;
	} else {
	   return 0;
	}
}

function is_date($strdate, $format='ru')
{
   if (empty($strdate)) return false;

   $rez = false;
   if ($format == 'ru')	{
	   if (!strpos($strdate, '.')) return false;
	   $strdate .= '..';
	   $day = $month = $year = 0;
	   list($day, $month, $year) = explode(".", $strdate);
	   $rez = checkdate($month, $day, $year);
   } else if ($format == 'en')	{
	   if (!strpos($strdate, '-')) return false;
	   $strdate .= '--';
	   $day = $month = $year = 0;
	   list($year, $month, $day) = explode("-", $strdate);
	   $rez = checkdate($month, $day, $year);
   }
   return $rez;
}

function convert_date($strdate, $from='ru', $to='en')
{
   if ($strdate == '0') return '0000-00-00'; 
   if ($from=='ru' && $to=='en')  {
   	   if (!is_date($strdate, 'ru')) {
   	   	  $newfdate = $strdate;
   	   } else {
		   list($day, $month, $year) = explode(".", $strdate);
		   $newfdate = $year.'-'.$month.'-'.$day;
	   	   if (!is_date($newfdate, 'en')) {
 	  	   	  $newfdate = $strdate;
  	 	   }
	   }
   } else if ($from=='en' && $to=='ru')  {
   	   if (!is_date($strdate, 'en')) {
   	   	  $newfdate = $strdate;
   	   } else {
		  list($year, $month, $day) = explode("-", $strdate);
	 	  $newfdate = $day.'.'.$month.'.'.$year;
	   	   if (!is_date($newfdate, 'ru')) {
 	  	   	  $newfdate = $strdate;
  	 	   }
	   }
   }
   return $newfdate;
}

function get_speciality_array($facultyid)
{
    global $DB;

    $options = array();
    $strsql = "SELECT idSpecyal as id, Specyal FROM {bsu_tsspecyal}
               where idFakultet=$facultyid";
    $specialitys = $DB->get_records_sql ($strsql);

    $options[0] = get_string('selectspecyal', 'block_bsu_plan').'...';
    foreach ($specialitys as $speciality)   {
         $options[$speciality->id] = $speciality->id . '. ' . $speciality->specyal;
    }
    return $options;
}        



function get_kvalif_array()
{
    global $DB;
    /*
    $edformso = $DB->get_records_select('bsu_ref_edform', "EdFormNumber in (2,3,4)", null, '', "Id, EdFormNumber, Name"); 
    $edforms = (array)$edformso;
    */
    // $edforms = array('2'=>'очная', '3'=>'заочная', '4'=>'очно-заочная');
    $edformso = $DB->get_records_select('bsu_tskvalifspec', '', null, '', "idkvalif, kvalif");
    $options = array();
    foreach ($edformso as $ed) {
        $options[$ed->idkvalif] = $ed->kvalif; 
    }
    // print_object($edforms); exit();
  
    return $options;       
}


function get_profile_array($facultyid, $edformid, $kvalif)
{
    global $DB;

    $options = array();
    $options[0] = get_string('selectprofile', 'block_bsu_plan').'...';
    
    $strsql = "SELECT idSpecyal as id, Specyal FROM {bsu_tsspecyal}
               where idFakultet=$facultyid";
    if ($specialitys = $DB->get_records_select_menu ('bsu_ref_profiles', "idfaculty=$facultyid AND edformid = $edformid AND kvalif = $kvalif AND name <> '-'", null, 'name', 'id, name'))   {
        $options  += $specialitys; 
    }

    return $options;
}


function get_disciplinenameids_array()
{
    global $DB;
    
    $datas = $DB->get_records_select('bsu_ref_disciplinename', '', null, 'name', "id, name");
    $options = array();
    foreach ($datas as $data) {
        $options[$data->id] = $data->name; 
    }
  
    return $options;       
}


class curriculum_form extends moodleform {

    function definition() {
        global $DB, $faculty, $plan;

        $mform =&$this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'fid');
        $mform->setType('fid', PARAM_INT);
        $mform->setDefault('fid', $faculty->departmentcode);

        $mform->addElement('hidden', 'departmentcode');
        $mform->setType('departmentcode', PARAM_INT);
        $mform->setDefault('departmentcode', $faculty->departmentcode);
        
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_RAW);

		$mform->addElement('header', '', get_string('editingcurriculum', 'block_bsu_plan'));
        
        $mform->addElement('static', '', get_string('faculty', 'block_bsu_plan'), $faculty->name);
        
        $mform->addElement('static', '', 'ID плана', $plan->id);

        $options = get_speciality_array($faculty->id);
        $mform->addElement('select', 'specialityid', get_string('speciality', 'block_bsu_plan'), $options);

        $options = get_profile_array($faculty->id, $plan->edformid, $plan->kvalif);
        $mform->addElement('select', 'profileid', get_string('profile', 'block_bsu_plan'), $options);

        $options = get_kvalif_array();
        $mform->addElement('select', 'kvalif', get_string('kvalif', 'block_bsu_plan'), $options);
        
        $options = get_edformids_array();
        $mform->addElement('select', 'edformid', get_string('bsu_ref_groups:idedform', 'block_bsu_ref'), $options);

        $mform->addElement('text', 'name', get_string('name'), 'size="100"');
        $mform->setType('name', PARAM_RAW);
        $mform->addRule('name', get_string('missingname'), 'required', null, 'client');        
        
        $mform->addElement('text', 'shortname', get_string('shortname'), 'size="50"');
        $mform->setType('shortname', PARAM_RAW);
        // $mform->addRule('shortname', get_string('missingname'), 'required', null, 'client');        

        $mform->addElement('text', 'lastshifr', get_string('lastshifr', 'block_bsu_plan'), 'size="20"');
        $mform->setType('lastshifr', PARAM_RAW);

        $periods = array();
        $periods['1 год'] = '1 год'; // интерны, ордитары
        $periods['2 года'] = '2 года'; // магистр очная
        $periods['2,5 года'] = '2,5 года'; // магистр очная
        $periods['3 года'] = '3 года'; // магистр очная
        $periods['3,5 года'] = '3,5 года'; // мед колледж
        $periods['4 года'] = '4 года'; // бакалавр очная
        $periods['4,5 года'] = '4,5 года'; // бакалавр очная
        $periods['5 лет'] = '5 лет'; // специалист очная
        $periods['5,5 лет'] = '5,5 лет';
        $periods['6 лет'] = '6 лет';
        $periods['7 лет'] = '7 лет';
        $periods['8 лет'] = '8 лет';
        $mform->addElement('select', 'period', get_string('period', 'block_bsu_plan'), $periods);

        $timenorms = array();
        $timenorms[1] = 1; // магистр очная
        $timenorms[2] = 2; // магистр очная
        $timenorms[3] = 3; // медколледж
        $timenorms['2.5'] = 2.5;
        $timenorms['3.5'] = 3.5; // медколледж
        $timenorms[4] = 4; // бакалавр очная
        $timenorms['4.5'] = 4.5; // бакалавр очная
        $timenorms[5] = 5;
        $timenorms['5.5'] = 5.5;        
        $timenorms[6] = 6;
        $timenorms[7] = 7;
        $timenorms[8] = 8;
        $mform->addElement('select', 'timenorm', get_string('timenorm', 'block_bsu_plan'), $timenorms);
        
        $fields = array('hourinzet', 'zetinweek', 'zetinyear', 'zettotal');
        foreach ($fields as $field) {
            $mform->addElement('text', $field, get_string($field, 'block_bsu_plan'), 'size="7"');
            $mform->setType($field, PARAM_RAW);
        }    


        $this->add_action_buttons(true, get_string('savechanges'));
    }


    function validation($datanew, $files) {
        global $CFG, $DB, $action;

        $datanew = (object)$datanew;
        $datanew->action = $action;

        $err = array();
        if(empty($datanew->name)) {
            $err['name'] = 'Введите, пожалуйста, название учебного плана';
        }    

        if(empty($datanew->shortname)) {
            $err['shortname'] = 'Введите, пожалуйста, короткое название учебного плана';
        }    
        
        if ($datanew->specialityid == 0)    {
            $err['specialityid'] = 'Не задана специальность';
        }
        

        if ($datanew->edformid == 1)    {
            $err['edformid'] = get_string('erroridedform', 'block_bsu_ref');
        }

        if ($datanew->kvalif == 1)    {
            $err['kvalif'] = 'Не задана квалификация';
        }
      
        if (count($err) == 0){
            return true;
        } else {
            return $err;
        }
    }
}


require_once("$CFG->libdir/excel/Worksheet.php");
require_once("$CFG->libdir/excel/Workbook.php");

/**
 * Print a nicely formatted table to EXCEL.
 *
 * @param array $table is an object with several properties.
 *     <ul<li>$table->head - An array of heading names.
 *     <li>$table->align - An array of column alignments
 *     <li>$table->size  - An array of column sizes
 *     <li>$table->wrap - An array of "nowrap"s or nothing
 *     <li>$table->data[] - An array of arrays containing the data.
 *     <li>$table->width  - A percentage of the page
 *     <li>$table->tablealign  - Align the whole table
 *     <li>$table->cellpadding  - Padding on each cell
 *     <li>$table->cellspacing  - Spacing between cells
 *****************  NEW (added by shtifanov) **********************
 *     <li>$table->downloadfilename - .XLS file name (new)
 *     <li>$table->worksheetname - Name of sheet in work book  (new)
 *     <li>$table->titles  - An array of titles names in firsts rows. (new)
 *     <li>$table->titlesrows  - Height of titles rows (new)
 *     <li>$table->columnwidth  - An array of columns width in Excel table (new)
 * </ul>
 * @param bool $return whether to return an output string or echo now
 * @return boolean or $string
 * @todo Finish documenting this function
 */

function print_table_to_excel($table, $lastcols = 0, $table2 = null)
{
    global $CFG;

    $order   = array("\r\n", "\n", "\r");
    $downloadfilename = $table->downloadfilename;

    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"{$downloadfilename}.xls\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    header("Pragma: public");

    $workbook = new Workbook("-");
    $txtl = new textlib();

	$strwin1251 =  $txtl->convert($table->worksheetname, 'utf-8', 'windows-1251');
    $myxls =&$workbook->add_worksheet($strwin1251);

	$numcolumn = count ($table->columnwidth) - $lastcols;
    $i=0;
    foreach ($table->columnwidth as $width)	{
		$myxls->set_column($i, $i, $width);
		$i++;
	}

	$formath1 =& $workbook->add_format();
	$formath1->set_size(12);
    // $formath1->set_align('center');
    $formath1->set_align('vcenter');
	$formath1->set_color('black');
	$formath1->set_bold(1);
	// $formath1->set_italic();
	$formath1->set_text_wrap();
	// $formath1->set_border(2);
    if (isset($table->titlesalign)) {
        $formath1->set_align($table->titlesalign);
        // $formath3->set_align('center');
    } else {
        $formath1->set_align('center');
    }

    $i = $ii = 0;
   
    foreach ($table->titles as $key => $title)	{
		$myxls->set_row($i, $table->titlesrows[$key]);
		$strwin1251 =  $txtl->convert($title, 'utf-8', 'windows-1251');
	    $myxls->write_string($i, 0, $strwin1251, $formath1);
		$myxls->merge_cells($i, 0, $i, $numcolumn-1);
		$i++;
    }

	$formath2 =& $workbook->add_format();
	$formath2->set_size(11);
    $formath2->set_align('center');
    $formath2->set_align('vcenter');
	$formath2->set_color('black');
	$formath2->set_bold(1);
	//$formath2->set_italic();
	$formath2->set_border(2);
	$formath2->set_text_wrap();

	$formath3 =& $workbook->add_format();
	$formath3->set_size(10);
    $formath3->set_align('center');
    $formath3->set_align('vcenter');
	$formath3->set_color('black');
	$formath3->set_bold(1);
	//$formath2->set_italic();
	$formath3->set_border(2);
	$formath3->set_text_wrap();
    

    if (!empty($table->head)) {
    	$formatp = array();
    	$numcolumn = count ($table->head) - $lastcols;
        foreach ($table->head as $key => $heading) {
        	if ($key >= $numcolumn) continue;
            $heading = str_replace ('<br>', "\n", $heading);
            $heading = str_replace ('<hr>', "\n", $heading);
            $heading = str_replace ('&nbsp;', " ", $heading);

	   		$strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	        $myxls->write_string($i, $key,  $strwin1251, $formath2);

			$formatp[$key] =& $workbook->add_format();
			$formatp[$key]->set_size(10);
		    $formatp[$key]->set_align($table->align[$key]);
		    $formatp[$key]->set_align('vcenter');
			$formatp[$key]->set_color('black');
			$formatp[$key]->set_bold(0);
			$formatp[$key]->set_border(1);
			$formatp[$key]->set_text_wrap();
        }
        $i++;
        $ii = $i;
    }

    if (!empty($table->dblhead)) {
        
		$formatpc =& $workbook->add_format();
		$formatpc->set_size(10);
	    $formatpc->set_align('center');
	    $formatpc->set_align('vcenter');
		$formatpc->set_color('black');
		$formatpc->set_bold(0);
		$formatpc->set_border(1);
		$formatpc->set_text_wrap();

		$formatpl =& $workbook->add_format();
		$formatpl->set_size(10);
	    $formatpl->set_align('left');
	    $formatpl->set_align('vcenter');
		$formatpl->set_color('black');
		$formatpl->set_bold(0);
		$formatpl->set_border(1);
		$formatpl->set_text_wrap();

        $myxls->set_row($i, 33);        

        foreach ($table->dblhead->head2 as $key2 => $heading2) {
            $myxls->write_blank($i, $key2+1,  $formath2);
        }    
        
        $countcols = count($table->dblhead->head1);
        $j = 0;
        foreach ($table->dblhead->head1 as $key => $heading) {
            // $heading = str_replace('&nbsp;', ' ', $heading);
            
            if (isset($table->dblhead->span1[$key])) {
            	$span1 = $table->dblhead->span1[$key];
            } else 	{
            	$span1 = '';
            }
            
            $whatspan = substr($span1, 0, 7);
            
            if ($whatspan == 'rowspan') {
                $strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	            $myxls->write_string($i, $key,  $strwin1251, $formath2);
                $myxls->write_string($i+1, $key,  '', $formath2);
                $myxls->merge_cells($i, $key, $i+1, $key);                
            } else if ($whatspan == 'colspan') {
                $adelta = explode('=', $span1);
                $delta = (integer)$adelta[1];
                $strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	            $myxls->write_string($i, $j+1,  $strwin1251, $formath2);
                $myxls->merge_cells($i, $j+1, $i, $j+$delta);                
                for ($ii=0; $ii<$delta; $ii++) {
                    $heading = $table->dblhead->head2[$j];
                    $strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
    	            $myxls->write_string($i+1, $j+1,  $strwin1251, $formath3);
                    $j++;
                }   
            }    
        }   

        $i  += 2;
        $ii = $i;
    }



    if (isset($table->data)) foreach ($table->data as $keyrow => $row) {
      	$numcolumn = count ($row) - $lastcols;
        foreach ($row as $keycol => $item) 	{
           	if ($keycol >= $numcolumn) continue;
            $item = str_replace($order, '<br>', $item);
            $item = str_replace ('<br>', "\n", $item);
            $item = str_replace ('<hr>', "\n", $item);
            $item = str_replace ('&nbsp;', " ", $item);
        	$clearitem = strip_tags($item);
        	switch ($clearitem)	{
        		case '&raquo;': $clearitem = '>>'; break;
        		case '&laquo;': $clearitem = '<<'; break;
        	}
 			$strwin1251 =  $txtl->convert($clearitem, 'utf-8', 'windows-1251');
            if (!empty($table->dblhead)) {
                if ($keycol == 0)   {
			        $myxls->write($i + $keyrow, $keycol,  $strwin1251, $formatpl);
                } else {
                    $myxls->write($i + $keyrow, $keycol,  $strwin1251, $formatpc);
                }
            } else {
                $myxls->write($i + $keyrow, $keycol,  $strwin1251, $formatp[$keycol]);
            }     
			$ii = $i + $keyrow;
		}
    }
    
    if (!empty($table2)) {
    	$i = $ii + 2;
    	
    	$formatp = array();
    	$numcolumn = count ($table2->head) - $lastcols;
        foreach ($table2->head as $key => $heading) {
        	if ($key >= $numcolumn) continue;
	   		$strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	        $myxls->write_string($i, $key,  $strwin1251, $formath2);

			$formatp[$key] =& $workbook->add_format();
			$formatp[$key]->set_size(10);
		    $formatp[$key]->set_align($table2->align[$key]);
		    $formatp[$key]->set_align('vcenter');
			$formatp[$key]->set_color('black');
			$formatp[$key]->set_bold(0);
			$formatp[$key]->set_border(1);
			$formatp[$key]->set_text_wrap();
        }
        $i++;
    }

    if (isset($table2->data)) foreach ($table2->data as $keyrow => $row) {
      	$numcolumn = count ($row) - $lastcols;
        foreach ($row as $keycol => $item) 	{
           	if ($keycol >= $numcolumn) continue;
            $item = str_replace($order, '<br>', $item);
            $item = str_replace ('<br>', "\n", $item);
        	$clearitem = strip_tags($item);
        	switch ($clearitem)	{
        		case '&raquo;': $clearitem = '>>'; break;
        		case '&laquo;': $clearitem = '<<'; break;
        	}
 			$strwin1251 =  $txtl->convert($clearitem, 'utf-8', 'windows-1251');
			$myxls->write($i + $keyrow, $keycol,  $strwin1251, $formatp[$keycol]);
			$ii = $i + $keyrow;
		}
    }
      

    $workbook->close();
}


class discipline_form extends moodleform {

    function definition() {
        global $DB, $faculty, $plan, $discipline, $term;

        $mform =&$this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'fid');
        $mform->setType('fid', PARAM_INT);
        $mform->setDefault('fid', $faculty->departmentcode);

        $mform->addElement('hidden', 'pid');
        $mform->setType('pid', PARAM_INT);
        $mform->setDefault('pid', $plan->id);

        $mform->addElement('hidden', 'did');
        $mform->setType('did', PARAM_INT);
        $mform->setDefault('did', $discipline->id);

        $mform->addElement('hidden', 'term');
        $mform->setType('term', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_RAW);

		$mform->addElement('header', '', get_string('editingdiscipline', 'block_bsu_plan'));
        
        $mform->addElement('static', '', get_string('faculty', 'block_bsu_plan'), $faculty->name);
        $mform->addElement('static', '', get_string('curriculum', 'block_bsu_plan'), $plan->name);
        
        // if ($discipline->id == 0)   {
            $options = get_disciplinenameids_array();
            $mform->addElement('select', 'disciplinenameid', get_string('discipline', 'block_bsu_plan'), $options);
            $mform->setDefault('disciplinenameid', $discipline->id);
        /* } else {
            $mform->addElement('static', '', get_string('discipline', 'block_bsu_plan'), $discipline->nname);    
        }*/
        
        $mform->addElement('static', '', '<hr>', '<hr>');
        
        $fields = array('cyclename', 'identificatorvidaplana', 'gos', 'sr', 
                        'semestrexamen', 'semestrzachet', 'semestrdiffzach', 'semestrkursovik',  'semestrkp', 
                        'competition', 'hoursinter', 'creditov', 'mustlearning', 
                        'identificatordiscipline', 'razdel');
        foreach ($fields as $field) {
            $mform->addElement('text', $field, get_string($field, 'block_bsu_plan'), 'size="20"');
            $mform->setType($field, PARAM_TEXT);
        }
        
        $mform->addElement('static', '', '<hr>', '<hr>');
        $mform->addElement('static', '', '<b>' . get_string('term', 'block_bsu_plan').'</b>', '<b>' .$term .'</b>');
        
        $fields = array('numsemestr', 'lection', 'praktika', 'lab', 'ksr', 'examenhours', 'srs', 'zachet', 'zachetdiff');
        foreach ($fields as $field) {
            if ($field == 'numsemestr') {
                $strfield = get_string($field.'1', 'block_bsu_plan');    
            } else {
                $strfield = get_string($field, 'block_bsu_plan');
            }
            
            $mform->addElement('text', $field, $strfield, 'size="20"');
            $mform->setType($field, PARAM_TEXT);
        }
        
        $this->add_action_buttons(true, get_string('savechanges'));
    }


    function validation($datanew, $files) {
        global $CFG, $DB, $action;

        $datanew = (object)$datanew;
        $datanew->action = $action;

        $err = array();
      
        if (count($err) == 0){
            return true;
        } else {
            return $err;
        }
    }
}

function get_plan_groups($pid, $edformid = 0, $withcountstud = false, $withbr = false)  
{
    global $CFG, $OUTPUT, $DB;
        
        
    $srtedformid = $strgroup = '';   
    $agroups = array();
    if ($edformid > 0)  {
        $srtedformid = 'AND idedform='. $edformid;
    }
    
    $strbr = '';
    if ($withbr)    {
        $strbr = '<br />';
    }
    
    $sql = "SELECT g.name as gname, g.countstud, p.groupid, p.id
            FROM mdl_bsu_ref_groups g
            INNER JOIN mdl_bsu_plan_groups p ON p.groupid=g.id
            where p.planid = $pid $srtedformid
            order by gname";
    // echo $sql . '<br />';                     
    if($allgroups = $DB->get_records_sql($sql))   {

        foreach ($allgroups as $group)  {
            if ($withcountstud) {
                $agroups[] = $strbr . $group->gname . " ($group->countstud ст.)";
            } else {
                $agroups[] = $group->gname;
            }    
        }
        $strgroup = implode ('<br>', $agroups);                              
    }      
    return  $strgroup;
} 

function get_plan_groups_with_link($fid, $pid, $edformid=0, $link = '/blocks/bsu_schedule/group/studentsgroup.php')  
{
    global $CFG, $OUTPUT, $DB;
        
        
    $srtedformid = $strgroup = '';   
    $agroups = array();
    if ($edformid > 0)  {
        $srtedformid = 'AND idedform='. $edformid;
    }
    
    $sql = "SELECT g.name as gname, p.groupid, p.id
            FROM mdl_bsu_ref_groups g
            INNER JOIN mdl_bsu_plan_groups p ON p.groupid=g.id
            where p.planid = $pid $srtedformid
            order by gname";
    // echo $sql . '<br />';                     
    if($allgroups = $DB->get_records_sql($sql))   {
                                
        foreach ($allgroups as $group)  {
            $link2 = $CFG->wwwroot . $link . "?fid=$fid&gid=$group->groupid";
            $agroups[] = "<a href=\"{$link2}\">$group->gname</a>";
        }
        $strgroup = implode ('<br>', $agroups);                              
    }      
    return  $strgroup;
} 


function get_cur_year_term_hours($yid, $polug, $departmentcode, $edformid = 0) 
{
    global $DB, $CFG;
    
    $sumhour = 0;
    if ($plans = $DB->get_records_select('bsu_plan', "departmentcode=$departmentcode and notusing=0", null, '', 'id')) {
        foreach ($plans as $plan)   {
            // echo "<hr>";
            $strgroup = get_plan_groups($plan->id, $edformid);
            if ($strgroup != '')    {
                $agroups = explode ('<br>', $strgroup);
                foreach ($agroups as $group)    {
                    // $h = get_hours_group_polug($yid, $polug, $plan->id, $group);                    
                    $sumhour += get_hours_group_polug($yid, $polug, $plan->id, $group); 
                    // echo "plan->id = $plan->id  group = $group hours = $h <br>";    
                }
            }
        }
    }
    return $sumhour;
}


function get_hours_group_polug($yid, $polug, $planid, $group)
{
    global $DB, $CFG;
    
    $term = get_term_group($yid, $group, $polug);
    // echo " group = $group term = $term <br>";
    $sql = "SELECT d.id as did, d.semestrexamen, d.semestrzachet, d.semestrkursovik, d.semestrdiffzach
                s.lection, s.praktika, s.lab, s.numsemestr 
                FROM {bsu_discipline_semestr} s
                INNER JOIN {bsu_discipline} d ON d.id=s.disciplineid
                WHERE d.planid=$planid and s.numsemestr=$term";
    $hours = 0;           
    if ($datas = $DB->get_records_sql($sql))  {
        foreach ($datas as $data) {
            $hours += $data->lection; //  + $data->praktika + $data->lab;
            if ($subgroups = $DB->get_records_select('bsu_discipline_subgroup', "disciplineid=$data->did", null, '', 'id'))   {
                $cnt = count ($subgroups);
                $hours += ($cnt*($data->praktika + $data->lab));
            }  else {
                $hours += $data->praktika + $data->lab;
            }
            $hours += get_formskontrol_hours($data->semestrexamen, $data->semestrzachet, $data->semestrdiffzach, $data->semestrkursovik, $term);
            // echo "did = $data->did  hours = $hours<br>";           
        }
    }

    return $hours;    
}   

function get_term_group($yid, $group, $polug) 
{
    global $DB;

    $year = $DB->get_record_select('bsu_ref_edyear', "id=$yid", null, 'god');
    
    $group = (int)substr($group, -4, 2);
    $year  = (int)substr($year->god, 2, 2);
    $cid = $year - $group + 1;
    // $cid++; 
    
    if ($term = $DB->get_record_select('bsu_ref_semester', "Course = $cid AND HalfYear = $polug AND Number > 0", null, 'number'))  {
        return $term->number;    
    } 
    
    return 0;     
}


function get_formskontrol_hours($semestrexamen, $semestrzachet, $semestrdiffzach, $semestrkursovik, $term)
{
    $ret = 0;
    
    // echo $semestrexamen . ' - ' . $semestrzachet . ' - ' . $term . '<br>';
    $termhex = dechex($term);
    $termhex = strtoupper($termhex);
    // echo $termhex . '<br>';
    
    $pos = strpos($semestrexamen, $termhex);
    if (!($pos === false)) {
        $ret += 2;
    }    
        
    $pos = strpos($semestrzachet, $termhex);
    if (!($pos === false)) {       
        $ret += 2; 
    }

    $pos = strpos($semestrdiffzach, $termhex);
    if (!($pos === false)) {       
        $ret += 2; 
    }

    $pos = strpos($semestrkursovik, $termhex);
    if (!($pos === false)) {
        $ret += 2;
    }
    
    return $ret; 
}





class discipline_form_metodist extends moodleform {

    function definition() {
        global $DB, $faculty, $plan, $discipline, $term;

        $mform =&$this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'fid');
        $mform->setType('fid', PARAM_INT);
        $mform->setDefault('fid', $faculty->departmentcode);

        $mform->addElement('hidden', 'pid');
        $mform->setType('pid', PARAM_INT);
        $mform->setDefault('pid', $plan->id);

        $mform->addElement('hidden', 'did');
        $mform->setType('did', PARAM_INT);
        $mform->setDefault('did', $discipline->id);

        $mform->addElement('hidden', 'term');
        $mform->setType('term', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_RAW);

		$mform->addElement('header', '', get_string('editingdiscipline', 'block_bsu_plan'));
        
        $mform->addElement('static', '', get_string('faculty', 'block_bsu_plan'), $faculty->name);
        $mform->addElement('static', '', get_string('curriculum', 'block_bsu_plan'), $plan->name);
        
        $mform->addElement('static', '', get_string('discipline', 'block_bsu_plan'), $discipline->nname);    
        
        $mform->addElement('static', '', '<hr>', '<hr>');

        /*
        $fields = array('semestrdiffzach');
        foreach ($fields as $field) {
            $mform->addElement('text', $field, get_string($field, 'block_bsu_plan'), 'size="20"');
            $mform->setType($field, PARAM_TEXT);
        }
        */
        $options = array();
        for ($ii = 1; $ii<=9; $ii++)    {
            $options[$ii] = $ii;    
        }
        $options['A'] = 'A';
        $options['B'] = 'B';
        $options['C'] = 'C';
        $options['D'] = 'D';
        $options['E'] = 'E';
        $options['F'] = 'F';
        $mform->addElement('select', 'semestrdiffzach', get_string('semestrdiffzach', 'block_bsu_plan'), $options);
        $mform->setDefault('semestrdiffzach', $discipline->semestrdiffzach);
        

        $mform->addElement('static', '', '<hr>', '<hr>');
        
        $fields = array('cyclename', 'identificatorvidaplana', 'gos', 'sr', 
                        'semestrexamen', 'semestrzachet', 'semestrkursovik',  
                        'competition', 'hoursinter', 'creditov', 'mustlearning', 
                        'identificatordiscipline', 'razdel');
        foreach ($fields as $field) {
            $mform->addElement('static', '', get_string($field, 'block_bsu_plan'), $discipline->{$field});
        }
        /*
        $mform->addElement('static', '', '<hr>', '<hr>');
        $mform->addElement('static', '', '<b>' . get_string('term', 'block_bsu_plan').'</b>', '<b>' .$term .'</b>');
        
        $fields = array('numsemestr', 'lection', 'praktika', 'lab', 'ksr', 'examenhours', 'srs', 'zachet', 'zachetdiff');
        foreach ($fields as $field) {
            $mform->addElement('text', $field, get_string($field, 'block_bsu_plan'), 'size="20"');
            $mform->setType($field, PARAM_TEXT);
        }
        */
        
        $this->add_action_buttons(true, get_string('savechanges'));
    }

}



class practice_form extends moodleform {

    function definition() {
        global $DB, $fid, $pid, $yid, $practice, $action, $edformid, $tab;

        $mform =&$this->_form;

        $mform->addElement('hidden', 'yid');
        $mform->setType('yid', PARAM_INT);
        $mform->setDefault('yid', $yid);
        
        $mform->addElement('hidden', 'pid');
        $mform->setType('pid', PARAM_INT);
        $mform->setDefault('pid', $pid);

        $mform->addElement('hidden', 'fid');
        $mform->setType('fid', PARAM_INT);
        $mform->setDefault('fid', $fid);

        $mform->addElement('hidden', 'prid');
        $mform->setType('prid', PARAM_INT);
        $mform->setDefault('prid', $practice->id);

        $mform->addElement('hidden', 'eid');
        $mform->setType('eid', PARAM_INT);
        $mform->setDefault('eid', $practice->edworkkindid);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_RAW);
        $mform->setDefault('action', $action);

        $mform->addElement('hidden', 'tab');
        $mform->setType('tab', PARAM_RAW);
        $mform->setDefault('tab', $tab);
        
        // $mform->addElement('static', '', get_string('faculty', 'block_bsu_plan'), $faculty->name);

        // if ($action != 'editspec')  {
        if ($practice->edworkkindid == 13)  {
            $mform->addElement('header', '', 'Редактирование практики');
            $options = get_practicetype_array($edformid);    
            $mform->addElement('select', 'practicetypeid', get_string('practicetype', 'block_bsu_plan'), $options);
            $mform->addElement('hidden', 'edworkkindid');
            $mform->setType('edworkkindid', PARAM_INT);
            $mform->setDefault('edworkkindid', 13);

        }  else {
            $mform->addElement('header', '', 'Редактирование спец. вида работы');
            if ($yid >= 15) {
                $strsql = "SELECT id, name, description FROM {bsu_ref_edworkkind} WHERE id not in (1,2,3,4,5,6,13,14,35,36,39,40) order by name";
            } else {
                $strsql = "SELECT id, name, description FROM {bsu_ref_edworkkind} WHERE id not in (1,2,3,4,5,13,14,39,40) order by name";
            }    
            $menu = array();
        	if ($options = $DB->get_records_sql($strsql)) 	{
        		foreach ($options as $option) {
        			$menu[$option->id] = $option->name . ' (' .$option->description .')';
        		}
                $menu[10] = 'КОНСгак (консультации перед ГАК)';
        	}   
            $mform->addElement('select', 'edworkkindid', get_string('edwork', 'block_bsu_schedule'), $menu);
            $mform->setDefault('edworkkindid', $practice->edworkkindid);     
        }
        

        $mform->addElement('text', 'name', get_string('name'), 'size="100"');
        $mform->setType('name', PARAM_RAW);
        $mform->addRule('name', get_string('missingname'), 'required', null, 'client');        
        
        $mform->addElement('text', 'term', get_string('term', 'block_bsu_plan'), 'size="5"');
        $mform->setType('term', PARAM_RAW);
        $mform->addRule('term', get_string('missingname'), 'required', null, 'client');        

        if ($practice->edworkkindid == 13)  {
            $mform->addElement('text', 'week', get_string('week', 'block_bsu_plan'), 'size="5"');
            $mform->setType('week', PARAM_RAW);
            // $mform->addRule('week', get_string('missingname'), 'required', null, 'client');
        }            

        $this->add_action_buttons(true, get_string('savechanges'));
    }


    function validation($datanew, $files) {
        global $CFG, $DB, $action;

        $datanew = (object)$datanew;
        $datanew->action = $action;

        $err = array();
        if(empty($datanew->name)) {
            $err['name'] = 'Введите, пожалуйста, название практики.';
        }    

        if(empty($datanew->term)) {
            $err['term'] = 'Введите, пожалуйста, номер семестра.';
        }    
        /*
        if(empty($datanew->week)) {
            $err['term'] = 'Введите, пожалуйста, количество недель.';
        } 
        */   

        if (count($err) == 0){
            return true;
        } else {
            return $err;
        }
    }
}



function get_practicetype_array($edformid)
{
    global $DB;

//     $edforms = get_edformids_array();
    $edformso = $DB->get_records_select('bsu_tsotdelenie', '', null, '', "idotdelenie, otdelenie"); 
    $edforms = array();
    foreach ($edformso as $ed) {
        $edforms[$ed->idotdelenie] = $ed->otdelenie; 
    }
    
    $options = array();
    if ($prtypes = $DB->get_records_select('bsu_ref_practice_type', "edformid = $edformid", null, 'name', 'id, name, edformid'))    {
        foreach ($prtypes as $prtype)   {
            $options[$prtype->id] = $prtype->name . ' ('. $edforms[$prtype->edformid] . ')'; 
        } 
    }
  
    return $options;       
}


function listbox_specvidrabot($scriptname, $planid, $prid)
{
    global $CFG, $OUTPUT, $DB;

    $edworkkind = $DB->get_records_menu('bsu_ref_edworkkind', null, '', 'id, name');
    $edworkkind[10] = 'КОНСгак'; 
    $edworkkind[100] = 'РМР';

    $planmenu = array();
    $planmenu[0] = 'Выберите спец. вида работу';// get_string('selectpractice', 'block_bsu_plan').'...';

    if ($specvidrabots = $DB->get_records_select('bsu_plan_practice', "planid = $planid AND edworkkindid<>13", null, 'term, edworkkindid'))  {
        foreach ($specvidrabots as $specvidrabot)   {
            $planmenu[$specvidrabot->id] = $edworkkind[$specvidrabot->edworkkindid] . ': ' . $specvidrabot->name;
        }
    }    

  echo '<tr align="left"> <td align=right>Спец. вида работа: </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'prid', $planmenu, $prid, null, 'switchspecvid');
  echo '</td></tr>';
  return 1;
}



function get_formskontrol($semestrexamen, $semestrzachet, $semestrkursovik, $semestrdiffzach, $term, 
                           $semestrkp=0, $allhours=0, $semestrref=0, $semestrkontr=0)
{
    $ret = '-';
    
    // shift_semestrexamenzachet('123456A');
    // shift_semestrexamenzachet($semestrzachet);
    // echo $semestrexamen . ' - ' . $semestrzachet . ' - ' . $term . '<br>';
    $termhex = dechex($term);
    $termhex = strtoupper($termhex);
    // echo $termhex . '<br>';
    
    /*
    $pos = strpos($semestrexamen, $termhex);
    if (!($pos === false)) {
        $ret = 'экз.';
    } 
    */
    $aret = array();
    if (!empty($semestrexamen)) {
        $arr1 = str_split($semestrexamen);
        foreach ($arr1 as  $arr)    {
            if ($arr == $termhex) $aret[] = 'экз.';    
        }
    }    
    
    /*
    $pos = strpos($semestrzachet, $termhex);
    if (!($pos === false)) {
        if ($ret != '-')    {
            $ret .= ', зач.';
        } else {
            $ret = 'зач.';
        }    
    }
    */

    if (!empty($semestrdiffzach)) {
        $arr1 = str_split($semestrdiffzach);
        foreach ($arr1 as  $arr)    {
            if ($arr == $termhex)  $aret[] = 'дифф.зач.';    
        }
    }    

    if (!empty($semestrzachet)) {
        $arr1 = str_split($semestrzachet);
        $counzachet = count($arr1);
        foreach ($arr1 as  $ii => $arr)    {
            if ($arr == $termhex)  {
                $aret[] = 'зач.';
                /*
                // echo "$allhours > 108 && empty($semestrexamen) && $ii == ($counzachet-1)<br />";
                if ($allhours > 108 && empty($semestrexamen) && $ii == ($counzachet-1) && $term <= 6)    {
                    $aret[] = '<strong>ДИФФ.ЗАЧ.</strong>';
                }  else {
                    $aret[] = 'зач.';
                }  
                */
            }                    
        }
    }    
    
    /*
    $pos = strpos($semestrkursovik, $termhex);
    if (!($pos === false)) {
        if ($ret != '-')    {
            $ret .= ', курс.р.';
        } else { 
            $ret = 'курс.р.';
        }    
    }
    */

    if (!empty($semestrkursovik)) {
        $arr1 = str_split($semestrkursovik);
        foreach ($arr1 as  $arr)    {
            if ($arr == $termhex)  $aret[] = 'курс.р.';    
        }
    }    


    if (!empty($semestrkp)) {
        $arr1 = str_split($semestrkp);
        foreach ($arr1 as  $arr)    {
            if ($arr == $termhex)  $aret[] = 'к.п.';    
        }
    }    
    
    if (!empty($semestrref)) {
        $arr1 = str_split($semestrref);
        foreach ($arr1 as  $arr)    {
            if ($arr == $termhex)  $aret[] = 'реф.';    
        }
    }    

    if (!empty($semestrkontr)) {
        $arr1 = str_split($semestrkontr);
        foreach ($arr1 as  $arr)    {
            if ($arr == $termhex)  $aret[] = 'контр.';    
        }
    }    
    

    if (!empty($aret))  {
        $ret = implode(', ', $aret); 
    }
    
    return $ret; 
 
}


function get_edyear_name($yid)  
{
    global $DB;
    
    $yearname = $DB->get_field_select('bsu_ref_edyear', 'EdYear', "Id = $yid");
    $ayid = explode ('/', $yearname);
    $yearname = '20'.$ayid[0].'/20'.$ayid[1];
    
    return $yearname;
}



function delete_charge_discipline_term($frm, $term)
{
    global $DB, $OUTPUT;
    
    // print_object($frm);
    // echo "$term";
     // находим существующую нагрузку
    $conditions = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplineid' => $frm->did, 'term' => $term);
    // print_object($conditions);
    if ($edworks = $DB->get_records('bsu_edwork', $conditions, '', 'id, edworkmaskid'))	{
        $edmids = array();
        foreach ($edworks as $edwork)   {
            $DB->delete_records_select('bsu_edwork', "id = $edwork->id");
            $edmids[] = $edwork->edworkmaskid;
        }
        $edmids2 = array_unique($edmids);
        
        foreach ($edmids2 as $edworkmaskid)  {
           $sql = "SELECT id, edworkmaskid FROM mdl_bsu_edwork where edworkmaskid=$edworkmaskid";
           if (!$edworks = $DB->get_records_sql($sql))  {
             $DB->delete_records_select('bsu_edwork_mask', "id = $edworkmaskid");
             $DB->delete_records_select('bsu_teachingload', "edworkmaskid = $edworkmaskid");
           }
        }
    }
}                      


/**
 * Данная функция создает низподающий список учебных лет. 
 * @param string  $scriptname строка адреса скрипта, использующего список
 * @param int     $yid id учебного года
 * @return string HTML-код для отображения низподающего списка
 */
////////////////////////////////////////////////////////////////////////////
function listbox_edyear($scriptname, $yid)
{
    global $CFG, $DB, $OUTPUT;

    $outmenu = array();
    // $outmenu[0] = 'Выберите учебный год...';
    if( $edyears = $DB->get_records('bsu_ref_edyear', null, 'id') ){
        foreach($edyears AS $edyear){
            // $outmenu[$edyear->id] = $edyear->god." (".$edyear->edyear.") ";
            if ($edyear->id == 1)   {
                $outmenu[$edyear->id] = $edyear->edyear;
            } else {
                list ($y1, $y2) = explode ('/',  $edyear->edyear);
                $outmenu[$edyear->id] = '20'.$y1.'/20'.$y2;
            }       
        }
    }  
    echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left">';        
    echo $OUTPUT->single_select($scriptname, 'yid', $outmenu, $yid, null, 'switchyid');
    echo '</td></tr>';

    return 1;
}


/**
 * Print the top portion of a standard themed box.
 *
 * @param string $align ?
 * @param string $width ?
 * @param string $color ?
 * @param int $padding ?
 * @param string $class ?
 * @todo Finish documenting this function
 */
function print_simple_box_start_old($align='', $width='', $color='', $padding=5, $class='generalbox', $id='') {

    if ($color) {
        $color = 'bgcolor="'. $color .'"';
    }
    if ($align) {
        $align = 'align="'. $align .'"';
    }
    if ($width) {
        $width = 'width="'. $width .'"';
    }
    if ($id) {
        $id = 'id="'. $id .'"';
    }
    echo "<table $align $width $id class=\"$class\" border=\"0\" cellpadding=\"$padding\" cellspacing=\"0\">".
         "<tr><td $color class=\"$class"."content\">";
}

/**
 * Print the end portion of a standard themed box.
 */
function print_simple_box_end_old() {
    echo '</td></tr></table>';
}



/**
 * Print a nicely formatted COLOR table.
 *
 * @param array $table is an object with several properties.
 *     <ul<li>$table->head - An array of heading names.
 *     <li>$table->align - An array of column alignments
 *     <li>$table->size  - An array of column sizes
 *     <li>$table->wrap - An array of "nowrap"s or nothing
 *     <li>$table->data[] - An array of arrays containing the data.
 *     <li>$table->width  - A percentage of the page
 *     <li>$table->cellpadding  - Padding on each cell
 *     <li>$table->cellspacing  - Spacing between cells
 * new!!!! $table->bgcolor[] - An array of TD colors
 * new!!!! $table->wraphead
 * new!!!! $table->border
 * new!!!! $table->tablealign  - Align the whole table
 * new!!!! $table->class
 * new!!!! $table->class
 * new!!!! $table->dblhead->head1 - An array of first row heading
 * new!!!! $table->dblhead->span1 - An array of first row spaning (example, rowspan=2 or colspan=11)
 * new!!!! $table->dblhead->head2 - An array of second row heading
 * </ul>
 * @return boolean
 * @todo Finish documenting this function
 */
function print_color_table($table) {

    if (isset($table->align)) {
        foreach ($table->align as $key => $aa) {
            if ($aa) { // && $aa != 'left'
                $align[$key] = ' align='. $aa;
            } else {
                $align[$key] = '';
            }
        }
    }
    if (isset($table->size)) {
        foreach ($table->size as $key => $ss) {
            if ($ss) {
                $size[$key] = ' width="'. $ss .'"';
            } else {
                $size[$key] = '';
            }
        }
    }
    if (isset($table->wrap)) {
        foreach ($table->wrap as $key => $ww) {
            if ($ww) {
                $wrap[$key] = ' nowrap ';
            } else {
                $wrap[$key] = '';
            }
        }
    }

    if (empty($table->width)) {
        $table->width = '80%';
    }

    if (empty($table->tablealign)) {
        $table->tablealign = 'center';
    }

    if (empty($table->cellpadding)) {
        $table->cellpadding = '5';
    }

    if (empty($table->cellspacing)) {
        $table->cellspacing = '1';
    }

    if (empty($table->class)) {
        $table->class = 'generaltable';
    }

    if (empty($table->headerstyle)) {
        $table->headerstyle = 'header';
    }

    if (empty($table->border)) {
        $table->border = '1';
    }

    $tableid = empty($table->id) ? '' : 'id="'.$table->id.'"';

    // print_simple_box_start_old('center', $table->width, '#ffffff', 0);
	// echo '<table width="'.$table->width.' border='.$table->border;
    // echo " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\" class=\"$table->class boxalign$table->tablealign\" $tableid>\n";
    // echo '<table width="100%" border=1 align=center ';
    // echo " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\" $tableid class=\"$table->class\">\n"; //bordercolor=gray
    echo "<table align=center $tableid class=\"$table->class\">\n"; //bordercolor=gray

    $countcols = 0;

    if (!empty($table->head)) {
        $countcols = count($table->head);
        echo '<tr>';
        foreach ($table->head as $key => $heading) {

            if (!isset($size[$key])) {
                $size[$key] = '';
            }
            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            if (isset($table->wraphead) && $table->wraphead == 'nowrap') {
            	$headwrap = ' nowrap="nowrap" ';
            } else 	{
            	$headwrap = '';
            }
            echo '<th '. $align[$key].$size[$key] . $headwrap . " class=\"$table->headerstyle\">". $heading .'</th>'; // class="header c'.$key.'
			// $output .= '<th style="vertical-align:top;'. $align[$key].$size[$key] .';white-space:nowrap;" class="header c'.$key.'" scope="col">'. $heading .'</th>';
        }
        echo '</tr>'."\n";
    }

    if (!empty($table->dblhead)) {
        $countcols = count($table->dblhead->head1);
        echo '<tr>';
        foreach ($table->dblhead->head1 as $key => $heading) {

            if (isset($table->dblhead->size[$key])) {
                $size[$key] = $table->dblhead->size[$key];
            } else {
                $size[$key] = '';
            }

            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            if (isset($table->wraphead) && $table->wraphead == 'nowrap') {
            	$headwrap = ' nowrap="nowrap" ';
            } else 	{
            	$headwrap = '';
            }

            if (isset($table->dblhead->span1[$key])) {
            	$span1 = $table->dblhead->span1[$key];
            } else 	{
            	$span1 = '';
            }

            echo "<th $span1 ". $align[$key].$size[$key] . $headwrap . " class=\"$table->headerstyle\">". $heading .'</th>'; // class="header c'.$key.'
			// $output .= '<th style="vertical-align:top;'. $align[$key].$size[$key] .';white-space:nowrap;" class="header c'.$key.'" scope="col">'. $heading .'</th>';
        }
        echo '</tr>'."\n";

        $countcols = count($table->dblhead->head2);
        echo '<tr>';
        foreach ($table->dblhead->head2 as $key => $heading) {

            if (!isset($size[$key])) {
                $size[$key] = '';
            }
            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            if (isset($table->wraphead) && $table->wraphead == 'nowrap') {
            	$headwrap = ' nowrap="nowrap" ';
            } else 	{
            	$headwrap = '';
            }

            echo '<th '. $align[$key].$size[$key] . $headwrap . " class=\"$table->headerstyle\">". $heading .'</th>'; // class="header c'.$key.'
			// $output .= '<th style="vertical-align:top;'. $align[$key].$size[$key] .';white-space:nowrap;" class="header c'.$key.'" scope="col">'. $heading .'</th>';
        }
        echo '</tr>'."\n";
    }

    if (!empty($table->data)) {
        $oddeven = 1;
        foreach ($table->data as $keyrow => $row) {
            $oddeven = $oddeven ? 0 : 1;
            //echo "<tr class=\"$table->class\">"."\n";
            echo '<tr class="r1">'."\n";
            if (is_string($row)) {
                $dd = explode('|', $row); 
                if ($dd[0] == 'hr' and $countcols) {
                    echo '<td colspan="'. $countcols .'"><div class="tabledivider"></div></td>';
                } else if ($dd[0] == 'dr' and $countcols) {
                    echo '<td align=center colspan="'. $countcols .'">'.$dd[1].'</td>';
                }    
            } else {  /// it's a normal row of data
                foreach ($row as $key => $item) {
                    if (!isset($size[$key])) {
                        $size[$key] = '';
                    }
                    if (!isset($align[$key])) {
                        $align[$key] = '';
                    }
                    if (!isset($wrap[$key])) {
                        $wrap[$key] = '';
                    }
                    if (!empty($table->bgcolor[$keyrow][$key])) {
                    	$tdbgcolor = ' bgcolor="#'.$table->bgcolor[$keyrow][$key].'"';
                    }
                    else {
                    	$tdbgcolor = '';
                    }
                    echo '<td class="cell c0" '. $align[$key].$size[$key].$wrap[$key].$tdbgcolor. '>'. $item .'</td>'; //  class="'.$table->class.'"
                }
            }
            echo '</tr>'."\n";
        }
    }
    echo '</table>'."\n";
    // print_simple_box_end_old();

    return true;
}



function get_maxsemestr_plan($planid)   
{
    global $DB;           
    
    $timenorm = $DB->get_field_select('bsu_plan', 'timenorm', "id = $planid");
    $maxplanterm =  2*$timenorm;
    
	$sql = "SELECT max(b.numsemestr) as maxsem FROM {bsu_discipline} a
            inner join {bsu_discipline_semestr} b on a.id=b.disciplineid
            where a.planid=$planid";
    if ($max = $DB->get_record_sql($sql))  {
        $maxsem = $max->maxsem;
    } else {
        if (empty($timenorm))   {
            $maxsem = 14;
        } else {
            $maxsem = 2*$timenorm;
        }
    }
    
    if ($maxplanterm > $maxsem) $maxsem = $maxplanterm;

    if ($maxtermpractice = $DB->get_field_sql("SELECT max(term) FROM mdl_bsu_plan_practice where planid=$planid"))    {
        if ($maxtermpractice > $maxsem) $maxsem = $maxtermpractice;
    }    
    
    return $maxsem;
}     



function get_list_box_all_kaf($yid, $fid)
{    
    global $DB;
       
    $edyear = $DB->get_field_select('bsu_ref_edyear', 'edyear', "id=$yid");
    // concat (name, ' (', $edyear, ')') as name 
    if($fid > 0) {
        $sql = "SELECT id, concat (name, ' (', '$edyear', ')') as name 
                FROM {bsu_vw_ref_subdepartments} WHERE id>1 AND departmentcode=$fid AND yearid = $yid ORDER BY NAME";
        $menu1 = $DB->get_records_sql_menu($sql, null);
        $sql = "SELECT id, concat (name, ' (', '$edyear', ')') as name
                FROM {bsu_vw_ref_subdepartments} 
                WHERE id>1 AND departmentcode<>$fid AND departmentcode>10000 AND yearid = $yid 
                ORDER BY NAME";
        $menu2 = $DB->get_records_sql_menu($sql, null);
    } else {
        $menu1 = $DB->get_records_sql_menu("SELECT id, concat (name, ' (', '$edyear', ')') as name 
                            FROM {bsu_vw_ref_subdepartments} 
                            WHERE departmentcode>10000 AND yearid = $yid", null);
        $menu2 = array();
    }
    
    if (!empty($menu2)) {
        $menu1[0] = get_string('selectsubdepartment', 'block_bsu_schedule') . '...';
        ksort($menu1);
        $allkaf = array(array('Кафедры факультета'=>$menu1), array('Другие кафедры'=>$menu2));  
    } else {
        $allkaf = $menu1;
        $allkaf[0] = get_string('selectsubdepartment', 'block_bsu_schedule');
        ksort($allkaf);
    }
    
    return $allkaf;
    
}    




/**
 * Add an entry to the bsu_plan_log table.
 *
 * @package dean
 * @category log
 * @global moodle_database $DB
 * @global stdClass $CFG
 * @global stdClass $USER
 * @uses SITEID
 * @uses DEBUG_DEVELOPER
 * @uses DEBUG_ALL
 * @param    int     $planid  The plan id
 * @param    string  $action  'view', 'update', 'add' or 'delete', possibly followed by another word to clarify.
 * @param    string  $url     The file and parameters used to see the results of the action
 * @param    string  $info    Additional description information
 * @return void
 */
function add_to_bsu_plan_log($action, $planid, $disciplineid, $url='', $info='') 
{
    global $DB, $CFG, $USER;

    $userid = empty($USER->id) ? '0' : $USER->id;

    $REMOTE_ADDR = getremoteaddr();

    $timenow = time();
    $info = $info;
    if (!empty($url)) { // could break doing html_entity_decode on an empty var.
        parse_str($url); 
        $url = html_entity_decode($url);
    } else {
        $url = '';
    }

    // Restrict length of log lines to the space actually available in the
    // database so that it doesn't cause a DB error. Log a warning so that
    // developers can avoid doing things which are likely to cause this on a
    // routine basis.
    if(!empty($info) && textlib::strlen($info)>255) {
        $info = textlib::substr($info,0,252).'...';
        debugging('Warning: logged very long info',DEBUG_DEVELOPER);
    }

    // If the 100 field size is changed, also need to alter print_log in course/lib.php
    if(!empty($url) && textlib::strlen($url)>255) {
        $url = textlib::substr($url,0,252).'...';
        debugging('Warning: logged very long URL',DEBUG_DEVELOPER);
    }

    if (defined('MDL_PERFDB')) { global $PERF ; $PERF->logwrites++;};

    $log = array('time'=>$timenow, 
                 'userid'=>$userid, 
                 'ip'=>$REMOTE_ADDR, 
                 'action'=>$action, 
                 'planid'=>$planid,
                 'disciplineid' => $disciplineid, 
                 'url'=>$url, 
                 'info'=>$info);

    
    list($module, $action)  = explode(':', $action); 
                 
    if ($module == 'discipline')  {
        if (isset($disciplinenameid)) {
            $log['disciplinenameid'] = $disciplinenameid;
        }    
        if (isset($numsemestr)) {
            $log['term'] = $numsemestr;
        }
        if (isset($notusing)) {
            $log['notusing'] = $notusing;
        }
    } else if ($module == 'stream')  {
        $log['groupid'] = $groupid;
        $log['subgroupid'] = $subgroupid;
        $log['numstream'] = $numstream;
        $log['term'] = $term;
        $log['edworkkindid'] = $edworkkindid;
    } else if ($module == 'practice')  {
        $log['term'] = $term;
        $log['edworkkindid'] = $edworkkindid;
    }

    try {
        // print_object($log);
        $DB->insert_record_raw('bsu_plan_log', $log, false);
    } catch (dml_exception $e) {
        debugging('Error: Could not insert a new entry to the Moodle log. '. $e->error, DEBUG_ALL);
    }
}


function get_all_terms_discipline_in_kontrol($discipline)
{
    global $DB;
  
    $akontr = array("examen" => 'semestrexamen', "zachet" => 'semestrzachet', "zachetdiff" => 'semestrdiffzach', 
                    "kr" => 'semestrkursovik',   "kp" => 'semestrkp', 
                    'referat' => 'semestrref', 'kontr' => 'semestrkontr');

    $dsemestrs = array();
    foreach ($akontr as $ds_field => $semestr_kontr)  {
        if (empty($discipline->{$semestr_kontr})) continue;
        $terms = str_split($discipline->{$semestr_kontr}, 1);
        foreach ($terms as $term)   {
            $decterm = hexdec($term);
            if (!isset($dsemestrs[$decterm]))   {
                $dsemestrs[$decterm] = new stdClass();
                foreach ($akontr as $afield => $avalue)  {
                    $dsemestrs[$decterm]->{$afield} = 0;
                }    
            }
            
            $dsemestrs[$decterm]->{$ds_field} = 1;
        }
    }
    
        
    return $dsemestrs; 
}


function listbox_specyality($scriptname, $fid, $specialityid)
{
    global $CFG, $OUTPUT, $DB;
    
    $idfacultet = $DB->get_field_select('bsu_ref_department', 'id', "DepartmentCode=$fid");
    $menu1 = array( 1 => 'Все специальности');
    $menu2 = $DB->get_records_menu('bsu_tsspecyal', array('idFakultet' => $idfacultet), 'Specyal', "idSpecyal, Specyal");
    $menu  = $menu1 + $menu2;
    // ksort($menu); 

    echo '<tr align="left"> <td align=right>Специальность: </td><td align="left">';
    echo $OUTPUT->single_select($scriptname, 'sid', $menu, $specialityid, null, 'switchspec');
    echo '</td></tr>';
}

?>