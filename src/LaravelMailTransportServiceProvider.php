<?php

namespace FleetTrackingTechnology\LaravelMailTransport;

use FleetTrackingTechnology\LaravelMailTransport\Console\MailTransportTestCommand;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class LaravelMailTransportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mail-transport.php', 'mail-transport');
    }

    public function boot(): void
    {
        Mail::extend('microsoft_graph', function (array $config) {
            return MicrosoftGraphMailTransport::create($config);
        });

        MailTransportApplier::applyFromContainer();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MailTransportTestCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/mail-transport.php' => config_path('mail-transport.php'),
        ], 'mail-transport-config');
    }
}
