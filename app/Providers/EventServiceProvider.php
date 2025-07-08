<?php

namespace App\Providers;

use App\Listeners\SetLoginFlag;
use App\Listeners\AutoBackupBeforeCascadeDelete;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\OrderCropPlanted;
use App\Events\AllCropsReady;
use App\Events\OrderHarvested;
use App\Events\OrderPacked;
use App\Events\PaymentReceived;
use App\Listeners\OrderCropPlantedListener;
use App\Listeners\AllCropsReadyListener;
use App\Listeners\OrderHarvestedListener;
use App\Listeners\OrderPackedListener;
use App\Listeners\PaymentReceivedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Login::class => [
            SetLoginFlag::class,
        ],
        OrderCropPlanted::class => [
            OrderCropPlantedListener::class,
        ],
        AllCropsReady::class => [
            AllCropsReadyListener::class,
        ],
        OrderHarvested::class => [
            OrderHarvestedListener::class,
        ],
        OrderPacked::class => [
            OrderPackedListener::class,
        ],
        PaymentReceived::class => [
            PaymentReceivedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register automatic backup listener for critical model deletions
        Event::listen('eloquent.deleting: *', AutoBackupBeforeCascadeDelete::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 