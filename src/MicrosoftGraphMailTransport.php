<?php

namespace FleetTrackingTechnology\LaravelMailTransport;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class MicrosoftGraphMailTransport
{
    public static function create(array $config): TransportInterface
    {
        foreach (['client_id', 'client_secret', 'tenant_id'] as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException(
                    "Microsoft Graph mailer is missing \"{$key}\". Set mail-transport.microsoft_graph.* or bind a MailTransportResolver."
                );
            }
        }

        $graphHost = trim((string) ($config['graph_endpoint'] ?? '')) !== ''
            ? trim((string) $config['graph_endpoint'])
            : 'graph.microsoft.com';

        $authHost = trim((string) ($config['auth_endpoint'] ?? '')) !== ''
            ? trim((string) $config['auth_endpoint'])
            : 'login.microsoftonline.com';

        $http = HttpClient::create($config['client'] ?? []);

        return new NativeMicrosoftGraphTransport(
            $graphHost,
            $authHost,
            (string) $config['tenant_id'],
            (string) $config['client_id'],
            (string) $config['client_secret'],
            ! empty($config['no_save']),
            $http,
        );
    }
}
