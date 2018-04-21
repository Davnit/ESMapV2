
import time, sys, json
from datetime import datetime
import ClientConfig, Sources, Calls, WebClient, Reporting, Geocoder

# syncs current data with remote server
#  returns: sources, active_calls
def resync():
    if not c.UseRemoteServer or len(c.DataUrl) == 0:
        raise Exception("Cannot resync when using local data.")

    print("Syncing with server: {0}".format(c.DataUrl.split("://")[1].split("/")[0]))

    sources = Sources.getRemoteSources(c.DataUrl)
    active = { }

    # Get current call list from server
    ok, response = WebClient.openUrl(c.DataUrl, { "request": 2 })
    if ok:
        data = json.loads(response)
        status = data["status"]

        if status["success"]:
            if isinstance(data["data"], dict):
                # Format: data => source: { cid => [ category, location, meta ] }
                for srcID, calls in data["data"].items():
                    isrc = int(srcID)
                    if not isrc in sources: continue
                    
                    src = sources[isrc]
                    active[src.id] = [ ]

                    for cID, call in calls.items():
                        # Create a call object to represent this item
                        callObj = Calls.CallData(json.loads(call[2]))
                        callObj.category = call[0]
                        callObj.location = call[1]
                        callObj.key = cID
                        callObj.source = src
            
                        active[src.id].append(callObj)
                        print("\t{0}: {1}".format(src.tag, callObj.getShortDisplayString()))
            else:
                print("\t... No calls active.")

        else:
            print("\t...", status["message"])
    else:
        print("\t... failed: " + str(response))

    return sources, active

# Startup
print("Loading config...")
c = ClientConfig.load()
ClientConfig.save(c)        # This writes default values of new settings

# Get initial data
if c.UseRemoteServer:
    sources, active_calls = resync()
    last_sync = datetime.now()
else:
    sources = Sources.getLocalSources(c.SourceLocation)
    active_calls = { }

# Can't do anything without sources
if len(sources) == 0:
    print("No sources found.")
    sys.exit()

# List source info and initialize call lists
for src in sources.values():
    print("Source #{0}: {1} => {2} [Can check?: {3}]".format(src.id, src.tag, src.parser, Sources.canCheck(src)))
    if not src.id in active_calls:
        active_calls[src.id] = [ ]

# Run
while True:
    # should we resync?
    if c.UseRemoteServer and c.SyncTime != 0:
        if (last_sync == None or (datetime.now() - last_sync).seconds >= c.SyncTime):
            sources, active_calls = resync()
            last_sync = datetime.now()

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
                        report.sendReport(c.IngestUrl, c.ClientKey)


    # Handle geocode requests
    if Geocoder.canHandleRequests(c):
        requests = Geocoder.getRequests(c.DataUrl)

        if len(requests) > 0:
            # Resolve all of the requests
            for request in requests:
                if not request.resolved and not Geocoder.quota_exceeded:
                    if request.tryResolve(c.GeoApiUrl, c.GeoApiKey):
                        print("Geocode resolved: {0} -> {1}".format(request.location, request.getFormattedAddress()))
                    else:
                        print("Geocode failed: {0} -> {1}".format(request.location, request.getError()))

            # Report the results
            report = Reporting.GeocodeReport(requests)
            if len(report.getData().items()) > 0:
                if report.sendReport(c.IngestUrl, c.ClientKey):
                    Geocoder.closeRequests(report.getData().keys())
                else:
                    # Save a copy of the data for analysis if needed
                    # This is done mostly because the geocoding data sometimes triggers mod_security rules
                    try:
                        with open('geodata.json', 'w') as f:
                            f.write(json.dumps(report.getData(), separators=(',',':')))
                            f.close()
                    except Exception as ex:
                        print("Error writing geodata: ", ex)

    time.sleep(c.TickInterval)



