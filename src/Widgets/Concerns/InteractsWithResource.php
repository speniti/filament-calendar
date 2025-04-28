<?php

declare(strict_types=1);

namespace Peniti\FilamentCalendar\Widgets\Concerns;

use Filament\Resources\Resource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

trait InteractsWithResource
{
    #[Locked]
    public Model|string|null $model = null;

    #[Locked]
    public Model|int|string|null $record = null;

    /** @var class-string<resource>|'' */
    #[Locked]
    protected static string $resource = '';

    /**  @return class-string<resource>|'' */
    public static function getResource(): string
    {
        return static::$resource;
    }

    public function getModel(): ?string
    {
        $model = $this->model;

        if ($model instanceof Model) {
            return $model::class;
        }

        if (filled($model)) {
            return $model;
        }

        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        return $resource::getModel();
    }

    public function getRecord(): ?Model
    {
        $record = $this->record;

        return $record instanceof Model ? $record : null;
    }

    public function getRecordTitle(): string|Htmlable|null
    {
        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        if (! $resource::hasRecordTitle()) {
            return $resource::getTitleCaseModelLabel();
        }

        return $resource::getRecordTitle($this->getRecord());
    }

    public function resetRecord(): self
    {
        $this->record = null;

        return $this;
    }

    protected function resolveRecord(int|string $key): ?Model
    {
        $resource = static::getResource();

        if (! class_exists($resource)) {
            return null;
        }

        $this->record = $resource::resolveRecordRouteBinding($key);

        return $this->record;
    }
}
