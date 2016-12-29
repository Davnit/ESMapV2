<?php

    require_once "database.php";
    
    function getData($sql)
    {
        global $db;
        $statement = $db->prepare($sql);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get list of active or recently expired calls.
    $sql = "SELECT c.meta, g.latitude, g.longitude FROM calls c ";
    $sql .= "LEFT JOIN geocodes g ON g.id = c.geoid ";
    $sql .= "WHERE c.expired IS NULL OR (c.expired >= NOW() - INTERVAL 1 HOUR)";    
    $callList = getData($sql);
    
    $calls = array();
    foreach ($callList as $cL)
    {
        $meta = json_decode($cL["meta"]);
        $call["desc"] = sprintf("%s @ %s", $meta->description, $meta->location);
        
        // Only show calls that have resolved coordinates
        if (($cL["latitude"] != null) and ($cL["longitude"] != null))
        {
            $call["lat"] = floatval($cL["latitude"]);
            $call["lng"] = floatval($cL["longitude"]);
            $calls[] = $call;
        }
    }
    
    $obj = array(
        "updated" => time(),
        "calls" => $calls
    );
    
    file_put_contents("livemap.json", json_encode($obj, JSON_PRETTY_PRINT));

?>