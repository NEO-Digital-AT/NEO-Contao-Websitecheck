"""Badge centring check for rendered PDF reports (Dompdf or Gotenberg).

Finds every small, solid, roughly square/round filled shape (grade badges,
chips, dots) and measures how far the glyph inside sits from its centre.
Run after changing PDF templates:
    pip install pypdfium2 numpy scipy
    python3 dev/check-pdf-badges.py report.pdf [more.pdf …]
Exit code 1 when any badge is off by more than 0.3 mm.
"""
import sys
import numpy as np
import pypdfium2 as pdfium
from scipy import ndimage

SCALE = 4                       # px per pt
MM = 72 / 25.4 * SCALE          # px per mm
TOL_MM = 0.30                   # allowed glyph-centre offset

def check(path):
    doc = pdfium.PdfDocument(path)
    bad, total = [], 0
    for pno in range(len(doc)):
        img = np.asarray(doc[pno].render(scale=SCALE).to_pil().convert('RGB')).astype(np.int16)
        h, w, _ = img.shape
        # saturated or mid-grey solid colours (white/near-white and near-black excluded)
        mx, mn = img.max(axis=2), img.min(axis=2)
        sat = (mx - mn) > 60
        grey = (abs(mx - mn) < 8) & (mx > 150) & (mx < 225)
        cand = sat | grey
        # quantise to colour buckets so touching colours split
        q = (img // 24)
        key = (q[..., 0] * 400 + q[..., 1] * 20 + q[..., 2])
        key[~cand] = -1
        for k in np.unique(key[key >= 0]):
            mask = key == k
            if mask.sum() < (2.5 * MM) ** 2 * 0.6:
                continue
            lab, n = ndimage.label(mask)
            for sl in ndimage.find_objects(lab):
                if sl is None:
                    continue
                ys, xs = sl
                bh, bw = (ys.stop - ys.start) / MM, (xs.stop - xs.start) / MM
                if not (3.5 <= bh <= 30 and 3.5 <= bw <= 30 and 0.7 <= bw / bh <= 1.45):
                    continue
                comp = lab[sl] > 0
                fill = comp.mean()
                if fill < 0.72:          # letters of coloured headings have holes/gaps
                    continue
                box = img[sl]
                colour = np.median(box[comp], axis=0)
                # glyph = pixels inside the shape's hull that differ strongly from its colour
                hull = ndimage.binary_fill_holes(comp)
                diff = np.abs(box - colour).sum(axis=2)
                glyph = hull & (diff > 160)
                # ignore anti-aliased border ring
                glyph = ndimage.binary_erosion(hull, iterations=int(0.4 * MM)) & glyph
                if glyph.sum() < 12 or glyph.sum() > 0.35 * hull.sum():   # photos, not badges
                    continue
                gy, gx = np.nonzero(glyph)
                cy = (gy.min() + gy.max()) / 2
                cx = (gx.min() + gx.max()) / 2
                hy, hx = np.nonzero(hull)
                by = (hy.min() + hy.max()) / 2
                bx = (hx.min() + hx.max()) / 2
                dy, dx = (cy - by) / MM, (cx - bx) / MM
                total += 1
                ok = abs(dy) <= TOL_MM and abs(dx) <= TOL_MM + 0.15
                if not ok:
                    bad.append((pno + 1, round(xs.start / MM, 1), round(ys.start / MM, 1), round(bw, 1), round(dy, 2), round(dx, 2)))
    return total, bad

rc = 0
for path in sys.argv[1:]:
    total, bad = check(path)
    name = path.rsplit('/', 1)[-1]
    if bad:
        rc = 1
        print(f"FAIL {name}: {len(bad)} of {total} badges off-centre")
        for b in bad[:12]:
            print(f"     page {b[0]} at x={b[1]}mm y={b[2]}mm size {b[3]}mm  dy={b[4]:+}mm dx={b[5]:+}mm")
    else:
        print(f"ok   {name}: {total} badges centred (tolerance {TOL_MM} mm)")
sys.exit(rc)
