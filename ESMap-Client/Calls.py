
def merge(old, new):
    # Hash the calls in each collection
    oldCalls = { c.getHash() : c for c in old }
    newCalls = { c.getHash() : c for c in new }

    final = { }

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
            final[k] = c

    # Add new calls to the final collection
    final.update(added)

    return final, added, removed
    

class CallData():
    def __init__(self, meta):
        self.meta = meta        # An array of information describing the call
        self.source = None      # The CallSource object representing the source of the call

    # Returns a short string identifying the call
    #   Example: Burglary @ 123 Main St
    def getShortDisplayString(self):
        s = self.meta['description']
        
        # Append the location if available
        location = self.meta['location']
        if len(location) > 0:
            s += " @ " + location

        return s

    def getLongDisplayString(self):
        s = "{0}: {1} [{2}] [#{3}]"
        return s.format(self.source.tag, self.getShortDisplayString(), self.meta["call_type"], self.meta["call_number"])

    def getHash(self):
        return hash(str(self.meta))


        
        
