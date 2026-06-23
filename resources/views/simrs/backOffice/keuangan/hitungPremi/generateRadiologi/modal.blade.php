<div class="modal fade" id="modalGenerateRadiologi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formGenerateRadiologi">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Preview Generate Radiologi</h5>
                    <small class="text-muted">Cek sumber data, rumus, dan penerima sebelum menyimpan.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewRadiologiLoading" class="text-center py-4">
                    <span class="spinner-border text-success"></span>
                    <div class="text-muted mt-2">Menghitung preview radiologi...</div>
                </div>

                <div id="previewRadiologiContent" class="d-none">
                    <div class="radiologi-simple-head">
                        <div class="radiologi-simple-main">
                            <div class="radiologi-simple-icon"><i class="mdi mdi-radiology-box-outline"></i></div>
                            <div>
                                <div class="radiologi-simple-title" id="generateRadiologiTitle">Memuat preview...</div>
                                <div class="radiologi-simple-text" id="generateRadiologiText">
                                    Cek data dan nominal sebelum generate.
                                </div>
                            </div>
                        </div>
                        <span class="radiologi-simple-badge" id="generateRadiologiTypeBadge">Jenis</span>
                    </div>

                    <div class="radiologi-source-note" id="previewRadiologiSourceNote"></div>

                    <div class="radiologi-preview-grid mb-3" id="previewRadiologiStats"></div>

                    <div class="radiologi-simple-section mb-3">
                        <div class="radiologi-simple-section-title">Rumus yang Dipakai</div>
                        <div class="radiologi-info-list" id="previewRadiologiFormula"></div>
                    </div>

                    <div class="radiologi-simple-section mb-0">
                        <div class="radiologi-simple-section-title">Penerima Premi Petugas</div>
                        <div class="radiologi-simple-section-subtitle" id="previewRadiologiRecipientSubtitle">-</div>
                        <div class="radiologi-recipient-list mt-2" id="previewRadiologiRecipients"></div>
                        <div id="previewRadiologiQuality"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitGenerateRadiologi" disabled>
                    <i class="mdi mdi-content-save-check-outline me-1"></i>
                    Generate dan Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalConfigRadiologi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formConfigRadiologi">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalConfigRadiologiLabel">Konfigurasi Premi Radiologi</h5>
                    <small class="text-muted">Konfigurasi UMUM dan BPJS disimpan terpisah.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenisConfigRadiologi">

                <div class="radiologi-simple-head">
                    <div class="radiologi-simple-main">
                        <div class="radiologi-simple-icon"><i class="mdi mdi-tune-variant"></i></div>
                        <div>
                            <div class="radiologi-simple-title" id="configRadiologiRuleTitle">Aturan Data</div>
                            <div class="radiologi-simple-text" id="configRadiologiRuleText">
                                Pilih jenis untuk melihat aturan sumber data.
                            </div>
                        </div>
                    </div>
                    <span class="radiologi-simple-badge" id="configRadiologiTypeBadge">Jenis</span>
                </div>

                <div class="radiologi-simple-section mb-3">
                    <div class="radiologi-simple-section-title">Ringkasan Konfigurasi</div>
                    <div class="radiologi-info-list" id="configRadiologiFormulaPreview"></div>
                </div>

                <div class="radiologi-config-grid">
                    <div class="radiologi-config-section">
                        <h6><i class="mdi mdi-account-hard-hat-outline"></i> Premi Petugas</h6>
                        <div class="mb-3">
                            <label class="form-label">Mode Hitung</label>
                            <select class="form-select" id="configPetugasMode">
                                <option value="percent">Persen dari basis</option>
                                <option value="nominal">Nominal tetap</option>
                            </select>
                            <small class="radiologi-field-help" id="configPetugasModeHelp">
                                Basis UMUM adalah tarif_tindakan_petugas, basis BPJS adalah biaya.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Persentase</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configPetugasPercent">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="radiologi-field-help">Dipakai jika mode hitung petugas = persen.</small>
                        </div>
                        <div class="mb-3 d-none" id="configBpjsFormulaGroup">
                            <label class="form-label">Rumus Persen BPJS</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text">Angka</span>
                                        <input type="number" step="0.0001" min="0" max="1" class="form-control"
                                            id="configBpjsPetugasFormulaRate">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text">Bagi</span>
                                        <input type="number" step="1" min="1" max="999999" class="form-control"
                                            id="configBpjsPetugasFormulaDivider">
                                    </div>
                                </div>
                            </div>
                            <small class="radiologi-field-help" id="configBpjsFormulaHelp">
                                Persen efektif dihitung otomatis dari angka / pembagi.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nominal Tetap</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" class="form-control" id="configPetugasNominal">
                            </div>
                            <small class="radiologi-field-help">Dipakai jika mode hitung petugas = nominal tetap.</small>
                        </div>
                    </div>

                    <div class="radiologi-config-section">
                        <h6><i class="mdi mdi-account-group-outline"></i> Premi Bersama</h6>
                        <div class="mb-3">
                            <label class="form-label">Mode Hitung</label>
                            <select class="form-select" id="configBersamaMode">
                                <option value="source">Total manajemen</option>
                                <option value="percent">Persen dari manajemen</option>
                                <option value="nominal">Nominal tetap</option>
                                <option value="petugas">Sama dengan hasil premi petugas</option>
                            </select>
                            <small class="radiologi-field-help">
                                BPJS dapat memakai hasil premi petugas sebagai nominal premi bersama.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Persentase dari Manajemen</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configBersamaPercent">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="radiologi-field-help">Dipakai jika mode premi bersama = persen.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nominal Tetap</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" class="form-control" id="configBersamaNominal">
                            </div>
                            <small class="radiologi-field-help">Dipakai jika mode premi bersama = nominal tetap.</small>
                        </div>
                    </div>
                </div>

                <div class="radiologi-config-section mt-3">
                    <label class="form-label fw-bold">Pegawai Penerima Premi Petugas</label>
                    <select class="form-select" id="configRadiologiRecipients" multiple></select>
                    <small class="text-muted d-block mt-2" id="countRadiologiRecipients">0 penerima dipilih</small>
                    <div class="radiologi-recipient-list mt-3" id="configRadiologiRecipientPreview"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitConfigRadiologi">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailRadiologi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Detail Premi Radiologi</h5>
                    <small class="text-muted">Audit hasil generate dan pembagian per pegawai.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailRadiologiLoading" class="text-center py-5">
                    <span class="spinner-border text-success"></span>
                    <div class="text-muted mt-2">Memuat detail radiologi...</div>
                </div>

                <div id="detailRadiologiContent" class="d-none">
                    <div class="radiologi-simple-head">
                        <div class="radiologi-simple-main">
                            <div class="radiologi-simple-icon"><i class="mdi mdi-clipboard-text-search-outline"></i></div>
                            <div>
                                <div class="radiologi-simple-title" id="detailRadiologiTitle">-</div>
                                <div class="radiologi-simple-text" id="detailRadiologiMeta">-</div>
                                <div class="radiologi-simple-text" id="detailRadiologiSourceMeta">-</div>
                            </div>
                        </div>
                        <span class="radiologi-simple-badge" id="detailRadiologiStatusBadge">Status</span>
                    </div>

                    <div class="radiologi-detail-grid mb-3" id="detailRadiologiStats"></div>

                    <div class="radiologi-simple-section mb-3">
                        <div class="radiologi-simple-section-title">Rumus Tersimpan</div>
                        <div class="radiologi-info-list" id="detailRadiologiFormula"></div>
                    </div>

                    <div class="radiologi-simple-section mb-0">
                        <div class="radiologi-simple-section-title">Penerima Premi Petugas</div>
                        <div class="radiologi-simple-section-subtitle" id="detailRadiologiRecipientSubtitle">-</div>
                        <div class="radiologi-recipient-list mt-2" id="detailRadiologiRecipients"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
