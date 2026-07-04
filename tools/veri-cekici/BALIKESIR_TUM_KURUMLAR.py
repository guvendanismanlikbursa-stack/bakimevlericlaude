import json
import re
import sys
import time
from datetime import datetime
from pathlib import Path

import openpyxl
import requests
from playwright.sync_api import sync_playwright

try:
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
except Exception:
    pass

API_URL = "https://api.bakimevibul.com/local-scraper-import.php"
API_KEY = "local_scraper_20260628_7b91c4"
CITY = "Balıkesir"
SOURCE_SITE = "shared"
MAX_PER_QUERY = 1000

DISTRICTS = [
    "Altıeylül", "Karesi", "Ayvalık", "Balya", "Bandırma", "Bigadiç", "Burhaniye",
    "Dursunbey", "Edremit", "Erdek", "Gömeç", "Gönen", "Havran", "İvrindi",
    "Kepsut", "Manyas", "Marmara", "Savaştepe", "Sındırgı", "Susurluk"
]

SECTIONS = {
    "elderly": [
        ("huzurevi", ["huzurevi", "özel huzurevi"]),
        ("yaşlı bakım evi", ["yaşlı bakım evi", "yaşlı bakımevi"]),
        ("yaşlı bakım merkezi", ["yaşlı bakım merkezi", "yaşlı yaşam merkezi"]),
        ("engelli bakım merkezi", ["engelli bakım merkezi", "bakım merkezi"]),
    ],
    "child": [
        ("anaokulu", ["anaokulu", "özel anaokulu"]),
        ("kreş", ["kreş", "özel kreş"]),
        ("gündüz bakımevi", ["gündüz bakımevi", "çocuk gündüz bakımevi"]),
        ("çocuk bakım merkezi", ["çocuk bakım merkezi", "çocuk kulübü"]),
    ],
    "rehab": [
        ("özel eğitim ve rehabilitasyon merkezi", ["özel eğitim ve rehabilitasyon merkezi", "özel eğitim merkezi"]),
        ("rehabilitasyon merkezi", ["rehabilitasyon merkezi"]),
        ("fizik tedavi ve rehabilitasyon merkezi", ["fizik tedavi ve rehabilitasyon merkezi", "fizik tedavi merkezi"]),
        ("dil ve konuşma terapisi", ["dil ve konuşma terapisi", "ergoterapi"]),
    ],
}

def tr_norm(value):
    table = str.maketrans("ÇĞİÖŞÜçğıöşü", "CGIOSUcgiosu")
    return str(value or "").translate(table).lower()

def clean_phone(value):
    value = re.sub(r"^(Telefon|Phone):\s*", "", str(value or "")).strip()
    return value

def district_from_text(text, default="Balıkesir Merkez"):
    hay = tr_norm(text)
    for district in DISTRICTS:
        if tr_norm(district) in hay:
            return district
    return default

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

def scrape_query(page, query):
    print(f"ARAMA: {query}", flush=True)
    url = "https://www.google.com/maps/search/" + requests.utils.quote(query)
    page.goto(url, wait_until="domcontentloaded", timeout=45000)
    page.wait_for_timeout(4500)
    for label in ["Kabul", "Accept", "Tümünü kabul et"]:
        try:
            btn = page.get_by_role("button", name=re.compile(label, re.I)).first
            if btn.count():
                btn.click(timeout=1000)
                page.wait_for_timeout(1000)
                break
        except Exception:
            pass
    last = -1
    stable = 0
    for _ in range(80):
        count = page.locator('a.hfpxzc, a[href*="/maps/place"]').count()
        if count >= MAX_PER_QUERY:
            break
        if count == last:
            stable += 1
            if stable >= 6:
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
        page.wait_for_timeout(900)
    hrefs = []
    try:
        hrefs = page.eval_on_selector_all('a.hfpxzc, a[href*="/maps/place"]', "els => [...new Set(els.map(a => a.href).filter(Boolean))]")
    except Exception:
        hrefs = []
    hrefs = hrefs[:MAX_PER_QUERY]
    print(f"  bulunan link: {len(hrefs)}", flush=True)
    rows = []
    for i, href in enumerate(hrefs, 1):
        try:
            page.goto(href, wait_until="domcontentloaded", timeout=30000)
            page.wait_for_timeout(1500)
            name = text_or_empty(page, ['h1.DUwDvf', 'h1'])
            if not name:
                continue
            address = attr_or_text(page, ['button[data-item-id^="address"]', 'button[data-tooltip*="Adres"]', '[data-item-id*="address"]'])
            address = re.sub(r"^(Adres|Address):\s*", "", address).strip()
            phone = clean_phone(attr_or_text(page, ['button[data-item-id^="phone"]', 'button[data-tooltip*="Telefon"]', '[data-item-id*="phone"]']))
            rating = text_or_empty(page, ['div.F7nice span[aria-hidden="true"]', 'span.ceNzKf'])
            category = text_or_empty(page, ['button.DkEaL', '[jsaction*="category"]'])
            rows.append({"name": name, "category": category, "address": address, "district": district_from_text(address), "phone": phone, "email": "", "rating": rating})
            print(f"  {i}/{len(hrefs)} {name} | {phone or '-'} | {rating or '-'}", flush=True)
        except Exception as exc:
            print(f"  hata link {i}: {exc}", flush=True)
    return rows

