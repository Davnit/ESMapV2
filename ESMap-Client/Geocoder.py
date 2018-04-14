
import json, time
from datetime import datetime

import WebClient

open_requests = { }
quota_exceeded = False
last_api_call = None

def getRequests(sourceUrl):
    ok, response = WebClient.openUrl(sourceUrl, { "request": 3 })
    if not ok:
        print("ERROR Unable to obtain geocode requests: " + str(response))
    else:
        # Decode the returned JSON and check the response status
        data = json.loads(response)
    
        status = data["status"]
        if not status["success"]:
            print("ERROR Obtaining geocode requests: " + status["message"])
        else:
            # Check if values were returned
            if isinstance(data["data"], dict):
                # Parse the returned values
                for id, location in data["data"].items():
                    if id in open_requests: continue        # Only add new requests

                    req = GeocodeRequest(id, location)
                    req.filter = { "country": [ "US" ], "administrative_area": [ "Florida", "Orange County" ] }
                    open_requests[id] = req

    return open_requests.values()

# Returns true if the specified config should handle geocode requests
def canHandleRequests(config):
    global quota_exceeded
    if not config.EnableGeocodes or len(config.DataUrl) == 0 or len(config.GeoApiUrl) == 0:
        return False
    else:
        if quota_exceeded:
            quota_exceeded = (last_api_call is not None) and ((datetime.now() - last_api_call).seconds < (3600))
            return not quota_exceeded
        return True

# Removes requests with the specified ids
def closeRequests(requestIds):
    for id in requestIds:
        if id in open_requests:
            del open_requests[id]

def unicodeReplace(str):
	str = str.replace('\u2013', '-')
	return str


class GeocodeRequest():
    def __init__(self, id, location):
        self.id = id                # The ID of this geocode entry as specified by the server
        self.location = location    # The location to be geocoded
        self.resolved = False       # True if the request has been resolved
        self.results = None         # JSON data returned by the geocode API
        self.filter = None          # Component filtering information

    # Attempts to resolve the request
    def tryResolve(self, apiUrl, clientKey = None):
        global quota_exceeded, last_api_call

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

        last_api_call = datetime.now()

        # Parse results
        self.results = json.loads(response)
        if self.results["status"] in [ "OK", "ZERO_RESULTS" ]:
            self.resolved = True
        elif self.results["status"] == "OVER_QUERY_LIMIT":
            quota_exceeded = True
        return self.resolved

    # Gets a properly formatted address as returned by the geocode API
    def getFormattedAddress(self):
        if not self.resolved or self.results is None: return None

        resultList = self.results["results"]
        if len(resultList) > 0:
            res = resultList[0]
            if "formatted_address" in res:
                return unicodeReplace(res["formatted_address"])
        return None

    # Returns an error message associated with this request.
    def getError(self):
        if self.results is not None:
            if "error_message" in self.results:
                return self.results["status"] + ": " + self.results["error_message"]
            else:
                return self.results["status"]
        return None
        
            

        
