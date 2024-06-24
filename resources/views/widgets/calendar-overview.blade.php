@php
  use Filament\Support\Facades\FilamentAsset;
  use Filament\Support\Facades\FilamentColor;
  use Illuminate\Support\Arr;
  use Spatie\Color\Rgba;

  $color = static function (string $key, int $shade = 500, float $alpha = 1): ?Rgba
  {
      if (! $color = Arr::get(FilamentColor::getColors(), "$key.$shade")) {
          return null;
      }

      return Rgba::fromString("rgba({$color}, {$alpha})");
  }
@endphp

<x-filament-widgets::widget>
  <x-filament::section>
    <style>
      :root {
        --fc-button-active-bg-color: {{ $color('primary', 600) }};
        --fc-button-active-hover-bg-color: {{ $color('primary', 500) }};
        --fc-button-active-border-color: {{ $color('primary', 500, 0.5) }};
        --fc-button-active-dark-bg-color: {{ $color('primary', 500) }};
        --fc-button-active-hover-bg-color: {{ $color('primary', 400) }};
        --fc-button-active-dark-hover-bg-color: {{ $color('primary', 400) }};
        --fc-button-active-focus-ring-color: {{ $color('primary', 500, 0.5) }};
        --fc-button-active-dark-focus-ring-color: {{ $color('primary', 400, 0.5) }};

        --fc-event-bg-color: {{ $color('primary', 500, 0.85) }};
        --fc-event-text-color: #fff;

        --fc-today-bg-color: {{ $color('primary', 500, 0.1) }};
      }
    </style>

    <div
      ax-load
      ax-load-src="{{ FilamentAsset::getAlpineComponentSrc('fullcalendar', 'speniti/filament-calendar') }}"
      x-data="calendar(@js($options))"
      x-ignore
      wire:ignore
    ></div>
  </x-filament::section>

  <x-filament-actions::modals/>
</x-filament-widgets::widget>
