<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>İşletme Paneli</title>
    <style>
        :root{--bg:#f3f5fb;--card:#fff;--text:#0f172a;--line:#dbe3ef;--muted:#64748b;--brand:#2563eb}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);font-family:"Segoe UI",Tahoma,Arial,sans-serif;color:var(--text)}
        .wrap{max-width:980px;margin:24px auto;padding:0 12px}
        .card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:18px}
        h1{margin:0 0 14px}
        .muted{color:var(--muted)}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
        .item{border:1px solid var(--line);border-radius:10px;padding:12px;background:#fff}
        .key{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}
        .logout{margin-top:14px}
        button{border:0;border-radius:10px;padding:10px 14px;background:var(--brand);color:#fff;font-weight:600;cursor:pointer}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>İşletme Paneli</h1>
            <p class="muted">Hoş geldiniz, {{ $business->name }} ({{ $company->company_name }})</p>
            <div class="grid">
                <div class="item"><span class="key">İşletme ID</span>{{ $business->id }}</div>
                <div class="item"><span class="key">Telefon</span>{{ $business->phone }}</div>
                <div class="item"><span class="key">E-posta</span>{{ $business->email ?? '-' }}</div>
                <div class="item"><span class="key">Durum</span>{{ $business->status }}</div>
            </div>
            <form class="logout" method="post" action="{{ route('business.logout') }}">
                @csrf
                <button type="submit">Çıkış</button>
            </form>
        </div>
    </div>
</body>
</html>
