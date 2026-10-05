<?php

namespace App\Providers;

use App\Communications\Sources\CsvSource;
use App\Communications\Sources\ErpClientSource;
use App\Communications\Sources\ErpEmployeeSource;
use App\Communications\Sources\ErpLeadSource;
use App\Communications\Sources\GoogleSheetsSource;
use App\Contracts\AudienceSource;
use App\Http\Controllers\Api\CampaignController;
use App\Models\Employee;
use App\Models\StockMovement;
use App\Pending\CrmPendingProvider;
use App\Pending\FinancePendingProvider;
use App\Pending\HrPendingProvider;
use App\Pending\PurchasesPendingProvider;
use App\Pending\SalesPendingProvider;
use App\Policies\EmployeePolicy;
use App\Policies\StockMovementPolicy;
use App\Services\PendingService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;

class AppServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        Employee::class      => EmployeePolicy::class,
        StockMovement::class => StockMovementPolicy::class,
    ];

    public function register(): void
    {
        // PendingService as singleton — providers register once at boot.
        $this->app->singleton(PendingService::class);

        // CampaignController needs the sources list injected.
        $this->app->bind(CampaignController::class, function () {
            return new CampaignController([
                new ErpEmployeeSource(),
                new ErpClientSource(),
                new ErpLeadSource(),
                new GoogleSheetsSource(),
                new CsvSource(),
            ]);
        });
    }

    public function boot(): void
    {
        $this->registerPolicies();

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });

        // Register pending providers — each vertical owns its slice.
        $pending = $this->app->make(PendingService::class);
        $pending->register(new HrPendingProvider());
        $pending->register(new CrmPendingProvider());
        $pending->register(new PurchasesPendingProvider());
        $pending->register(new SalesPendingProvider());
        $pending->register(new FinancePendingProvider());
    }
}
