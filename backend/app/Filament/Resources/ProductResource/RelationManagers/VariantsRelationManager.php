<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('sku')
                ->required()
                ->unique(ignoreRecord: true),

            \Filament\Forms\Components\TextInput::make('name')->maxLength(255)->nullable(),
            \Filament\Forms\Components\TextInput::make('color')->maxLength(255)->nullable(),
            \Filament\Forms\Components\TextInput::make('material')->maxLength(255)->nullable(),
            \Filament\Forms\Components\TextInput::make('dimensions')->maxLength(255)->nullable(),

            \Filament\Forms\Components\TextInput::make('price_cents')
                ->label('Цена (денари)')
                ->numeric()
                ->required()
                ->formatStateUsing(fn ($state) => $state !== null ? (int) round($state / 100) : null)
                ->dehydrateStateUsing(fn ($state) => $state !== null ? ((int) $state) * 100 : null),

            \Filament\Forms\Components\TextInput::make('sale_price_cents')
                ->label('Попуст цена (денари)')
                ->numeric()
                ->nullable()
                ->formatStateUsing(fn ($state) => $state !== null ? (int) round($state / 100) : null)
                ->dehydrateStateUsing(fn ($state) => $state !== null ? ((int) $state) * 100 : null),

            \Filament\Forms\Components\TextInput::make('stock_qty')
                ->numeric()
                ->default(0),

            \Filament\Forms\Components\Toggle::make('track_stock')->default(true),
            \Filament\Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('sku')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('name')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('price_cents')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state) => number_format((int) round($state / 100), 0, '.', ',') . ' ден')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('stock_qty')->sortable(),
                \Filament\Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('id', 'desc');
    }
}
