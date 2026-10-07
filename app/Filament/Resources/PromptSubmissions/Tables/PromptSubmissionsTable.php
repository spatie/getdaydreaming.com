<?php

namespace App\Filament\Resources\PromptSubmissions\Tables;

use App\Models\PromptSubmission;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromptSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('prompt')
                    ->label('Feature request')
                    ->description(fn (PromptSubmission $record): ?string => $record->name)
                    ->wrap()
                    ->lineClamp(3)
                    ->limit(240)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('app_version')
                    ->label('App version')
                    ->badge()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Private reply email')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('View'),
                DeleteAction::make()->iconButton()->tooltip('Delete'),
            ]);
    }
}
