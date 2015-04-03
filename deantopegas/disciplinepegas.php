<?php
	
    require_once("../../../config.php");
    require_once("../../../config_pegas.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_schedule/lib_schedule.php");
    require_once("../../bsu_schedule/group/lib_local.php");

    define("MAX_SYMBOLS_LISTBOX", 120);
    
    $yid = optional_param('yid', 0, PARAM_INT);                 // ed yearid
    $fid = optional_param('fid', 0, PARAM_INT);					// departmentcode
    $pid = optional_param('pid', 0, PARAM_INT);                 // plan id
    $tab = optional_param('tab', 'plan', PARAM_TEXT);
    $gid = optional_param('gid', 0, PARAM_INT);
	$ishowall = optional_param('iall', 0, PARAM_INT);		// Show all course
	$modecheck = optional_param('check', 0, PARAM_INT);		// Synchronise enrol/unenrol
	$catid = optional_param('cat', 0, PARAM_INT);		
        
    if ($yid == 0)   {
        $yid = get_current_edyearid();
    }
    
    $PAGE->set_url('/blocks/area/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title('Список дисциплин плана');
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    $PAGE->navbar->add('Рабочие планы', new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add('Рабочие планы (по квалификации)', new moodle_url("deanpegas.php", array()));
    $PAGE->navbar->add("Список дисциплин");
    $PAGE->requires->js('/lib/jquery/js/jquery-1.9.1.js', true);
    $PAGE->requires->js('/blocks/bsu_plan/deantopegas/disciplineslist.js', true);
    
    echo $OUTPUT->header();
    
    $scriptname = "disciplinepegas.php";
    //$scriptname2 = "rupview.php?fid=$fid";
    $strlistfaculties =  listbox_department($scriptname, $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	
   	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
    echo $strlistfaculties;


    if($fid > 0){
        listbox_plan($scriptname."?fid=$fid", $fid, $pid);
        if ($pid > 0)   {
            if($plan = $DB->get_record_select('bsu_plan', "id = $pid", null, 'id, concat (id, ". ", name) as pname, departmentcode, specialityid, profileid, edformid, kvalif, timenorm')) {    
                
                if ($specyal = $DB->get_record_select('bsu_tsspecyal', "idSpecyal = $plan->specialityid", null, 'Specyal  as sname, KodSpecyal as scode')) {
                    $sname = $specyal->sname;
                } else {
                    $sname = 'СПЕЦИАЛЬНОСТЬ НЕ НАЙДЕНА';
                }     
                $plan->sname = $sname; 
                
                echo '<tr> <td align=right>'.get_string('speciality', 'block_bsu_plan').': </td><td>';
                echo '<b>'.$plan->sname.'</b>'; // $plan->scode . '. ' .
                echo '</td></tr>';

                if ($plan->profileid > 0) {
                    echo '<tr> <td align=right>'.get_string('profile', 'block_bsu_plan').': </td><td>';
                    $profile = '-';
                    if (!$profile = $DB->get_field('bsu_ref_profiles', 'name', array('id' => $plan->profileid))) {
                        $profile = '-';
                    }
                    echo '<b>'.$profile.'</b>'; // $plan->scode . '. ' .
                    echo '</td></tr>';
                }

                echo '<tr> <td align=right>'.get_string('edform', 'block_bsu_plan').': </td><td>';
                $plan->edform = $DB->get_field('bsu_tsotdelenie', 'Otdelenie', array('idOtdelenie' => $plan->edformid ));
                echo '<b>'.$plan->edform.'</b>'; // $plan->scode . '. ' .
                echo '</td></tr>';
                
                echo '<tr> <td align=right>'.get_string('kvalif', 'block_bsu_plan').': </td><td>';
                $plan->kvalifname = $DB->get_field('bsu_tskvalifspec', 'Kvalif', array('idKvalif' =>  $plan->kvalif));
                echo '<b>'.$plan->kvalifname .'</b>'; // $plan->scode . '. ' .
                echo '</td></tr>';
                
                $plan->fakultetid = $DB->get_field_select('bsu_ref_department', 'id', "departmentcode = $plan->departmentcode");
                
                $strgroups = get_plan_groups($pid);
                if ($strgroups != '')   {
                    $agroups = explode ('<br>', $strgroups);
                    echo '</td><tr> <td align=right>'.get_string('groups', 'block_bsu_plan').': </td><td>';
                    $strgroups = str_replace('<br>', ', ', $strgroups);
                    echo '<b>'.$strgroups.'</b>'; // $plan->scode . '. ' .
                } else {
                    $agroups = array();
                }
                echo '</table>';
                
                $toprow = array();
                $link = "yid=$yid&fid=$fid&pid=$pid";
                $toprow[] = new tabobject('plan', $scriptname.'?tab=plan&'.$link, 'План');
                $toprow[] = new tabobject('studentsgroup', $scriptname.'?tab=studentsgroup&'.$link, get_string('studentsgroup', 'block_bsu_schedule'));
                $toprow[] = new tabobject('registergroupincourse', $scriptname.'?tab=registergroupincourse&'.$link, 'Регистрация группы в курсах Пегаса');
                $tabs = array($toprow);
                print_tabs($tabs, $tab, NULL, NULL);

                switch($tab)    {
                    case 'plan':
                                $table = table_all_disciplines_pegas($yid, $fid, $plan);
                                if (!empty($table)) {
                                    echo '<form name="courseidlist" method="post" action="setcourseid.php">';
                                    echo '<center><input type="submit" value="Сохранить"</center><br />';
                                    echo '<input type="hidden" name="yid" value="' .  $yid . '">';
                                    echo '<input type="hidden" name="fid" value="' .  $fid . '">';
                                    echo '<input type="hidden" name="pid" value="' .  $pid . '">';
                                    echo '<center>'.html_writer::table($table).'</center>';
                                    echo '<center><input type="submit" value="Сохранить"</center>';
                                    echo '</form>';
                                }
                    break; 
                    
                    case 'studentsgroup':
                                echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
                                listbox_groups_plan($scriptname."?tab=$tab&".$link, $fid, $pid, $gid);
                                echo '</table>';
                                
                                if ($gid > 0)   {
                                    $table = table_studentsgroup($fid, $gid, $plan);
                                    
                                    $options = array('action'=> 'excel',  'fid' => $fid, 'gid' => $gid,  'sesskey' => sesskey());
                                    echo '<br /><br /><center>'.$OUTPUT->single_button(new moodle_url($scriptname.'.php', $options), get_string('downloadexcel'), 'get', $options).'</center><br /><br />';
                                    
                                    echo'<center>'.html_writer::table($table).'</center>';
                                }    
                                
                    break;            

                    case 'registergroupincourse':
                                echo '<table border=1 class="generalbox" cellspacing="0" cellpadding="10" align="center">';
                                listbox_groups_plan($scriptname."?tab=$tab&".$link, $fid, $pid, $gid);
                                echo '</table>';
                                
                                if ($gid > 0)   {
                                    if ($frm = data_submitted())   {
                                        register_group_in_course_pegas($yid, $fid, $pid, $gid, $frm);
                                    }    
                                    
                                	if ($ishowall == 0)  {
                                	   $sql = "SELECT pc.id, pc.courseid, ds.numsemestr as term FROM dean.mdl_bsu_discipline_pegas_course pc
                                             inner join mdl_bsu_discipline d on d.id=pc.disciplineid
                                             inner join mdl_bsu_discipline_semestr ds on d.id=ds.disciplineid
                                             where pc.planid=$pid";
                                		if($coursesincurr =$DB->get_records_sql($sql)) {
                                			foreach($coursesincurr as $cc)   {
                                				if ($cc->courseid != 1) {
                                					$allcourses[$cc->term][] = $DBPEGAS->get_record("course", array('id' => $cc->courseid), 'id, fullname');
                                				}
                                			}
                                		}
                                	} else	{
                                	    // $allcourses = get_records("course", '', '', "fullname");
                                	    $allcourses = array();
                                	    if ($catid)	{
                                	    	$allcourses =  $DBPEGAS->get_records_sql ("SELECT id, fullname  FROM {$CFG->prefix}course
                                											WHERE category=$catid  
                                											ORDER BY fullname");
                                	    }	
                                	}
                        
                                    $OUTPUT->box_start();
                                    print_form_register_group_in_courses($yid, $fid, $pid, $gid, $plan, $tab, $allcourses, $ishowall, $catid);
                                    $OUTPUT->box_end();
                                }    
                    break;            
                                     
                }                
                                  
            }
        }
    }        
    
    echo $OUTPUT->footer();
    
    
function table_all_disciplines_pegas($yid, $fid, $plan, $delemiter = '</br>')
    {
    global $CFG, $DB, $OUTPUT, $USER, $ACCESS_USER, $DBPEGAS;
    
    $step = 2000001; // переменная шага, для пустых инпутов
    
    $pixinfokurs = "<img src=\"$CFG->pixpath/i/info.gif\" alt=\"Вводите каждый курс к дисциплине в новое поле\" title=\"Вводите каждый курс к дисциплине в новое поле. Для добавления 'Поля' используйте ''Плюс'' \">";
    $pixinfodisc = "<img src=\"$CFG->pixpath/i/info.gif\" alt=\"Для добавления материалов к курсу, нажмите на название дисциплины\" title=\"Для добавления материалов к курсу, нажмите на название дисциплины\">";
    
    $planid = $plan->id;
    $table = new html_table();
    $table->head = array (get_string('npp', 'block_bsu_plan'),
                          get_string('cyclename', 'block_bsu_plan'),
                          'Блок', // get_string('identificatordiscipline', 'block_bsu_plan'),
                          get_string('discipline', 'block_bsu_plan') . $pixinfodisc . ' / <br />Курс(ы) "Пегаса"',
                          get_string('term', 'block_bsu_plan'),
                          'Контроль', // get_string('formskontrol', 'block_bsu_plan'),
                          get_string('lection', 'block_bsu_plan'),
                          'Пр.', // get_string('praktika', 'block_bsu_plan'),
                          get_string('lab', 'block_bsu_plan'),
                          get_string('srs', 'block_bsu_plan'),
                          'СР (всего)',
                          'Итого',
                          // 'Кол-во подписанных курсов',                          
                          'ID Курсов'.$pixinfokurs,
                          get_string('actions'));
    $table->align = array ('center', 'left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->width = "80%";
    $table->columnwidth = array (7, 10, 10, 60,  10, 10, 16, 15, 15, 15,15,15);

   	$table->titlesrows = array(30, 30);
    $table->titles = array();
    $table->titles[] = get_string('curriculum', 'block_bsu_plan') . ' ' . $plan->pname; 
	$table->titles[] = get_string('speciality', 'block_bsu_plan') . ' ' . $plan->sname;
   
    $sql = "SELECT d.id as did, d.disciplinenameid, n.name as nname, d.cyclename, d.identificatordiscipline, d.sr, 
                   d.semestrexamen, d.semestrzachet, d.semestrkursovik, d.semestrdiffzach, d.semestrkp, 
                   d.mustlearning, p.id as pid
                FROM {bsu_discipline} d 
                INNER JOIN {bsu_plan} p ON p.id=d.planid
                INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                WHERE p.id=$planid
                ORDER BY 3";
                // ORDER BY d.identificatordiscipline, d.cyclename";

                  
    if ($datas = $DB->get_records_sql($sql))  {     //получили данные по дисциплинам
        
        $datas1 = $datas;
        
        for ($k=1; $k<=1; $k++) {
            /*
            if ($k==1) $datas1 = $basedisc;
            else $datas1 = $vardisc;
            */
        
            $i = 1;
            foreach($datas1 as $data) {
                $disname =  "<a href=\"deanpegasumk.php?pid={$plan->id}&did=$data->did&dname=$data->nname\" title=\"Прикрепить УМК к дисциплине\">" . $data->nname . '</a> / <br />';
                
                //$strlinkupdate = '';
                $strterms = $l = $p = $lab = $ksr = $srs = $fk = $m = array();
                if ($semestrs = $DB->get_records_select('bsu_discipline_semestr', "disciplineid = $data->did", null, 'numsemestr')) {
                    foreach ($semestrs as $semestr) {
                        $strterms[] .= $semestr->numsemestr;
                        // id, disciplineid, numsemestr, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz, prlecinweek, prlabinweek, prprinweek, przet
                        $l[] = $semestr->lection;
                        $p[] = $semestr->praktika;
                        $lab[] = $semestr->lab;
                        $ksr[] = $semestr->ksr;
                        $srs[] = $semestr->srs;
                        $m[] = $semestr->srs; 
                        $allhours = $semestr->lection + $semestr->praktika + $semestr->lab + $semestr->ksr + $semestr->srs;  
                        $fk[] = get_formskontrol($data->semestrexamen, $data->semestrzachet, $data->semestrkursovik, $data->semestrdiffzach, $semestr->numsemestr, $data->semestrkp, $allhours);
                        // 1834 - Логвиненко
                        // 61823 - Мураховская
                        // 54087 - Богачева
                    }
                }  else {
                    //$link = "pid=$planid&did={$data->did}";
                    //$strlinkupdate .= "<a href='disciplines.php?action=deldid&{$link}'><img class='icon' title='".get_string('delete')."' src='".$OUTPUT->pix_url('i/cross_red_big')."'></a>";
                }
                
               if (!empty($data->semestrexamen)) {
                    $arr1 = str_split($data->semestrexamen);
                    foreach ($arr1 as  $arr)    {
                        $arr = hexdec($arr);
                        if (!in_array($arr, $strterms)) {
                            
                            $strterms[] .= $arr;
                            // id, disciplineid, numsemestr, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz, prlecinweek, prlabinweek, prprinweek, przet
                            $l[] = 0;
                            $p[] = 0;
                            $lab[] = 0;
                            $ksr[] = 0;
                            $srs[] = 0;
                            $m[] = 0; 
                            $allhours = 0;  
                            $fk[] = 'экз.';
                        }    
                    }
                }            
                        

               if (!empty($data->semestrzachet)) {
                    $arr1 = str_split($data->semestrzachet);
                    foreach ($arr1 as  $arr)    {
                        $arr = hexdec($arr);
                        if (!in_array($arr, $strterms)) {
                            
                            $strterms[] .= $arr;
                            // id, disciplineid, numsemestr, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz, prlecinweek, prlabinweek, prprinweek, przet
                            $l[] = 0;
                            $p[] = 0;
                            $lab[] = 0;
                            $ksr[] = 0;
                            $srs[] = 0;
                            $m[] = 0; 
                            $allhours = 0;  
                            $fk[] = 'зач.';
                        }    
                    }
                }            
               
                if (!empty($data->semestrkursovik) && !in_array($data->semestrkursovik, $strterms)) {
                        //print_object($strterms);
                        $strterms[] .= $data->semestrkursovik;
                        // id, disciplineid, numsemestr, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz, prlecinweek, prlabinweek, prprinweek, przet
                        $l[] = 0;
                        $p[] = 0;
                        $lab[] = 0;
                        $ksr[] = 0;
                        $srs[] = 0;
                        $m[] = 0; 
                        $allhours = 0;  
                        $fk[] = 'курс.раб.';
                }

               $list_umks = $DB->get_records("bsu_discipline_pegas_course", array('planid' => $plan->id, 'disciplineid' => $data->did));

               if($list_umks) { //  Если в базе есть хотя бы один курс
                    //$pix = "<img src=\"$CFG->pixpath/s/smiley.gif\" alt=\"УМК прикреплены\" title=\"УМК прикреплены\">";
                    $coursenames = array();
                    $pix = $value = "";
                    $pix .= "<div id=\"$data->did\">";
                    foreach($list_umks as $dp) {
                        if ($coursename = $DBPEGAS->get_field_select('course', 'fullname', "id=$dp->courseid"))   {
                            $coursenames[] = '<b><a href="http://pegas.bsu.edu.ru/course/view.php?id=' . $dp->courseid . '">' . $coursename . '</a></b>'; 
                        }
                        $inputname = $dp->id . "_" . "kurs_" . $data->did ;
                        $pix .= "<input type=\"text\" name=\"$inputname\" value=\"{$dp->courseid}\" size='5' title=\"Введите ID курса\"><br><br>";
                    }
                    $disname .= implode (',<br />' , $coursenames);  
                    $pix .= "</div>";
                    $pix .= "<span id='add'><a href='#' data-did=" . $data->did . "><img src=\"$CFG->pixpath/i/add.png\" alt=\"Добавить поле ввода\" title=\"Добавить поле ввода\"></a></span><hr>";
                    //$pix .= "<img src=\"$CFG->pixpath/i/add.gif\" data-did=" . $data->did . " alt=\"Добавить поле ввода\" title=\"Добавить поле ввода\">";
                    //$pix .= "<span id='del'><a href='#' data-did=" . $data->did . ">[-]</a></span>";
               } else { //  если в базе нет курса
                    $inputname = $step . "_kurs_" . $data->did ;
                    $step++;
                    $pix = "<div id=\"$data->did\"><input type=\"text\" size='5' name=\"$inputname\" value=\"\" ><br><br></div>";
                    $pix .= "<span id='add'><a href='#' data-did=" . $data->did . "><img src=\"$CFG->pixpath/i/add.png\" alt=\"Добавить поле ввода\" title=\"Добавить поле ввода\"></a></span><hr>";
                    //$pix .= "<span id='del'><a href='#' data-did=" . $data->did . ">[-]</a></span>";
                    $disname .= '-';                   
               }
               $link = "yid=$yid&fid=$fid&pid=$planid&did={$data->did}";
               $strlinkupdate = "<a href='addiscipline.php?{$link}'><img class='icon' title='".get_string('edit')."' src='".$OUTPUT->pix_url('i/edit')."'></a>";

             
               $table->data[] = array($i, $data->cyclename, $data->identificatordiscipline, 
                                       $disname, 
                                       implode ($delemiter, $strterms), 
                                       implode ($delemiter,$fk), 
                                       implode ($delemiter,$l), 
                                       implode ($delemiter,$p), 
                                       implode ($delemiter,$lab), 
                                       implode ($delemiter,$srs),
                                       $data->sr, 
                                       $data->mustlearning,
                                       $pix,
                                       $strlinkupdate);
                $i++;
            }                 
        }
    }

    return $table;
}


function print_form_register_group_in_courses($yid, $fid, $pid, $gid, $plan, $tab, $allcourses, $ishowall, $catid) 
{
    global $DB, $OUTPUT, $DBPEGAS; 
    
    // print_object($allcourses);
    
    $academygroupname = $DB->get_field_select('bsu_ref_groups', 'name', "id=$gid");
    
    $enrolcourses = array();
	$idcourses    = array();
	$numstudents  = array();
	$numgroups    = array();
	$dean_amembers = array();
    if ($mgroups = $DBPEGAS->get_records_select("groups", "name = '$academygroupname'"))  {
    	$i=0;
		foreach($mgroups as $mgroup)  {
			$enrolcourses[$i] =  $DBPEGAS->get_record_select("course", "id = $mgroup->courseid", null, 'id, fullname');
			$idcourses[$i] = $mgroup->courseid;
			if ($memgr = $DBPEGAS->get_records_select('groups_members', "groupid = $mgroup->id", null, '', 'userid'))	{
				$numstudents[$i] = count($memgr);
			} else {
				$numstudents[$i] = 0;
			}
            $sql = "SELECT courseid, name FROM mdl_groups
                    WHERE courseid = {$mgroup->courseid} AND name = '{$mgroup->name}'";
			if ($agroupsw = $DBPEGAS->get_records_sql($sql))   {
				$numgroups[$i] = count($agroupsw);
			} else {
				$numgroups[$i] = 0;
			}

			if ($damem = $DB->get_records_select('bsu_group_members', "groupid = $gid")) 	{
				$dean_amembers[$i] = count($damem);
			} else {
				$dean_amembers[$i] = 0;
			}

			$i++;
		}
	}

    
   // echo '<hr>'. $ishowall . '<hr>';

   $sesskey = !empty($USER->id) ? $USER->sesskey : '';
   ?>

    <form name="studentform" id="studentform" method="post" action="disciplinepegas.php">
    <input type="hidden" name="fid" value="<?php echo $fid ?>" />
    <input type="hidden" name="pid" value="<?php echo $pid ?>" />
    <input type="hidden" name="gid" value="<?php echo $gid ?>" />
    <input type="hidden" name="tab" value="<?php echo $tab ?>" />    
    <input type="hidden" name="iall" value="<?php echo $ishowall ?>" />
    <input type="hidden" name="sesskey" value="<?php echo $sesskey ?>" />
    <table align="center" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td valign="top" align="center">
          <?php
              echo get_string('listofenrollcourse', 'block_dean') . ': ' . count($enrolcourses) . '<br>';
          ?>
          <select name="removeselect[]" size="10" id="removeselect" multiple
                  onFocus="document.studentform.add.disabled=true;
                           document.studentform.remove.disabled=false;
                           document.studentform.addselect.selectedIndex=-1;" />
          <?php
	    	  $i=0;
              foreach ($enrolcourses as $ec) {
                  if ($dean_amembers[$i] !=  $numstudents[$i])	{
	                  $strfn = '!!!!! (' . $numstudents[$i] . ' - ' . $dean_amembers[$i] . ') ' . mb_substr($ec->fullname, 0, MAX_SYMBOLS_LISTBOX, "UTF-8");
                  } else {
	                  // $strfn = '(' . $numstudents[$i] . ' - ' . $dean_amembers[$i] . ')  ' . substr($ec->fullname,0, 150) ;
					  $strfn = mb_substr($ec->fullname, 0, MAX_SYMBOLS_LISTBOX, "UTF-8");
	              }

	              if ($numgroups[$i] > 1) {
	              		$strfn .=  ' (' . $numgroups[$i] . ')!!!!!' ;
	              }

                  echo "<option value=\"$ec->id\">$strfn</option>\n";
               	  $i++;
              }
          ?>

          </select>
      </td>
	</tr>
	<tr>
      <td valign="top" align="center">
        <input name="add" type="submit" id="add" value="&uarr;" />
        <input name="remove" type="submit" id="remove" value="&darr;" />
      </td>
	</tr>
    <tr>
      <td valign="top" align="center">
          <?php
           	  if ($ishowall == 0)  {
          ?>
          <select name="addselect[]" size="20" id="addselect" multiple
                  onFocus="document.studentform.add.disabled=false;
                           document.studentform.remove.disabled=true;
                           document.studentform.removeselect.selectedIndex=-1;">
          <?php
				for ($term=1; $term<=12; $term++)  {
				    echo "<optgroup label=\"". get_string("term","block_dean"). " $term\">\n";
                    if (isset($allcourses[$term]))   {
    		        	foreach ($allcourses[$term] as $ec) {
    						if (!in_array($ec->id, $idcourses))  {
    		                  $strfn = mb_substr($ec->fullname, 0, MAX_SYMBOLS_LISTBOX, "UTF-8");
    	                	  echo "<option value=\"$ec->id\">$strfn</option>\n";
    	                	}
    		            }
                    }    
					echo "</optgroup>\n";
				}
		  		 echo '</select>';
			  } else {
			  		
			        ?>
			          <table align="center" border="0">
    					<tr>
      						<td valign="top" align="center">
 								<?php echo get_string('categories').'<br>'; ?>     						
      						   <select name="cat" size="20" id="cat">
				         	 	<?php	   
    									$categories = $DBPEGAS->get_records_sql ("SELECT id, name  FROM mdl_course_categories ORDER BY name");;
    									foreach ($categories as $category) {
    										$strfn = mb_substr($category->name, 0, 30, "UTF-8");
    										echo "<option value=\"$category->id\">$strfn</option>\n";
    									}	
    							?>		
							 </select>
							 </td>
					         <td valign="middle" align="center">
        					  <input name="viewcat" type="submit" id="viewcat" value=">>" />
      						</td>
							 <td>
							 	<?php echo get_string('listofallcourse','block_dean') . ': ' . count($allcourses) .'<br>'; ?> 
							  <select name="addselect[]" size="20" id="addselect" multiple
				                  onFocus="document.studentform.add.disabled=false;
				                           document.studentform.remove.disabled=true;
				                           document.studentform.removeselect.selectedIndex=-1;">
			        <?php
					if ($catid)	{
			        	foreach ($allcourses as $ec) {
							if (!in_array($ec->id, $idcourses))  {
			                  $strfn = mb_substr($ec->fullname, 0, 100, "UTF-8");
		                	  echo "<option value=\"$ec->id\">$strfn</option>\n";
	
		                	}
			            }
			        }    
		            echo '</select></td></tr></table>';
			   }

          ?>
         
       </td>
    </tr>
    </table>
    </form>

    <table align="center" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td valign="top" align="center">
          <?php
        	 $options = array('yid' => $yid, 'fid' => $fid, 'pid' => $pid, 'gid' => $gid, 'tab' => $tab);
    		 if ($ishowall == 0) {
    		     $options['iall'] = 1;
    	     	 $textbutton = get_string('showallcourse','block_dean');
    		 } else  {
    		     $options['iall'] = 0;
    	     	 $textbutton = get_string('showcoursecurr','block_dean');
    		 }
             // print_object($options);
             echo $OUTPUT->single_button(new moodle_url('disciplinepegas.php', $options), $textbutton);  
          ?>
      </td>
      <td valign="top" align="center">
          <?php
        	 $options = array('yid' => $yid, 'fid' => $fid, 'pid' => $pid, 'gid' => $gid, 'tab' => $tab, 'iall' => $ishowall, 'check' => 1);
             echo $OUTPUT->single_button(new moodle_url('disciplinepegas.php', $options), 'Синхронизировать подписку');
          ?>
      </td>
    </tr>
    </table>
<?php
}


function register_group_in_course_pegas($yid, $fid, $pid, $gid, $frm)
{
    global $DB, $DBPEGAS;
    
    if (!empty($frm->add) and !empty($frm->addselect) and confirm_sesskey()) {

	    $academystudents = get_records('dean_academygroups_members', 'academygroupid', $academygroup->id);
        // print_r($academystudents); echo '<hr>';

        foreach ($frm->addselect as $addcourse) {
			/// Create a new group
			// debug
			// $crs = get_record('course', 'id', $addcourse);
			// print '<br>='.$crs->fullname.':'.$crs->fullname. '<br>';
			//
		    if ($newgrp = get_record('groups', 'name', $academygroup->name, 'courseid', $addcourse))	{
		    	notify ('Группа {$academygroup->name} уже подписана на курс с идентификатором $addcourse.', 'black');
		    	continue;
		        // $newgrpid = $newgrp->id;
                // delete_records("groups_members", "groupid", $newgrp->id);
		    }  else {
       	        $newgroup->name = $academygroup->name;
           	    $newgroup->courseid = $addcourse;
				$newgroup->description = '';
				$newgroup->password = '';
				$newgroup->theme = '';
               	$newgroup->lang = current_language();
                $newgroup->timecreated = time();
   	            if (!$newgrpid=insert_record("groups", $newgroup)) {
       	            error("Could not insert the new group '$newgroup->name'");
           	    } else {
           	    	// notify("the new group '$newgroup->name'");
   	            	add_to_log(1, 'dean', 'new moodle group registered', "blocks/dean/groups/registergroup.php?mode=new&amp;fid=$fid&amp;sid=$sid&amp;cid=$cid", $USER->lastname.' '.$USER->firstname);
           	    }
           	}

			// $newgrp = get_record("groups", "name", $academygroup->name, "courseid", $addcourse);
		    if ($academystudents)  {
		   	   foreach ($academystudents as $astud)	  {
                /// Enrol student
			     if ($usr = get_record('user', 'id', $astud->userid))	{
				    // print '-'.$usr->id.':'.$usr->lastname.' '. $usr->firstname. '<br>';
				    if ($usr->deleted != 1)	 {
						if (enrol_student_dean($astud->userid, $addcourse))  {

		                	  /// Delete duplicated students in the other group
		                      $strsql = "SELECT g.id, g.name, g.courseid, m.userid
										   FROM mdl_groups as g INNER JOIN mdl_groups_members as m ON g.id = m.groupid
										   WHERE (g.courseid=$addcourse) AND (m.userid={$astud->userid})";
						   	  if ($duplgroups = get_records_sql($strsql))	{
									foreach ($duplgroups as $duplgroup)	 {
				 	                	 delete_records('groups_members', 'groupid', $duplgroup->id, 'userid', $astud->userid);
									}
		 				      }

		                	 /// Add people to a group
		                	 if (!$newmemberwas = get_record('groups_members', 'groupid', $newgrpid, 'userid', $astud->userid))	 {
							     $newmember->groupid = $newgrpid;
			    	             $newmember->userid = $astud->userid;
			        	         $newmember->timeadded = time();
			            	     if (!insert_record('groups_members', $newmember)) {
			                	    notify("Error occurred while adding user $astud->userid to group $academygroup->name");
				                 }
				             }

  			            } else {
		                      error("Could not add student with id $astud->userid to the course $addcourse!");
		                }
  			        } else {
  			        	delete_records('dean_academygroups_members', 'userid', $astud->userid, 'academygroupid', $academygroup->id);
  			        }
  			     } else {
		        	delete_records('dean_academygroups_members', 'userid', $astud->userid, 'academygroupid', $academygroup->id);
  			     }
               }
			}
        }
    } else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {

        $academystudents = get_records('dean_academygroups_members', 'academygroupid', $academygroup->id);

        foreach ($frm->removeselect as $removecourse) {
 		    if ($academystudents) 	{
 		   		foreach ($academystudents as $astud)	  {
	                /// UnEnrol student
					unenrol_student_dean ($astud->userid, $removecourse);

					// delete_records('dean_academygroups_members', 'userid', $removestudent);
					// add_to_log(1, 'dean', 'one student deleted from academygroup', "/blocks/dean/gruppa/changelistgroup.php?mode=4&amp;cid=$cid&amp;sid=$sid&amp;fid=$fid&amp;gid=$gid", $USER->lastname.' '.$USER->firstname);
				}
			}
		    if ($delgrp = get_record("groups", "name", $academygroup->name, "courseid", $removecourse))	{
                delete_records("groups", "id", $delgrp->id);
                delete_records("groups_members", "groupid", $delgrp->id);
            }
		}
    } else if (!empty($frm->showall)) {
        unset($frm->searchtext);
        $frm->previoussearch = 0;
    }

}
?>