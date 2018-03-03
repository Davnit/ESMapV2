<?php

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
    
    // Check for request
    if (!isset($_GET["request"]))
    {
        reportError("No request");
    }
    
    // Validate request
    $request = $_GET["request"];
    if (!is_numeric($request))
    {
        reportError("Invalid request");
    }
    
    try
    {
        require_once "lib/Database.php";
    }
    catch (PDOException $pe)
    {
        reportError("Database not available.");
    }
    
    $request = intval($request);
    
    // Default response
    $response = array(
        "status" => array(
            "success" => true
        ),
        "data" => array()
    );
    
    switch ($request)
    {
        case 1:     # Source assignments
            $data = getData("SELECT id, tag, url, parser, update_time FROM sources ORDER BY id");
            
            $sources = array();
            foreach ($data as $d)
            {
                $sources[$d["id"]] = array($d["tag"], $d["url"], $d["parser"], $d["update_time"]);
            }
            $response["data"] = $sources;
            break;
            
        case 2:     # Client/server sync
            $sql = "SELECT c.source, c.cid, g.location, c.category, c.meta FROM calls c ";
            $sql .= "LEFT JOIN geocodes g ON c.geoid = g.id ";
            $sql .= "WHERE expired IS NULL ORDER BY c.id ASC";
            $data = getData($sql);
            
            $calls = array();
            foreach ($data as $d)
            {
                $src = intval($d["source"]);
                if (!array_key_exists($src, $calls))
                    $calls[$src] = array();
                
                $calls[$src][$d["cid"]] = array($d["category"], $d["location"], $d["meta"]);
            }
            $response["data"] = $calls;
            break;
            
        case 3:     # Geocode requests
            $data = getData("SELECT id, location FROM geocodes WHERE results IS NULL AND latitude IS NULL AND longitude IS NULL ORDER BY id DESC LIMIT 20");
            
            $geo = array();
            foreach ($data as $d)
            {
                $geo[$d["id"]] = $d["location"];
            }
            $response["data"] = $geo;
            break;
            
        default:
            reportError("Unrecognized request");
            die();
    }
    
    print(json_encode($response));

?>