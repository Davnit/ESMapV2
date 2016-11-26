<?php

    function reportError($message)
    {
        $response = array();
        $response["status"]["success"] = false;
        $response["status"]["message"] = $message;
        echo json_encode($response);
        return;
    }
    
    if (!isset($_POST["calldata"])) {
        reportError("No data provided.");
        die();
    }
    
    $data = json_decode($_POST["calldata"], true);
    
    if ($data == null) {
        reportError("Invalid data: " . json_last_error_msg());
        die();
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
    
    # Update the database
    if (count($data["new"]) > 0) 
    {
        $newCount = insertRows("calls", $fields, $td, true);
    }
    if (count($data["expired"]) > 0) 
    {
        $expCount = updateTimestamps("calls", "expired", "cid", $data["expired"]);
    }
    
    $response = array();
    $response["status"]["success"] = true;
    $response["status"]["added"] = isset($newCount) ? $newCount : 0;
    $response["status"]["expired"] = isset($expCount) ? $expCount : 0;
    
    echo json_encode($response);

?>