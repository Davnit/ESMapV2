<?php

    require_once "database.php";
    
    // Get a list of geocodes that have not been resolved.
    $statement = $db->prepare("SELECT id, location FROM geocodes WHERE resolved IS NULL LIMIT 10");
    $statement->execute();
    $geoList = $statement->fetchAll();
         
    // Turn this list into an id -> location lookup
    $geocodes = array();
    foreach ($geoList as $gL)
    {
        $geocodes[$gL["id"]] = $gL["location"];
    }
    
    $response = array(
        "geocodes" => $geocodes
    );
    
    echo json_encode($response);

?>