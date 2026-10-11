"""
Generates the original placeholder illustrations used by the demo seeder.
Run:  python database/seeders/assets/generate_assets.py
Replace them with real photography from the admin Media Library in production.
"""
import os
import random

OUT = os.path.dirname(os.path.abspath(__file__))
NAVY = '#0b2545'
BLUE = '#1677e5'

def save(name, w, h, body, bg=None):
    defs = '''<defs>
  <linearGradient id="glass" x1="0" x2="1"><stop offset="0" stop-color="#ffffff" stop-opacity=".9"/><stop offset=".35" stop-color="#cfe6ff" stop-opacity=".55"/><stop offset=".7" stop-color="#9cc9ff" stop-opacity=".45"/><stop offset="1" stop-color="#ffffff" stop-opacity=".8"/></linearGradient>
  <linearGradient id="white" x1="0" x2="1"><stop offset="0" stop-color="#e9eef5"/><stop offset=".45" stop-color="#ffffff"/><stop offset="1" stop-color="#d7dee8"/></linearGradient>
  <linearGradient id="amber" x1="0" x2="1"><stop offset="0" stop-color="#7a3b0c"/><stop offset=".4" stop-color="#b8661c"/><stop offset="1" stop-color="#6b3109"/></linearGradient>
  <linearGradient id="navybg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#071a33"/><stop offset=".6" stop-color="#0d3b73"/><stop offset="1" stop-color="#1767c4"/></linearGradient>
  <linearGradient id="lightbg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f3f8ff"/><stop offset="1" stop-color="#dcebfb"/></linearGradient>
  <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8ec5ff"/><stop offset="1" stop-color="#e8f3ff"/></linearGradient>
  <radialGradient id="glow" cx=".7" cy=".4" r=".6"><stop offset="0" stop-color="#3d8bff" stop-opacity=".55"/><stop offset="1" stop-color="#3d8bff" stop-opacity="0"/></radialGradient>
</defs>'''
    bgrect = f'<rect width="{w}" height="{h}" fill="{bg}"/>' if bg else ''
    svg = f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}">{defs}{bgrect}{body}</svg>'
    open(os.path.join(OUT, name), 'w', encoding='utf-8').write(svg)

def bottle(x, base, w, h, fill='url(#glass)', cap=BLUE, ribs=True):
    neck = w * 0.32
    shoulder = h * 0.28
    body = f'''<g>
<rect x="{x - neck/2 - 3}" y="{base - h - 16}" width="{neck + 6}" height="18" rx="4" fill="{cap}"/>
<rect x="{x - neck/2 - 3}" y="{base - h - 16}" width="{neck + 6}" height="5" rx="2" fill="#ffffff" opacity=".35"/>
<path d="M{x - neck/2},{base - h + 2} L{x - neck/2},{base - h + shoulder*0.35} C{x - w/2},{base - h + shoulder*0.7} {x - w/2},{base - h + shoulder} {x - w/2},{base - h + shoulder + 6}
 L{x - w/2},{base - 10} Q{x - w/2},{base} {x - w/2 + 10},{base} L{x + w/2 - 10},{base} Q{x + w/2},{base} {x + w/2},{base - 10}
 L{x + w/2},{base - h + shoulder + 6} C{x + w/2},{base - h + shoulder} {x + w/2},{base - h + shoulder*0.7} {x + neck/2},{base - h + shoulder*0.35} L{x + neck/2},{base - h + 2} Z"
 fill="{fill}" stroke="#ffffff" stroke-opacity=".6" stroke-width="1.5"/>'''
    if ribs:
        for i in range(3):
            yy = base - h * (0.18 + i * 0.07)
            body += f'<rect x="{x - w/2 + 2}" y="{yy}" width="{w - 4}" height="3" fill="#ffffff" opacity=".35"/>'
    body += f'<rect x="{x - w/2 + w*0.14}" y="{base - h + shoulder + 12}" width="{w*0.1}" height="{h*0.5}" rx="3" fill="#ffffff" opacity=".55"/></g>'
    return body

