<?php

namespace FleetTrackingTechnology\LaravelMailTransport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Microsoft Graph sendMail via HTTP (compatible with Symfony Mailer 6 / Laravel 10).
 * Logic aligned with symfony/microsoft-graph-mailer bridge.
 */
final class NativeMicrosoftGraphTransport extends AbstractTransport
{
    private ?string $cachedToken = null;

    private int $tokenExpiresAt = 0;

    public function __construct(
        private string $graphHost,
        private string $authHost,
        private string $tenantId,
        private string $clientId,
        private string $clientSecret,
        private bool $noSave,
        private HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return sprintf('microsoftgraph+native://%s', $this->graphHost);
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();
        if (! $original instanceof Email) {
            throw new \InvalidArgumentException(sprintf(
                'Microsoft Graph transport only supports %s instances.',
                Email::class
            ));
        }

        $envelope = $message->getEnvelope();
        $endpoint = sprintf(
            'https://%s/v1.0/users/%s/sendMail',
            $this->graphHost,
            rawurlencode($envelope->getSender()->getAddress())
        );

        $payload = $this->buildPayload($original, $envelope);

        $response = $this->httpClient->request('POST', $endpoint, [
            'json' => $payload,
            'auth_bearer' => $this->getAccessToken(),
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new HttpTransportException('Could not reach the remote Microsoft server.', $response, 0, $e);
        }

        if (202 !== $statusCode) {
            throw new HttpTransportException(
                'Unable to send an email: '.$response->getContent(false).sprintf(' (code %d).', $statusCode),
                $response
            );
        }
    }

    private function getAccessToken(): string
    {
        if ($this->cachedToken !== null && time() < $this->tokenExpiresAt) {
            return $this->cachedToken;
        }

        $tokenUrl = sprintf('https://%s/%s/oauth2/v2.0/token', $this->authHost, $this->tenantId);
        $scope = sprintf('https://%s/.default', $this->graphHost);

        $response = $this->httpClient->request('POST', $tokenUrl, [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => $scope,
                'grant_type' => 'client_credentials',
            ],
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new HttpTransportException('Could not reach the Microsoft authentication server.', $response, 0, $e);
        }

        if (200 !== $statusCode) {
            throw new HttpTransportException(
                'Unable to authenticate: '.$response->getContent(false).sprintf(' (code %d).', $statusCode),
                $response
            );
        }

        $data = $response->toArray();
        $this->cachedToken = $data['access_token'] ?? '';
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $this->tokenExpiresAt = time() + max(60, $expiresIn - 60);

        return $this->cachedToken;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Email $email, Envelope $envelope): array
    {
        $msg = [
            'sender' => $this->formatAddress($envelope->getSender()),
            'subject' => (string) $email->getSubject(),
            'body' => $this->bodyPayload($email),
            'importance' => $this->importance($email),
            'toRecipients' => array_map(fn (Address $a) => $this->formatAddress($a), $email->getTo()),
        ];

        if ($from = $email->getFrom()) {
            $msg['from'] = $this->formatAddress($from[0]);
        }

        if ($atts = $this->attachments($email)) {
            $msg['attachments'] = $atts;
        }

        if ($bcc = array_map(fn (Address $a) => $this->formatAddress($a), $email->getBcc())) {
            $msg['bccRecipients'] = $bcc;
        }

        if ($cc = array_map(fn (Address $a) => $this->formatAddress($a), $email->getCc())) {
            $msg['ccRecipients'] = $cc;
        }

        if ($custom = $this->customHeaders($email)) {
            $msg['internetMessageHeaders'] = $custom;
        }

        if ($reply = array_map(fn (Address $a) => $this->formatAddress($a), $email->getReplyTo())) {
            $msg['replyTo'] = $reply;
        }

        $data = ['message' => $msg];
        if ($this->noSave) {
            $data['saveToSentItems'] = false;
        }

        return $data;
    }

    /**
     * @return array{content: string, contentType: string}
     */
    private function bodyPayload(Email $email): array
    {
        if ($email->getHtmlBody()) {
            return ['content' => $email->getHtmlBody(), 'contentType' => 'html'];
        }

        return ['content' => (string) $email->getTextBody(), 'contentType' => 'text'];
    }

    private function formatAddress(Address $address): array
    {
        $inner = ['address' => $address->getAddress()];
        if ($address->getName()) {
            $inner['name'] = $address->getName();
        }

        return ['emailAddress' => $inner];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attachments(Email $email): array
    {
        $out = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename') ?? 'attachment';
            $disposition = $headers->getHeaderBody('Content-Disposition') ?? 'attachment';
            $contentType = $headers->has('Content-Type')
                ? $headers->get('Content-Type')->getBody()
                : 'application/octet-stream';

            $row = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $filename,
                'contentBytes' => base64_encode($attachment->getBody()),
                'contentType' => $contentType,
            ];
            if ('inline' === $disposition) {
                $row['contentId'] = $attachment->getContentId();
                $row['isInline'] = true;
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @return list<array{name: string, value: string}>
     */
    private function customHeaders(Email $email): array
    {
        $skip = ['x-ms-client-request-id', 'operation-id', 'authorization', 'x-ms-content-sha256', 'received', 'dkim-signature', 'content-transfer-encoding', 'sender', 'from', 'to', 'cc', 'bcc', 'subject', 'content-type', 'reply-to', 'return-path'];
        $headers = [];
        foreach ($email->getHeaders()->all() as $name => $header) {
            if (\in_array(strtolower((string) $name), $skip, true)) {
                continue;
            }
            if ('' === $header->getBodyAsString()) {
                continue;
            }
            $headers[] = [
                'name' => $header->getName(),
                'value' => $header->getBodyAsString(),
            ];
        }

        return $headers;
    }

    private function importance(Email $email): string
    {
        return match ($email->getPriority()) {
            Email::PRIORITY_HIGHEST,
            Email::PRIORITY_HIGH => 'high',
            Email::PRIORITY_LOW,
            Email::PRIORITY_LOWEST => 'low',
            default => 'normal',
        };
    }
}
