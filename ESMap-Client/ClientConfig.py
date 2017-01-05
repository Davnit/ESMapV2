
import configparser
from os import path

defaultConfigPath = 'config.ini'
googleGeocodeAPI = "https://maps.googleapis.com/maps/api/geocode/json"

def load(configPath=None):
    if configPath is None:
        configPath = defaultConfigPath

    # Try to parse the config file
    p = configparser.ConfigParser()
    p.read(configPath)
    c = Config(p)

    # If the file doesn't exist, create it with default values
    if not path.isfile(configPath):
        save(c, configPath)
    return c

def save(c, configPath=None):
    if configPath is None:
        configPath = defaultConfigPath

    p = configparser.ConfigParser()

    p.add_section("MAIN")
    p.set("MAIN", "tick_interval", str(c.TickInterval))
    p.set("MAIN", "use_remote", str(c.UseRemoteServer))

    p.add_section("PATHS")
    p.set("PATHS", "sources", c.SourceLocation)
    p.set("PATHS", "ingest", c.IngestUrl)
    p.set("PATHS", "data", c.DataUrl)

    p.add_section("GEOCODES")
    p.set("GEOCODES", "enabled", str(c.EnableGeocodes))
    p.set("GEOCODES", "api", c.GeoApiUrl)
    p.set("GEOCODES", "api_key", c.GeoApiKey)

    with open(configPath, "w") as file:
        p.write(file)

def parseBool(str):
    return (str.lower() == "true") or (str == "1")

class Config():
    def __init__(self, p):
        self.TickInterval = int(p.get("MAIN", "tick_interval", fallback="60"))           # Time to wait between checking if sources need to be updated
        self.UseRemoteServer = parseBool(p.get("MAIN", "use_remote", fallback="False"))  # Should the client sync with a remote server?
      
        self.SourceLocation = p.get("PATHS", "sources", fallback="sources.txt")          # Location to obtain sources from (local only, otherwise DataUrl is used)
        self.IngestUrl = p.get("PATHS", "ingest", fallback="")                           # URL to report new data to
        self.DataUrl = p.get("PATHS", "data", fallback="")                               # URL to retrieve data from

        self.EnableGeocodes = parseBool(p.get("GEOCODES", "enabled", fallback="False"))  # Should the client process geocode requests
        self.GeoApiUrl = p.get("GEOCODES", "api", fallback=googleGeocodeAPI)             # API used to process geocode requests
        self.GeoApiKey = p.get("GEOCODES", "api_key", fallback="")                       # Client key assigned by the geocode API



