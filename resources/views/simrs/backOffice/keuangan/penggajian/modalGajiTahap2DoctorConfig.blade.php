<div class="modal fade" id="modalPayrollDoctorConfig" tabindex="-1"
    aria-labelledby="modalPayrollDoctorConfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content payroll-modal">
            <div class="modal-header">
                <div class="payroll-modal-title">
                    <div class="payroll-modal-icon is-amber">
                        <i class="mdi mdi-account-cog-outline mdi-24px"></i>
                    </div>
                    <div>
                        <div class="payroll-kicker">Aturan Payroll</div>
                        <h5 class="modal-title fw-bold mb-0" id="modalPayrollDoctorConfigLabel">Konfigurasi Gaji Tahap 1 & 2</h5>
                        <small class="payroll-muted-copy">Komponen dokter, pembulatan premi, dan total gaji</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <ul class="nav nav-pills payroll-config-tabs mb-3" id="payrollDoctorConfigTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="doctorConfigStage1Tab" data-bs-toggle="pill"
                            data-bs-target="#doctorConfigStage1Pane" type="button" role="tab"
                            aria-controls="doctorConfigStage1Pane" aria-selected="true" data-config-stage="stage1">
                            <i class="mdi mdi-numeric-1-circle-outline me-1"></i>
                            Tahap 1
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="doctorConfigStage2Tab" data-bs-toggle="pill"
                            data-bs-target="#doctorConfigStage2Pane" type="button" role="tab"
                            aria-controls="doctorConfigStage2Pane" aria-selected="false" data-config-stage="stage2">
                            <i class="mdi mdi-numeric-2-circle-outline me-1"></i>
                            Tahap 2
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="roundingConfigTab" data-bs-toggle="pill"
                            data-bs-target="#roundingConfigPane" type="button" role="tab"
                            aria-controls="roundingConfigPane" aria-selected="false">
                            <i class="mdi mdi-cash-sync me-1"></i>
                            Pembulatan
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="doctorConfigStage1Pane" role="tabpanel"
                        aria-labelledby="doctorConfigStage1Tab" tabindex="0">
                        <div class="payroll-config-overview mb-3">
                            <div class="payroll-config-stat">
                                <span>Dokter Terdaftar</span>
                                <strong id="stage1DoctorConfigCount">0 dokter</strong>
                            </div>
                            <div class="payroll-config-stat">
                                <span>Opsi Premi</span>
                                <strong id="stage1DoctorPremiumCount">0 opsi</strong>
                            </div>
                            <div class="payroll-config-stat">
                                <span>Komponen Gaji</span>
                                <strong id="stage1DoctorSalaryLabel">Gaji Pokok (Kehadiran)</strong>
                            </div>
                        </div>

                        <div class="payroll-config-toolbar mb-3">
                            <div>
                                <label class="payroll-field-label">Dokter UGD Kontrak</label>
                                <select id="configStage1DoctorSelect" class="form-select form-select-sm w-100"></select>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm btn-add-doctor-config"
                                data-config-stage="stage1">
                                <i class="mdi mdi-plus me-1"></i>
                                Tambah
                            </button>
                        </div>

                        <div id="stage1DoctorConfigLoading" class="text-center text-muted py-4 d-none">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Memuat konfigurasi...
                        </div>

                        <div id="stage1DoctorConfigEmpty" class="payroll-config-empty d-none">
                            Belum ada dokter UGD kontrak yang dikonfigurasi untuk gaji tahap 1.
                        </div>

                        <div id="stage1DoctorConfigRows" class="payroll-config-list"></div>
                    </div>

                    <div class="tab-pane fade" id="doctorConfigStage2Pane" role="tabpanel"
                        aria-labelledby="doctorConfigStage2Tab" tabindex="0">
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
                                <strong id="stage2DoctorSalaryLabel">STR/Gaji Pokok</strong>
                            </div>
                        </div>

                        <div class="payroll-config-toolbar mb-3">
                            <div>
                                <label class="payroll-field-label">Dokter</label>
                                <select id="configStage2DoctorSelect" class="form-select form-select-sm w-100"></select>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm btn-add-doctor-config"
                                data-config-stage="stage2">
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

                    <div class="tab-pane fade" id="roundingConfigPane" role="tabpanel"
                        aria-labelledby="roundingConfigTab" tabindex="0">
                        <div id="roundingConfigLoading" class="text-center text-muted py-4 d-none">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Memuat konfigurasi pembulatan...
                        </div>

                        <div id="roundingConfigForm" class="payroll-config-list">
                            <div class="payroll-config-row">
                                <div class="payroll-config-row-header">
                                    <div>
                                        <span class="employee-name">Nominal Premi Diterima</span>
                                        <span class="employee-subtext">Diterapkan pada setiap detail premi yang masuk ke gaji</span>
                                    </div>
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input payroll-rounding-enabled" type="checkbox"
                                            id="roundingPremiumEnabled" data-rounding-key="premium">
                                    </label>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="payroll-field-label">Kelipatan</label>
                                        <input type="number" min="1" step="1" class="form-control form-control-sm"
                                            id="roundingPremiumBase" value="1000">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="payroll-field-label">Mode</label>
                                        <select class="form-select form-select-sm" id="roundingPremiumMode">
                                            <option value="nearest">Terdekat</option>
                                            <option value="up" selected>Ke Atas</option>
                                            <option value="down">Ke Bawah</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="payroll-config-row">
                                <div class="payroll-config-row-header">
                                    <div>
                                        <span class="employee-name">Total Gaji Tahap 1</span>
                                        <span class="employee-subtext">Diterapkan setelah gaji dibayar dan tunjangan dijumlahkan</span>
                                    </div>
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input payroll-rounding-enabled" type="checkbox"
                                            id="roundingStage1Enabled" data-rounding-key="stage1" checked>
                                    </label>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="payroll-field-label">Kelipatan</label>
                                        <input type="number" min="1" step="1" class="form-control form-control-sm"
                                            id="roundingStage1Base" value="1000">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="payroll-field-label">Mode</label>
                                        <select class="form-select form-select-sm" id="roundingStage1Mode">
                                            <option value="nearest">Terdekat</option>
                                            <option value="up" selected>Ke Atas</option>
                                            <option value="down">Ke Bawah</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="payroll-config-row">
                                <div class="payroll-config-row-header">
                                    <div>
                                        <span class="employee-name">Total Gaji Tahap 2</span>
                                        <span class="employee-subtext">Diterapkan setelah pendapatan tahap 2 dikurangi potongan</span>
                                    </div>
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input payroll-rounding-enabled" type="checkbox"
                                            id="roundingStage2Enabled" data-rounding-key="stage2" checked>
                                    </label>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="payroll-field-label">Kelipatan</label>
                                        <input type="number" min="1" step="1" class="form-control form-control-sm"
                                            id="roundingStage2Base" value="1000">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="payroll-field-label">Mode</label>
                                        <select class="form-select form-select-sm" id="roundingStage2Mode">
                                            <option value="nearest">Terdekat</option>
                                            <option value="up" selected>Ke Atas</option>
                                            <option value="down">Ke Bawah</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSavePayrollDoctorConfig">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </div>
    </div>
</div>
