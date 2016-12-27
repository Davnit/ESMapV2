<?php

    require_once "database.php";
    
    function getData($sql)
    {
        global $db;
        $statement = $db->prepare($sql);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get source metadata
    $sources = getData("SELECT id, tag FROM sources");
    
    // Get list of active or recently expired calls.
    $sql = "SELECT c.id, c.source, c.category, c.meta, c.added, c.expired, g.latitude, g.longitude FROM calls c ";
    $sql .= "LEFT JOIN geocodes g ON g.id = c.geoid ";
    $sql .= "WHERE c.expired IS NULL OR (c.expired >= NOW() - INTERVAL 1 HOUR)";
    
    $calls = getData($sql);
    
    // Set values to correct data types
    for ($i = 0; $i < count($sources); $i++)
    {
        $sources[$i]["id"] = intval($sources[$i]["id"]);
    }
    
    for ($i = 0; $i < count($calls); $i++)
    {
        $call = $calls[$i];
        
        $calls[$i]["id"] = intval($call["id"]);
        $calls[$i]["source"] = intval($call["source"]);
        $calls[$i]["meta"] = json_decode($call["meta"]);
        
        if ($call["latitude"] != null) $calls[$i]["latitude"] = floatval($call["latitude"]);
        if ($call["longitude"] != null) $calls[$i]["longitude"] = floatval($call["longitude"]);
    }
    
    
    $obj = array(
        "sources" => $sources,
        "calls" => $calls
    );
    
    file_put_contents("current.json", json_encode($obj, JSON_PRETTY_PRINT));

?>