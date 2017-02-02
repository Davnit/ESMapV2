<?php

    $config = require("lib/Config.php");
    
?><html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
            var lastUpdate = null;
            var table = null;
            
            var IDList = {}
            
            google.load("visualization", "1", { packages: [ "table" ] });
            google.setOnLoadCallback(startup);
            
            function startup() {
                table = new google.visualization.Table(document.getElementById("table"));
                google.visualization.events.addListener(table, 'select', selectHandler);
                
                populateTable();
                setInterval(populateTable, <?php echo (intval($config["page_refresh"]) * 1000); ?>);
            }
            
            function populateTable() {
                $.ajax("data/call_log.json", { cache: false }).done(function(obj) {
                    var updateTime = new Date(obj.updated);

                    if (lastUpdate == null || updateTime > lastUpdate) {
                        lastUpdate = updateTime;
                        
                        var data = [
                            [ "Source", "Description", "Location", "Call Time", "Closed" ]
                        ];
                        
                        var counter = 0;
                        for (var id in obj.calls) {
                            item = obj.calls[id];
                            item[0] = obj.sources[item[0]];
                            data.push(item);
                            
                            IDList[counter] = id;
                            counter++;
                        }
                        
                        var options = {
                            showRowNumber: false,
                            width: "100%"
                        };
                        
                        table.draw(google.visualization.arrayToDataTable(data), options);
                    }
                });
            }
            
            function selectHandler() {
                var selection = table.getSelection();
                if (selection.length > 0) {
                    var row = selection[0].row;
                    if (row in IDList) {
                        window.location.href = "call.php?id=" + IDList[row];
                    }
                }
            }
        </script>
        
        <link rel="stylesheet" href="css/main.css">
        <style type="text/css">
            #table {
                height: 100%;
            }
        </style>
        
        <title>Call Log - <?php echo $config["app_title"]; ?></title>
    </head>
    
    <body>
<?php include "header.php"; ?>

        <div id="content">
            <div id="table"></div>
        </div>
    </body>
</html>