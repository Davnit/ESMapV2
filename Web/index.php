<?php

    require_once "database.php";
    
    $sql = "SELECT c.id, s.tag, c.category, c.meta, c.expired FROM calls c ";
    $sql .= "LEFT JOIN sources s ON s.id = c.source ";
    $sql .= "WHERE expired IS NULL OR (expired >= NOW() - INTERVAL 1 HOUR)";
    
    $statement = $db->prepare($sql);
    $statement->execute();
    
    $result = $statement->fetchAll();
    
    function getTD($value)
    {
        return "<td>$value</td>";
    }
    
    if ($statement->rowCount() > 0)
    {
        print("<table>\r\n");
        print("\t<tr><th>ID</th><th>Source</th><th>Category</th><th>Time</th><th>Description</th><th>Location</th><th>Status</th></tr>\r\n");
        foreach ($result as $row)
        {
            $meta = json_decode($row["meta"]);
            
            print("\t<tr>");
            print(getTD($row["id"]));
            print(getTD($row["tag"]));
            print(getTD($row["category"]));
            print(getTD($meta->call_time));
            print(getTD($meta->description));
            print(getTD($meta->location));
            print(getTD(strlen($row["expired"]) > 0 ? "Closed" : "Active"));
            print("</tr>");
        }
        print("</table>");
    }
    else
    {
        print("No active calls.");
    }

?>