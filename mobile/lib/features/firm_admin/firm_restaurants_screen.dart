import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/ui/app_content.dart';
import 'package:kurye_mobile/core/ui/glass_card.dart';
import 'package:kurye_mobile/features/firm_admin/firm_admin_api.dart';

class FirmRestaurantsScreen extends StatefulWidget {
  const FirmRestaurantsScreen({super.key});

  @override
  State<FirmRestaurantsScreen> createState() => _FirmRestaurantsScreenState();
}

class _FirmRestaurantsScreenState extends State<FirmRestaurantsScreen> {
  late Future<Map<String, dynamic>> _future;
  bool _bootstrapped = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_bootstrapped) return;
    _bootstrapped = true;
    _future = _load();
  }

  FirmAdminApi _api(BuildContext context) => FirmAdminApi(AppScope.of(context).api);

  Future<Map<String, dynamic>> _load() => _api(context).restaurants(page: 1);

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  Future<void> _showRestaurantForm({Map<dynamic, dynamic>? initial}) async {
    final isEdit = initial != null;
    final formKey = GlobalKey<FormState>();
    final initialMap = initial ?? const {};
    final name = TextEditingController(text: (initialMap['name'] ?? '').toString());
    final phone = TextEditingController(text: (initialMap['phone'] ?? '').toString());
    final address = TextEditingController(text: (initialMap['address'] ?? '').toString());
    final adminName = TextEditingController();
    final adminEmail = TextEditingController();
    final adminPassword = TextEditingController();
    var status = ((initialMap['status'] ?? 'active').toString() == 'inactive') ? 'inactive' : 'active';
    var businessType = (initialMap['business_type'] ?? 'restaurant').toString();
    var saving = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (ctx) {
        return StatefulBuilder(builder: (ctx, setSheet) {
          final inset = MediaQuery.of(ctx).viewInsets.bottom;
          Future<void> submit() async {
            if (!formKey.currentState!.validate()) return;
            setSheet(() => saving = true);
            try {
              if (isEdit) {
                await _api(context).updateRestaurant(
                  restaurantId: (initialMap['id'] as num).toInt(),
                  name: name.text.trim(),
                  businessType: businessType,
                  status: status,
                  phone: phone.text.trim().isEmpty ? null : phone.text.trim(),
                  address: address.text.trim().isEmpty ? null : address.text.trim(),
                );
              } else {
                await _api(context).createRestaurant(
                  name: name.text.trim(),
                  businessType: businessType,
                  status: status,
                  phone: phone.text.trim().isEmpty ? null : phone.text.trim(),
                  address: address.text.trim().isEmpty ? null : address.text.trim(),
                  adminName: adminName.text.trim(),
                  adminEmail: adminEmail.text.trim(),
                  adminPassword: adminPassword.text.trim(),
                );
              }
              if (!ctx.mounted) return;
              Navigator.pop(ctx);
              await _refresh();
              if (!mounted) return;
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(isEdit ? 'Restoran güncellendi.' : 'Restoran eklendi.')),
              );
            } on DioException catch (e) {
              final data = e.response?.data;
              final String msg = data is Map && data['message'] != null
                  ? data['message'].toString()
                  : 'İşlem başarısız.';
              if (!mounted) return;
              ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
            } finally {
              if (ctx.mounted) setSheet(() => saving = false);
            }
          }

          return Padding(
            padding: EdgeInsets.fromLTRB(16, 0, 16, 16 + inset),
            child: Form(
              key: formKey,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(isEdit ? 'Restoran düzenle' : 'Yeni restoran', style: Theme.of(ctx).textTheme.titleMedium),
                    const SizedBox(height: 10),
                    TextFormField(
                      controller: name,
                      decoration: const InputDecoration(labelText: 'Restoran adı'),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Ad zorunlu' : null,
                    ),
                    const SizedBox(height: 10),
                    InputDecorator(
                      decoration: const InputDecoration(labelText: 'İşletme tipi'),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: businessType,
                          isExpanded: true,
                          items: _businessTypes
                              .map((e) => DropdownMenuItem<String>(value: e.$1, child: Text(e.$2)))
                              .toList(),
                          onChanged: (v) => setSheet(() => businessType = v ?? 'restaurant'),
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    TextFormField(controller: phone, decoration: const InputDecoration(labelText: 'Telefon')),
                    const SizedBox(height: 10),
                    TextFormField(controller: address, decoration: const InputDecoration(labelText: 'Adres')),
                    const SizedBox(height: 10),
                    InputDecorator(
                      decoration: const InputDecoration(labelText: 'Durum'),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: status,
                          isExpanded: true,
                          items: const [
                            DropdownMenuItem(value: 'active', child: Text('Aktif')),
                            DropdownMenuItem(value: 'inactive', child: Text('Pasif')),
                          ],
                          onChanged: (v) => setSheet(() => status = v ?? 'active'),
                        ),
                      ),
                    ),
                    if (!isEdit) ...[
                      const SizedBox(height: 12),
                      const Divider(),
                      const SizedBox(height: 6),
                      TextFormField(
                        controller: adminName,
                        decoration: const InputDecoration(labelText: 'Yönetici adı'),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Yönetici adı zorunlu' : null,
                      ),
                      const SizedBox(height: 10),
                      TextFormField(
                        controller: adminEmail,
                        decoration: const InputDecoration(labelText: 'Yönetici e-posta'),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Yönetici e-posta zorunlu' : null,
                      ),
                      const SizedBox(height: 10),
                      TextFormField(
                        controller: adminPassword,
                        decoration: const InputDecoration(labelText: 'Yönetici şifre'),
                        obscureText: true,
                        validator: (v) => (v == null || v.trim().length < 8) ? 'En az 8 karakter' : null,
                      ),
                    ],
                    const SizedBox(height: 14),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: saving ? null : submit,
                        icon: saving
                            ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                            : Icon(isEdit ? Icons.save_outlined : Icons.add),
                        label: Text(isEdit ? 'Kaydet' : 'Ekle'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        });
      },
    );
  }

  Future<void> _toggleStatus(Map<dynamic, dynamic> m) async {
    final id = (m['id'] as num).toInt();
    final current = (m['status'] ?? 'active').toString();
    final target = current == 'active' ? 'inactive' : 'active';
    try {
      await _api(context).setRestaurantStatus(restaurantId: id, status: target);
      await _refresh();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(target == 'active' ? 'Restoran aktif edildi.' : 'Restoran pasif edildi.')),
      );
    } on DioException catch (e) {
      final data = e.response?.data;
      final String msg = data is Map && data['message'] != null
          ? data['message'].toString()
          : 'Durum güncellenemedi.';
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    }
  }

  static const List<(String, String)> _businessTypes = [
    ('restaurant', 'Restoran'),
    ('fast_food', 'Fast food'),
    ('kebab', 'Kebap / Döner'),
    ('cafe', 'Kafe'),
    ('bakery', 'Fırın / Pastane'),
    ('market', 'Market'),
    ('grocery', 'Bakkal'),
    ('greengrocer', 'Manav'),
    ('pharmacy', 'Eczane'),
    ('florist', 'Çiçekçi'),
    ('pet', 'Pet shop'),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Restoranlar'),
        actions: [
          IconButton(
            onPressed: () => _showRestaurantForm(),
            icon: const Icon(Icons.add_business_outlined),
            tooltip: 'Yeni restoran',
          ),
          IconButton(onPressed: _refresh, icon: const Icon(Icons.refresh)),
        ],
      ),
      body: AppContent(
        child: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return const Center(child: Text('Restoranlar yüklenemedi.'));
            }

            final d = snap.data ?? const {};
            final items = (d['data'] as List?) ?? const [];
            if (items.isEmpty) {
              return const Center(child: Text('Restoran yok.'));
            }

            return RefreshIndicator(
              onRefresh: _refresh,
              child: ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                itemCount: items.length,
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemBuilder: (context, i) {
                  final m = items[i] as Map? ?? const {};
                  final id = (m['id'] ?? '').toString();
                  final name = (m['name'] ?? '—').toString();
                  final products = (m['products_count'] ?? 0).toString();
                  final status = (m['status'] ?? 'active').toString();

                  return GlassCard(
                    padding: const EdgeInsets.all(14),
                    child: Row(
                      children: [
                        Container(
                          height: 44,
                          width: 44,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(14),
                            color: theme.colorScheme.primary.withValues(alpha: 0.10),
                          ),
                          child: Icon(Icons.restaurant_outlined, color: theme.colorScheme.primary),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '#$id  $name',
                                style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900),
                              ),
                              const SizedBox(height: 6),
                              Text('Ürün sayısı: $products', style: theme.textTheme.bodySmall),
                              const SizedBox(height: 6),
                              _Pill(text: status == 'inactive' ? 'inactive' : 'active'),
                            ],
                          ),
                        ),
                        PopupMenuButton<String>(
                          onSelected: (v) async {
                            if (v == 'edit') {
                              await _showRestaurantForm(initial: m);
                            } else if (v == 'toggle') {
                              await _toggleStatus(m);
                            }
                          },
                          itemBuilder: (_) => [
                            const PopupMenuItem(value: 'edit', child: Text('Düzenle')),
                            PopupMenuItem(
                              value: 'toggle',
                              child: Text(status == 'active' ? 'Pasife al' : 'Aktif et'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  );
                },
              ),
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showRestaurantForm(),
        icon: const Icon(Icons.add_business),
        label: const Text('Restoran ekle'),
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(999),
        color: theme.colorScheme.surface.withValues(alpha: 0.65),
        border: Border.all(color: theme.dividerColor.withValues(alpha: 0.6)),
      ),
      child: Text(
        text,
        style: theme.textTheme.labelSmall?.copyWith(fontWeight: FontWeight.w800),
      ),
    );
  }
}

