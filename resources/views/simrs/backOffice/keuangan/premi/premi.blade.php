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
                    <div class="p-3 mb-3 bg-light rounded-3 shadow-sm">

                        <!-- HEADER -->
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon bg-primary bg-opacity-10 text-primary rounded-circle me-3 d-flex align-items-center justify-content-center"
                                style="width: 44px; height: 44px;">
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
                    <div class="p-3 mb-3 bg-light rounded-3 shadow-sm">

                        <div class="row">
                            <div class="col-12 col-xl-12 stretch-card">
                                <div class="row flex-grow-1">

                                    {{-- PREMI DOKTER --}}
                                    <div class="col-md-4 grid-margin">
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
                                    <div class="col-md-4 grid-margin">
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
                                    <div class="col-md-4 grid-margin">
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
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push("scripts")
        @include("simrs.backOffice.keuangan.premi.jsMain")
    @endpush
