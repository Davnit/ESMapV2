
import time, sys, json
import ClientConfig, Sources, Calls, WebClient

# Startup
print("Loading config...")
c = ClientConfig.load()

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
    # Iterate the data sources and check the ones that need updating
    for src in sources.values():
        if Sources.needsUpdate(src) and Sources.canCheck(src):
            calls = Sources.check(src)

            # If data was returned, merge the new data with the existing
            if calls:
                result, added, removed = Calls.merge(active_calls[src.id], calls)
                active_calls[src.id] = result.values()

                # Show the changes
                if len(added) > 0 or len(removed) > 0:
                    print("UPDATE FROM {0}: {1} active calls ({2} new, {3} expired)".format(src.tag, len(result), len(added), len(removed)))
                    for call in added.values():
                        print("\tNEW:", call.getLongDisplayString())
                    for call in removed.values():
                        print("\tEXP:", call.getShortDisplayString())

                    # Test report
                    if c.UseRemoteServer and len(c.IngestUrl) > 0:
                        report = { 
                            "source": src.id, 
                            "new": [ v.getReportData() for v in added.values() ], 
                            "expired": [ v.getKey() for v in removed.values() ]
                        }

                        ok, response = WebClient.postData(c.IngestUrl, { "calldata": json.dumps(report, separators=(',',':')) })
                        if ok:
                            data = json.loads(response)
                            if data["status"]["success"] == True:
                                success = True
                                if data["status"]["added"] != len(added): success = False
                                if data["status"]["expired"] != len(removed): success = False

                                if not success:
                                    print("Reporting discrepancy. Server added {0} rows, expired {1}.".format(data["status"]["added"], data["status"]["expired"]))
                            else:
                                print("Report failed. Reason: " + data["status"]["message"])

    time.sleep(c.TickInterval)



