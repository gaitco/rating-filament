<?php

namespace Ghanem\RatingFilament\Tests\Fixtures;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Ghanem\RatingFilament\Infolists\Components\RatingEntry;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InfolistComponent extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->record(['rating' => 3.5])
            ->components([
                RatingEntry::make('rating'),
            ]);
    }

    public function render(): View
    {
        return view('rating-filament-tests::infolist');
    }
}
