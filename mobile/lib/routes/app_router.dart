import 'package:go_router/go_router.dart';
import 'package:kurye_mobile/core/auth/auth_store.dart';
import 'package:kurye_mobile/core/http/api_client.dart';
import 'package:kurye_mobile/screens/login_screen.dart';
import 'package:kurye_mobile/screens/shell_screen.dart';

GoRouter buildRouter({required AuthStore authStore, required ApiClient api}) {
  final auth = authStore;

  return GoRouter(
    initialLocation: '/login',
    refreshListenable: auth,
    redirect: (context, state) {
      final hasToken = auth.token != null && auth.token!.isNotEmpty;
      if (!hasToken && state.matchedLocation != '/login') {
        return '/login';
      }
      if (hasToken && state.matchedLocation == '/login') {
        return '/app';
      }
      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => LoginScreen(authStore: auth, api: api),
      ),
      GoRoute(
        path: '/app',
        builder: (context, state) => const ShellScreen(),
      ),
    ],
  );
}

