<?php // $Id: disciplines.php,v 1.8 2012/10/20 12:29:28 shtifanov Exp $

    require_once ("../../../config.php");
    require_once("../lib_plan.php");
    require_once("../../bsu_charge/lib_charge.php");
    require_once("../../bsu_charge/lib_charge_spec.php");

    $yid = required_param('yid', PARAM_INT);			// ed yearid    
    $fid = required_param('fid', PARAM_INT); // faculty id
    $pid = required_param('pid', PARAM_INT); // plan id
    $did = required_param('did', PARAM_INT); // discipline  id
    $planterm = required_param('term', PARAM_INT); // number term
    $plantab = optional_param('plantab', 'plan', PARAM_TEXT);        
    
    $PAGE->requires->js('/blocks/bsu_plan/curriculums/editd.js', true);
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    
    $strtitle = get_string('pluginname', 'block_bsu_plan');
    $strtitle2 = get_string('rup_view_edit', 'block_bsu_plan');
    $strtitle3 = get_string('disciplines', 'block_bsu_plan');
    $strtitle4 = "Редактирование дисциплины";
    
    $PAGE->set_title($strtitle4);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->navbar->add($strtitle, new moodle_url("{$CFG->BSU_PLAN}/index.php", array()));
    $PAGE->navbar->add($strtitle2, new moodle_url("{$CFG->BSU_PLAN}/curriculums/curriculums.php", array('fid' => $fid)));
    $PAGE->navbar->add($strtitle3, new moodle_url("disciplines.php", array('yid' => $yid, 'fid' => $fid, 'pid' => $pid, 'term' => $planterm, 'tab' => $plantab )));
    $PAGE->navbar->add($strtitle4);
    
    echo $OUTPUT->header();

    $scriptname = "editd.php";
    $strlistfaculties =  listbox_department($scriptname, $fid);                        
	if (!$strlistfaculties)   { 
        notice(get_string('permission', 'block_bsu_plan'), '../index.php');
	}	

   
    /***************************************Если данные формы были отправлены***********************************/
    if ($frm = data_submitted()) {
    
        // print_object($frm);
        
        if (isset($frm->button2))    {        
            $redirlink = "disciplines.php?yid=$yid&pid=$pid&fid=$fid&term=$planterm&tab=$plantab";
            redirect($redirlink, '', 0);
        }    
    
    
        if ($frm->dname_name != $frm->dnname)   {
            change_disciplinenameid_in_discipline($frm);
        }

        $str_semestrexamen = $str_semestrzachet = $str_semestrkursovik = $str_semestrdiffzach = ''; 
        $str_semestrkp = $str_semestrref = $str_semestrkontr  = '';
    
        $cur_sem = 0;
    
        $obj1 = new stdClass(); //bsu_discipline
    
        for ($i = 1; $i <= $frm->maxsem; $i++) {
    
            /********************************* Обновление таблицы bsu_discipline *********************************/
    
            $kontr_ekz = "kontr_ekz_{$i}";
            if (isset($frm->$kontr_ekz)) {
                $str_semestrexamen .= strtoupper(dechex($i));
            }
    
            $kontr_zach = "kontr_zach_{$i}";
            if (isset($frm->$kontr_zach)) {
                $str_semestrzachet .= strtoupper(dechex($i));
            }
            $kontr_kr = "kontr_kr_{$i}";
            if (isset($frm->$kontr_kr)) {
                $str_semestrkursovik .= strtoupper(dechex($i));
            }
            $kontr_diffzach = "kontr_diffzach_{$i}";
            if (isset($frm->$kontr_diffzach)) {
                $str_semestrdiffzach .= strtoupper(dechex($i));
            }
            $kontr_kp = "kontr_kp_{$i}";
            if (isset($frm->$kontr_kp)) {
                $str_semestrkp .= strtoupper(dechex($i));
            }

            $kontr_kp = "kontr_ref_{$i}";
            if (isset($frm->$kontr_kp)) {
                $str_semestrref .= strtoupper(dechex($i));
            }

            $kontr_kp = "kontr_kontr_{$i}";
            if (isset($frm->$kontr_kp)) {
                $str_semestrkontr .= strtoupper(dechex($i));
            }
        }
    
        $obj1->id = $did;
        $obj1->cyclename = $frm->cyclename;
        $obj1->gos = $frm->gos;
        $obj1->identificatordiscipline = $frm->identificatordiscipline;
        $obj1->mustlearning = $frm->mustlearning;
        $obj1->competition = $frm->competition;
        $obj1->creditov = $frm->creditov;
        $obj1->razdel = $frm->razdel;
        $obj1->sr = $frm->sr;
        $obj1->semestrexamen = $str_semestrexamen;
        $obj1->semestrzachet = $str_semestrzachet;
        $obj1->semestrkursovik = $str_semestrkursovik;
        $obj1->semestrdiffzach = $str_semestrdiffzach;
        $obj1->semestrkp = $str_semestrkp;
        $obj1->semestrref = $str_semestrref;
        $obj1->semestrkontr = $str_semestrkontr;
        
        $obj1->timemodified = time();
        $obj1->modifierid = $USER->id;
    
        $DB->update_record('bsu_discipline', $obj1);
        
        // update_charge_discipline_term_formkontrol($frm);        

        $alltermsinkontrol = get_all_terms_discipline_in_kontrol($obj1);
        // print_object($alltermsinkontrol);
        
        $termwithkontrols = array();
        foreach ($alltermsinkontrol as $t => $v){
            $termwithkontrols[] = $t;
        }
        
        unset($obj1);
    
        /******************************** Добавление/обновление  bsu_discipline_semestr *********************************/
        $mas_nag = array();

        $termwithhours = array();
        foreach ($frm as $key => $value) {
            if (!is_numeric($value)) continue;
    
            $mas_nag_tmp = explode('_', $key);
    
            if ($mas_nag_tmp[0] == 'nag') {
                $index = $mas_nag_tmp[2] . '_' . $mas_nag_tmp[3];
                if (!isset($mas_nag[$index])) {
                    $mas_nag[$index] = new stdClass();
                }
                $mas_nag[$index]->{$mas_nag_tmp[1]} = $value;
                
                $aterm1 = $mas_nag_tmp[2];
                $termwithhours[] = $aterm1;

                if (isset($alltermsinkontrol[$aterm1])) {
                    $arraytermktrl = (array)$alltermsinkontrol[$aterm1]; 
                    foreach ($arraytermktrl as $afield => $avalue)    {
                        $mas_nag[$index]->{$afield} = $avalue;
                    }
                } else {
                    $fields = array('examen', 'zachet', 'zachetdiff', 'kr', 'kp', 'referat', 'kontr');
                    foreach ($fields as $field) {
                        $mas_nag[$index]->{$field} = 0; 
                    }                
                }
            }
        }

        // print_object($mas_nag);

        // print_object($termwithkontrols);
        // print_object($termwithhours);
        
        $termwithkontrols = array_diff($termwithkontrols, $termwithhours); 
        // print_object($termwithkontrols);
        
        foreach ($termwithkontrols as $aterm1)   {
            if ($dsrec = $DB->get_record_select('bsu_discipline_semestr', "disciplineid=$did AND numsemestr=$aterm1")) {
                $index = $aterm1 . '_' . $dsrec->id;
            } else {
                $index = $aterm1 . '_0';
            }    
            if (!isset($mas_nag[$index])) {
                $mas_nag[$index] = new stdClass();
            }
            if (isset($alltermsinkontrol[$aterm1])) {
                $arraytermktrl = (array)$alltermsinkontrol[$aterm1]; 
                foreach ($arraytermktrl as $afield => $avalue)    {
                    $mas_nag[$index]->{$afield} = $avalue;
                }
            }
            if ($dsrec) {
                $mas_nag[$index]->lec = $dsrec->lection;
                $mas_nag[$index]->labzan = $dsrec->lab;
                $mas_nag[$index]->prakzan = $dsrec->praktika;
                $mas_nag[$index]->ksr = $dsrec->ksr;
                $mas_nag[$index]->sam = $dsrec->srs;
                $mas_nag[$index]->trud = $dsrec->zet;
            } 
        }                
        
        // print_object($mas_nag); 
        // exit();       

        // удаляем несуществующие семестры
        $vseterms = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16); 
        $diff1 = array_diff($vseterms, $termwithkontrols);
        $diff2 = array_diff($vseterms, $termwithhours);
        $absents = array_intersect($diff1, $diff2);
        // print 'absent:'; 
        // print_object($absents);
        if (!empty($absents))    {
            foreach ($absents as $absenterm)   {
                if ($dsrec = $DB->get_record_select('bsu_discipline_semestr', "disciplineid=$did AND numsemestr=$absenterm")) {
                    $dsid = $dsrec->id;
                    if (is_empty_object($dsrec)) {
                        if ($DB->delete_records_select('bsu_discipline_semestr', "id = $dsid")) {
                            notify("Удалена строка в bsu_discipline_semestr (1) - $dsid<br>");
                            delete_charge_discipline_term($frm, $absenterm);
                            delete_stream_discipline_term($frm, $term);
                            // add_to_log(1, 'discipline', 'delete semestr', "planid=$frm->pid&numsemestr=$term", $frm->dname_name, $frm->did, $USER->id);
                            add_to_bsu_plan_log('discipline:delete term', $frm->pid, $frm->did, "planid=$frm->pid&numsemestr=$absenterm", $frm->dname_name);
                        } else {
                            notify("Not deleted $dsid in bsu_discipline_semestr");
                        }
                    }  else {
                        $fields = array('examen', 'zachet', 'zachetdiff', 'kr', 'kp', 'referat', 'kontr');
                        $rec = new stdClass();
                        $rec->id = $dsid; 
                        foreach ($fields as $field) {
                            $rec->{$field} = 0; 
                        }                
                        $DB->update_record('bsu_discipline_semestr', $rec);        
                    }  
                }
            }
        }

    
        foreach ($mas_nag as $index => $obj) {
            list($term, $discipline_semestrid) = explode('_', $index);
            if ($discipline_semestrid > 0) {
                if (is_empty_object($obj)) {
                    // echo 'delete 1 ' .  $discipline_semestrid . '<br>';
                    if ($DB->delete_records_select('bsu_discipline_semestr', "id = $discipline_semestrid")) {
                        notify("Удалена строка в bsu_discipline_semestr (2) - $discipline_semestrid<br>");
                        delete_charge_discipline_term($frm, $term);
                        delete_stream_discipline_term($frm, $term);
                        // add_to_log(1, 'discipline', 'delete semestr', "planid=$frm->pid&numsemestr=$term", $frm->dname_name, $frm->did, $USER->id);
                        add_to_bsu_plan_log('discipline:delete term', $frm->pid, $frm->did, "planid=$frm->pid&numsemestr=$term", $frm->dname_name);
                    } else {
                        notify("Not deleted $discipline_semestrid in bsu_discipline_semestr");
                    }
                    
                 } else {
                    $rec = new stdClass();
                    $rec->id = $discipline_semestrid;
                    if (isset($obj->trud)) {
                        $rec->zet = $obj->trud;
                    } else {
                        $rec->zet = 0;
                    }
                    if (isset($obj->lec)) {
                        $rec->lection = $obj->lec;
                    } else {
                        $rec->lection = 0;
                    }
                    if (isset($obj->prakzan)) {
                        $rec->praktika = $obj->prakzan;
                    } else {
                        $rec->praktika = 0;
                    }
                    if (isset($obj->labzan)) {
                        $rec->lab = $obj->labzan;
                    } else {
                        $rec->lab = 0;
                    }
                    if (isset($obj->sam)) {
                        $rec->srs = $obj->sam;
                    } else {
                        $rec->srs = 0;
                    }
                    /*
                    if (isset($obj->exhours)) {
                        $rec->examenhours = $obj->exhours;
                    } else {
                        $rec->examenhours = 0;
                    }
                    */
                    if (isset($obj->ksr)) {
                        $rec->ksr = $obj->ksr;
                    } else {
                        $rec->ksr = 0;
                    }
                    
                    $fields = array('examen', 'zachet', 'zachetdiff', 'kr', 'kp', 'referat', 'kontr');
                    foreach ($fields as $field) {
                        if (isset($obj->{$field}))  {
                            $rec->{$field} = $obj->{$field}; 
                        }
                    }
                    
                    $oldrec = $DB->get_record_select('bsu_discipline_semestr', "id = $discipline_semestrid", null, 'id, lection, lab, praktika'); 
                    //....Готово!
                    //echo 'update ' .  $discipline_semestrid . '<br>';
                    if ($DB->update_record('bsu_discipline_semestr', $rec))  {
                        // update_charge_discipline_term($frm, $term, $oldrec, $rec);
                        //echo "Обовлена строка - $discipline_semestrid<br>"
                    }
                 
                    unset($rec);
                }
            } else {
                if (!is_empty_object($obj)) {
                    $rec = new stdClass();
                    $rec->numsemestr = $term;
                    $rec->disciplineid = $did;
                    if (isset($obj->trud)) {
                        $rec->zet = $obj->trud;
                    } else {
                        $rec->zet = 0;
                    }
                    if (isset($obj->lec)) {
                        $rec->lection = $obj->lec;
                    } else {
                        $rec->lection = 0;
                    }
                    if (isset($obj->prakzan)) {
                        $rec->praktika = $obj->prakzan;
                    } else {
                        $rec->praktika = 0;
                    }
                    if (isset($obj->labzan)) {
                        $rec->lab = $obj->labzan;
                    } else {
                        $rec->lab = 0;
                    }
                    if (isset($obj->sam)) {
                        $rec->srs = $obj->sam;
                    } else {
                        $rec->srs = 0;
                    }
                    /*
                    if (isset($obj->exhours)) {
                        $rec->examenhours = $obj->exhours;
                    } else {
                        $rec->examenhours = 0;
                    }
                    */
                    if (isset($obj->ksr)) {
                        $rec->ksr = $obj->ksr;
                    } else {
                        $rec->ksr = 0;
                    }
    
                    $fields = array('examen', 'zachet', 'zachetdiff', 'kr', 'kp', 'referat', 'kontr');
                    foreach ($fields as $field) {
                        if (isset($obj->{$field}))  {
                            $rec->{$field} = $obj->{$field}; 
                        }
                    }

                    //print_object($rec);
                    //echo 'insert ' . $term . '<br>';
                    if ($newid = $DB->insert_record('bsu_discipline_semestr', $rec)) {
                        //echo "Добавлена строка - $newid<br>";
                        $index = $rec->numsemestr . '_' . $newid;
                        $mas_nag[$index] = new stdClass();
                    }
                }
    
            }
    
        }
    
        /*
        $sql = "SELECT id, concat(numsemestr, '_', id) as i
                    FROM mdl_bsu_discipline_semestr
                    where disciplineid=$did";
        if ($semestrs = $DB->get_records_sql_menu($sql)) {
            foreach ($semestrs as $index) {
                if (!isset($mas_nag[$index])) {
                    list($term, $discipline_semestrid) = explode('_', $index);
    
                    echo 'delete 2 ' .  $discipline_semestrid . '<br>';
                    
                    if ($DB->delete_records_select('bsu_discipline_semestr', "id = $discipline_semestrid")) {
                        //echo "Удалена строка (2) - $discipline_semestrid<br>";
                        delete_charge_discipline_term($frm, $term);
                    } else {
                        notify("Not deleted $discipline_semestrid in bsu_discipline_semestr");
                    }
                    
                }
            }
        }
        */
        
        // обновляем нагрузку с сохранением распределения преподавателей
        update_charge_with_save_teachingload($yid, $did, $pid);        
        
        
        $redirlink = "disciplines.php?yid=$yid&pid=$pid&fid=$fid&term=$planterm&tab=$plantab";
        redirect($redirlink, get_string('changessaved'), 0);
    }
    //***************************Конец блока кода с обработкой отправленных данных**************************************
    
    if ($faculty = $DB->get_record_select('bsu_ref_department', "departmentcode = $fid", null,
        'id, departmentcode, name, shortname')) {
    
    } else {
        //echo  "departmentcode = $fid";
        echo "Не удалось выгрузить данные по факультету";
    }
    $plan = $DB->get_record_select('bsu_plan', "id=$pid", null, 'id, name, timenorm');
    
    //print_object($faculty);
    
    $sql = 'SELECT d.disciplinenameid, rd.name
                FROM mdl_bsu_discipline d
                INNER JOIN mdl_bsu_ref_disciplinename rd on d.disciplinenameid=rd.id
                where d.id = ' . $did;
    
    $dname = $DB->get_record_sql($sql);
    
    /***************Поиск максимального семестра для данной дисциплины плана****************/
   
    $sql = "SELECT 1 as i, max(b.numsemestr) as maxsem FROM {bsu_discipline} a
                inner join {bsu_discipline_semestr} b on a.id=b.disciplineid
                where a.planid=$pid";
    if ($max = $DB->get_records_sql($sql)) {
        $maxsem = $max[1]->maxsem;
    } else {
        $maxsem = 0;
    }
