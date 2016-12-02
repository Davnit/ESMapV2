
import json

import WebClient


class SourceUpdateReport():
    def __init__(self, source, result, added, removed):
        self.source = source
        self.active = result
        self.added = added
        self.expired = removed

    def hasChanges(self):
        return len(self.added) > 0 or len(self.expired) > 0

    def getChangeData(self):
        return {
            "source": self.source.id,
            "new": [ v.getReportData() for v in self.added.values() ],
            "expired": [ v.getKey() for v in self.expired.values() ]
        }

    def sendChangeReport(self, url):
        data = { "calldata": json.dumps(self.getChangeData(), separators=(',',':')) }

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

                # Did the server accept all of the changes?
                if not match:
                    msg = "Reporting mismatch. Server added {0} rows, expired {1}."
                    print(msg.format(status["added"], status["expired"]))
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


class GeocodeReport():
    def __init__(self, requests):
        self.requests = requests

    # Returns a dictionary of ID -> Geocode Results for the requests contained in this report
    def getData(self):
        return { r.id: r.results for r in self.requests }

    def sendReport(self, url):
        data = { "geodata": json.dumps(self.getData(), separators=(',',':')) }

        ok, response = WebClient.postData(url, data)
        if ok:
            data = json.loads(response)
            status = data["status"]

            if status["success"]:
                match = True
                if status["resolved"] != len(self.requests): match = False

                if not match:
                    print("Reporting mismatch. Server resolved {0} locations.".format(status["resolved"]))
                else:
                    return True
            else:
                print("Report failed. Reason:", status["message"])
        else:
            print("Report submission failed. Reason:", str(response))

        return False
        