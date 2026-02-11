<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('to_status')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('from_status')->label('From')->badge(),
                \Filament\Tables\Columns\TextColumn::make('to_status')->label('To')->badge()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('note')->limit(60),
                \Filament\Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->paginated(false);
    }
}
