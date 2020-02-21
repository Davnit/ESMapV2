<?php

    ob_end_flush();
    
    require_once "Database.php";
    require_once "Locations.php";
    
    $bounds = array('28.30', '-81.71', '28.81', '-80.84');
    $badCodes = getData("SELECT id, location, results FROM geocodes WHERE latitude < '$bounds[0]' OR longitude < '$bounds[1]' OR latitude > '$bounds[2]' OR longitude > '$bounds[3]'");
    print("Found " . count($badCodes) . " bad geocodes. <br />\n");
    
    $fixes = array();
    $fixCount = 0;
    
    $db->beginTransaction();
    
    // Loop through all of the out-of-bounds geocodes
    foreach ($badCodes as $row)
    {
        $data = json_decode($row["results"], true);
        
        $results = $data["results"];
        
        print("BAD: " . $row["location"] . " => " . $results[0]["formatted_address"] . " <br />\n");
        
        // Check each of the potential locations for one that is in-bounds
        $result_index = -1;
        for ($idx = 0; $idx < count($results); $idx++)
        {
            $location = $results[$idx];
            
            $lat = $location["geometry"]["location"]["lat"];
            $lng = $location["geometry"]["location"]["lng"];
            
            if (checkBounds(array($lat, $lng), $bounds)) 
            {
                $pre = "&nbsp;->&nbsp;";
                
                // Assign the result index and correct coordinates
                $result_index = $idx;
                $fixes[$row["id"]] = array($result_index, $lat, $lng);
                
                // Update the database
                $sql  = "UPDATE geocodes SET residx = :idx, ";
                $sql .= "latitude = " . floatval($lat) . ", longitude = " . floatval($lng) . " WHERE id = :geoID";
                $statement = $db->prepare($sql);
                $statement->bindValue(":idx", $result_index, PDO::PARAM_INT);
                $statement->bindValue(":geoID", $row["id"], PDO::PARAM_INT);
                if ($statement->execute())
                    $fixCount++;
                
                break;  // If multiple locations are valid, take only the first one.
            }
            else
            {
                $pre = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            
            print($pre . $location["formatted_address"] . " [$lat, $lng] <br />\n");
        }
    }
    
    if (isset($_GET["commit"]))
    {
        $db->commit();
    
        print("Fixed $fixCount/" . count($badCodes) . " bad geocodes. (" . count($fixes) . " attempted) <br />\n");
    }
    else
    {
        $db->rollBack();
        
        print("Found potential fixes for $fixCount/" . count($badCodes) . " bad geocodes. (" . count($fixes) . " attempted) <br />\n");
    }

?>    