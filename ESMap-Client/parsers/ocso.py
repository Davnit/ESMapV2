
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
        row_data = dict()
        row_data["call_time"]   = getValue(call, "ENTRYTIME")   
        row_data["location"]    = getValue(call, "LOCATION")
        row_data["sector"]      = getValue(call, "SECTOR")
        row_data["zone"]        = getValue(call, "ZONE")
        row_data["district"]    = getValue(call, "RD")
        row_data["description"] = getValue(call, "DESC")
        
        # OCSO doesn't provide unique call numbers so combine the number they give with the date
        num = call.split("INCIDENT=\"")[1].split("\">")[0]
        cd = row_data["call_time"].split()[0].split("-")
        num = '-'.join([ cd[2], cd[0], cd[1], num ])
        row_data["call_number"] = num
        
        # Interpret call type from description
        call_type = "Police"
        desc = row_data["description"].upper()
        
        if "FIRE" in desc:
            call_type = "Fire"
        elif "MEDICAL ONLY" in desc:
            call_type = "EMS"
        elif ("TRAFFIC" in desc) or (desc == "VEHICLE ACCIDENT") or (desc == "OBSTRUCT ON HWY"):
            call_type = "Traffic"
        elif ("PATROL" in desc) or (desc == "HOUSE/BUS./AREA/CHECK"):
            call_type = "Patrol"
            
        row_data["call_type"] = call_type
     
        # Add call to list
        results.append(row_data)
    except Exception:
        pass
        
