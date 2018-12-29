<?php

    return array(
        "db_host" => "localhost",
        "db_name" => "esmap",
        "db_user" => "esmap",
        "db_pass" => "",
        "db_prefix" => "",
        
        "app_title" => "Orange County Emergency Services Activity",
        "client_key" => "",
        "maps_api_key" => "",
        
        "page_refresh" => 60,               # Time, in seconds, between updates of the main content pages (live map. call list)
        "map_activity_delay" => 300,        # Time, in seconds, to wait for page updates after user interaction
        "icon_bin" => "icons/",             # Location where map icons are stored
        "history_time" => "1 HOUR"          # Calls that have expired within this time are shown on the live map.
    );

?>