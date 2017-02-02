<?php

    $config = require("lib/Config.php");
    
?><html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
            var lastUpdate = null;
            var timerID = null;
            var map = null;
            
            google.load("visualization", "1", { packages: [ "map" ] });
            google.setOnLoadCallback(startup);
            
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
                        
                        var data = [
                            [ "Latitude", "Longitude", "Description", "Marker" ]
                        ];
                        
                        for (i = 0; i < obj.calls.length; i++) {
                            item = obj.calls[i];
                            data.push([ item.lat, item.lng, item.desc, item.category ]);
                        }
                    
                        var iconBin = "<?php echo $config["icon_bin"]; ?>";
                        
                        var options = {
                            showTip: true, 
                            enableScrollWheel: true, 
                            mapType: "normal",
                            useMapTypeControl: true,
                            
                            icons: {
                                General: {
                                    normal: iconBin + "default.png",
                                },
                                Police: {
                                    normal: iconBin + "Police.png",
                                },
                                Fire: {
                                    normal: iconBin + "Fire.png",
                                },
                                EMS: {
                                    normal: iconBin + "Help.png",
                                },
                                Traffic: {
                                    normal: iconBin + "Traffic.png"
                                }
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
        </style>
        
        <title>Live Map - <?php echo $config["app_title"]; ?></title>
    </head>
    
    <body>
<?php include "header.php"; ?>

        <div id="content">
            <div id="map"></div>
        </div>
    </body>
</html>