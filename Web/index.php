<?php

    $config = require("lib/Config.php");
    
?><html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">
            var lastUpdate = null;
            var timerID = null;
            var map = null;
            
            google.charts.load("current", { "packages": [ "map" ] });
            google.charts.setOnLoadCallback(startup);
            
            function startup() {
                var mapDiv = document.getElementById("map");
                mapDiv.addEventListener("mousedown", resetTimer);
                
                map = new google.visualization.Map(mapDiv);
                
                populateMap();
                timerID = setInterval(populateMap, <?php echo (intval($config["page_refresh"]) * 1000); ?>);
            }
            
            function populateMap() {
                $.ajax("data/livemap.json", { cache: false }).done(function(obj) {
                    var updateTime = new Date(obj.updated);
                    
                    if (lastUpdate == null || updateTime > lastUpdate) {
                        lastUpdate = updateTime;
                        
                        var localTime = new Date(0);
                        localTime.setUTCSeconds(obj.updated);
                        document.getElementById("updateTime").innerHTML = "Updated: " + localTime.toString();
                        
                        var data = [
                            [ "Latitude", "Longitude", "Description", "Marker" ]
                        ];
                        
                        var geoHash = new Array();
                        var coords, adjLat, adjLng;
                        
                        for (var id in obj.calls) {
                            item = obj.calls[id];
                            item[2] = '<a href="./call.php?id=' + id + '">' + item[2] + '</a>';
                            
                            adjLat = item[0];
                            adjLng = item[1];
                            coords = adjLat + ',' + adjLng;
                            while (geoHash[coords] != null) {
                                adjLat = parseFloat(item[0]) + ((Math.random() - .5) / 5000);
                                adjLng = parseFloat(item[1]) + ((Math.random() - .5) / 5000);
                                coords = adjLat + ',' + adjLng;
                            }
                            geoHash[coords] = 1;
                            
                            item[0] = adjLat;
                            item[1] = adjLng;
                            data.push(item);
                        }
                    
                        var iconBin = "<?php echo $config["icon_bin"]; ?>";
                        
                        var options = {
                            showTooltip: false,
                            showInfoWindow: true,
                            enableScrollWheel: true, 
                            mapType: "normal",
                            useMapTypeControl: true,
                            
                            icons: {
                                Fire: { normal: iconBin + "fire.png" },
                                FireGeneral: { normal: iconBin + "warning.png" },
                                Alert: { normal: iconBin + "warning.png" },
                                EMS: { normal: iconBin + "medical.png" },
                                Patrol: { normal: iconBin + "patrol.png" },
                                Police: { normal: iconBin + "police.png" },
                                Hazmat: { normal: iconBin + "biohazard.png" },
                                Death: { normal: iconBin + "death.png" },
                                Traffic: { normal: iconBin + "traffic.png" }
                            }
                        };
                        
                        map.draw(google.visualization.arrayToDataTable(data), options);
                    }
                });
            }
            
            function resetTimer() {
                clearInterval(timerID);
                timerID = setInterval(populateMap, <?php echo (intval($config["map_activity_delay"]) * 1000); ?>);
            }
        </script>
        
        <link rel="stylesheet" href="css/main.css">
        <style type="text/css">
            #map {
                height: 100%;
            }
            
            #footer {
                background-color: #143253;
                display: table-row;
                color: white;
            }
            
            #updateTime {
                padding: 2px;
            }
        </style>
        
        <title>Live Map - <?php echo $config["app_title"]; ?></title>
    </head>
    
    <body>
<?php include "header.php"; ?>

        <div id="content">
            <div id="map"></div>
        </div>
        
        <div id="footer">
            <div id="updateTime"></div>
        </div>
    </body>
</html>