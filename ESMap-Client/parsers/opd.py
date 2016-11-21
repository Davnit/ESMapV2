
'''
    Web page parsing script for 'Orlando Police Department' (OPD)
    http://www1.cityoforlando.net/opd/activecalls/activecad.xml
    http://www1.cityoforlando.net/opd/activecalls/selfcad.xml    
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
        meta["call_number"]     = call.split("incident=\"")[1].split("\">")[0]
        meta["call_time"]       = getValue(call, "DATE")
        meta["location"]        = getValue(call, "LOCATION")
        meta["district"]        = getValue(call, "DISTRICT")
        meta["description"]     = getValue(call, "DESC")
        
        # Interpret call type from description
        call_type = "Police"
        desc = meta["description"].upper()
        
        if "FIRE" in desc:
            call_type = "Fire"
        elif ("ACCIDENT" in desc) or ("HIT AND RUN" in desc):
            call_type = "Traffic"
        elif desc == "HOUSE/BUSINESS CHECK":
            call_type = "Patrol"
            
        row_data["category"] = call_type
        row_data["key"] = "OPD-" + meta["call_number"]
            
        # Add call to list
        row_data["meta"] = meta
        results.append(row_data)
    except Exception:
        pass
