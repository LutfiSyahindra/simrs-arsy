<div class="modal fade" id="modalDistribusi">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-semibold">
                    <i class="mdi mdi-account-switch text-danger me-1"></i>
                    Distribusi Potongan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Pegawai Sumber</label>
                        <select id="pegawaiSumber" class="form-select"></select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pegawai Tujuan</label>
                        <select id="pegawaiTujuan" class="form-select" multiple></select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Pilih Potongan</label>
                        <select id="potonganSelect" class="form-select" multiple></select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <button id="btnPreviewDistribusi" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-eye-outline me-1"></i>
                        Preview
                    </button>

                    <button type="button" id="prosesDistribusi" class="btn btn-danger">
                        <i class="mdi mdi-check me-1"></i>
                        Distribusikan
                    </button>
                </div>

                <div id="previewDistribusi" class="mt-4 d-none">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="fw-semibold text-dark">
                                Preview Distribusi
                            </div>
                            <small class="text-muted">
                                Hasil sebelum disimpan
                            </small>
                        </div>

                        <div id="previewContent" class="small"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
