
import configparser, io

defaultConfigPath = 'config.ini'

def load(path=None):
    if path is None:
        path = defaultConfigPath

    p = configparser.ConfigParser()
    p.read(path)

    return Config(p)

def save(c, path=None):
    if path is None:
        path = defaultConfigPath

    p = configparser.ConfigParser()
    p.add_section('MAIN')
    p.set('MAIN', 'test', c.TestValue)
    if c.SourceLocation is not None: p.set('MAIN', 'source_location', c.SourceLocation)
    p.set('MAIN', 'tick_interval', str(c.TickInterval))

    with open(path, 'w') as file:
        p.write(file)

class Config():
    def __init__(self, p):
        self.TestValue = p.get('MAIN', 'test', fallback='default value')            
        self.SourceLocation = p.get('MAIN', 'source_location', fallback=None)       # Path to local file containing source metadata
        self.TickInterval = int(p.get('MAIN', 'tick_interval', fallback="60"))        # Time to wait between checking if sources need to be updated
