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

        td.dt-control::before {
            display: none !important;
        }

        /* card lebih soft */
        .expand-card {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #f1f1f1;
            padding: 18px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            animation: fadeSmooth 0.2s ease;
        }

        /* label */
        .detail-item small {
            font-size: 11px;
            color: #888;
        }

        /* value */
        .detail-item div {
            font-weight: 500;
            font-size: 14px;
        }

        /* tombol expand */
        .btn-expand {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            border: none;
            background: #f1f3f5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            transition: all 0.2s ease;
        }

        .btn-expand i {
            font-size: 14px;
            color: #495057;
        }

        .btn-expand:hover {
            background: #e9ecef;
            transform: scale(1.1);
        }

        /* animasi halus */
        @keyframes fadeSmooth {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* hilangkan icon default datatable */
        td.dt-control::before {
            display: none !important;
        }
    </style>
@endpush

@section("content")
    @include("simrs.masterData.Keuangan.gajiPokok.modalMain")
    @include("simrs.masterData.Keuangan.gajiPokok.modalExcell")
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Gaji Pokok</a></li>
            <li class="breadcrumb-item active" aria-current="page">Data Gaji Pokok</li>
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
                                <h5 class="fw-bold text-primary mb-1">Data Gaji Pokok</h5>
                                <small class="text-muted">Kelola dan cari data Gaji Pokok dengan cepat</small>
                            </div>
                        </div>

                        <!-- Bagian Kanan: Search dan Tombol Aksi -->
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <!-- Search Bar -->
                            <div class="input-group input-group-sm" style="width: 220px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="mdi mdi-magnify text-muted"></i>
                                </span>
                                <input type="text" id="searchGapok" class="form-control border-start-0"
                                    placeholder="Cari Gaji Pokok...">
                            </div>

                            <button type="button" class="btn btn-success btn-sm d-flex align-items-center"
                                data-bs-toggle="modal" data-bs-target="#gapokModalExcell">
                                <i class="mdi mdi-file-excel me-1"></i>
                            </button>

                            <button type="button" id="btnTambahGapok" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#gapokModal">
                                <i class="mdi mdi-cash-plus"></i>
                            </button>

                            <button type="button" id="btnSyncGapok"
                                class="btn btn-warning btn-sm d-flex align-items-center">
                                <i class="mdi mdi-sync me-1"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tableGapok" class="table">
                            <thead>
                                <tr>
                                    <th></th> <!-- 🔥 expand -->
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                    <th>Status Kerja</th>
                                    <th>Gaji Pokok</th>
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
    @include("simrs.masterData.Keuangan.gajiPokok.jsMain")
@endpush
