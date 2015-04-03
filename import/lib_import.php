<?php // $Id: importrup.php,v 1.3 2011/10/13 11:25:28 shtifanov Exp $


   ////////////////////////////////////////////////////
    ///                                              ///
    ///   функция перевода строки в транслит         ///
    ///     $input - текст для трансляции            ///
    ///                                              ///
    ////////////////////////////////////////////////////
    function transliterate($input){
        
        $gost = array(
           "Є"=>"YE","І"=>"I","Ѓ"=>"G","і"=>"i","№"=>"-","є"=>"ye","ѓ"=>"g",
           "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
           "Е"=>"E","Ё"=>"YO","Ж"=>"ZH",
           "З"=>"Z","И"=>"I","Й"=>"J","К"=>"K","Л"=>"L",
           "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
           "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"X",
           "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
           "Ы"=>"Y","Ь"=>"","Э"=>"E","Ю"=>"YU","Я"=>"YA",
           "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
           "е"=>"e","ё"=>"yo","ж"=>"zh",
           "з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l",
           "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
           "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"x",
           "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
           "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
           " "=>"_","—"=>"_",","=>"_","!"=>"_","@"=>"_",
           "#"=>"-","$"=>"","%"=>"","^"=>"","&"=>"","*"=>"",
           "("=>"",")"=>"","+"=>"","="=>"",";"=>"",":"=>"",
           "'"=>"","\""=>"","~"=>"","`"=>"","?"=>"","/"=>"",
           "\\"=>"","["=>"","]"=>"","{"=>"","}"=>"","|"=>"" ,"."=>"."
        );
        
        return strtr($input, $gost);
    }

function get_attribute_value($attributes, $nameattrib, $printerror = true)
{
    if (isset($attributes->{$nameattrib}))  {
        $attribute = (string)$attributes->{$nameattrib};
        return $attribute;
    } else {
        if ($printerror)    {
            print_object($attributes);
            print_error('Не задан атрибут '. $nameattrib, 'importshp.php');
        }
        return false;
    }
}


function get_disciplinenameid($discplinename, $output = true)
{
    global $CFG, $DB, $OUTPUT;

    $strsql = "SELECT id FROM {$CFG->prefix}bsu_ref_disciplinename
              where name = '$discplinename'";
    if ($discipline = $DB->get_record_sql ($strsql)) 	{
        // if ($output) echo $OUTPUT->notification('Имя дисциплины найдено в справочнике: ' . $discplinename, 'notifysuccess');
        return $discipline->id;
    }  else {
        // if ($output) echo $OUTPUT->notification('Имя дисциплины не найдено в справочнике: ' . $discplinename);
        $rec = new stdClass();
        $rec->name = (string)$discplinename;
        // print_object($rec);
        if ($newid = $DB->insert_record('bsu_ref_disciplinename', $rec))    {
            echo $OUTPUT->notification('Добавлено имя новой дисциплины: ' . $discplinename, 'notifysuccess');
            return $newid;
        } else {
            print_error('Error insert in  bsu_ref_disciplinename');
        }
    }
    return 0;

}


function get_plantypeid($name, $output = true)
{
    global $CFG, $DB, $OUTPUT;

    $strsql = "SELECT id FROM {$CFG->prefix}bsu_ref_plantype
              where description = '$name'";
    if ($plantype = $DB->get_record_sql ($strsql)) 	{
        if ($output) echo $OUTPUT->notification('Имя типа плана найдено в справочнике.', 'notifysuccess');
        return $plantype->id;
    }  else {
        if($output) echo $OUTPUT->notification('Имя типа плана не найдено в справочнике:' .  $name . '. По умолчанию задан тип "специалист".');
    }
    return 3;
}


/*
function  get_specialityid($lastshifr, $plantypeid)
{
    global $CFG, $DB, $OUTPUT;

    $subcode = '';
    switch ($plantypeid)    {
        case 2: case 3: $subcode = '.65';
        break;
        case 4: case 5: $subcode = '.62';
        break;
        case 6: case 7: $subcode = '.68';
        break;
    }

    // $ashifr = explode('-', $name);
    // $shifr  = str_replace('_', '.', $ashifr[0]);
    $shifr  =  $lastshifr . $subcode;

    $strsql = "SELECT id FROM {$CFG->prefix}bsu_ref_speciality
              where code = '$shifr'";
    if ($speciality = $DB->get_record_sql ($strsql)) 	{
        echo $OUTPUT->notification('Шифр специальности найден в справочнике.', 'notifysuccess');
        return $speciality->id;
    }  else {
        $strsql = "SELECT id FROM {$CFG->prefix}bsu_ref_speciality
                  where code like '{$shifr}%'";
        if ($speciality = $DB->get_record_sql ($strsql)) 	{
            echo $OUTPUT->notification('Шифр специальности найден в справочнике.', 'notifysuccess');
            return $speciality->id;
        } else {
            echo $OUTPUT->notification('Шифр специальности не найден в справочнике:' .  $shifr);
        }
    }
    return 1;
}
*/



function  get_edformid($name0, $output = true )
{
    global $CFG, $DB, $OUTPUT;
    
    $name = mb_strtolower($name0, 'UTF-8');
    $pos = mb_strpos($name, 'очно-заочная');
    if (!($pos === false)) {
        $edformid = 4;
        echo $OUTPUT->notification('Форма обучения: очно-заочная.', 'notifysuccess');
    } else {
        $pos = mb_strpos($name, 'заочная');
        if (!($pos === false)) {
            $edformid = 3;
            echo $OUTPUT->notification('Форма обучения - заочная.', 'notifysuccess');
        }  else {
            $pos = mb_strpos($name, 'очная');
            if (!($pos === false)) {
                $edformid = 2;
                if ($output) echo $OUTPUT->notification('Форма обучения - очная.', 'notifysuccess');
            }  else {
                $edformid = 1;
                if ($output) echo $OUTPUT->notification('Форма обучения неопределена.');
            }
        }    
    }    
        
/*    
    if ($pos === false) {
        $pos = mb_strpos($name, 'очная');
        if ($pos === false) {
            $edformid = 4;
            if ($output) echo $OUTPUT->notification('Форма обучения неопределена.');
        }  else {
            $edformid = 2;
            if ($output) echo $OUTPUT->notification('Форма обучения - очная.', 'notifysuccess');
        }
    } else {
    }
*/

    return $edformid;
}


function  get_cycleid($abbrev, $name)
{
    global $CFG, $DB, $OUTPUT;

    $abrname = $abbrev . ' ' . $name;

    $strsql = "SELECT id FROM {$CFG->prefix}bsu_ref_cycle
              where abbrev = '$abbrev' AND name = '$name'";
    if ($cycleref = $DB->get_record_sql ($strsql)) 	{
        echo $OUTPUT->notification('Аббревиатура и название цикла найдены в справочнике: ' . $abrname, 'notifysuccess');
        return $cycleref->id;
    }  else {
        echo $OUTPUT->notification('Аббревиатура и название цикла не найдены в справочнике: ' . $abrname);
        $rec = new stdClass();
        $rec->abbrev = (string)$abbrev;
        $rec->name   = (string)$name;
        // print_object($rec);
        if ($newid = $DB->insert_record('bsu_ref_cycle', $rec))    {
            echo $OUTPUT->notification('Добавлен новый цикл: ' . $abrname, 'notifysuccess');
            return $newid;
        } else {
            print_error('Error insert in bsu_ref_cycle!');
        }
    }
    return 0;
}


// Display list faculty as popup_form

function  get_specialityid($fid, $lastshifr, $plantypeid=0)
{
    global $DB, $OUTPUT, $USER;
    
    $struploadrup = get_string("uploadrup", "block_bsu_plan");
    $maxuploadsize = get_max_upload_file_size();
    $strsql = "SELECT idSpecyal as id, Specyal FROM {bsu_tsspecyal}
               where idFakultet=$fid AND KodSpecyal = '$lastshifr'";
    if ($specialitys = $DB->get_records_sql ($strsql)) 	{
        if (count($specialitys) > 1) {
            echo $OUTPUT->notification('Обнаружено несколько специальностей с одинаковым шифром:' . $lastshifr);
            return -1;           
        } else {
            $speciality = reset($specialitys);
            echo $OUTPUT->notification('Шифр специальности найден в справочнике.', 'notifysuccess');
        }    
        return $speciality->id;
    }  else {
        echo $OUTPUT->notification('Для данного факультета шифр специальности не найден в справочнике:' .  $lastshifr);
        return 0;
    }
}


function  listbox_speciality($scriptname, $fid, $sid)
{
    global $CFG, $DB, $OUTPUT;

    $strsql = "SELECT idSpecyal as id, Specyal as name FROM {bsu_tsspecyal}
               where idFakultet=$fid
               order by 2";
/*    
    echo '<tr><td align=right>Специальность:</td><td align="left">';
    echo '<select name="sid">
         <option selected="selected" value="0">Выберите специальность...</option>';
    foreach ($specialitys as $speciality)   {
        echo "<option value=\"$speciality->id\">$speciality->specyal</option><br>";
    }
    echo '</select><br><br>';
    echo '</td></tr>';
*/
    $menu = array();
    $menu[0] = 'Выберите специальность ...';
    
    
    if ($specialitys = $DB->get_records_sql ($strsql)) 	{
    	foreach ($specialitys as $speciality)   {
    		$menu[$speciality->id] = $speciality->name;
    	}
    }
    
    echo '<tr><td align=right>Специальность:</td><td align="left">';
    echo $OUTPUT->single_select($scriptname, 'sid', $menu, $sid, null, 'switchspec');
    // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
    echo '</td></tr>';
        
}    


function listbox_kvalifspec()
{
    global $CFG, $DB;

    $strsql = "SELECT idKvalif as id, Kvalif FROM {bsu_tskvalifspec} order by idKvalif";
    $specialitys = $DB->get_records_sql ($strsql);
    
    echo '<tr><td align=right>Квалификация:</td><td align="left">';
    echo '<select name="kvalif">';
    // <option selected="selected" value="0">Выберите квалификацию ...</option>';
    foreach ($specialitys as $speciality)   {
        echo "<option value=\"$speciality->id\">$speciality->kvalif</option><br>";
    }
    echo '</select><br><br>';
    echo '</td></tr>';    
    
}

