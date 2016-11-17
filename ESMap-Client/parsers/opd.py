
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
        row_data = dict()
        row_data["call_number"] = call.split("incident=\"")[1].split("\">")[0]
        row_data["call_time"]   = getValue(call, "DATE")
        row_data["location"]    = getValue(call, "LOCATION")
        row_data["district"]    = getValue(call, "DISTRICT")
        row_data["description"] = getValue(call, "DESC")
        
        # Interpret call type from description
        call_type = "Police"
        desc = row_data["description"].upper()
        
        if "FIRE" in desc:
            call_type = "Fire"
        elif ("ACCIDENT" in desc) or ("HIT AND RUN" in desc):
            call_type = "Traffic"
        elif desc == "HOUSE/BUSINESS CHECK":
            call_type = "Patrol"
            
        row_data["call_type"] = call_type
            
        # Add call to list
        results.append(row_data)
    except Exception:
        pass
