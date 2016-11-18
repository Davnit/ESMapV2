<?php

    if (!isset($_POST["calldata"])) {
        die("ERROR: No data provided.");
    }
    
    $data = json_decode($_POST["calldata"]);
    
    if ($data == null) {
        die("ERROR: Invalid data.");
    }
    
    print("Source: $data->source\r\n");
    print("New calls: " . count($data->new) . "\r\n");
    print("Expired calls: " .count($data->expired));

?>