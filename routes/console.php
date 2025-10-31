<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('make:user', function () {
    $name = Laravel\Prompts\text(label: 'User Name', required: true);

    $email = Laravel\Prompts\text(label: 'Email address', required: true);

    $password = Illuminate\Support\Facades\Hash::make(Laravel\Prompts\password(label: 'Password', required: true));

    $user = App\Models\User::create(['name' => $name, 'email' => $email, 'password' => $password]);

    $user->markEmailAsVerified();

    $user->givePermissionTo(App\Models\Spatie\Permission::findOrCreate('manage'));

    $this->components->info('Success! ' . ($user->getAttribute('email') ?? $user->getAttribute('username') ?? 'You') . " may now log in with the password you provided.");
});

/**
 * 排程任務
 */

// 每五分鐘執行一次物流黑貓的任務
Illuminate\Support\Facades\Schedule::command('logistics:blackcat')->everyFiveMinutes();
