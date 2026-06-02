<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Firms\Models\Firm;
use App\Services\FirmContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tek domain kullanımında tenant (kurye şirketi) context'ini oturumdan çözer.
 * - Firm admin / restoran / kurye / müşteri: user.firm_id
 * - Süper admin: context boş kalır (opsiyonel tenant seçimi ileride eklenebilir)
 */
class ResolveFirmFromAuth
{
    public function __construct(private readonly FirmContext $firmContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $firmId = (int) ($user->firm_id ?? 0);
        if ($firmId > 0) {
            // status filtrelemesi burada yapılmaz; erişim/engelleme EnsureActiveAccount + role middleware’lerdedir.
            $firm = Firm::query()->find($firmId);
            $this->firmContext->set($firm);
        } else {
            $this->firmContext->set(null);
        }

        return $next($request);
    }
}

