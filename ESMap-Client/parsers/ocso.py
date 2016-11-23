
'''
    Web page parsing script for 'Orange County Sheriff's Office' (OCSO)
    http://www.ocso.com/Portals/0/CFS_FEED/activecalls.xml   
'''

def getValue(xml, eName):
    return xml.split("<" + eName + ">")[1].split("</" + eName + ">")[0].strip()

    
calls = data.split("<CALL ")
for idx in range(1, len(calls)):
    try:
        call = calls[idx].split("</CALL>")[0]
    
        # Parse page data
        row_data = { }
        
        meta = { }
        meta["call_number"]         = call.split("INCIDENT=\"")[1].split("\">")[0]
        meta["call_time"]           = getValue(call, "ENTRYTIME")   
        meta["location"]            = getValue(call, "LOCATION")
        meta["sector"]              = getValue(call, "SECTOR")
        meta["zone"]                = getValue(call, "ZONE")
        meta["district"]            = getValue(call, "RD")
        meta["description"]         = getValue(call, "DESC")
        
        # OCSO doesn't provide unique call numbers so we need to come up with our own semi-unique key
        cd = meta["call_time"].split()[0].split("-")
        row_data["key"] = '-'.join([ "OCSO", ''.join([ cd[2], cd[0], cd[1] ]), meta["call_number"] ])
        
        # Interpret call type from description
        call_type = "Police"
        desc = meta["description"].upper()
        
        if "FIRE" in desc:
            call_type = "Fire"
        elif "MEDICAL ONLY" in desc:
            call_type = "EMS"
        elif ("TRAFFIC" in desc) or (desc == "VEHICLE ACCIDENT") or (desc == "OBSTRUCT ON HWY"):
            call_type = "Traffic"
        elif ("PATROL" in desc) or (desc == "HOUSE/BUS./AREA/CHECK"):
            call_type = "Patrol"
            
        row_data["category"] = call_type
     
        # Add call to list
        row_data["meta"] = meta
        results.append(row_data)
    except Exception:
        pass
        