import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/ui/app_content.dart';
import 'package:kurye_mobile/core/ui/glass_card.dart';
import 'package:kurye_mobile/features/firm_admin/firm_admin_api.dart';

class FirmCouriersScreen extends StatefulWidget {
  const FirmCouriersScreen({super.key});

  @override
  State<FirmCouriersScreen> createState() => _FirmCouriersScreenState();
}

class _FirmCouriersScreenState extends State<FirmCouriersScreen> {
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

  Future<Map<String, dynamic>> _load() => _api(context).couriers(page: 1);

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  Future<void> _showCourierForm({Map<dynamic, dynamic>? initial}) async {
    final isEdit = initial != null;
    final formKey = GlobalKey<FormState>();
    final initialMap = initial ?? const {};
    final name = TextEditingController(text: (initial?['name'] ?? '').toString());
    final email = TextEditingController(text: (initial?['email'] ?? '').toString());
    final phone = TextEditingController(text: (initial?['phone'] ?? '').toString());
    final vehicle = TextEditingController(text: (initial?['vehicle_type'] ?? '').toString());
    final password = TextEditingController();
    var status = ((initial?['status'] ?? 'active').toString() == 'inactive') ? 'inactive' : 'active';
    var saving = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setSheet) {
            final bottomInset = MediaQuery.of(ctx).viewInsets.bottom;
            Future<void> submit() async {
              if (!formKey.currentState!.validate()) return;
              setSheet(() => saving = true);
              try {
                if (isEdit) {
                  await _api(context).updateCourier(
                    courierId: (initialMap['id'] as num).toInt(),
                    name: name.text.trim(),
                    email: email.text.trim(),
                    password: password.text.trim().isEmpty ? null : password.text.trim(),
                    phone: phone.text.trim().isEmpty ? null : phone.text.trim(),
                    vehicleType: vehicle.text.trim().isEmpty ? null : vehicle.text.trim(),
                    status: status,
                  );
                } else {
                  await _api(context).createCourier(
                    name: name.text.trim(),
                    email: email.text.trim(),
                    password: password.text.trim(),
                    phone: phone.text.trim().isEmpty ? null : phone.text.trim(),
                    vehicleType: vehicle.text.trim().isEmpty ? null : vehicle.text.trim(),
                    status: status,
                  );
                }
                if (!ctx.mounted) return;
                Navigator.pop(ctx);
                await _refresh();
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(isEdit ? 'Kurye güncellendi.' : 'Kurye eklendi.')),
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
              padding: EdgeInsets.fromLTRB(16, 0, 16, 16 + bottomInset),
              child: Form(
                key: formKey,
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(isEdit ? 'Kurye düzenle' : 'Yeni kurye', style: Theme.of(ctx).textTheme.titleMedium),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: name,
                        decoration: const InputDecoration(labelText: 'Ad soyad'),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Ad zorunlu' : null,
                      ),
                      const SizedBox(height: 10),
                      TextFormField(
                        controller: email,
                        decoration: const InputDecoration(labelText: 'Giriş e-posta/kullanıcı adı'),
                        validator: (v) => (v == null || v.trim().length < 2) ? 'Geçerli giriş bilgisi girin' : null,
                      ),
                      const SizedBox(height: 10),
                      TextFormField(
                        controller: password,
                        decoration: InputDecoration(labelText: isEdit ? 'Yeni şifre (opsiyonel)' : 'Şifre'),
                        obscureText: true,
                        validator: (v) {
                          if (!isEdit && (v == null || v.trim().length < 8)) return 'En az 8 karakter';
                          if (isEdit && v != null && v.trim().isNotEmpty && v.trim().length < 8) return 'En az 8 karakter';
                          return null;
                        },
                      ),
                      const SizedBox(height: 10),
                      TextFormField(controller: phone, decoration: const InputDecoration(labelText: 'Telefon')),
                      const SizedBox(height: 10),
                      TextFormField(controller: vehicle, decoration: const InputDecoration(labelText: 'Araç tipi')),
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
          },
        );
      },
    );
  }

  Future<void> _toggleStatus(Map<dynamic, dynamic> m) async {
    final id = (m['id'] as num).toInt();
    final current = (m['status'] ?? 'active').toString();
    final target = current == 'active' ? 'inactive' : 'active';
    try {
      await _api(context).setCourierStatus(courierId: id, status: target);
      await _refresh();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(target == 'active' ? 'Kurye aktif edildi.' : 'Kurye pasif edildi.')),
      );
    } on DioException catch (e) {
      final msg = (e.response?.data is Map && (e.response?.data['message'] != null))
          ? e.response?.data['message'].toString()
          : 'Durum güncellenemedi.';
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg ?? 'Durum güncellenemedi.')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Kuryeler'),
        actions: [
          IconButton(
            onPressed: () => _showCourierForm(),
            icon: const Icon(Icons.person_add_alt_1_outlined),
            tooltip: 'Yeni kurye',
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
              return const Center(child: Text('Kuryeler yüklenemedi.'));
            }

            final d = snap.data ?? const {};
            final items = (d['data'] as List?) ?? const [];
            if (items.isEmpty) {
              return const Center(child: Text('Kurye yok.'));
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
                  final status = (m['status'] ?? '—').toString();
                  final hasLoc = m['lat'] != null && m['lng'] != null;

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
                          child: Icon(Icons.delivery_dining_outlined, color: theme.colorScheme.primary),
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
                              Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: [
                                  _Pill(text: status == 'inactive' ? 'inactive' : 'active'),
                                  _Pill(text: hasLoc ? 'Konum var' : 'Konum yok'),
                                ],
                              ),
                            ],
                          ),
                        ),
                        PopupMenuButton<String>(
                          onSelected: (v) async {
                            if (v == 'edit') {
                              await _showCourierForm(initial: m);
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
        onPressed: () => _showCourierForm(),
        icon: const Icon(Icons.person_add_alt_1),
        label: const Text('Kurye ekle'),
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

