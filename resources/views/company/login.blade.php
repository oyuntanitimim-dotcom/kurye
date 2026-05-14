<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Şirket Girişi</title>
    @include('partials.theme-fonts')
    <style>
        @include('partials.theme-css-unified')
    </style>
</head>
<body class="auth-page">
<div class="auth-card">
    <h1>Şirket Girişi</h1>
    <p>Şirket yönetim paneline erişim.</p>
    <form method="post" action="{{ route('company.login.store') }}">
        @csrf
        <label>Kullanıcı Adı</label>
        <input name="username" value="{{ old('username') }}" required autocomplete="username">
        <label>Şifre</label>
        <input name="password" type="password" required autocomplete="current-password">
        <button type="submit" class="btn btn-primary">Giriş Yap</button>
    </form>
    @if($errors->any())
        <p class="auth-error">{{ $errors->first() }}</p>
    @endif
</div>
</body>
</html>
