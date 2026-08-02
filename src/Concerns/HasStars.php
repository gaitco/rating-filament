<?php

namespace Ghanem\RatingFilament\Concerns;

use Closure;

trait HasStars
{
    protected int | Closure | null $stars = null;

    protected string | Closure $starColor = '#f59e0b';

    protected bool | Closure $showValue = true;

    public function stars(int | Closure $stars): static
    {
        $this->stars = $stars;

        return $this;
    }

    public function getStars(): int
    {
        $stars = $this->evaluate($this->stars);

        if ($stars !== null) {
            return (int) $stars;
        }

        // Fall back to the core package's configured maximum so the widget and
        // the model layer always agree on the scale.
        return (int) (config('rating.max') ?? 5);
    }

    public function starColor(string | Closure $color): static
    {
        $this->starColor = $color;

        return $this;
    }

    public function getStarColor(): string
    {
        return $this->evaluate($this->starColor);
    }

    public function showValue(bool | Closure $condition = true): static
    {
        $this->showValue = $condition;

        return $this;
    }

    public function getShowValue(): bool
    {
        return (bool) $this->evaluate($this->showValue);
    }
}
