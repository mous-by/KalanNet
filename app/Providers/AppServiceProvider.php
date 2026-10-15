<?php

namespace App\Providers;

use App\Support\SchemaExiste;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Paginator::useBootstrapFive();

        // Vérifications de schéma gardées en cache (SchemaExiste) : oubliées après chaque migration.
        Event::listen(MigrationsEnded::class, fn () => SchemaExiste::oublier());

        // @devise(150000) -> "150 000 FCFA" (ou l'equivalent pour le pays de
        // l'ecole active) ; @devise(150000, $ecole) pour forcer une ecole
        // precise (ex: page SupAdmin qui liste plusieurs ecoles a la fois).
        Blade::directive('devise', fn ($expression) => "<?php echo \App\Support\Devise::format({$expression}); ?>");
    }
}
