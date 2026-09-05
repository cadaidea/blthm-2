<!doctype html><html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Confirmación') · {{ config('tienda.marca', config('app.name')) }}</title>
<style>
:root{--brand:#161921;--accent:#0499FC;--line:#e7e7e2}
*{box-sizing:border-box}body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;color:var(--brand)}
.wrap{max-width:460px;margin:0 auto;padding:24px 16px}
.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 12px 40px rgba(0,0,0,.08)}
.brand{font-size:15px;letter-spacing:.2em;text-transform:uppercase;text-align:center;margin-bottom:16px}
h1{font-size:20px;margin:0 0 14px}
label{display:block;font-size:13px;margin:12px 0 4px;color:#444}
input[type=text],input[type=tel]{width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;font-size:15px}
input[type=file]{width:100%;padding:10px 0;font-size:14px}
.btn{width:100%;margin-top:18px;padding:14px;background:var(--accent);color:#fff;border:0;border-radius:999px;font-size:16px;cursor:pointer}
.err{background:#fdecec;color:#b3261e;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:10px}
.hint{color:#888;font-size:12px;margin-top:4px}
.ok-ico{font-size:48px;text-align:center}
.center{text-align:center}
</style></head><body><div class="wrap"><div class="card">
<div class="brand">{{ config('tienda.marca', config('app.name')) }}</div>
@yield('content')
</div></div>    @include('tienda.partials.cookies')
</body></html>
