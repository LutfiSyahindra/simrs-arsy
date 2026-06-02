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
        @include("simrs.masterData.Keuangan.tunjanganPegawai.modalMain")
        @include("simrs.masterData.Keuangan.tunjanganPegawai.modalExcell", [
            "tunjangan" => $tunjangan,
            "jabatan" => $jabatan,
            "profesi" => $profesi,
        ])
        @include("simrs.masterData.Keuangan.tunjanganPegawai.modalCopy")

        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Tunjangan Pegawai</a></li>
                <li class="breadcrumb-item active" aria-current="page">Data Tunjangan Pegawai</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card master-card">
                    <div class="card-body p-0">
                        <div class="master-panel">
                            <div class="master-title-group">
                                <div class="master-icon master-icon-success">
                                    <i class="mdi mdi-wallet"></i>
                                </div>

                                <div>
                                    <div class="master-eyebrow">Master Keuangan</div>
                                    <h5 class="master-title">Data Tunjangan Pegawai</h5>
                                    <p class="master-subtitle mb-0">
                                        Kelola tunjangan pegawai, impor data, dan pantau total per pegawai.
                                    </p>
                                </div>
                            </div>

                            <div class="master-actions">
                                <div class="input-group input-group-sm master-search">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchTunjanganPegawai" class="form-control border-start-0"
                                        placeholder="Cari tunjangan pegawai...">
                                </div>

                                <button type="button" class="btn master-action-btn master-action-success"
                                    data-bs-toggle="modal" data-bs-target="#tunjanganPegawaiModalExcell"
                                    aria-label="Import Excel" title="Import Excel">
                                    <i class="mdi mdi-file-excel"></i>
                                </button>

                                <button type="button" id="btnTambahTunjangan"
                                    class="btn master-action-btn master-action-primary" data-bs-toggle="modal"
                                    data-bs-target="#tunjanganPegawaiModal">
                                    <i class="mdi mdi-plus"></i>
                                    <span>Tambah</span>
                                </button>
                            </div>
                        </div>

                        <div class="master-table-wrap">
                            <div class="table-responsive">
                                <table id="tableTunjanganPegawai"
                                    class="table table-hover align-middle master-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="30"></th>
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
        </div>
    </div>
@endsection

@push("scripts")
    @include("simrs.masterData.Keuangan.tunjanganPegawai.jsMain")
@endpush
