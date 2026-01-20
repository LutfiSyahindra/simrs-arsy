@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    @include("template.AddOn.dateRangePicker")
@endpush

@section("content")
    @include("simrs.pelayanan.petugasPanggil.admisi.modal")
    {{-- PAGE TITLE --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Petugas Panggil Admisi</h4>
            <p class="text-muted mb-0">Manajemen antrian pasien Admisi</p>
        </div>
        <nav>
            <ol class="breadcrumb breadcrumb-dot mb-0">
                <li class="breadcrumb-item">SIMRS</li>
                <li class="breadcrumb-item active">Petugas Panggil</li>
            </ol>
        </nav>
    </div>
    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body text-center p-4">
                    <!-- Foto Profil atau Ilustrasi -->
                    <div class="d-flex justify-content-center mb-3">
                        <img src="{{ asset("plugins/img/avatar-3.jpg") }}"
                            class="rounded-circle border border-3 border-primary-subtle shadow-sm"
                            style="width: 100px; height: 100px;" alt="Profile Picture">
                    </div>

                    <!-- Garis pemisah halus -->
                    <hr class="mt-3 mb-4 text-muted opacity-25">

                    <!-- Detail Antrian -->
                    <div class="text-start px-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-secondary">Nomor Antrian</span>
                            <span class="fw-bold fs-4 text-primary" id="detail-nomor">-</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-secondary">Loket</span>
                            <span class="fw-bold fs-5 text-success" id="detail-loket">-</span>
                        </div>
                    </div>

                    <!-- Status Aktif -->
                    <div class="mt-4">
                        <span class="badge bg-info bg-opacity-75 px-3 py-2 rounded-pill">
                            <i class="bi bi-person-check me-1"></i> Sedang Melayani
                        </span>
                    </div>
                </div>
            </div>

        </div> <!-- end col-->

        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-end gap-2">
                                        <!-- Button Buka Loket -->
                                        <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                            data-bs-target="#info-header-modal" id="btn-buka-loket">
                                            <i class="ri-home-office-fill me-1"></i> Buka Loket
                                        </button>

                                        <!-- Button Tutup Loket -->
                                        <button type="button" class="btn btn-danger" id="btn-tutup-loket">
                                            <i class="ri-door-closed-fill me-1"></i> Tutup Loket
                                        </button>
                                    </div>

                                    <br>
                                    <h4 class="header-title">Fixed Header</h4>
                                    <!-- Tambahkan table-responsive agar tabel bisa di-scroll jika lebarnya lebih besar dari layar -->
                                    <div class="table-responsive">
                                        <table id="fixed-header-datatable"
                                            class="table table-striped dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th style="width: 80px; text-align: left;">No Antrian</th>
                                                    <th style="width: 80px; text-align: left;">Loket</th>
                                                    <th style="text-align: left;">Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="dataAdmisi">
                                            </tbody>
                                        </table>
                                    </div>
                                </div> <!-- end card body-->
                            </div> <!-- end card -->
                        </div><!-- end col-->
                    </div> <!-- end row-->
                </div> <!-- end card body -->
            </div> <!-- end card -->
        </div> <!-- end col -->

    </div>
    <!-- end row-->
@endsection

@push("scripts")
    @include("simrs.pelayanan.petugasPanggil.admisi.jsMain")
@endpush
