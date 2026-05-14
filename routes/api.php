<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\IntegrationWebhookController;
use App\Http\Controllers\Api\V1\CourierLocationApiController;
use App\Http\Controllers\Api\V1\CourierOrderApiController;
use App\Http\Controllers\Api\V1\Courier\CourierStatsController as CourierStatsController;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\OrderApiController;
use App\Http\Controllers\Api\V1\RestaurantApiController;
use App\Http\Controllers\Api\V1\Firm\OperationsSnapshotController as FirmOperationsSnapshotController;
use App\Http\Controllers\Api\V1\Firm\AssignCourierController as FirmAssignCourierController;
use App\Http\Controllers\Api\V1\Firm\OperationSettingsController as FirmOperationSettingsController;
use App\Http\Controllers\Api\V1\Firm\ReportsSummaryController as FirmReportsSummaryController;
use App\Http\Controllers\Api\V1\Firm\FinanceOverviewController as FirmFinanceOverviewController;
use App\Http\Controllers\Api\V1\Firm\FinanceModulesController as FirmFinanceModulesController;
use App\Http\Controllers\Api\V1\Firm\CouriersController as FirmCouriersController;
use App\Http\Controllers\Api\V1\Firm\RestaurantsController as FirmRestaurantsController;
use App\Http\Controllers\Api\V1\Firm\NotificationsController as FirmNotificationsController;
use App\Http\Controllers\Api\V1\Restaurant\OrdersController as RestaurantOrdersController;
use App\Http\Controllers\Api\V1\Restaurant\InsightsController as RestaurantInsightsController;
use App\Http\Controllers\Api\V1\Routing\RouteController as RoutingRouteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/app-config', AppConfigController::class);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::post('/integrations/{provider}/webhook', [IntegrationWebhookController::class, 'handle'])
        ->middleware('throttle:integration-webhook');

    Route::middleware(['auth:sanctum', 'throttle:courier-location'])->group(function (): void {
        Route::post('/courier/location', [CourierLocationApiController::class, 'store']);
    });

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function (): void {
        Route::get('/me', MeController::class);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/restaurants', [RestaurantApiController::class, 'index']);
        Route::get('/restaurants/{restaurant}', [RestaurantApiController::class, 'show']);

        Route::get('/orders', [OrderApiController::class, 'mine']);
        Route::get('/orders/{order}', [OrderApiController::class, 'show']);

        Route::get('/courier/orders/active', [CourierOrderApiController::class, 'active']);
        Route::post('/courier/orders/{order}/accept-assignment', [CourierOrderApiController::class, 'acceptAssignment']);
        Route::post('/courier/orders/{order}/decline-assignment', [CourierOrderApiController::class, 'declineAssignment']);
        Route::patch('/courier/orders/{order}', [CourierOrderApiController::class, 'updateStatus']);
        Route::get('/courier/summary', [CourierStatsController::class, 'summary']);
        Route::get('/courier/earnings', [CourierStatsController::class, 'earnings']);
        Route::get('/courier/payouts/balance', [CourierStatsController::class, 'payoutBalance']);

        // Firm admin (mobile)
        Route::get('/firm/operations/snapshot', FirmOperationsSnapshotController::class);
        Route::post('/firm/orders/{order}/assign-courier', FirmAssignCourierController::class);
        Route::patch('/firm/operations/settings', [FirmOperationSettingsController::class, 'update']);
        Route::get('/firm/reports/summary', FirmReportsSummaryController::class);
        Route::get('/firm/finance/overview', FirmFinanceOverviewController::class);
        Route::get('/firm/finance/balances', [FirmFinanceModulesController::class, 'balances']);
        Route::get('/firm/finance/courier-collections', [FirmFinanceModulesController::class, 'courierCollections']);
        Route::get('/firm/finance/courier-collections/{courier}/detail', [FirmFinanceModulesController::class, 'courierCollectionDetail']);
        Route::get('/firm/finance/courier-payouts', [FirmFinanceModulesController::class, 'courierPayouts']);
        Route::get('/firm/finance/courier-payouts/{settlement}', [FirmFinanceModulesController::class, 'courierPayoutShow']);
        Route::post('/firm/finance/courier-payouts', [FirmFinanceModulesController::class, 'courierPayoutCreate']);
        Route::post('/firm/finance/courier-payouts/{settlement}/void', [FirmFinanceModulesController::class, 'courierPayoutVoid']);
        Route::get('/firm/finance/reconciliation', [FirmFinanceModulesController::class, 'reconciliation']);
        Route::get('/firm/couriers', [FirmCouriersController::class, 'index']);
        Route::post('/firm/couriers', [FirmCouriersController::class, 'store']);
        Route::put('/firm/couriers/{courier}', [FirmCouriersController::class, 'update']);
        Route::patch('/firm/couriers/{courier}/status', [FirmCouriersController::class, 'setStatus']);
        Route::get('/firm/restaurants', [FirmRestaurantsController::class, 'index']);
        Route::post('/firm/restaurants', [FirmRestaurantsController::class, 'store']);
        Route::put('/firm/restaurants/{restaurant}', [FirmRestaurantsController::class, 'update']);
        Route::patch('/firm/restaurants/{restaurant}/status', [FirmRestaurantsController::class, 'setStatus']);
        Route::get('/firm/notifications', [FirmNotificationsController::class, 'index']);
        Route::post('/firm/notifications/{notification}/mark-read', [FirmNotificationsController::class, 'markRead']);
        Route::post('/firm/notifications/mark-all-read', [FirmNotificationsController::class, 'markAllRead']);

        // Restaurant (mobile)
        Route::get('/restaurant/orders', [RestaurantOrdersController::class, 'index']);
        Route::get('/restaurant/orders/{order}', [RestaurantOrdersController::class, 'show']);
        Route::post('/restaurant/orders/{order}/accept', [RestaurantOrdersController::class, 'accept']);
        Route::post('/restaurant/orders/{order}/preparing', [RestaurantOrdersController::class, 'preparing']);
        Route::post('/restaurant/orders/{order}/ready', [RestaurantOrdersController::class, 'ready']);
        Route::post('/restaurant/orders/{order}/cancel', [RestaurantOrdersController::class, 'cancel']);
        Route::post('/restaurant/orders/{order}/request-courier', [RestaurantOrdersController::class, 'requestCourier']);
        Route::get('/restaurant/customers', [RestaurantInsightsController::class, 'customers']);
        Route::get('/restaurant/products', [RestaurantInsightsController::class, 'products']);
        Route::get('/restaurant/categories', [RestaurantInsightsController::class, 'categories']);
        Route::get('/restaurant/reports/summary', [RestaurantInsightsController::class, 'reports']);

        // Routing (mobile)
        Route::post('/routing/route', RoutingRouteController::class)->middleware('throttle:240,1');
    });
});
