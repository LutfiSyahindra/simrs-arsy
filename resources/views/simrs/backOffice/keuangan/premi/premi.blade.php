@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    @include("template.AddOn.dateRangePicker")
    <style>
        .modal-dialog {
            overflow-y: initial !important;
        }

        .modal-body {
            max-height: auto !important;
            overflow-y: auto;
        }

        .select2-container {
            z-index: 999999 !important;
        }

        .select2-dropdown {
            z-index: 999999 !important;
        }

        .notif-unread {
            background-color: #fff7e6 !important;
            font-weight: 600;
        }

        /* Icon bulat */
        .premi-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Soft button */
        .btn-soft-danger {
            background-color: #fdecec;
            color: #dc3545;
            border: none;
        }

        .btn-soft-danger:hover {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-soft-success {
            background-color: #e7f6ee;
            color: #198754;
            border: none;
        }

        .btn-soft-success:hover {
            background-color: #198754;
            color: #fff;
        }

        /* Export animation */
        .btn-export {
            transition: all 0.2s ease;
        }

        .btn-export:hover {
            transform: translateY(-1px);
        }

        /* Card hover */
        .premi-card {
            transition: box-shadow 0.2s ease;
        }

        .premi-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .premi-chart-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 1rem;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: .75rem;
            scroll-snap-type: x mandatory;
        }

        .premi-chart-item {
            flex: 0 0 calc((100% - 2rem) / 3);
            max-width: calc((100% - 2rem) / 3);
            scroll-snap-align: start;
        }

        .premi-chart-row::-webkit-scrollbar {
            height: 8px;
        }

        .premi-chart-row::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .premi-chart-row::-webkit-scrollbar-track {
            background: transparent;
        }

        @media (max-width: 991.98px) {
            .premi-chart-item {
                flex-basis: calc((100% - 1rem) / 2);
                max-width: calc((100% - 1rem) / 2);
            }
        }

        @media (max-width: 767.98px) {
            .premi-chart-item {
                flex-basis: 100%;
                max-width: 100%;
            }
        }

        /* ===============================
                                   PREMIUM DASHBOARD POLISH
                                ================================ */
        body {
            background: #f5f7fb;
        }

        .card {
            border: 0;
            border-radius: 14px;
        }

        .card>.card-body {
            padding: 1.25rem;
        }

        .page-breadcrumb .breadcrumb {
            margin-bottom: .75rem;
        }

        .page-breadcrumb .breadcrumb-item a {
            color: #2563eb;
            font-weight: 600;
        }

        /* Filter panel */
        .premi-filter-panel {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
            border: 1px solid #e5e7eb;
            border-radius: 14px !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05) !important;
        }

        .premi-filter-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: #e0ecff;
            color: #2563eb;
        }

        .premi-filter-panel .form-control,
        .premi-filter-panel .form-select,
        .premi-filter-panel .input-group-text {
            border-color: #e2e8f0;
        }

        .premi-filter-panel .form-control:focus,
        .premi-filter-panel .form-select:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 .15rem rgba(37, 99, 235, .12);
        }

        /* Chart area */
        .premi-chart-panel {
            background: #ffffff !important;
            border: 1px solid #e5e7eb;
            border-radius: 14px !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05) !important;
            position: relative;
        }

        .premi-chart-panel::after {
            content: "";
            position: absolute;
            top: 12px;
            right: 0;
            bottom: 18px;
            width: 34px;
            pointer-events: none;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0), #fff);
        }

        .premi-chart-item>.card {
            border: 1px solid #edf2f7 !important;
            border-radius: 14px;
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .premi-chart-item>.card:hover {
            transform: translateY(-2px);
            border-color: #dbeafe !important;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .08) !important;
        }

        .premi-chart-item .card-title {
            font-size: .78rem;
            letter-spacing: .02em;
            text-transform: uppercase;
            margin-bottom: 0;
        }

        .premi-chart-item h4 {
            color: #0f172a;
            margin-top: .35rem;
            margin-bottom: .15rem;
        }

        /* Tabs */
        #premiTab {
            border-bottom: 0;
            gap: .4rem;
            background: #f8fafc;
            padding: .45rem;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }

        #premiTab .nav-link {
            border: 0;
            border-radius: 10px;
            color: #64748b;
            font-weight: 600;
            padding: .55rem .85rem;
        }

        #premiTab .nav-link:hover {
            background: #eef2ff;
            color: #2563eb;
        }

        #premiTab .nav-link.active {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .22);
        }

        .tab-content {
            border: 1px solid #e5e7eb !important;
            border-radius: 14px;
            background: #fff;
            margin-top: .75rem;
        }

        /* Summary cards above tables */
        .premi-card {
            border: 1px solid #e5e7eb !important;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        }

        .premi-icon {
            border-radius: 12px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .6);
        }

        /* Tables */
        .table-responsive {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8fafc !important;
            color: #475569;
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .02em;
            border-bottom: 1px solid #e5e7eb;
            padding-top: .8rem;
            padding-bottom: .8rem;
        }

        .table tbody td {
            vertical-align: middle;
            border-color: #eef2f7;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: #fbfdff;
        }

        /* Action buttons */
        .btn-group-sm>.btn,
        .btn-sm {
            border-radius: 9px;
        }

        .btn-outline-primary,
        .btn-outline-danger,
        .btn-outline-success {
            border-width: 1px;
        }

        /* Mobile */
        @media (max-width: 767.98px) {
            #premiTab {
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            #premiTab .nav-link {
                white-space: nowrap;
            }

            .card>.card-body {
                padding: 1rem;
            }
        }
    </style>
