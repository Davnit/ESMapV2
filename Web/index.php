<html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
            google.load("visualization", "1", { packages: [ "map" ] });
            google.setOnLoadCallback(populateMap);
            
            function populateMap() {
                $.get("current.json").done(function(obj) {
                    var data = [
                        [ "Latitude", "Longitude", "Description" ]
                    ];
                    
                    for (i = 0; i < obj.calls.length; i++) {
                        item = obj.calls[i];
                        if (item.latitude != null && item.longitude != null) {
                            data.push([ item.latitude, item.longitude, item.meta.description + " @ " + item.meta.location ]);
                        }
                    }
                    
                    var options = {
                        showTip: true, 
                        enableScrollWheel: true, 
                        mapType: "normal",
                        useMapTypeControl: true
                    };
                    
                    var map = new google.visualization.Map(document.getElementById("map"));
                    map.draw(google.visualization.arrayToDataTable(data), options);
                });
            }
        </script>
        
        <style type="text/css">
            #content {
                position: absolute;
                top: 0;
                bottom: 0;
                left: 0;
                width: 100%;
            }
            
            #map {
                height: 100%;
            }
        </style>
        
        <title>ESMap - Call Map</title>
    </head>
    
    <body>
        <div id="content">
            <div id="map"></div>
        </div>
    </body>
</html>