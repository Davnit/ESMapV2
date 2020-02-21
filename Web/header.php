        <div id="header">
            <h1>Emergency Services Activity - Orange County, FL</h1>
            <div id="menu">
                <ul>
                    <li><a href="./index.php">Live Map</a></li>
                    <li><a href="./list.php">Call List</a></li>
<?php
    if (strlen($config["about_url"]) > 0) { ?>
                    <li><a href="<?php echo $config["about_url"]; ?>">About</a></li>
<?php } ?>
                </ul>
            </div>
        </div>