
import json, time

import WebClient

def getRequests(sourceUrl):
    requests = [ ]

    ok, response = WebClient.openUrl(sourceUrl, { "request": 3 })
    if not ok:
        print("ERROR Unable to obtain geocode requests: " + str(response))
        return requests     # Return no requests
    
    # Decode the returned JSON and check the response status
    data = json.loads(response)
    
    status = data["status"]
    if not status["success"]:
        print("ERROR Obtaining geocode requests: " + status["message"])
        return requests

    # Check if values were returned
    if not isinstance(data["data"], dict):
        return requests

    # Parse the returned values
    for id, location in data["data"].items():
        req = GeocodeRequest(id, location)
        req.filter = { "country": [ "US" ], "administrative_area": [ "Florida", "Orange County" ] }
        requests.append(req)
    return requests

# Returns true if the specified config should handle geocode requests
def canHandleRequests(config):
    return config.EnableGeocodes and len(config.DataUrl) > 0 and len(config.GeoApiUrl) > 0


class GeocodeRequest():
    def __init__(self, id, location):
        self.id = id                # The ID of this geocode entry as specified by the server
        self.location = location    # The location to be geocoded
        self.resolved = False       # True if the request has been resolved
        self.results = None         # JSON data returned by the geocode API
        self.filter = None          # Component filtering information

    # Attempts to resolve the request
    def tryResolve(self, apiUrl, clientKey = None):

        # Build request parameters
        params = { "address": self.location }
        if clientKey is not None:
            params["key"] = clientKey

        # Convert filter to piped list
        #  key1:v1|key1:v2|key1:v3|key2:v1|key2:v2|etc
        comp = ""
        for k, v in self.filter.items():
            for item in v:
                comp += k + ":" + item + "|"
        if len(comp) > 0:
            params["components"] = comp[:-1]

        # Make the request
        ok, response = WebClient.openUrl(apiUrl, params)
        if not ok: return False

        # Parse results
        self.results = json.loads(response)
        if self.results["status"] == "OK":
            self.resolved = True
        return self.resolved

    # Gets a properly formatted address as returned by the geocode API
    def getFormattedAddress(self):
        if not self.resolved or self.results is None: return None

        resultList = self.results["results"]
        if len(resultList) > 0:
            res = resultList[0]
            if "formatted_address" in res:
                return res["formatted_address"]
        return None

        
            

        
