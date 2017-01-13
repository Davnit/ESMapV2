<?php

    $config = require("lib/Config.php");
    
?><html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
            var lastUpdate = null;
            var map = null;
            
            google.load("visualization", "1", { packages: [ "map" ] });
            google.setOnLoadCallback(startup);
            
            function startup() {
                map = new google.visualization.Map(document.getElementById("map"));
                
                populateMap();
                setInterval(populateMap, <?php echo (intval($config["page_refresh"]) * 1000); ?>);
            }
            
            function populateMap() {
                $.get("data/livemap.json").done(function(obj) {
                    var updateTime = new Date(obj.updated);
                    
                    if (lastUpdate == null || updateTime > lastUpdate) {
                        lastUpdate = updateTime;
                        
                        var data = [
                            [ "Latitude", "Longitude", "Description" ]
                        ];
                        
                        for (i = 0; i < obj.calls.length; i++) {
                            item = obj.calls[i];
                            data.push([ item.lat, item.lng, item.desc ]);
                        }
                    
                        var options = {
                            showTip: true, 
                            enableScrollWheel: true, 
                            mapType: "normal",
                            useMapTypeControl: true
                        };
                        
                        map.draw(google.visualization.arrayToDataTable(data), options);
                    }
                });
            }
        </script>
        
        <link rel="stylesheet" href="css/main.css">
        <style type="text/css">
            #map {
                height: 100%;
            }
        </style>
        
        <title>ESMap - Call Map</title>
    </head>
    
    <body>
<?php include "header.php"; ?>

        <div id="content">
            <div id="map"></div>
        </div>
    </body>
</html>