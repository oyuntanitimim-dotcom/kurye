import 'package:flutter/material.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/auth/auth_store.dart';
import 'package:kurye_mobile/features/courier/courier_earnings_screen.dart';
import 'package:kurye_mobile/features/courier/courier_home_screen.dart';
import 'package:kurye_mobile/features/firm_admin/operations_api.dart';
import 'package:kurye_mobile/features/firm_admin/operations_screen.dart';
import 'package:kurye_mobile/features/firm_admin/firm_reports_screen.dart';
import 'package:kurye_mobile/features/firm_admin/firm_couriers_screen.dart';
import 'package:kurye_mobile/features/firm_admin/firm_restaurants_screen.dart';
import 'package:kurye_mobile/features/firm_admin/firm_finance_screen.dart';
import 'package:kurye_mobile/features/restaurant/restaurant_orders_api.dart';
import 'package:kurye_mobile/features/restaurant/restaurant_orders_screen.dart';
import 'package:kurye_mobile/screens/map_screen.dart';
import 'package:kurye_mobile/screens/profile_screen.dart';

class ShellScreen extends StatefulWidget {
  const ShellScreen({super.key});

  @override
  State<ShellScreen> createState() => _ShellScreenState();
}

class _ShellScreenState extends State<ShellScreen> {
  @override
  Widget build(BuildContext context) {
    final scope = AppScope.of(context);

    return AnimatedBuilder(
      animation: Listenable.merge([scope.auth, scope.shellNav]),
      builder: (context, _) {
        final me = scope.auth.me;
        final role = me?.role ?? AppRole.unknown;

        final tabs = switch (role) {
          AppRole.courier => _courierTabs(
              scope,
              (i) => scope.shellNav.setTab(i),
            ),
          AppRole.restaurant => _restaurantTabs(scope),
          AppRole.firmAdmin => _firmAdminTabs(scope),
          _ => _unknownTabs(scope),
        };

        final safeIndex = scope.shellNav.tab.clamp(0, tabs.length - 1);
        if (safeIndex != scope.shellNav.tab) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (!mounted) return;
            scope.shellNav.setTab(safeIndex);
          });
        }

        final theme = Theme.of(context);
        return Scaffold(
          body: IndexedStack(
            index: safeIndex,
            children: tabs.map((t) => t.widget).toList(),
          ),
          bottomNavigationBar: tabs.length <= 1
              ? null
              : (role == AppRole.firmAdmin && tabs.length > 5)
                  ? _ScrollableBottomNav(
                      selectedIndex: safeIndex,
                      tabs: tabs,
                      onTap: (i) => scope.shellNav.setTab(i),
                    )
                  : SafeArea(
                      top: false,
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(18),
                          child: Theme(
                            data: theme.copyWith(
                              navigationBarTheme: theme.navigationBarTheme.copyWith(
                                height: 62,
                                indicatorColor: theme.colorScheme.primary.withValues(alpha: 0.18),
                                labelTextStyle: WidgetStateTextStyle.resolveWith((states) {
                                  final w800 = (theme.textTheme.labelSmall ?? const TextStyle()).copyWith(
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: 0.1,
                                  );
                                  if (states.contains(WidgetState.selected)) {
                                    return w800.copyWith(color: theme.colorScheme.primary);
                                  }
                                  return w800.copyWith(
                                    color: theme.textTheme.bodySmall?.color ?? theme.colorScheme.onSurfaceVariant,
                                  );
                                }),
                                iconTheme: WidgetStateProperty.resolveWith((states) {
                                  final base = theme.iconTheme.copyWith(size: 20);
                                  if (states.contains(WidgetState.selected)) {
                                    return base.copyWith(color: theme.colorScheme.primary);
                                  }
                                  return base.copyWith(color: theme.textTheme.bodySmall?.color);
                                }),
                              ),
                            ),
                            child: NavigationBar(
                              labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
                              selectedIndex: safeIndex,
                              onDestinationSelected: (i) => scope.shellNav.setTab(i),
                              destinations: tabs
                                  .map(
                                    (t) => NavigationDestination(
                                      icon: Icon(t.icon),
                                      selectedIcon: Icon(t.selectedIcon ?? t.icon),
                                      label: t.label,
                                    ),
                                  )
                                  .toList(),
                            ),
                          ),
                        ),
                      ),
                    ),
        );
      },
    );
  }
}

class _TabDef {
  const _TabDef({
    required this.label,
    required this.icon,
    this.selectedIcon,
    required this.widget,
  });
  final String label;
  final IconData icon;
  /// Daha dolu/aktif ikon; seçilince yeşil vurgu ile referanstaki gibi görünür.
  final IconData? selectedIcon;
  final Widget widget;
}

