<?php

declare(strict_types=1);

namespace Peniti\FilamentCalendar\Calendar\Event;

enum Display: string
{
    case AUTO = 'auto';
    case BACKGROUND = 'background';
    case BLOCK = 'block';
    case INVERSE_BACKGROUND = 'inverse-background';
    case LIST_ITEM = 'list-item';
    case NONE = 'none';
}