def dedupe(rows):
    seen = set()
    out = []
    for row in rows:
        key = (tr_norm(row.get('name')), tr_norm(row.get('phone')), tr_norm(row.get('district')))
        if key in seen:
            continue
        seen.add(key)
        out.append(row)
    return out

def save_excel(all_rows):
    path = Path(__file__).with_name(f"balikesir_tum_kurumlar_{datetime.now().strftime('%Y%m%d_%H%M%S')}.xlsx")
    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = "Aktarılan Veriler"
    headers = ["Bölüm", "Kurum Türü", "Kurum Adı", "Kategori", "Adres", "İlçe", "Telefon", "Puan"]
    ws.append(headers)
    for domain, cat, row in all_rows:
        ws.append([domain, cat, row.get('name'), row.get('category'), row.get('address'), row.get('district'), row.get('phone'), row.get('rating')])
    wb.save(path)
    print(f"Rapor Excel: {path}", flush=True)

def post_category(care_domain, care_category, rows):
    payload = {
        "source_site": SOURCE_SITE,
        "care_domain": care_domain,
        "care_category": care_category,
        "city": CITY,
        "district": "Balıkesir Merkez",
        "items": rows,
    }
    r = requests.post(API_URL, headers={"X-Local-Scraper-Key": API_KEY, "Content-Type": "application/json"}, data=json.dumps(payload, ensure_ascii=False).encode('utf-8'), timeout=240)
    print(f"AKTARIM {care_domain}/{care_category}: {r.text}", flush=True)
    r.raise_for_status()
    return json.loads(r.content.decode('utf-8-sig'))

def main():
    all_for_report = []
    totals = {"created": 0, "skipped": 0, "errors": 0}
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36", viewport={"width": 1365, "height": 900})
        page = context.new_page()
        done = {('elderly', 'huzurevi'), ('child', 'anaokulu'), ('child', 'kreş'), ('child', 'kres'), ('rehab', 'özel eğitim ve rehabilitasyon merkezi'), ('rehab', 'ozel-egitim-rehabilitasyon')}
        for domain, categories in SECTIONS.items():
            for care_category, keywords in categories:
                if (domain, care_category) in done:
                    print(f'SKIP tamamlandı: {domain}/{care_category}', flush=True)
                    continue
                collected = []
                for keyword in keywords:
                    collected.extend(scrape_query(page, f'{keyword} {CITY}'))
                main_keyword = keywords[0]
                for district in DISTRICTS:
                    collected.extend(scrape_query(page, f'{main_keyword} {district} {CITY}'))
                rows = dedupe(collected)
                for row in rows:
                    all_for_report.append((domain, care_category, row))
                result = post_category(domain, care_category, rows)
                totals["created"] += int(result.get("created", 0))
                totals["skipped"] += int(result.get("skipped", 0))
                totals["errors"] += len(result.get("errors", []))
        browser.close()
    save_excel(all_for_report)
    print("GENEL TOPLAM", totals, flush=True)

if __name__ == "__main__":
    main()


