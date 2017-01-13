
'''
    Web page parsing script for 'Orange County Fire Rescue' (OCFR)
    http://www.orangecountyfl.net/EmergencySafety/FireRescueActiveCalls.aspx
'''
    
def tdsplit(tr, s):
    return tr.split(s)[1].split(">", 1)[1].split("</")[0].strip()
    
# This source has been flaky with their geocodes. They only use a bounds suggestion so
#   occasionally things are resolved outside of the desired bounds.
def verifyGeo(lat, lng):
    bounds = { "southwest": { "lat": 28.346725, "lng": -81.65859829999999 },
               "northeast": { "lat": 28.7860889, "lng": -80.870879 }}
    
    # Check for default coordinates (these are specified in the source's own code)
    if (lat == "28.539555") and (lng == "-81.374692"):
        return False
        
    lat = float(lat)
    lng = float(lng)
    
    # Check bounds
    if (lat < bounds["southwest"]["lat"]) or (lat > bounds["northeast"]["lat"]):
        return False
    if (lng < bounds["southwest"]["lng"]) or (lng > bounds["northeast"]["lng"]):
        return False
        
    return True
    

tableContainerId = "lstvwCalls4Svc_itemPlaceholderContainer"

# All call data is contained in an HTML table.
if tableContainerId in data:
    table = data.split(tableContainerId)[1].split("</table>")[0]
    
    # Skip split[0] (pre-first row) and [1] (header row)
    rows = table.split("<tr")
    for idx in range(2, len(rows)):
        try:
            r = rows[idx].split("</tr>")[0].strip()
        
            row_data = { }
        
            meta = { }
            meta["call_number"] = tdsplit(r, "CALL_NO")
            meta["call_time"]   = tdsplit(r, "DISPATCH_TIME")
            meta["description"] = tdsplit(r, "CALL_DESCRIPTION")
            meta["call_type"]   = tdsplit(r, "CALL_TYPE")
            meta["unit"]        = tdsplit(r, "UNITLabel")
            
            locStr = tdsplit(r, "STREET_NAME")
            if not ("/" in locStr.replace(" N/A", "")):
                locStr = tdsplit(r, "STREET_NO") + " " + locStr
            meta["location"] = locStr
            
            ct = meta["call_type"].upper()
            if ct in [ "FIRE", "TRAFFIC" ]:
                ct = ct.title()
            elif ct == "EMS":
                ct = ct
            else:
                ct = "General"

            locStr = locStr.replace(" N/A", "").replace("STATE ROAD ", "FL-").replace("/", " AND ")
                
            row_data["key"] = "OCFR-" + meta["call_number"]
            row_data["location"] = locStr
            row_data["category"] = ct
            row_data["meta"] = meta
    
            geo = r.split("MAPBUTTON")[1].split("latlng=")[1].split("\">")[0].split(",")
            if verifyGeo(geo[0], geo[1]):
                row_data["geo_lat"] = geo[0]
                row_data["geo_lng"] = geo[1]
        
            results.append(row_data)
        except Exception as ex:
            pass
    

    