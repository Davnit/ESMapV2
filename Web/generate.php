<?php

    try
    {
        require_once "lib/Database.php";
    }
    catch (PDOException $pe)
    {
        die("Database not available.");
    }
    
    $config = require("lib/Config.php");
    
    // Get a list of sources
    $sourceList = getData("SELECT id, tag, bounds, time_zone, time_format FROM sources");
    
    // Get a lookup of source ID -> tag
    $tags = 
    
    // Make a multidimensional array of source data
    $sources = array();
    foreach ($sourceList as $sL)
    {
        $sources[$sL["id"]] = array(
            "tag" => $sL["tag"],
            "bounds" => explode("|", str_replace(",", "|", $sL["bounds"])),
            "timezone" => $sL["time_zone"],
            "time_format" => $sL["time_format"]
        );
    }
    
    // Get list of active or recently expired calls.
    $sql = "SELECT c.id, c.source, c.category, c.meta, c.added, c.expired, g.latitude, g.longitude FROM calls c ";
    $sql .= "LEFT JOIN geocodes g ON g.id = c.geoid ";
    $sql .= "WHERE c.expired IS NULL";

    // Include recently expired calls
    $historyTime = $config["history_time"];
    if (strlen($historyTime) > 0) {
        $sql .= " OR (c.expired >= NOW() - INTERVAL $historyTime)";
    }
    
    $callList = getData($sql);
    
    // Organize data to be written
    $mapCalls = array();
    $tableCalls = array();
    foreach ($callList as $cL)
    {
        $id = intval($cL["id"]);
        $src = intval($cL["source"]);
        $meta = json_decode($cL["meta"], true);
        
        // Do we recognize this source? If not, skip it.
        if (array_key_exists($src, $sources) === false)
            continue;
        
        // The live map should only contain calls that have resolved coordinates.
        if (($cL["latitude"] != null) and ($cL["longitude"] != null))
        {
            $verified = true;
            
            $fLat = floatval($cL["latitude"]);
            $fLng = floatval($cL["longitude"]);
            
            // Check if the source of this call has a defined boundary
            $bounds = $sources[$src]["bounds"];
            
            if (count($bounds) == 4)
            {
                // Check if the point is outside of the bounds
                //   [sw_lat, sw_lng, ne_lat, ne_lng]
                if ($fLat < $bounds[0] or $fLng < $bounds[1] or $fLat > $bounds[2] or $fLng > $bounds[3])
                {
                    $verified = false;
                }
            }
            
            if ($verified)
            {
                // Construct tooltip text
                $tooltip = $meta["description"];
                if (isset($meta["location"]) and strlen($meta["location"]) > 0)
                    $tooltip .= " @ " . $meta["location"];
                
                // Add the point to the map list
                //   Format: [ latitude, longitude, tooltip, marker ]
                $mapCalls[$id] = array($fLat, $fLng, $tooltip, str_replace("-", "", $cL["category"]));
            }
        }
        
        // Get call time information
        $added = $cL["added"];
        $expired = $cL["expired"];
        $timezone = $sources[$src]["timezone"];
        
        // Determine the call's start time. Prefer the source-provided time but use other known data to fill in missing elements.
        $addedTime = new DateTime($added);
        
        if (isset($meta["call_time"]) and strlen($meta["call_time"]) > 0)
        {
            // Prefer the source time format but make a guess if that doesn't work out.
            if (($tryTime = DateTime::createFromFormat($sources[$src]["time_format"], $meta["call_time"])) == false)
                $tryTime = new DateTime($meta["call_time"]);
            
            if ($tryTime !== false)
            {
                // If the dates are different then it could be an old call. Use the date from the time added.
                if ($tryTime->diff($addedTime)->days != 0)
                    $tryTime->setDate($addedTime->format("Y"), $addedTime->format("m"), $addedTime->format("d"));
                
                // Subtract a day if the call time is after we found it (as long as sources accurately provide data...)
                //   This should handle calls where we don't find it until the next day.
                if ($tryTime > $addedTime)
                    $tryTime->sub(new DateInterval("P1D"));
            }
        }
        
        // If we successfully figured out a time, use that - otherwise use the added time.
        $callTime = ($tryTime == false) ? $addedTime : $tryTime;
        
        // If a specific date was provided, use that.
        if (isset($meta["call_date"]) and strlen($meta["call_date"]) > 0)
        {
            if (($tryDate = DateTime::createFromFormat($sources[$src]["time_format"], $meta["call_date"])) == false)
                $tryDate = new DateTime($meta["call_date"]);
            
            if ($tryDate !== false)
            {
                // Use this date for the call date.
                $callTime->setDate($tryDate->format("Y"), $tryDate->format("m"), $tryDate->format("d"));
            }
        }
        
        // The call log table contains slightly more information about every call.
        //   Format: [ source, description, location, call time, closed time]
        $tableCalls[$id] = array($src, $meta["description"], $meta["location"], $callTime->format("Y-m-d H:i:s"), $expired);
    }
    
    // Set serialization precision for floating point numbers
    //   This makes it so that our coordinates aren't serialized with a bunch of useless junk after them.
    ini_set("serialize_precision", -1);
    
    // Create the object for the live map and serialize it
    $obj = array(
        "updated" => time(),
        "calls" => $mapCalls
    );
    file_put_contents("data/livemap.json", json_encode($obj));
    
    // Create the object for the call list and serialize it.
    $obj = array(
        "updated" => time(),
        "sources" => array_column($sourceList, "tag", "id"),
        "calls" => $tableCalls
    );
    file_put_contents("data/call_log.json", json_encode($obj));

?>