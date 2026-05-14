<?php

namespace FleetTrackingTechnology\LaravelMailTransport\Console;

use FleetTrackingTechnology\LaravelMailTransport\Mail\MailTransportTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTransportTestCommand extends Command
{
    protected $signature = 'mail:test
                            {to? : Recipient email (defaults to MAIL_FROM_ADDRESS)}
                            {--mailer= : Override mailer: smtp, microsoft_graph, log, etc.}';

    protected $description = 'Send a synchronous test email using the current mail configuration';

    public function handle(): int
    {
        $to = $this->argument('to') ?: (string) config('mail.from.address');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid or missing recipient. Example: php artisan mail:test you@example.com');

            return self::FAILURE;
        }

        $override = $this->option('mailer');
        $override = is_string($override) ? trim($override) : '';
        $override = $override !== '' ? $override : null;

        $mailer = Mail::mailer($override);

        $this->line('Recipient: '.$to);
        $this->line('Mailer: '.($override ?? (string) config('mail.default')));
        if (($override ?? config('mail.default')) === 'log') {
            $this->warn('Mailer is "log": check storage/logs for the message body (no real inbox delivery).');
        }

        try {
            $mailer->to($to)->sendNow(new MailTransportTestMail(now()->toDateTimeString()));
        } catch (\Throwable $e) {
            $this->error('Send failed: '.$e->getMessage());
            if ($this->output->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }

        $this->info('Test email sent successfully.');

        return self::SUCCESS;
    }
}
