<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $modelLabel = 'Audit Log';

    protected static ?string $pluralModelLabel = 'Audit Log';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')
                    ->label('Waktu Kejadian')
                    ->dateTime('d/m/Y H:i:s'),
                TextEntry::make('actor.name')
                    ->label('Aktor')
                    ->default('Sistem'),
                TextEntry::make('action')
                    ->label('Aksi')
                    ->badge(),
                TextEntry::make('entity_type')
                    ->label('Tipe Entitas'),
                TextEntry::make('entity_id')
                    ->label('ID Entitas'),
                TextEntry::make('request_id')
                    ->label('Client Request ID')
                    ->default('-'),
                TextEntry::make('metadata')
                    ->label('Metadata')
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string) $state)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label('Aktor')
                    ->default('Sistem')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->searchable(),
                TextColumn::make('entity_type')
                    ->label('Entitas')
                    ->searchable(),
                TextColumn::make('entity_id')
                    ->label('ID Entitas')
                    ->sortable(),
                TextColumn::make('request_id')
                    ->label('Request ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
