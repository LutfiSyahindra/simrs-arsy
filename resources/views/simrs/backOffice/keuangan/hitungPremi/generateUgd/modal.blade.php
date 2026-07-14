<div class="modal fade" id="modalGenerateUgd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalGenerateUgdLabel">Generate UGD</h5>
                    <small class="text-muted" id="generateUgdModalSubtitle">
                        Tambahkan beberapa dokter sekaligus, lalu generate dalam satu proses.
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formGenerateUgd" class="ugd-generate-form">
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3" id="generateUgdModeInfo">
                        Input manual pasien UGD berdasarkan data real pelayanan.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Periode</label>
                            <input type="text" id="periodeGenerateUgd" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis</label>
                            <input type="hidden" id="jenisGenerateUgd">
                            <input type="text" id="jenisGenerateUgdLabel" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div>
                                    <label class="form-label mb-0">Daftar Dokter</label>
                                    <small class="text-muted d-block">
                                        Setiap baris dihitung dari jumlah pasien x nominal hitung.
                                    </small>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddGenerateUgdRow">
                                    <i class="mdi mdi-plus-circle-outline me-1"></i>
                                    Tambah Dokter
                                </button>
                            </div>
                            <div id="generateUgdRows" class="ugd-input-rows"></div>
                            <div class="invalid-feedback d-block" id="entriesGenerateUgdError"></div>
                        </div>
                        <div class="col-12">
                            <div class="ugd-preview-total" id="previewTotalGenerateUgd">
                                Rp 0
                            </div>
                            <small class="text-muted d-block text-center mt-1">
                                Total = jumlah pasien x nominal hitung.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitGenerateUgd" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-calculator-variant-outline me-1"></i>
                        Generate dan Hitung
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfigUgd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content border-0 rounded-3" id="formConfigUgd">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalConfigUgdLabel">Konfigurasi UGD</h5>
                    <small class="text-muted">Atur default nominal yang otomatis terisi saat generate.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenisConfigUgd">
                <div class="alert alert-info py-2">
                    Default nominal disimpan per plotting. Isi nominal untuk satu atau beberapa plotting sekaligus.
                </div>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <label class="form-label mb-0">Default Nominal <span id="configUgdTypeLabel">Umum</span></label>
                    <small class="text-muted">Isi 0 jika tidak ingin otomatis terisi.</small>
                </div>
                <div id="configUgdNominalContainer" class="d-grid gap-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="btnSubmitConfigUgd" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-content-save-check-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>
