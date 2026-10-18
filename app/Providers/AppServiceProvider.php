<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\TransactionPosted;
use App\Listeners\RecordTransactionAuditLog;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(TransactionPosted::class, RecordTransactionAuditLog::class);

        Blade::directive('money', function (string $expression) {
            return "<?php echo ((\$__moneyVal = {$expression}) instanceof \\App\\Domain\\Money\\Money ? \$__moneyVal->formatted() : \\App\\Domain\\Money\\Money::of(\$__moneyVal)->formatted()); ?>";
        });
    }
}
