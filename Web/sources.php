<?php

    require_once "database.php";
    
    $statement = $db->prepare("SELECT * FROM sources");
    if (!$statement->execute())
    {
        die("FAIL " . $statement->errorInfo());
    }
    
    $result = $statement->fetchAll();
    
    if ($statement->rowCount() > 0)
    {
        foreach ($result as $row)
        {
            print(implode("|", [ $row["id"], $row["tag"], $row["url"], $row["parser"], $row["interval"] ]) . "\r\n");
        }
    }
    else
    {
        die("FAIL No results");
    }

?>