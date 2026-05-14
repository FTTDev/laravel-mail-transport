<?php

namespace FleetTrackingTechnology\LaravelMailTransport;

use FleetTrackingTechnology\LaravelMailTransport\Contracts\MailTransportResolver;
use FleetTrackingTechnology\LaravelMailTransport\Resolvers\EnvironmentMailTransportResolver;
use Illuminate\Support\Facades\Config;

final class MailTransportApplier
{
    public static function applyFromContainer(): void
    {
        $resolver = app()->bound(MailTransportResolver::class)
            ? app(MailTransportResolver::class)
            : new EnvironmentMailTransportResolver;

        self::apply($resolver);
    }

    public static function apply(MailTransportResolver $resolver): void
    {
        $default = $resolver->defaultMailer();

        if ($default === 'microsoft_graph') {
            Config::set('mail.default', 'microsoft_graph');
        }

        $graph = $resolver->microsoftGraphMailerConfig();

        Config::set('mail.mailers.microsoft_graph', array_merge(
            ['transport' => 'microsoft_graph'],
            $graph
        ));
    }
}
