
import time, sys
import ClientConfig, Sources

# Startup
print("Loading config...")
c = ClientConfig.load()

# Run
print("Reading sources...")
sources = Sources.getLocalSources(c.SourceLocation)

for src in sources.values():
    print("Source #{0}: {1} => {2} [Can check?: {3}]".format(src.id, src.tag, src.parser, Sources.canCheck(src)))

while True:
    for src in sources.values():
        if Sources.needsUpdate(src) and Sources.canCheck(src):
            calls = Sources.check(src)
            if calls:
                print("UPDATE FROM {0}: {1} active calls".format(src.tag, len(calls)))
                for call in calls:
                    print("\t" + call.getShortDisplayString())

    time.sleep(c.TickInterval)



