<?php

    require_once "lib/Database.php";
    $config = require("lib/Config.php");
    
    function convertTimeZone($time, $zone)
    {
        if (strlen(trim($time)) == 0) return $time;
        $dt = new DateTime($time, new DateTimeZone("Etc/UTC"));
        $dt->setTimeZone(new DateTimeZone($zone));
        return $dt->format("Y-m-d H:i:s");
    }
    
    // Get a list of sources
    $sourceList = getData("SELECT id, tag, bounds, time_zone FROM sources");
    
    // Get a lookup of source ID -> tag
    $tags = 
    
    // Make a multidimensional array of source data
    $sources = array();
    foreach ($sourceList as $sL)
    {
        $sources[$sL["id"]] = array(
            "tag" => $sL["tag"],
            "bounds" => explode("|", str_replace(",", "|", $sL["bounds"])),
            "timezone" => $sL["time_zone"]
        );
    }
    
    #array_map(function ($x) { return explode("|", str_replace(",", "|", $x)); }, array_column($sourceList, "bounds", "id"));    
    
    // Get list of active or recently expired calls.
    $sql = "SELECT c.source, c.category, c.meta, c.added, c.expired, g.latitude, g.longitude FROM calls c ";
    $sql .= "LEFT JOIN geocodes g ON g.id = c.geoid ";
    $sql .= "WHERE c.expired IS NULL OR (c.expired >= NOW() - INTERVAL " . $config["history_time"] . ")";    
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
            
            $fLat = floatval($cL["latitude"]);
            $fLng = floatval($cL["longitude"]);
            
            // Check if the source of this call has a defined boundary
            if (array_key_exists($src, $sources))
            {
                $bounds = $sources[$src]["bounds"];
                
                if (count($bounds) == 4)
                {
                    // Check if the point is outside of the bounds
                    //   [ne_lat, ne_lng, sw_lat, sw_lng]
                    if ($fLat > $bounds[0] and $fLng > $bounds[1] and $fLat < $bounds[2] and $fLng < $bounds[3])
                    {
                        $verified = false;
                    }
                }
            }
            
            if ($verified)
            {
                // Add the point to the map list
                $mapCalls[] = array(
                    "category" => $cL["category"],
                    "desc" => sprintf("%s @ %s", $meta->description, $meta->location),
                    "lat" => $fLat, 
                    "lng" => $fLng
                );
            }
        }
        
        // Get call time information
        $added = $cL["added"];
        $expired = $cL["expired"];
        $timezone = $sources[$src]["timezone"];
        
        // Convert times to the source's time zone
        if ($timezone != null)
        {
            $added = convertTimeZone($added, $timezone);
            $expired = convertTimeZone($expired, $timezone);
        }
        
        // Determine the call's start time. If the source specified a time, use that, otherwise
        //   use the time the call was added to the database.
        $callTime = (strlen($meta->call_time) > 0) ? $meta->call_time : $added;
        
        // The call log table contains slightly more information about every call.
        $tableCalls[] = array(
            "dept" => $src,                     # ID of the call source
            "desc" => $meta->description,       # Call description
            "loc" => $meta->location,           # Unprocessed location of the call
            "time" => $callTime,                # The time the call was made or found
            "closed" => $expired                # The time the call was closed/expired (or null if its ongoing)
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
        "sources" => array_column($sourceList, "tag", "id"),
        "calls" => $tableCalls
    );
    file_put_contents("data/call_log.json", json_encode($obj, JSON_PRETTY_PRINT));

?>