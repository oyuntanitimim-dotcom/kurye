<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Services\LocalPaymentGateway;
use App\Modules\Payments\Services\NullPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        config(['payment.driver' => 'local']);
        parent::tearDown();
    }

    public function test_default_driver_resolves_local_gateway(): void
    {
        config(['payment.driver' => 'local']);

        $this->assertInstanceOf(LocalPaymentGateway::class, $this->app->make(PaymentGatewayInterface::class));
    }

    public function test_null_driver_resolves_null_gateway(): void
    {
        config(['payment.driver' => 'null']);

        $this->assertInstanceOf(NullPaymentGateway::class, $this->app->make(PaymentGatewayInterface::class));
    }
}
