<?php
    require_once("../../../config.php");
        
    $req = "SELECT id, name "
    	."FROM mdl_bsu_ref_disciplinename "
    	."WHERE name LIKE '".$_REQUEST['term']."%' limit 10"; 
    
    
    $disc = $DB->get_records_sql($req);
    
    foreach($disc as $d){
        $results[] = array('label' => $d->name);
    }
      
    echo json_encode($results);

?>