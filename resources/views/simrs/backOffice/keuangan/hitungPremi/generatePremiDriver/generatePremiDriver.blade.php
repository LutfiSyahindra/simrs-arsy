@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        body {
            background: #f5f7fb;
        }

        .driver-page {
            --driver-ink: #172033;
            --driver-muted: #64748b;
            --driver-line: #e2e8f0;
            --driver-blue: #2563eb;
            --driver-teal: #0f766e;
            --driver-rose: #be123c;
            --driver-amber: #d97706;
            color: var(--driver-ink);
        }

        .driver-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .driver-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #123047 0%, #0f766e 54%, #2563eb 100%);
            border-radius: 8px;
            box-shadow: 0 16px 34px rgba(15, 42, 67, .18);
            color: #fff;
            display: flex;
            gap: 22px;
            justify-content: space-between;
            margin-top: 15px;
            overflow: hidden;
            padding: 22px;
        }

        .driver-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            min-width: 0;
        }

        .driver-hero-icon {
            align-items: center;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 12px 25px rgba(15, 23, 42, .18);
            color: var(--driver-teal);
            display: flex;
            flex: 0 0 auto;
            font-size: 30px;
            height: 58px;
            justify-content: center;
            width: 58px;
        }

        .driver-eyebrow {
            color: #ccfbf1;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .driver-title {
            font-size: 24px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .driver-description {
            color: rgba(255, 255, 255, .78);
            font-size: 13px;
            margin: 0;
            max-width: 720px;
        }

        .driver-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
        }

        .driver-control-box {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 8px;
            padding: 12px;
        }

        .driver-control-box label {
            color: #dbeafe;
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .driver-control-box .form-control {
            border: 0;
            height: 39px;
        }

        .driver-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .driver-period-picker .form-control {
            font-weight: 800;
            text-align: center;
        }

        .driver-period-step,
        .driver-period-current {
            align-items: center;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            font-size: 17px;
            height: 39px;
            justify-content: center;
            padding: 0;
        }

        .driver-period-step:hover,
        .driver-period-current:hover {
            background: #fff;
            color: var(--driver-teal);
        }

        .driver-period-current {
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            margin-top: 8px;
            padding: 0 10px;
            width: 100%;
        }

        .driver-tabs {
            background: #fff;
            border: 1px solid var(--driver-line);
            border-radius: 8px;
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 8px;
        }

        .driver-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 8px;
            color: #64748b;
            display: flex;
            flex: 1;
            gap: 10px;
            justify-content: center;
            min-height: 46px;
            padding: 10px 12px;
        }

        .driver-tab i {
            font-size: 22px;
        }

        .driver-tab.active {
            background: #ecfdf5;
            box-shadow: inset 0 0 0 1px #99f6e4;
            color: var(--driver-teal);
            font-weight: 800;
        }

        .driver-panel {
            background: #fff;
            border: 1px solid var(--driver-line);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .driver-panel-head {
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .driver-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .driver-panel-subtitle {
            color: var(--driver-muted);
            font-size: 12px;
            margin-top: 2px;
        }

        .driver-panel-body {
            padding: 17px;
        }

        .driver-summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 16px;
        }

        .driver-summary-item {
            background: #fff;
            border: 1px solid var(--driver-line);
            border-radius: 8px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .045);
            display: flex;
            gap: 12px;
            min-width: 0;
            padding: 14px;
        }

        .driver-summary-icon {
            align-items: center;
            border-radius: 8px;
            display: flex;
            flex: 0 0 auto;
            font-size: 24px;
            height: 45px;
            justify-content: center;
            width: 45px;
        }

        .driver-summary-item.blue .driver-summary-icon {
            background: #dbeafe;
            color: var(--driver-blue);
        }

        .driver-summary-item.teal .driver-summary-icon {
            background: #ccfbf1;
            color: var(--driver-teal);
        }

        .driver-summary-item.rose .driver-summary-icon {
            background: #ffe4e6;
            color: var(--driver-rose);
        }

        .driver-summary-item.amber .driver-summary-icon {
            background: #fef3c7;
            color: var(--driver-amber);
        }

        .driver-summary-label {
            color: var(--driver-muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .driver-summary-value {
            color: #0f172a;
            font-size: 20px;
            font-weight: 900;
            line-height: 1.2;
            margin-top: 3px;
            overflow-wrap: anywhere;
        }

        .driver-summary-note {
            color: var(--driver-muted);
            font-size: 12px;
            margin-top: 2px;
        }

        .driver-work-grid {
            align-items: start;
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1.35fr) minmax(330px, .65fr);
        }

        .driver-form-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1fr) 190px;
            margin-bottom: 14px;
        }

        .driver-label {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .driver-input-rows {
            display: grid;
            gap: 10px;
        }

        .driver-input-row {
            align-items: start;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(180px, 1.25fr) minmax(115px, .6fr) minmax(95px, .45fr) minmax(130px, .6fr) 36px;
            padding: 10px;
        }

        .driver-row-number {
            align-items: center;
            background: #e0f2fe;
            border-radius: 8px;
            color: #0369a1;
            display: flex;
            font-size: 12px;
            font-weight: 900;
            height: 34px;
            justify-content: center;
        }

        .driver-row-field label {
            color: var(--driver-muted);
            display: block;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .driver-row-total {
            align-items: center;
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            color: var(--driver-blue);
            display: flex;
            font-size: 13px;
            font-weight: 900;
            justify-content: flex-end;
            min-height: 38px;
            padding: 0 10px;
            text-align: right;
        }

        .driver-remove-row {
            align-items: center;
            background: #fff;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            color: #be123c;
            display: flex;
            height: 38px;
            justify-content: center;
            margin-top: 20px;
            width: 36px;
        }

        .driver-remove-row:disabled {
            border-color: #e2e8f0;
            color: #cbd5e1;
            cursor: not-allowed;
        }

        .driver-preview-stack {
            display: grid;
            gap: 12px;
        }

        .driver-preview-box {
            border: 1px solid var(--driver-line);
            border-radius: 8px;
            padding: 14px;
        }

        .driver-preview-box.primary {
            background: linear-gradient(135deg, #eff6ff 0%, #ecfdf5 100%);
            border-color: #bfdbfe;
        }

        .driver-preview-label {
            color: var(--driver-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .driver-preview-value {
            color: #0f172a;
            font-size: 24px;
            font-weight: 900;
            line-height: 1.2;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .driver-split-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .driver-split-item {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        .driver-split-item strong {
            color: #0f172a;
            display: block;
            font-size: 16px;
            margin-top: 3px;
            overflow-wrap: anywhere;
        }

        .driver-actions {
            align-items: center;
            display: inline-flex;
            gap: 6px;
            justify-content: center;
        }

        .driver-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            padding: 0;
            width: 34px;
        }

        .driver-lock {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            padding: 5px 9px;
        }

        .driver-lock.locked {
            background: #fff7ed;
            color: #c2410c;
        }

        .driver-lock.open {
            background: #ecfdf5;
            color: #0f766e;
        }

        .driver-toolbar {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .driver-search {
            max-width: 300px;
            width: 100%;
        }

        .driver-search .form-control,
        .driver-search .input-group-text {
            border-color: var(--driver-line);
            height: 38px;
        }

        .driver-config-grid {
            align-items: start;
            display: grid;
            gap: 16px;
            grid-template-columns: 340px minmax(0, 1fr);
        }

        .driver-config-card {
            background: #fff;
            border: 1px solid var(--driver-line);
            border-radius: 8px;
            padding: 15px;
        }

        .driver-config-card-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .driver-form-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .driver-destination-list {
            display: grid;
            gap: 8px;
            max-height: 520px;
            overflow: auto;
            padding-right: 3px;
        }

        .driver-destination-item {
            align-items: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto auto;
            padding: 11px 12px;
        }

        .driver-destination-name {
            color: #0f172a;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .driver-destination-meta {
            color: var(--driver-muted);
            font-size: 12px;
            margin-top: 2px;
        }

        .driver-destination-price {
            color: var(--driver-blue);
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .driver-status-pill {
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 9px;
        }

        .driver-status-pill.active {
            background: #ecfdf5;
            color: #0f766e;
        }

        .driver-status-pill.inactive {
            background: #f1f5f9;
            color: #64748b;
        }

        .driver-empty {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: var(--driver-muted);
            font-size: 13px;
            padding: 18px;
            text-align: center;
        }

        .driver-setup-alert {
            align-items: center;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            color: #9a3412;
            display: none;
            gap: 10px;
            margin-bottom: 12px;
            padding: 12px 14px;
        }

        .driver-setup-alert.active {
            display: flex;
        }

        .driver-setup-alert i {
            font-size: 22px;
        }

        .driver-setup-alert strong {
            color: #7c2d12;
            display: block;
            font-size: 13px;
        }

        .driver-setup-alert span {
            display: block;
            font-size: 12px;
        }

        .driver-section {
            display: none;
        }

        .driver-section.active {
            display: block;
        }

        .driver-detail-list {
            display: grid;
            gap: 8px;
        }

        .driver-detail-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) 90px 120px 135px;
            padding: 10px 12px;
        }

        .select2-container .select2-selection--single {
            border-color: #dee2e6;
            height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        @media (max-width: 1200px) {
            .driver-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .driver-work-grid,
            .driver-config-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .driver-hero {
                flex-direction: column;
            }

            .driver-hero-controls {
                flex: none;
            }

            .driver-input-row {
                grid-template-columns: 34px minmax(0, 1fr);
            }

            .driver-row-total,
            .driver-remove-row {
                grid-column: 2;
                margin-top: 0;
            }

            .driver-form-grid,
            .driver-split-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {

            .driver-tabs,
            .driver-panel-head,
            .driver-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .driver-summary-grid {
                grid-template-columns: 1fr;
            }

            .driver-detail-item,
            .driver-destination-item {
                grid-template-columns: 1fr;
            }

            .driver-destination-price {
                text-align: left;
            }
        }
    </style>
@endpush

@section("content")
    <div class="page-content driver-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route("dashboard") }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route("backOffice.keuangan.hitungPremi") }}">Hitung Premi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Driver Ambulance</li>
            </ol>
        </nav>

        <section class="driver-hero">
            <div class="driver-hero-main">
                <div class="driver-hero-icon">
                    <i class="mdi mdi-ambulance"></i>
                </div>
                <div>
                    <div class="driver-eyebrow">Generate Premi</div>
                    <h4 class="driver-title">Premi Ambulance Driver</h4>
                    <p class="driver-description">
                        Tujuan dan harga dikelola di konfigurasi, lalu setiap periode dihitung dari jumlah perjalanan per
                        tujuan.
                    </p>
                </div>
            </div>
            <div class="driver-hero-controls">
                <div class="driver-control-box">
                    <label>Periode Generate</label>
                    <div class="driver-period-picker">
                        <button type="button" class="driver-period-step" id="btnPrevPeriodDriver"
                            title="Periode sebelumnya">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" id="periodeDriver" class="form-control">
                        <button type="button" class="driver-period-step" id="btnNextPeriodDriver"
                            title="Periode berikutnya">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="driver-period-current" id="btnCurrentPeriodDriver">
                        <i class="mdi mdi-calendar-today"></i> Bulan Ini
                    </button>
                </div>
            </div>
        </section>

        <div class="driver-tabs" role="tablist">
            <button type="button" class="driver-tab active" data-section="generate">
                <i class="mdi mdi-calculator-variant-outline"></i>
                <span>Generate</span>
            </button>
            <button type="button" class="driver-tab" data-section="riwayat">
                <i class="mdi mdi-table-clock"></i>
                <span>Riwayat</span>
            </button>
            <button type="button" class="driver-tab" data-section="konfigurasi">
                <i class="mdi mdi-cog-outline"></i>
                <span>Konfigurasi</span>
            </button>
        </div>

        <div class="driver-summary-grid">
            <div class="driver-summary-item blue">
                <div class="driver-summary-icon"><i class="mdi mdi-account-tie-hat-outline"></i></div>
                <div>
                    <div class="driver-summary-label">Pegawai</div>
                    <div class="driver-summary-value" id="summaryPegawaiDriver">0</div>
                    <div class="driver-summary-note" id="summaryGeneratedDriver">0 data generate</div>
                </div>
            </div>
            <div class="driver-summary-item teal">
                <div class="driver-summary-icon"><i class="mdi mdi-map-marker-distance"></i></div>
                <div>
                    <div class="driver-summary-label">Perjalanan</div>
                    <div class="driver-summary-value" id="summaryJumlahDriver">0</div>
                    <div class="driver-summary-note" id="summaryTujuanDriver">0 tujuan aktif</div>
                </div>
            </div>
            <div class="driver-summary-item amber">
                <div class="driver-summary-icon"><i class="mdi mdi-cash-multiple"></i></div>
                <div>
                    <div class="driver-summary-label">Grand Total</div>
                    <div class="driver-summary-value" id="summaryGrandDriver">Rp 0</div>
                    <div class="driver-summary-note" id="summaryLockedDriver">0 terkunci</div>
                </div>
            </div>
            <div class="driver-summary-item rose">
                <div class="driver-summary-icon"><i class="mdi mdi-account-group-outline"></i></div>
                <div>
                    <div class="driver-summary-label">Premi Bersama</div>
                    <div class="driver-summary-value" id="summaryBersamaDriver">Rp 0</div>
                    <div class="driver-summary-note" id="summaryPercentDriver">20% dari grand total</div>
                </div>
            </div>
        </div>

        <section class="driver-section active" id="sectionDriverGenerate">
            <div class="driver-work-grid">
                <div class="driver-panel">
                    <div class="driver-panel-head">
                        <div>
                            <div class="driver-panel-title" id="formDriverTitle">Generate Premi Driver</div>
                            <div class="driver-panel-subtitle" id="formDriverSubtitle">Pilih pegawai dan rincian tujuan
                                ambulance.</div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetDriverForm">
                            <i class="mdi mdi-refresh me-1"></i> Reset
                        </button>
                    </div>
                    <div class="driver-panel-body">
                        <form id="formGenerateDriver">
                            <input type="hidden" id="editingDriverId">
                            <div class="driver-setup-alert" id="driverSetupAlert">
                                <i class="mdi mdi-alert-circle-outline"></i>
                                <div class="flex-grow-1">
                                    <strong>Tujuan ambulance belum dikonfigurasi.</strong>
                                    <span>Tambahkan tujuan dan harga terlebih dahulu agar generate dapat berjalan.</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-warning" id="btnOpenDriverConfigFromAlert">
                                    Konfigurasi
                                </button>
                            </div>
                            <div class="driver-form-grid">
                                <div>
                                    <label class="driver-label">Pegawai Penerima</label>
                                    <select id="pegawaiGenerateDriver" class="form-select" style="width:100%;"></select>
                                    <div class="invalid-feedback d-block" id="pegawaiGenerateDriverError"></div>
                                </div>
                                <div>
                                    <label class="driver-label">Periode</label>
                                    <input type="text" id="periodeGenerateDriver" class="form-control" readonly>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div>
                                    <label class="driver-label mb-0">Rincian Tujuan</label>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddDriverRow">
                                    <i class="mdi mdi-plus me-1"></i> Tambah Tujuan
                                </button>
                            </div>
                            <div id="driverRows" class="driver-input-rows"></div>
                            <div class="invalid-feedback d-block mt-2" id="entriesGenerateDriverError"></div>

                            <div class="driver-form-actions mt-3">
                                <button type="button" class="btn btn-outline-info" id="btnPreviewDriver">
                                    <i class="mdi mdi-eye-outline me-1"></i> Preview
                                </button>
                                <button type="submit" class="btn btn-primary" id="btnSubmitDriver">
                                    <i class="mdi mdi-calculator-variant-outline me-1"></i> Generate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <aside class="driver-preview-stack">
                    <div class="driver-preview-box primary">
                        <div class="driver-preview-label">Grand Total</div>
                        <div class="driver-preview-value" id="previewGrandDriver">Rp 0</div>
                        <div class="driver-panel-subtitle" id="previewFormulaDriver">0 tujuan / 0 perjalanan</div>
                    </div>
                    <div class="driver-split-grid">
                        <div class="driver-split-item">
                            <div class="driver-preview-label">Untuk Pegawai</div>
                            <strong id="previewPegawaiDriver">Rp 0</strong>
                            <div class="driver-panel-subtitle" id="previewPegawaiPercentDriver">100%</div>
                        </div>
                        <div class="driver-split-item">
                            <div class="driver-preview-label">Premi Bersama</div>
                            <strong id="previewBersamaDriver">Rp 0</strong>
                            <div class="driver-panel-subtitle" id="previewBersamaPercentDriver">20%</div>
                        </div>
                    </div>
                    <div class="driver-preview-box">
                        <div class="driver-preview-label">Pegawai Dipilih</div>
                        <div class="driver-preview-value" style="font-size:17px;" id="previewPegawaiNameDriver">-</div>
                        <div class="driver-panel-subtitle" id="previewPegawaiPositionDriver">-</div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="driver-section" id="sectionDriverRiwayat">
            <div class="driver-panel">
                <div class="driver-panel-head">
                    <div>
                        <div class="driver-panel-title">Riwayat Generate Driver</div>
                        <div class="driver-panel-subtitle">Periode <span id="resultDriverPeriodLabel">-</span></div>
                    </div>
                    <div class="driver-toolbar">
                        <div class="input-group driver-search">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="mdi mdi-magnify text-muted"></i>
                            </span>
                            <input type="text" id="searchGenerateDriver" class="form-control border-start-0"
                                placeholder="Cari pegawai...">
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="btnRefreshDriver">
                            <i class="mdi mdi-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="driver-panel-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="tableGenerateDriver">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Periode</th>
                                    <th>Pegawai</th>
                                    <th>Tujuan</th>
                                    <th>Jumlah</th>
                                    <th>Grand Total</th>
                                    <th>Untuk Pegawai</th>
                                    <th>Premi Bersama</th>
                                    <th>Status</th>
                                    <th>Generate</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section class="driver-section" id="sectionDriverKonfigurasi">
            <div class="driver-config-grid">
                <div class="driver-config-card">
                    <div class="driver-config-card-title">Perhitungan</div>
                    <form id="formConfigDriver">
                        <div class="mb-3">
                            <label class="driver-label">Premi Bersama (%)</label>
                            <input type="number" step="0.01" min="0" max="100"
                                id="configPremiBersamaDriver" class="form-control">
                            <div class="invalid-feedback d-block" id="configPremiBersamaDriverError"></div>
                        </div>
                        <div class="driver-split-grid mb-3">
                            <div class="driver-split-item">
                                <div class="driver-preview-label">Pegawai</div>
                                <strong id="configPegawaiPercentDriver">100%</strong>
                            </div>
                            <div class="driver-split-item">
                                <div class="driver-preview-label">Bersama</div>
                                <strong id="configBersamaPercentDriver">20%</strong>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="btnSaveConfigDriver">
                            <i class="mdi mdi-content-save-outline me-1"></i> Simpan Perhitungan
                        </button>
                    </form>
                </div>

                <div class="driver-panel mb-0">
                    <div class="driver-panel-head">
                        <div>
                            <div class="driver-panel-title">Tujuan Ambulance</div>
                            <div class="driver-panel-subtitle">Harga tujuan aktif dipakai saat generate.</div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetTujuanDriver">
                            <i class="mdi mdi-plus me-1"></i> Tujuan Baru
                        </button>
                    </div>
                    <div class="driver-panel-body">
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <form id="formTujuanDriver" class="driver-config-card">
                                    <input type="hidden" id="tujuanDriverId">
                                    <div class="driver-config-card-title" id="tujuanDriverFormTitle">Tambah Tujuan</div>
                                    <div class="mb-3">
                                        <label class="driver-label">Kode</label>
                                        <input type="text" id="tujuanDriverKode" class="form-control"
                                            placeholder="AMB001">
                                        <div class="invalid-feedback d-block" id="tujuanDriverKodeError"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="driver-label">Nama Tujuan</label>
                                        <input type="text" id="tujuanDriverNama" class="form-control"
                                            placeholder="RS Rujukan">
                                        <div class="invalid-feedback d-block" id="tujuanDriverNamaError"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="driver-label">Harga</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" id="tujuanDriverHarga" class="form-control text-end"
                                                inputmode="numeric" autocomplete="off" placeholder="0">
                                        </div>
                                        <div class="invalid-feedback d-block" id="tujuanDriverHargaError"></div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="driver-label">Urutan</label>
                                            <input type="number" min="0" id="tujuanDriverSort"
                                                class="form-control" value="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="driver-label">Status</label>
                                            <select id="tujuanDriverActive" class="form-select">
                                                <option value="1">Aktif</option>
                                                <option value="0">Nonaktif</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100" id="btnSaveTujuanDriver">
                                        <i class="mdi mdi-content-save-outline me-1"></i> Simpan Tujuan
                                    </button>
                                </form>
                            </div>
                            <div class="col-lg-7">
                                <div class="driver-destination-list" id="driverTujuanList"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="modalDetailDriver" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Detail Premi Driver</h5>
                        <small class="text-muted" id="detailDriverSubtitle">-</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="driver-summary-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                        <div class="driver-summary-item amber">
                            <div>
                                <div class="driver-summary-label">Grand Total</div>
                                <div class="driver-summary-value" id="detailGrandDriver">Rp 0</div>
                            </div>
                        </div>
                        <div class="driver-summary-item teal">
                            <div>
                                <div class="driver-summary-label">Untuk Pegawai</div>
                                <div class="driver-summary-value" id="detailPegawaiDriver">Rp 0</div>
                            </div>
                        </div>
                        <div class="driver-summary-item rose">
                            <div>
                                <div class="driver-summary-label">Premi Bersama</div>
                                <div class="driver-summary-value" id="detailBersamaDriver">Rp 0</div>
                            </div>
                        </div>
                    </div>
                    <div class="driver-detail-list" id="detailDriverRows"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generatePremiDriver.jsMain")
@endpush
