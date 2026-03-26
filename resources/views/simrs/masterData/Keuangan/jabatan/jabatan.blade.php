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
    </style>
@endpush

@section("content")
    @include("simrs.masterData.Keuangan.jabatan.modalMain")
    {{-- @include("simrs.masterData.Keuangan.jabatan.modalExcell") --}}
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Jabatan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Data Jabatan</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div
                        class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 shadow-sm">
                        <!-- Bagian Kiri: Ikon dan Judul -->
                        <div class="d-flex align-items-center mb-3 mb-md-0">
                            <div class="icon bg-primary bg-opacity-10 text-primary rounded-circle me-3 d-flex align-items-center justify-content-center"
                                style="width: 44px; height: 44px;">
                                <i class="mdi mdi-cash mdi-24px"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-primary mb-1">Data Jabatan</h5>
                                <small class="text-muted">Kelola dan cari data Jabatan dengan cepat</small>
                            </div>
                        </div>

                        <!-- Bagian Kanan: Search dan Tombol Aksi -->
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <!-- Search Bar -->
                            <div class="input-group input-group-sm" style="width: 220px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="mdi mdi-magnify text-muted"></i>
                                </span>
                                <input type="text" id="searchJabatan" class="form-control border-start-0"
                                    placeholder="Cari Jabatan...">
                            </div>

                            <button type="button" id="btnTambahJabatan" class="btn btn-primary btn-sm"
                                data-bs-toggle="modal" data-bs-target="#jabatanModal">
                                <i class="mdi mdi-cash-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tableJabatan" class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Jabatan</th>
                                    <th>Tunjangan</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    @include("simrs.masterData.Keuangan.jabatan.jsMain")
@endpush
