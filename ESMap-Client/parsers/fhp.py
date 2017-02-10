
'''
    Web page parsing script for 'Florida Highway Patrol' (FHP)
    http://www.flhsmv.gov/fhp/traffic/crs_h407.htm
    
    At this time, this script will only return calls from Orange County.
'''

def getRowValue(table, name):
    return table.split(name)[1].split("<td")[1].split(">", 1)[1].split("</td>")[0].replace("&nbsp;", " ").strip()

replacements.update({
    "TURNPIKE": "FLORIDA TURNPIKE", "TPKE": "FLORIDA TURNPIKE",
    "GREENWAY": "FL-417", "GREENEWAY": "FL-417"
})

types = {
    "Police": [ "SILVER ALERT", "AMBER ALERT", "ROCK THROWING", "MISSING PERSON", "POSSIBLE FATALITY" ],
    "Death": [ "SUICIDE", "FATALITY" ]
}

infoList = data.split("<input type=\"hidden\" id=\"popInfo")    
for idx in range(1, len(infoList)):
    try:
        info = infoList[idx].split("value=\"")[1]

        row_data = { }
            
        meta = { }
        meta["call_number"]   = getRowValue(info, "Incident #")
        meta["dispatched"]    = getRowValue(info, "Dispatched")
        meta["arrived"]       = getRowValue(info, "Arrived")    
        meta["call_date"]     = getRowValue(info, "Date")
            
        # FHP doesn't provide unique call numbers so we need to come up with our own
        cd = meta["call_date"].split("/")
        row_data["key"] = '-'.join([ "FHP", ''.join([ cd[2], cd[0], cd[1] ]), meta["call_number"][9:] ])
    
        location = getRowValue(info, "Location").rsplit("[", 1)
        meta["location"]      = location[0].replace("  ", " ").strip()
        
        if len(location) > 1:
            meta["city"]      = location[1].split("]")[0].strip().title()
   
        meta["county"]        = getRowValue(info, "County").title()
        meta["remarks"]       = getRowValue(info, "Remarks")
        meta["description"]   = getRowValue(info, "Incident Type")
        
        call_type = "Traffic"
        desc = meta["description"].upper()
        if desc.startswith("FIRE"):
            call_type = "Fire"
        else:
            for sType, termList in types.items():
                for term in termList:
                    if term in desc:
                        call_type = sType
            
        # Make a rough parse of the location string
        #   This source is pretty good about providing its own geocodes but this is just in case.
        loc = meta["location"]
        
        # Check for cross streets (usually always provided, even when it's a direct address)
        if (" x[" in loc):
            parts = loc.split(" x[")
            loc = parts[0] + " AND " + parts[1][:-1]
        else:
            loc = loc
        
        # If the first "word" is a number this is most likely a direct address
        if loc.split()[0].isdigit():
            loc = loc.split("[")[0]
            
        # Replace SR- (state road) prefix with the state FL
        loc = loc.replace("SR-", "FL-")
            
        row_data["location"]  = loc
        row_data["category"]  = call_type
        row_data["meta"]      = meta
        
        results.append(row_data)
    except Exception as ex:
        print("Error parsing 'FHP' index {0}: {1}".format(idx, ex))

# Parse source coordinate data
latcol = data.split("var Lat = ")[1].split("[")[1].split("];")[0].replace("\"", "").split(",")    
lngcol = data.split("var Lng = ")[1].split("[")[1].split("];")[0].replace("\"", "").split(",")
if ((len(latcol) == len(results)) and (len(lngcol) == len(results))):
    for idx in range(0, len(results)):
        if (not latcol[idx].startswith("0")) and (not lngcol[idx].startswith("0")):
            results[idx]["geo_lat"] = latcol[idx]
            results[idx]["geo_lng"] = lngcol[idx]

# Only add calls from Orange County
results[:] = [ item for item in results if (item["meta"]["county"].upper() == "ORANGE") ]
