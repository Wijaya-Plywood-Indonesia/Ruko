<?php

namespace App\Filament\Resources\ProduksiPakans\RelationManagers;

use App\Models\Komposisi;
use App\Models\ProduksiPakanHasil;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProduksiPakanBahanRelationManager extends RelationManager
{
    protected static string $relationship = 'produksiPakanBahan';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('id_barang')
                    ->label('Pilih Barang')
                    ->relationship(
                        name: 'barang',
                        titleAttribute: 'nama_barang',
                        /** * Eager load 'satuan' agar query efisien saat memunculkan dropdown
                         */
                        modifyQueryUsing: fn(Builder $query) => $query->with('satuan')
                    )
                    ->getOptionLabelFromRecordUsing(function (Model $record) {
                        $satuan = $record->satuan?->nama_satuan ?? '-';
                        return "{$record->nama_barang} ({$satuan})";
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('kuantitas')
                    ->label('Jumlah')
                    ->numeric()
                    ->live()
                    ->required(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ProduksiPakanBahan')
            ->columns([

                TextColumn::make('barang.nama_barang')
                    ->label('Barang / Bahan (Satuan)')
                    ->formatStateUsing(function ($record) {
                        if (!$record->barang) return '—';

                        $namaBarang = $record->barang->nama_barang;
                        $satuan = $record->barang->satuan?->nama_satuan ?? '-';

                        // Menghasilkan format: "Jagung Giling (Kg)"
                        return "{$namaBarang} ({$satuan})";
                    })
                    /**
                     * PENCARIAN GANDA (Nama & Satuan)
                     * Memungkinkan user mencari "Jagung" atau mencari "Kg" langsung di kolom yang sama.
                     */
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('barang', function ($q) use ($search) {
                            $q->where('nama_barang', 'like', "%{$search}%")
                                ->orWhereHas('satuan', function ($sq) use ($search) {
                                    $sq->where('nama_satuan', 'like', "%{$search}%");
                                });
                        });
                    }),

                TextColumn::make('kuantitas')
                    ->label('Jumlah')
                    ->alignCenter(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->default('-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // CreateAction::make()->label('Tambah Bahan'),
            ])
            ->recordActions([
                Action::make('updateKeterangan')
                    ->visible(fn(RelationManager $livewire) => Auth::user()->hasRole('super_admin') || $livewire->getOwnerRecord()->validated_by === null)
                    // Logika Label Dinamis
                    ->label(fn($record) => $record->keterangan ? 'Perbarui Keterangan' : 'Tambah Keterangan')
                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                    ->color(fn($record) => $record->keterangan ? 'info' : 'gray')

                    // Membuat Popup (Modal)
                    ->modalHeading('Keterangan Produksi')
                    ->modalSubmitActionLabel('Simpan')
                    ->form([
                        Textarea::make('keterangan')
                            ->label('Isi Keterangan')
                            ->placeholder('Tulis keterangan di sini...')
                            ->rows(5)
                            ->required(),
                    ])
                    // Mengisi form dengan data lama saat popup terbuka
                    ->fillForm(fn($record): array => [
                        'keterangan' => $record->keterangan,
                    ])
                    // Logika Simpan
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'keterangan' => $data['keterangan'],
                        ]);

                        Notification::make()
                            ->title('Keterangan berhasil disimpan')
                            ->success()
                            ->send();
                    }),
                EditAction::make()->visible(fn(RelationManager $livewire) => Auth::user()->hasRole('super_admin') || $livewire->getOwnerRecord()->validated_by === null),
                DeleteAction::make()->visible(fn(RelationManager $livewire) => Auth::user()->hasRole('super_admin') || $livewire->getOwnerRecord()->validated_by === null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
