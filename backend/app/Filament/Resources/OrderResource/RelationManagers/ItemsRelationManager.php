<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('product_name')->label('Product')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('variant_name')->label('Variant'),
                \Filament\Tables\Columns\TextColumn::make('sku'),
                \Filament\Tables\Columns\TextColumn::make('unit_price_den')
                    ->label('Unit')
                    ->formatStateUsing(fn ($s) => number_format((int) $s, 0, '.', ',') . ' ден'),
                \Filament\Tables\Columns\TextColumn::make('qty')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('line_total_den')
                    ->label('Line total')
                    ->formatStateUsing(fn ($s) => number_format((int) $s, 0, '.', ',') . ' ден'),
            ])
            ->paginated(false);
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canEdit($record): bool
    {
        return false;
    }

    protected function canDelete($record): bool
    {
        return false;
    }
}
