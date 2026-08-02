<?php

namespace Ghanem\RatingFilament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;
use Ghanem\RatingFilament\Concerns\HasStars;
use Illuminate\Database\Eloquent\Builder;

class RatingColumn extends Column
{
    use HasStars;

    protected string $view = 'rating-filament::tables.columns.rating-column';

    protected bool | Closure $showCount = false;

    /**
     * Defaults to the alias selected by ghanem/rating's withAvgRating() scope,
     * so display reads an already-selected column and sorting happens in SQL.
     */
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'ratings_avg_rating');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Safety net: stays correct even if a resource forgets withAvgRating().
        // orderByAvgRating() is a local scope on Ratingable models (e.g. Post),
        // but this column is also used directly on Rating records (e.g. in
        // RatingsRelationManager), whose builder has no such scope — fall back
        // to ordering by the column's own name there.
        $this->sortable(
            query: function (Builder $query, string $direction): Builder {
                return $query->getModel()->hasNamedScope('orderByAvgRating')
                    ? $query->orderByAvgRating($direction)
                    : $query->orderBy($this->getName(), $direction);
            },
        );
    }

    public function showCount(bool | Closure $condition = true): static
    {
        $this->showCount = $condition;

        return $this;
    }

    public function getShowCount(): bool
    {
        return (bool) $this->evaluate($this->showCount);
    }
}
