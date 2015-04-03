<?PHP // $Id: searchstudent.php,v 1.7 2011/12/19 12:35:44 shtifanov Exp $

    require_once("../../../config.php");
    require_once("../../bsu_schedule/lib_schedule.php");
    require_once("../lib_plan.php");

    require_login();

    $namestudent = optional_param('namestudent', '', PARAM_TEXT);		// student lastname
    $loginstudent = optional_param('loginstudent', '', PARAM_TEXT);		// student login
   	$action = optional_param('action', '', PARAM_TEXT);
    $uid = optional_param('uid', 0, PARAM_INT);
    
    $yid = get_current_edyearid();

	$strstudent  = get_string('teachers');
	$strstudents = get_string('teachers');
    $strsearch = get_string("search");
    $strsearchresults  = get_string("searchresults");
    $searchtext1 = '';
    $searchtext2 = '';
    
	$strtitle = get_string('pluginname', 'block_bsu_plan');
    $strscript = get_string('searchprepod', 'block_bsu_plan');
   
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_title($strscript);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    // $PAGE->set_cacheable(true);
    //$PAGE->set_button('$nbsp;');
	$PAGE->navbar->add($strtitle, new moodle_url("$CFG->BSU_PLAN/index.php", array()));
	$PAGE->navbar->add($strscript);
    echo $OUTPUT->header();

    $fid = 0;
    $strlistfaculties =  listbox_department('', $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

    $admin_is = is_siteadmin();
    /*
    if (!$admin_is) {
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
    }            
*/
    if ($action == 'sched') {
        $user = $DB->get_record_sql("SELECT id, lastname, firstname FROM {user} WHERE id=$uid");
        echo '<h2 align=center>Расписание преподавателя: ' . fullname($user) . '</h2>'; 
        $table = loaded_teacher($uid, 1);
        if ($table != '')   {
            echo '<center>'.html_writer::table($table).'</center>';
        } else {
            echo $OUTPUT->notification('Расписание не найдено.');
        }           
    } else if ($action == 'setid') {
        if ($user = $DB->get_record_sql("SELECT id, lastname, firstname FROM {user} WHERE id=$uid"))    {
            // print_object($user);
            $fullname = fullname($user);
            $fullname = trim ($fullname);
            $timemodified = time();
            $sql = "UPDATE mdl_bsu_schedule_mask set teacherid = $uid, timemodified = $timemodified  where teacher like '$fullname'";
            $DB->Execute($sql);
            $sql = "UPDATE mdl_bsu_schedule set teacherid = $uid, timemodified = $timemodified  where teacher like '$fullname'";
            // echo $sql . '<br>';
            $DB->Execute($sql);
        }
        
    } else if ($action == 'create') {
        $user = new stdClass();
	    list ($user->lastname, $user->firstname, $user->secondname) = explode (' ', $namestudent);
        $user->firstname = $user->firstname . ' ' . $user->secondname;
		$idmax = $DB->get_record_sql("SELECT max(id) as id FROM {user}");
		$idmax = $idmax->id + 1;
		$user->username = 'teacher_' . translit_russian_utf8($user->lastname) .  rand(10, 99);
        $user->email = $user->username . '@bsu.edu.ru'; 
        $user->password = hash_internal_user_password('1234567');
		$user->city = 'Белгород';
		$user->mnethostid = $CFG->mnet_localhost_id;
		$user->timemodified = time();
		$user->country = 'RU';
		$user->lang = 'ru';
		$user->confirmed = 1;
		$user->description = '-';
        // print_object($user);
        
		if (!$uid = $DB->insert_record('user', $user))    {
		      print_object($user);
		      print_error('Error insert  user.');
		} else {
            $sql = "UPDATE mdl_bsu_schedule_mask set teacherid = $uid  where teacher like '$namestudent'";
            $DB->Execute($sql);
            $sql = "UPDATE mdl_bsu_schedule set teacherid = $uid  where teacher like '$namestudent'";
            $DB->Execute($sql);
		}
        $namestudent = $user->lastname; 
    }
 /*               
$table = loaded_group($gid);
$table = loaded_room($idr);
*/

    if (isset($action) && !empty($action)) 	{

	    if (isset($namestudent) && !empty($namestudent)) 	{
		     $searchtext1 = $namestudent;
             
	         $studentsql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.auth, u.imagealt, 
								  u.city, u.country, u.lastlogin, u.picture, u.lang, u.timezone, u.id_fl,
	                              u.lastaccess, m.subdepartmentid, m.positionid, m.staffid
	                       FROM mdl_user u
	                       INNER JOIN mdl_bsu_teacher_subdepartment m ON u.id = m.teacherid
	                       WHERE u.lastname LIKE '$namestudent%'  AND deleted = 0 
	                       ORDER BY u.lastname, u.firstname"; // AND  m.teacherid > 0
            
            /*
	         $studentsql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.auth, u.imagealt, 
								  u.city, u.country, u.lastlogin, u.picture, u.lang, u.timezone,
	                              u.lastaccess
	                       FROM mdl_user u
	                       WHERE u.lastname LIKE '$namestudent%'  AND deleted = 0 
	                       ORDER BY u.lastname, u.firstname"; // AND  m.teacherid > 0
            */
	    } else if (isset($loginstudent) && !empty($loginstudent)) 	{
	       
			 $searchtext2 = $loginstudent;

	         $studentsql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.auth, u.imagealt,
								  u.city, u.country, u.lastlogin, u.picture, u.lang, u.timezone, u.id_fl,
	                              u.lastaccess, m.academygroupid 
	                       FROM {$CFG->prefix}user u
	                       LEFT JOIN {$CFG->prefix}dean_academygroups_members m ON m.userid = u.id
	                       WHERE u.username LIKE '$loginstudent%'  AND deleted = 0
	                       ORDER BY u.username";
	    }
        
        // echo $studentsql;
        $flag = false;
        $table = NULL;
        if ($students = $DB->get_records_sql($studentsql)) {
            $table = table_students($students, $yid);
            $flag = true; 
      		// print_table($table);           
		}  
        
        if ($admin_is)	{
            $sql = "SELECT id, teacherid, teacher FROM mdl_bsu_schedule_mask
                    where teacher like '$namestudent%'
                    order by teacher";
            if ($students = $DB->get_records_sql($sql))  {
                $table = table_schedule($students, $table);
                $flag = true; 
            }        
        }
        
        if ($flag) {
            echo '<center>'.html_writer::table($table).'</center>';
		} else {
			notify('Преподаватель(и) не найдены.');
			echo '<hr>';
		}
	}

	echo $OUTPUT->heading($strscript, 2);

	echo $OUTPUT->heading('Поиск по фамилии', 3);
    echo $OUTPUT->box_start();
	echo '<div align=center><form name="studentform1" id="studentform1" method="post" action="searchprepod.php?action=lastname">'.
		 get_string('lastname'). '&nbsp&nbsp'.
		 '<input type="text" name="namestudent" size="10" value="' . $searchtext1. '" />'.
	     '<input name="search" id="search" type="submit" value="' . $strsearch . '" />'.
		 '</form></div>';
    echo $OUTPUT->box_end();

	echo $OUTPUT->heading('Поиск по логину', 3);
    echo $OUTPUT->box_start();
	echo '<div align=center><form name="studentform2" id="studentform2" method="post" action="searchstudent.php?action=login">'.
		 get_string('username'). '&nbsp&nbsp'.
		 '<input type="text" name="loginstudent" size="10" value="' . $searchtext2. '" />'.
	     '<input name="search" id="search" type="submit" value="' . $strsearch . '" />'.
		 '</form></div>';
    echo $OUTPUT->box_end();

    echo '<p></p>';
    
    echo $OUTPUT->footer();



function table_students($students, $yid)
{
    global $CFG, $USER, $admin_is, $DB, $OUTPUT, $namestudent;

    $edyear = $DB->get_records_select_menu('bsu_ref_edyear', '', null, 'id', 'id, edyear');
    
    $sql = "SELECT sd.id as idSubdepartment, sd.Name as SubDepartment, d.id as idDepartment, d.name as Department 
          FROM mdl_bsu_vw_ref_subdepartments sd
          inner join mdl_bsu_ref_department d on sd.DepartmentCode=d.DepartmentCode";
    $subdeps = $DB->get_records_sql($sql);      
    $asubdeps = array();
    foreach($subdeps as $subdep)    {
        // $asubdeps[$subdep->idSubdepartment] = new stdClass();
        $asubdeps[$subdep->idsubdepartment] = $subdep->subdepartment;     
    }
    $positionlong = $DB->get_records_menu('bsu_staffpositions', null, '', 'id, name');
    $positionshort = $DB->get_records_menu('bsu_staffpositions', null, '', 'id, ir_name');

    $table = new html_table();
	$table->head  = array ('Фото', get_string('fullname'), get_string('username'). '/GUID', 
                            get_string('email'), 'Должность', 'Кафедра', get_string('actions'));
	$table->align = array ("center", "left", "left", "left", "left", "left", "center");


    foreach ($students as $student) {
        if (is_numeric($student->username)) continue;
        $strlinkupdate = $linkcharge = '';
        $strfullname = "<a href=\"$CFG->wwwroot/user/editadvanced.php?id=$student->id&course=1\">" . fullname($student) . '</a>';
 
        if ($teacher = $DB->get_records_select('bsu_teacher_subdepartment', "yearid=$yid and teacherid=$student->id"))   {
            $lastname = urlencode ($student->lastname);
    		$title = 'Показать расписание преподавателя';            
            $strlinkupdate .= "<a title=\"$title\" href=\"searchprepod.php?action=sched&uid={$student->id}&sesskey=$USER->sesskey&namestudent=$lastname\">"; 
    		$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/report.gif\" alt=\"$title\" /></a>";
        }
        
	    if (isset($asubdeps[$student->subdepartmentid])) {
            $sn1 = $positionshort[$student->positionid];
            $sn2 = $asubdeps[$student->subdepartmentid];
        } else {
            $sn1 = $sn2 = '-';
        }
        
        if ($tsubdeps = $DB->get_records_select('bsu_teacher_subdepartment', "teacherid=$student->id"))   {
            $sn2 = '';
            foreach ($tsubdeps as $tsubdep) {
                if (isset($asubdeps[$tsubdep->subdepartmentid]))    {
                    $y = $edyear[$tsubdep->yearid];
                    $sn2 .= $asubdeps[$tsubdep->subdepartmentid] . " ($y уч.год) <br />";
                    if ($yid == $tsubdep->yearid)   {
                        $sesskey =sesskey();
                        $linkcharge = $CFG->wwwroot . "/blocks/bsu_charge/teachercharge.php?sesskey=$sesskey&tab=1&yid=$yid";
                        $t = reset($teacher);
                        $depcode = $DB->get_field_select('bsu_vw_ref_subdepartments', 'departmentcode', "id=$tsubdep->subdepartmentid");
                        $linkcharge .= "&fid={$depcode}&sid={$tsubdep->subdepartmentid}&tpid={$t->teacherid}_{$t->positionid}";
                    }
                }        
            }
        }    
        
        if ($linkcharge != '')  {
            $strlinkupdate .= "<a href=\"$linkcharge\" target=\"_blank\" title=\"Посмотреть нагрузку преподавателя\"><img  src='".$OUTPUT->pix_url('i/charge')."'></a>";
        }    

       
        $guid = 'GUID:';
        if (!empty($student->id_fl))    {
            $guid .= $student->id_fl;
        } else {
            $guid .= ' not found';
        }

	    if ($admin_is)	{
            /*	       
    		$title = 'Установить teacherid в таблицах bsu_schedule_mask и bsu_schedule для данного Ф.И.О.';            
            $strlinkupdate .= "<a title=\"$title\" href=\"searchprepod.php?action=setid&uid={$student->id}&sesskey=$USER->sesskey&namestudent=$lastname\">"; 
    		$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/tick_green_big.gif\" alt=\"$title\" /></a>&nbsp;";
            */

			$title = 'Удалить учетную запись';
            $strlinkupdate .= "<a title=\"$title\" href=\"$CFG->wwwroot/admin/user.php?sort=name&delete={$student->id}&amp;sesskey=$USER->sesskey\">"; 
			$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/cross_red_big.gif\" alt=\"$title\" /></a>";
        }


        $table->data[] = array ($OUTPUT->user_picture($student, array('courseid'=>1)),
						    $strfullname,
                            "<strong>$student->username</strong> ($student->auth)".
                            "<br /><small>$guid</small>",
                            $student->email,
                            $sn1, $sn2,
                            $strlinkupdate);
    }
    
    return $table;
}    



function table_schedule($students, $table)
{
    global $CFG, $USER, $admin_is, $DB, $OUTPUT, $namestudent;

    if ($table == NULL) {
       $table = new html_table();
	   $table->head  = array ('foto', get_string('fullname'), get_string('username'), get_string('email'), 'Должность', 'Кафедра', get_string('actions'));
	   $table->align = array ("center", "left", "left", "left", "left", "center", "center");
    }       

    foreach ($students as $student) {
        $strlinkupdate = '';
        if ($admin_is)	{
    		$title = 'Создать учетную карточку преподавателя';            
            $strlinkupdate .= "<a title=\"$title\" href=\"searchprepod.php?action=create&sesskey=$USER->sesskey&namestudent=$student->teacher\">"; 
    		$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/user.gif\" alt=\"$title\" /></a>&nbsp;";
        }    

        $table->data[] = array ('',	$student->teacher, $student->teacherid, '', '', '', $strlinkupdate);
    }
    
    return $table;
}    


function translit_russian_utf8($input)
{
  $arrRus = array('а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м',
                  'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ь',
                  'ы', 'ъ', 'э', 'ю', 'я',
                  'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М',
                  'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ь',
                  'Ы', 'Ъ', 'Э', 'Ю', 'Я');
  $arrEng = array('a', 'b', 'v', 'g', 'd', 'e', 'jo', 'zh', 'z', 'i', 'y', 'k', 'l', 'm',
                  'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'c', 'ch', 'sh', 'sch', '',
                  'y', '', 'e', 'ju', 'ja',
                  'A', 'B', 'V', 'G', 'D', 'E', 'JO', 'ZH', 'Z', 'I', 'Y', 'K', 'L', 'M',
                  'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'KH', 'C', 'CH', 'SH', 'SCH', '',
                  'Y', '', 'E', 'JU', 'JA');
  return str_replace($arrRus, $arrEng, $input);
}


?>