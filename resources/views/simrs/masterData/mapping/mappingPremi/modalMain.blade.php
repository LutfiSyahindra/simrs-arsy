<div class="modal fade" id="premiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold">Mapping Premi</h5>
                    <small class="text-muted">
                        Pilih premi, jenis tindakan, dan persentase yang berlaku.
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="premiForm">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label">Jenis Premi</label>
                        <select name="jnsPremi_id" id="jenisPremiSelect" class="form-select">
                            <option value="">-- Pilih Jenis Premi --</option>
                        </select>
                        <div class="invalid-feedback" id="error-jnsPremi_id"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-semibold">Jenis Tindakan</div>
                            <small class="text-muted">Persentase dapat berbeda untuk setiap jenis tindakan.</small>
                        </div>
                        <button type="button" id="addPremiMappingRow" class="btn btn-sm btn-outline-primary">
                            <i class="mdi mdi-plus"></i> Tambah Baris
                        </button>
                    </div>

                    <div id="premiMappingContainer" class="d-flex flex-column gap-3"></div>
                    <div class="invalid-feedback d-block" id="error-mappings"></div>
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <div class="me-auto small text-muted">
                    Data disimpan ke tabel <span class="fw-semibold">mapping_premi</span>.
                </div>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="premiForm" class="btn btn-primary">
                    <i class="mdi mdi-content-save-outline"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
