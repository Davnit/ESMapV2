<?php

    function reportError($message)
    {
        $response = array(
            "status" => array(
                "success" => false,
                "message" => $message,
            )
        );
        echo json_encode($response);
        return;
    }
    
    if (!isset($_POST["calldata"]) and !isset($_POST["geodata"])) {
        reportError("No data provided.");
        die();
    }
    
    // Access the database
    require_once "database.php";
    
    // Default response
    $response = array(
        "status" => array(
            "success" => true
        )
    );
    
    // Parse call data
    $data = json_decode($_POST["calldata"], true);
    if ($data != null)
    {
        // Static data
        $source = $data["source"];
    
        // Update the database
        if (count($data["new"]) > 0) 
        {
            $calls = array();       // New calls
        
            // Get a lookup of key -> location from the new call data
            $locations = array_column($data["new"], "location", "key");
            $locations = array_filter($locations, function($v) { return strlen($v) > 0; });
        
            // Format new call data as rows for the table
            foreach ($data["new"] as $row)
            {
                $calls[$row["key"]] = [ $source, $row["key"], $row["category"], -1, json_encode($row["meta"]) ];
            }
        
            // Add new locations to geocode table
            insertRows("geocodes", [ "location" ], $locations, true);
        
            // Get geocode IDs for new calls
            $sql = "SELECT id, location FROM geocodes WHERE location IN (%s)";
            $sql = sprintf($sql, implode(",", array_fill(0, count($locations), "?")));
            $statement = $db->prepare($sql);
            $statement->execute(array_values($locations));
        
            $geocodes = $statement->fetchAll();
            if (count($geocodes) > 0)
            {
                // location -> geoid
                $geocodes = array_column($geocodes, "id", "location");
            
                // Add geoid to calls
                foreach ($locations as $k => $v)
                {
                    $calls[$k][3] = $geocodes[$v];
                }
            }
        
            // Add new calls
            $fields = [ "source", "cid", "category", "geoid", "meta" ];
            $newCount = insertRows("calls", $fields, $calls, true);
        }
    
        // Update expired calls
        if (count($data["expired"]) > 0) 
        {
            $expCount = updateTimestamps("calls", "expired", "cid", $data["expired"]);
        }
    
        $response["status"]["added"] = isset($newCount) ? $newCount : 0;
        $response["status"]["expired"] = isset($expCount) ? $expCount : 0;    
    }
    
    // Parse geocode data
    $data = json_decode($_POST["geodata"], true);
    if ($data != null)
    {
        $updateCount = 0;
        foreach ($data as $k => $v)
        {
            // Update the results for this ID
            $sql = "UPDATE geocodes SET resolved = NOW(), results = :results WHERE id = :geoID";
            $statement = $db->prepare($sql);
            $statement->bindParam(":results", json_encode($v), PDO::PARAM_STR);
            $statement->bindParam(":geoID", $k, PDO::PARAM_INT);
            
            // Track the number of successful updates
            if ($statement->execute())
            {
                $updateCount++;
            }
        }
        
        $response["status"]["resolved"] = $updateCount;
    }
    
    echo json_encode($response);

?>