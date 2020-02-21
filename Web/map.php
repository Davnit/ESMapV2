<?php

    $config = require("lib/Config.php");
    
    $siteTitle = $config["app_title"];
    if (strlen($config["maps_api_key"]) == 0) {
        die("Google Maps API key not set.
            For more information go to <a href=\"https://developers.google.com/maps/gmp-get-started\">Google Maps Platform Documentation</a>.");
    }
    
    $source = (isset($_GET["src"]) ? $_GET["src"] . "-map" : "livemap");
    
?><html>
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
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
        
        <link rel="icon" href="icon.png">
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
        
        <title>Live Map - <?php echo $siteTitle; ?></title>
<?php
    if (strlen($config["app_title"]) > 0) { ?>
        <meta property="og:title" content="Live Map - <?php echo $siteTitle; ?>" />
        <meta property="og:type" content="website" />
<?php }
    if (strlen($config["url_base"]) > 0) { ?>
        <meta property="og:url" content="<?php echo $config["url_base"]; ?>map" />
<?php } 
    if (strlen($config["og_site_name"]) > 0) { ?>
        <meta property="og:site_name" content="<?php echo $config["og_site_name"]; ?>"/>
<?php }
    if (strlen($config["og_description"]) > 0) { ?>
        <meta property="og:description" content="<?php echo $config["og_description"]; ?>" />
        <meta name="description" content="<?php echo $config["og_description"]; ?>" />
<?php }
    if (strlen($config["seo_keywords"]) > 0) { ?>
        <meta name="keywords" content="<?php echo $config["seo_keywords"]; ?>" />
<?php } ?>
        
        <meta name="viewport" content="initial-scale=0.75, user-scalable=false">
    </head>
    
    <body>
<?php include "header.php"; ?>

        <div id="content">
            <div id="map"></div>
        </div>
        
        <div id="footer">
            <div id="updateTime"></div>
        </div>
        
        <script type="text/javascript">
            var lastUpdate = null;
            var infoWindow = null;
            var timerID = null;
            var map = null;
            var markers = null;
            var initialized = false;
            
            var iconBase = "<?php echo $config["icon_bin"]; ?>";
            var icons = {
                Fire: { icon: iconBase + "fire.png" },
                FireGeneral: { icon: iconBase + "warning.png" },
                Alert: { icon: iconBase + "warning.png" },
                EMS: { icon: iconBase + "medical.png" },
                Patrol: { icon: iconBase + "patrol.png" },
                Police: { icon: iconBase + "police.png" },
                Hazmat: { icon: iconBase + "biohazard.png" },
                Death: { icon: iconBase + "death.png" },
                Traffic: { icon: iconBase + "traffic.png" }
            };
            
            function startup() {
                var options = {
                    center: { lat: 28.48449, lng: -81.25188 },
                    gestureHandling: 'greedy',
                    zoom: 12
                };
                map = new google.maps.Map(document.getElementById("map"), options);
                infoWindow = new google.maps.InfoWindow();
                
                markers = [];
                populateMap();
                    
                timerID = setInterval(populateMap, <?php echo (intval($config["page_refresh"]) * 1000); ?>);
            }
            
            function populateMap() {
                $.ajax("data/<?php echo $source; ?>.json", { cache: false }).done(function(obj) {
                    var updateTime = new Date(0);
                    updateTime.setUTCSeconds(obj.updated);
                    
                    if (updateTime <= lastUpdate)
                        return;
                    
                    lastUpdate = updateTime;
                    document.getElementById("updateTime").innerHTML = "Updated: " + updateTime.toString();
                    
                    var geoHash = [];
                    
                    // Check over existing markers for updates and removals
                    for (var id in markers) {
                        var marker = markers[id];
                        
                        if (id in obj.calls) {
                            // Call still active, update info
                            var item = obj.calls[id];
                            
                            marker.setPosition(getUniquePoint(geoHash, item[0], item[1]));
                            marker.setIcon(icons[item[3]].icon);
                            marker.setTitle(item[2]);
                        } else {
                            // Call expired, remove from map.
                            marker.setMap(null);
                            delete markers[id];
                        }
                    }
                    
                    // Add new calls
                    for (id in obj.calls) {
                        var item = obj.calls[id];
                        
                        if (!(id in markers)) {
                            var marker = createMarker(id, item);
                            marker.setMap(map);
                            marker.setPosition(getUniquePoint(geoHash, item[0], item[1]));
                            
                            marker.addListener('click', function() { 
                                infoWindow.setContent(this.title + ' [<a href="./call?id=' + this.call_id + '">Details</a>]');
                                infoWindow.open(map, this);
                            });
                            
                            markers[id] = marker;
                        }
                    }
                    
                    // First time load, fit map to markers.
                    if (!initialized) {
                        var bounds = new google.maps.LatLngBounds();
                        for (var id in markers) {
                            bounds.extend(markers[id].getPosition());
                        }
                        map.setCenter(bounds.getCenter());
                        map.fitBounds(bounds);
                        initialized = true;
                    }
                });
            }
            
            function getUniquePoint(hashes, lat, lng) {
                var adjLat = lat;
                var adjLng = lng;
                var coords = adjLat + ',' + adjLng;
                
                while (hashes[coords] != null) {
                    adjLat = parseFloat(lat) + ((Math.random() - .5) / 5000);
                    adjLng = parseFloat(lng) + ((Math.random() - .5) / 5000);
                    coords = adjLat + ',' + adjLng;
                }
                hashes[coords] = 1;
                return { lat: adjLat, lng: adjLng };
            }
            
            function createMarker(id, item) {
                return new google.maps.Marker({
                    icon: icons[item[3]].icon,
                    title: item[2],
                    call_id: id
                });
            }
        </script>
        
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $config["maps_api_key"]; ?>&callback=startup"></script>
    </body>
</html>