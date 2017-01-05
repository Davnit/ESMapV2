<?php

    require_once "database.php";
    
    // Get a list of sources
    $sourceList = getData("SELECT id, tag, bounds FROM sources");
    
    // Get a lookup of source ID -> tag
    $tags = array_column($sourceList, "tag", "id");
    
    // Get a lookup of source ID -> bounds[], where each element of the array is a lat or lng (ne_lat, ne_lng, sw_lat, sw_lng)
    $bounds = array_map(function ($x) { return explode("|", str_replace(",", "|", $x)); }, array_column($sourceList, "bounds", "id"));    
    
    // Get list of active or recently expired calls.
    $sql = "SELECT c.source, c.meta, c.added, c.expired, g.latitude, g.longitude FROM calls c ";
    $sql .= "LEFT JOIN geocodes g ON g.id = c.geoid ";
    $sql .= "WHERE c.expired IS NULL OR (c.expired >= NOW() - INTERVAL 1 HOUR)";    
    $callList = getData($sql);
    
    $mapCalls = array();
    $tableCalls = array();
    foreach ($callList as $cL)
    {
        $src = intval($cL["source"]);
        $meta = json_decode($cL["meta"]);
        
        // The live map should only contain calls that have resolved coordinates.
        if (($cL["latitude"] != null) and ($cL["longitude"] != null))
        {
            $verified = true;
            
            // Check if the source of this call has a defined boundary
            if (array_key_exists($src, $bounds))
            {
                $bnd = $bounds[$src];
                
                $fLat = floatval($cL["latitude"]);
                $fLng = floatval($cL["longitude"]);
                
                // Check if the point is outside of the bounds
                if ($fLat > $bnd[0] and $fLng > $bnd[1] and $fLat < $bnd[2] and $fLng < $bnd[3])
                {
                    $verified = false;
                }
            }
            
            if ($verified)
            {
                // Add the point to the map list
                $mapCalls[] = array(
                    "desc" => sprintf("%s @ %s", $meta->description, $meta->location),
                    "lat" => $fLat, 
                    "lng" => $fLng
                );
            }
        }
        
        // Determine the call's start time. If the source specified a time, use that, otherwise
        //   use the time the call was added to the database.
        $callTime = (strlen($meta->call_time) > 0) ? $meta->call_time : $cL["added"];
        
        // The call log table contains slightly more information about every call.
        $tableCalls[] = array(
            "dept" => $src,                     # ID of the call source
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
    file_put_contents("data/livemap.json", json_encode($obj, JSON_PRETTY_PRINT));
    
    // Create the object for the call list and serialize it.
    $obj = array(
        "updated" => time(),
        "sources" => $tags,
        "calls" => $tableCalls
    );
    file_put_contents("data/call_log.json", json_encode($obj, JSON_PRETTY_PRINT));

?>