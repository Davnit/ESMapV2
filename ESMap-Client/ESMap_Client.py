
import time, sys, json
import ClientConfig, Sources, Calls, WebClient, Reporting, Geocoder

# Startup
print("Loading config...")
c = ClientConfig.load()
ClientConfig.save(c)        # This writes default values of new settings

print("Obtaining sources...")
sources = None
if c.UseRemoteServer:
    sources = Sources.getRemoteSources(c.SourceLocation)
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
if c.UseRemoteServer and len(c.SyncUrl) > 0:
    print("Syncing with server: {0}".format(c.SyncUrl.split("://")[1].split("/")[0]))

    ok, response = WebClient.openUrl(c.SyncUrl)
    if ok:
        if response.startswith("FAIL"):
            print("\t... " + response)
        else:
            calls = response.split("\r\n")
            for call in list(filter(None, calls)):
                s = call.split("|")

                src = sources[int(s[0])]
                active_calls[src.id].append(Calls.CallData(s[1]))     # Add a dummy CallData instance, with only the key.
                print("\t{0} [{1}]: {2}".format(src.tag, src.id, s[1]))
    else:
        print("\t... failed: " + response)

# Run
while True:
    shouldGeocode = False       # Determines if we should get geocode requests this cycle

    # Iterate the data sources and check the ones that need updating
    for src in sources.values():
        if Sources.needsUpdate(src) and Sources.canCheck(src):
            calls = Sources.check(src)

            # If data was returned, merge the new data with the existing
            if calls:
                result, added, removed = Calls.merge(active_calls[src.id], calls)
                active_calls[src.id] = result.values()

                report = Reporting.SourceUpdateReport(src, result, added, removed)

                # Show the changes
                if report.hasChanges():
                    report.printChanges()

                    # Update the server with new data
                    if c.UseRemoteServer and len(c.IngestUrl) > 0:
                        report_ok = report.sendChangeReport(c.IngestUrl)

                        # Check if we need to get geocode requests
                        if Geocoder.canHandleRequests(c) and report_ok and len(report.added) > 0:
                            shouldGeocode = True                        

    # Handle geocode requests
    if Geocoder.canHandleRequests(c) and shouldGeocode:
        requests = Geocoder.getRequests(c.GeocodeRequestUrl)

        # Resolve all of the requests
        for request in requests:
            if request.tryResolve(c.GeoApiUrl, c.GeoApiKey):
                print("Geocode resolved: {0} -> {1}".format(request.location, request.getFormattedAddress()))

        # Report the results
        report = Reporting.GeocodeReport(requests)
        report.sendReport(c.IngestUrl)

    time.sleep(c.TickInterval)