//print "m=$maxsem<br>"    ;
$maxsem = 16;
    $maxplanterm =  2*$plan->timenorm;
    if ($maxplanterm > $maxsem) $maxsem = $maxplanterm;  
    
    $currtab = substr($plantab, 0, 4);
    $maxactiveterm = 0;
    $activeterms = array();
    if ($currtab == 'kurs') {
        $kurs = substr($plantab, 4, 1);
        $activeterms[] = 2*$kurs - 1;
        $activeterms[] = 2*$kurs;
        $maxactiveterm = 2*$kurs;
    }    
    
    $closed_terms = array();
    for ($i = 1; $i <= $maxsem; $i++) {
        if ($yid == 15) {
            if (!in_array($i, $activeterms) && $i < $maxactiveterm)    {
                $closed_terms[] = $i;
            }
        } else {
            if (!in_array($i, $activeterms))    {
                $closed_terms[] = $i;
            }
        }    
    }    
    // $closed_terms = array(1, 2, 3, 4); // список семестров закрытых для редактирования
    
    /***********************************************************************************************/
    $discipline = $DB->get_record_sql("SELECT d.*, n.name as nname FROM {bsu_discipline} d 
                                           INNER JOIN {bsu_ref_disciplinename} n ON n.id=d.disciplinenameid
                                           WHERE d.id=$did");
    
    $array_discipline_semestr = (array )$discipline;
    $discipline_semestr = (object)$array_discipline_semestr;
    $discipline_semestr->semestrexamen = str_split($discipline_semestr->semestrexamen, 1);
    $discipline_semestr->semestrzachet = str_split($discipline_semestr->semestrzachet, 1);
    $discipline_semestr->semestrdiffzach = str_split($discipline_semestr->semestrdiffzach, 1);
    $discipline_semestr->semestrkursovik = str_split($discipline_semestr->semestrkursovik, 1);
    $discipline_semestr->semestrkp = str_split($discipline_semestr->semestrkp, 1);
    $discipline_semestr->semestrref = str_split($discipline_semestr->semestrref, 1);
    $discipline_semestr->semestrkontr = str_split($discipline_semestr->semestrkontr, 1);
    
   
    /*********************************Шапка таблицы контрольных мероприятий*************************/
    
    $kontr_event = '<tr><td align="right" width="200px"><b>Контрольное мероприятие</b></td>';
    for ($i = 1; $i <= $maxsem; $i++) {
        if (in_array($i, $closed_terms))    {
            $kontr_event .= '<td align="center">' . $i . '</td>';
        } else {
            $kontr_event .= '<td align="center"><b>' . $i . '</b></td>';
        }        
    }
    $kontr_event .= '</tr>';
    
    /*********************************Тело таблицы контрольных мероприятий*********************************/
    
    $kontr_ed = array("ekz" => 'Экзамен (ЭКЗ)', "zach" => 'Зачет (ЗАЧ)', "diffzach" =>
        'Зачет дифференцированный (ДифЗАЧ)', "kr" => 'Курсовая работа (КУРС)', "kp" =>
        'Курсовой проект (КП)', 'ref' => 'Реферат (РЕФ)', 'kontr' => 'Контрольная работа (КНТР)');
    $kontr_ed1 = array("ekz" => 'semestrexamen', "zach" => 'semestrzachet',
        "diffzach" => 'semestrdiffzach', "kr" => 'semestrkursovik', 
        "kp" => 'semestrkp', 'ref' => 'semestrref', 'kontr' => 'semestrkontr');
    
    foreach ($kontr_ed as $key => $kontr) {
        $kontr_event .= '<tr><td align="left">' . $kontr . '<hr></td>';
        // echo $maxsem;
        for ($i = 1; $i <= $maxsem; $i++) {
            // print_object($discipline_semestr->{$kontr_ed1[$key]});
            $disabled = "";
            /*
            if (in_array($i, $closed_terms)) {
                $disabled = " title=\"Недоступно для редактирования\" disabled = \"true\" ";
            }
            */
    
            $j = strtoupper(dechex($i));
    
            if (in_array($j, $discipline_semestr->{$kontr_ed1[$key]})) {
                $kontr_event .= '<td><center><input type="checkbox" checked="true"' . $disabled .
                    'name="' . "kontr_{$key}_{$i}" . '"/></center></td>';
            } else {
                $kontr_event .= '<td><center><input type="checkbox"' . $disabled . 'name="' .
                    'kontr_' . $key . '_' . $i . '"/></center></td>';
            }
    
        }
        $kontr_event .= '</tr>';
    }
    
    
    $nagr_ed = array("lec" =>  'Лекции (в семестр) <i><b>из Ауд.</b></i> / час',
                     "labzan" =>  'Лабораторные занятия (в семестр) <i><b>из Ауд.</b></i> / час', 
                     "prakzan" => 'Практические занятия (в семестр) <i><b>из Ауд.</b></i> / час',
                     "ksr" => 'Контроль самостоятельной работы (в семестр) <i><b>из Ауд.</b></i> / час', 
                     "aud" =>  '<strong>Аудиторная (в семестр) / час</strong>', 
                     "sam" => 'Самостоятельная (в семестр) / час',
                     "trud" => 'Трудоемкость (в семестр) / ЗЕТ');
                     //                      "exhours" => 'Экзамен / час',
    $nagr_ed1 = array("lec" => 'lection', "labzan" => 'lab', "prakzan" => 'praktika', 
                      "sam" => 'srs', "ksr" => 'ksr', "trud" => 'zet'); // "exhours" => 'examenhours', 
    
    
    /*********************************Шапка таблицы Нагрузки по семестрам*********************************/
    
    $nagr = '<tr><td align="center" width="200px"><b>Распределение нагрузки по семестрам</b></td>';
    for ($i = 1; $i <= $maxsem; $i++) {
        if (in_array($i, $closed_terms))    {
            $nagr .= '<td align="center">' . $i . '</td>';
        } else {
            $nagr .= '<td align="center"><b>' . $i . '</b></td>';
        }        
    }
    $nagr .= '</tr>';
    
    
    /*********************************Тело таблицы Нагрузки по семестрам*********************************/
    $semestrs = $DB->get_records_select('bsu_discipline_semestr', "disciplineid=$did", null, 'numsemestr', 
                                        'numsemestr, id, disciplineid, lection, praktika, lab, ksr, examenhours, srs, zachet, zachetdiff, examen, kp, kr, zet, referat, essay, kontr, rgr, intlec, intlab, intpr, intiz');
    // print_object($semestrs);
    foreach ($nagr_ed as $key => $nagr_) {
        $nagr .= '<tr><td align="left">' . $nagr_ . '<hr></td>';
    
        for ($i = 1; $i <= $maxsem; $i++) {
            $disabled = ' ';
            if (in_array($i, $closed_terms) && $nagr_ != $nagr_ed['aud']) {
                $disabled = ' disabled="true"  title="Недоступно для редактирования" ';
            }
            
            if (isset($semestrs[$i]))   {
                 $semestr =  $semestrs[$i];
            // if ($semestr = $DB->get_record_select('bsu_discipline_semestr', "disciplineid=$did AND numsemestr=$i")) {
                /********************************Отображаем (не)защищенные текстовые поля********************************/
                if ($nagr_ != $nagr_ed['aud']) {
                    //отображаем ОТКРЫТЫЕ ячейки в ЗАПОЛНЕННЫХ столбцах
                    $nagr .= '<td><center><input type="text" size="3" name="' . 'nag_' . $key . '_' .
                        $i . '_' . $semestr->id . '" value="' . $semestr->{$nagr_ed1[$key]} . '"' .
                        " id=\"nag_{$key}_{$i}_{$semestr->id}\"" . $disabled . ' onkeyup="check_summ(this)" ' .
                        ' /></center></td>';
                } else {
                    //отображаем ЗАКРЫТЫЕ ячейки в ЗАПОЛНЕННЫХ столбцах
                    $summaaud = $semestr->lab + $semestr->lection + $semestr->praktika + $semestr->ksr;
                    $value = ' value=' . $summaaud . ' ';
                    //$value = ' value='.$semestr->{$nagr_ed1[$key]}.' ';
                    $name = " name=nag_{$key}_{$i}_{$semestr->id} ";
                    $id = " id=\"nag_{$key}_{$i}_{$semestr->id}\" ";
                    $nagr .= '<td><center><input type="text" size="3"' . $name . $id . $value .
                        ' disabled="true"  title="Недоступно для редактирования" ' . '/></center></td>';
                }
            } else
                if ($nagr_ != $nagr_ed['aud']) {
                    //отображаем ОТКРЫТЫЕ ячейки в ПУСТЫХ столбцах
                    $nagr .= '<td><center><input type="text" size="3" name="' . 'nag_' . $key . '_' .
                        $i . '_0"' . " id=\"nag_{$key}_{$i}_0\"" . $disabled . ' onkeyup="check_summ(this)" ' .
                        ' /></center></td>';
                } else {
                    //отображаем ЗАКРЫТЫЕ ячейки в ПУСТЫХ столбцах
                    $nagr .= '<td><center><input type="text"size="3" name="' . 'nag_' . $key . '_' .
                        $i . '_0"' . " id=\"nag_{$key}_{$i}_0\"" .
                        ' disabled="true"  title="Недоступно для редактирования" ' . '/></center></td>';
                }
                /************************************************************************************************************/
        }
        $nagr .= '</tr>';
    }
    //$nagr .= '<tr><td><input type = "hidden" name = "id_in_discipline_semestr" value = "'.$id_in_discipline_semestr.'" ></td></tr>';
    
    if (is_siteadmin()) {
       /*********************************Шапка таблицы Служебная информация*********************************/
        
        $nagr .= '<tr><td align="center" width="200px"><small>Служебная информация</small></td>';
        for ($i = 1; $i <= $maxsem; $i++) {
            if (in_array($i, $closed_terms))    {
                $nagr .= '<td align="center"><small>' . $i . '</small></td>';
            } else {
                $nagr .= '<td align="center"><small><b>' . $i . '</b></small></td>';
            }        
        }
        $nagr .= '</tr>';    
        
        $fields = array('examen', 'zachet', 'zachetdiff', 'kr', 'kp', 'referat', 'kontr');
        foreach ($fields as $field) {
            
            $nagr .= '<tr><td align="left"><small>' . $field . '</small></td>';
            for ($i = 1; $i <= $maxsem; $i++) {
                if (isset($semestrs[$i]))   {
                     $semestr =  $semestrs[$i];
                     $nagr .= '<td><center><small>' . $semestr->{$field} . '</small></center></td>';
                } else {
                     $nagr .= '<td><center><small>-</small></center></td>';                   
                }
            }
            $nagr .= '</tr>';
        }        
    }    
    ?>
    
    <form  name="datarup" method="post" action="editd.php">
        <input type="hidden" name="yid" value="<?php echo $yid ?>" />
        <input type="hidden" name="fid" value="<?php echo $fid ?>" />
        <input type="hidden" name="pid" value="<?php echo $pid ?>" />
        <input type="hidden" name="term" value="<?php echo $planterm ?>" />
        <input type="hidden" name="did" value="<?php echo $did ?>" />
        <input type="hidden" name="plantab" value="<?php echo $plantab ?>" />
        
        <?php 
        if (empty($activeterms))    {
            echo '<input type="hidden" name="aterm1" value="0" />';
            echo '<input type="hidden" name="aterm2" value="0" />';
        } else {
            echo '<input type="hidden" name="aterm1" value="'.$activeterms[0].'" />';
            echo '<input type="hidden" name="aterm2" value="'.$activeterms[1].'" />';
        }
        
        ?>
    
         <table border="2" align="center">
            <tr valign="bottom">
                <td>
                    <table border="1" align="center" bgcolor="lightyellow" width="300px">
                        <tr>
                            <td align="left">Факультет/Институт:<br /></td>
                        </tr>
                        <tr>
                            <td align="center">
                                <input type="hidden" name="maxsem"  value="<?php echo $maxsem; ?>"/>
                                <input type="hidden" name="faculty_name"  value="<?php echo $faculty->name; ?>"/>
                                <b><?php echo $faculty->name; ?></b>
                            </td>
                        </tr>
                        <tr>
                            <td align="left">
                            Рабочий учебный план:
                            </td>
                        </tr>
                        <tr>
                            <td align="center">
                                <input type="hidden" name="plan_name"  value="<?php echo $plan->name; ?>"/>
                                <b><?php echo $plan->id. '. ' . $plan->name; ?></b>
                            </td>
                        </tr>
                        <tr>
                            <td align="left">
                            Дисциплина:
                            </td>
                        </tr>
                        <tr>
                            <td align="center">
                                <input type="hidden" name="dname_name"  value="<?php echo $dname->name; ?>"/>
                                <input type="hidden" name="disciplinenameid"  value="<?php echo $dname->disciplinenameid; ?>"/>
                                <b><?php echo $dname->name; ?></b>
                            </td>
                        </tr>
                    </table>
                </td>
                <td>
                    <!--  "#7DBB87"  -->
                    <table border="1" align="center" bgcolor="#EEEEEE" width="600px">
                        <tr>
                            <td align="right">Название:<br /></td>
                            <td colspan="3"><input type="text"  size="67"  name="dnname" value="<?php echo $dname->name; ?>"></td>
                        </tr>
                        <tr>
                            <td align="right">Цикл:<br /></td>
                            <td><input type="text" name="cyclename" value="<?php echo $discipline_semestr->cyclename; ?>"></td>
                            <td align="right">ГОС:<br /></td>
                            <td><input type="text" name="gos" value="<?php echo $discipline_semestr->gos; ?>"></td>
                        </tr>
                        <tr>
                            <td align="right">Блок (идентификатор дисциплины):</td>
                            <td align="right"><input type="text" name="identificatordiscipline" value="<?php echo $discipline_semestr->identificatordiscipline; ?>"></td>
                            <td align="right">Подлежит изучению:<br /></td>
                            <td><input type="text" name="mustlearning" value="<?php echo $discipline_semestr->mustlearning; ?>"></td>
                        </tr>
                        <tr>
                            <td align="right">Компетенции:</td>
                            <td align="right"><input type="text" name="competition" value="<?php echo $discipline_semestr->competition; ?>"></td>
                            <td align="right">Кредитов:<br /></td>
                            <td><input type="text" name="creditov" value="<?php echo $discipline_semestr->creditov; ?>"></td>
                        </tr>
                        <tr>
                            <td align="right">Раздел:</td>
                            <td align="right"><input type="text" name="razdel" value="<?php echo $discipline_semestr->razdel; ?>"></td>
                            <td align="right">СР (всего):<br /></td>
                            <td><input type="text" name="sr"  value="<?php echo $discipline_semestr->sr; ?>"></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr valign="top">
                <td colspan="2">
                    <table border="1" align="center" width="100%" bgcolor="#EEEEEE">                
                        <?php echo $kontr_event ?>
                    </table>
                </td>
            </tr>
            <tr valign="top">
                <td colspan="2">
                    <table border="1" align="center" width="100%" bgcolor="#EEEEEE">                
                        <?php echo $nagr ?>
                    </table>
                </td>
            </tr>
            <tr align="center">
                <td colspan="2">
                <input type="submit" name="button1" value="Сохранить" />
                <input type="reset"  value="Обновить" title="Возвращает значения таблицы на момент до нажатия кнопки 'Сохранить'"/>                     
                <input type="submit" name="button2" value="Отменить" />
                </td>
                </tr>
        </table>
    </form> 
    <!--<input type="text" size="3" name="kl" onchange="alert(this.value)"/> -->
    
<?php

    $sql = "SELECT FROM_UNIXTIME(d.timemodified, '%d.%m.%Y %h:%i') as timemodified, d.modifierid, concat (u.lastname, ' ', u.firstname) as fullname
            FROM mdl_bsu_discipline d
            inner join mdl_user u on u.id=d.modifierid
            where d.id=$did";
    if ($whomodifierd = $DB->get_record_sql($sql))  {
        notify("Дисциплина редактировалась $whomodifierd->timemodified. Пользователь: $whomodifierd->fullname.", 'notifysuccess');
    }        

    echo $OUTPUT->footer();
    
    
// возвращает true если  $obj имеет нулевые поля
function is_empty_object($obj)
{
    $fields = array('trud', 'lec', 'prakzan', 'labzan', 'sam', 
                    'examen', 'zachet', 'zachetdiff', 'kr', 'kp', 'referat', 'kontr',
                    'lection', 'praktika', 'lab', 'ksr', 'srs');
    
    foreach ($fields as $field) {
        if (isset($obj->{$field}) && !empty($obj->{$field})) {
            return false;
        }
    }
    
    /*
    if (isset($obj->trud) && !empty($obj->trud)) {
        return false;
    }

    if (isset($obj->lec) && !empty($obj->lec)) {
        return false;
    }

    if (isset($obj->prakzan) && !empty($obj->prakzan)) {
        return false;
    }

    if (isset($obj->labzan) && !empty($obj->labzan)) {
        return false;
    }

    if (isset($obj->sam) && !empty($obj->sam)) {
        return false;
    }

    if (isset($obj->examen) && !empty($obj->examen)) {
        return false;
    }

    */  

    return true;
}



function change_disciplinenameid_in_discipline($frm)
{
    global $DB;
    
    $disc = trim($frm->dnname);
    $current_disc = $DB->get_records('bsu_ref_disciplinename', array('name'=>$disc), '', 'id, name');

    $test_disc = FALSE;
    foreach($current_disc as $cd){
        if($cd->name == $disc){
            $test_disc = TRUE;
            $disciplinenameid = $cd->id;  
            break;//поиск совпадения введенной дисциплины с имеющимися в БД
        }
    }
    
    if($test_disc) {
        //дисциплина не добавлена, т.к. она уже имеется в БД
        // echo "<br /><br /><br /><center><b><font color='red'>Такая дисциплина уже имеется в БД</font></b>,<br> добавление не произошло.</center>";
        // echo "<center><a href='disciplines.php' style=' text-decoration:none; color:#ffcc00; font:bold 14px '';'>&#60&#60Вернуться к добавлению</a></center>";            
    } else {
        //удачное добавление дисциплины
        $discipline = new stdClass();
        $discipline->name = $disc;
        $disciplinenameid = $DB->insert_record("bsu_ref_disciplinename",$discipline);
        
        echo "<br /><br /><br /><center><font color='green'>Добавлена дисциплина</font> ==> <b>$discipline->name</b></center>";
        // echo "<center><a href='disciplines.php' style=' text-decoration:none; color:#ffcc00; font:bold 14px '';'>&#60&#60Вернуться к добавлению</a></center>";
    }
    
    $olddisciplinenameid = $DB->get_field_select('bsu_discipline', 'disciplinenameid', "id = $frm->did");
    $DB->set_field_select('bsu_discipline', 'disciplinenameid', $disciplinenameid, "id = $frm->did"); 
    
    update_charge_disciplinenameid($frm, $olddisciplinenameid, $disciplinenameid);
} 

   
function update_charge_disciplinenameid($frm, $olddisciplinenameid, $newdisciplinenameid)
{
    global $DB, $OUTPUT;
    
    // print_object($frm);
    // echo "$olddisciplinenameid => $newdisciplinenameid";
    // находим существующую нагрузку
    $conditions  = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplineid' => $frm->did);
    $conditions2 = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplinenameid' => $olddisciplinenameid);
    
    if ($DB->record_exists('bsu_edwork', $conditions))	{
        $DB->set_field('bsu_edwork', 'disciplinenameid', $newdisciplinenameid, $conditions);        
        $edmids = $DB->get_records_menu('bsu_edwork_mask', $conditions2, '', 'id as id1, id as id2');
        $strids = implode (',', $edmids);
        $DB->set_field_select('bsu_edwork_mask', 'disciplinenameid', $newdisciplinenameid, "id in ($strids)");
    }
     
    $DB->set_field('bsu_discipline_stream_mask', 'disciplinenameid', $newdisciplinenameid, $conditions2); 
     
    $tables = array('bsu_discipline_subdepartment', 'bsu_schedule', 'bsu_schedule_mask');               
    foreach($tables as $table){ // перебор имен таблиц    
        $DB->set_field_select($table, "disciplinenameid", $newdisciplinenameid, "yearid=$frm->yid AND disciplineid=$frm->did");
        // notify ($table);     
    }
    
    $DB->set_field_select('bsu_discipline_synonym', "disciplinenameid", $newdisciplinenameid, "disciplineid=$frm->did");
    $DB->set_field_select('bsu_discipline_synonym', "s_disciplinenameid", $newdisciplinenameid, "s_disciplineid=$frm->did");
    
}

function update_charge_discipline_term($frm, $term, $oldrec, $rec)
{
    global $DB, $OUTPUT;
    
    // print_object($frm);
    // echo "$term";
     // находим существующую нагрузку
    $conditions = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplineid' => $frm->did, 'term' => $term);
    // id, yearid, edworkmaskid, planid, practiceid, disciplineid, disciplinenameid, term, edworkkindid, streammaskid, groupid, subgroupid, numstream, subdepartmentid, hours
    if ($edworks = $DB->get_records('bsu_edwork', $conditions, '', 'id, edworkmaskid, edworkkindid, streammaskid, hours'))	{
        $lectionedmids = array();
        $labedmids = array();
        $praktikaedmids = array();
        $labstreamids = array();
        $praktikastreamids = array();
        foreach ($edworks as $edwork)   {
            switch ($edwork->edworkkindid)  {
                case 1: // lection
                        if ($rec->lection != $edwork->hours) {
                            $DB->set_field_select('bsu_edwork', 'hours', $rec->lection, "id = $edwork->id");
                            $lectionedmids[] = $edwork->edworkmaskid;
                        }
                break;
                case 2: // lab
                        if ($rec->lab != $edwork->hours) {
                            $DB->set_field_select('bsu_edwork', 'hours', $rec->lab, "id = $edwork->id");
                            $labedmids[] = $edwork->edworkmaskid;
                            $labstreamids[$edwork->edworkmaskid] = $edwork->streammaskid;
                        }
                break;
                case 3: // praktika
                        if ($rec->praktika != $edwork->hours) {
                            $DB->set_field_select('bsu_edwork', 'hours', $rec->praktika, "id = $edwork->id");
                            $praktikaedmids[] = $edwork->edworkmaskid;
                            $praktikastreamids[$edwork->edworkmaskid] = $edwork->streammaskid;
                        }
                break;
                
            }            
        }
        if (!empty($lectionedmids)) {
            $lectionedmids = array_unique($lectionedmids);
            foreach ($lectionedmids as $edworkmaskid)  {
                $sql = "SELECT max(hours) as max  FROM {bsu_edwork}  WHERE  edworkmaskid = $edworkmaskid";
                $newhours = $DB->get_field_sql($sql);
                $DB->set_field_select('bsu_edwork_mask', 'hours', $newhours, "id = $edworkmaskid");
            }
        }    
        if (!empty($labedmids)) {
            $labedmids = array_unique($labedmids);
            foreach ($labedmids as $edworkmaskid)  {
                if ($labstreamids[$edworkmaskid]>0) {
                    $sql = "SELECT max(hours) as max  FROM {bsu_edwork}  WHERE  edworkmaskid = $edworkmaskid";
                    $newhours = $DB->get_field_sql($sql);
                    $DB->set_field_select('bsu_edwork_mask', 'hours', $newhours, "id = $edworkmaskid");                    
                } else {
                    $sql = "SELECT sum(hours) as sum  FROM {bsu_edwork}  WHERE  edworkmaskid = $edworkmaskid";
                    $newhours = $DB->get_field_sql($sql);
                    $DB->set_field_select('bsu_edwork_mask', 'hours', $newhours, "id = $edworkmaskid");
                }  
            }
        }    
        if (!empty($praktikaedmids)) {
            $praktikaedmids = array_unique($praktikaedmids);
            foreach ($praktikaedmids as $edworkmaskid)  {
                if ($praktikastreamids[$edworkmaskid]>0) {
                    $sql = "SELECT max(hours) as max  FROM {bsu_edwork}  WHERE  edworkmaskid = $edworkmaskid";
                    $newhours = $DB->get_field_sql($sql);
                    $DB->set_field_select('bsu_edwork_mask', 'hours', $newhours, "id = $edworkmaskid");                    
                } else {
                    $sql = "SELECT sum(hours) as sum  FROM {bsu_edwork}  WHERE  edworkmaskid = $edworkmaskid";
                    $newhours = $DB->get_field_sql($sql);
                    $DB->set_field_select('bsu_edwork_mask', 'hours', $newhours, "id = $edworkmaskid");
                }  
            }
        } 
        
           
    }
}


function update_charge_discipline_term_formkontrol($frm)
{
    global $DB, $OUTPUT;
    
    // print_object($frm);
    // находим существующую нагрузку
    $conditions = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplineid' => $frm->did);
    if ($DB->record_exists('bsu_edwork', $conditions))	{
        $activeterms = array($frm->aterm1, $frm->aterm2);
        $formkontrols = array();
        foreach ($activeterms as $aterm)    {
            $kontr_ekz = "kontr_ekz_{$aterm}";
            if (isset($frm->$kontr_ekz)) {
                $conditions2 = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplineid' => $frm->did, 
                                      'term' => $aterm, 'edworkkindid' => 4);
                // print_object($conditions2);                                      
                if (!$DB->record_exists('bsu_edwork', $conditions2))	{
                    $formkontrols[] = 'examen_'.$aterm;
                    // echo 'examen_'.$aterm;
                }     
            }

            $kontr_zach = "kontr_zach_{$aterm}";
            if (isset($frm->$kontr_zach)) {
                $conditions2 = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplineid' => $frm->did, 
                                      'term' => $aterm, 'edworkkindid' => 5);
                if (!$DB->record_exists('bsu_edwork', $conditions2))	{
                    $formkontrols[] = 'zachet_'.$aterm;
                }     
            }
            
            $kontr_diffzach = "kontr_diffzach_{$aterm}";
            if (isset($frm->$kontr_diffzach)) {
                $conditions2 = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplineid' => $frm->did, 
                                      'term' => $aterm, 'edworkkindid' => 39);
                if (!$DB->record_exists('bsu_edwork', $conditions2))	{
                    $formkontrols[] = 'diffzachet_'.$aterm;
                }     
            }
            
            /*
            $kontr_kr = "kontr_kr_{$aterm}";
            if (isset($frm->$kontr_kr)) {
                
            }
            $kontr_kp = "kontr_kp_{$aterm}";
            if (isset($frm->$kontr_kp)) {
                
            }
            */
        }    
        // print_object($formkontrols);
        
        // проверяем какие конрольные формы надо убрать из нагрузки
        $sql = "SELECT d.id as did, d.disciplinenameid, d.semestrexamen, d.semestrzachet, d.semestrkursovik, d.semestrdiffzach
                FROM {bsu_discipline} d 
                WHERE d.id = $frm->did";
        $discipline = $DB->get_record_sql($sql);
        
        
        $sql = "SELECT id, concat(term, '_', edworkkindid) as fk FROM mdl_bsu_edwork 
                where disciplineid = $frm->did and edworkkindid in (4,10,5,39) and term in ($frm->aterm1, $frm->aterm2)
                group by edworkkindid, term";
        $fkedworks = $DB->get_records_sql_menu($sql);

        $fkdis = array();                        
        if (!empty($discipline->semestrexamen)) {
            $arr1 = str_split($discipline->semestrexamen);
            foreach ($arr1 as $arr)    {
                $decterm = hexdec($arr);
                if (in_array($decterm, $activeterms))   {
                    $fkdis[] = $decterm . '_4';
                    $fkdis[] = $decterm . '_10';
                }
            }
        }
        if (!empty($discipline->semestrzachet)) {
            $arr1 = str_split($discipline->semestrzachet);
            foreach ($arr1 as $arr)    {
                $decterm = hexdec($arr);
                if (in_array($decterm, $activeterms))   {
                    $fkdis[] = $decterm . '_5';
                }
            }
        }   
        if (!empty($discipline->semestrdiffzach)) {
            $arr1 = str_split($discipline->semestrdiffzach);
            foreach ($arr1 as $arr)    {
                $decterm = hexdec($arr);
                if (in_array($decterm, $activeterms))   {
                    $fkdis[] = $decterm . '_39';
                }
            }
        }    
        
        // print_object($fkedworks);
        // print_object($fkdis);
        
        $fkdels = array_diff($fkedworks, $fkdis);
        // print_object($fkdels);
        if (!empty($fkdels))    {
            foreach ($fkdels as $fkdel) {
                list($term, $edworkkindid) = explode ('_', $fkdel);
                
                $conditions = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplineid' => $frm->did, 
                                     'term' => $term, 'edworkkindid' => $edworkkindid);
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
        }   
                
        // проверяем какие конрольные формы надо добавить в нагрузку
        if (!empty($formkontrols))  {
            $strgroups = get_plan_groups($frm->pid);
            if (!empty($strgroups)) {    
                $agroups = explode ('<br>', $strgroups);
                $terms = get_terms_group($frm->yid, $agroups);
                $countstudents = get_count_students_groups($agroups);
            
                $cntsubdep = $DB->count_records_select('bsu_discipline_subdepartment', "yearid=$frm->yid AND disciplineid=$frm->did");
                if ($cntsubdep > 1) return;
                // id, yearid, disciplineid, disciplinenameid, subdepartmentid, specialityid
                if ($subdepartmentid = $DB->get_field_select('bsu_discipline_subdepartment', 'subdepartmentid', "yearid = $frm->yid AND disciplineid=$frm->did"))   {
                    $discipline->subdepartmentid = $subdepartmentid;    
                } else {
                    $discipline->subdepartmentid = 0;
                }
                
                
                foreach ($formkontrols as $formkontrol) {
                    list($fk, $term) = explode('_', $formkontrol);
                    $discipline->numsemestr = $term; 
                    switch ($fk)    {
                        case 'examen':
                            $discipline->examen = 0.33;
                            $edworkmask = get_edwork_for_edworkkind (4, $discipline, $agroups, $terms, $frm->yid, $frm->pid, 'examen');
                            multiply_on_students($edworkmask, $countstudents);
                            create_edwork_for_edworkkind ($edworkmask);
                            // print_object($edworkmask);
                            
                            $discipline->kons = 2;
                            $edworkmask = get_edwork_for_edworkkind (10, $discipline, $agroups, $terms, $frm->yid, $frm->pid, 'kons');
                            create_edwork_for_edworkkind ($edworkmask);
                            // print_object($edworkmask);
                        break;
                         
                        case 'zachet':
                            $discipline->zachet = 0.25;
                            $edworkmask = get_edwork_for_edworkkind (5, $discipline, $agroups, $terms, $frm->yid, $frm->pid, 'zachet');
                            multiply_on_students($edworkmask, $countstudents);
                            create_edwork_for_edworkkind ($edworkmask);
                            // print_object($edworkmask);
                        break;
                        
                        case 'diffzachet':
                            $discipline->diffzachet = 0.25;
                            $edworkmask = get_edwork_for_edworkkind (39, $discipline, $agroups, $terms, $frm->yid, $frm->pid, 'diffzachet');
                            multiply_on_students($edworkmask, $countstudents);
                            create_edwork_for_edworkkind ($edworkmask);
                            // print_object($edworkmask);
                        break;
                    }

                }
            }    
        }
    }
}