List<_TabDef> _courierTabs(AppScope scope, void Function(int index) goTab) {
  return [
    _TabDef(
      label: 'Ana Sayfa',
      icon: Icons.home_outlined,
      selectedIcon: Icons.home,
      widget: CourierHomeScreen(onJumpToTab: goTab),
    ),
    const _TabDef(
      label: 'Kazanç',
      icon: Icons.savings_outlined,
      selectedIcon: Icons.savings,
      widget: CourierEarningsScreen(),
    ),
    const _TabDef(
      label: 'Profil',
      icon: Icons.person_outline,
      selectedIcon: Icons.person,
      widget: ProfileScreen(),
    ),
  ];
}

List<_TabDef> _firmAdminTabs(AppScope scope) {
  final opsApi = OperationsApi(scope.api);
  return [
    _TabDef(
      label: 'Operasyon',
      icon: Icons.dashboard_outlined,
      selectedIcon: Icons.dashboard,
      widget: OperationsScreen(api: opsApi),
    ),
    _TabDef(
      label: 'Harita',
      icon: Icons.map_outlined,
      selectedIcon: Icons.map,
      widget: MapScreen(operationsApi: opsApi),
    ),
    const _TabDef(
      label: 'Raporlar',
      icon: Icons.bar_chart_outlined,
      selectedIcon: Icons.bar_chart,
      widget: FirmReportsScreen(),
    ),
    const _TabDef(
      label: 'Kuryeler',
      icon: Icons.delivery_dining_outlined,
      selectedIcon: Icons.delivery_dining,
      widget: FirmCouriersScreen(),
    ),
    const _TabDef(
      label: 'Restoran',
      icon: Icons.restaurant_outlined,
      selectedIcon: Icons.restaurant,
      widget: FirmRestaurantsScreen(),
    ),
    const _TabDef(
      label: 'Finans',
      icon: Icons.payments_outlined,
      selectedIcon: Icons.payments,
      widget: FirmFinanceScreen(),
    ),
    const _TabDef(
      label: 'Profil',
      icon: Icons.person_outline,
      selectedIcon: Icons.person,
      widget: ProfileScreen(),
    ),
  ];
}

List<_TabDef> _restaurantTabs(AppScope scope) {
  final api = RestaurantOrdersApi(scope.api);
  return [
    _TabDef(label: 'Siparişler', icon: Icons.receipt_long, widget: RestaurantOrdersScreen(api: api)),
    const _TabDef(label: 'Harita', icon: Icons.map, widget: MapScreen()),
    const _TabDef(label: 'Profil', icon: Icons.person_outline, widget: ProfileScreen()),
  ];
}

List<_TabDef> _unknownTabs(AppScope scope) {
  final isBootLoading = scope.auth.token != null && scope.auth.me == null;
  return [
    _TabDef(
      label: 'Profil',
      icon: Icons.person,
      widget: Scaffold(
        appBar: AppBar(title: const Text('Profil')),
        body: isBootLoading
            ? const Center(child: CircularProgressIndicator())
            : Padding(
                padding: const EdgeInsets.all(16),
                child: Text('Rol bulunamadı. /api/v1/me kontrol edin. Kullanıcı: ${scope.auth.me?.name ?? '—'}'),
              ),
      ),
    ),
  ];
}

class _ScrollableBottomNav extends StatelessWidget {
  const _ScrollableBottomNav({
    required this.selectedIndex,
    required this.tabs,
    required this.onTap,
  });

  final int selectedIndex;
  final List<_TabDef> tabs;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(18),
          child: Material(
            color: isDark ? theme.colorScheme.surface.withValues(alpha: 0.88) : theme.colorScheme.surface,
            child: SizedBox(
              height: 64,
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 6),
                child: Row(
                  children: [
                    for (var i = 0; i < tabs.length; i++)
                      _NavChip(
                        label: tabs[i].label,
                        icon: tabs[i].icon,
                        selectedIcon: tabs[i].selectedIcon,
                        selected: i == selectedIndex,
                        onTap: () => onTap(i),
                      ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _NavChip extends StatelessWidget {
  const _NavChip({
    required this.label,
    required this.icon,
    required this.selected,
    required this.onTap,
    this.selectedIcon,
  });

  final String label;
  final IconData icon;
  final IconData? selectedIcon;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final fg = selected ? theme.colorScheme.primary : (theme.textTheme.bodySmall?.color ?? theme.colorScheme.onSurfaceVariant);
    final bg = selected ? theme.colorScheme.primary.withValues(alpha: 0.14) : Colors.transparent;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          curve: Curves.easeOut,
          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
          decoration: BoxDecoration(
            color: bg,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: selected ? theme.colorScheme.primary.withValues(alpha: 0.22) : theme.dividerColor.withValues(alpha: 0.55),
            ),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(selected ? (selectedIcon ?? icon) : icon, size: 18, color: fg),
              const SizedBox(width: 6),
              Text(
                label,
                style: (theme.textTheme.labelSmall ?? const TextStyle()).copyWith(
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.1,
                  color: fg,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

