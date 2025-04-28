<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Appointment;
use Awcodes\Palette\Forms\Components\ColorPickerSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Arr;
use Spatie\Color\Rgb;

final class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(5)->schema([
                    DatePicker::make('start')->columnSpan(3),

                    TimePicker::make('at')
                        ->seconds(false)
                        ->columnSpan(2),
                ]),

                Textarea::make('summary')
                    ->rows(3)
                    ->required(),

                ColorPickerSelect::make('color')
                    ->colors(Arr::mapWithKeys(
                        FilamentColor::getColors(),
                        static fn (array $shades) => [(string) Rgb::fromString("rgb($shades[500])") => $shades]
                    ))
                    ->labels(Arr::mapWithKeys(
                        FilamentColor::getColors(),
                        static fn (array $shades, string $key) => [(string) Rgb::fromString("rgb($shades[500])") => ucfirst($key)]
                    ))
                    ->storeAsKey(),

                RichEditor::make('notes')
                    ->toolbarButtons([
                        'undo',
                        'redo',
                        'bold',
                        'italic',
                        'bulletList',
                        'orderedList',
                        'table',
                    ]),

            ]);
    }
}
