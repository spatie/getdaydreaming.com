<?php

namespace App\Filament\Resources\PromptSubmissions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromptSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Feature request')
                    ->schema([
                        TextEntry::make('prompt')
                            ->hiddenLabel()
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->label('Public credit name')->placeholder('Not given'),
                        TextEntry::make('email')->label('Private reply email')->copyable()->placeholder('Not given'),
                        TextEntry::make('reference')->copyable(),
                        TextEntry::make('app_version')->label('App version'),
                        TextEntry::make('app_build')->label('App build'),
                        TextEntry::make('created_at')->label('Received')->dateTime(),
                    ]),
            ]);
    }
}
