<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Support\SlideOverConfigurations;
use App\Filament\Traits\HasConsistentSlideOvers;
use App\Filament\Traits\CsvExportAction;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends BaseResource
{
    use HasConsistentSlideOvers, CsvExportAction;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Employees';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee Information')
                    ->description('Basic employee details')
                    ->schema([
                        static::getNameField('Full Name'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                    ])->columns(3),
                
                Forms\Components\Section::make('Access & Permissions')
                    ->description('Configure employee access level')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Employee Role')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable()
                            ->options([
                                'user' => 'User (Basic Access)',
                                'manager' => 'Manager (Enhanced Access)',
                                'admin' => 'Admin (Full Access)',
                            ])
                            ->default(['user'])
                            ->required()
                            ->helperText('Select the appropriate access level'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Leave blank to keep existing password'),
                        Forms\Components\Toggle::make('email_verified')
                            ->label('Email Verified')
                            ->default(true)
                            ->dehydrateStateUsing(fn ($state) => $state ? now() : null)
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($component, $state, $record) => 
                                $component->state($record?->email_verified_at !== null)
                            ),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'customer')))
            ->columns([
                static::getNameColumn(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'user' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('timeCards_count')
                    ->label('Time Cards')
                    ->counts('timeCards')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name', fn (Builder $query) => $query->whereNot('name', 'customer'))
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->tooltip('View record'),
                    Tables\Actions\EditAction::make()
                        ->tooltip('Edit record'),
                    Tables\Actions\DeleteAction::make()
                        ->tooltip('Delete record')
                        ->visible(fn (User $record) => auth()->user()->hasRole('admin') && $record->id !== auth()->id()),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->headerActions([
                static::getCsvExportAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('admin')),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
        ];
    }
    
    /**
     * Define CSV export columns for Users - uses automatic detection from schema
     * Optionally add relationship columns manually
     */
    protected static function getCsvExportColumns(): array
    {
        // Get automatically detected columns from database schema
        $autoColumns = static::getColumnsFromSchema();
        
        // Add relationship columns
        return static::addRelationshipColumns($autoColumns, [
            'roles' => ['name'],
            'timeCards' => ['date', 'hours_worked', 'overtime_hours'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['roles'];
    }
    
    /**
     * Custom query for CSV export - exclude customers
     */
    protected static function getTableQuery(): Builder
    {
        return static::getModel()::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'customer'))
            ->with(['roles']);
    }
} 