/*
else if ($plan->specialityid == -1) {
    // idSpecyal, idFakultet, Specyal, KodSpecyal, Note, LittleSpec, Ped, idFEO, PNR, PrizPK
    $strsql = "SELECT idSpecyal as id, specyal FROM {bsu_tsspecyal}
           where idFakultet=$faculty->id AND KodSpecyal = '$plan->lastshifr'";
    $specialitys = $DB->get_records_sql ($strsql);
    foreach ($specialitys as $speciality)   {
        // print_object($speciality);
        echo $speciality->specyal . '<br>';
    }
    
}
*/

/*
function listbox_groups_for_plans($scriptname, $fid, $gid)
{
  global $CFG, $OUTPUT, $DB;

  $groupmenu = array();
  $groupmenu[0] = get_string('selectagroup', 'block_bsu_plan') . ' ...';

  $strsql = "SELECT a.id as gid, a.departmentcode, a.name 
             FROM {$CFG->prefix}bsu_ref_groups a
             LEFT JOIN {$CFG->prefix}bsu_plan_groups  b ON b.groupid = a.id
             where (a.departmentcode = $fid) AND (b.groupid is null)
             ORDER BY a.name DESC";
  // echo $strsql . '<br>';
  if ($arr_group = $DB->get_records_sql ($strsql)) 	{
		foreach ($arr_group as $gr) {
			$groupmenu[$gr->gid] = $gr->name;
		}
  }

  $strsql = "SELECT a.id as gid, a.departmentcode, a.name FROM {$CFG->prefix}bsu_ref_groups a
             LEFT JOIN {$CFG->prefix}bsu_plan_groups  b ON b.groupid = a.id
             where (a.departmentcode = $fid) AND (b.groupid is not null)
             ORDER BY a.name DESC";
  // echo $strsql . '<br>';
  if ($arr_group = $DB->get_records_sql ($strsql)) 	{
		foreach ($arr_group as $gr) {
			$groupmenu[$gr->gid] = $gr->name . ' (план загружен)';
		}
  }

  echo '<tr><td align=right>'.get_string('group').':</td><td align="left">';
  echo $OUTPUT->single_select($scriptname, 'gid', $groupmenu, $gid, null, 'switchgroup');
  // popup_form($scriptname, $facultymenu, 'switchfac', $fid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}
*/



function import_shaht_plan_spo ($xml, $fid, $sid, $kvalif)
{
    global $CFG, $OUTPUT, $DB, $USER, $faculty, $newfile_name;

    $plan = new stdClass();
    // $plan->groupid = $gid;

    $attributes = $xml->attributes();
    $plan->checksum = 0;
    $plan->xmlfile = $newfile_name;
    $plan->timecreated = time();
    $plan->modifierid = $USER->id;          

    // $plan->checksum = $plan->checksum[0];

    $attributes = $xml->Title->attributes();
    $edformname = get_attribute_value($attributes, 'ed_form');
    $plan->edformid = get_edformid($edformname);

    $plan->edyearid = get_current_edyearid();
    $plan->departmentcode = $fid;
    $plan->shortname = get_attribute_value($attributes, 'spec_shifr');
    $plan->name = get_attribute_value($attributes, 'spec_shifr', false) . '. '.get_attribute_value($attributes, 'spec_name');
    $plan->lastshifr = get_attribute_value($attributes, 'spec_shifr');
    $plan->startyear = (integer)get_attribute_value($attributes, 'year_begin', false);
    if ($plan->startyear == 0) $plan->startyear = date('Y');
    $arr = explode (' ', get_attribute_value($attributes, 'GOS_date'));
    $plan->gosdate = convert_date($arr[0]);
    $plan->sertificatedate = get_attribute_value($attributes, 'ratif_date');
    $arr = explode (' ', get_attribute_value($attributes, 'ratif_date'));
    $plan->sertificatedate = convert_date($arr[0]);
    $plan->zetinyear = 0; // get_attribute_value($attributes, 'ЗЕТнаГОД');
    // $plan->zetinyear = str_replace(',', '.', $plan->zetinyear);
    $plan->hourinzet = 0; // get_attribute_value($attributes, 'ЧасовВЗЕТ');
    //$plan->hourinzet = str_replace(',', '.', $plan->hourinzet);
    $plan->zetinweek = 0; // get_attribute_value($attributes, 'ЗЕТвНеделе');
    // $plan->zetinweek = str_replace(',', '.', $plan->zetinweek);
    $plan->zettotal  = 0; // (string)get_attribute_value($attributes, 'ЗЕТнаВСЕ', false);
    // $plan->zettotal  = str_replace(',', '.', $plan->zettotal);
    $plan->plantypeid = 1; // не определен

    if ($sid == 0)  {
        $plan->specialityid = get_specialityid($faculty->id, $plan->lastshifr, $plan->plantypeid);
        if ($plan->specialityid == 0) {
            echo $OUTPUT->notification('Импорт не выполнен из-за осутствия специальности в справочнике.');
            return 0;        
        }  else if ($plan->specialityid == -1) {
            redirect("importshp.php?action=selectspec&fid=$fid", 'Выполнить импорт с выбором специальности.', 3);
        }
    } else {
        $plan->specialityid = $sid;
        echo $OUTPUT->notification('Шифр специальности определен пользователем.', 'notifysuccess');
    }            
    // print_object($plan);


    $xmlcycles = $xml->Plan;
    $cycles = array ();
    $i=0;
    foreach ($xmlcycles->block as $xmlcycle) {
        
        $attributes = $xmlcycle->attributes();
        if (isset($attributes->index))  {
            $cycles[$i] = new stdClass();
            $cycles[$i]->number = get_attribute_value($attributes, 'code');
            $cycles[$i]->abbrev = get_attribute_value($attributes, 'index', false);
            $cycles[$i]->name   = (string)get_attribute_value($attributes, 'name', false);
        }    
        $i++;
    }

    // print_object($cycles);
 

    $specialities = array();

/*    
    $xmlspecialities = $xml->План->Титул->Специальности->Специальность;
    $i=0;
    foreach ($xmlspecialities as $xmlspeciality)  {
        $attributes = $xmlspeciality->attributes();
        $specialities[$i]->name = get_attribute_value($attributes, 'Название');
        $i++;
    }
*/
    // print_object($specialities);

    $xmlgrafiki = $xml->Title->GYP->Course;
    $grafiki = array();
    $i=0;
    foreach ($xmlgrafiki as $xmlgrafik)  {
        $attributes = $xmlgrafik->attributes();
            $grafiki[$i] = new stdClass();
            $grafiki[$i]->numkurs = (integer)get_attribute_value($attributes, 'num', false);
            $grafiki[$i]->numweekspring = 0;
            $grafiki[$i]->numweekautumn = 0;
            $grafiki[$i]->numstudents = (integer)get_attribute_value($attributes, 'stud', false);
            $grafiki[$i]->numgroups = 0;
            $grafiki[$i]->grafik = '';
            $grafiki[$i]->grafik2 = '';

            if (isset($xmlgrafik->Imestr)) {
                $grafiki[$i]->semestrgraf = array();
                $j=0;
                foreach ($xmlgrafik->Imestr as $grafsem)   {
                     $attributes = $grafsem->attributes();
                     $grafiki[$i]->semestrgraf[$j] = new stdClass();
                     $grafiki[$i]->semestrgraf[$j]->numkurs = $grafiki[$i]->numkurs;
                     $grafiki[$i]->semestrgraf[$j]->numsemestr = (integer)get_attribute_value($attributes, 'num', false);
                     $grafiki[$i]->semestrgraf[$j]->totalweek = 0; // (integer)get_attribute_value($attributes, 'СтрНедТО', false);
                     $grafiki[$i]->semestrgraf[$j]->numfistweek = (integer)get_attribute_value($attributes, 'numFirstWeek', false);
                     $grafiki[$i]->semestrgraf[$j]->numfistelement = (integer)get_attribute_value($attributes, 'numFirstEl', false);
                     $grafiki[$i]->semestrgraf[$j]->grafik = (string)get_attribute_value($attributes, 'graf', false);
                     $j++;
                }
            }
            $i++;
        
    }

    // print_object($grafiki);
    

    $xmlblocks = $xml->Plan->block;
    $disciplines = array();
    $praktiki = array ();
    $i=0; $p=0;
    foreach ($xmlblocks as $xmlblock) {
        
        if (isset($xmlblock->disc)) {
            $attributes = $xmlblock->attributes();
            $abbrev = get_attribute_value($attributes, 'index', false);
            
            foreach ($xmlblock->disc as $xmldiscipline) {
                // print_object($xmldiscipline);
                $attributes = $xmldiscipline->attributes();
                $disciplines[$i] = new stdClass();
                $disciplines[$i]->disciplinenameid = get_disciplinenameid($attributes->name);
            // $disciplines[$i]->subdepartmentid
                $disciplines[$i]->cyclename = $abbrev;
    /*        
                $disciplines[$i]->identificatorvidaplana = ''; // get_attribute_value($attributes, 'index');
                $disciplines[$i]->gos = get_attribute_value($attributes, 'ГОС', false);
                $disciplines[$i]->semestrexamen = (integer)get_attribute_value($attributes, 'СемЭкз', false);
                $disciplines[$i]->semestrzachet = (integer)get_attribute_value($attributes, 'СемЗач', false);
                $disciplines[$i]->semestrkursovik  = (integer)get_attribute_value($attributes, 'СемКР', false);
                $disciplines[$i]->hoursinter = (integer)get_attribute_value($attributes, 'ЧасовИнтер', false);
                $disciplines[$i]->creditov = (integer)get_attribute_value($attributes, 'КредитовНаДисциплину', false);
                $disciplines[$i]->razdel  = (integer)get_attribute_value($attributes, 'Раздел', false);
    */
                $disciplines[$i]->sr = get_attribute_value($attributes, 'o_samN', false);        
                $disciplines[$i]->competition = (string)get_attribute_value($attributes, 'Cmptns', false);
                $disciplines[$i]->mustlearning = (integer)get_attribute_value($attributes, 'itogo_oN', false);
                $disciplines[$i]->identificatordiscipline = get_attribute_value($attributes, 'index', false);
                
    
                $disciplines[$i]->credits = array();
    /*
                $xmlcredits = $xmldiscipline->КредитовПоКурсам->Курс;
                $j=0;
                foreach ($xmlcredits as $xmlcredit) {
                    $attributes = $xmlcredit->attributes();
                    $disciplines[$i]->credits[$j]->numkurs =  get_attribute_value($attributes, 'Ном');
                    $disciplines[$i]->credits[$j]->CrECTS =  str_replace(',', '.', (string)get_attribute_value($attributes, 'CrECTS', false));
                    $disciplines[$i]->credits[$j]->zet =  str_replace(',', '.', (string)get_attribute_value($attributes, 'ЗЕТ', false));
                    $j++;
                }
    */
                $xmlsemesters = $xmldiscipline->semesters_info->semester;
                $disciplines[$i]->semestrs = array();
                $j=0;
                foreach ($xmlsemesters as $xmlsemester) {
                    $attributes = $xmlsemester->attributes();
                    $disciplines[$i]->semestrs[$j] = new stdClass();
                    $disciplines[$i]->semestrs[$j]->numsemestr =  get_attribute_value($attributes, 'num');
                    $disciplines[$i]->semestrs[$j]->lection =  (integer)get_attribute_value($attributes, 'lect', false);
                    $disciplines[$i]->semestrs[$j]->praktika =  (integer)get_attribute_value($attributes, 'pr', false);
                    $disciplines[$i]->semestrs[$j]->lab =  (integer)get_attribute_value($attributes, 'lab', false);
                    // $disciplines[$i]->semestrs[$j]->ksr =  (integer)get_attribute_value($attributes, 'КСР', false);
                    $disciplines[$i]->semestrs[$j]->examenhours =  (integer)get_attribute_value($attributes, 'count_ex', false);
                    $disciplines[$i]->semestrs[$j]->zachet =  (integer)get_attribute_value($attributes, 'count_z', false);
                    $disciplines[$i]->semestrs[$j]->zachetdiff =  (integer)get_attribute_value($attributes, 'count_zdif', false);
                    $disciplines[$i]->semestrs[$j]->srs =  (integer)get_attribute_value($attributes, 'samN', false);
                    
                    $termhex = dechex($disciplines[$i]->semestrs[$j]->numsemestr);
                    $termhex = strtoupper($termhex);
    
                    if ($disciplines[$i]->semestrs[$j]->examenhours > 0)    {
                        $disciplines[$i]->semestrexamen = $termhex;    
                    }
                    
                    if ($disciplines[$i]->semestrs[$j]->zachet > 0) {
                        $disciplines[$i]->semestrzachet = $termhex;
                    }
                    
                    if ($disciplines[$i]->semestrs[$j]->zachetdiff > 0) {
                        $disciplines[$i]->semestrdiffzach = $termhex;
                    }    
                        
                    
                     
                    $j++;
                }
                $i++;
            }    
        }
        
        if (isset($xmlblock->practice)) {
            // print_object($xmlblock->practice);
            // $attributes = $xmlblock->attributes();
            // $abbrev = get_attribute_value($attributes, 'index', false);
            
            foreach ($xmlblock->practice as $xmlpractice) {
                // print_object($xmlpractice);
                $attributes = $xmlpractice->attributes();
                $index = (string)get_attribute_value($attributes, 'index', false);
                $name  = (string)get_attribute_value($attributes, 'name', false);
                $competition  = (string)get_attribute_value($attributes, 'Cmptns', false);
                
                $xmlsemesters = $xmlpractice->semesters_info->semester;
                foreach ($xmlsemesters as $xmlsemester) {
                    $attributes = $xmlsemester->attributes();
                    // print_object($attributes);
                    if (isset($attributes->weeks))  {
                        $praktiki[$p] = new stdClass();
                        $praktiki[$p]->index = $index;
                        $praktiki[$p]->name  = $name;
                        $praktiki[$p]->competition  = $competition;
                        $praktiki[$p]->term  = (integer)get_attribute_value($attributes, 'num', false);
                        $praktiki[$p]->week  = (integer)get_attribute_value($attributes, 'weeks', false);
                        $praktiki[$p]->hours = (float)get_attribute_value($attributes, 'hours', false);
                        $p++;
                    }   
                }     
             }
        }       

        
    }
    // print_object($disciplines);
    // print_object($praktiki);
    // exit(); 

    /*
    if ($DB->get_record_select('bsu_plan', "name = '$plan->name'", null, 'id')) {
        echo $OUTPUT->notification("ПЛАН с именем $plan->name уже существует в БД.");
        return 0;
    }
    */

    if ($planid = $DB->insert_record('bsu_plan', $plan)) {
        echo $OUTPUT->notification('ПЛАН добавлен в БД. Id = ' . $planid, 'notifysuccess');

        
        foreach ($cycles as $cycle) {
            $cycle->planid = $planid;
            $cycle->cycleid =  get_cycleid($cycle->abbrev, $cycle->name);
            if ($DB->insert_record('bsu_plan_cycle', $cycle)) {
                echo $OUTPUT->notification('ЦИКЛ добавлен в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ЦИКЛа в БД.');
            }
        }

        
        foreach ($grafiki as $grafik)  {
            $grafik->planid = $planid;
            if ($DB->insert_record('bsu_plan_grafikuchprocess', $grafik)) {
                echo $OUTPUT->notification('ГРАФИК добавлен в БД.', 'notifysuccess');
                if (isset($grafik->semestrgraf))    {
                    foreach ($grafik->semestrgraf as $semestrgraf)  {
                        $semestrgraf->planid  = $planid;
                        if ($DB->insert_record('bsu_plan_weeks', $semestrgraf)) {
                            echo $OUTPUT->notification('ГРАФИК СЕМЕСТРОВЫЙ добавлен в БД.', 'notifysuccess');
                        } else {
                            echo $OUTPUT->notification('Ошибка при добавлении ГРАФИКа СЕМЕСТРОВОГО в БД.');
                        }
                    }
                }
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ГРАФИКа в БД.');
            }
        }

        foreach ($disciplines as $discipline)   {
            $discipline->planid = $planid;
            // print_object($discipline);
            if ($disciplineid = $DB->insert_record('bsu_discipline', $discipline)) {
                echo $OUTPUT->notification('ДИСЦИПЛИНА добавлена в БД с id='.$disciplineid, 'notifysuccess');
                
                /*
                foreach ($discipline->credits as $credit)   {
                    $credit->disciplineid = $disciplineid;
                    $credit->CrECTS = str_replace(',', '.', $credit->CrECTS);
                    $credit->zet = str_replace(',', '.', $credit->zet);
                    if ($DB->insert_record('bsu_discipline_creditovkurs', $credit)) {
                        echo $OUTPUT->notification('КредитовПоКурсам добавлен в БД.', 'notifysuccess');
                    } else {
                        print_object($credit);
                        echo $OUTPUT->notification('Ошибка при добавлении КредитовПоКурсам в БД.');
                    }
                }*/

                foreach ($discipline->semestrs as $semestr)   {
                    $semestr->disciplineid = $disciplineid;
                    if ($DB->insert_record('bsu_discipline_semestr', $semestr)) {
                        echo $OUTPUT->notification('Семестр дисицплины добавлен в БД.', 'notifysuccess');
                    } else {
                        print_object($semestr);
                        echo $OUTPUT->notification('Ошибка при добавлении Семестра дисицплины в БД.');
                    }
                }

            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ДИСЦИПЛИНЫ в БД.');
            }
        }
/*
        foreach ($planweeks as $planweek)   {
            $planweek->planid = $planid;
            if ($DB->insert_record('bsu_plan_weeks_normi', $planweek)) {
                echo $OUTPUT->notification('НЕДЕЛЯ добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении НЕДЕЛИ в БД.');
            }
        }
*/


       foreach ($praktiki as $praktik)   {
            $praktik->planid = $planid;
            if ($DB->insert_record('bsu_plan_practice_shacht', $praktik)) {
                // echo $OUTPUT->notification('ПРАКТИКА добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ПРАКТИКИ в БД.');
            }

			$praktik->practicetypeid = find_practicetypeid($praktik->name, $plan->edformid);
			$praktik->name = $praktik->index . '. '. $praktik->name;
            $praktik->edworkkindid = 13;
            $praktik->timemodified = time();
            $praktik->modifierid = $USER->id;          
            // print_object($praktik);
            
            if ($DB->insert_record('bsu_plan_practice', $praktik)) {
                echo $OUTPUT->notification('ПРАКТИКА добавлена в БД.', 'notifysuccess');
            } else {
				print_object($praktik);
                echo $OUTPUT->notification('Ошибка при добавлении в БД.');
            }
       }
       

        
    } else {
        echo $OUTPUT->notification('Ошибка при добавлении ПЛАНА в БД.');
    }


    // echo $OUTPUT->single_button(new moodle_url("view.php", $options), get_string("downloadtext"));
}




