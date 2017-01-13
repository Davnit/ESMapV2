<?php

    $config = require("lib/Config.php");
    
?><html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
            var lastUpdate = null;
            var table = null;
            
            google.load("visualization", "1", { packages: [ "table" ] });
            google.setOnLoadCallback(startup);
            
            function startup() {
                table = new google.visualization.Table(document.getElementById("table"));
                
                populateTable();
                setInterval(populateTable, <?php echo (intval($config["page_refresh"]) * 1000); ?>);
            }
            
            function populateTable() {
                $.get("data/call_log.json").done(function(obj) {
                    var updateTime = new Date(obj.updated);

                    if (lastUpdate == null || updateTime > lastUpdate) {
                        lastUpdate = updateTime;
                        
                        var data = [
                            [ "Source", "Description", "Location", "Call Time", "Closed" ]
                        ];
                        
                        for (i = 0; i < obj.calls.length; i++) {
                            item = obj.calls[i];
                            data.push([ obj.sources[item.dept], item.desc, item.loc, item.time, item.closed ]);
                        }
                        
                        var options = {
                            showRowNumber: false,
                            width: "100%"
                        };
                        
                        table.draw(google.visualization.arrayToDataTable(data), options);
                    }
                });
            }
        </script>
        
        <link rel="stylesheet" href="css/main.css">
        <style type="text/css">
            #table {
                height: 100%;
            }
        </style>
        
        <title>ESMap - Call Log</title>
    </head>
    
    <body>
<?php include "header.php"; ?>

        <div id="content">
            <div id="table"></div>
        </div>
    </body>
</html>