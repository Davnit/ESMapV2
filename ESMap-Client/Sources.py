
defaultLocalSourcePath = 'sources.txt'

# Retreives a list of call sources from a local file
def getLocalSources(path=None):
    if path is None:
        path = defaultLocalSourcePath

    # Read the file
    data = open(path, 'r').readlines()

    i = 1
    sources = { }

    # Process each line into a source
    for itm in data:
        s = itm.split()
        
        source = CallSource(s[0], s[1])
        source.id = i

        sources[i] = source
        i += 1

    return sources

# Stores metadata describing a source of emergency calls
class CallSource():
    def __init__(self, tag, url):
        self.id = None      # -1 = no ID assigned
        self.tag = tag      # Short identifer for the source (usually agency abbreviation)
        self.url = url      # Location of source data

    