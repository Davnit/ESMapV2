
import json

import WebClient


class SourceUpdateReport():
    def __init__(self, source, result, added, removed, updated):
        self.source = source
        self.active = result
        self.added = added
        self.expired = removed
        self.updated = updated

    def hasChanges(self):
        return len(self.added) > 0 or len(self.expired) > 0 or len(self.updated) > 0

    def getData(self):
        return {
            "source": self.source.id,
            "new": [ v.getReportData() for v in self.added.values() ],
            "expired": [ v.getKey() for v in self.expired.values() ],
            "updated": { k: v for k,v in self.updated.items() }
        }

    def sendReport(self, url, key = None):
        data = { "calldata": json.dumps(self.getData(), separators=(',',':')) }

        if key is not None and len(key) > 0:
            data["key"] = key

        # Submit data
        ok, response = WebClient.postData(url, data)
        if ok:
            data = json.loads(response)
            status = data["status"]

            # If the server accepted the report, compare the results
            if status["success"]:
                match = True
                if status["added"] != len(self.added): match = False
                if status["expired"] != len(self.expired): match = False
                if status["updated"] != len(self.updated): match = False

                # Did the server accept all of the changes?
                if not match:
                    msg = "Reporting mismatch. Server added {0}/{1} rows, expired {2}/{3}, updated {4}/{5}."
                    print(msg.format(status["added"], len(self.added), 
                                     status["expired"], len(self.expired), 
                                     status["updated"], len(self.updated)))
                else:
                    return True
            else:
                print("Report failed. Reason: " + status["message"])
        else:
            print("Report submission failed. Reason: " + response)

        # default fail
        return False

    def printChanges(self):
        msg = "UPDATE from {0}: {1} active calls ({2} new, {3} expired)"
        print(msg.format(self.source.tag, len(self.active), len(self.added), len(self.expired)))

        # Print added calls
        if len(self.added) > 0:
            print("\tNEW:")
            for c in self.added.values():
                print("\t\t", c.getLongDisplayString())

        # Print expired calls
        if len(self.expired) > 0:
            print("\tEXPIRED:")
            for c in self.expired.values():
                print("\t\t", c.getShortDisplayString())

        if len(self.updated) > 0:
            print("\tUPDATED:")
            for c in self.updated.keys():
                print("\t\t", c)
                for k,v in self.updated[c].items():
                    # Also print changes in sub-dictionaries
                    if isinstance(v, dict):
                        for sk,sv in v.items():
                            print("\t\t\tmeta.{0}: {1}".format(sk, sv))
                    else:
                        print("\t\t\t{0}: {1}".format(k, v))


class GeocodeReport():
    def __init__(self, requests):
        self.requests = requests

    # Returns a dictionary of ID -> Geocode Results for the requests contained in this report
    def getData(self):
        return { r.id: r.results for r in self.requests if r.resolved == True }

    def sendReport(self, url, key = None):
        data = { "geodata": json.dumps(self.getData(), separators=(',',':')) }

        if key is not None and len(key) > 0:
            data["key"] = key

        ok, response = WebClient.postData(url, data)
        if ok:
            data = json.loads(response)
            status = data["status"]

            if status["success"]:
                match = True
                if "resolved" in status and status["resolved"] != len(self.requests): match = False

                if not match:
                    print("Reporting mismatch. Server resolved {0} locations.".format(status["resolved"]))
                    
                return True
            else:
                print("Geocode report failed. Reason:", status["message"])
        else:
            print("Geocode report submission failed. Reason:", str(response))

        return False
        