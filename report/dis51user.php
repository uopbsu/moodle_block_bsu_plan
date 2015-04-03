<?php
    
    require_once("../../../config.php");
    require_once("../lib_plan.php");
    
    require_login();
    // $context = get_context_instance(CONTEXT_SYSTEM, 0);
    
    if( !( is_siteadmin($USER) OR ($USER->id == 6702) OR ($USER->id == 66340) ) ){
        notice('Нет доступа!', 'index.php');
    }
    
    $term   = optional_param('term', 0, PARAM_INT );
    $action = optional_param('action', null, PARAM_TEXT);
    $tab    = optional_param('tab', 'v', PARAM_TEXT );
    $yearid = optional_param('yearid', 0, PARAM_INT );
    $subgroupid = optional_param('subgroupid', 0, PARAM_INT );
    $fidall = optional_param('fidall', 0, PARAM_INT);					// id department

    $scriptname = "dis51user.php";
                             
    $frm = data_submitted();
    if( isset($frm->addselect ) ){
        if($subgroupid <> 0){
            echo "Добавляем<br>";
                    
            foreach($frm->addselect AS $addselect){
                if( $groupmember = $DB->get_record('bsu_group_members', array('id' => $addselect) ) ){
                    
                    $object = new stdClass();
                    $object->username = $groupmember->username;
                    $object->subgroupid = $subgroupid;
                    $object->deleted = 0;
                    if( $zapis = $DB->get_records_sql("SELECT id, username, subgroupid, deleted
                                                       FROM mdl_bsu_discipline_subgroup_members
                                                       WHERE username = {$object->username} AND 
                                                             subgroupid = {$object->subgroupid}
                                                       ") ){
                        foreach($zapis AS $zapi){
                            $object->id = $zapi->id;
                            $DB->update_record('bsu_discipline_subgroup_members', $object);
                        }
                    }else{
                        $DB->insert_record('bsu_discipline_subgroup_members', $object);
                    }
                    
                    if ($cntstud = $DB->count_records_select('bsu_discipline_subgroup_members', "subgroupid=$object->subgroupid and deleted=0")) {
                        $DB->set_field_select('bsu_discipline_subgroup', 'countstud', $cntstud, "id=$object->subgroupid");
                        // recalculate_zachet_and_examen_for_subgroup($frm->yid, $frm->fid, $frm->gid, $frm->did, $frm->subgr, $cntstud);    
                        // recalculate_kursovik_for_subgroup($frm->yid, $frm->fid, $frm->gid, $frm->did, $frm->subgr, $cntstud, $frm->eid);
                    }    
                }
            }
            redirect( "dis51user.php?tab=e&term={$term}&yearid={$yearid}&subgroupid={$subgroupid}", 'Добавлено!', 5);    
        }        
    }
    
    if( isset($frm->removeselect ) ){
        
        echo "Удаляем<br>";
        
        foreach($frm->removeselect AS $removeselect){
            
            $object = new stdClass();
            $object->id = $removeselect;
            $object->deleted = 1;
            $DB->update_record('bsu_discipline_subgroup_members', $object);

            if ($cntstud = $DB->count_records_select('bsu_discipline_subgroup_members', "subgroupid=$subgroupid and deleted=0")) {
                $DB->set_field_select('bsu_discipline_subgroup', 'countstud', $cntstud, "id=$subgroupid");
                // recalculate_zachet_and_examen_for_subgroup($frm->yid, $frm->fid, $frm->gid, $frm->did, $frm->subgr, $cntstud);    
                // recalculate_kursovik_for_subgroup($frm->yid, $frm->fid, $frm->gid, $frm->did, $frm->subgr, $cntstud, $frm->eid);
            }    
        }
        redirect( "dis51user.php?tab=e&term={$term}&yearid={$yearid}&subgroupid={$subgroupid}", 'Удалено!', 5);
    }
        
    if($action == 'toexsel' AND $term <> 0 ){
        
        $fn = "f".$term ;
        
        $table = html_writer::table(dis51user($term, $yearid, 1));
        
        $header = '
        <html xmlns:v="urn:schemas-microsoft-com:vml"
        xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:x="urn:schemas-microsoft-com:office:excel"
        xmlns="http://www.w3.org/TR/REC-html40">
        
        <head>
        <meta name="Excel Workbook Frameset">
        <meta http-equiv=Content-Type content="text/html; charset=utf-8">
        <meta name=ProgId content=Excel.Sheet>
        <meta name=Generator content="Microsoft Excel 14">
        </head>
        
        <body>'.$table.'</body></html>'.
        

	    $fn = $fn.'report.xls';
		header("Content-type: application/vnd.ms-excell");
		header("Content-Disposition: attachment; filename=\"{$fn}\"");
		header("Expires: 0");
		header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
		header("Pragma: public");
		print $table;
        exit();        
    }

    $strtitle = "Подгруппы по дисциплине Физическая культура";
    
    $PAGE->set_url('/blocks/bsu_reiting//index.php');
    $PAGE->set_context(null);
    $PAGE->set_heading($SITE->fullname);
    if( $tab == 'v' ){
        $PAGE->navbar->add($strtitle);
    }else{
        $PAGE->navbar->add($strtitle, $CFG->wwwroot."/blocks/ bsu_charge/dis51user.php?tab=v&yearid={$yearid}&term={$term}");
        $PAGE->navbar->add('Редактирование'); 
    }
    
    echo $OUTPUT->header();
    
    echo $OUTPUT->heading($strtitle, 2, 'headingblock header');
        
        echo "<center>".
             html_writer::start_tag('table', array( 'border' => 0 ) );
                echo html_writer::start_tag('tr');
                    echo html_writer::start_tag('td', array( 'width' => '100' ,  'valign' => 'top') );
                        echo " <p align = right ><b> Учебный год : </b></p>";
                    echo html_writer::end_tag('td');
                    echo html_writer::start_tag('td', array( 'valign' => 'top') );
                        echo selectyearid("dis51user.php?tab={$tab}&term={$term}", $yearid);
                    echo html_writer::end_tag('td');
                echo html_writer::end_tag('tr');
            echo html_writer::end_tag('table');
        echo "</center>";
        if($yearid <> 0){
            $toprow[] = new tabobject('1', "dis51user.php?tab=v&yearid={$yearid}&term=1", '1 семестр');
            $toprow[] = new tabobject('2', "dis51user.php?tab=v&yearid={$yearid}&term=2", '2 семестр');
            $toprow[] = new tabobject('3', "dis51user.php?tab=v&yearid={$yearid}&term=3", '3 семестр');
            $toprow[] = new tabobject('4', "dis51user.php?tab=v&yearid={$yearid}&term=4", '4 семестр');
            $toprow[] = new tabobject('5', "dis51user.php?tab=v&yearid={$yearid}&term=5", '5 семестр');
            $toprow[] = new tabobject('6', "dis51user.php?tab=v&yearid={$yearid}&term=6", '6 семестр');
        
            $tabs = array($toprow);
        
            print_tabs($tabs, $term, NULL, NULL);
            
            echo $OUTPUT->box_start('generalbox sitetopic');
            
            switch($tab){
        
                case 'v':
        
                    if($term <> 0){
                        ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
                        @set_time_limit(0);
                        @ob_implicit_flush(true);
                        @ob_end_flush();
                        
                       	echo '<table class="generalbox" border=1 cellspacing="0" cellpadding="0" align="center">';
                        listbox_all_department($scriptname."?tab=$tab&term=$term&yearid=$yearid", $fidall);
                        echo '</table>';
                        
                        if ($fidall > 0)    {
                        
                            echo '<div align=center>' . html_writer::table(dis51user($term, $yearid,0,$fidall)) . '</div>';
                            
                            echo '<center>
                                     <form method="post">
                                        <input type="submit" name="EXCEL" value="Скачать в формате EXCEL"></center>
                                        <input type="hidden" name="action" value="toexsel" />
                                     </form>
                                 <center>';  
                        }                 
                    }
                 break;
                 case 'e':

                    $groupid = 0; 
                    $namesubgroup = "";
                    $namegroup = "";
                    
                    if( $subgroup = $DB->get_record('bsu_discipline_subgroup', array('id' => $subgroupid)) ){
                        
                        $namesubgroup = "".$subgroup->name;
                        
                        if( $group = $DB->get_record('bsu_ref_groups', array('id' => $subgroup->groupid)) ){
                            $namegroup = "".$group->name;
                            $groupid =  $group->id;
                        }
                    }

                    $studentscount = 0;
                    if( $students = $DB->get_records_sql("SELECT dsm.id AS id, dsm.username, dsm.subgroupid, u.lastname, u.firstname
                                                          FROM {bsu_discipline_subgroup_members} AS dsm
                                                          INNER JOIN {user} AS u
                                                          ON u.username = dsm.username
                                                          WHERE dsm.subgroupid = {$subgroupid} and dsm.deleted=0 
                                                          ORDER BY u.lastname
                                                          ") ){
                        $studentscount = count($students);
                        
                    }

                    $usercount = 0;
                    if( $disciplinesubgroup = $DB->get_record('bsu_discipline_subgroup', array('id' => $subgroupid ) ) ){

                        $sql = "SELECT gm.id AS id, gm.username, u.lastname, u.firstname
                                FROM {bsu_group_members} AS gm
                                INNER JOIN {user} AS u ON u.username = gm.username
                                WHERE gm.deleted=0 and gm.groupid = {$groupid} AND 
                                      gm.username not in (SELECT dsm.username
                                                          FROM {bsu_discipline_subgroup_members} AS dsm
                                                          WHERE dsm.deleted=0 AND 
                                                                dsm.subgroupid in (SELECT id
                                                                                   FROM {bsu_discipline_subgroup}
                                                                                   WHERE yearid = {$disciplinesubgroup->yearid} AND 
                                                                                         groupid = {$disciplinesubgroup->groupid} AND 
                                                                                         disciplineid = {$disciplinesubgroup->disciplineid}
                                                                                   ) 
                                                          )
                                ORDER BY u.lastname
                                ";
                        if($searchusers = $DB->get_records_sql($sql) ){
                            
                            $usercount = count($searchusers);
                            
                        }     
                    }
                    
  

                       ?>
    
                <?php  
                echo "<form name='studentform' id='studentform' method='post' action='dis51user.php?tab=e&term={$term}&yearid={$yearid}&subgroupid={$subgroupid}'>"
                ?>
                <input type="hidden" name="tab" value="<?php echo $tab ?>" />
                <input type="hidden" name="term" value="<?php echo $term ?>" />
                <input type="hidden" name="yearid" value="<?php echo $yearid ?>" />
                <input type="hidden" name="subgroupid" value="<?php echo $subgroupid ?>" />
                  <table align="center" border="0" cellpadding="5" cellspacing="0">
                    <tr>
                      <td valign="top">
                          <?php
                              echo  "Студенты подгруппы <b> {$namesubgroup} </b> ({$studentscount})" ;
                          ?>
                      </td>
                      <td></td>
                      <td valign="top">
                          <?php
                              echo  "Нераспределённые студенты группы <b> {$namegroup} </b> ({$usercount})";
                          ?>
                      </td>
                    </tr>
                    <tr>
                      <td valign="top">
                          <select name="removeselect[]" size="20" id="removeselect" multiple
                                  onFocus="document.studentform.add.disabled=true;
                                           document.studentform.remove.disabled=false;
                                           document.studentform.addselect.selectedIndex=-1;">
                          <?php
                              if (!empty($students)) {
                                  foreach ($students as $student) {
                                      if( usersubgroupnotingroup ($student->username, $groupid) ){
                                          echo "<option value=\"{$student->id}\">".
                                                   $student->lastname." ".$student->firstname." (!!!ОТСУТСТВУЕТ В ГРУППЕ!!!) ".
                                               "</option>\n";
                                      }else{
                                          echo "<option value=\"{$student->id}\">".
                                                   $student->lastname." ".$student->firstname."  ".
                                               "</option>\n";
                                      }
                                  }
                              }
                          ?>
                
                          </select>
                      </td>
                      <td valign="top">
                        <br />
                        <input name="add" type="submit" id="add" value="&larr; Добавить" />
                        <br>
                        <input name="remove" type="submit" id="remove" value="Удалить &rarr;" />
                        <br />
                        
                        <?php
                            

                            
                        ?>                    
                        
                      </td>
                      <td valign="top">
                          <select name="addselect[]" size="21" id="addselect" multiple
                                  onFocus="document.studentform.add.disabled=false;
                                           document.studentform.remove.disabled=true;
                                           document.studentform.removeselect.selectedIndex=-1;">
                          <?php
                          echo "<optgroup label=\"$strsearchresults\"  >\n";
                            foreach($searchusers AS $searchuser){
                                echo "----";//$searchuser->lastname;
                                echo "<option value={$searchuser->id}>".
                                        $searchuser->lastname." ".$searchuser->firstname.
                                     " </option>\n";
      
                            }
                         ?>
                         </select>
                       </td>
                    </tr>
                  </table>
                </form>
                
                <?php
                       
                 ///Таблица Распределённых
                 $table = new html_table();
                 //$table->width = '100%' ;
                 $table->rowclasses = array();
                 $table->data = array();
                 $sql = "SELECT dsm.id, dsm.username, dsm.subgroupid, ds.name, u.lastname, u.firstname
                         FROM {bsu_discipline_subgroup_members} AS dsm
                         INNER JOIN {bsu_discipline_subgroup} AS ds ON ds.id = dsm.subgroupid
                         INNER JOIN {user} AS u ON u.username = dsm.username
                         WHERE dsm.deleted=0 AND
                               dsm.subgroupid in (SELECT id
                                                  FROM {bsu_discipline_subgroup}
                                                  WHERE yearid = {$disciplinesubgroup->yearid} AND
                                                        groupid = {$disciplinesubgroup->groupid} AND
                                                        disciplineid = {$disciplinesubgroup->disciplineid}
                                                  )
                        ORDER BY  u.lastname
                        ";
                 if( $rasprs = $DB->get_records_sql($sql) ){
                     echo $OUTPUT->heading("Распределённые студенты группы :", 2, 'headingblock header');
                     foreach($rasprs AS $raspr ){
                        $table->data[] = array( $raspr->lastname." ".$raspr->firstname." (".$raspr->name.") ");
                     }
                     echo html_writer::table($table); 
                 }
                 
                      
                 
                 ///Таблица Нераспределённых  
                 echo $OUTPUT->heading("Нераспределённые студенты группы :", 2, 'headingblock header');
                 
                 $table = new html_table();
                 //$table->width = '100%' ;
                 //$table->head  = array('Группа', 'Подгруппа', 'ФИО');
                 //$table->align = array('left', 'left', 'left');
                 $table->rowclasses = array();
                 $table->data = array();
                 foreach($searchusers AS $searchuser ){
                    $table->data[] = array( $searchuser->lastname." ".$searchuser->firstname );
                 }      
                 echo html_writer::table($table);
                       
                 break;
            }  
            
            echo $OUTPUT->box_end();
               
        }

    echo $OUTPUT->footer(); 

    function usersubgroupnotingroup ($username, $groupid){
    global $DB;
        $result = 1;

        $sql = "SELECT gm.id AS id, gm.username
                FROM {bsu_group_members} AS gm
                WHERE gm.groupid = {$groupid} AND 
                      gm.username = '{$username}' 
                                              ";
                                            
        if($DB->get_records_sql($sql) ){
            
            $result = 0;
            
        } 
      
        return $result; 
    }


    function selectyearid($scriptname, $yearid){
    global $OUTPUT, $DB;
        
        $selectyear = array();
        $selectyear[0] = "Выберите ...";
        $textsql = " SELECT Id, EdYear, God
                      FROM {bsu_ref_edyear}
                      WHERE Id >= 14
                      ORDER BY God 
                      ";
        if( $years = $DB->get_records_sql($textsql) ){
            foreach($years as $year) {
                $selectyear[$year->id] = $year->god." (".$year->edyear.")";
            }
        }

        return $OUTPUT->single_select($scriptname, 'yearid', $selectyear, $yearid, null, 'switchyearid');
        
    }

    function dis51user($term, $yearid, $excel=0, $fid){
    global $DB, $CFG;

        $table = new html_table();
        //$table->width = '100%' ;
        //$table->head  = array('Группа', 'Подгруппа', 'ФИО');
        $table->align = array('left', 'left', 'left');
        $table->rowclasses = array();
        $table->data = array();
        
        
        if( $departments = $DB->get_records_sql("SELECT rd.id, rd.departmentcode , rd.name
                                                 FROM  mdl_bsu_ref_department rd
                                                 inner join mdl_bsu_ref_department_year rdy using(departmentcode)
                                                 where rdy.yearid=$yearid and rd.departmentcode=$fid
                                                 order by rd.name") ){
            foreach($departments AS $department){
                
                $table->data[] = array("&nbsp;", "&nbsp;" , "<b>".$department->name."</b>");
                
                //for( $term = 1; $term <= 1; $term++ ){
                    $table->data[] = array("&nbsp;", '<b>Семестр : '.$term."</b>" , "&nbsp;"); 
                    $textsql = "SELECT m.id, m.planid, m.disciplinenameid, m.term, m.edworkkindid, m.yearid
                                FROM {bsu_discipline_stream_mask} AS m
                                INNER JOIN {bsu_plan} AS p
                                ON p.id = m.planid
                                WHERE m.disciplinenameid = 51 AND m.yearid = {$yearid} AND m.term = {$term} and m.edworkkindid=3 AND
                                      p.departmentcode = {$department->departmentcode}  
                                
                                ";
                                // echo $department->departmentcode." ";
                    // print $textsql;
                    if( $streammasks = $DB->get_records_sql($textsql) ){
                        foreach($streammasks AS $streammask){
                            
                            if (!$DB->record_exists_select('bsu_edwork', "streammaskid=$streammask->id")) continue;
                            
                            $textsql = "SELECT id, streammaskid, groupid, subgroupid, numstream
                                        FROM {bsu_discipline_stream}
                                        WHERE streammaskid = {$streammask->id}
                                        ORDER BY numstream ";
                            if( $disciplinestreams = $DB->get_records_sql($textsql) ){
                                
                                $namefaculty = " ";
                                $nameplan = " ";
                                if( $facs = $DB->get_records_sql("SELECT p.id AS id, p.departmentcode, p.name AS pname, vw.name AS fname
                                                                FROM {bsu_plan} AS p
                                                                INNER JOIN {bsu_ref_department} AS vw
                                                                ON vw.DepartmentCode = p.departmentcode
                                                                WHERE p.id = {$streammask->planid} 
                                                                ") ){
                                                                    //AND vw.id = {$department->id}
                                    foreach($facs AS $fac){
                                        $namefaculty = " Факультет : ".$fac->fname;
                                        $nameplan = $fac->pname;
                                    }                            
                                }
                                
                                $table->data[] = array('<i><b>План : '.$streammask->planid."</b></i>" , '', '<i><b>'.$namefaculty.'</b></i>'); // $nameplan
                                
                                foreach($disciplinestreams AS $disciplinestream){
                                    
                                    
                                    if( $disciplinestream->groupid <> 0 AND $disciplinestream->subgroupid <> 0 ){
                                        
                                        $textsql = "SELECT ds.id, ds.groupid, ds.disciplineid, ds.name AS podgupname, rg.name AS groupname, 
                                                    ds.countstud as dscountstud, rg.countstud as rgcountstud   
                                                    FROM {bsu_discipline_subgroup} AS ds
                                                    INNER JOIN {bsu_ref_groups} AS rg
                                                    ON rg.id = ds.groupid
                                                    WHERE ds.notusing = 0 AND ds.yearid = {$yearid} AND ds.groupid = {$disciplinestream->groupid}
                                                          AND ds.id = {$disciplinestream->subgroupid} ";
                                        if( $disciplinesubgroups = $DB->get_records_sql($textsql)  ){
                                            $table->data[] = array( "Поток: ".$disciplinestream->numstream, "&nbsp;", "&nbsp;" );
                                            
                                            foreach($disciplinesubgroups AS $disciplinesubgroup){
                                                if ($excel) {
                                                    $table->data[] = array( "Группа: ".$disciplinesubgroup->groupname . " ($disciplinesubgroup->rgcountstud ст.)" , 
                                                                            "Подгруппа: ".$disciplinesubgroup->podgupname  . " ($disciplinesubgroup->dscountstud ст.) ",
                                                                            "" );
                                                } else {
                                                    $table->data[] = array( "Группа: ".$disciplinesubgroup->groupname . " ($disciplinesubgroup->rgcountstud ст.)" , 
                                                                                "<a target = '_blank' href='dis51user.php?tab=e&term={$term}&yearid={$yearid}&subgroupid={$disciplinesubgroup->id}' >".
                                                                                "Подгруппа: ".$disciplinesubgroup->podgupname  . " ($disciplinesubgroup->dscountstud ст.) " . 
                                                                                html_writer::empty_tag('img', array( 'src' => $CFG->wwwroot."/blocks/bsu_aspirant/i/edit.gif", 'height' => "16" ,  'width' => "16" ) ).
                                                                                "</a>",
                                                                                "" );
                                                    
                                                }                                                                
                                                
                                                if( $subgroupmembers = $DB->get_records_sql("SELECT @i := @i + 1 AS ids,
                                                                                                    dsm.id AS id, dsm.username, dsm.subgroupid, u.lastname, u.firstname
                                                                                             FROM (select @i := 0) AS z, {bsu_discipline_subgroup_members} AS dsm
                                                                                             INNER JOIN {user} AS u
                                                                                             ON u.username = dsm.username
                                                                                             WHERE dsm.subgroupid = {$disciplinesubgroup->id} and dsm.deleted=0 
                                                                                             ORDER BY u.lastname") ){
                                                    $ii = 1;
                                                    foreach($subgroupmembers AS $subgroupmember){
                                                        $table->data[] = array( "&nbsp;", "&nbsp;", $ii++ . '. ' . $subgroupmember->lastname." ".$subgroupmember->firstname );
                                                    }
                                                }
                                            }                                    
                                        }   
                                    }
                                    
                                    if( $disciplinestream->groupid <> 0 AND $disciplinestream->subgroupid == 0 ){
                                        
                                        $textsql = "SELECT id , name AS groupname 
                                                    FROM {bsu_ref_groups}
                                                    WHERE id = {$disciplinestream->groupid} ";
                                        if( $disciplinesubgroup = $DB->get_record_sql($textsql)  ){
                                            $table->data[] = array( "Поток: ".$disciplinestream->numstream, "&nbsp;", "&nbsp;" );
        
                                            $table->data[] = array( "Группа: ".$disciplinesubgroup->groupname, "&nbsp;", "&nbsp;" );
                                            
                                            if( $subgroupmembers = $DB->get_records_sql("SELECT gm.id AS id, gm.username, u.lastname, u.firstname
                                                                                         FROM {bsu_group_members} AS gm
                                                                                         INNER JOIN {user} AS u
                                                                                         ON u.username = gm.username
                                                                                         WHERE gm.groupid = {$disciplinestream->groupid} and gm.deleted=0 
                                                                                         ORDER BY u.lastname") ){
                                                $ii = 1;
                                                foreach($subgroupmembers AS $subgroupmember){
                                                    $table->data[] = array( "&nbsp;", "&nbsp;", $ii++ .  '. ' . $subgroupmember->lastname." ".$subgroupmember->firstname );
                                                }
                                            }
                                        } 
                                    }
                                    $table->data[] = array("&nbsp;", "&nbsp;" , "&nbsp;");
                                }
                                $table->data[] = array("&nbsp;", "&nbsp;" , "&nbsp;");
                            }
                        }                
                    }
                    $table->data[] = array("&nbsp;", "&nbsp;" , "&nbsp;"); 
                    //} 

                    $startyear = 2000 + $yearid - round($term / 2);
                    //echo  $startyear ."<br>";
                    //////////////////

                    $sqltext = "SELECT @i := @i + 1 AS id, rg.name AS groupname, pg.planid, d.id as did, rg.id  AS groupid ,ds.id as subgroupid, ds.name AS subgroupname,
                                       rg.countstud AS gcountstud, ds.countstud AS sgcountstud 
                                FROM (select @i:=0) AS z, mdl_bsu_ref_groups rg
                                inner join mdl_bsu_plan_groups pg on rg.id = pg.groupid
                                inner join mdl_bsu_discipline d using (planid)
                                inner join mdl_bsu_discipline_subgroup ds on d.id=ds.disciplineid AND ds.groupid = rg.id
                                where rg.yearid={$yearid} and rg.departmentcode={$department->departmentcode} and startyear = {$startyear} and d.disciplinenameid=51 AND
                                      ds.id not in (SELECT s.subgroupid
                                                    FROM mdl_bsu_discipline_stream AS s
                                                    INNER JOIN mdl_bsu_discipline_stream_mask AS m
                                                    ON s.streammaskid = m.id
                                                    INNER JOIN mdl_bsu_plan AS p
                                                    ON p.id = m.planid
                                                    WHERE m.disciplinenameid = 51 AND m.yearid = {$yearid} AND m.term = {$term} and m.edworkkindid=3 AND
                                                          p.departmentcode = {$department->departmentcode})
                                UNION
                                SELECT @i := @i + 1 AS id, rg.name AS groupname, pg.planid, 0 AS did, rg.id AS groupid ,0 AS subgroupid, ' ' AS subgroupname,
                                       rg.countstud AS gcountstud, 0 AS sgcountstud
                                FROM (select @i:=0) AS z, mdl_bsu_ref_groups rg
                                inner join mdl_bsu_plan_groups pg on rg.id = pg.groupid
                                inner join mdl_bsu_discipline d using (planid)
                                where rg.yearid={$yearid} and rg.departmentcode={$department->departmentcode} and rg.startyear = {$startyear} and d.disciplinenameid=51 AND
                                      rg.id not in (SELECT s.groupid
                                                    FROM mdl_bsu_discipline_stream AS s
                                                    INNER JOIN mdl_bsu_discipline_stream_mask AS m
                                                    ON s.streammaskid = m.id
                                                    INNER JOIN mdl_bsu_plan AS p
                                                    ON p.id = m.planid
                                                    WHERE m.disciplinenameid = 51 AND m.yearid = {$yearid} AND m.term = {$term} and m.edworkkindid=3 AND
                                                          p.departmentcode = {$department->departmentcode})
                                ";
                    //echo $sqltext;
                    if( $vnepotokas = $DB->get_records_sql($sqltext) ){
                        $table->data[] = array("<b><font color = red > Без потока: </font></b>", "&nbsp;", "&nbsp;"); 
                        foreach($vnepotokas AS $vnepotoka){
                            if( $vnepotoka->subgroupid == 0 ){
                                $table->data[] = array("Группа: ".$vnepotoka->groupname. " ({$vnepotoka->gcountstud} ст.)" , "&nbsp;" , "&nbsp;");
                                if( $subgroupmembers = $DB->get_records_sql("SELECT gm.id AS id, gm.username, u.lastname, u.firstname
                                                                             FROM {bsu_group_members} AS gm
                                                                             INNER JOIN {user} AS u
                                                                             ON u.username = gm.username
                                                                             WHERE gm.groupid = {$vnepotoka->groupid}  and gm.deleted=0
                                                                             ORDER BY u.lastname") ){
                                    $ii = 1;
                                    foreach($subgroupmembers AS $subgroupmember){
                                        $table->data[] = array( "&nbsp;", "&nbsp;", $ii++ .  '. ' . $subgroupmember->lastname." ".$subgroupmember->firstname );
                                    }
                                }   
                            }else{
                                //$table->data[] = array("Группа : ".$vnepotoka->groupname, "Подгруппа : ".$vnepotoka->subgroupname , "&nbsp;");
                                $table->data[] = array( "Группа: ".$vnepotoka->groupname . " ({$vnepotoka->gcountstud} ст.)" , 
                                                        "<a target = '_blank' href='dis51user.php?tab=e&term={$term}&yearid={$yearid}&subgroupid={$vnepotoka->subgroupid}' >".
                                                        "Подгруппа: ".$vnepotoka->subgroupname." ({$vnepotoka->sgcountstud} ст.) " . 
                                                        html_writer::empty_tag('img', array( 'src' => $CFG->wwwroot."/blocks/bsu_aspirant/i/edit.gif", 'height' => "16" ,  'width' => "16" ) ).
                                                        "</a>",
                                                        "" );
                                if( $subgroupmembers = $DB->get_records_sql("SELECT @i := @i + 1 AS ids,
                                                                                    dsm.id AS id, dsm.username, dsm.subgroupid, u.lastname, u.firstname
                                                                             FROM (select @i := 0) AS z, {bsu_discipline_subgroup_members} AS dsm
                                                                             INNER JOIN {user} AS u
                                                                             ON u.username = dsm.username
                                                                             WHERE dsm.subgroupid = {$vnepotoka->subgroupid} and dsm.deleted=0 
                                                                             ORDER BY u.lastname") ){
                                    $ii = 1;
                                    foreach($subgroupmembers AS $subgroupmember){
                                        $table->data[] = array( "&nbsp;", "&nbsp;", $ii++ . '. ' . $subgroupmember->lastname." ".$subgroupmember->firstname );
                                    }
                                }
                                                        
                            }
                             
                            
                            
                        }
                    }
                    
                    /////////////////
                   
            } 
        }

        return $table;    
    }

?>