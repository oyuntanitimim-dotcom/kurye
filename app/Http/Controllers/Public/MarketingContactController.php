<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Marketing\MarketingContactLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MarketingContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'company' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->to(route('marketing.home').'#iletisim')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $lead = MarketingContactLead::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'read' => false,
        ]);

        $notify = config('marketing.lead_notify_email');
        if (is_string($notify) && $notify !== '') {
            try {
                Mail::raw(
                    "Yeni iletişim talebi (#{$lead->id})\n\n".
                    "Ad: {$lead->name}\nE-posta: {$lead->email}\n".
                    ($lead->phone ? "Tel: {$lead->phone}\n" : '').
                    ($lead->company ? "Şirket: {$lead->company}\n" : '').
                    "\nMesaj:\n{$lead->message}\n",
                    function ($message) use ($notify, $lead): void {
                        $message->to($notify)->subject('Tanıtım sitesi — iletişim talebi #'.$lead->id);
                    }
                );
            } catch (\Throwable $e) {
                Log::warning('marketing.lead_mail_failed', ['error' => $e->getMessage(), 'lead_id' => $lead->id]);
            }
        }

        Log::info('marketing.lead_created', ['lead_id' => $lead->id, 'email' => $lead->email]);

        return redirect()
            ->to(route('marketing.home').'#iletisim')
            ->with('status', 'Mesajınız alındı. En kısa sürede size dönüş yapacağız.');
    }
}
