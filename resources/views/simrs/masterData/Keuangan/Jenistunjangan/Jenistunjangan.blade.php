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
        @include("simrs.masterData.Keuangan.Jenistunjangan.modalMain")

        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Jenis Tunjangan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Data Jenis Tunjangan</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card master-card">
                    <div class="card-body p-0">
                        <div class="master-panel">
                            <div class="master-title-group">
                                <div class="master-icon master-icon-primary">
                                    <i class="mdi mdi-wallet-plus"></i>
                                </div>

                                <div>
                                    <div class="master-eyebrow">Master Keuangan</div>
                                    <h5 class="master-title">Data Jenis Tunjangan</h5>
                                    <p class="master-subtitle mb-0">
                                        Atur jenis, tipe, dan nilai tunjangan sebagai referensi penggajian.
                                    </p>
                                </div>
                            </div>

                            <div class="master-actions">
                                <div class="input-group input-group-sm master-search">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchJnsTunjangan" class="form-control border-start-0"
                                        placeholder="Cari jenis tunjangan...">
                                </div>

                                <button type="button" id="btnTambahTunjangan"
                                    class="btn master-action-btn master-action-primary" data-bs-toggle="modal"
                                    data-bs-target="#tunjanganModal">
                                    <i class="mdi mdi-plus"></i>
                                    <span>Tambah</span>
                                </button>
                            </div>
                        </div>

                        <div class="master-table-wrap">
                            <div class="table-responsive">
                                <table id="tableJnsTunjangan" class="table table-hover align-middle master-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode</th>
                                            <th>Nama Tunjangan</th>
                                            <th>Tipe</th>
                                            <th>Nilai</th>
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
    @include("simrs.masterData.Keuangan.Jenistunjangan.jsMain")
@endpush
