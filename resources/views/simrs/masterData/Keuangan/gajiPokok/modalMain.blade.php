<div class="modal fade" id="gapokModal" tabindex="-1" aria-labelledby="gapokModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <!-- Header -->
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-semibold" id="gapokModalLabel">
                    <i class="mdi mdi-cash-multiple text-primary me-1"></i>
                    Form Gaji Pokok
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body px-4">
                <form id="gapokForm">
                    @csrf

                    <!-- Informasi Pegawai -->
                    <div class="mb-3">
                        <small class="text-muted fw-semibold">INFORMASI PEGAWAI</small>
                        <hr class="mt-1 mb-3">
                    </div>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">NIK</label>
                            <input type="text" name="nik" class="form-control form-control-sm">
                            <div class="invalid-feedback" id="error-nik"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Pegawai</label>
                            <input type="text" name="nama" class="form-control form-control-sm">
                            <div class="invalid-feedback" id="error-nama"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jbtn" class="form-control form-control-sm">
                            <div class="invalid-feedback" id="error-jbtn"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Kerja</label>
                            <select name="stts_kerja" class="form-select form-select-sm">
                                <option value="">-- Pilih Status --</option>
                                <option value="FT">Kontrak</option>
                                <option value="T">Tetap</option>
                                <option value="PT">Part Time</option>
                            </select>
                            <div class="invalid-feedback" id="error-stts_kerja"></div>
                        </div>

                    </div>

                    <!-- Informasi Gaji -->
                    <div class="mt-4 mb-3">
                        <small class="text-muted fw-semibold">INFORMASI GAJI</small>
                        <hr class="mt-1 mb-3">
                    </div>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Masa Kerja</label>
                            <select name="masa_kerja" class="form-select form-select-sm">
                                <option value="">-- Pilih Status --</option>
                                <option value="FT>1">Kontrak Lebih dari 1 tahun</option>
                                <option value="<1">Kurang dari 1 tahun</option>
                                <option value="PT">Part Time</option>
                            </select>
                            <div class="invalid-feedback" id="error-masa_kerja"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Gaji Pokok</label>
                            <input type="number" name="gaji_pokok" class="form-control form-control-sm">
                            <div class="invalid-feedback" id="error-gaji_pokok"></div>
                        </div>

                    </div>

                    <input type="hidden" name="gapokId" id="gapokId">

                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
                    Tutup
                </button>

                <button type="submit" form="gapokForm" id="submitForm" class="btn btn-primary btn-sm px-3">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan
                </button>
            </div>

        </div>
    </div>
</div>
