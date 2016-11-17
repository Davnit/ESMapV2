
import time, sys
import ClientConfig, Sources, Calls

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
                        print("\tNEW:", call.getShortDisplayString())
                    for call in removed.values():
                        print("\tEXP:", call.getShortDisplayString())

    time.sleep(c.TickInterval)



