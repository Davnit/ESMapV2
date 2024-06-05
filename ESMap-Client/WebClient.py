
from urllib import request, parse

USER_AGENT = r"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:103.0) Gecko/20100101 Firefox/103.0"

# Makes a request to the specified web server location.
# If it returns true, the second value is the contents of the returned page
# If it returns false, the second value is the exception object
def makeRequest(url, params, post, timeout, encoding='utf-8'):
    if params:
        url += "?" + parse.urlencode(params)

    if post:
        post = parse.urlencode(post).encode(encoding)

    try:
        req = request.Request(url, post, headers={'User-Agent': USER_AGENT})
        return True, request.urlopen(req, timeout=timeout).read().decode(encoding)
    except Exception as ex:
        return False, ex

# Posts data to a page
def postData(url, data, params=None, timeout=20, encoding='utf-8'):
    return makeRequest(url, params, data, timeout, encoding)

# Gets the contents of a page without posting any data.
def openUrl(url, params=None, timeout=20, encoding='utf-8'):
    return makeRequest(url, params, None, timeout, encoding)
