<?php

namespace App\Providers;

use App\Modules\Firms\Models\Campaign;
use App\Modules\Firms\Models\Coupon;
use App\Infrastructure\Geo\Contracts\CourierGeoLocatorInterface;
use App\Infrastructure\Geo\NullCourierGeoLocator;
use App\Infrastructure\Geo\RedisGeoCourierLocator;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Services\LocalPaymentGateway;
use App\Modules\Payments\Services\NullPaymentGateway;
use App\Policies\CampaignPolicy;
use App\Policies\CouponPolicy;
use App\Services\FirmContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Services\Geocoding\NominatimGeocoder;
use App\Support\FinanceReporting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirmContext::class, fn () => new FirmContext);
        $this->app->singleton(NominatimGeocoder::class, fn () => new NominatimGeocoder);
        $this->app->bind(PaymentGatewayInterface::class, function (): PaymentGatewayInterface {
            return match ((string) config('payment.driver', 'local')) {
                'null' => new NullPaymentGateway,
                default => new LocalPaymentGateway,
            };
        });

        $this->app->singleton(CourierGeoLocatorInterface::class, function (): CourierGeoLocatorInterface {
            if (! config('courier.geo_redis_enabled', false)) {
                return new NullCourierGeoLocator;
            }

            return new RedisGeoCourierLocator(
                connection: (string) config('courier.geo_redis_connection', 'default'),
                keyPrefix: (string) config('courier.geo_redis_key', 'kurye:couriers:geo'),
            );
        });
    }

    public function boot(): void
    {
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);

        RateLimiter::for('integration-webhook', function (Request $request): Limit {
            $per = max(10, (int) config('marketplace_integrations.webhook_per_minute', 120));

            return Limit::perMinute($per)->by($request->ip());
        });

        RateLimiter::for('courier-location', function (Request $request): Limit {
            $per = max(12, (int) config('courier.location_posts_per_minute', 180));

            return Limit::perMinute($per)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        View::composer(
            [
                'firm.finance.overview',
                'firm.finance.balances',
                'firm.finance.courier_collections',
                'firm.finance.courier_payouts.index',
                'firm.finance.courier_payouts.create',
                'firm.finance.courier_payouts.show',
                'firm.finance.reconciliation',
                'firm.dashboard',
                'firm.reports',
                'restaurant.reports',
                'admin.finance.overview',
                'admin.finance.firms',
                'admin.finance.reconciliation',
                'admin.reports',
                'courier.earnings',
            ],
            static function (\Illuminate\View\View $view): void {
                $view->with('financeOnlineOrdersOnly', FinanceReporting::onlineOrdersOnlyEnabled());
            }
        );
    }
}
