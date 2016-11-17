
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


        
        
