import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:go_router/go_router.dart';
import 'package:kurye_mobile/core/auth/auth_store.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/http/api_client.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.authStore, required this.api});

  final AuthStore authStore;
  final ApiClient api;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;
  String? _error;
  bool _obscure = true;

  String _baseUrl(BuildContext context) => AppScope.of(context).configStore.config.apiBaseUrl;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final login = await widget.api.login(email: _email.text.trim(), password: _password.text);
      final token = (login['token'] ?? login['access_token'] ?? '').toString();
      if (token.isEmpty) {
        throw Exception('Token missing');
      }

      // Token'ı sessizce yaz; /me dönmeden rol UI'ını tetikleme.
      await widget.authStore.setTokenOnly(token);
      final me = await widget.api.me();
      await widget.authStore.setSession(token: token, meJson: me);

      // Pull runtime config from backend (tile URL etc.) to keep mobile site-dependent.
      try {
        final cfg = await widget.api.appConfig();
        final map = cfg['map'] is Map ? (cfg['map'] as Map) : const {};
        final tilesUrl = (map['tiles_url'] ?? '').toString();
        final attrib = (map['tiles_attribution'] ?? '').toString();
        if (mounted && tilesUrl.isNotEmpty) {
          final scope = AppScope.of(context);
          scope.configStore.update(
            scope.configStore.config.copyWith(
              tilesUrlTemplate: tilesUrl,
              tilesAttribution: attrib.isNotEmpty ? attrib : scope.configStore.config.tilesAttribution,
            ),
          );
        }
      } catch (_) {
        // ignore in MVP
      }

      if (!mounted) return;
      context.go('/app');
    } catch (e) {
      setState(() {
        final base = _baseUrl(context);
        if (e is DioException) {
          final type = e.type;
          final isConn = type == DioExceptionType.connectionError ||
              type == DioExceptionType.connectionTimeout ||
              type == DioExceptionType.receiveTimeout ||
              type == DioExceptionType.sendTimeout;
          if (isConn) {
            _error = 'Sunucuya bağlanılamadı.\n$base\n\n'
                'Kontrol: Telefon ve PC aynı Wi‑Fi’de mi? '
                'PC’de servis 0.0.0.0:8000 açık mı? Firewall 8000 izinli mi?';
            return;
          }
          if (e.response?.statusCode == 401) {
            _error = 'E‑posta veya şifre hatalı.';
            return;
          }
        }
        _error = 'Giriş başarısız. Bilgileri kontrol edin.';
      });
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    return Scaffold(
      body: Stack(
        children: [
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    scheme.primary.withValues(alpha: 0.16),
                    scheme.primaryContainer.withValues(alpha: 0.10),
                    scheme.surface,
                  ],
                ),
              ),
            ),
          ),
          SafeArea(
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 440),
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Center(
                        child: Container(
                          width: 68,
                          height: 68,
                          decoration: BoxDecoration(
                            color: scheme.primary,
                            borderRadius: BorderRadius.circular(18),
                            boxShadow: [
                              BoxShadow(
                                color: scheme.primary.withValues(alpha: 0.22),
                                blurRadius: 14,
                                offset: const Offset(0, 8),
                              ),
                            ],
                          ),
                          child: const Icon(Icons.delivery_dining, color: Colors.white, size: 30),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'Kurye',
                        textAlign: TextAlign.center,
                        style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Giriş yap ve devam et',
                        textAlign: TextAlign.center,
                        style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        alignment: WrapAlignment.center,
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          _InfoPill(icon: Icons.security_outlined, text: 'Güvenli giriş'),
                          _InfoPill(icon: Icons.bolt_outlined, text: 'Hızlı erişim'),
                        ],
                      ),
                      const SizedBox(height: 10),
                      Builder(
                        builder: (context) => Text(
                          _baseUrl(context),
                          textAlign: TextAlign.center,
                          style: theme.textTheme.labelSmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(14),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              TextField(
                                controller: _email,
                                decoration: const InputDecoration(
                                  labelText: 'E-posta',
                                  prefixIcon: Icon(Icons.alternate_email),
                                ),
                                keyboardType: TextInputType.emailAddress,
                                autofillHints: const [AutofillHints.username],
                              ),
                              const SizedBox(height: 12),
                              TextField(
                                controller: _password,
                                decoration: InputDecoration(
                                  labelText: 'Şifre',
                                  prefixIcon: const Icon(Icons.lock_outline),
                                  suffixIcon: IconButton(
                                    onPressed: () => setState(() => _obscure = !_obscure),
                                    icon: Icon(_obscure ? Icons.visibility : Icons.visibility_off),
                                  ),
                                ),
                                obscureText: _obscure,
                                autofillHints: const [AutofillHints.password],
                              ),
                              if (_error != null) ...[
                                const SizedBox(height: 10),
                                Container(
                                  padding: const EdgeInsets.all(10),
                                  decoration: BoxDecoration(
                                    color: Colors.red.withValues(alpha: 0.08),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: Colors.red.withValues(alpha: 0.18)),
                                  ),
                                  child: Text(
                                    _error!,
                                    style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.error),
                                  ),
                                ),
                              ],
                              const SizedBox(height: 14),
                              FilledButton(
                                onPressed: _loading ? null : _submit,
                                child: _loading ? const Text('Giriş yapılıyor…') : const Text('Giriş yap'),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Devam ederek kullanım koşullarını kabul etmiş olursun.',
                        textAlign: TextAlign.center,
                        style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  const _InfoPill({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.45),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 15, color: theme.colorScheme.primary),
          const SizedBox(width: 6),
          Text(text, style: theme.textTheme.labelSmall?.copyWith(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}

