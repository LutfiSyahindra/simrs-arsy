<div class="modal fade" id="modalGenerateVk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalGenerateVkLabel">Generate VK</h5>
                    <small class="text-muted" id="generateVkModalSubtitle">
                        Tambahkan beberapa tindakan sekaligus, lalu generate dalam satu proses.
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formGenerateVk" class="vk-generate-form">
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3" id="generateVkModeInfo">
                        Input manual jumlah tindakan VK berdasarkan data real pelayanan.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Periode</label>
                            <input type="text" id="periodeGenerateVk" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis</label>
                            <input type="hidden" id="jenisGenerateVk">
                            <input type="text" id="jenisGenerateVkLabel" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div>
                                    <label class="form-label mb-0">Daftar Tindakan</label>
                                    <small class="text-muted d-block">
                                        Setiap baris dihitung dari jumlah tindakan x nominal hitung.
                                    </small>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddGenerateVkRow">
                                    <i class="mdi mdi-plus-circle-outline me-1"></i>
                                    Tambah Tindakan
                                </button>
                            </div>
                            <div id="generateVkRows" class="vk-input-rows"></div>
                            <div class="invalid-feedback d-block" id="entriesGenerateVkError"></div>
                        </div>
                        <div class="col-12">
                            <div class="vk-preview-total" id="previewTotalGenerateVk">
                                Rp 0
                            </div>
                            <small class="text-muted d-block text-center mt-1">
                                Total = jumlah tindakan x nominal hitung.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitGenerateVk" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-calculator-variant-outline me-1"></i>
                        Generate dan Hitung
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
