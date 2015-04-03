<?php
    /*
    require_once ("../../../config.php");
    require_once("../import/lib_import.php");
    
    $planid = optional_param('planid', '9', PARAM_TEXT);
    
    $PAGE->set_url('/blocks/bsu_plan/index.php');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    
    $PAGE->set_title('График УП');
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_focuscontrol('');
    $PAGE->requires->js('/blocks/bsu_plan/curriculums/graphicup.js', true);
    $PAGE->requires->js('/lib/jquery/js/jquery-1.9.1.js', true);
    
    $PAGE->navbar->add('Домой в блок', new moodle_url("{$CFG->BSU_PLAN}/index.php"));
    $PAGE->navbar->add('График учебного процесса');
    
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading('График учебного плана: ' . $planid);
    */
    
    $arrmonth = array('Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь', 'Январь',
        'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август');
    
    $countweekinmonth = array(5,4,4,5,4,4,5,4,4,5,4,4);
    
    $arrweek = array('1-7сен','8-14 сен','15-21 сен','22-28 сен','29 сен - 5 окт',
        '6-12 окт','13-19 окт','20-26 окт','27окт - 02ноя','3-9 ноя','10-16 ноя',
        '17-23 ноя','24 ноя - 30 ноя','1-7 дек','8-14 дек','15-21 дек','22-28 дек',
        '29 дек - 4 янв','5-11 янв','12-18 янв','19-25 янв','26 янв - 1 фев','2-8 фев',
        '9-15 фев','16-22 фев','23 фев - 1 мар','2-8 мар','9-15 мар','16-22 мар','23-29 мар',
        '30 мар - 5 апр','6-12 апр','13-19 апр','20-26 апр','27 апр - 3 май','4-10 май',
        '11-17 май','18-24 май','25-31 май','1-7 июн','8-14 июн','15-21 июн','22-28 июн',
        '29 июн - 5 июл','6-12 июл','13-19 июл','20-26 июл','27 июл -2 авг','3-9 авг',
        '10-16 авг','17-23 авг','24-30 авг');
    
    $tdmonth = '<td style="border:1px; border-style:solid;" rowspan="3"><center><b>Кол-во недель (Осень)<b></center></td>';
    $tdmonth .= '<td style="border:1px; border-style:solid;" rowspan="3"><center><b>Кол-во недель (Весна)<b></center></td>';
    
    //*************************Объединяем ячейки для вывода названия месяца**************************//
    for($i=0; $i<12; $i++){
        $tdmonth .= "<td  style=\"border:1px; border-style:solid;\" colspan=\"{$countweekinmonth[$i]}\">" . '<b>' . $arrmonth[$i] . '</b>' . '</td>';
    }
    //*************************Выводим названия диапазонов для каждой недели**************************//
    $x=-1;
    $tdweek = '';
    $htmlnumtdweek = '';
    $numtdweek = 0;
    foreach($arrweek as $_arrweek){
        $tdweek .= '<td style="border:1px; border-style:solid;"><div style="position:relative; width:23px; height:117px; border:0px #CCCCCC inset; overflow:hidden; float:left">'; 
        $tdweek .= "<img style=\"position:absolute; left:" . $x . "px; top:-1px\" src=\"../img/123.png\" />";
        $tdweek .= '</div></td>';
        $x = $x - 23;
        
        $numtdweek++;
        $htmlnumtdweek .= '<td style="border:1px; border-style:solid;">' . '<b>' . '<center>' . $numtdweek . '<center>' . '</b>'. '</td>';
    }
    
    if($dbplanup = $DB->get_records('bsu_plan_grafikuchprocess', array('planid'=>$planid), null, 'numkurs, numweekspring, numweekautumn, grafik, char_length(grafik) as countvidkontr')){
        $grid = '';
        foreach($dbplanup as $planup){
            $grid .= '<tr>';
            //*************************Выводим левые кнопки для редактирования и сохранения**************************//
            $grid .= '<td style="border:1px; border-style:solid;">' . '<div style="float:left"><b>' . $planup->numkurs . '</b></div>' . 
                        "<div id=\"edit1_{$planup->numkurs}\" >
                            <a onClick=\"hide_show('{$planup->numkurs}', $planid);\" href=\"#\" title=\"Редактировать сетку курса\">
                            <img src=\"$CFG->pixpath/i/edit.gif\">
                        </a></div>" . 
                        "<div id=\"save1_{$planup->numkurs}\" style=\"display:none;\">
                            <a onClick=\"hide_show('{$planup->numkurs}', $planid);\" href=\"#\" title=\"Сохранить сетку курса\">
                            <img width=\"16\" src=\"{$CFG->pixpath}/i/load-icon.png\">
                        </a></div>" .
                    '</td>';
            
            /*Вывод двух ячеек со значением кол-ва недель  осенью и весной*/
            
            $grid .= '<td style="border:1px; border-style:solid">' . 
                            '<div id="'.'week_autumn_label_'.$planup->numkurs.'">' . 
                                '<center>' . $planup->numweekautumn . '</center>' . 
                            '</div>' . 
                            '<div id = "'.'week_autumn_div_input_'.$planup->numkurs.'" style="display:none;">' . 
                                '<center>' . '<input id="week_autumn_input_'.$planup->numkurs.'" type="text" style="width:19px;" value="' . $planup->numweekautumn . '"></center>
                            </div>
                        </td>';
    		$grid .= '<td style="border:1px; border-style:solid">' . 
                            '<div id="'.'week_spring_label_'.$planup->numkurs.'">' . 
                                '<center>' . $planup->numweekspring . '</center>'. 
                            '</div>' . 
                            '<div id = "'.'week_spring_div_input_'.$planup->numkurs.'" style="display:none;">' . 
                                '<center>' . '<input id="week_spring_input_'.$planup->numkurs.'" type="text" style="width:19px;" value="' . $planup->numweekspring . '"></center> 
                            </div>
                        </td>';
            //$grid .= '<td style="border:1px; border-style:solid;" colspan="52">' . $planup->grafik . '</td>';
            //$grid .= '<td style="border:1px; border-style:solid;" colspan="52">' . $planup->countvidkontr . 'Курс' . '</td>';
            $arrvidkontr = str_split_php4_utf8($planup->grafik); // исправить на implode по символу разделителя
            $i=1;
            foreach($arrvidkontr as $ii => $vidkontr){
                if($vidkontr == 'Т'){
                    $vidkontr = '';
                }
                //*************************Выводим контрольные значения в ячейки таблицы**************************//
                $grid .= '<td style="border:1px; border-style:solid">' . 
                            '<div id="'.'l_'.$planup->numkurs.'_'.$i.'">' . 
                                $vidkontr . 
                            '</div>' . 
                            '<div id = "'.$planup->numkurs.'_'.$i.'" style="display:none;">' . 
                                '<input id="input_'.$planup->numkurs.'_'.$i.'" type="text" style="width:19px;" value="' . $vidkontr . '">
                            </div>
                        </td>';        
                $i++;    
                    
            }
            //*************************Выводим правые кнопки для редактирования и сохранения**************************//
            $grid .= '<td style="border:1px; border-style:solid">'. 
                    "<div id=\"edit_{$planup->numkurs}\" ><a onClick=\"hide_show('{$planup->numkurs}', $planid);\" href=\"#\" title=\"Редактировать сетку курса\">
                        <img src=\"{$CFG->pixpath}/i/edit.gif\">
                    </a></div>" . 
                    "<div id=\"save_$planup->numkurs\" style=\"display:none;\"><a onClick=\"hide_show('{$planup->numkurs}', $planid);\" href=\"#\" title=\"Сохранить сетку курса\">
                        <img width=\"16\" src=\"{$CFG->pixpath}/i/load-icon.png\">
                    </a></div>" .
                '</td></tr>';
        }
        
    }else{
        notify('Данные плана не найдены');
    }
    
?>
    <table width="100%" border="1">
        <tr>
            <td align="center" colspan="55" style="border: 1px; border-style: solid;">
<i>Замечание: при заполнении графика учебного процесса используются следующие символы:<br />
<b>Э</b> - Экзаменационные сессии; <b>У</b> - Учебная практика; <b>П</b> - Другие Практики, НИР; <b>Д</b> - Выпускная работа, Диссертация;<br />
<b>Г</b> - Гос. экзамены и защита; <b>К</b> - Каникулы; <b>А</b> - Итоговая Аттестация, выпускные экзамены; <b>=</b> - Неделя отсутствует.</i>
            </td>
        </tr>

        <tr>
            <?php
    	       echo '<td></td>' . $tdmonth;
            ?>
        </tr>   
        <tr>
            <?php
                echo '<td rowspan="2"><b>Курс</b></td>';
                echo $tdweek;
            ?>
        </tr>
        <tr>
            <?php
    	       echo $htmlnumtdweek;
            ?>
        </tr>
        <?php
    	   echo $grid;
        ?>
    </table>
    <div align="center" id="statusbar"></div>
    <br />
    
<?php 
//echo $OUTPUT->footer();
?>