<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Şirket Yönetimi</title>
    <style>
        body{margin:0;background:#f5f7fb;font-family:"Segoe UI",Tahoma,Arial,sans-serif;color:#1f2937}
        .wrap{max-width:1100px;margin:24px auto;padding:0 16px}
        .card{background:#fff;border:1px solid #e6ebf2;border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(17,24,39,.04)}
        h1{margin:0 0 8px}p{margin:0 0 12px;color:#6b7280}
        .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
        .mini{background:#fff;border:1px solid #e6ebf2;border-radius:10px;padding:12px}
        .mini b{display:block;font-size:18px}
        form{margin-top:12px}
        button{border:1px solid #e6ebf2;background:#fff;border-radius:8px;padding:8px 12px;cursor:pointer}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Şirket Yönetimi</h1>
        <p>Hoş geldiniz, {{ $company?->company_name ?? session('company_name') }}.</p>
        <div class="grid">
            <div class="mini"><span>Şirket ID</span><b>{{ $company?->id ?? '-' }}</b></div>
            <div class="mini"><span>Durum</span><b>{{ $company?->status ?? '-' }}</b></div>
            <div class="mini"><span>Fiyat/Sipariş</span><b>{{ $company?->price_per_order ?? '0.00' }}</b></div>
        </div>
        <form method="post" action="{{ route('company.logout') }}">
            @csrf
            <button type="submit">Çıkış</button>
        </form>
    </div>
</div>
</body>
</html>
