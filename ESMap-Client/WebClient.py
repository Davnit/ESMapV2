
from urllib import request, parse

# Makes a request to the specified web server location.
# If it returns true, the second value is the contents of the returned page
# If it returns false, the second value is the exception object
def makeRequest(url, params, post, timeout, encoding='utf-8'):
    if params:
        url += "?" + parse.urlencode(params)

    if post:
        post = parse.urlencode(post).encode(encoding)

    try:
        return True, request.urlopen(url, post, timeout).read().decode(encoding)
    except Exception as ex:
        return False, ex

# Posts data to a page
def postData(url, data, params=None, timeout=20, encoding='utf-8'):
    return makeRequest(url, params, data, timeout, encoding)

# Gets the contents of a page without posting any data.
def openUrl(url, params=None, timeout=20, encoding='utf-8'):
    return makeRequest(url, params, None, timeout, encoding)