function import_shaht_plan_vpo ($xml, $fid, $sid, $kvalif, $output = true, $return = false)
{
    global $CFG, $OUTPUT, $DB, $USER, $faculty, $newfile_name;

    $plan = new stdClass();
    // $plan->groupid = $gid;

    $attributes = $xml->attributes();
    $plan->checksum = (string)$attributes->КонтрольнаяСумма;
    $plan->xmlfile = $newfile_name;
    $plan->timecreated = time();
    $plan->modifierid = $USER->id;          

    // $plan->checksum = $plan->checksum[0];

    $attributes = $xml->План->attributes();
    $edformname = get_attribute_value($attributes, 'ФормаОбучения');
    $plan->edformid = get_edformid($edformname, $output);

    $attributes = $xml->План->Титул->attributes();

    $plan->edyearid = get_current_edyearid();
    $plan->departmentcode = $fid;
    $plan->shortname = get_attribute_value($attributes, 'ИмяПлана');
    $plan->name = get_attribute_value($attributes, 'ПолноеИмяПлана');
    $plan->lastshifr = get_attribute_value($attributes, 'ПоследнийШифр', false);
    $plan->startyear = (integer)get_attribute_value($attributes, 'ГодНачалаПодготовки', false);
    if ($plan->startyear == 0) $plan->startyear = date('Y');
    $plan->gosdate = get_attribute_value($attributes, 'ДатаГОСа', false);
    list($datagos, $t) = explode(' ', $plan->gosdate);
    $plan->gosdate = convert_date($datagos);
    $plan->sertificatedate = get_attribute_value($attributes, 'ДатаСертификатаИМЦА', false);
    $plan->sertificatedate = convert_date($plan->sertificatedate);
    $plan->zetinyear = get_attribute_value($attributes, 'ЗЕТнаГОД', false);
    $plan->zetinyear = str_replace(',', '.', $plan->zetinyear);
    $plan->hourinzet = get_attribute_value($attributes, 'ЧасовВЗЕТ', false);
    $plan->hourinzet = str_replace(',', '.', $plan->hourinzet);
    $plan->zetinweek = get_attribute_value($attributes, 'ЗЕТвНеделе', false);
    $plan->zetinweek = str_replace(',', '.', $plan->zetinweek);
    $plan->zettotal  = (string)get_attribute_value($attributes, 'ЗЕТнаВСЕ', false);
    $plan->zettotal  = str_replace(',', '.', $plan->zettotal);
    $plan->vidplana = (integer)get_attribute_value($attributes, 'ВидПлана', false);
    $plan->kodlevel = get_attribute_value($attributes, 'КодУровня', false);
    $plan->uroven = get_attribute_value($attributes, 'Уровень', false);
    $plan->termsinkurs = (integer)get_attribute_value($attributes, 'СеместровНаКурсе', false);
    $plan->elementovinweek = (integer)get_attribute_value($attributes, 'ЭлементовВНеделе', false);
    $plan->gostype = (integer)get_attribute_value($attributes, 'ТипГОСа', false);
    $plan->ksr_iz = get_attribute_value($attributes, 'КСР_ИЗ', false);
    $plan->iga_zetinweek = get_attribute_value($attributes, 'ИГА_ЗЕТвНеделе', false);
    $plan->iga_zetinweek  = str_replace(',', '.', $plan->iga_zetinweek);
    $plan->iga_hoursinzet = get_attribute_value($attributes, 'ИГА_ЧасовВЗЕТ', false);
    $plan->iga_hoursinzet  = str_replace(',', '.', $plan->iga_hoursinzet);

    if (isset($xml->План->Титул->Квалификации)) {
        $xmlqualification = $xml->План->Титул->Квалификации->Квалификация->attributes();
        $plantype = (string)get_attribute_value($xmlqualification, 'Название', false);
        if ($plantype) {
            $plan->plantypeid = get_plantypeid($plantype, $output);
        } else {
            $plan->plantypeid = 3; // специалист
        }    
    } else {
        $plan->plantypeid = 3; // специалист
    }

    if ($sid == 0)  {
        $plan->specialityid = get_specialityid($faculty->id, $plan->lastshifr, $plan->plantypeid);
        if ($plan->specialityid == 0) {
            echo $OUTPUT->notification('Импорт не выполнен из-за осутствия специальности в справочнике.');
            return 0;        
        }  else if ($plan->specialityid == -1) {
            redirect("importshp.php?action=selectspec&fid=$fid&file=$newfile_name", 'Выполнить импорт с выбором специальности.', 3);
        }
    } else {
        $plan->specialityid = $sid;
        if ($output) echo $OUTPUT->notification('Шифр специальности определен пользователем = ' . $sid, 'notifysuccess');
    }     
    if ($output) echo $OUTPUT->notification('Код квалификации определен пользователем = ' . $kvalif, 'notifysuccess');
    $plan->kvalif = $kvalif;        
    // print_object($plan);  exit();

    $xmlcycles = $xml->План->Титул->АтрибутыЦиклов->Цикл;
    $cycles = array ();
    $i=0;
    foreach ($xmlcycles as $xmlcycle) {
        $attributes = $xmlcycle->attributes();
        $cycles[$i] = new stdClass(); 
        $cycles[$i]->number = (integer)get_attribute_value($attributes, 'Ном', false);
        $cycles[$i]->abbrev = get_attribute_value($attributes, 'Абревиатура', false);
        if (!$cycles[$i]->abbrev)   {
            $cycles[$i]->abbrev = get_attribute_value($attributes, 'Аббревиатура', false);
        }
        $cycles[$i]->name   = (string)get_attribute_value($attributes, 'Название', false);
        $i++;
    }
    // print_object($cycles); exit();
   
    $xmlspecialities = $xml->План->Титул->Специальности->Специальность;
    $specialities = array();
    $i=0;
    foreach ($xmlspecialities as $xmlspeciality)  {
        $attributes = $xmlspeciality->attributes();
        $specialities[$i] = new stdClass();
        $specialities[$i]->name = get_attribute_value($attributes, 'Название');
        $i++;
    }

    // print_object($specialities);

    $xmlgrafiki = $xml->План->Титул->ГрафикУчПроцесса->Курс;
    $grafiki = array();
    $i=0;
    foreach ($xmlgrafiki as $xmlgrafik)  {
        $attributes = $xmlgrafik->attributes();
        // if (isset($attributes->НедОсень) && isset($attributes->НедВесна))   {
            $grafik = get_attribute_value($attributes, 'График', false);
            if ($grafik)    {
                $grafik = substr($grafik, 0, 10);
                if ($grafik == '==========') continue;
            }
            
            $grafiki[$i] = new stdClass();
            $grafiki[$i]->numkurs = (integer)get_attribute_value($attributes, 'Ном', false);
            $grafiki[$i]->numgroups = (integer)get_attribute_value($attributes, 'Групп', false);
            $grafiki[$i]->numstudents = (integer)get_attribute_value($attributes, 'Студентов', false);
            $grafiki[$i]->grafik = (string)get_attribute_value($attributes, 'График', false);
            $grafiki[$i]->grafik2 = (string)get_attribute_value($attributes, 'График2', false);
            $grafiki[$i]->numweekspring = (integer)get_attribute_value($attributes, 'НедВесна', false );
            $grafiki[$i]->numweekautumn = (integer)get_attribute_value($attributes, 'НедОсень', false);

            if (isset($xmlgrafik->Семестр)) {
                $grafiki[$i]->semestrgraf = array();
                $j=0;
                foreach ($xmlgrafik->Семестр as $grafsem)   {
                     $attributes = $grafsem->attributes();
                     $grafiki[$i]->semestrgraf[$j] = new stdClass();
                     $grafiki[$i]->semestrgraf[$j]->numkurs = $grafiki[$i]->numkurs;
                     $grafiki[$i]->semestrgraf[$j]->numsemestr = (integer)get_attribute_value($attributes, 'Ном', false);
                     $grafiki[$i]->semestrgraf[$j]->totalweek = (integer)get_attribute_value($attributes, 'СтрНедТО', false);
                     $grafiki[$i]->semestrgraf[$j]->numfistweek = (integer)get_attribute_value($attributes, 'НомерПервойНедели', false);
                     $grafiki[$i]->semestrgraf[$j]->numfistelement = (integer)get_attribute_value($attributes, 'НомерПервогоЭлемента', false);
                     $grafiki[$i]->semestrgraf[$j]->grafik = (string)get_attribute_value($attributes, 'График', false);
                     $j++;
                }
            }
            $i++;
        // }
    }
    // print_object($grafiki); exit();


    $xmldisciplines = $xml->План->СтрокиПлана->Строка;
    $disciplines = array();
    $i=0;
    foreach ($xmldisciplines as $xmldiscipline) {
        $attributes = $xmldiscipline->attributes();
        // echo $attributes->Дис . '<br>';
        
        if (!isset($attributes->Цикл))  {
            if (!isset($attributes->НовЦикл) ) continue;  
        } 
        $disciplines[$i] = new stdClass();
        $disciplines[$i]->disciplinenameid = get_disciplinenameid($attributes->Дис, $output);
        $namediscipline = $DB->get_record_select('bsu_ref_disciplinename', "id = ".$disciplines[$i]->disciplinenameid, null, 'name');
        $disciplines[$i]->nname = $namediscipline->name; 
        // $disciplines[$i]->subdepartmentid
        if (isset($attributes->НовЦикл))    {
            $cyclename = get_attribute_value($attributes, 'НовЦикл');
            $disciplines[$i]->cyclename = trim($cyclename);
        } else {
            $disciplines[$i]->cyclename = get_attribute_value($attributes, 'Цикл');
        }    
        
        $disciplines[$i]->identificatorvidaplana = (integer)get_attribute_value($attributes, 'ИдетификаторВидаПлана', false);

        $disciplines[$i]->gos = get_attribute_value($attributes, 'ГОС', false);
        $disciplines[$i]->sr = get_attribute_value($attributes, 'СР', false);
        $disciplines[$i]->semestrexamen = get_attribute_value($attributes, 'СемЭкз', false);
        $disciplines[$i]->semestrzachet = get_attribute_value($attributes, 'СемЗач', false);
        $disciplines[$i]->semestrkursovik  = get_attribute_value($attributes, 'СемКР', false);
        $disciplines[$i]->competition = (string)get_attribute_value($attributes, 'Компетенции', false);
        $disciplines[$i]->hoursinter = (integer)get_attribute_value($attributes, 'ЧасовИнтер', false);
        $disciplines[$i]->creditov = (integer)get_attribute_value($attributes, 'КредитовНаДисциплину', false);
        $disciplines[$i]->mustlearning = (integer)get_attribute_value($attributes, 'ПодлежитИзучению', false);

        if (isset($attributes->НовИдДисциплины))    {
            $disciplines[$i]->identificatordiscipline = get_attribute_value($attributes, 'НовИдДисциплины', false);
        } else {
            $disciplines[$i]->identificatordiscipline = get_attribute_value($attributes, 'ИдетификаторДисциплины', false);
        }    
        $disciplines[$i]->razdel  = (integer)get_attribute_value($attributes, 'Раздел', false);

         
        $avalue = get_attribute_value($attributes, 'СемКП', false); 
        $semestrkp = $semestresse = $semestrkontr = $semestrref = $semestrrgr = '';
        get_esse_kontr_ref_rgr_kp($avalue, $semestrkp, $semestresse, $semestrkontr, $semestrref, $semestrrgr);
        $disciplines[$i]->semestrkp   = $semestrkp;
        $disciplines[$i]->semestresse = $semestresse;
        $disciplines[$i]->semestrkontr = $semestrkontr;
        $disciplines[$i]->semestrref = $semestrref;
        $disciplines[$i]->semestrrgr = $semestrrgr;
        
        $cyclename = get_attribute_value($attributes, 'НовЦикл', false);
        $disciplines[$i]->newcyclename = trim($cyclename);
        $disciplines[$i]->newidentificatordiscipline = get_attribute_value($attributes, 'НовИдДисциплины', false); 
        $disciplines[$i]->perezachetekz = get_attribute_value($attributes, 'ИзученоЭкз', false);
        $disciplines[$i]->perezachetzachet = get_attribute_value($attributes, 'ИзученоЗач', false);
        $disciplines[$i]->perezachetfiddzach= get_attribute_value($attributes, 'ИзученоЗачO', false);
        $disciplines[$i]->perezachetkp = get_attribute_value($attributes, 'ИзученоКП', false);
        $disciplines[$i]->perezachetkr = get_attribute_value($attributes, 'ИзученоКР', false);
        $disciplines[$i]->perezachethour = get_attribute_value($attributes, 'ПерезачетЧасов', false);
        $disciplines[$i]->competitioncode = get_attribute_value($attributes, 'КомпетенцииКоды', false);
        if (mb_strlen($disciplines[$i]->competitioncode, 'UTF-8')   > 250)  {
            // echo $disciplines[$i]->competitioncode . '<br />';
            $disciplines[$i]->competitioncode = mb_substr($disciplines[$i]->competitioncode, 0, 200, 'UTF-8');
        }
        
        $hoursinzet = get_attribute_value($attributes, 'ЧасовВЗЕТ', false);
        $disciplines[$i]->hoursinzet = str_replace(',', '.', $hoursinzet);

        // print_object($disciplines[$i]);
        
        $disciplines[$i]->credits = array();
        if (isset($xmldiscipline->КредитовПоКурсам->Курс))  {
            $xmlcredits = $xmldiscipline->КредитовПоКурсам->Курс;
            $j=0;
            foreach ($xmlcredits as $xmlcredit) {
                $attributes = $xmlcredit->attributes();
                $disciplines[$i]->credits[$j] = new stdClass();
                $disciplines[$i]->credits[$j]->numkurs =  get_attribute_value($attributes, 'Ном');
                $disciplines[$i]->credits[$j]->CrECTS =  str_replace(',', '.', (string)get_attribute_value($attributes, 'CrECTS', false));
                $disciplines[$i]->credits[$j]->zet =  str_replace(',', '.', (string)get_attribute_value($attributes, 'ЗЕТ', false));
                $j++;
            }
        }    

        if (isset($xmldiscipline->Сем))  {
            $xmlsemesters = $xmldiscipline->Сем;
            $disciplines[$i]->semestrs = array();
            $j=0;
            foreach ($xmlsemesters as $xmlsemester) {
                $attributes = $xmlsemester->attributes();
                $disciplines[$i]->semestrs[$j] = new stdClass();
                $disciplines[$i]->semestrs[$j]->numsemestr =  get_attribute_value($attributes, 'Ном');
                $disciplines[$i]->semestrs[$j]->lection =  (integer)get_attribute_value($attributes, 'Лек', false);
                $disciplines[$i]->semestrs[$j]->praktika =  (integer)get_attribute_value($attributes, 'Пр', false);
                $disciplines[$i]->semestrs[$j]->lab =  (integer)get_attribute_value($attributes, 'Лаб', false);            
                $disciplines[$i]->semestrs[$j]->ksr =  (integer)get_attribute_value($attributes, 'КСР', false);
                $disciplines[$i]->semestrs[$j]->examenhours =  (integer)get_attribute_value($attributes, 'ЧасЭкз', false);
                $disciplines[$i]->semestrs[$j]->srs =  (integer)get_attribute_value($attributes, 'СРС', false);
                $disciplines[$i]->semestrs[$j]->zachet =  (integer)get_attribute_value($attributes, 'Зач', false);
                $disciplines[$i]->semestrs[$j]->zachetdiff =  (integer)get_attribute_value($attributes, 'ЗачО', false);
                $disciplines[$i]->semestrs[$j]->examen =  (integer)get_attribute_value($attributes, 'Экз', false);
                $disciplines[$i]->semestrs[$j]->kp =  (integer)get_attribute_value($attributes, 'КП', false);
                $disciplines[$i]->semestrs[$j]->kr =  (integer)get_attribute_value($attributes, 'КР', false);
                $disciplines[$i]->semestrs[$j]->zet =  str_replace(',', '.', (string)get_attribute_value($attributes, 'ЗЕТ', false));
                $disciplines[$i]->semestrs[$j]->referat =  (integer)get_attribute_value($attributes, 'Реф', false);
                $disciplines[$i]->semestrs[$j]->essay =  (integer)get_attribute_value($attributes, 'Эссе', false);
                $disciplines[$i]->semestrs[$j]->kontr  =  (integer)get_attribute_value($attributes, 'Контр', false);
                $disciplines[$i]->semestrs[$j]->rgr =  (integer)get_attribute_value($attributes, 'РГР', false);
                $disciplines[$i]->semestrs[$j]->intlec =  (integer)get_attribute_value($attributes, 'ИнтЛек', false);
                $disciplines[$i]->semestrs[$j]->intlab =  (integer)get_attribute_value($attributes, 'ИнтЛаб', false);
                $disciplines[$i]->semestrs[$j]->intpr =  (integer)get_attribute_value($attributes, 'ИнтПр', false);
                $disciplines[$i]->semestrs[$j]->intiz =  (integer)get_attribute_value($attributes, 'ИнтИЗ', false);
                $disciplines[$i]->semestrs[$j]->prlecinweek =  (integer)get_attribute_value($attributes, 'ПроектЛекВНед', false);
                $disciplines[$i]->semestrs[$j]->prlabinweek =  (integer)get_attribute_value($attributes, 'ПроектЛабВНед', false);
                $disciplines[$i]->semestrs[$j]->prprinweek =  (integer)get_attribute_value($attributes, 'ПроектПрВНед', false);
                $disciplines[$i]->semestrs[$j]->przet =  str_replace(',', '.', (string)get_attribute_value($attributes, 'ПроектЗЕТ', false));
                 
                $j++;
            }
        } else if (isset($xmldiscipline->Курс))  {
            /*
            if (empty($disciplines[$i]->semestrexamen)) $disciplines[$i]->semestrexamen = '';
            if (empty($disciplines[$i]->semestrzachet)) $disciplines[$i]->semestrzachet = '';
            if (empty($disciplines[$i]->semestrkursovik)) $disciplines[$i]->semestrkursovik  = '';
            */
            $disciplines[$i]->semestrexamen = $disciplines[$i]->semestrzachet = $disciplines[$i]->semestrkursovik  = '';
 
            $xmlkures = $xmldiscipline->Курс;
            $disciplines[$i]->semestrs = array();
            $j=$crckurs=0;
            foreach ($xmlkures as $xmlkurs)   { 
                $attributes = $xmlkurs->attributes();
                // echo 'KURS:'; print_object ($attributes);
                $numkurs = get_attribute_value($attributes, 'Ном');
                $examenhours =  (integer)get_attribute_value($attributes, 'ЧасЭкз', false);
                $crckurs += (integer)get_attribute_value($attributes, 'СРС', false);
                $xmlsessions =  $xmlkurs->Сессия;
                $sessions = array();
                foreach ($xmlsessions as $xmlsession)   {
                    $attributes = $xmlsession->attributes();
                    $nom = get_attribute_value($attributes, 'Ном');
                    $sessions[$nom] = new stdClass();
                    $sessions[$nom]->lection =  (integer)get_attribute_value($attributes, 'Лек', false);
                    $sessions[$nom]->praktika =  (integer)get_attribute_value($attributes, 'Пр', false);
                    $sessions[$nom]->lab =  (integer)get_attribute_value($attributes, 'Лаб', false);
                    $sessions[$nom]->srs =  (integer)get_attribute_value($attributes, 'СРС', false);
                    $sessions[$nom]->ksr =  (integer)get_attribute_value($attributes, 'КСР', false);
                    $sessions[$nom]->examen =  (integer)get_attribute_value($attributes, 'Экз', false);
                    $sessions[$nom]->zachet =  (integer)get_attribute_value($attributes, 'Зач', false);
                    $sessions[$nom]->vidkontrol =  get_attribute_value($attributes, 'ВидКонтр', false);
                    
                    // print_object ($xmlsession);
                }
                // echo 'sessions:'; print_object($sessions);
                
                $semestrs = array();
                $semestrs[1] = new stdClass();
                $semestrs[1]->numsemestr = $numkurs*2 - 1;
                $hexnumsemestr = strtoupper(dechex($semestrs[1]->numsemestr));
                $semestrs[1]->lection = 0; 
                $semestrs[1]->praktika = 0;
                $semestrs[1]->lab = 0;
                $semestrs[1]->srs = 0;
                $semestrs[1]->ksr = 0;
                $semestrs[1]->examen = 0;
                $semestrs[1]->zachet = 0;
                if (isset($sessions[1])) {
                    $semestrs[1]->lection  += $sessions[1]->lection; 
                    $semestrs[1]->praktika += $sessions[1]->praktika;
                    $semestrs[1]->lab += $sessions[1]->lab;
                    $semestrs[1]->srs += $sessions[1]->srs;
                    $semestrs[1]->ksr += $sessions[1]->ksr;
                    $semestrs[1]->examen += $sessions[1]->examen;
                    $semestrs[1]->zachet += $sessions[1]->zachet;
                    /*
                    if ($sessions[1]->vidkontrol == 'Э') {
                        $semestrs[1]->examenhours = $examenhours;
                        $disciplines[$i]->semestrexamen = $semestrs[1]->numsemestr;
                    } else if ($sessions[1]->vidkontrol == 'З') {
                        $semestrs[1]->examenhours = $examenhours;
                        $disciplines[$i]->semestrzachet = $semestrs[1]->numsemestr;
                    } 
                    */

                }
                if (isset($sessions[2])) {
                    $semestrs[1]->lection += $sessions[2]->lection;
                    $semestrs[1]->praktika += $sessions[2]->praktika;
                    $semestrs[1]->lab += $sessions[2]->lab;
                    $semestrs[1]->srs += $sessions[2]->srs;
                    $semestrs[1]->ksr += $sessions[2]->ksr;
                    $semestrs[1]->examen += $sessions[2]->examen;
                    $semestrs[1]->zachet += $sessions[2]->zachet;
                }
                if ($semestrs[1]->examen > 0) {
                    $semestrs[1]->examenhours = $examenhours;
                    $disciplines[$i]->semestrexamen .= $hexnumsemestr;
                } else {
                    $pos = mb_strpos($sessions[2]->vidkontrol, 'Э', 0, 'UTF-8');
                    if (!($pos === false))  {
                        $semestrs[1]->examenhours = $examenhours;
                        $disciplines[$i]->semestrexamen .= $hexnumsemestr;
                    } 
                }

                if ($semestrs[1]->zachet > 0) {
                    $semestrs[1]->examenhours = $examenhours;
                    $disciplines[$i]->semestrzachet .= $hexnumsemestr;
                } else {
                    $pos = mb_strpos($sessions[2]->vidkontrol, 'З', 0, 'UTF-8');
                    if (!($pos === false))  {
                        $semestrs[1]->examenhours = $examenhours;
                        $disciplines[$i]->semestrzachet .= $hexnumsemestr;
                    } 
                }

                if ($semestrs[1]->ksr > 0) {
                    $disciplines[$i]->semestrkursovik .= $hexnumsemestr;
                } else {
                    $pos = mb_strpos($sessions[2]->vidkontrol, 'Р', 0, 'UTF-8');
                    if (!($pos === false))  {
                        $disciplines[$i]->semestrkursovik .= $hexnumsemestr;
                    } 
                }
                    
                $semestrs[2] = new stdClass();
                $semestrs[2]->numsemestr = $numkurs*2;
                $hexnumsemestr = strtoupper(dechex($semestrs[2]->numsemestr));
                $semestrs[2]->lection = 0; 
                $semestrs[2]->praktika = 0;
                $semestrs[2]->lab = 0;
                $semestrs[2]->srs = 0;
                $semestrs[2]->ksr = 0;
                $semestrs[2]->examen = 0;
                $semestrs[2]->zachet = 0;
            
                if (isset($sessions[3])) {    
                    $semestrs[2]->lection = $sessions[3]->lection;
                    $semestrs[2]->praktika = $sessions[3]->praktika;
                    $semestrs[2]->lab = $sessions[3]->lab;
                    $semestrs[2]->srs = $sessions[3]->srs;
                    $semestrs[2]->ksr = $sessions[3]->ksr;
                    $semestrs[2]->examen = $sessions[3]->examen;
                    $semestrs[2]->zachet = $sessions[3]->zachet;
                }
                if ($semestrs[2]->examen > 0) {
                    $semestrs[2]->examenhours = $examenhours;
                    $disciplines[$i]->semestrexamen .= $hexnumsemestr;
                } else {
                    $pos = mb_strpos($sessions[3]->vidkontrol, 'Э', 0, 'UTF-8');
                    if (!($pos === false))  {
                        $semestrs[1]->examenhours = $examenhours;
                        $disciplines[$i]->semestrexamen .= $hexnumsemestr;
                    } 
                }
 
                if ($semestrs[2]->zachet > 0) {
                    $semestrs[2]->examenhours = $examenhours;
                    $disciplines[$i]->semestrzachet .= $hexnumsemestr;
                } else {
                    $pos = mb_strpos($sessions[3]->vidkontrol, 'З', 0, 'UTF-8');
                    if (!($pos === false))  {
                        $semestrs[1]->examenhours = $examenhours;
                        $disciplines[$i]->semestrzachet .= $hexnumsemestr;
                    } 
                }

                
                if ($semestrs[2]->ksr > 0) {
                    $disciplines[$i]->semestrkursovik .= $hexnumsemestr;
                } else {
                    $pos = mb_strpos($sessions[3]->vidkontrol, 'Р', 0, 'UTF-8');
                    if (!($pos === false))  {
                        $disciplines[$i]->semestrkursovik .= $hexnumsemestr;
                    } 
                }

    
                /*
                if (!empty($sessions[3]->vidkontrol))    {
                    $semestrs[2]->examenhours = $examenhours;
                }
                */
                 // echo 'semestrs:'; print_object($semestrs);
                
                foreach ($semestrs as $semestr) {
                    if ($semestr->lection != 0 || $semestr->praktika !=0 || $semestr->srs != 0 || $semestr->lab != 0) { 
                        $disciplines[$i]->semestrs[$j] = new stdClass();
                        $disciplines[$i]->semestrs[$j]->numsemestr =  $semestr->numsemestr;
                        $disciplines[$i]->semestrs[$j]->lection =  $semestr->lection;
                        $disciplines[$i]->semestrs[$j]->praktika =  $semestr->praktika;
                        $disciplines[$i]->semestrs[$j]->lab =  $semestr->lab;           
                        $disciplines[$i]->semestrs[$j]->ksr =  $semestr->ksr;
                        $disciplines[$i]->semestrs[$j]->srs =  $semestr->srs;
                        
                        if (isset($semestr->examenhours)) {
                            $disciplines[$i]->semestrs[$j]->examenhours =  $semestr->examenhours;
                        } else {
                            $disciplines[$i]->semestrs[$j]->examenhours =  0;
                        }    
                        
                        $j++;
                    }    
                }    
                
                if (empty($disciplines[$i]->semestrs))  {
                    $attributes = $xmlkurs->attributes();
                    // echo 'KURS:'; print_object ($attributes);
                    $numkurs = get_attribute_value($attributes, 'Ном');
                    $disciplines[$i]->semestrs[0] = new stdClass();
                    $disciplines[$i]->semestrs[0]->numsemestr =  $numkurs*2 -1;
                    $disciplines[$i]->semestrs[0]->lection = (integer)get_attribute_value($attributes, 'Лек', false);
                    $disciplines[$i]->semestrs[0]->praktika =  (integer)get_attribute_value($attributes, 'Пр', false);
                    $disciplines[$i]->semestrs[0]->lab =  (integer)get_attribute_value($attributes, 'Лаб', false);           
                    $disciplines[$i]->semestrs[0]->ksr =  (integer)get_attribute_value($attributes, 'КР', false);
                    $disciplines[$i]->semestrs[0]->srs =  (integer)get_attribute_value($attributes, 'СРС', false);
                    $disciplines[$i]->semestrs[0]->examenhours =  (integer)get_attribute_value($attributes, 'ЧасЭкз', false);
                }    
            } // xmlkurs 
            
            if (empty($disciplines[$i]->sr))    {
                $disciplines[$i]->sr = $crckurs;
            }    
                   
        } // isset($xmldiscipline->Курс   


        $i++;
    }
    
    
    // print_object($disciplines); exit();

         
    // $xmlnormi = $xml->План->Нормы;
    // print_object($xmlnormi);
    
    $cycles2 = array ();
    $i=0;
    if (isset($xml->План->Нормы->ЧасыПоГОС->Цикл))  {
        $xmlnormi = $xml->План->Нормы->ЧасыПоГОС->Цикл;
        foreach ($xmlnormi as $xmlnorm) {
            $attributes = $xmlnorm->attributes();
            $cycles2[$i] = new stdClass();
            $cycles2[$i]->number = get_attribute_value($attributes, 'Ном');
            $cycles2[$i]->hours  = get_attribute_value($attributes, 'Часов');
            $i++;
        }
     }   
    // print_object($cycles2);

    foreach ($cycles as $cycle) {
        foreach ($cycles2 as $cycle2) {
            if ($cycle->number == $cycle2->number)  {
                $cycle->hours = $cycle2->hours;
            }
        }
    }
    // print_object($cycles);

    
    $planweeks = array();
    $i=0;
    if (isset($xml->План->Нормы->Недели->Курс)) {
        $xmlweeks = $xml->План->Нормы->Недели->Курс;
        foreach ($xmlweeks as $xmlweek)   {
            $attributes = $xmlweek->attributes();
            $numkurs = get_attribute_value($attributes, 'Ном');
            $xmlsems = $xmlweek->Семестр;
            foreach ($xmlsems as $xmlsem)   {
                $attributes = $xmlsem->attributes();
                $planweeks[$i] = new stdClass();
                $planweeks[$i]->numkurs = $numkurs;
                $planweeks[$i]->numsemestr = (integer)get_attribute_value($attributes, 'Ном', false);
                $planweeks[$i]->numfirstweek = (integer)get_attribute_value($attributes, 'НомПервойНед', false);
                $i++;
            }
        }
    }    

    $praktiki = array ();
    $i=0;    
    if (isset($xml->План->СпецВидыРабот->УчебПрактики)) {
        $xmlprakt = $xml->План->СпецВидыРабот->УчебПрактики->УчебПрактика;
        add_practice($xmlprakt, $praktiki, $i);
    }
    if (isset($xml->План->СпецВидыРабот->ПрочиеПрактики)) {
        $xmlprakt = $xml->План->СпецВидыРабот->ПрочиеПрактики->ПрочаяПрактика;
        add_practice($xmlprakt, $praktiki, $i);
    }
    if (isset($xml->План->СпецВидыРабот->НИР)) {
        $xmlprakt = $xml->План->СпецВидыРабот->НИР->ПрочаяПрактика;
        add_practice($xmlprakt, $praktiki, $i);
    }    
    
    $praktikinew = array ();    
    $i=0;    
    if (isset($xml->План->СпецВидыРаботНов->Практика->ПрочаяПрактика)) {
        $xmlprakt = $xml->План->СпецВидыРаботНов->Практика->ПрочаяПрактика;
        add_practice_new($xmlprakt, $praktikinew, $i);
    }
    if (isset($xml->План->СпецВидыРаботНов->СимуляционныйКурс->ПрочаяПрактика)) {
        $xmlprakt = $xml->План->СпецВидыРаботНов->СимуляционныйКурс->ПрочаяПрактика;
        add_practice_new($xmlprakt, $praktikinew, $i);
    }
    if (isset($xml->План->СпецВидыРаботНов->Практика->Практика)) {
        $xmlprakt = $xml->План->СпецВидыРаботНов->Практика->Практика;
        add_practice_new($xmlprakt, $praktikinew, $i);
    }    
    
    if (isset($xml->План->СпецВидыРаботНов->НИРДиссер->НИРДиссер)) {
        $xmlprakt = $xml->План->СпецВидыРаботНов->НИРДиссер->НИРДиссер;
        add_practice_new($xmlprakt, $praktikinew, $i);
    }    
    
    if (isset($xml->План->СпецВидыРаботНов->ДиссерПодготовка->ДиссерПодготовка)) {
        $xmlprakt = $xml->План->СпецВидыРаботНов->ДиссерПодготовка->ДиссерПодготовка;
        add_practice_new($xmlprakt, $praktikinew, $i);
    }    
    
    if (isset($xml->План->СпецВидыРаботНов->УчебПрактики->ПрочаяПрактика)) {
        $xmlprakt = $xml->План->СпецВидыРаботНов->УчебПрактики->ПрочаяПрактика;
        add_practice_new($xmlprakt, $praktikinew, $i);
    }
    
    if (isset($xml->План->СпецВидыРаботНов->НИР->ПрочаяПрактика)) {
        $xmlprakt = $xml->План->СпецВидыРаботНов->НИР->ПрочаяПрактика;
        add_practice_new($xmlprakt, $praktikinew, $i);
    }

    if (isset($xml->План->СпецВидыРаботНов->ПрочиеПрактики->ПрочаяПрактика)) {
        $xmlprakt = $xml->План->СпецВидыРаботНов->ПрочиеПрактики->ПрочаяПрактика;
        add_practice_new($xmlprakt, $praktikinew, $i);
    }
    
    // print_object($praktiki);
    // print_object($praktikinew);
    
    $specvidrabot = array ();
    $i=0;
    if (isset($xml->План->СпецВидыРабот->Диплом))   {
        $xmldiplom = $xml->План->СпецВидыРабот->Диплом;
        $attributes = $xmldiplom->attributes();
        $fields = array('РуководствоЧасов', 'РуководствоКаф', 'РецензированиеЧасов', 'РецензированиеКаф');
        foreach ($fields as $field) {
            $specvidrabot[$i] = new stdClass();
            $specvidrabot[$i]->razdel   = 'Диплом';
            $specvidrabot[$i]->field  = $field;
            $specvidrabot[$i]->value  = (integer)get_attribute_value($attributes, $field, false);
            $i++; 
        }

        if (isset($xmldiplom->Консультации)) {
            $xmlkonsults = $xmldiplom->Консультации->Консультация;
            foreach ($xmlkonsults as $xmlkonsult)   {
                $attributes = $xmlkonsult->attributes();                 
                $fields = array('Название', 'Часов', 'Кафедра');
                foreach ($fields as $field) {
                    $specvidrabot[$i] = new stdClass();
                    $specvidrabot[$i]->razdel   = 'ДипломКонсультация';
                    $specvidrabot[$i]->field  = $field;
                    $specvidrabot[$i]->value  = (integer)get_attribute_value($attributes, $field, false);
                    $i++; 
                }
            } 
        }
    }

    if (isset($xml->План->СпецВидыРабот->Диссертация))   {
        $xmldiplom = $xml->План->СпецВидыРабот->Диссертация;
        $attributes = $xmldiplom->attributes();
        $fields = array('РуководствоЧасов', 'РуководствоКаф', 'РецензированиеКаф1', 'РецензированиеКаф2');	
        foreach ($fields as $field) {
            $specvidrabot[$i] = new stdClass();
            $specvidrabot[$i]->razdel   = 'Диссертация';
            $specvidrabot[$i]->field  = $field;
            $specvidrabot[$i]->value  = (integer)get_attribute_value($attributes, $field, false);
            $i++; 
        }
        
        if (isset($xmldiplom->Консультации)) {
            $xmlkonsults = $xmldiplom->Консультации->Консультация;
            foreach ($xmlkonsults as $xmlkonsult)   {
                $attributes = $xmlkonsult->attributes();                 
                $fields = array('Название', 'Часов', 'Кафедра');
                foreach ($fields as $field) {
                    $specvidrabot[$i] = new stdClass();
                    $specvidrabot[$i]->razdel   = 'ДиссертацияКонсультация';
                    $specvidrabot[$i]->field  = $field;
                    $specvidrabot[$i]->value  = (integer)get_attribute_value($attributes, $field, false);
                    $i++; 
                }
            } 
        }
        
    }

    if ($return) {
        $plan->cycles = $cycles;
        $plan->grafiki = $grafiki;
        $plan->disciplines = $disciplines;
        $plan->planweeks = $planweeks;
        $plan->praktiki = array_merge($praktiki, $praktikinew);
        $plan->specvidrabot = $specvidrabot;
        return $plan; 
    }
    /*
     print_object($plan);
     print_object($praktiki);
     print_object($praktikinew);
     print_object($specvidrabot);  
     return 0;
    */
    // print_object($xmlnormi);

    /*
    if ($DB->get_record_select('bsu_plan', "name = '$plan->name'", null, 'id')) {
        echo $OUTPUT->notification("ПЛАН с именем $plan->name уже существует в БД.");
        return 0;
    }
    */

    if ($planid = $DB->insert_record('bsu_plan', $plan)) {
        $data=new stdClass();
        $data->departmentcode=$plan->departmentcode;
        $data->planid=$planid;
        $data->yearid=get_current_edyearid();
        $DB->insert_record('bsu_plan_department_year', $data);
        echo $OUTPUT->notification('ПЛАН добавлен в БД. Id = ' . $planid, 'notifysuccess');

        foreach ($cycles as $cycle) {
            $cycle->planid = $planid;
            $cycle->cycleid =  get_cycleid($cycle->abbrev, $cycle->name);
            if ($DB->insert_record('bsu_plan_cycle', $cycle)) {
                echo $OUTPUT->notification('ЦИКЛ добавлен в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ЦИКЛа в БД.');
            }
        }

        foreach ($grafiki as $grafik)  {
            $grafik->planid = $planid;
            if ($DB->insert_record('bsu_plan_grafikuchprocess', $grafik)) {
                echo $OUTPUT->notification('ГРАФИК добавлен в БД.', 'notifysuccess');
                if (isset($grafik->semestrgraf))    {
                    foreach ($grafik->semestrgraf as $semestrgraf)  {
                        $semestrgraf->planid  = $planid;
                        if ($DB->insert_record('bsu_plan_weeks', $semestrgraf)) {
                            echo $OUTPUT->notification('ГРАФИК СЕМЕСТРОВЫЙ добавлен в БД.', 'notifysuccess');
                        } else {
                            echo $OUTPUT->notification('Ошибка при добавлении ГРАФИКа СЕМЕСТРОВОГО в БД.');
                        }
                    }
                }
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ГРАФИКа в БД.');
            }
        }

        foreach ($disciplines as $discipline)   {
            $discipline->planid = $planid;
            // print_object($discipline);
            if ($disciplineid = $DB->insert_record('bsu_discipline', $discipline)) {
                echo $OUTPUT->notification('ДИСЦИПЛИНА добавлена в БД с id='.$disciplineid, 'notifysuccess');
                foreach ($discipline->credits as $credit)   {
                    $credit->disciplineid = $disciplineid;
                    $credit->CrECTS = str_replace(',', '.', $credit->CrECTS);
                    $credit->zet = str_replace(',', '.', $credit->zet);
                    if ($DB->insert_record('bsu_discipline_creditovkurs', $credit)) {
                        echo $OUTPUT->notification('КредитовПоКурсам добавлен в БД.', 'notifysuccess');
                    } else {
                        print_object($credit);
                        echo $OUTPUT->notification('Ошибка при добавлении КредитовПоКурсам в БД.');
                    }
                }

                if (!isset($discipline->semestrs))  {
                    echo $OUTPUT->notification('<b>ВНИМАНИЕ! У дисциплины ' . $discipline->nname . ' не заданы часы занятий в семестре.</b>');
                } else {

                    foreach ($discipline->semestrs as $semestr)   {
                        $semestr->disciplineid = $disciplineid;
                        if ($DB->insert_record('bsu_discipline_semestr', $semestr)) {
                            echo $OUTPUT->notification('Семестр дисциплины добавлен в БД.', 'notifysuccess');
                        } else {
                            print_object($semestr);
                            echo $OUTPUT->notification('Ошибка при добавлении Семестра дисицплины в БД.');
                        }
                    }
                }    

            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ДИСЦИПЛИНЫ в БД.');
            }
        }

        foreach ($planweeks as $planweek)   {
            $planweek->planid = $planid;
            if ($DB->insert_record('bsu_plan_weeks_normi', $planweek)) {
                echo $OUTPUT->notification('НЕДЕЛЯ добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении НЕДЕЛИ в БД.');
            }
        }

        foreach ($praktiki as $praktik)   {
            $praktik->planid = $planid;
            if ($DB->insert_record('bsu_plan_practice_shacht', $praktik)) {
                echo $OUTPUT->notification('ПРАКТИКА добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ПРАКТИКИ в БД.');
            }
        }

        foreach ($praktikinew as $praktik)   {
            $praktik->planid = $planid;
            if ($practiceid = $DB->insert_record('bsu_plan_practice_shacht', $praktik)) {
                echo $OUTPUT->notification('ПРАКТИКА добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении ПРАКТИКИ в БД.');
            }
            
            if (isset($praktik->semestrs))  {
                foreach ($praktik->semestrs as $semestr)   {
                    $semestr->practiceid = $practiceid;
                    if ($DB->insert_record('bsu_plan_practice_semestr_shacht', $semestr)) {
                        echo $OUTPUT->notification('Семестр практики добавлен в БД.', 'notifysuccess');
                    } else {
                        print_object($semestr);
                        echo $OUTPUT->notification('Ошибка при добавлении Семестра практики в БД.');
                    }
                }
            }  
        }

        foreach ($specvidrabot as $specvidr)   {
            $specvidr->planid = $planid;
            if ($DB->insert_record('bsu_plan_specvidrabot', $specvidr)) {
                echo $OUTPUT->notification('СПЕЦВИДРАБОТА добавлена в БД.', 'notifysuccess');
            } else {
                echo $OUTPUT->notification('Ошибка при добавлении СПЕЦВИДРАБОТ в БД.');
            }
        }

		$apractice = array();
		foreach ($praktiki as $praktik)	{
			if (empty($praktik->term))	{
				if (!empty($praktik->kurs))	{
					$praktik->term = 2*$praktik->kurs;
				} else {
					$praktik->term = 1;
				}
			}
			$index = $praktik->vid . '_' . $praktik->term . '_' . $praktik->week;
			$apractice[$index] = $praktik->hours;
		}
        

		foreach ($praktikinew as $praktik)   {		
			if (isset($praktik->semestrs))  {
				$semestr = reset($praktik->semestrs);
				$praktik->term = $semestr->term;
				$praktik->week = $semestr->planweek;
				$praktik->hours = $semestr->planhour;
			} else {
				$praktik->term = 1;
				$praktik->week = 1;
				$praktik->hours = 0;
			}
			$index = $praktik->vid . '_' . $praktik->term . '_' . $praktik->week;
			$apractice[$index] = $praktik->hours;
		}
        
		
        // print_object($apractice);
        
		foreach ($apractice as $index => $hours)	{
			list($name, $term, $week) = explode ('_', $index);
			$practice = new stdClass();
			$practice->planid = $planid;
			$practice->name = $name;
			$practice->term = $term;
			$practice->week = $week;
			$practice->practicetypeid = find_practicetypeid($name, $plan->edformid);
            $practice->edworkkindid = 13;
            $practice->timemodified = time();
            $practice->modifierid = $USER->id;          
            // print_object($practice);
            
            if ($DB->insert_record('bsu_plan_practice', $practice)) {
                echo $OUTPUT->notification('ЗАПИСЬ добавлена в БД.', 'notifysuccess');
            } else {
				print_object($practice);
                echo $OUTPUT->notification('Ошибка при добавлении в БД.');
            }
		}  
        
        update_ds_plan($planid);   		
        
        echo $OUTPUT->notification("ПЛАН  $planid. {$plan->name} импортирован в систему ИнфоБелГУ:Учебный процесс.", 'notifysuccess');
    } else {
        echo $OUTPUT->notification('Ошибка при добавлении ПЛАНА в БД.');
    }


    // echo $OUTPUT->single_button(new moodle_url("view.php", $options), get_string("downloadtext"));
}



