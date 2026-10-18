<?php

namespace App\Filament\Resources\Transactions;

use App\Actions\Transactions\VoidTransaction;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Pages\ViewTransaction;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?string $modelLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi';

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
                TextEntry::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y'),
                TextEntry::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TransactionType ? $state->label() : (string) $state),
                TextEntry::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($record) => $record->amount->formatted()),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof TransactionStatus ? $state->value : (string) $state) {
                        'posted' => 'success',
                        'voided' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof TransactionStatus ? $state->label() : (string) $state),
                TextEntry::make('category.name')
                    ->label('Kategori')
                    ->default('-'),
                TextEntry::make('sourceAccount.name')
                    ->label('Akun Sumber')
                    ->default('-'),
                TextEntry::make('destinationAccount.name')
                    ->label('Akun Tujuan')
                    ->default('-'),
                TextEntry::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                TextEntry::make('notes')
                    ->label('Catatan Tambahan')
                    ->default('-')
                    ->columnSpanFull(),
                TextEntry::make('void_reason')
                    ->label('Alasan Pembatalan (Void)')
                    ->visible(fn ($record) => $record->status === TransactionStatus::Voided)
                    ->columnSpanFull(),
                TextEntry::make('voided_at')
                    ->label('Waktu Pembatalan')
                    ->dateTime('d/m/Y H:i:s')
                    ->visible(fn ($record) => $record->status === TransactionStatus::Voided),
                TextEntry::make('client_request_id')
                    ->label('Request ID Idempotensi')
                    ->default('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TransactionType ? $state->label() : (string) $state),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($record) => $record->amount->formatted())
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->default('-')
                    ->searchable(),
                TextColumn::make('sourceAccount.name')
                    ->label('Akun Sumber')
                    ->default('-')
                    ->searchable(),
                TextColumn::make('destinationAccount.name')
                    ->label('Akun Tujuan')
                    ->default('-')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(25),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof TransactionStatus ? $state->value : (string) $state) {
                        'posted' => 'success',
                        'voided' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof TransactionStatus ? $state->label() : (string) $state),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        TransactionType::Expense->value => 'Pengeluaran',
                        TransactionType::Income->value => 'Pemasukan',
                        TransactionType::Transfer->value => 'Transfer',
                        TransactionType::Adjustment->value => 'Penyesuaian',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        TransactionStatus::Posted->value => 'Tercatat',
                        TransactionStatus::Voided->value => 'Dibatalkan (Void)',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('void')
                    ->label('Batalkan (Void)')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Transaksi (Void)')
                    ->modalDescription('Transaksi ini akan dibatalkan secara permanen melalui domain logic dan dicatat dalam audit log. Tindakan ini tidak dapat diurungkan.')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(3),
                    ])
                    ->visible(fn (Transaction $record): bool => $record->status === TransactionStatus::Posted)
                    ->action(function (Transaction $record, array $data): void {
                        app(VoidTransaction::class)->handle(auth()->user(), $record, $data['reason']);

                        Notification::make()
                            ->title('Transaksi berhasil dibatalkan (void)')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'view' => ViewTransaction::route('/{record}'),
        ];
    }
}
