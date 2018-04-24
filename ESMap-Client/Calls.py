
# Combines two lists of calls and returns the resulting list
def merge(old, new):
    # Hash the calls in each collection
    oldCalls = { c.getKey() : c for c in old }
    newCalls = { c.getKey() : c for c in new }

    final = { }
    changes = { }

    # Find newly added calls (in new but not old)
    added = { }
    for k, c in newCalls.items():
        if not k in oldCalls:
            added[k] = c

    # Determine which calls have been removed/expired (in old but not new)
    removed = { }
    for k, c in oldCalls.items():
        if (not k in newCalls):
            removed[k] = c
        else:
            final[k] = newCalls[k]

            # Check for changes in call data
            cm = compare(c, final[k])

            # If the location has changed but the meta location hasn't, then it doesn't count.
            if "location" in cm and ((not "meta" in cm) or (not "location" in cm["meta"])):
                del cm["location"]

            # If coordinates have changed but the meta location hasn't, it also doesn't count.
            if "geo_lat" in cm and "geo_lng" in cm and ((not "meta" in cm) or (not "location" in cm["meta"])):
                del cm["geo_lat"]
                del cm["geo_lng"]

            if cm and len(cm) > 0:
                changes[k] = cm

    # Add new calls to the final collection
    final.update(added)

    return final, added, removed, changes

# Compares two CallData objects and returns the difference (a = old, b = new)
def compare(a, b):
    diff = { }
    
    repA = a.getReportData()
    repB = b.getReportData()

    # Compare all values in top 2 levels
    for key, valB in repB.items():
        if isinstance(valB, dict) and key in repA:
            # Check sub-dictionaries (metadata)
            sub = { }
            for subkey, subvalB in valB.items():
                if not compareValues(subkey, repA[key], valB):
                    sub[subkey] = subvalB
                    
            # Only report the sub if it has items
            if len(sub) > 0:
                diff[key] = sub
        elif not compareValues(key, repA, repB):
            diff[key] = valB

    return diff

# Returns true if the values match
def compareValues(key, dictA, dictB):
    return (key in dictA) and (key in dictB) and (dictA[key] == dictB[key])
    

class CallData():
    def __init__(self, data):
        if isinstance(data, str):
            self.meta = None    
            self.key = data     # A semi-unique key used to identify the call
        else:
            self.meta = data    # An array of information describing the call
            self.key = None

        self.category = None    # The type of call (Police, Fire, EMS, etc)
        self.source = None      # The CallSource object representing the source of the call
        self.location = None    # The parsed location used for geocoding
        self.coords = None      # The resolved coordinates of the geocoded location for this call

    # Returns a short string identifying the call
    #   Example: Burglary @ 123 Main St
    def getShortDisplayString(self):
        if self.meta is None:
            return "No data" if self.key is None else self.key

        s = self.meta["description"] if "description" in self.meta else "Unidentified"
        
        # Append the location if available
        location = self.meta["location"] if "location" in self.meta else ""
        if len(location) > 0:
            s += " @ " + location

        return s

    def getLongDisplayString(self):
        s = "{0}: {1} [{2}]"
        return s.format(self.getKey(), self.getShortDisplayString(), self.category)
    
    def getKey(self):
        if self.key is None:
            return hash(str(self.meta))
        else:
            return self.key

    # Returns a dictionary with values representing known call data
    def getReportData(self):
        data = { "key": self.key, "category": self.category, "location": self.location, "meta": self.meta }
        if not (self.coords is None):
            data["geo_lat"] = self.coords[0]
            data["geo_lng"] = self.coords[1]
        return data




        
        
