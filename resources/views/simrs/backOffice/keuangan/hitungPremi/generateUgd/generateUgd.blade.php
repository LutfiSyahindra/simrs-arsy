@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .ugd-page {
            --ugd-blue: #2563eb;
            --ugd-teal: #0f766e;
            --ugd-amber: #f59e0b;
            --ugd-muted: #64748b;
            --ugd-line: #e2e8f0;
            color: #172033;
        }

        .ugd-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .ugd-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #0f766e 0%, #0e7490 58%, #2563eb 100%);
            border-radius: 16px;
            box-shadow: 0 16px 34px rgba(91, 33, 182, .2);
            color: #fff;
            display: flex;
            gap: 22px;
            justify-content: space-between;
            margin-top: 15px;
            overflow: hidden;
            padding: 22px;
            position: relative;
        }

        .ugd-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .ugd-hero-icon {
            align-items: center;
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 12px 25px rgba(15, 23, 42, .18);
            color: var(--ugd-teal);
            display: flex;
            flex: 0 0 auto;
            font-size: 30px;
            height: 58px;
            justify-content: center;
            width: 58px;
        }

        .ugd-eyebrow {
            color: #ddd6fe;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .ugd-title {
            font-size: 24px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .ugd-description {
            color: rgba(255, 255, 255, .78);
            font-size: 13px;
            margin: 0;
            max-width: 690px;
        }

        .ugd-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .ugd-control-box {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 12px;
            padding: 12px;
        }

        .ugd-control-box label {
            color: #ede9fe;
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .ugd-control-box .form-control {
            border: 0;
            height: 39px;
        }

        .ugd-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .ugd-period-picker .form-control {
            font-weight: 800;
            text-align: center;
        }

        .ugd-period-step,
        .ugd-period-current {
            align-items: center;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 9px;
            color: #fff;
            display: inline-flex;
            font-size: 17px;
            height: 39px;
            justify-content: center;
            padding: 0;
        }

        .ugd-period-step:hover,
        .ugd-period-current:hover {
            background: #fff;
            color: var(--ugd-teal);
        }

        .ugd-period-current {
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            margin-top: 8px;
            padding: 0 10px;
            width: 100%;
        }

        .ugd-type-tabs {
            background: #fff;
            border: 1px solid var(--ugd-line);
            border-radius: 14px;
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 8px;
        }

        .ugd-type-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 10px;
            color: #64748b;
            display: flex;
            flex: 1;
            gap: 10px;
            padding: 11px 14px;
            text-align: left;
        }

        .ugd-type-tab i {
            font-size: 23px;
        }

        .ugd-type-tab strong,
        .ugd-type-tab small {
            display: block;
        }

        .ugd-type-tab small {
            font-size: 11px;
        }

        .ugd-type-tab.active {
            background: #ecfdf5;
            box-shadow: inset 0 0 0 1px #99f6e4;
            color: #0f766e;
        }

        .ugd-panel {
            background: #fff;
            border: 1px solid var(--ugd-line);
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .ugd-panel-head {
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .ugd-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .ugd-panel-subtitle {
            color: var(--ugd-muted);
            font-size: 11px;
        }

        .ugd-panel-actions {
            align-items: center;
            display: flex;
            gap: 8px;
        }

        .ugd-refresh-btn,
        .ugd-copy-btn,
        .ugd-lock-all-btn,
        .ugd-config-btn {
            align-items: center;
            background: #fff;
            border: 1px solid var(--ugd-line);
            border-radius: 9px;
            color: #475569;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 10px 12px;
        }

        .ugd-copy-btn {
            border-color: #bae6fd;
            color: #0369a1;
        }

        .ugd-copy-btn:hover {
            background: #e0f2fe;
            color: #075985;
        }

        .ugd-lock-all-btn {
            border-color: #fde68a;
            color: #92400e;
        }

        .ugd-lock-all-btn:hover {
            background: #fffbeb;
            color: #78350f;
        }

        .ugd-config-btn {
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .ugd-config-btn:hover {
            background: #eff6ff;
            color: #1e40af;
        }

        .ugd-summary-grid {
            display: grid;
            gap: 11px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 15px 17px;
        }

        .ugd-summary-card {
            background: linear-gradient(180deg, #fff, #f8fafc);
            border: 1px solid var(--ugd-line);
            border-radius: 11px;
            padding: 13px;
        }

        .ugd-summary-card.total {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border: 0;
            color: #fff;
        }

        .ugd-summary-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .ugd-summary-card.total .ugd-summary-label {
            color: #ddd6fe;
        }

        .ugd-summary-value {
            color: #0f172a;
            font-size: 19px;
            font-weight: 900;
            margin-top: 4px;
        }

        .ugd-summary-card.total .ugd-summary-value {
            color: #fff;
        }

        .ugd-ploting-summary {
            border-top: 1px solid #eef2f7;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            padding: 15px 17px;
        }

        .ugd-ploting-item {
            background: #f8fafc;
            border: 1px solid var(--ugd-line);
            border-radius: 10px;
            padding: 11px;
        }

        .ugd-ploting-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
        }

        .ugd-ploting-meta {
            color: var(--ugd-muted);
            font-size: 11px;
            margin-top: 2px;
        }

        .ugd-ploting-total {
            color: var(--ugd-teal);
            font-size: 17px;
            font-weight: 900;
            margin-top: 7px;
        }

        .ugd-ploting-empty {
            color: var(--ugd-muted);
            font-size: 12px;
            grid-column: 1 / -1;
            padding: 4px 0;
        }

        .ugd-generate-btn {
            align-items: center;
            background: #0f766e;
            border: 0;
            border-radius: 9px;
            color: #fff;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 10px 14px;
        }

        .ugd-filter-bar {
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 13px 17px;
        }

        .ugd-search {
            max-width: 340px;
            width: 100%;
        }

        .ugd-search .form-control,
        .ugd-search .input-group-text {
            border-color: var(--ugd-line);
            height: 38px;
        }

        .ugd-table-wrap {
            padding: 8px 17px 17px;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .ugd-badge,
        .ugd-lock {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            gap: 4px;
            padding: 4px 8px;
        }

        .ugd-badge.umum {
            background: #dcfce7;
            color: #166534;
        }

        .ugd-badge.bpjs {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .ugd-lock.locked {
            background: #fef3c7;
            color: #92400e;
        }

        .ugd-lock.open {
            background: #f1f5f9;
            color: #64748b;
        }

        .ugd-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .ugd-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 31px;
            justify-content: center;
            padding: 0;
            width: 31px;
        }

        #modalGenerateUgd .modal-dialog {
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
            margin-bottom: .5rem;
            margin-top: .5rem;
        }

        #modalGenerateUgd .modal-content {
            display: flex;
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
            overflow: hidden;
        }

        #modalGenerateUgd .ugd-generate-form {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        #modalGenerateUgd .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        #modalGenerateUgd .modal-footer {
            background: #fff;
            flex: 0 0 auto;
        }

        .ugd-input-rows {
            display: grid;
            gap: 10px;
        }

        .ugd-input-row {
            align-items: start;
            background: #f8fafc;
            border: 1px solid var(--ugd-line);
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(210px, 1.6fr) minmax(160px, 1fr) minmax(110px, .7fr) minmax(145px, .8fr) minmax(120px, .8fr) 34px;
            padding: 10px;
        }

        .ugd-row-number,
        .ugd-remove-row {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .ugd-row-number {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 12px;
            font-weight: 900;
        }

        .ugd-remove-row {
            background: #fff;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .ugd-row-field label {
            color: #475569;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .ugd-row-total {
            color: #0f766e;
            font-size: 13px;
            font-weight: 900;
            padding-top: 8px;
        }

        .ugd-preview-total {
            background: #ecfdf5;
            border: 1px solid #99f6e4;
            border-radius: 8px;
            color: #0f766e;
            font-size: 18px;
            font-weight: 900;
            padding: 12px;
            text-align: center;
        }

        @media (max-width: 991.98px) {
            .ugd-hero,
            .ugd-panel-head,
            .ugd-filter-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .ugd-hero-controls {
                flex-basis: auto;
                width: 100%;
            }

            .ugd-panel-actions,
            .ugd-search {
                width: 100%;
            }

            .ugd-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ugd-input-row {
                grid-template-columns: 34px 1fr 34px;
            }

            .ugd-row-field,
            .ugd-row-total {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 1199.98px) {
            #modalGenerateUgd .ugd-input-row {
                grid-template-columns: 34px minmax(0, 1fr) 34px;
            }

            #modalGenerateUgd .ugd-row-field,
            #modalGenerateUgd .ugd-row-total {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 575.98px) {
            .ugd-type-tabs {
                display: grid;
            }

            .ugd-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section("content")
    <div class="ugd-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Generate UGD</li>
            </ol>
        </nav>

        <section class="ugd-hero">
            <div class="ugd-hero-main">
                <div class="ugd-hero-icon">
                    <i class="mdi mdi-hospital-box-outline"></i>
                </div>
                <div>
                    <div class="ugd-eyebrow">Manual Generator</div>
                    <h4 class="ugd-title">Generate Premi UGD</h4>
                    <p class="ugd-description">
                        Input jumlah pasien real dari pelayanan UGD per dokter, pilih jenis BPJS atau UMUM,
                        tentukan ploting premi, lalu sistem menghitung total premi dari nominal hitung.
                    </p>
                </div>
            </div>
            <div class="ugd-hero-controls">
                <div class="ugd-control-box">
                    <label for="periodeUgd">Periode Perhitungan</label>
                    <div class="ugd-period-picker">
                        <button type="button" class="ugd-period-step" id="btnPrevPeriodUgd"
                            title="Bulan sebelumnya">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" id="periodeUgd" class="form-control">
                        <button type="button" class="ugd-period-step" id="btnNextPeriodUgd"
                            title="Bulan berikutnya">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="ugd-period-current" id="btnCurrentPeriodUgd">
                        <i class="mdi mdi-calendar-today-outline"></i>
                        Bulan Ini
                    </button>
                </div>
            </div>
        </section>

        <div class="ugd-type-tabs">
            <button type="button" class="ugd-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>UMUM</strong>
                    <small>Input pasien UGD non-BPJS</small>
                </span>
            </button>
            <button type="button" class="ugd-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-account-outline"></i>
                <span>
                    <strong>BPJS</strong>
                    <small>Input pasien UGD BPJS</small>
                </span>
            </button>
        </div>

        <section class="ugd-panel">
            <div class="ugd-panel-head">
                <div>
                    <div class="ugd-panel-title">Ringkasan Generate UGD</div>
                    <div class="ugd-panel-subtitle" id="summaryUgdSubtitle">
                        Data dihitung dari input real pelayanan UGD.
                    </div>
                </div>
                <div class="ugd-panel-actions">
                    <button type="button" class="ugd-refresh-btn" id="btnRefreshUgd">
                        <i class="mdi mdi-refresh"></i>
                        Refresh
                    </button>
                    <button type="button" class="ugd-copy-btn" id="btnCopyNextMonthUgd">
                        <i class="mdi mdi-content-copy"></i>
                        Copy Bulan Berikutnya
                    </button>
                    <button type="button" class="ugd-lock-all-btn" id="btnLockAllUgd">
                        <i class="mdi mdi-lock-check-outline"></i>
                        Kunci Semua
                    </button>
                    <button type="button" class="ugd-config-btn" id="btnConfigUgd">
                        <i class="mdi mdi-tune-variant"></i>
                        Konfigurasi
                    </button>
                    <button type="button" class="ugd-generate-btn" id="btnOpenGenerateUgd">
                        <i class="mdi mdi-plus-circle-outline"></i>
                        Generate UGD
                    </button>
                </div>
            </div>
            <div class="ugd-summary-grid">
                <div class="ugd-summary-card">
                    <div class="ugd-summary-label">Dokter</div>
                    <div class="ugd-summary-value" id="summaryDokterUgd">0</div>
                </div>
                <div class="ugd-summary-card">
                    <div class="ugd-summary-label">Pasien</div>
                    <div class="ugd-summary-value" id="summaryPasienUgd">0</div>
                </div>
                <div class="ugd-summary-card">
                    <div class="ugd-summary-label">Data Generate</div>
                    <div class="ugd-summary-value" id="summaryDataUgd">0</div>
                </div>
                <div class="ugd-summary-card total">
                    <div class="ugd-summary-label">Total UGD</div>
                    <div class="ugd-summary-value" id="summaryTotalUgd">Rp 0</div>
                </div>
            </div>
            <div class="ugd-ploting-summary" id="summaryPlotingUgd">
                <div class="ugd-ploting-empty">Belum ada total per ploting.</div>
            </div>
        </section>

        <section class="ugd-panel">
            <div class="ugd-panel-head">
                <div>
                    <div class="ugd-panel-title" id="resultUgdTitle">Hasil Generate UGD Umum</div>
                    <div class="ugd-panel-subtitle">Hasil tersimpan per dokter, jenis pelayanan, dan ploting premi.</div>
                </div>
            </div>
            <div class="ugd-filter-bar">
                <div class="input-group ugd-search">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="mdi mdi-magnify text-muted"></i>
                    </span>
                    <input type="search" id="searchGenerateUgd" class="form-control border-start-0"
                        placeholder="Cari dokter, kode, atau ploting...">
                </div>
                <small class="text-muted">
                    Data terbuka bisa direvisi. Data terkunci hanya Admin yang dapat membuka.
                </small>
            </div>
            <div class="ugd-table-wrap">
                <div class="table-responsive">
                    <table id="tableGenerateUgd" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Dokter</th>
                                <th>Ploting</th>
                                <th class="text-center">Pasien</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th>Generate</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include("simrs.backOffice.keuangan.hitungPremi.generateUgd.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateUgd.jsMain")
@endpush
