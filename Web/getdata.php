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
    
    require_once "lib/Locations.php";
    
    $request = intval($request);
    
    // Default response
    $response = array(
        "status" => array(
            "success" => true
        ),
        "data" => array()
    );
    
    // from the database.php include
    $db_prefix = $config["db_prefix"];
    
    switch ($request)
    {
        case 1:     # Source assignments
            $data = getData("SELECT id, tag, url, parser, update_time FROM " . $db_prefix . "sources");
            
            $sources = array();
            foreach ($data as $d)
            {
                $sources[$d["id"]] = array($d["tag"], $d["url"], $d["parser"], $d["update_time"]);
            }
            $response["data"] = $sources;
            break;
            
        case 2:     # Client/server sync
            $sql = "SELECT c.source, c.cid, g.location, c.category, c.meta FROM " . $db_prefix . "calls c ";
            $sql .= "LEFT JOIN " . $db_prefix . "geocodes g ON c.geoid = g.id ";
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
            $data = getData("SELECT id, location FROM " . $db_prefix . "geocodes WHERE results IS NULL AND latitude IS NULL AND longitude IS NULL ORDER BY id DESC LIMIT 20");
            
            $geo = array();
            foreach ($data as $d)
            {
                $geo[$d["id"]] = $d["location"];
            }
            $response["data"] = $geo;
            break;
            
        case 4:     # Inter-server sync (for dev only)
            $table = (isset($_GET["table"]) ? $_GET["table"] : "calls");
            if (!in_array($table, array("calls", "sources", "geocodes"))) {
                reportError("Invalid sync table name");
            }
            $order = ($table == "sources" ? "id ASC" : "added ASC");
            $limit = (isset($_GET["limit"]) && is_numeric($_GET["limit"]) ? intval($_GET["limit"]) : 1000);
            $values = array();
            
            $sql = "SELECT * FROM " . $db_prefix . $table;
            if ($table !== "sources") {
                if (isset($_GET["start"]) && is_numeric($_GET["start"])) {
                    # Start at a specific row ID
                    $sql .= " WHERE id >= ?";
                    $values[] = intval($_GET["start"]);
                } elseif (isset($_GET["since"])) {
                    # Start at a time
                    $sql .= " WHERE added >= ?";
                    $values[] = $_GET["since"];
                } else {
                    # Return most recent items
                    $order = "added DESC";
                }
                $sql .= " ORDER BY $order LIMIT $limit";
            }
            
            $response["data"] = getData($sql . ";", $values);
            break;
            
        default:
            reportError("Unrecognized request");
            die();
    }
    
    print(json_encode($response));

?>