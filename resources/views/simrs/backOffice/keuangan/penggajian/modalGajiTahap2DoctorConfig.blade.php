<div class="modal fade" id="modalGajiTahap2DoctorConfig" tabindex="-1"
    aria-labelledby="modalGajiTahap2DoctorConfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content payroll-modal">
            <div class="modal-header">
                <div class="payroll-modal-title">
                    <div class="payroll-modal-icon is-amber">
                        <i class="mdi mdi-account-cog-outline mdi-24px"></i>
                    </div>
                    <div>
                        <div class="payroll-kicker">Aturan Tahap 2</div>
                        <h5 class="modal-title fw-bold mb-0" id="modalGajiTahap2DoctorConfigLabel">Konfigurasi Dokter Tahap 2</h5>
                        <small class="payroll-muted-copy">Dokter yang masuk tahap 2 dan komponen yang dibayarkan</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="payroll-config-overview mb-3">
                    <div class="payroll-config-stat">
                        <span>Dokter Terdaftar</span>
                        <strong id="stage2DoctorConfigCount">0 dokter</strong>
                    </div>
                    <div class="payroll-config-stat">
                        <span>Opsi Premi</span>
                        <strong id="stage2DoctorPremiumCount">0 opsi</strong>
                    </div>
                    <div class="payroll-config-stat">
                        <span>Komponen Gaji</span>
                        <strong>STR/Gaji Pokok</strong>
                    </div>
                </div>

                <div class="payroll-config-toolbar mb-3">
                    <div>
                        <label class="payroll-field-label">Dokter</label>
                        <select id="configStage2DoctorSelect" class="form-select form-select-sm w-100"></select>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddStage2DoctorConfig">
                        <i class="mdi mdi-plus me-1"></i>
                        Tambah
                    </button>
                </div>

                <div id="stage2DoctorConfigLoading" class="text-center text-muted py-4 d-none">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Memuat konfigurasi...
                </div>

                <div id="stage2DoctorConfigEmpty" class="payroll-config-empty d-none">
                    Belum ada dokter yang dikonfigurasi untuk gaji tahap 2.
                </div>

                <div id="stage2DoctorConfigRows" class="payroll-config-list"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveStage2DoctorConfig">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </div>
    </div>
</div>
