<?php

namespace App\Filament\Resources\Accounts;

use App\Actions\Accounts\ArchiveAccount;
use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Accounts\Pages\ViewAccount;
use App\Models\Account;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $navigationLabel = 'Akun Rekening';

    protected static ?string $modelLabel = 'Akun Rekening';

    protected static ?string $pluralModelLabel = 'Akun Rekening';

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
                    ->label('Nama Akun'),
                TextEntry::make('type')
                    ->label('Jenis Rekening')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof AccountType ? $state->label() : (string) $state),
                TextEntry::make('currency')
                    ->label('Mata Uang'),
                TextEntry::make('current_balance')
                    ->label('Saldo Terkini')
                    ->formatStateUsing(fn ($record) => $record->current_balance->formatted()),
                TextEntry::make('initial_balance')
                    ->label('Saldo Awal')
                    ->formatStateUsing(fn ($record) => $record->initial_balance->formatted()),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof AccountStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'archived' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof AccountStatus ? $state->label() : (string) $state),
                IconEntry::make('include_in_safe_to_spend')
                    ->label('Masuk Kalkulasi Safe-to-Spend')
                    ->boolean(),
                TextEntry::make('institution_name')
                    ->label('Institusi / Bank')
                    ->default('-'),
                TextEntry::make('account_number')
                    ->label('Nomor Rekening')
                    ->default('-'),
                TextEntry::make('notes')
                    ->label('Catatan')
                    ->default('-')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof AccountType ? $state->label() : (string) $state),
                TextColumn::make('current_balance')
                    ->label('Saldo')
                    ->formatStateUsing(fn ($record) => $record->current_balance->formatted())
                    ->sortable(),
                TextColumn::make('institution_name')
                    ->label('Institusi')
                    ->default('-')
                    ->searchable(),
                IconColumn::make('include_in_safe_to_spend')
                    ->label('Safe to Spend')
                    ->boolean(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof AccountStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'archived' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof AccountStatus ? $state->label() : (string) $state),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        AccountStatus::Active->value => 'Aktif',
                        AccountStatus::Archived->value => 'Diarsipkan',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('archive')
                    ->label('Arsipkan')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Arsipkan Akun')
                    ->modalDescription('Akun ini akan dinonaktifkan (diarsipkan). Seluruh riwayat transaksi historis tetap terjaga.')
                    ->visible(fn (Account $record): bool => $record->status === AccountStatus::Active)
                    ->action(function (Account $record): void {
                        app(ArchiveAccount::class)->handle($record);

                        Notification::make()
                            ->title('Akun berhasil diarsipkan')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'view' => ViewAccount::route('/{record}'),
        ];
    }
}
