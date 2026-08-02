<?php

namespace Ghanem\RatingFilament\Resources\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Ghanem\RatingFilament\Forms\Components\RatingInput;
use Ghanem\RatingFilament\Tables\Columns\RatingColumn;

/**
 * Moderates the ratings a record has *received*.
 *
 * Extend it and attach it to a resource:
 *
 *     class PostRatingsRelationManager extends RatingsRelationManager {}
 */
abstract class RatingsRelationManager extends RelationManager
{
    protected static string $relationship = 'ratings';

    protected static ?string $title = 'Ratings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            RatingInput::make('rating')->required(),
            Textarea::make('body')->label('Review')->rows(4),
            TextInput::make('type')->helperText('Optional aspect, e.g. "food"'),
            TextInput::make('weight')->numeric(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('rating')
            ->columns([
                TextColumn::make('author_id')->label('Author')->sortable(),
                RatingColumn::make('rating')->showValue(),
                TextColumn::make('body')->label('Review')->limit(60)->wrap(),
                TextColumn::make('type')->badge()->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
