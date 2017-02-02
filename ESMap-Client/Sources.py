
import json
import WebClient, Calls
from os import path
from datetime import datetime

missing_parsers = [ ]

# Retreives a list of call sources from a local file
def getLocalSources(sourcePath):

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

def getRemoteSources(sourceUrl):
    sources = { }

    # Request sources from remote server
    ok, response = WebClient.openUrl(sourceUrl, { "request": 1 })
    if not ok:
        print("ERROR! Unable to obtain sources: " + str(response))
        return sources

    # Decode returned data and check status
    data = json.loads(response)
    
    status = data["status"]
    if not status["success"]:
        print("ERROR: Error getting sources: " + status["message"])
        return sources

    # Check for returned values
    if not isinstance(data["data"], dict):
        return sources

    # Parse returned values
    for srcID, sInfo in data["data"].items():
        src = CallSource(sInfo["tag"], sInfo["url"], getParserPath(sInfo["parser"]))
        src.id = srcID
        src.interval = int(sInfo["interval"])

        verifyParser(src.parser)
        sources[src.id] = src

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
        print("ERROR! Failed to open source {0}: {1}".format(source.tag, data))
        return False
    
    results = [ ]       # Provides a place for the parser script to store data
    replacements = { }  # Dictionary of localized location elements to be replaced.

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
        c = Calls.CallData(r["meta"])
        c.source = source
        c.key = r["key"]
        c.category = r["category"]

        # Do localized location replacements
        if len(replacements) > 0:
            location = [ ]
            for element in r["location"].split():           # For each part of the location
                found = False

                for find, replace in replacements.items():  # Check for each replacement
                    if element.upper() == find.upper():
                        found = True
                        location.append(replace)
                        break;

                # If nothing to be replaced was found just add the element
                if not found:
                    location.append(element)

            c.location = " ".join(location)
        else:
            c.location = r["location"]

        # Check for provided coordinates
        if ("geo_lat" in r) and ("geo_lng" in r):
            c.coords = [ r["geo_lat"], r["geo_lng"] ]

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

        

    