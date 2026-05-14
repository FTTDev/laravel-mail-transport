<?php

namespace FleetTrackingTechnology\LaravelMailTransport;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\MicrosoftGraph\Transport\MicrosoftGraphTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

final class MicrosoftGraphMailTransport
{
    public static function create(array $config): \Symfony\Component\Mailer\Transport\TransportInterface
    {
        foreach (['client_id', 'client_secret', 'tenant_id'] as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException(
                    "Microsoft Graph mailer is missing \"{$key}\". Set mail-transport.microsoft_graph.* or bind a MailTransportResolver."
                );
            }
        }

        $factory = new MicrosoftGraphTransportFactory(
            null,
            HttpClient::create($config['client'] ?? []),
        );

        $host = trim((string) ($config['graph_endpoint'] ?? '')) !== ''
            ? trim($config['graph_endpoint'])
            : 'default';

        $dsnString = sprintf(
            'microsoftgraph+api://%s:%s@%s?tenantId=%s',
            rawurlencode((string) $config['client_id']),
            rawurlencode((string) $config['client_secret']),
            $host,
            rawurlencode((string) $config['tenant_id'])
        );

        if (! empty($config['auth_endpoint'])) {
            $dsnString .= '&authEndpoint='.rawurlencode(trim((string) $config['auth_endpoint']));
        }

        if (! empty($config['no_save'])) {
            $dsnString .= '&noSave=true';
        }

        return $factory->create(Dsn::fromString($dsnString));
    }
}
