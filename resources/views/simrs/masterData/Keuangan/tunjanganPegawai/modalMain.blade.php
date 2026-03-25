<div class="modal fade" id="tunjanganPegawaiModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">

            <!-- HEADER -->
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold">
                        Tunjangan Pegawai
                    </h5>
                    <small class="text-muted">
                        Pilih pegawai dan tambahkan tunjangan
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">

                <form id="tunjanganPegawaiForm">
                    @csrf

                    <!-- PILIH PEGAWAI -->
                    <div class="mb-3">
                        <label class="form-label">Pilih Pegawai</label>
                        <select name="nik" id="pegawaiSelect" class="form-select">
                            <option value="">-- Pilih Pegawai --</option>
                            <!-- AJAX / loop -->
                        </select>
                        <div class="invalid-feedback" id="error-nik"></div>
                    </div>

                    <!-- INFO PEGAWAI -->
                    <div id="pegawaiInfo" class="mb-4 d-none">
                        <div class="p-3 rounded-4 border bg-light">

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted fw-semibold">
                                    INFORMASI PEGAWAI
                                </small>

                                <span class="badge bg-light text-primary border" id="infoStatus">
                                    -
                                </span>
                            </div>

                            <div class="row g-2 small">

                                <div class="col-md-6">
                                    <div class="text-muted">Nama</div>
                                    <div class="fw-semibold" id="infoNama">-</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="text-muted">Jabatan</div>
                                    <div class="fw-semibold" id="infoJabatan">-</div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <!-- TUNJANGAN -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted fw-semibold">TUNJANGAN</small>

                        <button type="button" id="addRow" class="btn btn-sm btn-outline-primary">
                            <i data-feather="plus"></i> Tambah
                        </button>
                    </div>

                    <div id="tunjanganContainer" class="d-flex flex-column gap-3">

                        <!-- ROW TEMPLATE -->
                        <div class="card border">
                            <div class="card-body py-3">
                                <div class="row g-3 align-items-end">

                                    <!-- JENIS TUNJANGAN -->
                                    <div class="col-md-8">
                                        <label class="form-label">Tunjangan</label>
                                        <select name="tunjangan_id[]" class="form-select">
                                            <option value="">-- Pilih Tunjangan --</option>
                                            <!-- loop tunjangan -->
                                        </select>
                                    </div>

                                    <!-- REMOVE -->
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-light btn-icon removeRow">
                                            <i data-feather="x"></i>
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <input type="hidden" id="tunjanganPegawaiId">

                </form>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit" form="tunjanganPegawaiForm" class="btn btn-primary">
                    <i data-feather="save"></i> Simpan
                </button>
            </div>

        </div>
    </div>
</div>
