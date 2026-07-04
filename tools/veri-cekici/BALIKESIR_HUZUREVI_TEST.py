import json
import sys

try:
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
except Exception:
    pass
import threading
from datetime import datetime
from pathlib import Path

import requests

from google_maps_scraper import scrape_google_maps, export_to_excel

API_URL = None
API_KEY = ""
CITY = "Balıkesir"
CARE_DOMAIN = "elderly"
CARE_CATEGORY = "huzurevi"
QUERY = "huzurevi Balıkesir"
MAX_RESULTS = 50

DISTRICTS = [
    "Altıeylül", "Karesi", "Ayvalık", "Balya", "Bandırma", "Bigadiç", "Burhaniye",
    "Dursunbey", "Edremit", "Erdek", "Gömeç", "Gönen", "Havran", "İvrindi",
    "Kepsut", "Manyas", "Marmara", "Savaştepe", "Sındırgı", "Susurluk"
]

def normalize(text):
    table = str.maketrans("ÇĞİÖŞÜçğıöşü", "CGIOSUcgiosu")
    return str(text or "").translate(table).lower()

def district_from_address(address):
    hay = normalize(address)
    for district in DISTRICTS:
        if normalize(district) in hay:
            return district
    return "Balıkesir Merkez"

def log(message):
    print(message, flush=True)

def main():
    stop = threading.Event()
    rows = scrape_google_maps(QUERY, MAX_RESULTS, log, stop, find_email=False)
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    out = Path(__file__).with_name(f"balikesir_huzurevi_test_{stamp}.xlsx")
    export_to_excel(rows, str(out))
    print(f"Excel kaydedildi: {out}", flush=True)

    items = []
    for row in rows:
        address = row.get("Adres", "")
        items.append({
            "name": row.get("??yeri Ad?") or row.get("Isyeri Adi") or "",
            "category": row.get("Kategori", ""),
            "address": address,
            "district": district_from_address(address),
            "phone": row.get("Telefon", ""),
            "email": row.get("E-posta", ""),
            "rating": row.get("Puan", ""),
        })
    payload = {
        "source_site": "shared",
        "care_domain": CARE_DOMAIN,
        "care_category": CARE_CATEGORY,
        "city": CITY,
        "district": "Balıkesir Merkez",
        "items": items,
    }
    if not API_URL:
        print(json.dumps(payload, ensure_ascii=False, indent=2), flush=True)
        print("Canli API gonderimi kapali; Excel ve JSON lokal olarak uretildi.", flush=True)
        return

    response = requests.post(
        API_URL,
        headers={"X-Local-Scraper-Key": API_KEY, "Content-Type": "application/json"},
        data=json.dumps(payload, ensure_ascii=False).encode("utf-8"),
        timeout=180,
    )
    print(response.text, flush=True)
    response.raise_for_status()

if __name__ == "__main__":
    main()

