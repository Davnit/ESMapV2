<?php
    
    if (isset($_GET["src"])) {
        $trail = "?src=" . $_GET["src"];
    } else {
        $trail = "";
    }
    
?>
        <div id="header">
            <h1><?php echo $config["app_title"]; ?></h1>
            <div id="menu">
                <ul>
                    <li><a href="./map<?php echo $trail; ?>">Live Map</a></li>
                    <li><a href="./list<?php echo $trail; ?>">Call List</a></li>
<?php
    if (strlen($config["about_url"]) > 0) { ?>
                    <li><a href="<?php echo $config["about_url"]; ?>">About</a></li>
<?php } ?>
                </ul>
            </div>
        </div>