<?php

namespace App\Filament\Resources\IndukAkuns\RelationManagers;

use App\Models\AnakAkun;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubAnakAkunRelationManager extends RelationManager
{
    protected static string $relationship = 'subAnakAkuns';
    protected static ?string $title = 'Sub-Anak Akun';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── Pilih Anak Akun ──────────────────────────────────────────
                Select::make('id_anak_akun')
                    ->label('Anak Akun')
                    ->options(function () {
                        return AnakAkun::where('id_induk_akun', $this->ownerRecord->id)
                            ->orderBy('kode_anak_akun')
                            ->get()
                            ->mapWithKeys(fn($a) => [
                                $a->id => "[{$a->kode_anak_akun}] {$a->nama_anak_akun}",
                            ]);
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),

                // ── Kode — user ketik HANYA suffix (01, 02, dst) ────────────
                // Field ini pakai nama 'kode_sub_anak_akun' supaya masuk $data,
                // tapi isinya masih berupa suffix saja.
                // Kode lengkap dirakit di mutateFormDataUsing.
                TextInput::make('kode_sub_anak_akun')
                    ->label('Kode Sub Anak Akun')
                    ->required()
                    ->maxLength(10)
                    ->prefix(function (Get $get) {
                        $anak = AnakAkun::find($get('id_anak_akun'));
                        return $anak ? $anak->kode_anak_akun . '-' : '—-';
                    })
                    ->hint(function (Get $get) {
                        $anak = AnakAkun::find($get('id_anak_akun'));
                        if (!$anak) return 'Pilih Anak Akun dulu';
                        return "Contoh: 01 → tersimpan sebagai {$anak->kode_anak_akun}-01";
                    })
                    ->placeholder('01')
                    // Saat EDIT: strip prefix dari kode lengkap di DB
                    // agar field hanya menampilkan suffix (02, bukan 2210-02)
                    ->afterStateHydrated(function ($component, $record) {
                        if (!$record) return;
                        $kode  = $record->kode_sub_anak_akun ?? '';
                        $parts = explode('-', $kode);
                        // Ambil bagian terakhir setelah '-'
                        $suffix = count($parts) > 1 ? end($parts) : $kode;
                        $component->state($suffix);
                    })
                    ->live(),

                // ── Nama ─────────────────────────────────────────────────────
                TextInput::make('nama_sub_anak_akun')
                    ->label('Nama Sub Anak Akun')
                    ->required()
                    ->maxLength(255),

                // ── Saldo Normal ──────────────────────────────────────────────
                Select::make('saldo_normal')
                    ->label('Saldo Normal')
                    ->options(['debet' => 'Debet', 'kredit' => 'Kredit'])
                    ->required()
                    ->native(false),

                // ── Status ───────────────────────────────────────────────────
                Select::make('status')
                    ->label('Status')
                    ->options(['aktif' => 'Aktif', 'non-aktif' => 'Non-Aktif'])
                    ->default('aktif')
                    ->required()
                    ->native(false),

                // ── Keterangan ───────────────────────────────────────────────
                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_sub_anak_akun')
            ->columns([
                TextColumn::make('kode_sub_anak_akun')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nama_sub_anak_akun')
                    ->label('Nama Sub Anak Akun')
                    ->searchable(),

                TextColumn::make('anakAkun.nama_anak_akun')
                    ->label('Anak Akun')
                    ->sortable(),

                BadgeColumn::make('saldo_normal')
                    ->label('Saldo Normal')
                    ->colors(['success' => 'debet', 'danger' => 'kredit']),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => match ((string) $state) {
                        'aktif', '1'     => 'Aktif',
                        'non-aktif', '0' => 'Non-Aktif',
                        default          => ucfirst($state),
                    })
                    ->colors([
                        'success' => fn($state) => in_array((string) $state, ['aktif', '1']),
                        'danger'  => fn($state) => in_array((string) $state, ['non-aktif', '0']),
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $anak   = AnakAkun::find($data['id_anak_akun']);
                        $suffix = ltrim($data['kode_sub_anak_akun'] ?? '', '-');

                        // Rakit kode lengkap: 2210-01
                        $data['kode_sub_anak_akun'] = $anak
                            ? $anak->kode_anak_akun . '-' . $suffix
                            : $suffix;

                        $data['created_by'] = Auth::id();
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $anak   = AnakAkun::find($data['id_anak_akun']);
                        $suffix = ltrim($data['kode_sub_anak_akun'] ?? '', '-');

                        // Rakit kode lengkap: 2210-01
                        $data['kode_sub_anak_akun'] = $anak
                            ? $anak->kode_anak_akun . '-' . $suffix
                            : $suffix;

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}