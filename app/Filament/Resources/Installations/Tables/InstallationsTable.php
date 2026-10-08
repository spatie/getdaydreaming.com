<?php

namespace App\Filament\Resources\Installations\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstallationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->columns([
                TextColumn::make('mac_name')
                    ->label('Mac name')
                    ->placeholder('Not reported')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('app_version')
                    ->label('App version')
                    ->badge()
                    ->sortable(),
                TextColumn::make('macos_version')
                    ->label('macOS')
                    ->sortable(),
                TextColumn::make('architecture')
                    ->label('Chip')
                    ->sortable(),
                TextColumn::make('last_seen_at')
                    ->label('Last seen')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('report_count')
                    ->label('Reports')
                    ->sortable(),
                TextColumn::make('first_seen_at')
                    ->label('First seen')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
