<?php

namespace Ghanem\RatingFilament\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Ghanem\RatingFilament\Concerns\HasStars;

class RatingEntry extends Entry
{
    use HasStars;

    protected string $view = 'rating-filament::infolists.components.rating-entry';
}
