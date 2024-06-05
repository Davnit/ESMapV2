
import base64
from datetime import datetime
import hashlib
import json

from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.backends import default_backend


data = json.loads(data)

ct = base64.b64decode(data.get("ct"))
iv = bytes.fromhex(data.get("iv"))
salt = bytes.fromhex(data.get("s"))

# Build the password
t = ""
e = "CommonIncidents"
t += e[13] + e[1] + e[2] + "brady" + "5" + "r" + e.lower()[6] + e[5] + "gs"

# Calculate a key from the password
hasher = hashlib.md5()
key = b''
block = None
while len(key) < 32:
    if block:
        hasher.update(block)
    hasher.update(t.encode())
    hasher.update(salt)
    block = hasher.digest()

    hasher = hashlib.md5()
    key += block

# Create a cipher and decrypt the data
backend = default_backend()
cipher = Cipher(algorithms.AES(key), modes.CBC(iv), backend=backend)
decryptor = cipher.decryptor()
out = decryptor.update(ct) + decryptor.finalize()

# Clean up output data
out = out[1:out.rindex(b'"')].decode()              # Strip off extra bytes and wrapper quotes
out = out.replace(r'\"', r'"')                      # Un-escape quotes

data = json.loads(out)
active = data.get("incidents", {}).get("active", {})

# These were pulled from the app bundle on PP
call_types = {
    "AA": "Auto Aid",
    "MU": "Mutual Aid",
    "ST": "Strike Team/Task Force",
    "AC": "Aircraft Crash",
    "AE": "Aircraft Emergency",
    "AES": "Aircraft Emergency Standby",
    "LZ": "Landing Zone",
    "AED": "AED Alarm",
    "OA": "Alarm",
    "CMA": "Carbon Monoxide",
    "FA": "Fire Alarm",
    "MA": "Manual Alarm",
    "SD": "Smoke Detector",
    "TRBL": "Trouble Alarm",
    "WFA": "Waterflow Alarm",
    "FL": "Flooding",
    "LR": "Ladder Request",
    "LA": "Lift Assist",
    "PA": "Police Assist",
    "PS": "Public Service",
    "SH": "Sheared Hydrant",
    "EX": "Explosion",
    "PE": "Pipeline Emergency",
    "TE": "Transformer Explosion",
    "AF": "Appliance Fire",
    "CHIM": "Chimney Fire",
    "CF": "Commercial Fire",
    "WSF": "Confirmed Structure Fire",
    "WVEG": "Confirmed Vegetation Fire",
    "CB": "Controlled Burn/Prescribed Fire",
    "ELF": "Electrical Fire",
    "EF": "Extinguished Fire",
    "FIRE": "Fire",
    "FULL": "Full Assignment",
    "IF": "Illegal Fire",
    "MF": "Marine Fire",
    "OF": "Outside Fire",
    "PF": "Pole Fire",
    "GF": "Refuse/Garbage Fire",
    "RF": "Residential Fire",
    "SF": "Structure Fire",
    "VEG": "Vegetation Fire",
    "VF": "Vehicle Fire",
    "WCF": "Working Commercial Fire",
    "WRF": "Working Residential Fire",
    "BT": "Bomb Threat",
    "EE": "Electrical Emergency",
    "EM": "Emergency",
    "ER": "Emergency Response",
    "GAS": "Gas Leak",
    "HC": "Hazardous Condition",
    "HMR": "Hazmat Response",
    "TD": "Tree Down",
    "WE": "Water Emergency",
    "AI": "Arson Investigation",
    "HMI": "Hazmat Investigation",
    "INV": "Investigation",
    "OI": "Odor Investigation",
    "SI": "Smoke Investigation",
    "LO": "Lockout",
    "CL": "Commercial Lockout",
    "RL": "Residential Lockout",
    "VL": "Vehicle Lockout",
    "IFT": "Interfacility Transfer",
    "ME": "Medical Emergency",
    "MCI": "Multi Casualty",
    "EQ": "Earthquake",
    "FLW": "Flood Warning",
    "TOW": "Tornado Warning",
    "TSW": "Tsunami Warning",
    "CA": "Community Activity",
    "FW": "Fire Watch",
    "NO": "Notification",
    "STBY": "Standby",
    "TEST": "Test",
    "TRNG": "Training",
    "UNK": "Unknown",
    "AR": "Animal Rescue",
    "CR": "Cliff Rescue",
    "CSR": "Confined Space",
    "ELR": "Elevator Rescue",
    "RES": "Rescue",
    "RR": "Rope Rescue",
    "TR": "Technical Rescue",
    "TNR": "Trench Rescue",
    "USAR": "Urban Search and Rescue",
    "VS": "Vessel Sinking",
    "WR": "Water Rescue",
    "TCE": "Expanded Traffic Collision",
    "RTE": "Railroad/Train Emergency",
    "TC": "Traffic Collision",
    "TCS": "Traffic Collision Involving Structure",
    "TCT": "Traffic Collision Involving Train",
    "WA": "Wires Arcing",
    "WD": "Wires Down"
}

# Map their codes to our categories
categories = {
    "Fire": ["EX", "TE", "AF", "CHIM", "CF", "WSF", "WVEG", "CB", "ELF", "EF", "FIRE", "IF", "MF", "OF", "PF", "GF",
             "RF", "SF", "VEG", "VF", "WCF", "WRE"],
    "Traffic": ["AA", "TCE", "TC", "TCS", "TCT"],
    "Hazmat": ["HMR", "HMI"],
    "EMS": ["ME", "MCI"],
}

if active and len(active) > 0:
    for idx in range(len(active)):
        try:
            call = active[idx]

            ct = call["PulsePointIncidentCallType"]
            category = "Fire-General"
            for cat, types in categories.items():
                if ct in types:
                    category = cat
                    break

            call_time = datetime.strptime(call["CallReceivedDateTime"] + "+0000", "%Y-%m-%dT%H:%M:%SZ%z")

            has_full_address = call["AddressTruncated"] == "0"
            location = call["FullDisplayAddress"].split(', ', maxsplit=1)

            row = {
                "key": call["ID"],
                "category": category,
                "geo_lat": call["Latitude"],
                "geo_lng": call["Longitude"],
                "meta": {
                    "description": call_types.get(ct, ct + " (?)"),
                    "call_time": call_time.strftime("%Y-%m-%d %H:%M:%S") + " UTC",
                    "unit": ', '.join([u.get("UnitID", "N/A") for u in call.get("Unit", [])]),
                    "location": location[0],
                    "city": location[1]
                }
            }

            if has_full_address:
                row["location"] = location[0]
            else:
                # Might not be unique coordinates for this address, so include them in the location string.
                row["location"] = "%s_%s_%s" % (location[0], call["Latitude"], call["Longitude"])

            results.append(row)
        except Exception as ex:
            print("Error parsing 'PULSE' index %i: %s" % (idx,  ex))

