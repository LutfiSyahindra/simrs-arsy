@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .fisio-page {
            --fisio-ink: #122033;
            --fisio-main: #0f766e;
            --fisio-blue: #2563eb;
            --fisio-rose: #be123c;
            --fisio-line: #e2e8f0;
            --fisio-muted: #64748b;
            --fisio-soft: #f8fafc;
            color: var(--fisio-ink);
        }

        .fisio-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .fisio-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #12343b 0%, #0f766e 58%, #2563eb 100%);
            border-radius: 10px;
            box-shadow: 0 18px 38px rgba(15, 23, 42, .16);
            color: #fff;
            display: flex;
            gap: 18px;
            justify-content: space-between;
            margin-top: 14px;
            overflow: hidden;
            padding: 20px;
        }

        .fisio-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            min-width: 0;
        }

        .fisio-hero-icon {
            align-items: center;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 8px;
            display: flex;
            flex: 0 0 auto;
            font-size: 30px;
            height: 56px;
            justify-content: center;
            width: 56px;
        }

        .fisio-eyebrow,
        .fisio-control-box label {
            color: #ccfbf1;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .fisio-title {
            font-size: 24px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .fisio-description {
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            line-height: 1.55;
            margin: 0;
            max-width: 780px;
        }

        .fisio-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
        }

        .fisio-control-box {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 8px;
            padding: 12px;
        }

        .fisio-control-box label {
            display: block;
            margin-bottom: 6px;
        }

        .fisio-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .fisio-period-picker .form-control {
            border: 0;
            height: 39px;
            text-align: center;
        }

        .fisio-period-step,
        .fisio-period-current {
            align-items: center;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            font-size: 18px;
            height: 39px;
            justify-content: center;
            padding: 0;
        }

        .fisio-period-current {
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            margin-top: 8px;
            width: 100%;
        }

        .fisio-type-tabs {
            background: #fff;
            border: 1px solid var(--fisio-line);
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 8px;
        }

        .fisio-type-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 8px;
            color: var(--fisio-muted);
            display: flex;
            flex: 1;
            gap: 10px;
            padding: 11px 14px;
            text-align: left;
        }

        .fisio-type-tab i {
            font-size: 23px;
        }

        .fisio-type-tab strong,
        .fisio-type-tab small {
            display: block;
        }

        .fisio-type-tab small {
            font-size: 11px;
        }

        .fisio-type-tab.active {
            background: #ecfdf5;
            box-shadow: inset 0 0 0 1px #99f6e4;
            color: var(--fisio-main);
        }

        .fisio-panel {
            background: #fff;
            border: 1px solid var(--fisio-line);
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .fisio-panel-head,
        .fisio-filter-bar {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .fisio-panel-head {
            border-bottom: 1px solid #eef2f7;
        }

        .fisio-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .fisio-panel-subtitle {
            color: var(--fisio-muted);
            font-size: 12px;
            margin-top: 2px;
        }

        .fisio-panel-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .fisio-btn {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            height: 38px;
            justify-content: center;
            padding: 0 12px;
        }

        .fisio-btn-primary {
            background: #0f766e;
            border: 1px solid #0f766e;
            color: #fff;
        }

        .fisio-btn-primary:hover {
            background: #115e59;
            color: #fff;
        }

        .fisio-btn-light {
            background: #f8fafc;
            border: 1px solid var(--fisio-line);
            color: #334155;
        }

        .fisio-summary-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            padding: 15px 17px;
        }

        .fisio-summary-card,
        .fisio-mini-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        .fisio-summary-card.total,
        .fisio-mini-card.total {
            background: #ecfdf5;
            border-color: #99f6e4;
        }

        .fisio-summary-label,
        .fisio-mini-label {
            color: var(--fisio-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .fisio-summary-value,
        .fisio-mini-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
            margin-top: 5px;
            overflow-wrap: anywhere;
        }

        .fisio-formula-strip {
            border-top: 1px solid #eef2f7;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 15px 17px;
        }

        .fisio-info-row {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 11px 12px;
        }

        .fisio-info-label {
            color: var(--fisio-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .fisio-info-note {
            color: var(--fisio-muted);
            font-size: 11px;
            margin-top: 2px;
        }

        .fisio-info-value {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
        }

        .fisio-table-wrap {
            padding: 0 17px 17px;
        }

        .fisio-search {
            max-width: 320px;
            width: 100%;
        }

        .fisio-badge,
        .fisio-lock {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            padding: 5px 8px;
            white-space: nowrap;
        }

        .fisio-badge.umum,
        .fisio-lock.open {
            background: #ecfdf5;
            color: #047857;
        }

        .fisio-badge.bpjs {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .fisio-lock.locked {
            background: #fff7ed;
            color: #c2410c;
        }

        .fisio-actions {
            display: inline-flex;
            gap: 6px;
            justify-content: center;
        }

        .fisio-actions .btn {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            height: 32px;
            justify-content: center;
            padding: 0;
            width: 32px;
        }

        #modalGenerateFisio .modal-content,
        #modalConfigFisio .modal-content,
        #modalDetailFisio .modal-content {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 26px 64px rgba(15, 23, 42, .24);
            overflow: hidden;
        }

        #modalGenerateFisio .modal-header,
        #modalConfigFisio .modal-header,
        #modalDetailFisio .modal-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .fisio-simple-head {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 14px;
        }

        .fisio-simple-main {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .fisio-simple-icon {
            align-items: center;
            background: #ecfdf5;
            border-radius: 8px;
            color: var(--fisio-main);
            display: flex;
            flex: 0 0 auto;
            font-size: 24px;
            height: 44px;
            justify-content: center;
            width: 44px;
        }

        .fisio-simple-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .fisio-simple-text,
        .fisio-field-help {
            color: var(--fisio-muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .fisio-simple-badge {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #334155;
            font-size: 11px;
            font-weight: 800;
            padding: 6px 10px;
        }

        .fisio-config-grid,
        .fisio-preview-grid,
        .fisio-detail-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .fisio-config-section,
        .fisio-simple-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
        }

        .fisio-config-section h6,
        .fisio-simple-section-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 800;
            gap: 7px;
            margin-bottom: 12px;
        }

        .fisio-recipient-list {
            display: grid;
            gap: 8px;
        }

        .fisio-recipient-item {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 10px 12px;
        }

        .fisio-recipient-name {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
        }

        .fisio-recipient-meta {
            color: var(--fisio-muted);
            font-size: 11px;
            margin-top: 2px;
        }

        .fisio-recipient-total {
            color: #0f766e;
            font-size: 13px;
            font-weight: 800;
            text-align: right;
        }

        .fisio-quality {
            border-radius: 8px;
            font-size: 12px;
            margin-top: 12px;
            padding: 10px 12px;
        }

        .fisio-quality.ok {
            background: #ecfdf5;
            color: #047857;
        }

        .fisio-quality.warn {
            background: #fff7ed;
            color: #c2410c;
        }

        .fisio-empty-state {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: var(--fisio-muted);
            font-size: 12px;
            padding: 13px;
            text-align: center;
        }

        .fisio-action-table .form-control,
        .fisio-action-table .form-check-input {
            min-width: 0;
        }

        .fisio-action-table .btn {
            border-radius: 8px;
        }

        #generateFisioUmumFields {
            grid-column: span 2;
        }

        @media (max-width: 1199.98px) {
            .fisio-summary-grid,
            .fisio-formula-strip {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .fisio-config-grid,
            .fisio-preview-grid,
            .fisio-detail-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .fisio-hero,
            .fisio-panel-head,
            .fisio-filter-bar,
            .fisio-simple-head {
                flex-direction: column;
            }

            .fisio-hero-controls,
            .fisio-search {
                flex-basis: auto;
                max-width: none;
                width: 100%;
            }

            .fisio-type-tabs {
                flex-direction: column;
            }

            .fisio-summary-grid,
            .fisio-formula-strip,
            .fisio-config-grid,
            .fisio-preview-grid,
            .fisio-detail-grid {
                grid-template-columns: 1fr;
            }

            #generateFisioUmumFields {
                grid-column: auto;
            }

            .fisio-info-row,
            .fisio-recipient-item {
                grid-template-columns: 1fr;
            }

            .fisio-info-value,
            .fisio-recipient-total {
                text-align: left;
            }
        }
    </style>
@endpush

@section("content")
    <div class="fisio-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Generate Fisio</li>
            </ol>
        </nav>

        <section class="fisio-hero">
            <div class="fisio-hero-main">
                <div class="fisio-hero-icon">
                    <i class="mdi mdi-human-cane"></i>
                </div>
                <div>
                    <div class="fisio-eyebrow">Manual terkonfigurasi</div>
                    <h4 class="fisio-title">Generate Premi Fisio</h4>
                    <p class="fisio-description">
                        UMUM memakai daftar tindakan dan harga konfigurasi. BPJS memakai jumlah pasien dan nominal per
                        pasien. Pembagian Petugas 1, Petugas 2, dan premi bersama mengikuti rumus aktif.
                    </p>
                </div>
            </div>
            <div class="fisio-hero-controls">
                <div class="fisio-control-box">
                    <label for="periodeFisio">Periode Perhitungan</label>
                    <div class="fisio-period-picker">
                        <button type="button" class="fisio-period-step" id="btnPrevPeriodFisio">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" id="periodeFisio" class="form-control">
                        <button type="button" class="fisio-period-step" id="btnNextPeriodFisio">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="fisio-period-current" id="btnCurrentPeriodFisio">
                        <i class="mdi mdi-calendar-today-outline"></i>
                        Bulan Ini
                    </button>
                </div>
            </div>
        </section>

        <div class="fisio-type-tabs">
            <button type="button" class="fisio-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>UMUM</strong>
                    <small>Tindakan x jumlah</small>
                </span>
            </button>
            <button type="button" class="fisio-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-account-outline"></i>
                <span>
                    <strong>BPJS Kesehatan</strong>
                    <small>Pasien x nominal</small>
                </span>
            </button>
        </div>

        <section class="fisio-panel">
            <div class="fisio-panel-head">
                <div>
                    <div class="fisio-panel-title">Ringkasan Generate Fisio</div>
                    <div class="fisio-panel-subtitle" id="summaryFisioSubtitle">
                        Data dihitung dari hasil generate periode aktif.
                    </div>
                </div>
                <div class="fisio-panel-actions">
                    <button type="button" class="fisio-btn fisio-btn-light" id="btnRefreshFisio">
                        <i class="mdi mdi-refresh"></i>
                        Refresh
                    </button>
                    <button type="button" class="fisio-btn fisio-btn-light" id="btnOpenConfigFisio">
                        <i class="mdi mdi-cog-outline"></i>
                        Konfigurasi
                    </button>
                    <button type="button" class="fisio-btn fisio-btn-primary" id="btnOpenGenerateFisio">
                        <i class="mdi mdi-calculator-variant-outline"></i>
                        Generate
                    </button>
                </div>
            </div>
            <div class="fisio-summary-grid">
                <div class="fisio-summary-card">
                    <div class="fisio-summary-label" id="summaryVolumeLabelFisio">Tindakan</div>
                    <div class="fisio-summary-value" id="summaryVolumeFisio">0</div>
                </div>
                <div class="fisio-summary-card total">
                    <div class="fisio-summary-label">Grand Total</div>
                    <div class="fisio-summary-value" id="summaryGrandFisio">Rp 0</div>
                </div>
                <div class="fisio-summary-card">
                    <div class="fisio-summary-label">Petugas 1</div>
                    <div class="fisio-summary-value" id="summaryPetugas1Fisio">Rp 0</div>
                </div>
                <div class="fisio-summary-card">
                    <div class="fisio-summary-label">Petugas 2</div>
                    <div class="fisio-summary-value" id="summaryPetugas2Fisio">Rp 0</div>
                </div>
                <div class="fisio-summary-card">
                    <div class="fisio-summary-label">Premi Bersama</div>
                    <div class="fisio-summary-value" id="summaryBersamaFisio">Rp 0</div>
                </div>
                <div class="fisio-summary-card">
                    <div class="fisio-summary-label">Terkunci</div>
                    <div class="fisio-summary-value" id="summaryLockedFisio">0</div>
                </div>
            </div>
            <div class="fisio-formula-strip" id="formulaFisioStrip">
                <div class="fisio-info-row">
                    <div>
                        <div class="fisio-info-label">Rumus Aktif</div>
                        <div class="fisio-info-note">Konfigurasi belum dimuat.</div>
                    </div>
                    <div class="fisio-info-value">-</div>
                </div>
            </div>
        </section>

        <section class="fisio-panel">
            <div class="fisio-filter-bar">
                <div>
                    <div class="fisio-panel-title" id="resultFisioTitle">Hasil Generate Fisio Umum</div>
                    <div class="fisio-panel-subtitle">UMUM tersimpan per tindakan, BPJS tersimpan per periode.</div>
                </div>
                <div class="fisio-search input-group">
                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                    <input type="text" class="form-control" id="searchGenerateFisio"
                        placeholder="Cari periode / sumber...">
                </div>
            </div>
            <div class="fisio-table-wrap">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="tableGenerateFisio">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Sumber</th>
                                <th class="text-center">Qty/Pasien</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-end">Petugas 1</th>
                                <th class="text-end">Petugas 2</th>
                                <th class="text-end">Premi Bersama</th>
                                <th class="text-center">Penerima</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include("simrs.backOffice.keuangan.hitungPremi.generateFisio.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateFisio.jsMain")
@endpush
