<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;

final class AnotherPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.another-page';
}
