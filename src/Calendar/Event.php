<?php

declare(strict_types=1);

namespace Peniti\FilamentCalendar\Calendar;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonException;
use JsonSerializable;
use Peniti\FilamentCalendar\Calendar\Event\Display;
use ReflectionClass;
use ReflectionProperty;
use Spatie\Color\Hex;
use Spatie\Color\Rgb;
use Spatie\Color\Rgba;

/**
 * @implements Arrayable<string, mixed>
 *
 * @phpstan-type EventArray array{
 *     id: string, title: string, start: DateTimeInterface, end: DateTimeInterface,
 *     allDay?: bool, backgroundColor?: Hex|Rgb|Rgba, borderColor?: Hex|Rgb|Rgba,
 *     classNames?: array<string, mixed>, display?: Display, durationEditable?: bool,
 *     editable?: bool, extendedProps?: array<string, mixed>, groupId?: string,
 *     overlap?: bool, startEditable?: bool, textColor?: Hex|Rgb|Rgba, url?: string,
 * }
 * @phpstan-type EventJsonArray array{
 *      id: string, title: string, start: string, end: string,
 *      allDay?: bool, backgroundColor?: string, borderColor?: string,
 *      classNames?: array<string, mixed>, display?: string, durationEditable?: bool,
 *      editable?: bool, extendedProps?: array<string, mixed>, groupId?: string,
 *      overlap?: bool, startEditable?: bool, textColor?: string, url?: string,
 *  }
 */
final readonly class Event implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * @param  list<string>  $classNames
     * @param  array<string, mixed>  $extendedProps
     */
    public function __construct(
        public string $id,
        public string $title,
        public DateTimeInterface $start,
        public ?DateTimeInterface $end = null,
        public ?bool $allDay = null,
        public Hex|Rgb|Rgba|null $backgroundColor = null,
        public Hex|Rgb|Rgba|null $borderColor = null,
        public ?array $classNames = null,
        public ?Display $display = null,
        public ?bool $durationEditable = null,
        public ?bool $editable = null,
        public ?array $extendedProps = null,
        public ?string $groupId = null,
        public ?bool $overlap = null,
        public ?bool $startEditable = null,
        public Hex|Rgb|Rgba|null $textColor = null,
        public ?string $url = null,
    ) {}

    /** @return EventJsonArray */
    public function jsonSerialize(): array
    {

        /** @var EventJsonArray $event */
        $event = array_filter([
            ...$this->toArray(),
            'start' => $this->allDay ? $this->start->format('Y-m-d') : $this->start->format('c'),
            'end' => $this->allDay ? $this->end?->format('Y-m-d') : $this->end?->format('c'),
            'backgroundColor' => (string) $this->backgroundColor,
            'borderColor' => (string) $this->borderColor,
            'display' => $this->display?->value,
            'textColor' => (string) $this->textColor,
        ], static fn ($option) => is_bool($option) || ! empty($option));

        return $event;
    }

    /** @return EventArray */
    public function toArray(): array
    {
        $properties = new ReflectionClass($this)
            ->getProperties(ReflectionProperty::IS_PUBLIC);

        /** @var EventArray $event */
        $event = collect($properties)
            ->mapWithKeys(fn (ReflectionProperty $property) => [
                $property->getName() => $property->getValue($this),
            ])
            ->filter(fn ($option) => is_bool($option) || ! empty($option))
            ->toArray();

        return $event;
    }

    /** @throws JsonException */
    public function toJson($options = 0): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | $options);
    }
}
