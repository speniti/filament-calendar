@php
  if (!isset($columnSpan) || !is_array($columnSpan)) {
      $columnSpan = [
          'default' => $columnSpan ?? null,
      ];
  }

  if (!isset($columnStart) || !is_array($columnStart)) {
      $columnStart = [
          'default' => $columnStart ?? null,
      ];
  }

  $aspectRatio = $options['aspectRatio'];
@endphp

<x-filament::grid.column
  :default="$columnSpan['default'] ?? 1"
  :sm="$columnSpan['sm'] ?? null"
  :md="$columnSpan['md'] ?? null"
  :lg="$columnSpan['lg'] ?? null"
  :xl="$columnSpan['xl'] ?? null"
  :twoXl="$columnSpan['2xl'] ?? null"
  :defaultStart="$columnStart['default'] ?? null"
  :smStart="$columnStart['sm'] ?? null"
  :mdStart="$columnStart['md'] ?? null"
  :lgStart="$columnStart['lg'] ?? null"
  :xlStart="$columnStart['xl'] ?? null"
  :twoXlStart="$columnStart['2xl'] ?? null"
  class="fi-loading-section"
>
  <x-filament::section class="animate-pulse">
    <div class="w-full grid place-items-center" style="aspect-ratio: {{ $aspectRatio }};">
      <div
        class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 p-3 dark:bg-gray-500/20"
      >
        <x-filament::icon
          icon="heroicon-o-calendar-date-range"
          class="fi-ta-empty-state-icon h-6 w-6 text-gray-500 dark:text-gray-400"
        />
      </div>
    </div>
  </x-filament::section>
</x-filament::grid.column>
