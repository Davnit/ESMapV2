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
    
    $sql = "SELECT c.category, c.added, c.expired, c.meta, g.latitude, g.longitude, s.tag FROM calls c ";
    $sql .= "LEFT JOIN geocodes g on g.id = c.geoid ";
    $sql .= "LEFT JOIN sources s on s.id = c.source ";
    $sql .= "WHERE c.id = ?";
    
    $statement = $db->prepare($sql);
    $statement->execute([ $id ]);
    
    if ($statement->rowCount() == 0)
    {
        showError("Error: Call not found.");
    }
    
    $data = $statement->fetch(PDO::FETCH_ASSOC);
    
    $table = array(
        "Source" => $data["tag"],
        "Category" => $data["category"],
        "Found" => $data["added"],
        "Closed" => $data["expired"],
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
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
            google.load("visualization", "1", { packages: [ "map" ] });
            google.setOnLoadCallback(drawMap);
            
            function drawMap() {
                var data = google.visualization.arrayToDataTable([
                    [ "Latitude", "Longitude" ],
                    <?php echo ($isMapped ? "[" . $data["latitude"] . ", " . $data["longitude"] . "]" : "[0,0]") . "\n"; ?>
                ]);
                
                var options = {
                    showTip: false,
                    enableScrollWheel: true,
                    mapType: 'normal',
                    useMapTypeControl: true,
                    zoomLevel: 16
                };
                
                <?php if (!$isMapped) echo "//"; ?>var map = new google.visualization.Map(document.getElementById("map"));
                <?php if (!$isMapped) echo "//"; ?>map.draw(data, options);
            }
        </script>
        
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
            <div id="map"><?php if (!$isMapped) echo "<table><tr><td>No map data.</td></tr></table>"; ?></div>
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
    </body>
</html>