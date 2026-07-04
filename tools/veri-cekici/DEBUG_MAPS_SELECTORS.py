from playwright.sync_api import sync_playwright
import sys
try:
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
except Exception:
    pass
with sync_playwright() as p:
    b=p.chromium.launch(headless=True)
    page=b.new_page(user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
    page.goto('https://www.google.com/maps/search/huzurevi+Bal%C4%B1kesir', wait_until='domcontentloaded', timeout=45000)
    page.wait_for_timeout(8000)
    title=page.title()
    print('TITLE', title)
    for sel in ['[role="feed"]','[role="feed"] > div > div > a','a[href*="/maps/place"]','div.Nv2PK','div[role="article"]','a.hfpxzc']:
        try:
            print(sel, page.locator(sel).count())
        except Exception as e:
            print(sel, 'ERR', e)
    print(page.url)
    print(page.locator('body').inner_text(timeout=5000)[:2000])
    b.close()
