@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
@endpush

@section("content")
    @include("simrs.pelayanan.petugasPanggil.poli.modalPoliPanggil")
    {{-- PAGE TITLE --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Petugas Panggil Poli</h4>
            <p class="text-muted mb-0">Manajemen antrian pasien per poli</p>
        </div>
        <nav>
            <ol class="breadcrumb breadcrumb-dot mb-0">
                <li class="breadcrumb-item">SIMRS</li>
                <li class="breadcrumb-item active">Petugas Panggil</li>
            </ol>
        </nav>
    </div>

    <div class="row g-4">

        {{-- ================= LEFT : DETAIL PASIEN ================= --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card h-100">
                <div class="card-body text-center">

                    <img src="{{ asset("plugins/img/avatar-3.jpg") }}" class="rounded-circle mb-3" width="90"
                        alt="avatar">

                    <h5 class="fw-bold mb-1" id="nama-pasien">-</h5>
                    <p class="text-muted mb-0" id="dokter">-</p>
                    <p class="text-muted mb-2" id="poli">-</p>

                    <span class="badge bg-primary fs-6 mb-3 d-inline-block" id="reg">-</span>

                    <hr>

                    <div class="text-start small">
                        <div class="mb-2">
                            <strong>Nama</strong>
                            <div class="text-muted" id="detail-nama">-</div>
                        </div>
                        <div class="mb-2">
                            <strong>Tanggal Lahir</strong>
                            <div class="text-muted" id="detail-tgl">-</div>
                        </div>
                        <div class="mb-2">
                            <strong>Alamat</strong>
                            <div class="text-muted" id="detail-alamat">-</div>
                        </div>
                        <div class="mb-2">
                            <strong>Jenis Kelamin</strong>
                            <div class="text-muted" id="detail-jk">-</div>
                        </div>
                        <div class="mb-2">
                            <strong>No RM</strong>
                            <div class="text-muted" id="detail-rm">-</div>
                        </div>
                        <div>
                            <strong>No Rawat</strong>
                            <div class="text-muted" id="detail-rawat">-</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ================= RIGHT : DATA ANTRIAN ================= --}}
        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Daftar Antrian Poli</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#info-header-modal">
                        <i class="ri-hospital-line me-1"></i> Pilih Poli
                    </button>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="fixed-header-datatable" class="table table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:100px;">No Reg</th>
                                    <th>Nama Pasien</th>
                                    <th style="width:120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="dataPoli">
                                {{-- Data realtime --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push("scripts")
    @include("simrs.pelayanan.petugasPanggil.poli.jsMain")
@endpush