def jar(x, base, w, h, fill='url(#glass)', lid='#e53935'):
    return f'''<g>
<rect x="{x - w/2}" y="{base - h}" width="{w}" height="{h}" rx="{w*0.12}" fill="{fill}" stroke="#ffffff" stroke-opacity=".7" stroke-width="1.5"/>
<rect x="{x - w/2 - 4}" y="{base - h - h*0.2}" width="{w + 8}" height="{h*0.22}" rx="6" fill="{lid}"/>
<rect x="{x - w/2 - 4}" y="{base - h - h*0.2}" width="{w + 8}" height="{h*0.06}" rx="3" fill="#ffffff" opacity=".3"/>
<rect x="{x - w/2 + w*0.12}" y="{base - h + h*0.15}" width="{w*0.1}" height="{h*0.65}" rx="3" fill="#ffffff" opacity=".55"/>
</g>'''

def cap(x, y, r, color, top=True):
    g = f'<g><ellipse cx="{x}" cy="{y + r*0.55}" rx="{r}" ry="{r*0.38}" fill="#000" opacity=".12"/>'
    g += f'<rect x="{x - r}" y="{y - r*0.2}" width="{2*r}" height="{r*0.75}" fill="{color}"/>'
    for i in range(10):
        xx = x - r + (i + 0.5) * (2 * r / 10)
        g += f'<rect x="{xx - 1}" y="{y - r*0.15}" width="2" height="{r*0.65}" fill="#000" opacity=".12"/>'
    g += f'<ellipse cx="{x}" cy="{y + r*0.55}" rx="{r}" ry="{r*0.3}" fill="{color}"/>'
    g += f'<ellipse cx="{x}" cy="{y - r*0.2}" rx="{r}" ry="{r*0.38}" fill="{color}"/><ellipse cx="{x}" cy="{y - r*0.2}" rx="{r}" ry="{r*0.38}" fill="#fff" opacity=".22"/>'
    return g + '</g>'

def shelf(w, y):
    return f'<rect x="0" y="{y}" width="{w}" height="6" fill="#ffffff" opacity=".18"/>'

# 1. Hero (dark navy, bottles on the right)
b = f'<rect width="1600" height="800" fill="url(#navybg)"/><rect width="1600" height="800" fill="url(#glow)"/>'
for i in range(14):
    b += f'<rect x="{600 + i*70}" y="0" width="2" height="800" fill="#ffffff" opacity=".04"/>'
random.seed(4)
specs = [(860, 230, 560, BLUE), (990, 180, 470, '#ffffff'), (1110, 260, 600, BLUE), (1250, 150, 420, '#2e7d32'),
         (1370, 210, 520, BLUE), (1490, 170, 450, '#ffffff'), (930, 120, 300, '#e53935'), (1180, 120, 300, '#fbc02d')]
for x, w, h, c in specs:
    b += bottle(x, 760, w * 0.55, h, cap=c)
b += shelf(1600, 760)
b += '<rect width="1600" height="800" fill="#06162b" opacity=".08"/>'
save('hero-packaging.svg', 1600, 800, b)

# 2. Second hero slide: jars & caps on navy
b = f'<rect width="1600" height="800" fill="url(#navybg)"/><rect width="1600" height="800" fill="url(#glow)"/>'
for i, (x, c) in enumerate([(880, '#e53935'), (1040, '#1677e5'), (1200, '#111827'), (1360, '#ffffff'), (1500, '#e53935')]):
    b += jar(x, 760, 120 + (i % 2) * 30, 200 + (i % 3) * 40, lid=c)
for i, c in enumerate(['#1677e5', '#e53935', '#2e7d32', '#fbc02d', '#ffffff', '#111827']):
    b += cap(820 + i * 120, 300 + (i % 2) * 40, 40, c)
b += shelf(1600, 760)
save('hero-closures.svg', 1600, 800, b)

