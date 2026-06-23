<div class="modal fade" id="modalGenerateLaboratorium" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formGenerateLaboratorium">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Preview Generate Laboratorium</h5>
                    <small class="text-muted">Cek sumber data, rumus, dan penerima sebelum menyimpan.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewLaboratoriumLoading" class="text-center py-4">
                    <span class="spinner-border text-success"></span>
                    <div class="text-muted mt-2">Menghitung preview laboratorium...</div>
                </div>

                <div id="previewLaboratoriumContent" class="d-none">
                    <div class="laboratorium-simple-head">
                        <div class="laboratorium-simple-main">
                            <div class="laboratorium-simple-icon"><i class="mdi mdi-radiology-box-outline"></i></div>
                            <div>
                                <div class="laboratorium-simple-title" id="generateLaboratoriumTitle">Memuat preview...</div>
                                <div class="laboratorium-simple-text" id="generateLaboratoriumText">
                                    Cek data dan nominal sebelum generate.
                                </div>
                            </div>
                        </div>
                        <span class="laboratorium-simple-badge" id="generateLaboratoriumTypeBadge">Jenis</span>
                    </div>

                    <div class="laboratorium-source-note" id="previewLaboratoriumSourceNote"></div>

                    <div class="laboratorium-preview-grid mb-3" id="previewLaboratoriumStats"></div>

                    <div class="laboratorium-simple-section mb-3">
                        <div class="laboratorium-simple-section-title">Rumus yang Dipakai</div>
                        <div class="laboratorium-info-list" id="previewLaboratoriumFormula"></div>
                    </div>

                    <div class="laboratorium-simple-section mb-0">
                        <div class="laboratorium-simple-section-title">Penerima Premi Petugas</div>
                        <div class="laboratorium-simple-section-subtitle" id="previewLaboratoriumRecipientSubtitle">-</div>
                        <div class="laboratorium-recipient-list mt-2" id="previewLaboratoriumRecipients"></div>
                        <div id="previewLaboratoriumQuality"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitGenerateLaboratorium" disabled>
                    <i class="mdi mdi-content-save-check-outline me-1"></i>
                    Generate dan Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalConfigLaboratorium" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formConfigLaboratorium">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalConfigLaboratoriumLabel">Konfigurasi Premi Laboratorium</h5>
                    <small class="text-muted">Konfigurasi UMUM dan BPJS disimpan terpisah.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenisConfigLaboratorium">

                <div class="laboratorium-simple-head">
                    <div class="laboratorium-simple-main">
                        <div class="laboratorium-simple-icon"><i class="mdi mdi-tune-variant"></i></div>
                        <div>
                            <div class="laboratorium-simple-title" id="configLaboratoriumRuleTitle">Aturan Data</div>
                            <div class="laboratorium-simple-text" id="configLaboratoriumRuleText">
                                Pilih jenis untuk melihat aturan sumber data.
                            </div>
                        </div>
                    </div>
                    <span class="laboratorium-simple-badge" id="configLaboratoriumTypeBadge">Jenis</span>
                </div>

                <div class="laboratorium-simple-section mb-3">
                    <div class="laboratorium-simple-section-title">Ringkasan Konfigurasi</div>
                    <div class="laboratorium-info-list" id="configLaboratoriumFormulaPreview"></div>
                </div>

                <div class="laboratorium-config-grid">
                    <div class="laboratorium-config-section">
                        <h6><i class="mdi mdi-account-hard-hat-outline"></i> Premi Petugas</h6>
                        <div class="mb-3">
                            <label class="form-label">Mode Hitung</label>
                            <select class="form-select" id="configPetugasMode">
                                <option value="percent">Persen dari basis</option>
                                <option value="nominal">Nominal tetap</option>
                                <option value="bersama_divider">Premi bersama dibagi pembagi</option>
                            </select>
                            <small class="laboratorium-field-help" id="configPetugasModeHelp">
                                UMUM memakai bagian_laborat, BPJS memakai premi bersama dibagi pembagi.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Persentase</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configPetugasPercent">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="laboratorium-field-help">Dipakai jika mode hitung petugas = persen.</small>
                        </div>
                        <div class="mb-3 d-none" id="configBpjsDividerGroup">
                            <label class="form-label">Pembagi Petugas BPJS</label>
                            <div class="input-group">
                                <span class="input-group-text">Bagi</span>
                                <input type="number" step="1" min="1" max="999999" class="form-control"
                                    id="configBpjsPetugasDivider">
                            </div>
                            <small class="laboratorium-field-help">
                                Dipakai jika mode petugas = premi bersama dibagi pembagi.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nominal Tetap</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" class="form-control" id="configPetugasNominal">
                            </div>
                            <small class="laboratorium-field-help">Dipakai jika mode hitung petugas = nominal tetap.</small>
                        </div>
                    </div>

                    <div class="laboratorium-config-section">
                        <h6><i class="mdi mdi-account-group-outline"></i> Premi Bersama</h6>
                        <div class="mb-3">
                            <label class="form-label">Mode Hitung</label>
                            <select class="form-select" id="configBersamaMode">
                                <option value="source">Total manajemen</option>
                                <option value="percent">Persen dari basis</option>
                                <option value="nominal">Nominal tetap</option>
                            </select>
                            <small class="laboratorium-field-help">
                                UMUM default mengambil total manajemen, BPJS default 4% dari bagian_rs.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Persentase dari Basis</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configBersamaPercent">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="laboratorium-field-help">Dipakai jika mode premi bersama = persen.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nominal Tetap</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" class="form-control" id="configBersamaNominal">
                            </div>
                            <small class="laboratorium-field-help">Dipakai jika mode premi bersama = nominal tetap.</small>
                        </div>
                    </div>
                </div>

                <div class="laboratorium-config-section mt-3">
                    <label class="form-label fw-bold">Pegawai Penerima Premi Petugas</label>
                    <select class="form-select" id="configLaboratoriumRecipients" multiple></select>
                    <small class="text-muted d-block mt-2" id="countLaboratoriumRecipients">0 penerima dipilih</small>
                    <div class="laboratorium-recipient-list mt-3" id="configLaboratoriumRecipientPreview"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitConfigLaboratorium">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailLaboratorium" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Detail Premi Laboratorium</h5>
                    <small class="text-muted">Audit hasil generate dan pembagian per pegawai.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailLaboratoriumLoading" class="text-center py-5">
                    <span class="spinner-border text-success"></span>
                    <div class="text-muted mt-2">Memuat detail laboratorium...</div>
                </div>

                <div id="detailLaboratoriumContent" class="d-none">
                    <div class="laboratorium-simple-head">
                        <div class="laboratorium-simple-main">
                            <div class="laboratorium-simple-icon"><i class="mdi mdi-clipboard-text-search-outline"></i></div>
                            <div>
                                <div class="laboratorium-simple-title" id="detailLaboratoriumTitle">-</div>
                                <div class="laboratorium-simple-text" id="detailLaboratoriumMeta">-</div>
                                <div class="laboratorium-simple-text" id="detailLaboratoriumSourceMeta">-</div>
                            </div>
                        </div>
                        <span class="laboratorium-simple-badge" id="detailLaboratoriumStatusBadge">Status</span>
                    </div>

                    <div class="laboratorium-detail-grid mb-3" id="detailLaboratoriumStats"></div>

                    <div class="laboratorium-simple-section mb-3">
                        <div class="laboratorium-simple-section-title">Rumus Tersimpan</div>
                        <div class="laboratorium-info-list" id="detailLaboratoriumFormula"></div>
                    </div>

                    <div class="laboratorium-simple-section mb-0">
                        <div class="laboratorium-simple-section-title">Penerima Premi Petugas</div>
                        <div class="laboratorium-simple-section-subtitle" id="detailLaboratoriumRecipientSubtitle">-</div>
                        <div class="laboratorium-recipient-list mt-2" id="detailLaboratoriumRecipients"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
