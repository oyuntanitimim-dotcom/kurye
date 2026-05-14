<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Kapatılıyor</title>
    <meta name="robots" content="noindex">
</head>
<body class="m-0 p-3 text-sm text-slate-600">
<p>Listeye dönülüyor… <a class="text-slate-900 underline" href="{{ $to }}">Tıklayın</a></p>
<script>window.top.location = @json($to);</script>
</body>
</html>
