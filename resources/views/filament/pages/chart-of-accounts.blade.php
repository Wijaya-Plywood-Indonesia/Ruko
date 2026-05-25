<x-filament-panels::page>
    <style>
        /* ===== COA DESIGN SYSTEM - BASE (LIGHT MODE) ===== */
        .coa-root {
            font-family: 'Figtree', 'Nunito', sans-serif;
            color: #1e293b;
        }

        /* SEARCH */
        .coa-search-wrap {
            position: relative;
            margin-bottom: 1rem;
        }

        .coa-search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: #94a3b8;
            pointer-events: none;
        }

        .coa-search {
            width: 100%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            color: #334155;
            font-size: 0.875rem;
            outline: none;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            transition: all 0.2s;
        }

        .coa-search:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgb(245 158 11 / 0.15);
        }

        .coa-search::placeholder {
            color: #94a3b8;
        }

        /* CONTROLS */
        .coa-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 0.875rem;
        }

        .coa-btn-expand {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.45rem 0.9rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            background: rgb(245 158 11 / 0.1);
            border: 1px solid rgb(245 158 11 / 0.25);
            color: #d97706;
            transition: all 0.15s;
        }

        .coa-btn-expand:hover {
            background: rgb(245 158 11 / 0.18);
        }

        .coa-btn-collapse {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.45rem 0.9rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #64748b;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            transition: all 0.15s;
        }

        .coa-btn-collapse:hover {
            border-color: #94a3b8;
            color: #334155;
            background: #f8fafc;
        }

        .coa-stat {
            margin-left: auto;
            font-size: 0.8rem;
            font-weight: 700;
            color: #d97706;
            background: rgb(245 158 11 / 0.1);
            border: 1px solid rgb(245 158 11 / 0.2);
            padding: 0.4rem 0.875rem;
            border-radius: 2rem;
        }

        /* LEGEND */
        .coa-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            padding: 0.625rem 1rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
            margin-bottom: 1.25rem;
            font-size: 0.75rem;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.03);
        }

        .coa-legend-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            color: #475569;
            font-weight: 500;
        }

        .coa-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        /* PILLS (DEBET/KREDIT) */
        .b-de {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            padding: 0.12rem 0.4rem;
            border-radius: 0.25rem;
            background: rgb(245 158 11 / 0.15);
            color: #b45309;
            border: 1px solid rgb(245 158 11 / 0.3);
        }

        .b-kr {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            padding: 0.12rem 0.4rem;
            border-radius: 0.25rem;
            background: rgb(244 63 94 / 0.15);
            color: #e11d48;
            border: 1px solid rgb(244 63 94 / 0.3);
        }

        /* INDUK */
        .row-induk {
            border-left: 3px solid #f59e0b;
            background: linear-gradient(90deg, rgb(245 158 11 / 0.06) 0%, #ffffff 50%);
            border: 1px solid #f1f5f9;
            border-left-width: 3px;
            border-radius: 0 0.625rem 0.625rem 0;
            margin-bottom: 0.625rem;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.03);
        }

        .row-induk-hd {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            cursor: pointer;
            user-select: none;
            gap: 0.625rem;
        }

        .row-induk-hd:hover {
            background: rgb(245 158 11 / 0.04);
        }

        .kode-induk {
            font-size: 0.82rem;
            font-weight: 800;
            color: #d97706;
            min-width: 3rem;
            letter-spacing: 0.02em;
        }

        .nama-induk {
            flex: 1;
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* ANAK */
        .anak-list {
            padding: 0 0.625rem 0.625rem 1.5rem;
        }

        .anak-list-inner {
            border-top: 1px solid #e2e8f0;
            padding-top: 0.375rem;
        }

        .row-anak {
            border-left: 2px solid #38bdf8;
            background: rgb(56 189 248 / 0.03);
            border-radius: 0 0.5rem 0.5rem 0;
            margin-bottom: 0.375rem;
            overflow: hidden;
        }

        .row-anak:hover {
            background: rgb(56 189 248 / 0.08);
        }

        .row-anak-hd {
            display: flex;
            align-items: center;
            padding: 0.55rem 0.875rem;
            cursor: pointer;
            user-select: none;
            gap: 0.5rem;
        }

        .kode-anak {
            font-size: 0.78rem;
            font-weight: 700;
            color: #0284c7;
            min-width: 2.75rem;
        }

        .nama-anak {
            flex: 1;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
        }

        /* SUB ANAK */
        .row-sub {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.4rem 0.875rem 0.4rem 2rem;
            border-top: 1px solid #f1f5f9;
        }

        .row-sub:hover {
            background: #f8fafc;
        }

        .sub-bullet {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #22c55e;
            flex-shrink: 0;
        }

        .kode-sub {
            font-size: 0.72rem;
            font-weight: 600;
            color: #16a34a;
            min-width: 4rem;
            font-variant-numeric: tabular-nums;
        }

        .nama-sub {
            flex: 1;
            font-size: 0.8125rem;
            color: #475569;
        }

        /* CHILD REKURSIF */
        .row-child {
            border-left: 2px solid #c084fc;
            background: rgb(192 132 252 / 0.04);
            border-radius: 0 0.5rem 0.5rem 0;
            margin: 0.25rem 0 0.25rem 0.875rem;
            overflow: hidden;
        }

        .row-child-hd {
            display: flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            gap: 0.5rem;
        }

        .kode-child {
            font-size: 0.75rem;
            font-weight: 700;
            color: #9333ea;
            min-width: 2.75rem;
        }

        .nama-child {
            flex: 1;
            font-size: 0.85rem;
            font-weight: 500;
            color: #6b21a8;
        }

        /* CHEVRON & PILLS */
        .chev {
            width: 14px;
            height: 14px;
            color: #94a3b8;
            transition: transform 0.18s, color 0.18s;
            flex-shrink: 0;
        }

        .chev.open-induk {
            transform: rotate(90deg);
            color: #d97706;
        }

        .chev.open-anak {
            transform: rotate(90deg);
            color: #0284c7;
        }

        .cpill {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 2rem;
        }

        .cpill-induk {
            background: rgb(245 158 11 / 0.15);
            color: #d97706;
        }

        .cpill-anak {
            background: rgb(56 189 248 / 0.15);
            color: #0284c7;
        }

        .coa-empty {
            text-align: center;
            padding: 4rem 1rem;
            color: #64748b;
        }


        /* ========================================================= */
        /* ===== DARK MODE OVERRIDES (Aktif otomatis di Filament) == */
        /* ========================================================= */
        .dark .coa-root {
            color: #e2e8f0;
        }

        .dark .coa-search {
            background: #0f172a;
            border-color: #1e293b;
            color: #e2e8f0;
        }

        .dark .coa-search:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgb(245 158 11 / 0.15);
        }

        .dark .coa-search::placeholder {
            color: #64748b;
        }

        .dark .coa-search-icon {
            color: #64748b;
        }

        .dark .coa-btn-collapse {
            background: #0f172a;
            border-color: #1e293b;
            color: #64748b;
        }

        .dark .coa-btn-collapse:hover {
            border-color: #334155;
            color: #e2e8f0;
            background: #1e293b;
        }

        .dark .coa-legend {
            background: #0f172a;
            border-color: #1e293b;
        }

        .dark .coa-legend-item {
            color: #94a3b8;
        }

        .dark .b-de {
            color: #fbbf24;
        }

        .dark .b-kr {
            color: #fb7185;
        }

        .dark .row-induk {
            background: linear-gradient(90deg, rgb(245 158 11 / 0.07) 0%, transparent 70%);
            border-color: #1e293b;
        }

        .dark .row-induk-hd:hover {
            background: rgb(245 158 11 / 0.05);
        }

        .dark .kode-induk {
            color: #fbbf24;
        }

        .dark .nama-induk {
            color: #e2e8f0;
        }

        .dark .cpill-induk {
            color: #fbbf24;
        }

        .dark .anak-list-inner {
            border-top-color: rgb(30 41 59 / 0.5);
        }

        .dark .row-anak {
            background: rgb(56 189 248 / 0.04);
        }

        .dark .row-anak:hover {
            background: rgb(56 189 248 / 0.07);
        }

        .dark .kode-anak {
            color: #38bdf8;
        }

        .dark .nama-anak {
            color: #cbd5e1;
        }

        .dark .cpill-anak {
            color: #7dd3fc;
        }

        .dark .row-sub {
            border-top-color: rgb(30 41 59 / 0.5);
        }

        .dark .row-sub:hover {
            background: rgb(255 255 255 / 0.02);
        }

        .dark .kode-sub {
            color: #4ade80;
        }

        .dark .nama-sub {
            color: #94a3b8;
        }

        .dark .row-child {
            background: rgb(192 132 252 / 0.04);
        }

        .dark .kode-child {
            color: #c084fc;
        }

        .dark .nama-child {
            color: #a78bfa;
        }

        .dark .chev.open-induk {
            color: #fbbf24;
        }

        .dark .chev.open-anak {
            color: #38bdf8;
        }

        .dark .coa-empty {
            color: #475569;
        }
    </style>

    <div class="coa-root">

        {{-- SEARCH --}}
        <div class="coa-search-wrap">
            <svg class="coa-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" class="coa-search" placeholder="Cari kode atau nama akun..."
                wire:model.live.debounce.300ms="search" />
        </div>

        {{-- CONTROLS --}}
        <div class="coa-controls">
            <button wire:click="expandAll" class="coa-btn-expand">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
                Expand Semua
            </button>
            <button wire:click="collapseAll" class="coa-btn-collapse">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 9V4.5M9 9H4.5m4.5 0L3.75 3.75M15 9h4.5M15 9V4.5m0 4.5l5.25-5.25M9 15v4.5M9 15H4.5m4.5 0l-5.25 5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                </svg>
                Collapse Semua
            </button>
            <div class="coa-stat">
                {{ $this->getTotalAkunCount() }} induk &nbsp;&bull;&nbsp; {{ $this->getTotalAnakCount() }} akun
            </div>
        </div>

        {{-- LEGEND --}}
        <div class="coa-legend">
            <div class="coa-legend-item"><span class="coa-dot" style="background:#f59e0b;"></span> Induk Akun</div>
            <div class="coa-legend-item"><span class="coa-dot" style="background:#38bdf8;"></span> Anak Akun</div>
            <div class="coa-legend-item"><span class="coa-dot" style="background:#4ade80;"></span> Sub Anak</div>
            <div class="coa-legend-item"><span class="coa-dot" style="background:#c084fc;"></span> Sub-Sub Anak</div>
            <div style="margin-left:auto;display:flex;gap:0.5rem;">
                <span class="b-de">DE Debet</span>
                <span class="b-kr">KR Kredit</span>
            </div>
        </div>

        {{-- TREE --}}
        @php $data = $this->getChartData(); @endphp

        @if($data->isEmpty())
        <div class="coa-empty">
            <p style="font-size:1rem;font-weight:600;">Tidak ada akun ditemukan</p>
            <p style="font-size:0.8125rem;margin-top:0.25rem;">Coba ubah kata kunci pencarian</p>
        </div>
        @else
        @foreach($data as $induk)
        @php
        $indukOpen = in_array($induk->id, $expandedInduk);
        $anakList = $induk->anakAkuns ?? collect();
        @endphp
        <div class="row-induk">
            <div class="row-induk-hd" wire:click="toggleInduk({{ $induk->id }})">
                <svg class="chev {{ $indukOpen ? 'open-induk' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                </svg>
                <span class="kode-induk">{{ $induk->kode_induk_akun }}</span>
                <span class="nama-induk">{{ $induk->nama_induk_akun }}</span>
                @if($anakList->count())
                <span class="cpill cpill-induk">{{ $anakList->count() }} akun</span>
                @endif
                @if(strtolower($induk->saldo_normal) === 'debet')
                <span class="b-de">DE</span>
                @else
                <span class="b-kr">KR</span>
                @endif
            </div>

            @if($indukOpen && $anakList->count())
            <div class="anak-list">
                <div class="anak-list-inner">
                    @foreach($anakList as $anak)
                    @php
                    $anakOpen = in_array($anak->id, $expandedAnak);
                    $subList = $anak->subAnakAkuns ?? collect();
                    $childList = $anak->children ?? collect();
                    $hasKids = $subList->count() > 0 || $childList->count() > 0;
                    @endphp
                    <div class="row-anak">
                        <div class="row-anak-hd"
                            @if($hasKids) wire:click="toggleAnak({{ $anak->id }})" @endif
                            style="{{ !$hasKids ? 'cursor:default;' : '' }}">
                            @if($hasKids)
                            <svg class="chev {{ $anakOpen ? 'open-anak' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                            @else
                            <span style="display:inline-block;width:14px;"></span>
                            @endif
                            <span class="kode-anak">{{ $anak->kode_anak_akun }}</span>
                            <span class="nama-anak">{{ $anak->nama_anak_akun }}</span>
                            @if($hasKids)
                            <span class="cpill cpill-anak">{{ $subList->count() + $childList->count() }}</span>
                            @endif
                            @if(strtolower($anak->saldo_normal) === 'debet')
                            <span class="b-de">DE</span>
                            @else
                            <span class="b-kr">KR</span>
                            @endif
                        </div>

                        @if($anakOpen)
                        {{-- Children rekursif --}}
                        @foreach($childList as $child)
                        <div class="row-child">
                            <div class="row-child-hd">
                                <span class="kode-child">{{ $child->kode_anak_akun }}</span>
                                <span class="nama-child">{{ $child->nama_anak_akun }}</span>
                                @if(strtolower($child->saldo_normal) === 'debet')
                                <span class="b-de" style="margin-left:auto;">DE</span>
                                @else
                                <span class="b-kr" style="margin-left:auto;">KR</span>
                                @endif
                            </div>
                            @foreach($child->subAnakAkuns ?? [] as $sc)
                            <div class="row-sub" style="padding-left:2.5rem;">
                                <span class="sub-bullet" style="background:#c084fc;"></span>
                                <span class="kode-sub" style="color:#c084fc;">{{ $sc->kode_sub_anak_akun }}</span>
                                <span class="nama-sub">{{ $sc->nama_sub_anak_akun }}</span>
                                @if(strtolower($sc->saldo_normal) === 'debet')
                                <span class="b-de" style="margin-left:auto;">DE</span>
                                @else
                                <span class="b-kr" style="margin-left:auto;">KR</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endforeach

                        {{-- Sub Anak langsung --}}
                        @foreach($subList as $sub)
                        <div class="row-sub">
                            <span class="sub-bullet"></span>
                            <span class="kode-sub">{{ $sub->kode_sub_anak_akun }}</span>
                            <span class="nama-sub">{{ $sub->nama_sub_anak_akun }}</span>
                            @if(strtolower($sub->saldo_normal) === 'debet')
                            <span class="b-de" style="margin-left:auto;">DE</span>
                            @else
                            <span class="b-kr" style="margin-left:auto;">KR</span>
                            @endif
                        </div>
                        @endforeach
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforeach
        @endif

    </div>
</x-filament-panels::page>