#!/usr/bin/env python3
"""
Local Real-Time Bursa Efek Indonesia (BEI / Yahoo Finance) Proxy & Web Server
Menjalankan aplikasi DiviTrack di http://localhost:8765 tanpa blokir CORS browser.
"""
import http.server
import socketserver
import urllib.request
import urllib.parse
import json
import os

PORT = 8765
DIRECTORY = os.path.dirname(os.path.abspath(__file__))

class BEIRealtimeHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=DIRECTORY, **kwargs)

    def end_headers(self):
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
        self.send_header('Access-Control-Allow-Private-Network', 'true')
        super().end_headers()

    def do_OPTIONS(self):
        self.send_response(200)
        self.end_headers()

    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        if parsed.path == '/api/quote':
            qs = urllib.parse.parse_qs(parsed.query)
            ticker = (qs.get('ticker', ['BBRI'])[0]).upper().replace('.JK', '')
            query_ticker = f"{ticker}.JK"
            url = f"https://query1.finance.yahoo.com/v8/finance/chart/{urllib.parse.quote(query_ticker)}?interval=1d&range=1d"
            try:
                req = urllib.request.Request(url, headers={
                    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
                })
                with urllib.request.urlopen(req, timeout=6) as resp:
                    raw = json.loads(resp.read().decode('utf-8'))
                    meta = raw['chart']['result'][0]['meta']
                    price = meta.get('regularMarketPrice') or meta.get('chartPreviousClose') or 0
                    prev = meta.get('chartPreviousClose') or meta.get('previousClose') or price
                    chg_pct = ((price - prev) / prev * 100) if prev else 0
                    vol = meta.get('regularMarketVolume') or 0

                    payload = {
                        'ticker': ticker,
                        'price': price,
                        'previousClose': prev,
                        'changePercent': round(chg_pct, 2),
                        'volume': vol,
                        'status': 'ok'
                    }
                    self.send_response(200)
                    self.send_header('Content-Type', 'application/json')
                    self.end_headers()
                    try:
                        self.wfile.write(json.dumps(payload).encode('utf-8'))
                    except (BrokenPipeError, ConnectionResetError):
                        pass
                    return
            except Exception as e:
                try:
                    self.send_response(502)
                    self.send_header('Content-Type', 'application/json')
                    self.end_headers()
                    self.wfile.write(json.dumps({'error': str(e), 'status': 'error'}).encode('utf-8'))
                except (BrokenPipeError, ConnectionResetError):
                    pass
                return

        return super().do_GET()

if __name__ == '__main__':
    socketserver.TCPServer.allow_reuse_address = True
    with socketserver.TCPServer(("", PORT), BEIRealtimeHandler) as httpd:
        print(f"============================================================")
        print(f"🚀 Server Real-Time BEI Aktif di: http://localhost:{PORT}")
        print(f"📈 Buka http://localhost:{PORT} di browser Anda")
        print(f"============================================================")
        httpd.serve_forever()

