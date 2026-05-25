import urllib.request
import re
try:
    req = urllib.request.Request('https://alphaland.com.ph/balesin-island-club/', headers={'User-Agent': 'Mozilla/5.0'})
    html = urllib.request.urlopen(req).read().decode('utf-8')
    logos = re.findall(r'<img[^>]*src=[\'"]([^\'"]*logo[^\'"]*png)[\'"]', html, re.IGNORECASE)
    print("Found logos:", logos)
except Exception as e:
    print(e)
