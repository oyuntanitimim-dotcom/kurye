<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\Marketing\MarketingFooterColumn;
use App\Models\Marketing\MarketingFooterLink;
use App\Models\Marketing\MarketingMenu;
use App\Models\Marketing\MarketingMenuItem;
use App\Models\Marketing\MarketingPage;
use App\Models\Marketing\MarketingPageVersion;
use App\Models\Marketing\MarketingSite;
use App\Models\Marketing\MarketingSlide;
use Illuminate\Support\Facades\DB;

final class MarketingBootstrap
{
    /** @return array<int, array<string, string>> */
    public static function defaultFeatureStripItems(): array
    {
        return [
            [
                'title' => 'Çok kiracılı yapı',
                'body' => 'Firma, restoran ve şube ayrımıyla verilerinizi izole edin; tek panelden tüm operasyonu yönetin.',
            ],
            [
                'title' => 'Canlı operasyon',
                'body' => 'Sipariş akışı, kurye konumu ve yoğunluk tek ekranda; anında müdahale ve görünür SLA.',
            ],
            [
                'title' => 'Finans ve mutabakat',
                'body' => 'Teslimat gelirleri, kurye ödemeleri ve kanal mutabakatı için şeffaf raporlar ve dışa aktarım.',
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function defaultBlocks(): array
    {
        $home = route('marketing.home');

        return [
            [
                'type' => 'hero_slider',
                'data' => [
                    'slides' => [
                        [
                            'badge' => 'Kurye yönetim yazılımı',
                            'headline' => 'Kurye Yönetimini Tek Panelden Kontrol Edin',
                            'headline_accent' => 'Tek Panelden',
                            'subheadline' => 'Siparişten teslimata tüm süreçleri tek yerden yönetin. Canlı harita, otomatik atama ve detaylı raporlama ile operasyonunuzu hızlandırın.',
                            'cta_label' => 'Hemen Başla',
                            'cta_url' => $home.'#iletisim',
                            'cta_secondary_label' => 'Tanıtımı İzle',
                            'cta_secondary_url' => $home.'#panel-onizleme',
                            'banner_image' => null,
                            'pillars' => [
                                ['label' => 'Otomatik atama', 'icon' => 'bolt'],
                                ['label' => 'Canlı takip', 'icon' => 'map'],
                                ['label' => 'Detaylı raporlama', 'icon' => 'chart'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'rich_text',
                'data' => [
                    'id' => 'hakkimizda',
                    'html' => '<h2>Hakkımızda</h2><p>Kurye şirketleri ve restoranlar için operasyon yönetimini hızlandıran, sahada mobil uygulama ve panelde canlı görünürlük sunan bir platformuz.</p>',
                ],
            ],
            [
                'type' => 'feature_grid',
                'data' => [
                    'title' => 'Neden '.trim((string) config('marketing.brand_name')).'?',
                    'subtitle' => 'Operasyonunuzu güçlendiren özellikler — hepsi tek platformda.',
                    'items' => [
                        ['title' => 'Çok kanallı sipariş', 'body' => 'Yemek platformları ve özel kanallardan gelen siparişleri tek akışta birleştirin.'],
                        ['title' => 'Akıllı kurye ataması', 'body' => 'Yoğunluk, mesafe ve SLA kurallarına göre en uygun kuryeyi saniyeler içinde eşleştirin.'],
                        ['title' => 'Canlı harita', 'body' => 'Kuryelerinizi gerçek zamanlı izleyin; gecikme ve darboğazları erken görün.'],
                        ['title' => 'Finans ve mutabakat', 'body' => 'Teslimat gelirleri, kurye ödemeleri ve kanal mutabakatını şeffaf raporlarla yönetin.'],
                        ['title' => 'Entegrasyonlar', 'body' => 'Popüler yemek platformları ve webhook/API ile mevcut altyapınıza bağlanın.'],
                        ['title' => 'Raporlama', 'body' => 'Yönetim kuruluna hazır özetler; dışa aktarım ve zamanlanmış raporlar.'],
                    ],
                ],
            ],
            [
                'type' => 'process_steps',
                'data' => [
                    'title' => 'Nasıl Çalışır?',
                    'subtitle' => 'Dört adımda operasyonunuzu dijitale taşıyın.',
                    'steps' => [
                        ['number' => '1', 'title' => 'Kayıt & kurulum', 'body' => 'Hesabınızı oluşturun; şube ve kullanıcıları dakikalar içinde tanımlayın.'],
                        ['number' => '2', 'title' => 'Entegrasyon', 'body' => 'Sipariş kanallarınızı bağlayın veya API ile özel akışlarınızı ekleyin.'],
                        ['number' => '3', 'title' => 'Operasyon', 'body' => 'Panelden atamaları yönetin; sahada mobil uygulama ile kuryelerinizi koordine edin.'],
                        ['number' => '4', 'title' => 'Analiz', 'body' => 'Performans ve finans raporlarıyla sürekli iyileştirme yapın.'],
                    ],
                ],
            ],
            [
                'type' => 'screens',
                'data' => [
                    'title' => 'Güçlü Yönetim Paneli',
                    'subtitle' => 'Sipariş, harita ve raporlar — yan yana, tek bakışta.',
                    'items' => [
                        ['title' => 'Sipariş yönetimi', 'caption' => 'Durumlar ve filtreler', 'image' => null],
                        ['title' => 'Canlı harita', 'caption' => 'Konum ve rota', 'image' => null],
                        ['title' => 'Raporlar', 'caption' => 'Grafik ve dağılım', 'image' => null],
                    ],
                ],
            ],
            [
                'type' => 'testimonials',
                'data' => [
                    'title' => 'Müşterilerimiz Ne Diyor?',
                    'items' => [
                        [
                            'quote' => 'Atama sürelerimiz ciddi şekilde kısaldı. Yoğun saatlerde bile panel stabil kaldı.',
                            'author' => 'A. Yılmaz',
                            'role' => 'Operasyon Müdürü',
                            'company' => 'Kurye A.Ş.',
                        ],
                        [
                            'quote' => 'Çok şubeli yapımızda sipariş karmaşası azaldı; yönetim tek ekrandan takip ediyor.',
                            'author' => 'E. Demir',
                            'role' => 'İşletme Yetkilisi',
                            'company' => 'Restoran Grubu',
                        ],
                        [
                            'quote' => 'Finans raporları yönetim kuruluna sunulabilir netlikte; mutabakat süremiz kısaldı.',
                            'author' => 'M. Kaya',
                            'role' => 'Finans',
                            'company' => 'Lojistik Ortağı',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'faq',
                'data' => [
                    'title' => 'S.S.S.',
                    'items' => [
                        ['q' => 'Kurulum ne kadar sürer?', 'a' => 'Temel kurulum aynı gün tamamlanır; entegrasyon sayısına göre değişebilir.'],
                        ['q' => 'Entegrasyonlar nasıl çalışıyor?', 'a' => 'Webhook/API ile sipariş akışları tek panelde birleştirilir.'],
                        ['q' => 'Mobil uygulama offline çalışır mı?', 'a' => 'Bağlantı kesintilerinde kritik aksiyonlar kuyruklanır ve tekrar bağlanınca senkronlanır.'],
                    ],
                ],
            ],
            [
                'type' => 'rich_text',
                'data' => [
                    'id' => 'basvuru',
                    'html' => '<h2>Başvuru</h2><p>Demo ve teklif için başvurunuzu iletin; ekibimiz ihtiyaçlarınıza göre kurulum ve modül planını paylaşsın.</p><p><a href=\"'.$home.'#iletisim\">Başvuru için iletişime geçin</a></p>',
                ],
            ],
            [
                'type' => 'pricing',
                'data' => [
                    'title' => 'Fiyatlandırma',
                    'subtitle' => 'İhtiyacınıza uygun planı seçin; tüm paketlerde temel operasyon araçları dahildir.',
                    'plans' => [
                        [
                            'name' => 'Başlangıç',
                            'price' => '₺999',
                            'period' => 'ay',
                            'description' => 'Tek şehir, küçük ekipler için.',
                            'features' => ['Operasyon paneli', 'Standart raporlar', 'E-posta destek'],
                            'cta_label' => 'Hemen Başla',
                            'cta_url' => $home.'#iletisim',
                            'highlight' => false,
                        ],
                        [
                            'name' => 'Profesyonel',
                            'price' => '₺1.999',
                            'period' => 'ay',
                            'description' => 'Büyüyen filolar ve çok şube.',
                            'features' => ['Tüm entegrasyonlar', 'Gelişmiş raporlama', 'Öncelikli destek'],
                            'cta_label' => 'Hemen Başla',
                            'cta_url' => $home.'#iletisim',
                            'highlight' => true,
                        ],
                        [
                            'name' => 'Kurumsal',
                            'price' => 'Özel',
                            'period' => 'teklif',
                            'description' => 'SLA, özel modül ve yüksek hacim.',
                            'features' => ['Dedicated müşteri yöneticisi', 'Özel geliştirme', 'SLA ve denetim'],
                            'cta_label' => 'Görüşme planla',
                            'cta_url' => $home.'#iletisim',
                            'highlight' => false,
                        ],
                    ],
                ],
            ],
            [
                'type' => 'contact_form',
                'data' => [
                    'title' => 'İletişim',
                    'subtitle' => 'Ücretsiz deneme veya demo için formu doldurun; ekibimiz en kısa sürede dönüş yapar.',
                ],
            ],
            [
                'type' => 'cta',
                'data' => [
                    'title' => 'Hemen Başlayın, Farkı Yaşayın!',
                    'body' => 'Binlerce teslimatı sorunsuz yöneten şirketlere katılın. Ücretsiz deneme ile risk almadan deneyin.',
                    'cta_label' => 'Ücretsiz Dene',
                    'cta_url' => $home.'#iletisim',
                    'cta_secondary_label' => 'Giriş Yap',
                    'cta_secondary_url' => route('login'),
                ],
            ],
            [
                'type' => 'legal_notices',
                'data' => [],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function defaultBlock(string $type): ?array
    {
        foreach (self::defaultBlocks() as $block) {
            if (($block['type'] ?? '') === $type) {
                return $block;
            }
        }

        return null;
    }

    public function ensureSiteExists(): MarketingSite
    {
        $existing = MarketingSite::query()->first();
        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function (): MarketingSite {
            $site = MarketingSite::query()->create([
                'name' => config('marketing.brand_name'),
                'primary_domain' => null,
                'is_published' => true,
                'theme' => null,
            ]);

            $page = MarketingPage::query()->create([
                'marketing_site_id' => $site->id,
                'slug' => 'home',
                'title' => config('marketing.brand_name').' — Tanıtım',
                'meta_description' => 'Kurye yönetimini tek panelden kontrol edin. Canlı harita, atama ve raporlama.',
                'status' => 'published',
                'published_at' => now(),
                'published_version_id' => null,
            ]);

            $version = MarketingPageVersion::query()->create([
                'marketing_page_id' => $page->id,
                'version' => 1,
                'blocks_json' => self::defaultBlocks(),
                'created_by' => null,
            ]);

            $page->update([
                'published_version_id' => $version->id,
            ]);

            $menu = MarketingMenu::query()->create([
                'marketing_site_id' => $site->id,
                'key' => 'header',
            ]);

            $home = route('marketing.home');
            $items = [
                ['label' => 'Anasayfa', 'url' => $home, 'sort_order' => 0],
                ['label' => 'Hakkımızda', 'url' => $home.'#hakkimizda', 'sort_order' => 1],
                ['label' => 'Özellikler', 'url' => $home.'#ozellikler', 'sort_order' => 2],
                ['label' => 'Fiyatlarımız', 'url' => $home.'#fiyatlandirma', 'sort_order' => 3],
                ['label' => 'Referanslar', 'url' => $home.'#referanslar', 'sort_order' => 4],
                ['label' => 'Başvuru', 'url' => $home.'#basvuru', 'sort_order' => 5],
                ['label' => 'S.S.S.', 'url' => $home.'#sss', 'sort_order' => 6],
                ['label' => 'İletişim', 'url' => $home.'#iletisim', 'sort_order' => 7],
                ['label' => 'Panel girişi', 'url' => route('login'), 'sort_order' => 8],
            ];
            foreach ($items as $row) {
                MarketingMenuItem::query()->create([
                    'marketing_menu_id' => $menu->id,
                    'parent_id' => null,
                    'label' => $row['label'],
                    'url' => $row['url'],
                    'open_in_new_tab' => false,
                    'sort_order' => $row['sort_order'],
                ]);
            }

            MarketingSlide::query()->create([
                'marketing_site_id' => $site->id,
                'title' => 'Teslimat operasyonunda dijital zirve',
                'subtitle' => 'Çok kanallı sipariş, canlı harita ve güçlü raporlama — tek çatı altında.',
                'image_path' => null,
                'cta_label' => 'Özellikleri incele',
                'cta_url' => $home.'#ozellikler',
                'sort_order' => 0,
                'active' => true,
            ]);

            $cols = [
                ['heading' => 'Ürün', 'sort_order' => 0, 'links' => [
                    ['label' => 'Özellikler', 'url' => $home.'#ozellikler', 'sort_order' => 0],
                    ['label' => 'Fiyatlandırma', 'url' => $home.'#fiyatlandirma', 'sort_order' => 1],
                    ['label' => 'Panel girişi', 'url' => route('login'), 'sort_order' => 2],
                ]],
                ['heading' => 'Şirket', 'sort_order' => 1, 'links' => [
                    ['label' => 'Blog', 'url' => $home.'#blog', 'sort_order' => 0],
                    ['label' => 'İletişim', 'url' => $home.'#iletisim', 'sort_order' => 1],
                ]],
                ['heading' => 'Destek', 'sort_order' => 2, 'links' => [
                    ['label' => 'Yardım', 'url' => $home.'#iletisim', 'sort_order' => 0],
                    ['label' => 'KVKK', 'url' => $home.'#kvkk', 'sort_order' => 1],
                ]],
                ['heading' => 'Sosyal', 'sort_order' => 3, 'links' => [
                    ['label' => 'LinkedIn', 'url' => '#', 'sort_order' => 0],
                    ['label' => 'Instagram', 'url' => '#', 'sort_order' => 1],
                ]],
            ];
            foreach ($cols as $colDef) {
                $col = MarketingFooterColumn::query()->create([
                    'marketing_site_id' => $site->id,
                    'heading' => $colDef['heading'],
                    'sort_order' => $colDef['sort_order'],
                ]);
                foreach ($colDef['links'] as $linkDef) {
                    MarketingFooterLink::query()->create([
                        'marketing_footer_column_id' => $col->id,
                        'label' => $linkDef['label'],
                        'url' => $linkDef['url'],
                        'open_in_new_tab' => false,
                        'sort_order' => $linkDef['sort_order'],
                    ]);
                }
            }

            return $site->fresh() ?? $site;
        });
    }
}
