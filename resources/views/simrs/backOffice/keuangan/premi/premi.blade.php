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
    </style>
@endpush

@section("content")
    @include("simrs.backOffice.keuangan.premi.modal")
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Keungan</a></li>
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
                    </ul>

                    <div class="tab-content border border-top-0 p-3">
                        <!-- TAB DOKTER -->
                        <div class="tab-pane fade show active" id="tab-dokter" role="tabpanel">
                            <div class="table-responsive">
                                <table id="tablePremiDokter" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Dokter</th>
                                            <th>Total Premi</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- TAB PARAMEDIS -->
                        <div class="tab-pane fade" id="tab-paramedis" role="tabpanel">
                            <div class="table-responsive">
                                <table id="tablePremiParamedis" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Paramedis</th>
                                            <th>Total Premi</th>
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
    </div>
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.premi.jsMain")
@endpush