function add_practice($xmlprakt, &$praktiki, &$i)
{         
    foreach ($xmlprakt as $xmlpr) {
        $attributes = $xmlpr->attributes();
        // print_object($attributes);
        $praktiki[$i] = new stdClass();
        $praktiki[$i]->vid   =  (string)get_attribute_value($attributes, 'Вид', false);
        $praktiki[$i]->kurs  = (integer)get_attribute_value($attributes, 'Курс', false);
        $praktiki[$i]->term  = (integer)get_attribute_value($attributes, 'Сем', false);
        $praktiki[$i]->week  = (integer)get_attribute_value($attributes, 'Нед', false);
        $praktiki[$i]->summa = (integer)get_attribute_value($attributes, 'Суммировать', false);
        $praktiki[$i]->hours = (float)get_attribute_value($attributes, 'Часов', false);
        $praktiki[$i]->vidnormativa = (integer)get_attribute_value($attributes, 'ВидНорматива', false);
        $praktiki[$i]->vidnormativa2 = (integer)get_attribute_value($attributes, 'ВидНорматива2', false); 
        $praktiki[$i]->hours2 = (integer)get_attribute_value($attributes, 'Часов2', false);
        $i++;
    }
}

function add_practice_new($xmlprakt, &$praktiki, &$i)
{         
    foreach ($xmlprakt as $xmlpr) {
        $attributes = $xmlpr->attributes();
        // print_object($attributes);
        $praktiki[$i] = new stdClass();
        $praktiki[$i]->vid =  (string)get_attribute_value($attributes, 'Наименование', false);
        $praktiki[$i]->typepr =  (string)get_attribute_value($attributes, 'Тип', false);
        $hoursinzet = (string)get_attribute_value($attributes, 'ЧасовВЗЕТ', false);
        $praktiki[$i]->hoursinzet =  str_replace(',', '.', $hoursinzet);
        $praktiki[$i]->zetinweek =  (string)get_attribute_value($attributes, 'ЗЕТвНеделе', false);
        $praktiki[$i]->zetinweek = str_replace(',', '.', $praktiki[$i]->zetinweek);
        $praktiki[$i]->zetexpert =  (string)get_attribute_value($attributes, 'ЗЕТэкспертное', false);
        $praktiki[$i]->zetexpert = str_replace(',', '.', $praktiki[$i]->zetexpert);
        $praktiki[$i]->learningekz =  (string)get_attribute_value($attributes, 'ИзученоЭкз', false);
        $praktiki[$i]->learningzachet  =  (string)get_attribute_value($attributes, 'ИзученоЗач', false);
        $praktiki[$i]->learningdiffzachet  =  (string)get_attribute_value($attributes, 'ИзученоЗачO', false);
        $praktiki[$i]->perezachethour =  (string)get_attribute_value($attributes, 'ПерезачетЧасов', false);
        $praktiki[$i]->competition =  (string)get_attribute_value($attributes, 'Компетенции', false);
        $praktiki[$i]->isnew = 1; 

        if (isset($xmlpr->Семестр))  {
            $xmlsemesters = $xmlpr->Семестр;
            $praktiki[$i]->semestrs = array();
            $j=0;
            foreach ($xmlsemesters as $xmlsemester) {
                $attributes = $xmlsemester->attributes();
                $praktiki[$i]->semestrs[$j] = new stdClass();
                $praktiki[$i]->semestrs[$j]->term =  (integer)get_attribute_value($attributes, 'Ном', false);
                $praktiki[$i]->semestrs[$j]->planweek =  (integer)get_attribute_value($attributes, 'ПланНед', false);
                $praktiki[$i]->semestrs[$j]->planday =  (integer)get_attribute_value($attributes, 'ПланДней', false);
                $praktiki[$i]->semestrs[$j]->planhour =  (integer)get_attribute_value($attributes, 'ПланЧасов', false);
                $praktiki[$i]->semestrs[$j]->planhoursaud =  (integer)get_attribute_value($attributes, 'ПланЧасовАуд', false);
                $praktiki[$i]->semestrs[$j]->planhourssrs =  (integer)get_attribute_value($attributes, 'ПланЧасовСРС', false);    
                $planzet = (string)get_attribute_value($attributes, 'ПланЗЕТ', false);
                $praktiki[$i]->semestrs[$j]->planzet  = str_replace(',', '.', $planzet);
                $praktiki[$i]->semestrs[$j]->ekz =  (integer)get_attribute_value($attributes, 'Экз', false);    
                $praktiki[$i]->semestrs[$j]->zachet =  (integer)get_attribute_value($attributes, 'Зач', false);    
                $praktiki[$i]->semestrs[$j]->zachetdiff =  (integer)get_attribute_value($attributes, 'ЗачО', false);
                $j++;    
            }
        }    
        $i++;
    }
}



