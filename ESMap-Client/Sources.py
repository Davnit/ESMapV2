
import WebClient, Calls
from os import path
from datetime import datetime

defaultLocalSourcePath = 'sources.txt'
missing_parsers = [ ]

# Retreives a list of call sources from a local file
def getLocalSources(sourcePath=None):
    if sourcePath is None:
        sourcePath = defaultLocalSourcePath

    # Read the file
    data = open(sourcePath, 'r').readlines()

    i = 1
    sources = { }

    # Process each line into a source
    for itm in data:
        s = itm.split()

        if len(s) < 3:
            print("ERROR! Source #{0} is missing metadata. Requires: TAG URL PARSER - only {1} provided.".format(i, len(s)))

        source = CallSource(s[0], s[1], getParserPath(s[2]))
        source.id = i

        # Parse optional metadata
        if len(s) > 3:
            source.interval = int(s[3])

        verifyParser(source.parser)
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
    if source.parser == None:
        return False
    
    return verifyParser(source.parser)

# Returns the full path to a parser
def getParserPath(parser):
    if not path.isabs(parser):
        parser = path.normpath(path.join('parsers', parser))
    return parser

# Verifies that a parser file exists
def verifyParser(parser):
    if not path.isfile(parser):
        if not parser in missing_parsers:
            missing_parsers.append(parser)
            print("WARNING! Missing source parser: {0}".format(parser))
        return False
    else:
        if parser in missing_parsers:
            missing_parsers.remove(parser)
            print("NOTICE! Source found: {0}".format(parser))
        return True



# Returns a list of calls being reported by a source.
def check(source):
    # Confirm that the source can be checked and parsed
    if not canCheck(source):
        return False

    # Retrieve the page contents
    ok, data = WebClient.openUrl(source.url)
    if not ok:
        print("ERROR! {0}".format(data))
        return False
    
    results = [ ]       # Provides a place for the parser script to store data

    # Compile and run the parser script
    try:
        exec(compile(open(source.parser, 'rb').read(), source.parser, 'exec'))
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
        self.parser = parser    # The path to the script responsible for parsing the source file
        self.interval = 60      # Time to wait between update checks, in seconds

        self.last_update = None # Last time this source was checked

    