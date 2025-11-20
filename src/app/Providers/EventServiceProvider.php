<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // 👇 これをコメントアウトまたは削除
        // Registered::class => [
        //     \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
        // ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
