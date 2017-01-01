<?php

    // Check for request
    if (!isset($_GET["request"]))
    {
        die("FAIL No request");
    }
    
    // Validate request
    $request = $_GET["request"];
    if (!is_numeric($request))
    {
        die("FAIL Bad request");
    }
    
    require_once "database.php";
    
    $request = intval($request);
    
    switch ($request)
    {
        case 1:     # Source assignments
            $data = getData("SELECT * FROM sources");
            
            foreach ($data as $d)
            {
                print(implode("|", [ $d["id"], $d["tag"], $d["url"], $d["parser"], $d["interval"] ]) . "\r\n");
            }
            break;
            
        case 2:     # Client/server sync
            $data = getData("SELECT source, cid FROM calls WHERE expired IS NULL ORDER BY id ASC");
            
            foreach ($data as $d)
            {
                print(implode("|", [ $d["source"], $d["cid"] ]) . "\r\n");
            }
            break;
            
        case 3:     # Geocode requests
            $data = getData("SELECT id, location FROM geocodes WHERE resolved IS NULL");
            
            $geo = array();
            foreach ($data as $d)
            {
                $geo[$d["id"]] = $d["location"];
            }
            print(json_encode(array("geocodes" => $geo)));
            break;
            
        default:
            die("FAIL Unrecognized request");
    }

?>