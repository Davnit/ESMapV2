<?php
    
    $config = require("Config.php");
    
    $dsn = "mysql:host=" . $config["db_host"] . ";dbname=" . $config["db_name"];
    $db = new PDO($dsn, $config["db_user"], $config["db_pass"]);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db_prefix = $config["db_prefix"];
    
    function getData($sql, $values = array())
    {
        global $db;
    
        $statement = $db->prepare($sql);
        $statement->execute($values);
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function insertRows($table, $fields, $data, $handleDuplicates = false)
    {
        global $db, $db_prefix;
        
        $db->beginTransaction();
        
        $values = array();
        $rows = array();
        
        // Build a row for each element of the data
        if (count($fields) > 1)
        {
            foreach ($data as $d)
            {
                $rows[] = "(" . implode(",", array_fill(0, sizeof($d), "?")) . ")";     # Value placeholders
                $values = array_merge($values, array_values($d));                       # Value list
            }
        }
        else
        {
            // Single value
            $rows = array_fill(0, count($data), "(?)");
            $values = array_values($data);
        }
        
        // Build the query
        $sql = sprintf("INSERT INTO %s%s (%s) VALUES %s", $db_prefix, $table, implode(",", $fields), implode(",", $rows));
        if ($handleDuplicates !== false)
        {
            $sql .= " ON DUPLICATE KEY " . $handleDuplicates;
        }
        
        $statement = $db->prepare($sql);
        $statement->execute($values);
        
        $db->commit();
        
        return $statement->rowCount();
    }
    
    function updateTimestamps($table, $stampField, $keyField = "", $keyValues = null)
    {
        global $db, $db_prefix;
        
        $db->beginTransaction();
        
        if (strlen($keyField) > 0 and $keyValues !== null and sizeof($keyValues) > 0)
        {
            $sql = sprintf("UPDATE %s%s SET %s = NOW() WHERE %s IN (%s)", $db_prefix, $table, $stampField, $keyField, implode(",", array_fill(0, sizeof($keyValues), "?")));
            $statement = $db->prepare($sql);
            $statement->execute($keyValues);
        }
        else
        {
            $sql = sprintf("UPDATE %s%s SET %s = NOW()", $db_prefix, $table, $stampField);
            $statement = $db->prepare($sql);
            $statement->execute();
        }
        
        $db->commit();
        
        return $statement->rowCount();
    }

?>