<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

final class AnotherPage extends Page
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.another-page';
}
