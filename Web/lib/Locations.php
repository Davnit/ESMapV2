<?php
    
    function extractLocationData($dataArray)
    {
        // Validate input type
        if (is_array($dataArray) == false)
            return null;
        
        // Validate input content
        if (array_key_exists("location", $dataArray) == false)
            return null;
        
        if (strlen(trim($dataArray["location"])) < 1)
            return null;
        
        // default values
        $data = array("location" => $dataArray["location"], "latitude" => null, "longitude" => null);
        
        // Check for provided coordinates
        if (array_key_exists("geo_lat", $dataArray) and array_key_exists("geo_lng", $dataArray))
        {
            $data["latitude"] = $dataArray["geo_lat"];
            $data["longitude"] = $dataArray["geo_lng"];
        }
        
        return $data;
    }
    
    function processLocation($location)
    {
        $removeAll = array("EBO", "EB", "WBO", "WB", "NBO", "NB", "SBO", "SB", "EX");
        $removeStart= array("#", "APT");
        
        $suffixes = array("RD", "DR", "ST", "TRL", "AVE", "LN", "BLVD", "WAY", "PKWY", "CIR", "CT", "PL", "TER");
        
        // normal -> [ abbreviations ]
        $abbrev = array(
            "RD" => array("ROAD"), "ST" => array("STREET"), "DR" => array("DRIVE"), "CT" => array("COURT"),
            "LN" => array("LANE"), "WAY" => array("WY"), "PL" => array("PLC", "PLACE"), "TER" => array("TERRACE"),
            "PKWY" => array("PY", "PK", "PKY", "PARKWAY"), "AVE" => array("AV", "AVENUE"),
            "TRL" => array("TL", "TR", "TRAIL"), "CIR" => array("CR", "CIRCLE"), "PLZ" => array("PZ", "PLAZA"),
            "BLVD" => array("BV", "BVD", "BOULEVARD"), "EXPRESSWAY" => array("EXP", "EXPY")
        );
        
        $location = str_replace("/", " AND ", $location);
        $location = str_replace("  ", " ", $location);
        
        $returnLocation = array();
        
        $intersection = !(strpos($location, " AND ") == false);
        $addresses = explode(" AND ", $location);
        foreach ($addresses as $address)
        {
            $thisAddress = array();
            
            $elements = explode(" ", $address);
            
            // Check for a street number
            if (($intersection == false) and (ctype_digit($elements[0]) == false))
            {
                return false;
            }
            
            foreach ($elements as $e)
            {
                if (strlen(trim($e)) === 0) continue;    # Skip empty elements
                
                $e = strtoupper($e);
                
                // Remove pieces that are unnecesarry or that the geocoding API
                //   does not normally understand.
                if (in_array($e, $removeAll))
                    continue;
                
                // Remove confusing prefixes (apartment or suite numbers typically)
                $badElement = false;
                foreach ($removeStart as $rs)
                {
                    if (substr($e, 0, strlen($rs)) === $rs)
                    {
                        $badElement = true;
                        break;
                    }
                }
                if ($badElement)    # If the element is bad, skip it.
                    continue;
                    
                // Normalize abbreviations
                foreach ($abbrev as $normal => $abs)
                {
                    foreach ($abs as $ab)
                    {
                        if ($e === $ab) # If the element is an abbreviation
                        {
                            $e = $normal;
                            break 2;
                        }
                    }
                }
                
                // Add the finished element to the return location
                $thisAddress[] = $e;
                
                // Check for the end of the address part
                if (in_array($e, $suffixes))
                {
                    $elementsNeeded = ($intersection == true ? 2 : 3);  # How many elements are needed to be a complete address
                    if (count($thisAddress) >= $elementsNeeded)
                        break;
                }
            }
            
            // Add the intersection delimeter if needed
            if (count($returnLocation) > 0) $returnLocation[] = "AND";
            
            // Add this address to the return location
            $returnLocation = array_merge($returnLocation, $thisAddress);
        }
        
        $location = implode(" ", $returnLocation);
        
        if (strlen(trim($location)) < 1)
            return false;
        
        return $location;
    }
    
    function checkBounds($coords, $bounds)
    {
        return !($coords[0] < $bounds[0] or $coords[1] < $bounds[1] or $coords[0] > $bounds[2] or $coords[1] > $bounds[3]);
    }
    
?>