<?php

namespace Peniti\FilamentCalendar;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentCalendarServiceProvider extends PackageServiceProvider
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
                'fullcalendar',
                $this->package->basePath('/../dist/fullcalendar.js')
            ),

            Css::make(
                'fullcalendar',
                $this->package->basePath('/../dist/fullcalendar.css')
            ),

            Css::make(
                'filament-calendar',
                $this->package->basePath('/../dist/filament-calendar.css')
            ),
        ], 'speniti/filament-calendar');
    }
}
