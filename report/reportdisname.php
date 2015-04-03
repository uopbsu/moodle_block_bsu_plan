<?PHP // $Id: prakticecharge.php, v 1.1 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");
    require_once("../../bsu_charge/lib_charge.php");
    require_once("../../bsu_charge/lib_charge_spec.php");
    require_once("../lib_report.php");
    // require_once("lib.php");  

    $yid = optional_param('yid', 0, PARAM_INT);					// current year
    $fid = optional_param('fid', 0, PARAM_INT);	
    $planid = optional_param('pid', 0, PARAM_INT);      // plan id    
    $term = optional_param('term', 1, PARAM_INT);
	$prakid = optional_param('prid', 0, PARAM_INT);			// Discipline id (courseid)
	$eid = optional_param('eid', 0, PARAM_INT);			// edworkkindid
    $dnid = optional_param('dnid', 0, PARAM_INT);			        // id subdepartment    
    $action = optional_param('action', '', PARAM_ACTION);		// action
    $tab = optional_param('tab', 'setteacher', PARAM_ACTION);		// action
    $level = optional_param('level', 'd', PARAM_TEXT);					// discipline  id

    require_login();
    if ($yid == 0)   {
        $yid = get_current_edyearid();
        // $yid++;
    }

	$scriptname = basename($_SERVER['PHP_SELF']);	// echo '<hr>'.basename(me());
	$arrscriptname = explode('.', $scriptname);
    // $scriptname = $arrscriptname[0];
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strscript = 'Отчет по дисциплине';// get_string($arrscriptname[0], 'block_bsu_charge');

    if($action == 'excel') {
        $table = view_discipline_data($yid, $dnid);
        print_table_to_excel($table);
    }    

    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strscript);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->navbar->add($strtitle, new moodle_url("$CFG->BSU_PLAN/index.php", array()));
    $PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

    include('tabs.php');

	$scriptname = basename($_SERVER['PHP_SELF']);	// echo '<hr>'.basename(me());
    
    $adisciplinenames = array('Иностранный язык','История','Философия','Экономическая теория',
        'Экономика','Безопасность жизнедеятельности', 'Концепции современного естествознания','Культурология' );
    
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    listbox_disciplinename($scriptname, $adisciplinenames, $dnid);
    /*
    echo '<tr align="left"> <td align=right>Учебный год: </td><td align="left">';
    echo '';
    echo '</td></tr>';
    */
    echo '</table>';

    if($dnid > 0)    {
        
        ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
        @set_time_limit(0);
        @ob_implicit_flush(true);
        @ob_end_flush();

        $options = array('action'=> 'excel', 'level' => $level, 'yid' => $yid, 'dnid' => $dnid, 'sesskey' => sesskey());
        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center>';        
        
        $table = view_discipline_data($yid, $dnid);
        echo '<center>'. html_writer::table($table) . '</center>'; 

        echo '<center>'.$OUTPUT->single_button(new moodle_url($scriptname, $options), get_string('downloadexcel'), 'get', $options).'</center>';        
   }
   
    echo $OUTPUT->footer();
   

/**
 * Данная функция создает низподающий список рабочих учебных планов
 * @param string  $scriptname строка адреса скрипта, использующего список
 * @param int     $fid код факультета из поля DepartmentCode
 * @param int     $pid код выбранного плана 
 * @return string HTML-код для отображения низподающего списка
 */
function listbox_disciplinename($scriptname, $adisciplinenames, $dnid)
{
  global $CFG, $OUTPUT, $DB;

  $planmenu = array();
  $planmenu[0] = 'Выберите дисциплину ...';

  // $strnames = implode (',', $adisciplinenames);
  $strnames = '';
  foreach ($adisciplinenames as $a) {
     $strnames .= "'".$a. "'" . ',';
  }
  $strnames .= "''";
  $sql = "SELECT id, name FROM {bsu_ref_disciplinename} WHERE name in ($strnames) ORDER BY name";
  // echo $sql;  
  if($allplans = $DB->get_records_sql($sql))   {
		foreach ($allplans as $plan) 	{
            $planmenu[$plan->id] = $plan->name;
		}
  }

  echo '<tr align="left"> <td align=right>'.get_string('discipline', 'block_bsu_plan').': </td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'dnid', $planmenu, $dnid, null, 'switchdnid');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}

   
function view_discipline_data($yid, $dnid)
{
    global $DB;

    $table = new html_table();
    // $table->width = '100%' ;
    
    $table->head  = array('№', 'Институт/Факультет', 'Кафедра', 'Специальность', 'Квалификация', 
                           'Форма обучения', 'Дисциплина', 'Подлежит изучению', 'Лекции', 'Пр.занятия', 'Всего ауд.з.');
    // $table->rowclasses = array();
    $table->align = array ('center', 'left', 'left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'center');
    // $table->size = array ('5%', '40%', '70%');

    $table->columnwidth = array (5, 50, 50, 30, 16, 20, 20, 10, 10, 10, 10);
	// $table->class = 'userenrolment';
    // $table->attributes['class'] = 'userenrolment';

   	$table->titlesrows = array(20);
    $table->titles = array('Отчет по дисциплине');
    $table->titlesalign = 'left';
    $table->downloadfilename = 'reportdisname_' . $dnid;
    $table->worksheetname = $table->downloadfilename;
        
        
        $table->data = array();
        
        $sql = "SELECT  distinct dep.name,  sd.name as kafedra, s.Specyal as specialnost, ts.Kvalif,  
                        tot.otdelenie as edform, dn.name as disciplina, 
                        d.mustlearning, sum(ss.lection) as lec, sum(ss.praktika) as pr, 
                        sum(ss.lection) + sum(ss.praktika) as aud
                        FROM mdl_bsu_discipline d
                        inner join mdl_bsu_discipline_semestr ss on d.id=ss.disciplineid
                        INNER JOIN mdl_bsu_ref_disciplinename dn ON dn.id=d.disciplinenameid
                        INNER JOIN  mdl_bsu_plan p ON p.id=d.planid
                        INNER JOIN  mdl_bsu_tsspecyal s ON s.idSpecyal=p.specialityid
                        INNER JOIN mdl_bsu_ref_department dep on dep.departmentcode=p.departmentcode
                        INNER JOIN mdl_bsu_tskvalifspec ts on ts.idKvalif = p.kvalif
                        INNER JOIN mdl_bsu_tsotdelenie tot on tot.idOtdelenie = p.edformid
                        inner join mdl_bsu_discipline_subdepartment ds on d.id=ds.disciplineid
                        inner join mdl_bsu_vw_ref_subdepartments sd on sd.id=ds.subdepartmentid
                        where p.departmentcode<>0 and d.disciplinenameid=$dnid and ds.yearid=$yid and  tot.idOtdelenie = 2
                        group by d.id
                        order by dep.departmentcode, s.Specyal, dn.name;";
                        
         $result = $DB->mysqli->query($sql, MYSQLI_STORE_RESULT);
       

        $return = array();
        $i = 1;
        
        while($row = $result->fetch_assoc()) {
            $td = array();
            $td[] = $i++;
            foreach ($row as $value)    {
                $td[] = $value;    
            }
           // print_object($row);
           // $return[$i++] = (object)$row;
           $table->data[] = $td; 
        }
        $result->close();
                      
/*
        if( $plans = $DB->get_records_sql($textsqlplan)) {
            foreach($plans AS $plan){
    
         
                $table->data[] =   array($field0, $field1, $field2);
                
                //$table->data[] =   array('', '',  );
            }
        }
*/

        return $table;
}   



?>