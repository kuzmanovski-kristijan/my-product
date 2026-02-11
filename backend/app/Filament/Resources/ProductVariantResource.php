<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Filament\Resources\ProductVariantResource\RelationManagers;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')->maxLength(255)->nullable(),
                Forms\Components\TextInput::make('color')->maxLength(255)->nullable(),
                Forms\Components\TextInput::make('material')->maxLength(255)->nullable(),
                Forms\Components\TextInput::make('dimensions')->maxLength(255)->nullable(),
                Forms\Components\TextInput::make('price_cents')
                    ->label('Цена (денари)')
                    ->numeric()
                    ->required()
                    ->formatStateUsing(fn ($state) => $state !== null ? (int) round($state / 100) : null)
                    ->dehydrateStateUsing(fn ($state) => $state !== null ? ((int) $state) * 100 : null),
                Forms\Components\TextInput::make('sale_price_cents')
                    ->label('Попуст цена (денари)')
                    ->numeric()
                    ->nullable()
                    ->formatStateUsing(fn ($state) => $state !== null ? (int) round($state / 100) : null)
                    ->dehydrateStateUsing(fn ($state) => $state !== null ? ((int) $state) * 100 : null),
                Forms\Components\TextInput::make('stock_qty')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('track_stock')->default(true),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('color')
                    ->searchable(),
                Tables\Columns\TextColumn::make('material')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dimensions')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state) => number_format((int) round($state / 100), 0, '.', ',') . ' ден')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price_cents')
                    ->label('Попуст цена')
                    ->formatStateUsing(fn ($state) => $state ? number_format((int) round($state / 100), 0, '.', ',') . ' ден' : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_qty')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('track_stock')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariant::route('/create'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }
}
