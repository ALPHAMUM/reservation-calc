import urllib.request, urllib.parse, json, base64

url = 'https://freeimage.host/api/1/upload'
api_key = '6d207e02198a847aa98d0a2a901485a5'

with open('public/images/balesin-logo.png', 'rb') as f:
    img_b64 = base64.b64encode(f.read()).decode('utf-8')

data = urllib.parse.urlencode({
    'key': api_key,
    'action': 'upload',
    'source': img_b64,
    'format': 'json'
}).encode('utf-8')

req = urllib.request.Request(url, data=data, headers={
    'User-Agent': 'Mozilla/5.0'
})

try:
    response = urllib.request.urlopen(req)
    result = json.loads(response.read().decode('utf-8'))
    print("Direct URL:", result['image']['url'])
except Exception as e:
    print("Error:", e)