function update_charge_with_save_teachingload($yid, $did, $planid)
{
    global $DB, $OUTPUT;
  
    // проверяем $yid на текущий учебный год или следующий за ним   
    $curryid = get_current_edyearid();  
    if ($yid < $curryid) return false;
    
    // есть ли нагрузка в $yid учебном году 
    if ($DB->record_exists_select('bsu_edwork', "yearid=$yid AND disciplineid=$did"))   {
        delete_discipline_charge($yid, $did, $planid, true);
        $strgroups = get_plan_groups($planid);
        if (!empty($strgroups)) {    
            $agroups = explode ('<br>', $strgroups);
            create_edworks_for_discipline($yid, $planid, $did, $agroups);
            restore_teachingload($yid, $did, $planid);   
        }     
    // иначе создаем нагрузку в $yid учебном году      
    }  else {
        $strgroups = get_plan_groups($planid);
        if (!empty($strgroups)) {    
            $agroups = explode ('<br>', $strgroups);
            create_edworks_for_discipline($yid, $planid, $did, $agroups);
        }     
    }
    
    return true;
}

/*
 * Функция удаляет все потоки по дисциплине в семестре
 * @param $frm - объект с характеристиками дисциплины
 * @param $term - номер семестра
 */
function delete_stream_discipline_term($frm, $term)
{
    global $DB, $OUTPUT;

    // print_object($frm);
    // echo $term . '<br>';
    // находим существующую нагрузку
    $conditions = array ('yearid' => $frm->yid, 'planid' => $frm->pid, 'disciplinenameid' => $frm->disciplinenameid, 'term' => $term);
    print_object($conditions);
    if ($streams = $DB->get_records('bsu_discipline_stream_mask', $conditions, '', 'id, id as id2'))	{
        foreach ($streams as $stream)   {
            $DB->delete_records_select('bsu_discipline_stream', "streammaskid = $stream->id");
        }
        $DB->delete_records('bsu_discipline_stream_mask', $conditions);
    }
}


?>