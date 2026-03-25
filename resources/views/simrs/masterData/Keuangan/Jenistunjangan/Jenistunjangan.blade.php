@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    @include("template.AddOn.dropify")
@endpush

@section("content")
    @include("simrs.masterData.Keuangan.Jenistunjangan.modalMain")
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Jenis Tunjangan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Data Jenis Tunjangan</li>
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
                                <h5 class="fw-bold text-primary mb-1">Data Jenis Tunjangan</h5>
                                <small class="text-muted">Kelola dan cari data Jenis Tunjangan dengan cepat</small>
                            </div>
                        </div>

                        <!-- Bagian Kanan: Search dan Tombol Aksi -->
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <!-- Search Bar -->
                            <div class="input-group input-group-sm" style="width: 220px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="mdi mdi-magnify text-muted"></i>
                                </span>
                                <input type="text" id="searchJnsTunjangan" class="form-control border-start-0"
                                    placeholder="Cari Jenis Tunjangan...">
                            </div>

                            <button type="button" id="btnTambahTunjangan" class="btn btn-primary btn-sm"
                                data-bs-toggle="modal" data-bs-target="#tunjanganModal">
                                <i class="mdi mdi-cash-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tableJnsTunjangan" class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Jns Tunjangan</th>
                                    <th>Persentase (%)</th>
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
    @include("simrs.masterData.Keuangan.Jenistunjangan.jsMain")
@endpush
