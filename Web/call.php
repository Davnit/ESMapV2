<?php
    
    $config = require("lib/Config.php");
    
    $siteTitle = $config["app_title"];
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
    
    $sql = "SELECT c.category, c.cid, c.added, c.expired, c.meta, g.latitude, g.longitude, s.tag, s.time_zone FROM " . $db_prefix . "calls c ";
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
    
    if ($data["expired"] == null) {
        $table["Closed"] = "";
    }
    
    $meta = json_decode($data["meta"], true);
    foreach ($meta as $key => $value)
    {
        $dispKey = ucwords(str_replace("_", " ", $key));
        $table[$dispKey] = $value;
    }
    
    $isMapped = (isset($data["latitude"]) and isset($data["longitude"]));
    
    // For page metadata
    $callNumber = (in_array("call_number", $meta) ? $meta["call_number"] : $data["cid"]);
    $pageDesc = "Details for " . $data["tag"] . " call #" . $callNumber . ": " . $meta["description"] . " at " . $meta["location"] . ".";
    
?>
<html>
    <head>
<?php if (strlen($config["analytics_tag"]) > 0) {?>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $config["analytics_tag"]; ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '<?php echo $config["analytics_tag"]; ?>');
        </script>
<?php } ?>
        <link rel="icon" href="icon.png">
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
        
        <title>Call Details - <?php echo $siteTitle; ?></title>
<?php
    if (strlen($config["app_title"]) > 0) { ?>
        <meta property="og:title" content="Call Details - <?php echo $siteTitle; ?>" />
        <meta property="og:type" content="object" />
<?php }
    if (strlen($config["url_base"]) > 0) { ?>
        <meta property="og:url" content="<?php echo $config["url_base"] . "call?id=$id"; ?>" />
<?php } 
    if (strlen($config["og_site_name"]) > 0) { ?>
        <meta property="og:site_name" content="<?php echo $config["og_site_name"]; ?>"/>
<?php } ?>
        <meta property="og:description" content="<?php echo $pageDesc; ?>" />
        <meta name="description" content="<?php echo $pageDesc; ?>" />
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