# 3. Bottles category (light)
b = '<rect width="800" height="600" fill="url(#lightbg)"/>'
for x, w, h, c in [(140, 90, 330, BLUE), (250, 80, 380, BLUE), (360, 110, 420, '#ffffff'), (470, 80, 300, '#2e7d32'), (570, 100, 450, BLUE), (680, 80, 350, '#ffffff')]:
    b += bottle(x, 560, w, h, cap=c)
b += bottle(420, 560, 70, 220, fill='url(#amber)', cap='#111827', ribs=False)
save('bottles.svg', 800, 600, b)

# 4. Caps category
b = '<rect width="800" height="600" fill="url(#lightbg)"/>'
random.seed(7)
colors = ['#1677e5', '#e53935', '#2e7d32', '#fbc02d', '#ffffff', '#111827', '#1677e5', '#ffffff', '#e53935']
pos = [(180, 260), (330, 230), (480, 250), (630, 270), (250, 390), (400, 380), (550, 400), (320, 500), (480, 510)]
for (x, y), c in zip(pos, colors):
    b += cap(x, y, 62, c)
save('caps.svg', 800, 600, b)

# 5. Jars category
b = '<rect width="800" height="600" fill="url(#lightbg)"/>'
for x, w, h, c in [(150, 140, 230, '#e53935'), (320, 120, 300, '#111827'), (480, 160, 210, '#1677e5'), (640, 130, 270, '#e53935'), (240, 110, 140, '#9ca3af'), (560, 100, 120, '#ffffff')]:
    b += jar(x, 560, w, h, lid=c)
save('jars.svg', 800, 600, b)

# 6. Product thumbnails
def product(name, items):
    save(name, 600, 450, '<rect width="600" height="450" fill="#f5f9ff"/>' + items)

product('pet-water-bottle.svg', ''.join(bottle(80 + i * 72, 420, 54, 220 + (i % 3) * 50, cap=BLUE) for i in range(7)))
product('hdpe-pharma-bottle.svg', ''.join(bottle(110 + i * 128, 410, 100, 200 + (i % 2) * 40, fill='url(#white)', cap=c, ribs=False) for i, c in enumerate(['#fbc02d', '#1677e5', '#e53935', '#2e7d32'])))
product('flip-top-cap.svg', cap(200, 250, 90, '#1677e5') + cap(380, 290, 80, '#ffffff') + cap(300, 170, 60, '#1677e5'))
product('screw-cap.svg', cap(170, 240, 80, '#e53935') + cap(330, 210, 85, '#1677e5') + cap(460, 270, 75, '#ffffff'))
product('cosmetic-jar.svg', jar(160, 390, 150, 120, fill='url(#white)', lid='#111827') + jar(330, 390, 130, 160, fill='url(#glass)', lid='#111827') + jar(480, 390, 120, 100, fill='url(#white)', lid='#ffffff'))
product('food-storage-jar.svg', jar(150, 400, 140, 210, lid='#e53935') + jar(310, 400, 140, 240, lid='#1677e5') + jar(470, 400, 140, 200, lid='#e53935'))
product('hdpe-gallon.svg', bottle(200, 410, 180, 300, fill='url(#white)', cap='#1677e5', ribs=False) + bottle(420, 410, 150, 250, fill='url(#white)', cap='#2e7d32', ribs=False))
product('preform.svg', ''.join(f'<rect x="{90 + i*85}" y="{140 + (i%2)*20}" width="40" height="230" rx="18" fill="url(#glass)" stroke="#9cc9ff"/><rect x="{84 + i*85}" y="{124 + (i%2)*20}" width="52" height="22" rx="4" fill="{BLUE}"/>' for i in range(5)))

