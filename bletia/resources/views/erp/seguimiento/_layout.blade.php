<!doctype html><html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title','Seguimiento') · {{ config('tienda.marca', config('app.name')) }}</title>
<style>
:root{--brand:#161921;--accent:#0499FC;--line:#e7e7e2}
*{box-sizing:border-box}body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;color:var(--brand)}
.wrap{max-width:560px;margin:0 auto;padding:28px 16px}
.card{background:#fff;border-radius:16px;padding:26px;box-shadow:0 12px 40px rgba(0,0,0,.08)}
.brand{font-size:15px;letter-spacing:.2em;text-transform:uppercase;text-align:center;margin-bottom:18px}
h1{font-size:20px;margin:0 0 6px}.muted{color:#888}
input[type=text]{width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;font-size:15px}
.btn{margin-top:14px;padding:13px 22px;background:var(--accent);color:#fff;border:0;border-radius:999px;font-size:15px;cursor:pointer;width:100%}
.err{background:#fdecec;color:#b3261e;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:12px}
.badge{display:inline-block;background:#eef6ff;color:#0469b4;border-radius:999px;padding:6px 14px;font-size:14px;font-weight:bold}
.tl{list-style:none;margin:22px 0 0;padding:0}
.tl li{position:relative;padding:0 0 20px 26px;border-left:2px solid var(--line)}
.tl li:last-child{border-left-color:transparent}
.tl .dot{position:absolute;left:-8px;top:2px;width:14px;height:14px;border-radius:50%;background:var(--accent)}
.tl .st{font-weight:bold}.tl .dt{font-size:12px;color:#999}
</style></head><body><div class="wrap"><div class="card">
<div class="brand">{{ config('tienda.marca', config('app.name')) }}</div>
@yield('content')
</div></div>    @include('tienda.partials.cookies')
</body></html>
