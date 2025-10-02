<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Appointment;
use Awcodes\Palette\Forms\Components\ColorPickerSelect;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

final class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
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
