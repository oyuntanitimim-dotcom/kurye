<?php

namespace App\Http\Middleware;

use App\Modules\Firms\Models\Firm;
use App\Services\FirmContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveFirmFromDomain
{
    public function __construct(
        private readonly FirmContext $firmContext
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $firm = Firm::query()
            ->where('status', 'active')
            ->where(function ($q) use ($host): void {
                $q->where('domain', $host)
                    ->orWhere('domain', 'www.'.$host);
            })
            ->first();

        $path = ltrim($request->path(), '/');
        $isShopContext = $path === '' || str_starts_with($path, 'alisveris/') || $path === 'alisveris';

        if ($firm === null && app()->environment(['local', 'testing']) && $isShopContext) {
            $firm = Firm::query()->where('status', 'active')->orderBy('id')->first();
        }

        $this->firmContext->set($firm);

        return $next($request);
    }
}
