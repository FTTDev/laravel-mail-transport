<?php

namespace FleetTrackingTechnology\LaravelMailTransport\Resolvers;

use FleetTrackingTechnology\LaravelMailTransport\Contracts\MailTransportResolver;

final class EnvironmentMailTransportResolver implements MailTransportResolver
{
    public function defaultMailer(): string
    {
        $override = config('mail-transport.default_mailer');

        if ($override !== null && $override !== '') {
            return (string) $override;
        }

        return (string) config('mail.default', env('MAIL_MAILER', 'smtp'));
    }

    public function microsoftGraphMailerConfig(): array
    {
        $c = config('mail-transport.microsoft_graph', []);

        return [
            'client_id' => (string) ($c['client_id'] ?? ''),
            'client_secret' => (string) ($c['client_secret'] ?? ''),
            'tenant_id' => (string) ($c['tenant_id'] ?? ''),
            'graph_endpoint' => (string) ($c['graph_endpoint'] ?? ''),
            'auth_endpoint' => (string) ($c['auth_endpoint'] ?? ''),
            'no_save' => filter_var($c['no_save'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
