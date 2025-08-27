<?php

namespace App\Filament\Resources\RestaurantResource\RelationManagers;

use App\Models\MenuItem;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class MenuItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'menuItems';

    protected static ?string $recordTitleAttribute = 'name';

    // ✅ Must be public (not protected) to match parent
    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required(),
            Forms\Components\Textarea::make('description')
                ->required(),
            Forms\Components\TextInput::make('price')
                ->numeric()
                ->required(),
            Forms\Components\FileUpload::make('image')
                ->image(),
        ]);
    }

    // ✅ Same here — must be public
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')->limit(50),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}