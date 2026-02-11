<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            \Filament\Forms\Components\FileUpload::make('path')
                ->label('Image')
                ->image()
                ->directory('products')
                ->imageEditor()
                ->required(),

            \Filament\Forms\Components\TextInput::make('alt')->maxLength(255)->nullable(),

            \Filament\Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('path')->label('Image'),
                \Filament\Tables\Columns\TextColumn::make('alt')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('sort_order')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('sort_order');
    }
}
