@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    @include("template.AddOn.dropify")
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush

@section("content")
    @include("simrs.masterData.Keuangan.tunjanganPegawai.modalMain")
    @include("simrs.masterData.Keuangan.tunjanganPegawai.modalExcell")
    @include("simrs.masterData.Keuangan.tunjanganPegawai.modalCopy")
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Tunjangan Pegawai</a></li>
            <li class="breadcrumb-item active" aria-current="page">Data Tunjangan Pegawai</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div
                        class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 shadow-sm">

                        <!-- LEFT -->
                        <div class="d-flex align-items-center gap-3 mb-2 mb-md-0">

                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center"
                                style="width:44px;height:44px;">
                                <i class="mdi mdi-cash mdi-24px"></i>
                            </div>

                            <div>
                                <h6 class="fw-bold text-dark mb-0">Data Tunjangan Pegawai</h6>
                                <small class="text-muted">Kelola tunjangan dengan cepat & efisien</small>
                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="d-flex flex-wrap align-items-center gap-2">

                            <!-- SEARCH -->
                            <div class="input-group input-group-sm" style="width:220px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="mdi mdi-magnify text-muted"></i>
                                </span>
                                <input type="text" id="searchTunjanganPegawai" class="form-control border-start-0"
                                    placeholder="Cari...">
                            </div>

                            <!-- DISTRIBUSI -->
                            <button id="btnDistribusi" class="btn btn-outline-success btn-sm">
                                <i class="mdi mdi-account-multiple-plus"></i>
                            </button>

                            <!-- IMPORT -->
                            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal"
                                data-bs-target="#tunjanganPegawaiModalExcell">
                                <i class="mdi mdi-file-excel"></i>
                            </button>

                            <!-- ADD -->
                            <button type="button" id="btnTambahTunjangan" class="btn btn-primary btn-sm"
                                data-bs-toggle="modal" data-bs-target="#tunjanganPegawaiModal">
                                <i class="mdi mdi-plus"></i>
                            </button>

                            <!-- BULK SAVE -->
                            <button id="bulkSaveBtn" class="btn btn-primary btn-sm" disabled>
                                <i class="mdi mdi-content-save-all"></i>
                            </button>

                        </div>

                    </div>

                    <div class="table-responsive">
                        <table id="tableTunjanganPegawai" class="table align-middle table-hover border-top">

                            <thead>
                                <tr>
                                    <th width="30"></th> <!-- 🔥 tombol expand -->
                                    <th>No</th>
                                    <th>Nama Pegawai</th>
                                    <th>Jabatan</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>

                            <tbody></tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    @include("simrs.masterData.Keuangan.tunjanganPegawai.jsMain")
@endpush
