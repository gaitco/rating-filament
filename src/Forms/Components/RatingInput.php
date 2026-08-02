<?php

namespace Ghanem\RatingFilament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Ghanem\RatingFilament\Concerns\HasStars;

class RatingInput extends Field
{
    use HasStars;

    protected string $view = 'rating-filament::forms.components.rating-input';

    protected bool | Closure $isClearable = true;

    protected bool | Closure $allowHalf = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule('numeric');

        // Mirror the core package's bounds so the form rejects out-of-range
        // values with a field error instead of letting Rating::validateRating()
        // throw InvalidRatingException, which surfaces as a 500 in a panel.
        $min = config('rating.min');
        if ($min !== null) {
            $this->rule('min:' . $min);
        }

        $max = config('rating.max');
        if ($max !== null) {
            $this->rule('max:' . $max);
        }
    }

    public function clearable(bool | Closure $condition = true): static
    {
        $this->isClearable = $condition;

        return $this;
    }

    public function isClearable(): bool
    {
        return (bool) $this->evaluate($this->isClearable);
    }

    public function allowHalf(bool | Closure $condition = true): static
    {
        $this->allowHalf = $condition;

        return $this;
    }

    public function allowsHalf(): bool
    {
        return (bool) $this->evaluate($this->allowHalf);
    }
}
