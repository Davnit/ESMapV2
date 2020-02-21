<?php
    
    $config = require("lib/Config.php");
    
    function showError($message)
    {
        die($message);
    }
    
    if (!isset($_GET["id"]))
    {
        showError("Error: No call specified.");
    }
    
    $id = $_GET["id"];
    if (!is_numeric($id))
    {
        showError("Error: Invalid call ID");
    }
    
    $id = intval($id);
    
    require_once "lib/Database.php";
    
    $db_prefix = $config["db_prefix"];
    
    $sql = "SELECT c.category, c.added, c.expired, c.meta, g.latitude, g.longitude, s.tag, s.time_zone FROM " . $db_prefix . "calls c ";
    $sql .= "LEFT JOIN " . $db_prefix . "geocodes g on g.id = c.geoid ";
    $sql .= "LEFT JOIN " . $db_prefix . "sources s on s.id = c.source ";
    $sql .= "WHERE c.id = ?";
    
    $statement = $db->prepare($sql);
    $statement->execute([ $id ]);
    
    if ($statement->rowCount() == 0)
    {
        showError("Error: Call not found.");
    }
    
    $data = $statement->fetch(PDO::FETCH_ASSOC);
    
    $utc_tz = new DateTimeZone("Etc/UTC");
    $local_tz = new DateTimeZone($data["time_zone"]);
    
    $table = array(
        "Source" => $data["tag"],
        "Category" => $data["category"],
        "Found" => (new DateTime($data["added"], $utc_tz))->setTimezone($local_tz)->format("Y-m-d H:i:s"),
        "Closed" => (new DateTime($data["expired"], $utc_tz))->setTimezone($local_tz)->format("Y-m-d H:i:s")
    );
    
    $meta = json_decode($data["meta"], true);
    foreach ($meta as $key => $value)
    {
        $dispKey = ucwords(str_replace("_", " ", $key));
        $table[$dispKey] = $value;
    }
    
    $isMapped = (isset($data["latitude"]) and isset($data["longitude"]));
    
?>
<html>
    <head>        
        <link rel="stylesheet" href="css/main.css">
        <style type="text/css">
            #map {
                float: left;
                height: 100%;
                width: 50%;
                text-align: center;
            }

            #call_info {
                float: left;
                height: 100%;
                width: 50%;
            }

            table {
                width: 100%;
                height: 100%;
            }

            tr:nth-child(even) {
                background-color: #f2f2f2;
            }

            td {
                padding: 10px;
                font-size: 1.5em;
            }

            td:first-child {
                width: 25%;
                font-weight: bold;
                text-align: center;
            }
        </style>
        
        <title>Call Details - <?php echo $config["app_title"]; ?></title>
    </head>
    
    <body>
<?php include "header.php"; ?>

        <div id="content">
            <div id="map"></div>
            <div id="call_info">
                <table>
<?php

    foreach ($table as $key => $value)
    {
        printf("\t\t\t\t\t<tr><td>$key</td><td>$value</td></tr>\n");
    }

?>
            </div>
        </div>
        
        <script type="text/javascript">
            function drawMap() {
                <?php if ($isMapped) echo "var position = { lat: " . $data["latitude"] . ", lng: " . $data["longitude"] . " };"; ?>
                var options = {
                    gestureHandling: 'greedy',
                    <?php
                        if ($isMapped) {
                            echo "center: position,\n";
                            echo "zoom: 16\n";
                        } else {
                            echo "center: { lat: 28.48449, lng: -81.25188 },\n";
                            echo "zoom: 12";
                        }
                    ?>
                };
                
                var map = new google.maps.Map(document.getElementById("map"), options);
                <?php if ($isMapped) echo "var marker = new google.maps.Marker({ map: map, position: position });"; ?>
            }
        </script>
        
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $config["maps_api_key"]; ?>&callback=drawMap"></script>
    </body>
</html>