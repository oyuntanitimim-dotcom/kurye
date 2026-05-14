<?php

use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CourierController as AdminCourierController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FirmController as AdminFirmController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\RestaurantController as AdminRestaurantController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\UnifiedAuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Courier\DashboardController as CourierDashboardController;
use App\Http\Controllers\Courier\DeliveryHistoryController as CourierDeliveryHistoryController;
use App\Http\Controllers\Courier\EarningsController as CourierEarningsController;
use App\Http\Controllers\Courier\OrderController as CourierOrderController;
use App\Http\Controllers\Firm\CampaignController as FirmCampaignController;
use App\Http\Controllers\Firm\CourierController as FirmCourierController;
use App\Http\Controllers\Firm\DashboardController as FirmDashboardController;
use App\Http\Controllers\Firm\OperationsController as FirmOperationsController;
use App\Http\Controllers\Firm\OrderController as FirmOrderController;
use App\Http\Controllers\Firm\ReportController as FirmReportController;
use App\Http\Controllers\Firm\RestaurantController as FirmRestaurantController;
use App\Http\Controllers\Firm\SettingsController as FirmSettingsController;
use App\Http\Controllers\Admin\FinanceController as AdminFinanceController;
use App\Http\Controllers\Firm\CouponController as FirmCouponController;
use App\Http\Controllers\Firm\FinanceController as FirmFinanceController;
use App\Http\Controllers\Firm\CourierPayoutController as FirmCourierPayoutController;
use App\Http\Controllers\Firm\CourierLedgerEntryController as FirmCourierLedgerEntryController;
use App\Http\Controllers\Restaurant\CategoryController as RestaurantCategoryController;
use App\Http\Controllers\Restaurant\DashboardController as RestaurantDashboardController;
use App\Http\Controllers\Restaurant\ManualOrderController as RestaurantManualOrderController;
use App\Http\Controllers\Restaurant\OrderController as RestaurantOrderController;
use App\Http\Controllers\Restaurant\ProductController as RestaurantProductController;
use App\Http\Controllers\Restaurant\ReportController as RestaurantReportController;
use App\Http\Controllers\Restaurant\IntegrationController as RestaurantIntegrationController;
use App\Http\Controllers\Restaurant\SettingsController as RestaurantSettingsController;
use App\Http\Controllers\Restaurant\CustomerController as RestaurantCustomerController;
use App\Http\Controllers\Shop\AuthController as ShopAuthController;
use App\Http\Controllers\Admin\Marketing\MarketingDashboardController;
use App\Http\Controllers\Admin\Marketing\MarketingFooterController;
use App\Http\Controllers\Admin\Marketing\MarketingHomeController;
use App\Http\Controllers\Admin\Marketing\MarketingLeadController;
use App\Http\Controllers\Admin\Marketing\MarketingMenuItemController;
use App\Http\Controllers\Admin\Marketing\MarketingPhase2Controller;
use App\Http\Controllers\Admin\Marketing\MarketingSlideController;
use App\Http\Controllers\Public\MarketingContactController;
use App\Http\Controllers\Public\MarketingSiteController;
use App\Http\Controllers\Public\OrderTrackingController;
use App\Http\Controllers\Shop\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/takip/{token}', [OrderTrackingController::class, 'show'])
    ->middleware('throttle:120,1')
    ->name('order.tracking');

Route::get('/giris', [UnifiedAuthController::class, 'showLogin'])->name('login');
Route::post('/giris', [UnifiedAuthController::class, 'login'])->name('login.store');
Route::post('/cikis', [UnifiedAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/bildirimler', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/bildirimler/tumunu-okundu', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/bildirimler/{notification}/okundu', [NotificationController::class, 'markRead'])->name('notifications.markRead');
});

Route::redirect('/admin/login', '/giris');
Route::redirect('/firma/giris', '/giris');
Route::redirect('/restoran/giris', '/giris');
Route::redirect('/kurye/giris', '/giris');
Route::redirect('/magaza/giris', '/giris');

