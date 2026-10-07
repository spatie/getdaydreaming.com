<?php

namespace App\Filament\Resources\PromptSubmissions;

use App\Filament\Resources\PromptSubmissions\Pages\ListPromptSubmissions;
use App\Filament\Resources\PromptSubmissions\Pages\ViewPromptSubmission;
use App\Filament\Resources\PromptSubmissions\Schemas\PromptSubmissionInfolist;
use App\Filament\Resources\PromptSubmissions\Tables\PromptSubmissionsTable;
use App\Models\PromptSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PromptSubmissionResource extends Resource
{
    protected static ?string $model = PromptSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Feedback';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function infolist(Schema $schema): Schema
    {
        return PromptSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PromptSubmissionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromptSubmissions::route('/'),
            'view' => ViewPromptSubmission::route('/{record}'),
        ];
    }
}
