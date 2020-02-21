<?php

    $config = require("lib/Config.php");
    
    $siteTitle = $config["app_title"];
    
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
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">
            var lastUpdate = null;
            var table = null;
            
            var IDList = {};
            
            google.charts.load("current", { "packages": [ "table" ] });
            google.charts.setOnLoadCallback(startup);
            
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
                        
                        var data = [];
                        
                        var counter = 0;
                        for (var id in obj.calls) {
                            item = obj.calls[id];
                            item[0] = obj.sources[item[0]];
                            data.push(item);
                            
                            IDList[counter] = id;
                            counter++;
                        }
                        
                        // Add the header last and reverse the table.
                        //   This changes the default order in which calls are shown. Latest first.
                        data.push([ "Source", "Description", "Location", "Call Time", "Closed" ]);
                        data = data.reverse();
                        
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
                        window.location.href = "call.php?id=" + IDList[(Object.keys(IDList).length - 1) - row];
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
        
        <title>Call Log - <?php echo $siteTitle; ?></title>
<?php
    if (strlen($config["app_title"]) > 0) { ?>
        <meta property="og:title" content="Call Log - <?php echo $siteTitle; ?>" />
        <meta property="og:type" content="website" />
<?php }
    if (strlen($config["url_base"]) > 0) { ?>
        <meta property="og:url" content="<?php echo $config["url_base"]; ?>list" />
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
    </head>
    
    <body>
<?php include "header.php"; ?>

        <div id="content">
            <div id="table"></div>
        </div>
    </body>
</html>