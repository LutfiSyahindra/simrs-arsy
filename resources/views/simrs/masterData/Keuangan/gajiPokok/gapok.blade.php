@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    @include("template.AddOn.dropify")
    @include("simrs.partials.masterFinanceStyle")
@endpush

@section("content")
    <div class="finance-master-page">
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
                <div class="card master-card">
                    <div class="card-body p-0">
                        <div class="master-panel">
                            <div class="master-title-group">
                                <div class="master-icon master-icon-success">
                                    <i class="mdi mdi-cash-multiple"></i>
                                </div>

                                <div>
                                    <div class="master-eyebrow">Master Keuangan</div>
                                    <h5 class="master-title">Data Gaji Pokok</h5>
                                    <p class="master-subtitle mb-0">
                                        Pantau, impor, dan sinkronkan gaji pokok pegawai dengan cepat.
                                    </p>
                                </div>
                            </div>

                            <div class="master-actions">
                                <div class="input-group input-group-sm master-search">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchGapok" class="form-control border-start-0"
                                        placeholder="Cari gaji pokok...">
                                </div>

                                <button type="button" class="btn master-action-btn master-action-success"
                                    data-bs-toggle="modal" data-bs-target="#gapokModalExcell" aria-label="Import Excel"
                                    title="Import Excel">
                                    <i class="mdi mdi-file-excel"></i>
                                </button>

                                <button type="button" id="btnTambahGapok"
                                    class="btn master-action-btn master-action-primary" data-bs-toggle="modal"
                                    data-bs-target="#gapokModal">
                                    <i class="mdi mdi-cash-plus"></i>
                                    <span>Tambah</span>
                                </button>

                                <button type="button" id="btnSyncGapok" class="btn master-action-btn master-action-warning"
                                    aria-label="Sinkronkan data" title="Sinkronkan data">
                                    <i class="mdi mdi-sync"></i>
                                </button>
                            </div>
                        </div>

                        <div class="master-table-wrap">
                            <div class="table-responsive">
                                <table id="tableGapok" class="table table-hover align-middle master-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center"></th>
                                            <th>No</th>
                                            <th>Nama</th>
                                            <th>Jabatan</th>
                                            <th>Status Kerja</th>
                                            <th>Komponen Gaji</th>
                                            <th>No Telpon</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
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
    @include("simrs.masterData.Keuangan.gajiPokok.jsMain")
@endpush
