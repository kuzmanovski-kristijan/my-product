<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Appointments';
    protected static ?string $modelLabel = 'Appointment';
    protected static ?string $pluralModelLabel = 'Appointments';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Appointment')
                ->schema([
                    Forms\Components\Select::make('store_id')
                        ->label('Store')
                        ->relationship('store', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('name'))
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Starts at')
                        ->seconds(false)
                        ->required(),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Ends at')
                        ->seconds(false)
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'booked' => 'booked',
                            'canceled' => 'canceled',
                            'completed' => 'completed',
                        ])
                        ->required()
                        ->default('booked'),

                    Forms\Components\TextInput::make('full_name')
                        ->label('Full name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->required()
                        ->maxLength(50),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255)
                        ->nullable(),

                    Forms\Components\Textarea::make('note')
                        ->columnSpanFull()
                        ->maxLength(2000)
                        ->nullable(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['store:id,name,city']))
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('store.city')
                    ->label('City')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime('H:i')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'booked' => 'warning',
                        'completed' => 'success',
                        'canceled' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(fn () => Store::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->options([
                        'booked' => 'booked',
                        'completed' => 'completed',
                        'canceled' => 'canceled',
                    ]),

                Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('From'),
                        Forms\Components\DatePicker::make('date_to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('starts_at', '>=', $date))
                            ->when($data['date_to'] ?? null, fn (Builder $q, $date) => $q->whereDate('starts_at', '<=', $date));
                    }),

                Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query) => $query->whereDate('starts_at', now()->toDateString())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $record) => $record->status === 'booked')
                    ->action(function (Appointment $record) {
                        $record->update(['status' => 'completed']);
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $record) => $record->status === 'booked')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Cancel note (optional)')
                            ->maxLength(2000),
                    ])
                    ->action(function (Appointment $record, array $data) {
                        $record->update([
                            'status' => 'canceled',
                            'note' => trim(($record->note ? $record->note . "\n\n" : '') . 'Canceled: ' . ($data['note'] ?? '')) ?: $record->note,
                        ]);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
