<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class CreateOwnerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monetra:create-owner {--email= : Email akun pemilik} {--name= : Nama pemilik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provisioning akun pemilik privat Monetra secara interaktif dan aman';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=========================================');
        $this->info('  Monetra — Provisioning Akun Pemilik   ');
        $this->info('=========================================');

        $email = $this->option('email') ?: $this->ask('Masukkan email pemilik');
        $name = $this->option('name') ?: $this->ask('Masukkan nama pemilik', 'Pemilik Monetra');

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
        ], [
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("Akun dengan email [{$email}] sudah terdaftar di sistem!");

            return self::FAILURE;
        }

        $password = $this->secret('Masukkan kata sandi pemilik (minimal 8 karakter)');
        if (! $password || strlen($password) < 8) {
            $this->error('Kata sandi minimal harus 8 karakter!');

            return self::FAILURE;
        }

        $passwordConfirmation = $this->secret('Konfirmasi kata sandi');
        if ($password !== $passwordConfirmation) {
            $this->error('Konfirmasi kata sandi tidak cocok!');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'is_admin' => true,
            'password' => Hash::make($password),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'currency' => 'IDR',
            'payday_day' => 25,
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info("✓ Akun pemilik berhasil dibuat untuk [{$user->email}]!");
        $this->info('Silakan masuk melalui halaman login aplikasi.');

        return self::SUCCESS;
    }
}
