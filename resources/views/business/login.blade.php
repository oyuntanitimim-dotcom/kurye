<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>İşletme Giriş</title>
    @include('partials.theme-fonts')
    <style>
        @include('partials.theme-css-unified')
    </style>
</head>
<body class="auth-page">
    <div class="auth-card auth-card--wide">
        <h1>İşletme Giriş</h1>
        <p>Şirket kullanıcı adı ve işletme bilgileri ile giriş yapın.</p>
        <form method="post" action="{{ route('business.login.store') }}">
            @csrf
            <div>
                <label>Şirket kullanıcı adı</label>
                <input name="company_username" value="{{ old('company_username', request('company_username')) }}" required autocomplete="username">
            </div>
            <div>
                <label>İşletme kullanıcı adı</label>
                <input name="username" value="{{ old('username', request('username')) }}" required>
            </div>
            <div>
                <label>İşletme şifresi</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary">Giriş Yap</button>
        </form>
        @if($errors->any())
            <p class="auth-error">{{ $errors->first() }}</p>
        @endif
    </div>
</body>
</html>
