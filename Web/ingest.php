<?php

    require_once "lib/Locations.php";
    $config = require("lib/Config.php");
    
    function reportError($message)
    {
        $response = array(
            "status" => array(
                "success" => false,
                "message" => $message
            )
        );
        die(json_encode($response));
    }
    
    // Inserts locations and (optional) latitude/longitude to geocoding table.
    //  Returns location => geoID for each entry. If no locations are given, returns an empty array.
    //  Locations array must have 3 columns [ location, latitude, longitude ]. Use null.
    function getGeoData($locations)
    {
        if (count($locations) == 0) return array();
        
        // Attempt to insert all of these locations into the database
        //   Ignore duplicates (so that we link the new entries to old resolves)
        //   Inserts pre-resolved coordinates and merges with existing data
        $fields = [ "location", "latitude", "longitude" ];
        insertRows("geocodes", $fields, $locations, "UPDATE $fields[0] = $fields[0]");
        
        // Get rid of everything except the location strings
        $locations = array_column($locations, "location");
        
        // Get geocode IDs for locations
        $sql = "SELECT id, location FROM geocodes WHERE location IN (%s)";
        $sql = sprintf($sql, implode(",", array_fill(0, count($locations), "?")));
        
        $geocodes = getData($sql, $locations);
        if (count($geocodes) > 0)
        {
            // location -> geoid
            return array_column($geocodes, "id", "location");
        }
        else
            return array();
    }
    
    if (!isset($_POST["calldata"]) and !isset($_POST["geodata"])) {
        reportError("No data provided.");
    }
    
    // Validate client key
    $clientKey = $config["client_key"];
    if (strlen($clientKey) > 0)
    {
        if (!isset($_POST["key"]) or ($_POST["key"] !== $clientKey))
        {
            reportError("Client not authorized.");
        }
    }
    
    // Access the database
    try
    {
        require_once "lib/Database.php";
    }
    catch (PDOException $pe) 
    {
        reportError("Database not available.");
    }
    
    // Default response
    $response = array(
        "status" => array(
            "success" => true
        )
    );
    
    $data = null;
    
    // Parse call data
    if (isset($_POST["calldata"]))
        $data = json_decode($_POST["calldata"], true);
    
    if ($data != null)
    {
        // Static data
        $source = $data["source"];
    
        // Update the database
        if (count($data["new"]) > 0) 
        {
            $calls = array();       // New calls
            $locations = array();   // Locations of new calls
        
            // Format new call data as rows for the table
            //   key => [ source, key, category, geoID, metadata ]
            foreach ($data["new"] as $row)
            {
                $calls[$row["key"]] = [ $source, $row["key"], $row["category"], null, json_encode($row["meta"]) ];
                
                // Get fixed size location data array 
                //  [ location, latitude, longitude ]
                $locData = extractLocationData($row);
                if ($locData !== null)
                {
                    // Normalize and verify the location string
                    $proc = processLocation($locData["location"]);
                    
                    // If the location is valid, set it to the normalized version
                    if ($proc !== false)
                    {
                        $locData["location"] = $proc;
                    }
                    else
                    {
                        if ($locData["latitude"] == null or $locData["longitude"] == null)
                            continue;
                    }
                    
                    // match calls to location data
                    $locations[$row["key"]] = $locData;
                }
            }
        
            // Add new locations to geocode table
            if (count($locations) > 0)
            {
                // location -> geoID
                $geocodes = getGeoData($locations);
            
                // Trim down to just key => location
                $locations = array_map(function ($x) { return $x["location"]; }, $locations);
                
                // Map calls to the geoID for their locations.
                foreach ($locations as $k => $v)
                {
                    $calls[$k][3] = $geocodes[$v];
                }
            }
        
            // Add new calls - if a call ID is already there, reopen it.
            $fields = [ "source", "cid", "category", "geoid", "meta" ];
            $newCount = insertRows("calls", $fields, $calls, "UPDATE expired = NULL");
        }
    
        // Close expired calls (sets expired timestamp to now())
        if (count($data["expired"]) > 0) 
        {
            $expCount = updateTimestamps("calls", "expired", "cid", $data["expired"]);
        }
        
        // Handle data changes
        if (count($data["updated"]) > 0)
        {
            // Get updated call IDs
            $updateCIDs = array_keys($data["updated"]);
            
            // Get list of updated locations
            $newLocations = array();
            foreach ($data["updated"] as $cid => $row)
            {
                // [ location, latitude, longitude ]
                $locData = extractLocationData($row);
                if ($locData != null)
                {
                    $proc = processLocation($locData["location"]);
                    
                    if ($proc !== false or ($locData["latitude"] == null and $locData["longitude"] == null))
                    {
                        $locData["location"] = $proc;
                        $newLocations[$cid] = $locData;
                    }
                }
            }
            
            if (count($newLocations) > 0)
            {
                // location -> geoID
                $geocodes = getGeoData($newLocations);
                
                // Trim locations to just key => location
                $newLocations = array_map(function ($x) { return $x["location"]; }, $newLocations);
            }
            
            // Retrieve existing call data
            $sql = "SELECT c.cid, c.category, c.geoid, g.location, c.meta FROM calls c ";
            $sql .= "LEFT JOIN geocodes g ON c.geoid = g.id ";
            $sql .= "WHERE c.cid IN (" . implode(",", array_fill(0, sizeof($updateCIDs), "?")) . ")";
            
            $current = getData($sql, $updateCIDs);
            $current = array_column($current, null, "cid");     # index calls by ID
            
            $updCount = 0;
            
            // Iterate through all of the update data and compare to the current data
            foreach ($data["updated"] as $cid => $updates)
            {
                if (is_array($updates) == false) continue;
                
                $updatedValues = array();
                
                // Call records do not store a location, but a reference to the location (GID)
                if (array_key_exists("location", $updates))
                {
                    if (array_key_exists($cid, $newLocations))
                    {
                        $locStr = $newLocations[$cid];
                        if (is_string($locStr) && array_key_exists($locStr, $geocodes))
                        {
                            $newGID = $geocodes[$locStr];
                            if ($newGID != $current[$cid]["geoid"])
                                $updatedValues["geoid"] = $newGID;
                        }
                    }
                    
                }
                
                if (array_key_exists("category", $updates))
                {
                    if ($updates["category"] != $current[$cid]["category"])
                        $updatedValues["category"] = $updates["category"];
                }
                
                // Metadata update handling is a little more complex.
                //   The stored JSON needs to be decoded and merged with the updated values.
                if (array_key_exists("meta", $updates))
                {
                    $metaChanged = false;
                    $currentMeta = json_decode($current[$cid]["meta"], true) ?? array();
                    
                    // Iterate each value being changed
                    foreach ($updates["meta"] as $key => $newValue)
                    {
                        // Verify the new value is different
                        if (!array_key_exists($key, $currentMeta) || $currentMeta[$key] != $newValue)
                        {
                            $metaChanged = true;
                            $currentMeta[$key] = $newValue;     # Update value
                        }
                    }
                    
                    if ($metaChanged)
                        $updatedValues["meta"] = json_encode($currentMeta);
                }
                
                // If no values have actually changed, skip this request.
                if (count($updatedValues) < 1) continue;
                
                // Build a query based on the updated values
                $sql = "UPDATE calls SET ";
                foreach ($updatedValues as $k => $v)
                {
                    $sql .= "$k = ?,";
                }
                $sql = substr($sql, 0, -1);
                $sql .= " WHERE cid = ?";
                
                // Add call ID to value list for insertion
                $values = array_values($updatedValues);
                $values[] = $cid;
                
                // Execute update
                $db->beginTransaction();
                $statement = $db->prepare($sql);
                $statement->execute($values);
                $db->commit();
                
                $updCount++;
            }
        }
    
        // Set status values (client uses these to verify request was successful)
        $response["status"]["added"] = isset($newCount) ? $newCount : 0;
        $response["status"]["expired"] = isset($expCount) ? $expCount : 0;
        $response["status"]["updated"] = isset($updCount) ? $updCount : 0;
    }
    
    $data = null;
    
    // Parse geocode data
    if (isset($_POST["geodata"]))
        $data = json_decode($_POST["geodata"], true);
    
    if ($data != null)
    {
        $updateCount = 0;
        foreach ($data as $k => $v)
        {           
            $coords = null;     // Will hold an array containing latitude and logitude of the resolved point
            
            // Process the results
            if (array_key_exists("results", $v))
            {
                $results = $v["results"];
                if (count($results) > 0)
                {
                    // Check if geocode was resolved to a single point
                    //  location_type == ROOFTOP or types is [street_address or intersection]
                    $validTypes = array("street_address", "intersection");
                    
                    if ($results[0]["geometry"]["location_type"] == "ROOFTOP" or 
                        count(array_intersect($results[0]["types"], $validTypes)) > 0)
                        {
                            $location = $results[0]["geometry"]["location"];
                            $coords = array($location["lat"], $location["lng"]);
                        }
                }
            }
            
            // Update the results for this ID
            $sql = "UPDATE geocodes SET resolved = NOW(), results = :results";
            if ($coords != null)
            {
                $sql .= ", latitude = " . floatval($coords[0]);
                $sql .= ", longitude = " . floatval($coords[1]);
            }
            
            $sql .= " WHERE id = :geoID";
            $statement = $db->prepare($sql);
            $statement->bindValue(":results", json_encode($v), PDO::PARAM_STR);
            $statement->bindValue(":geoID", $k, PDO::PARAM_INT);
            
            // Track the number of successful updates
            if ($statement->execute())
            {
                $updateCount++;
            }
        }
        
        $response["status"]["resolved"] = $updateCount;
    }
    
    print(json_encode($response));
    
    // If changes were made to the dataset, update the current call list.
    $genStatus = array("added", "expired", "resolved", "updated");
    foreach ($genStatus as $s)
    {
        if (array_key_exists($s, $response["status"]) and $response["status"][$s] > 0)
        {
            include "generate.php";
            break;
        }
    }

?>