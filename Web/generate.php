<?php

    require_once "database.php";
    
    // Get a list of sources
    $sourceList = getData("SELECT id, tag FROM sources");
    
    // Turn it into a lookup
    $sources = array();
    foreach ($sourceList as $sL)
    {
        $sources[intval($sL["id"])] = $sL["tag"];
    }
    
    // Get list of active or recently expired calls.
    $sql = "SELECT c.source, c.meta, c.added, c.expired, g.latitude, g.longitude FROM calls c ";
    $sql .= "LEFT JOIN geocodes g ON g.id = c.geoid ";
    $sql .= "WHERE c.expired IS NULL OR (c.expired >= NOW() - INTERVAL 1 HOUR)";    
    $callList = getData($sql);
    
    $mapCalls = array();
    $tableCalls = array();
    foreach ($callList as $cL)
    {
        $meta = json_decode($cL["meta"]);
        
        // The live map should only contain calls that have resolved coordinates.
        if (($cL["latitude"] != null) and ($cL["longitude"] != null))
        {
            $mapCalls[] = array(
                "desc" => sprintf("%s @ %s", $meta->description, $meta->location),
                "lat" => floatval($cL["latitude"]),
                "lng" => floatval($cL["longitude"]),
            );
        }
        
        // Determine the call's start time. If the source specified a time, use that, otherwise
        //   use the time the call was added to the database.
        $callTime = (strlen($meta->call_time) > 0) ? $meta->call_time : $cL["added"];
        
        // The call log table contains slightly more information about every call.
        $tableCalls[] = array(
            "dept" => intval($cL["source"]),    # ID of the call source
            "desc" => $meta->description,       # Call description
            "loc" => $meta->location,           # Unprocessed location of the call
            "time" => $callTime,                # The time the call was made or found
            "closed" => $cL["expired"]          # The time the call was closed/expired (or null if its ongoing)
        );
    }
    
    // Create the object for the live map and serialize it
    $obj = array(
        "updated" => time(),
        "calls" => $mapCalls
    );
    file_put_contents("livemap.json", json_encode($obj, JSON_PRETTY_PRINT));
    
    // Create the object for the call list and serialize it.
    $obj = array(
        "updated" => time(),
        "sources" => $sources,
        "calls" => $tableCalls
    );
    file_put_contents("call_log.json", json_encode($obj, JSON_PRETTY_PRINT));

?>