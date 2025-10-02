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

<div class="fi-section fi-loading-section col-span-full">
  <div
    class="grid w-full animate-pulse place-items-center"
    style="aspect-ratio: {{ $aspectRatio }};"
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
</div>
