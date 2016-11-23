<?php

    require_once "database.php";
    
    $statement = $db->prepare("SELECT source, cid FROM calls WHERE expired IS NULL ORDER BY id ASC");
    if (!$statement->execute())
    {
        print("FAIL " . $statement->errorInfo());
    }
    
    $result = $statement->fetchAll();
    
    if ($statement->rowCount() > 0)
    {
        foreach ($result as $row)
        {
            print($row["source"] . "|" . $row["cid"] . "\r\n");
        }
    }
    else
    {
        print("FAIL No results");
    }
    
?>