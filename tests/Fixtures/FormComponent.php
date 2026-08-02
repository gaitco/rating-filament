<?php

namespace Ghanem\RatingFilament\Tests\Fixtures;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Ghanem\RatingFilament\Forms\Components\RatingInput;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FormComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['rating' => null]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([RatingInput::make('rating')])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('rating-filament-tests::form');
    }
}
