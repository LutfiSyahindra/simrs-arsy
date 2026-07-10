<div class="modal fade" id="potonganPegawaiModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold">
                        Potongan Pegawai
                    </h5>
                    <small class="text-muted">
                        Pilih pegawai dan tambahkan potongan
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="potonganPegawaiForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Pilih Pegawai</label>
                        <select name="nik" id="pegawaiSelect" class="form-select">
                            <option value="">-- Pilih Pegawai --</option>
                        </select>
                        <div class="invalid-feedback" id="error-nik"></div>
                    </div>

                    <div id="pegawaiInfo" class="mb-4 d-none">
                        <div class="p-3 rounded-4 border bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted fw-semibold">
                                    INFORMASI PEGAWAI
                                </small>
                                <span class="badge bg-light text-primary border" id="infoStatus">-</span>
                            </div>

                            <div class="row g-3 small">
                                <div class="col-md-4">
                                    <div class="text-muted">Nama</div>
                                    <div class="fw-semibold" id="infoNama">-</div>
                                </div>

                                <div class="col-md-4">
                                    <div class="text-muted">Jabatan</div>
                                    <div class="fw-semibold" id="infoJabatan">-</div>
                                </div>

                                <div class="col-md-4">
                                    <div class="text-muted">Gaji Pokok</div>
                                    <div class="fw-bold text-danger fs-6" id="infoGapok">
                                        Rp 0
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted fw-semibold">POTONGAN</small>

                        <button type="button" id="addRow" class="btn btn-sm btn-outline-primary">
                            <i data-feather="plus"></i> Tambah
                        </button>
                    </div>

                    <div id="potonganContainer" class="d-flex flex-column gap-3"></div>
                    <input type="hidden" id="potonganPegawaiId">
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit" form="potonganPegawaiForm" class="btn btn-primary">
                    <i data-feather="save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
