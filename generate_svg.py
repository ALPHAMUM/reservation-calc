import base64
svg = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 80">
<path d="M 30,45 Q 60,10 100,20 Q 140,30 145,45 Q 100,60 30,45 Z" fill="none" stroke="#6b9bc3" stroke-width="2"/>
<path d="M 100,20 Q 110,12 120,22 M 110,22 Q 120,15 130,25 M 120,25 Q 130,18 140,28" fill="none" stroke="#6b9bc3" stroke-width="2"/>
<text x="100" y="65" font-family="sans-serif" font-size="24" fill="#6b9bc3" text-anchor="middle" letter-spacing="2">BALESIN</text>
<text x="100" y="78" font-family="sans-serif" font-size="10" fill="#6b9bc3" text-anchor="middle" letter-spacing="4">ISLAND</text>
</svg>"""
b64 = base64.b64encode(svg.encode('utf-8')).decode('utf-8')
with open('svg_b64.txt', 'w') as f:
    f.write(f"data:image/svg+xml;base64,{b64}")
