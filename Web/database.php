<?php
    
    $config = require("config.php");
    
    $dsn = "mysql:host=" . $config["db_host"] . ";dbname=" . $config["db_name"];
    $db = new PDO($dsn, $config["db_user"], $config["db_pass"]);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    
    function insertRows($table, $fields, $data, $ignoreDuplicates = false)
    {
        global $db;
        
        $db->beginTransaction();
        
        $values = array();
        $rows = array();

        // Build a row for each element of the data
        foreach ($data as $d)
        {
            $rows[] = "(" . implode(",", array_fill(0, sizeof($d), "?")) . ")";     // Values with ? placeholder
            $values = array_merge($values, array_values($d));            
        }
        
        $sql = sprintf("INSERT INTO %s (%s) VALUES %s", $table, implode(",", $fields), implode(",", $rows));
        if ($ignoreDuplicates)
        {
            $sql .= " ON DUPLICATE KEY UPDATE " . $fields[0] . "=" . $fields[0];
        }
        $db->prepare($sql)->execute($values);
        
        $db->commit();
    }
    
    function updateTimestamps($table, $stampField, $keyField = "", $keyValues = null)
    {
        global $db;
        
        $db->beginTransaction();
        
        if (strlen($keyField) > 0 and $keyValues !== null and sizeof($keyValues) > 0)
        {
            $sql = sprintf("UPDATE %s SET %s = NOW() WHERE %s IN (%s)", $table, $stampField, $keyField, implode(",", array_fill(0, sizeof($keyValues), "?")));
            $db->prepare($sql)->execute($keyValues);
        }
        else
        {
            $sql = sprintf("UPDATE %s SET %s = NOW()", $table, $stampField);
            $db->prepare($sql)->execute();
        }
        
        $db->commit();
    }

?>