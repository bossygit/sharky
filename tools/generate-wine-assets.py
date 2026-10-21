#!/usr/bin/env python3
"""Génère les visuels SVG de la cave Nasande Vins (bouteilles, hero, éditoriaux).

Aucune photo externe : tout est vectoriel, donc net sur tous les écrans et
versionnable dans le dépôt. Les données viennent de database/data/vins.json.
"""
import json
import math
import textwrap
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DATA = json.loads((ROOT / "database/data/vins.json").read_text(encoding="utf-8"))
OUT = ROOT / "public/images/vins"
OUT.mkdir(parents=True, exist_ok=True)

SERIF = "Georgia, 'Times New Roman', serif"
SANS = "Helvetica, Arial, sans-serif"


def hex_to_rgb(c):
    c = c.lstrip("#")
    return tuple(int(c[i:i + 2], 16) for i in (0, 2, 4))


def shade(c, factor):
    r, g, b = hex_to_rgb(c)
    f = lambda v: max(0, min(255, int(v * factor)))
    return "#%02x%02x%02x" % (f(r), f(g), f(b))


def esc(s):
    return (s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;"))


SHAPES = {
    "bordeaux": dict(bw=156, btop=410, shoulder=92, nw=46, ntop=196, foot=8),
    "burgundy": dict(bw=150, btop=420, shoulder=132, nw=42, ntop=196, foot=6),
    "champagne": dict(bw=178, btop=438, shoulder=72, nw=54, ntop=188, foot=10),
    "alsace": dict(bw=140, btop=392, shoulder=64, nw=36, ntop=168, foot=8),
    "port": dict(bw=160, btop=430, shoulder=96, nw=48, ntop=204, foot=9),
}


def bottle_body(shape, cx=300):
    s = SHAPES[shape]
    bw, btop, sh, nw = s["bw"], s["btop"], s["shoulder"], s["nw"]
    ntop, foot = s["ntop"], s["foot"]
    bottom = 742 - foot
    left, right = cx - bw / 2, cx + bw / 2
    nl, nr = cx - nw / 2, cx + nw / 2
    return (
        f"M {left:.1f} {bottom:.1f} "
        f"L {left:.1f} {btop:.1f} "
        f"C {left:.1f} {btop - sh * .52:.1f}, {nl - 9:.1f} {btop - sh * .92:.1f}, {nl:.1f} {btop - sh:.1f} "
        f"L {nl:.1f} {ntop:.1f} L {nr:.1f} {ntop:.1f} L {nr:.1f} {btop - sh:.1f} "
        f"C {nr + 9:.1f} {btop - sh * .92:.1f}, {right:.1f} {btop - sh * .52:.1f}, {right:.1f} {btop:.1f} "
        f"L {right:.1f} {bottom:.1f} "
        f"Q {cx:.1f} {bottom + 10:.1f} {left:.1f} {bottom:.1f} Z"
    )


def label_svg(w, uid):
    bg, ink, accent = w["label_bg"], w["label_ink"], w["label_accent"]
    y = 505
    h = 168
    x = 300 - 74
    lines = textwrap.wrap(w["nom"], 17)[:3]
    parts = [
        f'<rect x="{x}" y="{y}" width="148" height="{h}" fill="{bg}" stroke="{accent}" stroke-width="1.1"/>',
        f'<rect x="{x + 5}" y="{y + 5}" width="138" height="{h - 10}" fill="none" stroke="{accent}" stroke-width="0.4" opacity=".7"/>',
        f'<text x="300" y="{y + 27}" text-anchor="middle" font-family="{SANS}" font-size="7.5" letter-spacing="2.4" fill="{accent}">{esc(w["maison"].upper()[:30])}</text>',
    ]
    ty = y + 50
    for line in lines:
        parts.append(f'<text x="300" y="{ty}" text-anchor="middle" font-family="{SERIF}" font-size="14" fill="{ink}">{esc(line)}</text>')
        ty += 18
    parts.append(f'<line x1="{x + 34}" y1="{ty + 4}" x2="{x + 114}" y2="{ty + 4}" stroke="{accent}" stroke-width=".7" opacity=".8"/>')
    parts.append(f'<text x="300" y="{ty + 26}" text-anchor="middle" font-family="{SANS}" font-size="7.5" letter-spacing="1.6" fill="{ink}" opacity=".85">{esc(w["millesime"])} · {esc(w["format"].replace(" ", "").upper())}</text>')
    parts.append(f'<text x="300" y="{ty + 42}" text-anchor="middle" font-family="{SANS}" font-size="6.2" letter-spacing="1.1" fill="{ink}" opacity=".6">{esc(w["appellation"][:34].upper())}</text>')
    return "\n".join(parts)


def bottle_svg(w, cx=300, scale=1.0, y_offset=0.0):
    s = SHAPES[w["shape"]]
    glass = w["glass"]
    light = shade(glass, 1.55)
    dark = shade(glass, 0.55)
    uid = w["slug"].replace("-", "")
    body = bottle_body(w["shape"], cx)
    cap_top = s["ntop"] - 52
    return f'''
<g transform="translate({cx},{y_offset}) scale({scale}) translate(-300,-720)">
  <ellipse cx="300" cy="748" rx="{s['bw'] * .72:.0f}" ry="11" fill="#1a120d" opacity=".20"/>
  <defs>
    <linearGradient id="g{uid}" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="{dark}"/>
      <stop offset=".22" stop-color="{shade(glass, .8)}"/>
      <stop offset=".38" stop-color="{light}"/>
      <stop offset=".55" stop-color="{glass}"/>
      <stop offset=".85" stop-color="{shade(glass, .72)}"/>
      <stop offset="1" stop-color="{shade(glass, .5)}"/>
    </linearGradient>
    <linearGradient id="v{uid}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#000" stop-opacity=".05"/>
      <stop offset=".75" stop-color="#000" stop-opacity="0"/>
      <stop offset="1" stop-color="#000" stop-opacity=".28"/>
    </linearGradient>
  </defs>
  <path d="{body}" fill="url(#g{uid})"/>
  <path d="{body}" fill="url(#v{uid})"/>
  <path d="M {300 - s['bw'] / 2 + 16:.0f} {s['btop'] + 10:.0f} L {300 - s['bw'] / 2 + 16:.0f} 700"
        stroke="#fff" stroke-opacity=".16" stroke-width="7" stroke-linecap="round" fill="none"/>
  <rect x="{300 - s['nw'] / 2 - 2:.1f}" y="{cap_top}" width="{s['nw'] + 4}" height="{s['ntop'] - cap_top + 8:.1f}"
        fill="{w['label_accent']}" rx="2"/>
  <rect x="{300 - s['nw'] / 2 - 2:.1f}" y="{cap_top + 16}" width="{s['nw'] + 4}" height="3" fill="#000" opacity=".18"/>
  {label_svg(w, uid)}
</g>'''


def write(name, content):
    (OUT / name).write_text(content, encoding="utf-8")


def generate_bottles():
    for w in DATA["wines"]:
        svg = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 800" width="600" height="800" role="img" aria-label="{esc(w['maison'])} {esc(w['nom'])}">
{bottle_svg(w)}
</svg>'''
        write(w["slug"] + ".svg", svg)


def generate_hero():
    by_slug = {w["slug"]: w for w in DATA["wines"]}
    picks = [by_slug["chateau-margaux-2017"], by_slug["ruinart-blanc-de-blancs"], by_slug["whispering-angel-2023"]]
    bottles = []
    for i, w in enumerate(picks):
        bottles.append(bottle_svg(w, cx=[1180, 1380, 1580][i], scale=[1.22, 1.15, 1.1][i], y_offset=[70, 40, 90][i]))
    svg = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1800 1100" width="1800" height="1100" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Cave Nasande Vins">
  <defs>
    <radialGradient id="glow" cx=".72" cy=".42" r=".55">
      <stop offset="0" stop-color="#8d4c2c" stop-opacity=".55"/>
      <stop offset=".55" stop-color="#4a1520" stop-opacity=".22"/>
      <stop offset="1" stop-color="#180b0a" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#170d0b"/>
      <stop offset=".45" stop-color="#2a0f14"/>
      <stop offset="1" stop-color="#120a09"/>
    </linearGradient>
    <filter id="grain">
      <feTurbulence type="fractalNoise" baseFrequency=".9" numOctaves="3" stitchTiles="stitch"/>
      <feColorMatrix type="saturate" values="0"/>
      <feComponentTransfer><feFuncA type="linear" slope=".06"/></feComponentTransfer>
    </filter>
  </defs>
  <rect width="1800" height="1100" fill="url(#bg)"/>
  <rect width="1800" height="1100" fill="url(#glow)"/>
  <g fill="none" stroke="#b08d57" stroke-opacity=".26" stroke-width="1.4">
    <path d="M 860 940 L 860 330 A 420 420 0 0 1 1700 330 L 1700 940"/>
    <path d="M 920 940 L 920 350 A 360 360 0 0 1 1640 350 L 1640 940" stroke-opacity=".12"/>
    <line x1="80" y1="940" x2="1720" y2="940" stroke-opacity=".3"/>
  </g>
  <g>{''.join(bottles)}</g>
  <rect width="1800" height="1100" filter="url(#grain)" opacity=".55"/>
  <rect width="1800" height="1100" fill="#0e0706" opacity=".12"/>
</svg>'''
    write("hero.svg", svg)


def generate_editorial(name, title, bottles, tone):
    body = "".join(bottles)
    svg = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 1100" width="900" height="1100" preserveAspectRatio="xMidYMid slice" role="img" aria-label="{esc(title)}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{tone[0]}"/><stop offset="1" stop-color="{tone[1]}"/>
    </linearGradient>
    <radialGradient id="s" cx=".5" cy=".35" r=".7">
      <stop offset="0" stop-color="#fff" stop-opacity=".16"/><stop offset="1" stop-color="#fff" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="900" height="1100" fill="url(#bg)"/>
  <rect width="900" height="1100" fill="url(#s)"/>
  <g fill="none" stroke="#d8b982" stroke-opacity=".35" stroke-width="1">
    <rect x="70" y="70" width="760" height="960"/>
    <rect x="86" y="86" width="728" height="928" stroke-opacity=".16"/>
  </g>
  <g>{body}</g>
</svg>'''
    write(name, svg)


def generate_category_tiles():
    tones = {
        "champagnes": ("#241a10", "#5d4425"),
        "vins-rouges": ("#1a0c0e", "#4a121c"),
        "vins-blancs": ("#22261a", "#5d5a34"),
        "vins-roses": ("#3a2620", "#8a5a4a"),
        "vins-doux": ("#2a1a0e", "#6d4a1f"),
    }
    by_cat = {}
    for w in DATA["wines"]:
        by_cat.setdefault(w["categorie"], []).append(w)
    for cat, wines in by_cat.items():
        picks = wines[:2]
        bottles = [bottle_svg(picks[0], cx=330, scale=.92, y_offset=100)]
        if len(picks) > 1:
            bottles.append(bottle_svg(picks[1], cx=560, scale=.8, y_offset=180))
        t = tones[cat]
        svg = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 1100" width="900" height="1100" preserveAspectRatio="xMidYMid slice" role="img" aria-label="{esc(cat)}">
  <defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{t[0]}"/><stop offset="1" stop-color="{t[1]}"/></linearGradient></defs>
  <rect width="900" height="1100" fill="url(#bg)"/>
  <circle cx="450" cy="420" r="330" fill="#fff" opacity=".045"/>
  <g>{''.join(bottles)}</g>
</svg>'''
        write("cat-" + cat + ".svg", svg)


def generate_gift():
    svg = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1400 800" width="1400" height="800" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Coffrets cadeaux">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#171a12"/><stop offset="1" stop-color="#2e3524"/></linearGradient>
    <linearGradient id="box" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3c2318"/><stop offset="1" stop-color="#22130d"/></linearGradient>
  </defs>
  <rect width="1400" height="800" fill="url(#bg)"/>
  <g fill="none" stroke="#b08d57" stroke-opacity=".35"><path d="M 380 640 L 380 300 L 1020 300 L 1020 640"/><path d="M 360 640 L 1040 640"/></g>
  <g transform="translate(700,470)">
    <ellipse cx="0" cy="190" rx="240" ry="22" fill="#000" opacity=".25"/>
    <rect x="-210" y="-30" width="420" height="220" fill="url(#box)"/>
    <rect x="-228" y="-58" width="456" height="46" fill="#4a2a1b"/>
    <rect x="-32" y="-58" width="64" height="248" fill="#b08d57" opacity=".85"/>
    <rect x="-210" y="76" width="420" height="4" fill="#b08d57" opacity=".45"/>
    <path d="M 0 -58 C -90 -150, -190 -110, -110 -50 C -70 -22, -20 -40, 0 -58 C 20 -40, 70 -22, 110 -50 C 190 -110, 90 -150, 0 -58 Z" fill="#b08d57" opacity=".9"/>
  </g>
  <g opacity=".9">{bottle_svg(DATA["wines"][0], cx=280, scale=1.0, y_offset=90)}</g>
</svg>'''
    write("cadeaux.svg", svg)


def generate_favicon():
    svg = '''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
<rect width="64" height="64" rx="8" fill="#3e0c16"/>
<text x="32" y="40" text-anchor="middle" font-family="Georgia, serif" font-size="26" letter-spacing="1" fill="#d8b982">NV</text>
<line x1="16" y1="47" x2="48" y2="47" stroke="#d8b982" stroke-width="1"/>
</svg>'''
    write("favicon.svg", svg)


def generate_placeholder():
    svg = '''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 800" width="600" height="800">
<rect width="600" height="800" fill="#f3ebdc"/>
<g fill="none" stroke="#b08d57" stroke-opacity=".55" stroke-width="2">
  <path d="M 260 640 L 260 300 C 260 240, 240 220, 240 180 L 360 180 C 360 220, 340 240, 340 300 L 340 640 Z"/>
  <line x1="240" y1="170" x2="360" y2="170"/>
</g>
<text x="300" y="700" text-anchor="middle" font-family="Georgia, serif" font-size="18" fill="#7d7266">Nasande Vins</text>
</svg>'''
    write("placeholder.svg", svg)


if __name__ == "__main__":
    generate_bottles()
    generate_hero()
    by_slug = {w["slug"]: w for w in DATA["wines"]}
    generate_editorial(
        "editorial-bordeaux.svg",
        "Bordeaux, l'élégance intemporelle",
        [bottle_svg(by_slug["chateau-lynch-bages-2018"], cx=340, scale=1.05, y_offset=120),
         bottle_svg(by_slug["chateau-gloria-2019"], cx=570, scale=.95, y_offset=200)],
        ("#1a0b0d", "#3d1018"),
    )
    generate_editorial(
        "editorial-champagne.svg",
        "Le champagne, une signature",
        [bottle_svg(by_slug["bollinger-special-cuvee"], cx=450, scale=1.1, y_offset=140)],
        ("#2a2318", "#6a5530"),
    )
    generate_category_tiles()
    generate_gift()
    generate_favicon()
    generate_placeholder()
    print("OK", len(list(OUT.glob("*.svg"))), "fichiers dans", OUT)
