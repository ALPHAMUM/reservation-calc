import urllib.request
import re

req = urllib.request.Request('https://balesin.com/', headers={'User-Agent': 'Mozilla/5.0'})
html = urllib.request.urlopen(req).read().decode('utf-8')
match = re.search(r'<img[^>]*src=[\'"]([^\'"]*logo[^\'"]*(?:png|svg|jpg))[\'"]', html, re.IGNORECASE)
if match:
    url = match.group(1)
    print("Found URL:", url)
    
    # download it
    img_req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    img_data = urllib.request.urlopen(img_req).read()
    
    import base64
    b64 = base64.b64encode(img_data).decode('utf-8')
    ext = url.split('.')[-1]
    print(f"data:image/{ext};base64,{b64}"[:100] + "...")
    
    with open('public/images/balesin-logo.txt', 'w') as f:
        f.write(f"data:image/{ext};base64,{b64}")
else:
    print('No logo found')