function find_practicetypeid($name, $edformid)
{
    global $DB;
    
    $id = 1;
    
    $sql = "SELECT id FROM mdl_bsu_ref_practice_type where name like '$name' and edformid=$edformid";
    
    if ($practicetypes =  $DB->get_records_sql($sql))  {
        $practicetype = reset ($practicetypes);
        $id = $practicetype->id;
    } else {
        $names = explode (' ', $name); 
        $sql = "SELECT id FROM mdl_bsu_ref_practice_type where name like '{$names[0]}%' and edformid=$edformid";
        // echo $sql;
        if ($practicetypes =  $DB->get_records_sql($sql))  {
            $practicetype = reset ($practicetypes);
            $id = $practicetype->id;
        }    
    }
    
    return $id;
}


function get_esse_kontr_ref_rgr_kp($avalue, &$semestrkp, &$semestresse, &$semestrkontr, &$semestrref, &$semestrrgr)
{
 
    // для тестирования 
    /*
    global $DB;
    $sql = "SELECT distinct semestrkp FROM mdl_bsu_discipline";
    $datas = $DB->get_records_sql($sql);
    $symbol = array('экрг');
    foreach ($datas as $data)   {
        $avalue = $data->semestrkp.'0';
        $semestrkp = $semestresse = $semestrkontr = $semestrref = $semestrrgr = '';
        $a = str_split_php4_utf8($avalue);
        $m = count($a);
        notify($avalue);
        print_object($a);
        for ($i=0; $i<$m-1;) {
                echo $a[$i+1] . '!!<br />';
                switch($a[$i+1])    {
                    case 'э': $semestresse .= $a[$i]; $i+=2;
                    break;
                    case 'к': $semestrkontr .= $a[$i]; $i+=2;
                    break;
                    case 'р': $semestrref .= $a[$i]; $i+=2;
                    break; 
                    case 'г': $semestrrgr .= $a[$i]; $i+=2;
                    break;
                    default:  $semestrkp .= $a[$i];  $i++;    
                }
        } 
        echo "$semestrkp = $semestresse = $semestrkontr = $semestrref = $semestrrgr<br />";
    } 
    */
    $avalue .= '0';
    $a = str_split_php4_utf8($avalue);
    $m = count($a);
    // notify($avalue);  print_object($a);
    for ($i=0; $i<$m-1;) {
            // echo $a[$i+1] . '!!<br />';
            switch($a[$i+1])    {
                case 'э': $semestresse .= $a[$i]; $i+=2;
                break;
                case 'к': $semestrkontr .= $a[$i]; $i+=2;
                break;
                case 'р': $semestrref .= $a[$i]; $i+=2;
                break; 
                case 'г': $semestrrgr .= $a[$i]; $i+=2;
                break;
                default:  $semestrkp .= $a[$i];  $i++;    
            }
    } 
}


