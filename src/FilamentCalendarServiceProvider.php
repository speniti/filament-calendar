<?php

declare(strict_types=1);

namespace Peniti\FilamentCalendar;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class FilamentCalendarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('filament-calendar')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            AlpineComponent::make(
                'calendar',
                $this->package->basePath('/../dist/components/calendar.js')
            ),

            //            Css::make(
            //                'calendar',
            //                $this->package->basePath('/../dist/components/calendar.css')
            //            ),

            Css::make(
                'filament-calendar',
                $this->package->basePath('/../dist/filament-calendar.css')
            ),
        ], 'speniti/filament-calendar');
    }
}