@endpush

@section("content")
    @include("simrs.backOffice.keuangan.premi.modal")
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Keuangan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Data Premi</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="p-3 mb-3 premi-filter-panel">

                        <!-- HEADER -->
                        <div class="d-flex align-items-center mb-3">
                            <div class="premi-filter-icon me-3 d-flex align-items-center justify-content-center">
                                <i class="mdi mdi-account-multiple-outline mdi-24px"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-primary mb-0">Data Premi</h5>
                                <small class="text-muted">Kelola dan cari data premi dengan cepat</small>
                            </div>
                        </div>

                        <!-- FILTER -->
                        <div class="row g-2">

                            <!-- DATE RANGE -->
                            <div class="col-12 col-md-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">
                                        <i class="mdi mdi-calendar text-muted"></i>
                                    </span>
                                    <input type="text" id="date-range" class="form-control">
                                </div>
                            </div>

                            <!-- STATUS BAYAR -->
                            <div class="col-6 col-md-2">
                                <select id="filterStatusBayar" class="form-select form-select-sm">
                                    <option value="">Status Bayar</option>
                                    <option value="piutang">Piutang</option>
                                    <option value="lunas_non_piutang">Sudah Bayar</option>
                                    <option value="belum_closing_kasir">Belum Closing Kasir</option>
                                </select>
                            </div>

                            <!-- PENJAMIN -->
                            <div class="col-6 col-md-2">
                                <select id="filterPenjamin" class="form-select form-select-sm">
                                    <option value="">Penjamin</option>
                                    <option value="umum">Umum</option>
                                    <option value="bpjs">BPJS</option>
                                    <option value="bpjstk">BPJS Ketenaga Kerjaan</option>
                                    <option value="asuransi">Asuransi</option>
                                </select>
                            </div>

                            <!-- STATUS RAWAT -->
                            {{-- <div class="col-6 col-md-2">
                                <select id="filterStatusRawat" class="form-select form-select-sm">
                                    <option value="">Status Rawat</option>
                                    <option value="rj">Rawat Jalan</option>
                                    <option value="ri">Rawat Inap</option>
                                </select>
                            </div> --}}

                            <!-- SEARCH -->
                            <div class="col-12 col-md-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchPremi" class="form-control"
                                        placeholder="Cari nama dokter / perawat...">
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Grafik --}}
                    <div class="p-3 mb-3 premi-filter-panel">

                        <div class="row">
                            <div class="col-12 col-xl-12 stretch-card">
                                <div class="premi-chart-row">

                                    {{-- PREMI DOKTER --}}
                                    <div class="premi-chart-item">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <h6 class="card-title text-muted">Premi Dokter</h6>
                                                    <i data-feather="user" class="text-primary"></i>
                                                </div>

                                                <h4 class="fw-semibold" id="totalPremiDokter">Rp 0</h4>

                                                <div class="small mb-2" id="premiDokterGrowth"></div>

                                                <div id="premiDokterChart" style="height:90px"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- PREMI PARAMEDIS --}}
                                    <div class="premi-chart-item">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body">

                                                <div class="d-flex justify-content-between mb-1">
                                                    <h6 class="card-title text-muted">Premi Paramedis</h6>
                                                    <i data-feather="users" class="text-info"></i>
                                                </div>

                                                <h4 class="fw-semibold" id="totalPremiParamedis">
                                                    Rp 0
                                                </h4>

                                                <div class="small mb-2" id="premiParamedisGrowth"></div>

                                                <div id="premiParamedisChart" style="height:90px"></div>

                                            </div>
                                        </div>
                                    </div>

                                    {{-- PREMI KAMAR --}}
                                    <div class="premi-chart-item">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body">

                                                {{-- HEADER --}}
                                                <div class="d-flex justify-content-between mb-1">
                                                    <h6 class="card-title text-muted">Premi Kamar</h6>
                                                    <i data-feather="home" class="text-success"></i>
                                                </div>

                                                {{-- TOTAL --}}
                                                <h4 class="fw-semibold" id="totalPremiKamar">
                                                    Rp 0
                                                </h4>

                                                {{-- GROWTH --}}
                                                <div class="small mb-2" id="premiKamarGrowth"></div>

                                                {{-- CHART --}}
                                                <div id="premiKamarChart" style="height:90px"></div>

                                            </div>
                                        </div>
                                    </div>

                                    {{-- PREMI RS --}}
                                    <div class="premi-chart-item">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body">

                                                {{-- HEADER --}}
                                                <div class="d-flex justify-content-between mb-1">
                                                    <h6 class="card-title text-muted">Premi Rumah Sakit</h6>
                                                    <i data-feather="plus-square" class="text-primary"></i>
                                                </div>

                                                {{-- TOTAL --}}
                                                <h4 class="fw-semibold" id="totalPremiRs">
                                                    Rp 0
                                                </h4>

                                                {{-- GROWTH --}}
                                                <div class="small mb-2" id="premiRsGrowth"></div>

                                                {{-- CHART --}}
                                                <div id="premiRsChart" style="height:90px"></div>

                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div> <!-- row -->
                    </div>

                    <ul class="nav nav-tabs mb-3" id="premiTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="dokter-tab" data-bs-toggle="tab" href="#tab-dokter"
                                role="tab">
                                <i class="mdi mdi-stethoscope me-1"></i> Dokter
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="paramedis-tab" data-bs-toggle="tab" href="#tab-paramedis"
                                role="tab">
                                <i class="mdi mdi-account-heart-outline me-1"></i> Paramedis
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="kamar-inap-tab" data-bs-toggle="tab" href="#tab-kamar-inap"
                                role="tab">
                                <i class="mdi mdi-bed-outline me-1"></i> Kamar Inap
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="rs-tab" data-bs-toggle="tab" href="#tab-rs" role="tab">
                                <i class="mdi mdi-hospital-building"></i> Rumah Sakit
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content border border-top-0 p-3">
                        <!-- TAB DOKTER -->
                        <div class="tab-pane fade show active" id="tab-dokter" role="tabpanel">

                            <div class="card shadow-sm border-0 mb-3 premi-card">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                                        <div class="d-flex align-items-center gap-2">
                                            <div class="premi-icon bg-primary-subtle text-primary">
                                                <i class="mdi mdi-stethoscope"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Premi Dokter</div>
                                                <small class="text-muted">Rekap total premi per dokter</small>
                                            </div>
                                        </div>

                                        <!-- 🔥 TOTAL MINI -->
                                        <div class="d-flex align-items-center gap-3">

                                            <div class="text-end">
                                                <div class="fw-bold text-primary fs-6" id="totalPremiDokterMini">
                                                    Rp 0
                                                </div>
                                                <small class="text-muted">Total periode</small>
                                            </div>

                                            <div class="vr"></div>

                                            <div class="d-flex gap-2">
                                                <button class="btn btn-soft-danger btn-sm btn-export" id="btnPdfDokter"
                                                    onclick="btnPdfDokter()">
                                                    <i class="mdi mdi-file-pdf-box"></i>
                                                    <span>PDF</span>
                                                </button>
                                            </div>

                                        </div>

                                    </div>

                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="tablePremiDokter" class="table table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Dokter</th>
                                            <th class="text-end">Total Premi</th>
                                            <th width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>

                        </div>

                        <!-- TAB PARAMEDIS -->
                        <div class="tab-pane fade" id="tab-paramedis" role="tabpanel">

                            <div class="card shadow-sm border-0 mb-3 premi-card">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                                        <div class="d-flex align-items-center gap-2">
                                            <div class="premi-icon bg-success-subtle text-success">
                                                <i class="mdi mdi-account-heart-outline"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Premi Paramedis</div>
                                                <small class="text-muted">Rekap total premi paramedis</small>
                                            </div>
                                        </div>

                                        <!-- 🔥 TOTAL MINI -->
                                        <div class="d-flex align-items-center gap-3">

                                            <div class="text-end">
                                                <div class="fw-bold text-primary fs-6" id="totalPremiParamedisMini">
                                                    Rp 0
                                                </div>
                                                <small class="text-muted">Total periode</small>
                                            </div>

                                            <div class="vr"></div>

                                            <div class="d-flex gap-2">
                                                <button class="btn btn-soft-danger btn-sm btn-export" id="btnPdfParamedis"
                                                    onclick="btnPdfParamedis()">
                                                    <i class="mdi mdi-file-pdf-box"></i>
                                                    <span>PDF</span>
                                                </button>

                                                {{-- <button class="btn btn-soft-success btn-sm btn-export"
                                                    id="btnExcelDokter">
                                                    <i class="mdi mdi-file-excel-box"></i>
                                                    <span>Excel</span>
                                                </button> --}}
                                            </div>

                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="tablePremiParamedis" class="table table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Paramedis</th>
                                            <th class="text-end">Total Premi</th>
                                            <th width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- TAB KAMAR INAP -->
                        <div class="tab-pane fade" id="tab-kamar-inap" role="tabpanel">

                            <div class="card shadow-sm border-0 mb-3 premi-card">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                                        <div class="d-flex align-items-center gap-2">
                                            <div class="premi-icon bg-success-subtle text-warning">
                                                <i class="mdi mdi-bed-outline"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Premi Kamar Inap</div>
                                                <small class="text-muted">Rekap total premi kamar inap</small>
                                            </div>
                                        </div>

                                        <!-- 🔥 TOTAL MINI -->
                                        <div class="d-flex align-items-center gap-3">

                                            <div class="text-end">
                                                <div class="fw-bold text-primary fs-6" id="totalPremiKamarInapMini">
                                                    Rp 0
                                                </div>
                                                <small class="text-muted">Total periode</small>
                                            </div>

                                            <div class="vr"></div>

                                            <div class="d-flex gap-2">

                                                <button class="btn btn-soft-success btn-sm btn-export"
                                                    id="btnToggleKamarView" data-view="grouped"
                                                    onclick="toggleKamarView()">
                                                    <i class="mdi mdi-view-grid-outline"></i>
                                                    <span id="labelToggleKamarView">Grouped</span>
                                                </button>

                                                <button class="btn btn-soft-danger btn-sm btn-export" id="btnPdfKamarInap"
                                                    onclick="btnPdfKamarInap()">
                                                    <i class="mdi mdi-file-pdf-box"></i>
                                                    <span>PDF</span>
                                                </button>

                                                {{-- <button class="btn btn-soft-success btn-sm btn-export"
                                                    id="btnExcelKamarInap">
                                                    <i class="mdi mdi-file-excel-box"></i>
                                                    <span>Excel</span>
                                                </button> --}}
                                            </div>

                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="tablePremiKamarInap" class="table table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Kamar Inap</th>
                                            <th class="text-end">Total Premi</th>
                                            <th width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- TAB RUMAH SAKIT -->
                        <div class="tab-pane fade" id="tab-rs" role="tabpanel">

                            <div class="card shadow-sm border-0 mb-3 premi-card">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                                        <div class="d-flex align-items-center gap-2">
                                            <div class="premi-icon bg-primary-subtle text-primary">
                                                <i class="mdi mdi-hospital-building"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Premi Rumah Sakit</div>
                                                <small class="text-muted">Rekap total premi Rumah Sakit</small>
                                            </div>
                                        </div>

                                        <!-- 🔥 TOTAL MINI -->
                                        <div class="d-flex align-items-center gap-3">

                                            <div class="text-end">
                                                <div class="fw-bold text-primary fs-6" id="totalPremiRsMini">
                                                    Rp 0
                                                </div>
                                                <small class="text-muted">Total periode</small>
                                            </div>

                                            <div class="vr"></div>

                                            <div class="d-flex gap-2">

                                                <button class="btn btn-soft-danger btn-sm btn-export" id="btnPdfRs"
                                                    onclick="btnPdfRs()">
                                                    <i class="mdi mdi-file-pdf-box"></i>
                                                    <span>PDF</span>
                                                </button>
                                            </div>

                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="tablePremiRs" class="table table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Total Premi RS</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push("scripts")
        @include("simrs.backOffice.keuangan.premi.jsMain")
    @endpush
