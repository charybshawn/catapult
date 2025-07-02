<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Get;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Customers';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->description('Basic customer details')
                    ->schema([
                        Forms\Components\Select::make('customer_type_id')
                            ->label('Customer Type')
                            ->relationship('customerType', 'name')
                            ->options(CustomerType::options())
                            ->default(function () {
                                return CustomerType::findByCode('retail')?->id;
                            })
                            ->required()
                            ->reactive()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('business_name')
                            ->maxLength(255)
                            ->placeholder('ABC Grocery Store')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Contact Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Jane Smith'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email 1')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('customer@example.com'),
                        Forms\Components\TextInput::make('cc_email')
                            ->label('CC Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('secondary@example.com'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('(416) 555-1234')
                            ->mask('(999) 999-9999')
                            ->afterStateUpdated(function ($state, $set) {
                                // Clean and format phone number
                                if ($state) {
                                    $cleaned = preg_replace('/[^0-9]/', '', $state);
                                    if (strlen($cleaned) === 10) {
                                        $formatted = sprintf('(%s) %s-%s', 
                                            substr($cleaned, 0, 3),
                                            substr($cleaned, 3, 3),
                                            substr($cleaned, 6)
                                        );
                                        $set('phone', $formatted);
                                    }
                                }
                            }),
                    ])->columns(2),
                
                Forms\Components\Section::make('Wholesale Settings')
                    ->description('Discount settings for wholesale customers')
                    ->schema([
                        Forms\Components\TextInput::make('wholesale_discount_percentage')
                            ->label('Wholesale Discount %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->default(0)
                            ->helperText('Default discount percentage for wholesale orders'),
                    ])
                    ->visible(function (Forms\Get $get) {
                        $customerTypeId = $get('customer_type_id');
                        if (!$customerTypeId) return false;
                        $customerType = CustomerType::find($customerTypeId);
                        return $customerType?->qualifiesForWholesalePricing() ?? false;
                    }),
                
                Forms\Components\Section::make('Delivery Address')
                    ->description('Where orders will be delivered')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Street Address')
                            ->maxLength(255)
                            ->placeholder('123 Main Street')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(100)
                            ->placeholder('Toronto'),
                        Forms\Components\Select::make('province')
                            ->label('Province/State')
                            ->searchable()
                            ->options([
                                // Canadian Provinces
                                'AB' => 'Alberta',
                                'BC' => 'British Columbia',
                                'MB' => 'Manitoba',
                                'NB' => 'New Brunswick',
                                'NL' => 'Newfoundland and Labrador',
                                'NS' => 'Nova Scotia',
                                'NT' => 'Northwest Territories',
                                'NU' => 'Nunavut',
                                'ON' => 'Ontario',
                                'PE' => 'Prince Edward Island',
                                'QC' => 'Quebec',
                                'SK' => 'Saskatchewan',
                                'YT' => 'Yukon',
                                // US States (abbreviated list - can be expanded)
                                'AL' => 'Alabama',
                                'AK' => 'Alaska',
                                'AZ' => 'Arizona',
                                'AR' => 'Arkansas',
                                'CA' => 'California',
                                'CO' => 'Colorado',
                                'CT' => 'Connecticut',
                                'DE' => 'Delaware',
                                'FL' => 'Florida',
                                'GA' => 'Georgia',
                                'HI' => 'Hawaii',
                                'ID' => 'Idaho',
                                'IL' => 'Illinois',
                                'IN' => 'Indiana',
                                'IA' => 'Iowa',
                                'KS' => 'Kansas',
                                'KY' => 'Kentucky',
                                'LA' => 'Louisiana',
                                'ME' => 'Maine',
                                'MD' => 'Maryland',
                                'MA' => 'Massachusetts',
                                'MI' => 'Michigan',
                                'MN' => 'Minnesota',
                                'MS' => 'Mississippi',
                                'MO' => 'Missouri',
                                'MT' => 'Montana',
                                'NE' => 'Nebraska',
                                'NV' => 'Nevada',
                                'NH' => 'New Hampshire',
                                'NJ' => 'New Jersey',
                                'NM' => 'New Mexico',
                                'NY' => 'New York',
                                'NC' => 'North Carolina',
                                'ND' => 'North Dakota',
                                'OH' => 'Ohio',
                                'OK' => 'Oklahoma',
                                'OR' => 'Oregon',
                                'PA' => 'Pennsylvania',
                                'RI' => 'Rhode Island',
                                'SC' => 'South Carolina',
                                'SD' => 'South Dakota',
                                'TN' => 'Tennessee',
                                'TX' => 'Texas',
                                'UT' => 'Utah',
                                'VT' => 'Vermont',
                                'VA' => 'Virginia',
                                'WA' => 'Washington',
                                'WV' => 'West Virginia',
                                'WI' => 'Wisconsin',
                                'WY' => 'Wyoming',
                            ])
                            ->default('ON'),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('Postal/ZIP Code')
                            ->maxLength(20)
                            ->placeholder('M5V 3A8')
                            ->mask(fn (Forms\Get $get) => $get('country') === 'CA' ? 'A9A 9A9' : null),
                        Forms\Components\Select::make('country')
                            ->options([
                                'CA' => 'Canada',
                                'US' => 'United States',
                                'MX' => 'Mexico',
                                // Add more countries as needed
                            ])
                            ->default('CA')
                            ->required()
                            ->reactive(),
                    ])->columns(3),
                
                Forms\Components\Section::make('Login Account')
                    ->description('Optional: Link to a user account for online access')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Linked User Account')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->placeholder('No login account')
                            ->helperText('Link to existing user or use action to create new')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn (?Customer $record) => $record === null || !$record->hasUserAccount()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Customer')
                    ->searchable(['contact_name', 'business_name'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('business_name', $direction)->orderBy('contact_name', $direction);
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ? (new Customer(['phone' => $state]))->formatted_phone : null),
                Tables\Columns\BadgeColumn::make('customerType.name')
                    ->label('Type')
                    ->colors([
                        'success' => fn ($state) => $state === 'Retail',
                        'info' => fn ($state) => $state === 'Wholesale',
                        'warning' => fn ($state) => $state === 'Farmers Market',
                    ]),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('province')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('wholesale_discount_percentage')
                    ->label('Discount %')
                    ->suffix('%')
                    ->toggleable()
                    ->visible(fn () => Customer::whereHas('customerType', function ($q) {
                        $q->whereIn('code', ['wholesale', 'farmers_market']);
                    })->exists()),
                Tables\Columns\IconColumn::make('has_user_account')
                    ->label('Login')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type_id')
                    ->label('Customer Type')
                    ->relationship('customerType', 'name')
                    ->options(CustomerType::options()),
                Tables\Filters\TernaryFilter::make('has_user_account')
                    ->label('Has Login')
                    ->placeholder('All customers')
                    ->trueLabel('With login')
                    ->falseLabel('Without login')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('user_id'),
                        false: fn (Builder $query) => $query->whereNull('user_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('create_login')
                    ->label('Create Login')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->visible(fn (Customer $record) => !$record->hasUserAccount())
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->helperText('Minimum 8 characters'),
                        Forms\Components\Toggle::make('send_credentials')
                            ->label('Email credentials to customer')
                            ->default(true),
                    ])
                    ->action(function (Customer $record, array $data): void {
                        // Create user account for customer
                        $user = User::create([
                            'name' => $record->contact_name,
                            'email' => $record->email,
                            'password' => Hash::make($data['password']),
                            'email_verified_at' => now(),
                        ]);
                        
                        // Assign customer role
                        $user->assignRole('customer');
                        
                        // Link to customer
                        $record->update(['user_id' => $user->id]);
                        
                        // TODO: Send email if requested
                        if ($data['send_credentials']) {
                            // Implement email sending
                        }
                    })
                    ->successNotificationTitle('Login created successfully'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Customer $record) => $record->orders()->count() === 0),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
