
import time, sys, json
import ClientConfig, Sources, Calls, WebClient

# Startup
print("Loading config...")
c = ClientConfig.load()

print("Reading sources...")
sources = Sources.getLocalSources(c.SourceLocation)

active_calls = { }

for src in sources.values():
    print("Source #{0}: {1} => {2} [Can check?: {3}]".format(src.id, src.tag, src.parser, Sources.canCheck(src)))
    active_calls[src] = [ ]

# Run
while True:
    # Iterate the data sources and check the ones that need updating
    for src in sources.values():
        if Sources.needsUpdate(src) and Sources.canCheck(src):
            calls = Sources.check(src)

            # If data was returned, merge the new data with the existing
            if calls:
                result, added, removed = Calls.merge(active_calls[src], calls)
                active_calls[src] = result.values()

                # Show the changes
                if len(added) > 0 or len(removed) > 0:
                    print("UPDATE FROM {0}: {1} active calls ({2} new, {3} expired)".format(src.tag, len(result), len(added), len(removed)))
                    for call in added.values():
                        print("\tNEW:", call.getLongDisplayString())
                    for call in removed.values():
                        print("\tEXP:", call.getShortDisplayString())

                    # Test report
                    if len(c.IngestUrl) > 0:
                        report = { 
                            "source": src.id, 
                            "new": [ v.getReportData() for v in added.values() ], 
                            "expired": [ v.getKey() for v in removed.values() ]
                        }

                        ok, response = WebClient.postData(c.IngestUrl, { "calldata": json.dumps(report, separators=(',',':')) })
                        print("Post data: " + str(ok))
                        print("Response: " + str(response))

    time.sleep(c.TickInterval)