# 7. Factory / building
def factory(name, w, h, dusk=False):
    sky = 'url(#navybg)' if dusk else 'url(#sky)'
    b = f'<rect width="{w}" height="{h}" fill="{sky}"/>'
    b += f'<rect x="0" y="{h*0.78}" width="{w}" height="{h*0.22}" fill="#cfd8dc"/><rect x="0" y="{h*0.82}" width="{w}" height="{h*0.18}" fill="#78909c"/>'
    for i in range(9):
        b += f'<circle cx="{40 + i*w/9}" cy="{h*0.76}" r="{h*0.07}" fill="#2e7d32"/><circle cx="{60 + i*w/9}" cy="{h*0.74}" r="{h*0.06}" fill="#388e3c"/>'
    bx, by, bw, bh = w*0.12, h*0.28, w*0.76, h*0.5
    b += f'<rect x="{bx}" y="{by}" width="{bw}" height="{bh}" fill="#eceff1"/><rect x="{bx}" y="{by}" width="{bw}" height="{bh*0.12}" fill="#cfd8dc"/>'
    for r in range(3):
        for c in range(10):
            b += f'<rect x="{bx + 20 + c*(bw-40)/10}" y="{by + bh*0.22 + r*bh*0.24}" width="{(bw-40)/10 - 8}" height="{bh*0.18}" fill="#1e4f8a" opacity=".85"/><rect x="{bx + 20 + c*(bw-40)/10}" y="{by + bh*0.22 + r*bh*0.24}" width="{(bw-40)/20}" height="{bh*0.18}" fill="#ffffff" opacity=".15"/>'
    b += f'<rect x="{bx + bw*0.42}" y="{by + bh*0.62}" width="{bw*0.16}" height="{bh*0.38}" fill="#0d3b73"/>'
    b += f'<text x="{bx + bw*0.5}" y="{by + bh*0.1}" text-anchor="middle" font-family="Arial, sans-serif" font-weight="700" font-size="{bh*0.08}" fill="{NAVY}">IR LIMITED</text>'
    save(name, w, h, b)

factory('factory.svg', 1200, 800)
factory('factory-banner.svg', 1600, 600, dusk=True)

# 8. Production line / quality lab / sustainability / target
b = '<rect width="1200" height="800" fill="#0d2c54"/>'
b += '<rect x="0" y="560" width="1200" height="40" fill="#90a4ae"/><rect x="0" y="600" width="1200" height="200" fill="#37474f"/>'
for i in range(11):
    b += bottle(60 + i * 105, 560, 60, 230, cap=BLUE)
for i in range(6):
    b += f'<rect x="{i*200}" y="80" width="140" height="300" fill="#1e4f8a" opacity=".5"/>'
save('production-line.svg', 1200, 800, b)

b = '<rect width="1200" height="800" fill="#e3f0ff"/>'
b += '<rect x="0" y="600" width="1200" height="200" fill="#b0bec5"/>'
b += '<path d="M520 220 h160 v40 l120 300 a30 30 0 0 1 -28 40 h-344 a30 30 0 0 1 -28 -40 l120 -300 z" fill="url(#glass)" stroke="#1677e5" stroke-width="6"/>'
b += '<path d="M440 470 h320 l40 90 a30 30 0 0 1 -28 40 h-344 a30 30 0 0 1 -28 -40 z" fill="#1677e5" opacity=".55"/>'
b += bottle(900, 600, 110, 380, cap=BLUE) + bottle(260, 600, 100, 330, cap='#2e7d32')
b += '<circle cx="600" cy="140" r="54" fill="none" stroke="#0b2545" stroke-width="10"/><path d="M640 180 l60 60" stroke="#0b2545" stroke-width="14" stroke-linecap="round"/>'
save('quality-lab.svg', 1200, 800, b)

b = '<rect width="1200" height="800" fill="url(#navybg)"/>'
b += '<ellipse cx="600" cy="680" rx="380" ry="90" fill="#5d4037"/>'
b += '<path d="M600 660 C600 520 600 430 600 360" stroke="#2e7d32" stroke-width="16" fill="none"/>'
b += '<path d="M600 470 C470 470 420 380 430 300 C540 300 600 360 600 470 Z" fill="#43a047"/><path d="M600 420 C730 420 790 330 780 240 C660 250 600 320 600 420 Z" fill="#66bb6a"/>'
save('sustainability.svg', 1200, 800, b)

