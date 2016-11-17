
import WebClient, Calls
from os import path
from datetime import datetime

defaultLocalSourcePath = 'sources.txt'
missing_parsers = [ ]

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

        if len(s) < 3:
            print("ERROR! Source #{0} is missing metadata. Requires: TAG URL PARSER - only {1} provided.".format(i, len(s)))
        
        source = CallSource(s[0], s[1], s[2])
        source.id = i

        # Parse optional metadata
        if len(s) > 3:
            source.interval = int(s[3])

        sources[i] = source
        i += 1

    return sources

# Returns True if the specified source is due for an update.
def needsUpdate(source):
    if source.last_update == None:
        return True

    return (datetime.now() - source.last_update).seconds >= source.interval

# Returns False if the source is missing its parser
def canCheck(source):
    return (not source.parser == None) and (not source.parser in missing_parsers)


# Returns a list of calls being reported by a source.
def check(source):
    parser = path.normpath(path.join('parsers', source.parser))

    # Check that the parser exists
    if not path.isfile(parser):
        if not parser in missing_parsers:
            missing_parsers.append(parser)
            print("WARNING! Missing source parser: {0}".format(source.parser))
        return False

    # Retrieve the page contents
    ok, data = WebClient.openUrl(source.url)
    if not ok:
        print("ERROR! {0}".format(data))
        return False
    
    results = [ ]       # Provides a place for the parser script to store data

    # Compile and run the parser script
    try:
        exec(compile(open(parser, 'rb').read(), parser, 'exec'))
    except Exception as ex:
        print("ERROR! Problem occurred while parsing source {0}: {1}".format(source.tag, ex))
        return False

    # Update the last_update time
    source.last_update = datetime.now()

    # Create call objects from the parser results and tag them
    calls = [ ]
    for r in results:
        c = Calls.CallData(r)
        c.source = source

        calls.append(c)

    return calls
    


# Stores metadata describing a source of emergency calls
class CallSource():
    def __init__(self, tag, url, parser):
        self.id = None
        self.tag = tag          # Short identifer for the source (usually agency abbreviation)
        self.url = url          # Location of source data
        self.parser = None      # The path to the script responsible for parsing the source file
        self.interval = 300     # Time to wait between update checks, in seconds

        self.last_update = None # Last time this source was checked

    