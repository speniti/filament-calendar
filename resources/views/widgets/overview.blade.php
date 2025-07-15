@php
  use Filament\Support\Facades\FilamentAsset;
  use Filament\Support\Facades\FilamentColor;
  use Illuminate\Support\Arr;
  use Spatie\Color\Rgba;

  $color = static function (
      string $key,
      int $shade = 500,
      float $alpha = 1,
  ): ?Rgba {
      if (!($color = Arr::get(FilamentColor::getColors(), "$key.$shade"))) {
          return null;
      }

      return Rgba::fromString("rgba($color, $alpha)");
  };

  $aspectRatio = $options['aspectRatio'];
@endphp

<!--suppress HtmlUnknownAttribute, RequiredAttributes -->
<x-filament-widgets::widget>
  <x-filament::section style="aspect-ratio: {{ $aspectRatio }};">
    <div
      wire:ignore
      style="aspect-ratio: {{ $aspectRatio }};"
      class="grid w-full place-items-center"
      x-filament-calendar-placeholder
    >
      <div
        class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 p-3 dark:bg-gray-500/20"
      >
        <x-filament::icon
          icon="heroicon-o-calendar-date-range"
          class="fi-ta-empty-state-icon h-6 w-6 text-gray-500 dark:text-gray-400"
        />
      </div>
    </div>

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
      x-load
      x-load-src="{{ FilamentAsset::getAlpineComponentSrc('calendar', 'speniti/filament-calendar') }}"
      x-data="calendar(@js($options, JSON_THROW_ON_ERROR))"
      x-ignore
      wire:ignore
      class="flex-1"
    ></div>
  </x-filament::section>

  <x-filament-actions::modals />
</x-filament-widgets::widget>