b = '<rect width="1200" height="800" fill="url(#lightbg)"/>'
for r, c in [(300, '#0b2545'), (240, '#ffffff'), (180, '#1677e5'), (120, '#ffffff'), (60, '#0b2545')]:
    b += f'<circle cx="560" cy="430" r="{r}" fill="{c}"/>'
b += '<path d="M560 430 L900 160" stroke="#0b2545" stroke-width="14" stroke-linecap="round"/><path d="M900 160 l60 -70 l10 60 l60 10 l-70 60 z" fill="#1677e5"/>'
save('target.svg', 1200, 800, b)

# 9. Founder placeholder portrait (neutral silhouette, replace with a real photo)
b = '<rect width="600" height="700" fill="url(#lightbg)"/>'
b += '<circle cx="300" cy="270" r="120" fill="#90a4ae"/><path d="M80 700 C90 520 190 430 300 430 C410 430 510 520 520 700 Z" fill="#455a64"/>'
b += '<path d="M260 430 L300 540 L340 430 Z" fill="#ffffff"/><path d="M290 450 L300 600 L310 450 Z" fill="#0b2545"/>'
save('founder-placeholder.svg', 600, 700, b)

# 10. Logos
def logo(name, color, sub):
    b = f'''<g fill="{color}">
<path d="M8 50 V22 Q8 8 22 8 H50 Q64 8 64 22 V50 H54 V24 Q54 18 48 18 H24 Q18 18 18 24 V50 Z"/>
<rect x="26" y="26" width="6" height="24"/><rect x="40" y="26" width="6" height="24"/><rect x="2" y="50" width="68" height="5" rx="2"/>
<text x="80" y="42" font-family="Poppins, Arial, sans-serif" font-size="30" font-weight="700">IR</text>
<text x="122" y="42" font-family="Poppins, Arial, sans-serif" font-size="30" font-weight="400">LIMITED</text>
<text x="81" y="58" font-family="Arial, sans-serif" font-size="9.5" opacity=".8">{sub}</text>
</g>'''
    save(name, 260, 64, b)

# The official logo is supplied as ir-logo.png / ir-logo-light.png / ir-favicon.png (not generated).

# 11. Neutral placeholder client logos (fictional wordmarks; replace with real, permitted logos)
for i, (n, c) in enumerate([('AquaPure', '#1677e5'), ('FreshFarm', '#2e7d32'), ('NovaPharma', '#6a1b9a'), ('Crystal Drinks', '#0b2545'), ('GreenLeaf Foods', '#388e3c'), ('Bright Home', '#ef6c00')]):
    save(f'client-{i+1}.svg', 240, 80, f'<circle cx="34" cy="40" r="22" fill="{c}"/><text x="34" y="47" text-anchor="middle" font-family="Arial" font-weight="700" font-size="20" fill="#fff">{n[0]}</text><text x="66" y="48" font-family="Arial, sans-serif" font-weight="700" font-size="20" fill="{c}">{n}</text>')

# 12. Certification badge placeholders (shown only after an admin marks them verified)
for slug, text in [('iso-9001', 'ISO 9001'), ('haccp', 'HACCP'), ('brc', 'BRCGS'), ('rohs', 'RoHS')]:
    save(f'cert-{slug}.svg', 200, 120, f'<rect x="4" y="4" width="192" height="112" rx="14" fill="#fff" stroke="{BLUE}" stroke-width="3"/><text x="100" y="70" text-anchor="middle" font-family="Arial" font-weight="700" font-size="26" fill="{NAVY}">{text}</text><text x="100" y="94" text-anchor="middle" font-family="Arial" font-size="11" fill="#607d8b">placeholder</text>')

print('generated in', OUT)
