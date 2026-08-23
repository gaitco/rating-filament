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
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Deliberately a method, not the `$title` property: a property initialiser
     * cannot call __(), so a static $title would be frozen in English.
     */
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('rating-filament::rating-filament.relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            RatingInput::make('rating')->required(),
            Textarea::make('body')
                ->label(__('rating-filament::rating-filament.fields.review'))
                ->rows(4),
            TextInput::make('type')
                ->label(__('rating-filament::rating-filament.fields.type'))
                ->helperText(__('rating-filament::rating-filament.fields.type_helper')),
            TextInput::make('weight')
                ->label(__('rating-filament::rating-filament.fields.weight'))
                ->numeric(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('rating')
            ->columns([
                TextColumn::make('author_id')
                    ->label(__('rating-filament::rating-filament.fields.author'))
                    ->sortable(),
                RatingColumn::make('rating')->showValue(),
                TextColumn::make('body')
                    ->label(__('rating-filament::rating-filament.fields.review'))
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('type')
                    ->label(__('rating-filament::rating-filament.fields.type'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