function str_split_php4_utf8($str) { 
     // place each character of the string into and array 
     $split=1; 
     $array = array(); 
     for ( $i=0; $i < strlen( $str ); ){ 
         $value = ord($str[$i]); 
         if($value > 127){ 
             if($value >= 192 && $value <= 223) 
                 $split=2; 
             elseif($value >= 224 && $value <= 239) 
                 $split=3; 
             elseif($value >= 240 && $value <= 247) 
                 $split=4; 
         }else{ 
             $split=1; 
         } 
             $key = NULL; 
         for ( $j = 0; $j < $split; $j++, $i++ ) { 
             $key .= $str[$i]; 
         } 
         array_push( $array, $key ); 
     } 
     return $array; 
}



function update_ds_plan($planid)    
{
    global $DB;
              
    $sql = "SELECT * FROM mdl_bsu_discipline d
            WHERE d.planid=$planid  
            group by d.id";
    // echo $sql . '<br />';                // and disciplinenameid=51
    if ($datas = $DB->get_records_sql($sql))  {
		foreach ($datas as $discipline) 	{
		    // print_object($discipline);
            $semestrs = $DB->get_records_select('bsu_discipline_semestr', "disciplineid=$discipline->id and numsemestr>0", null, 'numsemestr', 
                        'numsemestr, id, disciplineid, zachet, zachetdiff, examen, kp, kr, referat, kontr');
            // print_object($semestrs);
            $fields = array('examen', 'zachet', 'zachetdiff', 'kr', 'kp', 'referat', 'kontr');
		  
            $alltermsinkontrol = get_all_terms_discipline_in_kontrol($discipline);
            foreach ($alltermsinkontrol as $term => $kontrol)  {
                $alltermsinkontrol[$term]->id = 0;
                $alltermsinkontrol[$term]->numsemestr = $term;    
                $alltermsinkontrol[$term]->disciplineid = $discipline->id;           
            }     
            
            foreach ($alltermsinkontrol as $term => $kontrol)  {
                if (isset($semestrs[$term]))    {
                    $alltermsinkontrol[$term]->id = $semestrs[$term]->id;
                }
            }
            
            foreach ($alltermsinkontrol as $term => $kontrol)  {
                // print_object($alltermsinkontrol[$term]);
                if ($alltermsinkontrol[$term]->id == 0) {
                    // print_object($discipline);
                    // print_object($semestrs);
                    // print_object($alltermsinkontrol[$term]);
                    // notify('Insert');
                    if (!$DB->insert_record('bsu_discipline_semestr', $alltermsinkontrol[$term]))    {
                        print_object($alltermsinkontrol[$term]);
                        print_error('Ошибка обновления записи в bsu_discipline_semestr');
                    }
                    // print '<hr>';
                } else {
                    // notify('Update');
                    if(!$DB->update_record('bsu_discipline_semestr', $alltermsinkontrol[$term]))    {
                        print_object($alltermsinkontrol[$term]);
                        print_error('Ошибка обновления записи в bsu_discipline_semestr');
                    }
                }
                // print '<hr>';
            }     
            
		}
    }
}
?>
