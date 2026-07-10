@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    @include("simrs.partials.masterFinanceStyle")
@endpush

@section("content")
    <div class="finance-master-page">
        @include("simrs.masterData.Keuangan.jenisPotongan.modalMain")

        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Jenis Potongan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Data Jenis Potongan</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card master-card">
                    <div class="card-body p-0">
                        <div class="master-panel">
                            <div class="master-title-group">
                                <div class="master-icon master-icon-danger">
                                    <i class="mdi mdi-cash-minus"></i>
                                </div>

                                <div>
                                    <div class="master-eyebrow">Master Keuangan</div>
                                    <h5 class="master-title">Data Jenis Potongan</h5>
                                    <p class="master-subtitle mb-0">
                                        Atur jenis, tipe, dan nilai potongan sebagai referensi penggajian.
                                    </p>
                                </div>
                            </div>

                            <div class="master-actions">
                                <div class="input-group input-group-sm master-search">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchJnsPotongan" class="form-control border-start-0"
                                        placeholder="Cari jenis potongan...">
                                </div>

                                <button type="button" id="btnTambahPotongan"
                                    class="btn master-action-btn master-action-primary" data-bs-toggle="modal"
                                    data-bs-target="#potonganModal">
                                    <i class="mdi mdi-plus"></i>
                                    <span>Tambah</span>
                                </button>
                            </div>
                        </div>

                        <div class="master-table-wrap">
                            <div class="table-responsive">
                                <table id="tableJnsPotongan" class="table table-hover align-middle master-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode</th>
                                            <th>Nama Potongan</th>
                                            <th>Tipe</th>
                                            <th>Nilai</th>
                                            <th>Keterangan</th>
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
    @include("simrs.masterData.Keuangan.jenisPotongan.jsMain")
@endpush
