import ClientConfig, Sources

# Startup
print("Loading config...")
c = ClientConfig.load()

# Run
print("Reading sources...")
sources = Sources.getLocalSources(c.SourceLocation)

for src in sources.values():
    print("Source #{0}: {1}".format(src.id, src.tag))


# Shutdown
c.TestValue = c.TestValue + '+'
print("Saving config...")
ClientConfig.save(c)
