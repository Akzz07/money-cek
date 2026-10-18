<?php

namespace App\Filament\Resources\Categories;

use App\Actions\Categories\ArchiveCategory;
use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\ViewCategory;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Kategori';

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategori';

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
                TextEntry::make('name')
                    ->label('Nama Kategori'),
                TextEntry::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof CategoryType ? $state->label() : (string) $state),
                ColorEntry::make('color')
                    ->label('Warna'),
                TextEntry::make('icon')
                    ->label('Ikon'),
                IconEntry::make('is_system')
                    ->label('Kategori Bawaan Sistem')
                    ->boolean(),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof CategoryStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'archived' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof CategoryStatus ? $state->label() : (string) $state),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof CategoryType ? $state->label() : (string) $state),
                ColorColumn::make('color')
                    ->label('Warna'),
                TextColumn::make('icon')
                    ->label('Ikon'),
                IconColumn::make('is_system')
                    ->label('Sistem')
                    ->boolean(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof CategoryStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'archived' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof CategoryStatus ? $state->label() : (string) $state),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        CategoryType::Expense->value => 'Pengeluaran',
                        CategoryType::Income->value => 'Pemasukan',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        CategoryStatus::Active->value => 'Aktif',
                        CategoryStatus::Archived->value => 'Diarsipkan',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('archive')
                    ->label('Arsipkan')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Arsipkan Kategori')
                    ->modalDescription('Kategori ini akan dinonaktifkan (diarsipkan). Riwayat transaksi sebelumnya tetap aman.')
                    ->visible(fn (Category $record): bool => $record->status === CategoryStatus::Active && ! $record->is_system)
                    ->action(function (Category $record): void {
                        app(ArchiveCategory::class)->handle($record);

                        Notification::make()
                            ->title('Kategori berhasil diarsipkan')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'view' => ViewCategory::route('/{record}'),
        ];
    }
}
