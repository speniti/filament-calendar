<?php

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

    /** @var class-string<resource> */
    #[Locked]
    protected static string $resource;

    protected function resolveRecord(int|string $key): Model
    {
        $this->record = static::getResource()::resolveRecordRouteBinding($key);

        return $this->record;
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

        return static::getResource()::getModel();
    }

    public function getRecord(): ?Model
    {
        $record = $this->record;

        return $record instanceof Model ? $record : null;
    }

    public function getRecordTitle(): string|Htmlable
    {
        $resource = static::getResource();

        if (! $resource::hasRecordTitle()) {
            return $resource::getTitleCaseModelLabel();
        }

        return $resource::getRecordTitle($this->getRecord());
    }

    /**  @return class-string<resource>  */
    public static function getResource(): string
    {
        return static::$resource;
    }

    public function resetRecord(): self
    {
        $this->record = null;

        return $this;
    }
}
