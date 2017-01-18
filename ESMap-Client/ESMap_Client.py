
import time, sys, json
import ClientConfig, Sources, Calls, WebClient, Reporting, Geocoder

# Startup
print("Loading config...")
c = ClientConfig.load()
ClientConfig.save(c)        # This writes default values of new settings

print("Obtaining sources...")
sources = None
if c.UseRemoteServer:
    sources = Sources.getRemoteSources(c.DataUrl)
else:
    sources = Sources.getLocalSources(c.SourceLocation)

if len(sources) == 0:
    print("No sources found.")
    sys.exit()

active_calls = { }

for src in sources.values():
    print("Source #{0}: {1} => {2} [Can check?: {3}]".format(src.id, src.tag, src.parser, Sources.canCheck(src)))
    active_calls[src.id] = [ ]

# Sync
if c.UseRemoteServer and len(c.DataUrl) > 0:
    print("Syncing with server: {0}".format(c.DataUrl.split("://")[1].split("/")[0]))

    ok, response = WebClient.openUrl(c.DataUrl, { "request": 2 })
    if ok:
        data = json.loads(response)
        status = data["status"]

        if status["success"] and isinstance(data["data"], dict):
            for srcID, calls in data["data"].items():
                src = sources[srcID]

                for cID, call in calls.items():
                    # Create a call object to represent this item
                    callObj = Calls.CallData(json.loads(call["meta"]))
                    callObj.category = call["category"]
                    callObj.location = call["location"]
                    callObj.key = cID
                    callObj.source = src
            
                    active_calls[src.id].append(callObj)
                    print("\t{0}: {1}".format(src.tag, callObj.getShortDisplayString()))
        else:
            print("\t...", status["message"])
    else:
        print("\t... failed: " + str(response))

# Run
while True:
    shouldGeocode = False       # Determines if we should get geocode requests this cycle

    # Iterate the data sources and check the ones that need updating
    for src in sources.values():
        if Sources.needsUpdate(src) and Sources.canCheck(src):
            calls = Sources.check(src)

            # If data was returned, merge the new data with the existing
            if calls:
                result, added, removed, updates = Calls.merge(active_calls[src.id], calls)
                active_calls[src.id] = result.values()

                report = Reporting.SourceUpdateReport(src, result, added, removed, updates)

                # Show the changes
                if report.hasChanges():
                    report.printChanges()

                    # Update the server with new data
                    if c.UseRemoteServer and len(c.IngestUrl) > 0:
                        report_ok = report.sendChangeReport(c.IngestUrl)

                        # Check if we need to get geocode requests
                        if report_ok and len(report.added) > 0:
                            shouldGeocode = True                        

    # Handle geocode requests
    if Geocoder.canHandleRequests(c) and shouldGeocode:
        requests = Geocoder.getRequests(c.DataUrl)

        if len(requests) > 0:
            # Resolve all of the requests
            for request in requests:
                if request.tryResolve(c.GeoApiUrl, c.GeoApiKey):
                    print("Geocode resolved: {0} -> {1}".format(request.location, request.getFormattedAddress()))

            # Report the results
            report = Reporting.GeocodeReport(requests)
            report.sendReport(c.IngestUrl)

    time.sleep(c.TickInterval)



