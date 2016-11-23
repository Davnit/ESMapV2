<?php

    if (!isset($_POST["calldata"])) {
        die("ERROR: No data provided.");
    }
    
    $data = json_decode($_POST["calldata"], true);
    
    if ($data == null) {
        die("ERROR: Invalid data: " . json_last_error_msg());
    }
    
    // Access the database
    require_once "database.php";
    
    // Static data
    $fields = [ "source", "cid", "category", "meta" ];
    $source = $data["source"];
    
    // Build rows
    $td = array();
    foreach ($data["new"] as $row)
    {
        $td[] = [ $source, $row["key"], $row["category"], json_encode($row["meta"]) ];
    }
    
    if (count($data["new"]) > 0) insertRows("calls", $fields, $td, true);
    if (count($data["expired"]) > 0) updateTimestamps("calls", "expired", "cid", $data["expired"]);
    
    printf("Source: %s\r\n", $source);
    printf("New calls: %d\r\n", count($data["new"]));
    printf("Expired calls: %d", count($data["expired"]));

?>