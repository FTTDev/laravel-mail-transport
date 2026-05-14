<?php

namespace FleetTrackingTechnology\LaravelMailTransport\Contracts;

interface MailTransportResolver
{
    /**
     * Laravel mailer name: e.g. smtp, microsoft_graph, log.
     */
    public function defaultMailer(): string;

    /**
     * Keys merged into mail.mailers.microsoft_graph (transport is added by the package).
     *
     * @return array{
     *     client_id: string,
     *     client_secret: string,
     *     tenant_id: string,
     *     graph_endpoint?: string,
     *     auth_endpoint?: string,
     *     no_save?: bool
     * }
     */
    public function microsoftGraphMailerConfig(): array;
}
