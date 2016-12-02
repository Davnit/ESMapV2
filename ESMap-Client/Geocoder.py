
import json, time

import WebClient

def getRequests(sourceUrl):
    requests = [ ]

    ok, response = WebClient.openUrl(sourceUrl)
    if not ok:
        return requests     # Return no requests
    
    # Parse the response data into a list of requests
    data = json.loads(response)
    for id, location in data["geocodes"].items():
        requests.append(GeocodeRequest(id, location))
    return requests

# Returns true if the specified config should handle geocode requests
def canHandleRequests(config):
    return config.EnableGeocodes and len(config.GeocodeRequestUrl) > 0 and len(config.GeoApiUrl) > 0


class GeocodeRequest():
    def __init__(self, id, location):
        self.id = id                # The ID of this geocode entry as specified by the server
        self.location = location    # The location to be geocoded
        self.resolved = False       # True if the request has been resolved
        self.results = None         # JSON data returned by the geocode API

    # Attempts to resolve the request
    def tryResolve(self, apiUrl, clientKey = None):

        # Build request parameters
        params = { "address": self.location }
        if clientKey is not None:
            params["key"] = clientKey

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

        
            

        
