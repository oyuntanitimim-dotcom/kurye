<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo hesapları Eloquent "hashed" cast dışında doğrudan bcrypt yazar;
 * şifrelerin bozulduğu / migrate sonrası seed atlanmış ortamlarda girişi düzeltir.
 */
class RepairDemoLoginsCommand extends Command
{
    protected $signature = 'kurye:repair-demo-logins {--password= : Düz metin şifre (varsayılan: config dev_login veya "password")}';

    protected $description = 'Demo kullanıcı e-postalarının şifresini yeniler (users tablosu).';

    public function handle(): int
    {
        $plain = (string) ($this->option('password') ?: config('dev_login.password_hint', 'password'));
        $hash = Hash::make($plain);

        $emails = collect(config('dev_login.accounts', []))
            ->pluck('email')
            ->merge(['admin@kurye.local', 'firma-b@demo.local'])
            ->unique()
            ->filter()
            ->values();

        $found = 0;
        foreach ($emails as $email) {
            $count = DB::table('users')->where('email', $email)->update([
                'password' => $hash,
                'status' => 'active',
            ]);
            if ($count > 0) {
                $this->line("Güncellendi: {$email}");
                $found += $count;
            } else {
                $this->warn("users tablosunda yok: {$email} — php artisan db:seed çalıştırın.");
            }
        }

        if ($found === 0) {
            $this->error('Hiçbir kullanıcı güncellenmedi. Veritabanında demo kullanıcıları oluşturmak için: php artisan db:seed');
        } else {
            $this->info("Tamam. Giriş şifresi: {$plain}");
        }

        return $found > 0 ? self::SUCCESS : self::FAILURE;
    }
}
