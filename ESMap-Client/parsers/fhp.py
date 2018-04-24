
'''
    Web page parsing script for 'Florida Highway Patrol' (FHP)
    https://www.flhsmv.gov/fhp/traffic/live_traffic_feed.html
    
    At this time, this script will only return calls from Orange County.
    Updated to support the new ARCGIS system.
'''

dataObj = json.loads(data)

for incident in dataObj["features"]:
    try:
        attr = incident["attributes"]
    
        loc = attr["LOCATION"].rsplit("[", 1)
        desc = attr["TYPEEVENT"]
        
        # The city is designated in brackets at the end of the location.
        if len(loc) > 1:
            city = loc[1].split("]")[0].strip().title()
        else:
            city = ""
        
        meta = {
            "call_number": attr["INCIDENTID"],
            "call_date": attr["DATE"],
            "dispatched": attr["DISPATCHTIME"],
            "arrived": attr["ARRIVETIME"],
            "location": loc[0].replace("  ", " ").strip(),
            "county": attr["COUNTY"].title(),
            "city": city,
            "remarks": attr["REMARKS"],
            "description": desc
        }
        
        # Use the dispatch or arrival time for the call time, if specified.
        if len(meta["dispatched"]) > 0:
            meta["call_time"] = meta["call_date"] + " " + meta["dispatched"]
        elif len(meta["arrived"]) > 0:
            meta["call_time"] = meta["call_date"] + " " + meta["arrived"]
        
        location = meta["location"]
        if " x[" in location:
            parts = location.split(" x[")
            location = ""
            
            # Attempt to sort out the location string. FHP provides cross streets, mile markers, and other details
            #  some of which can be redundant. This makes a best effort to normalize the locations to match others.
            for i in range(0, len(parts) - 1):
                if " [" in parts[i]:
                    sub = parts[i].split(" [")
                    if sub[0] == sub[1].split("]")[0]:
                        location += sub[0]
                    else:
                        location += parts[i]
                else:
                    location += parts[i]
                location += " AND "
            
            # If the first component of the location is a number it's probably a direct address.
            if location.split()[0].isdigit():
                location = location.split("[")[0]
                
            # Replace generic "SR" (state road) prefix with the state "FL"
            location = location.replace("SR-", "FL-")
        
        category = "Traffic"
        if desc.upper().startswith("FIRE"):
            category = "Fire"
        elif "ALERT" in desc.upper():
            category = "Alert"
           
        row_data = {
            "key": attr["INCIDENTID"],
            "category": category,
            "location": meta["location"],
            "geo_lat": attr["LAT"],
            "geo_lng": attr["LON"],
            "meta": meta
        }
        
         
        
        results.append(row_data)
    except Exception as ex:
        print("Error parsing 'FHP': {0}".format(ex))
        

# Only add calls from Orange County
results[:] = [ item for item in results if (item["meta"]["county"].upper() == "ORANGE") ]
