<?php

namespace App\Providers;

use App\AI\EmailAnalyzer;
use App\AI\FakeAnalyzer;
use App\AI\OpenRouterAnalyzer;
use App\AI\PromptProvider;
use App\Mailbox\FakeMailbox;
use App\Mailbox\ImapMailbox;
use App\Mailbox\MailboxClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerMailboxClient();
        $this->registerEmailAnalyzer();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function registerMailboxClient(): void
    {
        $this->app->bind(MailboxClient::class, function (): MailboxClient {
            if (config('mailbox.driver') === 'imap') {
                return new ImapMailbox(
                    host: config('mailbox.imap.host'),
                    port: config('mailbox.imap.port'),
                    username: config('mailbox.imap.username'),
                    password: config('mailbox.imap.password'),
                    encryption: config('mailbox.imap.encryption'),
                    validateCert: config('mailbox.imap.validate_cert'),
                    folder: config('mailbox.imap.folder'),
                );
            }

            return new FakeMailbox;
        });
    }

    protected function registerEmailAnalyzer(): void
    {
        $this->app->bind(EmailAnalyzer::class, function (): EmailAnalyzer {
            if (config('ai.provider') === 'fake') {
                return new FakeAnalyzer;
            }

            return new OpenRouterAnalyzer(
                prompts: new PromptProvider,
                apiKey: (string) config('ai.openrouter.api_key'),
                model: (string) config('ai.openrouter.model'),
                baseUrl: (string) config('ai.openrouter.base_url'),
                timeout: (int) config('ai.openrouter.timeout'),
                verifySsl: (bool) config('ai.openrouter.verify_ssl', true),
            );
        });
    }
}
