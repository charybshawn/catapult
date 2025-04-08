<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->label('Product')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state) {
                            $item = Item::find($state);
                            $order = $this->getOwnerRecord();
                            $customer_type = $order->customer_type ?? 'retail';
                            $price = $item ? $item->getPriceForCustomerType($customer_type) : 0;
                            $set('price', $price);
                        }
                    })
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Unit Price ($)')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Product')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->money('USD')
                    ->getStateUsing(fn ($record) => $record->subtotal()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $order = $this->getOwnerRecord();
                        $item = Item::find($data['item_id']);
                        
                        if ($item && empty($data['price'])) {
                            $data['price'] = $item->getPriceForCustomerType($order->customer_type);
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 