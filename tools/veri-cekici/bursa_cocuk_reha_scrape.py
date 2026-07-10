import json
import re
import sys
import time
from pathlib import Path

import requests
from playwright.sync_api import sync_playwright

try:
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
except Exception:
    pass

CITY = "Bursa"
MAX_PER_QUERY = 25
OUTPUT_PATH = Path(__file__).with_name("bursa_cocuk_reha_scrape_results.json")

DISTRICTS = [
    "Büyükorhan", "Gemlik", "Gürsu", "Harmancık", "İnegöl", "İznik", "Karacabey",
    "Keles", "Kestel", "Mudanya", "Mustafakemalpaşa", "Nilüfer", "Orhaneli",
    "Orhangazi", "Osmangazi", "Yenişehir", "Yıldırım",
]

# Sadece "cocuk" ve "rehabilitasyon" bolumlerine ait kategoriler (3,4,5,6,7)
CATEGORIES = {
    3: ("çocuk bakım merkezi", "Çocuk Bakım Merkezi"),
    4: ("anaokulu", "Kreş ve Anaokulu"),
    5: ("özel eğitim ve rehabilitasyon merkezi", "Özel Eğitim ve Gelişim Merkezi"),
    6: ("fizik tedavi ve rehabilitasyon merkezi", "Fizik Tedavi ve Rehabilitasyon"),
    7: ("nörolojik rehabilitasyon merkezi", "Nörolojik Rehabilitasyon Merkezi"),
}


def text_or_empty(page, selectors):
    for sel in selectors:
        try:
            loc = page.locator(sel).first
            if loc.count():
                txt = loc.inner_text(timeout=2000).strip()
                if txt:
                    return txt
        except Exception:
            pass
    return ""


def attr_or_text(page, selectors, attr="aria-label"):
    for sel in selectors:
        try:
            loc = page.locator(sel).first
            if loc.count():
                val = loc.get_attribute(attr, timeout=2000) or loc.inner_text(timeout=2000)
                val = str(val or "").strip()
                if val:
                    return val
        except Exception:
            pass
    return ""


def scrape_query(page, query, kategori_id, kategori_adi, ilce):
    print(f"ARANIYOR: {query}", flush=True)
    url = "https://www.google.com/maps/search/" + requests.utils.quote(query)
    try:
        page.goto(url, wait_until="domcontentloaded", timeout=45000)
    except Exception as exc:
        print(f"  gezinme hatasi: {exc}", flush=True)
        return []
    page.wait_for_timeout(3500)
    for label in ["Kabul", "Accept", "Tümünü kabul et"]:
        try:
            btn = page.get_by_role("button", name=re.compile(label, re.I)).first
            if btn.count():
                btn.click(timeout=1000)
                page.wait_for_timeout(800)
                break
        except Exception:
            pass

    last = -1
    stable = 0
    for _ in range(30):
        count = page.locator('a.hfpxzc, a[href*="/maps/place"]').count()
        if count >= MAX_PER_QUERY:
            break
        if count == last:
            stable += 1
            if stable >= 4:
                break
        else:
            stable = 0
            last = count
        try:
            page.evaluate("""
                const feed = document.querySelector('[role="feed"]');
                if (feed) feed.scrollBy(0, 1800); else window.scrollBy(0, 1800);
            """)
        except Exception:
            pass
        page.wait_for_timeout(700)

    try:
        hrefs = page.eval_on_selector_all(
            'a.hfpxzc, a[href*="/maps/place"]',
            "els => [...new Set(els.map(a => a.href).filter(Boolean))]"
        )
    except Exception:
        hrefs = []
    hrefs = hrefs[:MAX_PER_QUERY]
    print(f"  bulunan link: {len(hrefs)}", flush=True)

    rows = []
    for i, href in enumerate(hrefs, 1):
        try:
            page.goto(href, wait_until="domcontentloaded", timeout=30000)
            page.wait_for_timeout(1200)
            name = text_or_empty(page, ['h1.DUwDvf', 'h1'])
            if not name:
                continue
            address = attr_or_text(page, ['button[data-item-id^="address"]', 'button[data-tooltip*="Adres"]'])
            address = re.sub(r"^(Adres|Address):\s*", "", address).strip()
            phone = attr_or_text(page, ['button[data-item-id^="phone"]', 'button[data-tooltip*="Telefon"]'])
            phone = re.sub(r"^(Telefon|Phone):\s*", "", phone).strip()
            rating = text_or_empty(page, ['div.F7nice span[aria-hidden="true"]', 'span.ceNzKf'])
            rows.append({
                "İşyeri Adı": name,
                "Kategori": kategori_adi,
                "Adres": address,
                "Telefon": phone,
                "Web Sitesi": "",
                "E-posta": "",
                "Puan": rating,
                "Enlem": "",
                "Boylam": "",
                "_ilce": ilce,
                "_kategori_id": kategori_id,
                "_kategori_adi": kategori_adi,
            })
            print(f"  {i}/{len(hrefs)} {name} | {phone or '-'}", flush=True)
        except Exception as exc:
            print(f"  hata link {i}: {exc}", flush=True)
    return rows


def main():
    if OUTPUT_PATH.exists():
        with open(OUTPUT_PATH, "r", encoding="utf-8") as f:
            data = json.load(f)
    else:
        data = {}

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True, args=["--no-sandbox"])
        context = browser.new_context(
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            viewport={"width": 1365, "height": 900},
        )
        page = context.new_page()

        for district in DISTRICTS:
            for kategori_id, (keyword, kategori_adi) in CATEGORIES.items():
                key = f"{district}|{kategori_id}"
                if key in data:
                    print(f"ATLA (zaten var): {key}", flush=True)
                    continue
                query = f"{keyword} {district} {CITY}"
                rows = scrape_query(page, query, kategori_id, kategori_adi, district)
                data[key] = rows
                with open(OUTPUT_PATH, "w", encoding="utf-8") as f:
                    json.dump(data, f, ensure_ascii=False, indent=2)
                print(f"KAYDEDILDI: {key} -> {len(rows)} kurum", flush=True)

        browser.close()

    print("TAMAMLANDI", flush=True)
    print(f"Cikti: {OUTPUT_PATH}", flush=True)


if __name__ == "__main__":
    main()