Route::get('/', [MarketingSiteController::class, 'home'])->name('marketing.home');
Route::post('/iletisim', [MarketingContactController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('marketing.contact.store');

Route::permanentRedirect('/restoranlar', '/alisveris/restoranlar');
Route::get('/restoran/{restaurant}', function (\App\Modules\Restaurants\Models\Restaurant $restaurant) {
    return redirect()->to(route('shop.restaurant', $restaurant), 301);
})->whereNumber('restaurant')->name('legacy.shop.restaurant');
Route::permanentRedirect('/sepet', '/alisveris/sepet');
Route::permanentRedirect('/kayit', '/alisveris/kayit');
Route::permanentRedirect('/odeme', '/alisveris/odeme');
Route::permanentRedirect('/siparislerim', '/alisveris/siparislerim');
Route::get('/siparislerim/{order}', function (\App\Modules\Orders\Models\Order $order) {
    return redirect()->to(route('shop.orders.show', $order), 301);
})->whereNumber('order')->name('legacy.shop.order');
Route::permanentRedirect('/profil', '/alisveris/profil');

Route::prefix('admin')->middleware(['auth', 'admin.auth'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('admin.dashboard');

    Route::get('/firmalar', [AdminFirmController::class, 'index'])->name('admin.firms.index');
    Route::get('/firmalar/yeni', [AdminFirmController::class, 'create'])->name('admin.firms.create');
    Route::post('/firmalar', [AdminFirmController::class, 'store'])->name('admin.firms.store');
    Route::get('/firmalar/{firm}', [AdminFirmController::class, 'show'])->name('admin.firms.show');
    Route::get('/firmalar/{firm}/duzenle', [AdminFirmController::class, 'edit'])->name('admin.firms.edit');
    Route::put('/firmalar/{firm}', [AdminFirmController::class, 'update'])->name('admin.firms.update');

    Route::get('/restoranlar', [AdminRestaurantController::class, 'index'])->name('admin.restaurants.index');
    Route::get('/restoranlar/{restaurant}/duzenle', [AdminRestaurantController::class, 'edit'])->name('admin.restaurants.edit');
    Route::put('/restoranlar/{restaurant}', [AdminRestaurantController::class, 'update'])->name('admin.restaurants.update');
    Route::get('/kuryeler', [AdminCourierController::class, 'index'])->name('admin.couriers.index');
    Route::get('/kullanicilar', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/siparisler', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/siparisler/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::get('/kampanyalar', [AdminCampaignController::class, 'index'])->name('admin.campaigns.index');
    Route::get('/kampanyalar/yeni', [AdminCampaignController::class, 'create'])->name('admin.campaigns.create');
    Route::post('/kampanyalar', [AdminCampaignController::class, 'store'])->name('admin.campaigns.store');
    Route::get('/kampanyalar/{campaign}/duzenle', [AdminCampaignController::class, 'edit'])->name('admin.campaigns.edit');
    Route::put('/kampanyalar/{campaign}', [AdminCampaignController::class, 'update'])->name('admin.campaigns.update');
    Route::delete('/kampanyalar/{campaign}', [AdminCampaignController::class, 'destroy'])->name('admin.campaigns.destroy');

    Route::get('/kuponlar', [AdminCouponController::class, 'index'])->name('admin.coupons.index');
    Route::get('/kuponlar/yeni', [AdminCouponController::class, 'create'])->name('admin.coupons.create');
    Route::post('/kuponlar', [AdminCouponController::class, 'store'])->name('admin.coupons.store');
    Route::get('/kuponlar/{coupon}/duzenle', [AdminCouponController::class, 'edit'])->name('admin.coupons.edit');
    Route::put('/kuponlar/{coupon}', [AdminCouponController::class, 'update'])->name('admin.coupons.update');
    Route::delete('/kuponlar/{coupon}', [AdminCouponController::class, 'destroy'])->name('admin.coupons.destroy');
    Route::get('/raporlar', AdminReportController::class)->name('admin.reports.index');
    Route::get('/finans', [AdminFinanceController::class, 'index'])->name('admin.finance.index');
    Route::get('/finans/kurye-sirketleri', [AdminFinanceController::class, 'firms'])->name('admin.finance.firms');
    Route::get('/finans/mutabakat', [AdminFinanceController::class, 'reconciliation'])->name('admin.finance.reconciliation');
    Route::view('/ayarlar', 'admin.settings', ['title' => 'Ayarlar'])->name('admin.settings');

    Route::prefix('marketing')->name('admin.marketing.')->group(function (): void {
        Route::get('/', [MarketingDashboardController::class, 'index'])->name('index');
        Route::get('/ana-sayfa', [MarketingHomeController::class, 'edit'])->name('home.edit');
        Route::put('/ana-sayfa', [MarketingHomeController::class, 'update'])->name('home.update');
        Route::post('/ana-sayfa/yayinla', [MarketingHomeController::class, 'publish'])->name('home.publish');
        Route::get('/menu', [MarketingMenuItemController::class, 'index'])->name('menu.index');
        Route::post('/menu', [MarketingMenuItemController::class, 'store'])->name('menu.store');
        Route::put('/menu/{item}', [MarketingMenuItemController::class, 'update'])->name('menu.update');
        Route::delete('/menu/{item}', [MarketingMenuItemController::class, 'destroy'])->name('menu.destroy');
        Route::get('/slaytlar', [MarketingSlideController::class, 'index'])->name('slides.index');
        Route::get('/slaytlar/yeni', [MarketingSlideController::class, 'create'])->name('slides.create');
        Route::post('/slaytlar', [MarketingSlideController::class, 'store'])->name('slides.store');
        Route::get('/slaytlar/{slide}/duzenle', [MarketingSlideController::class, 'edit'])->name('slides.edit');
        Route::put('/slaytlar/{slide}', [MarketingSlideController::class, 'update'])->name('slides.update');
        Route::delete('/slaytlar/{slide}', [MarketingSlideController::class, 'destroy'])->name('slides.destroy');
        Route::get('/footer', [MarketingFooterController::class, 'edit'])->name('footer.edit');
        Route::put('/footer', [MarketingFooterController::class, 'update'])->name('footer.update');
        Route::post('/footer/sutun', [MarketingFooterController::class, 'storeColumn'])->name('footer.columns.store');
        Route::delete('/footer/sutun/{column}', [MarketingFooterController::class, 'destroyColumn'])->name('footer.columns.destroy');
        Route::get('/faz-2', [MarketingPhase2Controller::class, 'show'])->name('phase2');
        Route::get('/basvurular', [MarketingLeadController::class, 'index'])->name('leads.index');
        Route::get('/basvurular/{lead}', [MarketingLeadController::class, 'show'])->name('leads.show');
    });
});

Route::prefix('firma')->middleware(['auth', 'firm.auth'])->group(function (): void {
    Route::get('/', FirmDashboardController::class)->name('firm.dashboard');
    Route::get('/operasyon', [FirmOperationsController::class, 'index'])->name('firm.operations.index');
    Route::get('/operasyon/ozet', [FirmOperationsController::class, 'snapshot'])
        ->middleware('throttle:240,1')
        ->name('firm.operations.snapshot');
    Route::get('/operasyon/kuryeler/{courier}/yakin', [FirmOperationsController::class, 'nearbyCouriers'])
        ->middleware('throttle:120,1')
        ->name('firm.operations.couriers.nearby');
    Route::get('/siparisler', [FirmOrderController::class, 'index'])->name('firm.orders.index');
    Route::get('/siparisler/poll', [FirmOrderController::class, 'poll'])
        ->middleware('throttle:90,1')
        ->name('firm.orders.poll');
    Route::get('/siparisler/watch-board', [FirmOrderController::class, 'watchBoard'])
        ->middleware('throttle:120,1')
        ->name('firm.orders.watch_board');
    Route::get('/siparisler/{order}', [FirmOrderController::class, 'show'])->name('firm.orders.show');
    Route::post('/siparisler/{order}/kurye', [FirmOrderController::class, 'assignCourier'])->name('firm.orders.assign');
    Route::post('/siparisler/{order}/otomatik-ata', [FirmOrderController::class, 'autoDispatch'])->name('firm.orders.auto_dispatch');
    Route::post('/siparisler/{order}/teslim-edildi', [FirmOrderController::class, 'markDelivered'])->name('firm.orders.mark_delivered');
    Route::post('/siparisler/{order}/iptal', [FirmOrderController::class, 'cancel'])->name('firm.orders.cancel');

    Route::get('/restoranlar', [FirmRestaurantController::class, 'index'])->name('firm.restaurants.index');
    Route::get('/restoranlar/yeni', [FirmRestaurantController::class, 'create'])->name('firm.restaurants.create');
    Route::post('/restoranlar', [FirmRestaurantController::class, 'store'])->name('firm.restaurants.store');
    Route::get('/restoranlar/{restaurant}/duzenle', [FirmRestaurantController::class, 'edit'])->name('firm.restaurants.edit');
    Route::put('/restoranlar/{restaurant}', [FirmRestaurantController::class, 'update'])->name('firm.restaurants.update');

    Route::get('/kuryeler', [FirmCourierController::class, 'index'])->name('firm.couriers.index');
    Route::get('/kuryeler/yeni', [FirmCourierController::class, 'create'])->name('firm.couriers.create');
    Route::post('/kuryeler', [FirmCourierController::class, 'store'])->name('firm.couriers.store');
    Route::get('/kuryeler/{courier}/duzenle', [FirmCourierController::class, 'edit'])->name('firm.couriers.edit');
    Route::put('/kuryeler/{courier}', [FirmCourierController::class, 'update'])->name('firm.couriers.update');

    Route::get('/kampanyalar', [FirmCampaignController::class, 'index'])->name('firm.campaigns.index');
    Route::get('/kampanyalar/yeni', [FirmCampaignController::class, 'create'])->name('firm.campaigns.create');
    Route::post('/kampanyalar', [FirmCampaignController::class, 'store'])->name('firm.campaigns.store');
    Route::get('/kampanyalar/{campaign}/duzenle', [FirmCampaignController::class, 'edit'])->name('firm.campaigns.edit');
    Route::put('/kampanyalar/{campaign}', [FirmCampaignController::class, 'update'])->name('firm.campaigns.update');
    Route::delete('/kampanyalar/{campaign}', [FirmCampaignController::class, 'destroy'])->name('firm.campaigns.destroy');

    Route::get('/kuponlar', [FirmCouponController::class, 'index'])->name('firm.coupons.index');
    Route::get('/kuponlar/yeni', [FirmCouponController::class, 'create'])->name('firm.coupons.create');
    Route::post('/kuponlar', [FirmCouponController::class, 'store'])->name('firm.coupons.store');
    Route::get('/kuponlar/{coupon}/duzenle', [FirmCouponController::class, 'edit'])->name('firm.coupons.edit');
    Route::put('/kuponlar/{coupon}', [FirmCouponController::class, 'update'])->name('firm.coupons.update');
    Route::delete('/kuponlar/{coupon}', [FirmCouponController::class, 'destroy'])->name('firm.coupons.destroy');

    Route::get('/raporlar', FirmReportController::class)->name('firm.reports.index');

    Route::prefix('finans')->group(function (): void {
        Route::get('/genel-durum', [FirmFinanceController::class, 'overview'])->name('firm.finance.overview');
        Route::get('/borc-alacak', [FirmFinanceController::class, 'balances'])->name('firm.finance.balances');
        Route::get('/kurye-tahsilatlari', [FirmFinanceController::class, 'courierCollections'])->name('firm.finance.courier_collections');
        Route::get('/kurye-tahsilatlari/{courier}/detay', [FirmFinanceController::class, 'courierCollectionDetail'])->name('firm.finance.courier_collections.detail');
        Route::get('/kurye-odemeleri/olustur', [FirmCourierPayoutController::class, 'create'])->name('firm.finance.courier_payouts.create');
        Route::get('/kurye-odemeleri/iframe-tamam', [FirmCourierPayoutController::class, 'embedDone'])->name('firm.finance.courier_payouts.embed_done');
        Route::post('/kurye-odemeleri', [FirmCourierPayoutController::class, 'store'])->name('firm.finance.courier_payouts.store');
        Route::get('/kurye-odemeleri', [FirmCourierPayoutController::class, 'index'])->name('firm.finance.courier_payouts.index');
        Route::get('/kurye-odemeleri/{settlement}', [FirmCourierPayoutController::class, 'show'])->name('firm.finance.courier_payouts.show');
        Route::post('/kurye-odemeleri/{settlement}/iptal', [FirmCourierPayoutController::class, 'void'])->name('firm.finance.courier_payouts.void');
        Route::post('/kurye-odemeleri/cari-kalem', [FirmCourierLedgerEntryController::class, 'store'])->name('firm.finance.courier_ledger.store');
        Route::delete('/kurye-odemeleri/cari-kalem/{entry}', [FirmCourierLedgerEntryController::class, 'destroy'])->name('firm.finance.courier_ledger.destroy');
        Route::get('/mutabakat', [FirmFinanceController::class, 'reconciliation'])->name('firm.finance.reconciliation');
    });

    Route::get('/ayarlar', [FirmSettingsController::class, 'edit'])->name('firm.settings.edit');
    Route::put('/ayarlar', [FirmSettingsController::class, 'update'])->name('firm.settings.update');
});

Route::prefix('restoran')->middleware(['auth', 'restaurant.auth'])->group(function (): void {
    Route::get('/', RestaurantDashboardController::class)->name('restaurant.dashboard');
    Route::get('/siparisler', [RestaurantOrderController::class, 'index'])->name('restaurant.orders.index');
    Route::get('/siparisler/poll', [RestaurantOrderController::class, 'poll'])
        ->middleware('throttle:90,1')
        ->name('restaurant.orders.poll');
    Route::get('/siparisler/yeni/manuel', [RestaurantManualOrderController::class, 'create'])->name('restaurant.orders.manual.create');
    Route::post('/siparisler/manuel', [RestaurantManualOrderController::class, 'store'])->name('restaurant.orders.manual.store');
    Route::get('/siparisler/{order}', [RestaurantOrderController::class, 'show'])->name('restaurant.orders.show');
    Route::post('/siparisler/{order}/onayla', [RestaurantOrderController::class, 'accept'])->name('restaurant.orders.accept');
    Route::post('/siparisler/{order}/hazirlaniyor', [RestaurantOrderController::class, 'preparing'])->name('restaurant.orders.preparing');
    Route::post('/siparisler/{order}/hazir', [RestaurantOrderController::class, 'ready'])->name('restaurant.orders.ready');
    Route::post('/siparisler/{order}/kurye-cagir', [RestaurantOrderController::class, 'requestCourier'])->name('restaurant.orders.request_courier');
    Route::post('/siparisler/{order}/iptal', [RestaurantOrderController::class, 'cancel'])->name('restaurant.orders.cancel');
    Route::get('/musteriler', [RestaurantCustomerController::class, 'index'])->name('restaurant.customers.index');
    Route::post('/musteriler', [RestaurantCustomerController::class, 'store'])->name('restaurant.customers.store');
    Route::get('/musteriler/disa-aktar', [RestaurantCustomerController::class, 'export'])->name('restaurant.customers.export');
    Route::post('/musteriler/tumunu-sil', [RestaurantCustomerController::class, 'destroyAll'])->name('restaurant.customers.destroy-all');
    Route::delete('/musteriler/{user}', [RestaurantCustomerController::class, 'destroy'])->name('restaurant.customers.destroy');
    Route::get('/urunler', [RestaurantProductController::class, 'index'])->name('restaurant.products.index');
    Route::get('/urunler/yeni', [RestaurantProductController::class, 'create'])->name('restaurant.products.create');
    Route::post('/urunler', [RestaurantProductController::class, 'store'])->name('restaurant.products.store');
    Route::post('/urunler/toplu-kaydet', [RestaurantProductController::class, 'bulkUpdate'])->name('restaurant.products.bulk-update');
    Route::post('/urunler/tumunu-kopyala', [RestaurantProductController::class, 'copyAll'])->name('restaurant.products.copy-all');
    Route::post('/urunler/tumunu-sil', [RestaurantProductController::class, 'destroyAll'])->name('restaurant.products.destroy-all');
    Route::get('/urunler/{product}/duzenle', [RestaurantProductController::class, 'edit'])->name('restaurant.products.edit');
    Route::put('/urunler/{product}', [RestaurantProductController::class, 'update'])->name('restaurant.products.update');
    Route::delete('/urunler/{product}/resimler/{image}', [RestaurantProductController::class, 'destroyImage'])
        ->name('restaurant.products.images.destroy');
    Route::delete('/urunler/{product}', [RestaurantProductController::class, 'destroy'])->name('restaurant.products.destroy');
    Route::get('/kategoriler', [RestaurantCategoryController::class, 'index'])->name('restaurant.categories.index');
    Route::post('/kategoriler', [RestaurantCategoryController::class, 'store'])->name('restaurant.categories.store');
    Route::get('/kategoriler/disa-aktar', [RestaurantCategoryController::class, 'export'])->name('restaurant.categories.export');
    Route::post('/kategoriler/toplu-kaydet', [RestaurantCategoryController::class, 'bulkUpdate'])->name('restaurant.categories.bulk-update');
    Route::post('/kategoriler/tumunu-sil', [RestaurantCategoryController::class, 'destroyAll'])->name('restaurant.categories.destroy-all');
    Route::delete('/kategoriler/{category}', [RestaurantCategoryController::class, 'destroy'])->name('restaurant.categories.destroy');
    Route::get('/raporlar', RestaurantReportController::class)->name('restaurant.reports.index');
    Route::get('/entegrasyon', [RestaurantIntegrationController::class, 'index'])->name('restaurant.integrations.index');
    Route::put('/entegrasyon/{provider}', [RestaurantIntegrationController::class, 'update'])
        ->where('provider', '[a-z0-9_]+')
        ->name('restaurant.integrations.update');
    Route::post('/entegrasyon/{provider}/token', [RestaurantIntegrationController::class, 'regenerateToken'])
        ->where('provider', '[a-z0-9_]+')
        ->name('restaurant.integrations.regenerateToken');
    Route::post('/entegrasyon/{provider}/urun-eslemesi', [RestaurantIntegrationController::class, 'storeProductMap'])
        ->where('provider', '[a-z0-9_]+')
        ->name('restaurant.integrations.productMaps.store');
    Route::get('/entegrasyon/{provider}/urun-eslemesi/template.csv', [RestaurantIntegrationController::class, 'downloadProductMapTemplate'])
        ->where('provider', '[a-z0-9_]+')
        ->name('restaurant.integrations.productMaps.template');
    Route::post('/entegrasyon/{provider}/urun-eslemesi/import', [RestaurantIntegrationController::class, 'importProductMaps'])
        ->where('provider', '[a-z0-9_]+')
        ->name('restaurant.integrations.productMaps.import');
    Route::delete('/entegrasyon/{provider}/urun-eslemesi/{productMap}', [RestaurantIntegrationController::class, 'destroyProductMap'])
        ->where('provider', '[a-z0-9_]+')
        ->name('restaurant.integrations.productMaps.destroy');

    // Trendyol Go (TGO) meal webhook integration helper actions (provider: trendyol_yemek)
    Route::post('/entegrasyon/trendyol_yemek/tgo/create-integrator', [RestaurantIntegrationController::class, 'tgoCreateIntegrator'])
        ->name('restaurant.integrations.tgo.create_integrator');
    Route::post('/entegrasyon/trendyol_yemek/tgo/refresh-token', [RestaurantIntegrationController::class, 'tgoRefreshToken'])
        ->name('restaurant.integrations.tgo.refresh_token');
    Route::post('/entegrasyon/trendyol_yemek/tgo/add-seller', [RestaurantIntegrationController::class, 'tgoAddSeller'])
        ->name('restaurant.integrations.tgo.add_seller');
    Route::post('/entegrasyon/trendyol_yemek/tgo/enable', [RestaurantIntegrationController::class, 'tgoEnable'])
        ->name('restaurant.integrations.tgo.enable');
    Route::post('/entegrasyon/trendyol_yemek/tgo/test-order', [RestaurantIntegrationController::class, 'tgoTestOrder'])
        ->name('restaurant.integrations.tgo.test_order');
    Route::post('/entegrasyon/trendyol_yemek/tgo/auto-connect', [RestaurantIntegrationController::class, 'tgoAutoConnect'])
        ->name('restaurant.integrations.tgo.auto_connect');
    Route::get('/ayarlar', [RestaurantSettingsController::class, 'edit'])->name('restaurant.settings.edit');
    Route::put('/ayarlar', [RestaurantSettingsController::class, 'update'])->name('restaurant.settings.update');
});

Route::prefix('kurye')->middleware(['auth', 'courier.auth'])->group(function (): void {
    Route::get('/', CourierDashboardController::class)->name('courier.dashboard');
    Route::get('/kazanc', CourierEarningsController::class)->name('courier.earnings');
    Route::post('/siparisler/{order}/kabul', [CourierOrderController::class, 'accept'])->name('courier.orders.accept');
    Route::post('/siparisler/{order}/atama-kabul', [CourierOrderController::class, 'acceptFirmAssignment'])->name('courier.orders.accept_firm_assignment');
    Route::post('/siparisler/{order}/atama-red', [CourierOrderController::class, 'declineFirmAssignment'])->name('courier.orders.decline_firm_assignment');
    Route::post('/siparisler/{order}/aldi', [CourierOrderController::class, 'pickedUp'])->name('courier.orders.picked_up');
    Route::post('/siparisler/{order}/yolda', [CourierOrderController::class, 'onTheWay'])->name('courier.orders.on_the_way');
    Route::post('/siparisler/{order}/teslim', [CourierOrderController::class, 'delivered'])->name('courier.orders.delivered');
    Route::get('/teslimatlar', [CourierDeliveryHistoryController::class, 'index'])->name('courier.deliveries.index');
});

Route::prefix('alisveris')->middleware(['firm.resolve'])->group(function (): void {
    Route::get('/', [ShopController::class, 'home'])->name('shop.home');
    Route::get('/restoranlar', [ShopController::class, 'restaurants'])->name('shop.restaurants');
    Route::get('/restoran/{restaurant}', [ShopController::class, 'restaurant'])->name('shop.restaurant');
    Route::get('/sepet', [ShopController::class, 'cart'])->name('shop.cart');
    Route::post('/sepet/ekle', [ShopController::class, 'addToCart'])->name('shop.cart.add');
    Route::post('/sepet/guncelle', [ShopController::class, 'updateCart'])->name('shop.cart.update');

    Route::get('/kayit', [ShopAuthController::class, 'showRegister'])->name('shop.register');
    Route::post('/kayit', [ShopAuthController::class, 'register'])->name('shop.register.store');

    Route::middleware('auth')->group(function (): void {
        Route::get('/odeme', [ShopController::class, 'checkoutForm'])->name('shop.checkout');
        Route::post('/odeme', [ShopController::class, 'checkout'])->name('shop.checkout.store');
        Route::get('/siparislerim', [ShopController::class, 'orders'])->name('shop.orders');
        Route::get('/siparislerim/{order}', [ShopController::class, 'orderShow'])->name('shop.orders.show');
        Route::post('/siparislerim/{order}/yorum', [ShopController::class, 'storeReview'])->name('shop.orders.review');
        Route::get('/profil', [ShopController::class, 'profile'])->name('shop.profile');
        Route::post('/profil/adres', [ShopController::class, 'storeAddress'])->name('shop.profile.address');
    });
});

// Back-compat: eski `/magaza/*` URL'leri `/alisveris/*` altına taşındı.
Route::any('magaza/{path?}', function (?string $path = null) {
    $suffix = ltrim((string) $path, '/');
    $target = '/alisveris'.($suffix !== '' ? '/'.$suffix : '');

    return redirect()->to($target, 301);
})->where('path', '.*');
