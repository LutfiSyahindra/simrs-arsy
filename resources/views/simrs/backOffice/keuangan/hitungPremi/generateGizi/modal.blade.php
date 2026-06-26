<div class="modal fade" id="modalGenerateGizi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content gizi-modal-content" id="formGenerateGizi">
            <div class="modal-header gizi-modal-header">
                <div class="gizi-inline">
                    <div class="gizi-simple-icon"><i class="mdi mdi-play-circle-outline"></i></div>
                    <div>
                        <h5 class="modal-title mb-0">Preview Generate Gizi</h5>
                        <small class="text-muted">Audit sumber data, mapping, dan pembagian sebelum disimpan.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewGiziLoading" class="text-center py-4">
                    <span class="spinner-border text-success"></span>
                    <div class="text-muted mt-2">Menghitung preview Gizi...</div>
                </div>

                <div id="previewGiziContent" class="d-none">
                    <div class="gizi-simple-head">
                        <div class="gizi-inline">
                            <div class="gizi-simple-icon"><i class="mdi mdi-food-apple-outline"></i></div>
                            <div>
                                <div class="gizi-simple-title" id="generateGiziTitle">Memuat preview...</div>
                                <div class="gizi-simple-text" id="generateGiziText">Cek data tindakan dan nominal premi.</div>
                            </div>
                        </div>
                        <span class="gizi-simple-badge" id="generateGiziTypeBadge">Jenis</span>
                    </div>

                    <div class="gizi-state-banner neutral" id="previewGiziStateBanner">
                        <i class="mdi mdi-progress-clock"></i>
                        <div>
                            <div class="gizi-state-title">Menyiapkan audit generate</div>
                            <div class="gizi-state-text">Preview akan memeriksa konfigurasi, data sumber, dan penerima premi.</div>
                        </div>
                    </div>

                    <div class="gizi-preview-grid mb-3" id="previewGiziStats"></div>

                    <div class="row g-3 mb-3">
                        <div class="col-lg-6">
                            <div class="gizi-simple-section h-100">
                                <div class="gizi-simple-section-title">
                                    <i class="mdi mdi-calculator-variant-outline"></i> Formula Aktif
                                </div>
                                <div class="gizi-info-list" id="previewGiziFormula"></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="gizi-simple-section h-100">
                                <div class="gizi-simple-section-title">
                                    <i class="mdi mdi-account-multiple-check-outline"></i> Penerima Premi
                                </div>
                                <div class="gizi-info-list" id="previewGiziRecipients"></div>
                            </div>
                        </div>
                    </div>

                    <div id="previewGiziWarnings"></div>

                    <div class="gizi-simple-section">
                        <div class="gizi-simple-section-title">
                            <i class="mdi mdi-clipboard-text-search-outline"></i> Sampel Tindakan Terambil
                        </div>
                        <div class="gizi-info-note" id="previewGiziDetailSubtitle">-</div>
                        <div class="gizi-detail-table mt-2" id="previewGiziDetails"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitGenerateGizi" disabled>
                    <i class="mdi mdi-content-save-check-outline me-1"></i>
                    Generate dan Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalConfigGizi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content gizi-modal-content" id="formConfigGizi">
            <div class="modal-header gizi-modal-header">
                <div class="gizi-inline">
                    <div class="gizi-simple-icon"><i class="mdi mdi-tune-variant"></i></div>
                    <div>
                        <h5 class="modal-title mb-0">Konfigurasi Premi Gizi</h5>
                        <small class="text-muted">Konfigurasi UMUM dan BPJS disimpan terpisah.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenisConfigGizi">

                <div class="gizi-simple-head">
                    <div class="gizi-inline">
                        <div class="gizi-simple-icon"><i class="mdi mdi-clipboard-pulse-outline"></i></div>
                        <div>
                            <div class="gizi-simple-title" id="configGiziTitle">Konfigurasi Gizi</div>
                            <div class="gizi-simple-text" id="configGiziText">Pilih mapping Konsul/Diit, formula, dan penerima.</div>
                        </div>
                    </div>
                    <span class="gizi-simple-badge" id="configGiziTypeBadge">Jenis</span>
                </div>

                <div class="gizi-state-banner neutral" id="configGiziHealthBanner">
                    <i class="mdi mdi-clipboard-check-outline"></i>
                    <div>
                        <div class="gizi-state-title">Konfigurasi siap disunting</div>
                        <div class="gizi-state-text">Semua formula dapat diubah tanpa mengubah kode.</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-lg-4">
                        <div class="gizi-simple-section h-100">
                            <div class="gizi-simple-section-title">
                                <i class="mdi mdi-calendar-sync-outline"></i> Alur Sumber Data
                            </div>
                            <label class="form-label">Periode sumber</label>
                            <select class="form-select" id="configGiziSourcePeriodMode">
                                <option value="current">Periode Generate</option>
                                <option value="previous">Bulan Sebelumnya</option>
                            </select>
                            <div class="gizi-info-note">
                                Filter penjamin tetap mengikuti jenis: UMUM selain BPJ dan -, BPJS hanya BPJ.
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="gizi-simple-section h-100">
                            <div class="gizi-simple-section-title">
                                <i class="mdi mdi-shape-plus-outline"></i> Mapping Konsul
                            </div>
                            <select class="form-select" id="configGiziKonsulMapping" multiple></select>
                            <div class="gizi-info-note">Bisa memilih lebih dari satu mapping untuk kelompok Konsul.</div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="gizi-simple-section h-100">
                            <div class="gizi-simple-section-title">
                                <i class="mdi mdi-shape-plus-outline"></i> Mapping Diit
                            </div>
                            <select class="form-select" id="configGiziDiitMapping" multiple></select>
                            <div class="gizi-info-note">Bisa memilih lebih dari satu mapping untuk kelompok Diit.</div>
                        </div>
                    </div>
                </div>

                <div class="gizi-simple-section mb-3">
                    <div class="gizi-simple-section-title">
                        <i class="mdi mdi-calculator-variant-outline"></i> Formula Pembagian
                    </div>
                    <div class="gizi-config-grid">
                        <div>
                            <label class="form-label">Konsul ke Pegawai</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configKonsulPegawaiPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Konsul ke Premi Bersama</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configKonsulBersamaPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Diit ke Petugas</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configDiitPetugasPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Pembagi Diit</label>
                            <input type="number" min="1" max="999" class="form-control" id="configDiitPetugasDivider">
                        </div>
                        <div>
                            <label class="form-label">Diit ke Premi Bersama</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configDiitBersamaPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Alur Premi Bersama Diit</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="configDiitBersamaEnabled">
                                <label class="form-check-label" for="configDiitBersamaEnabled">
                                    Aktifkan premi bersama Diit
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="gizi-recipient-grid mb-3">
                    <div class="gizi-recipient-panel p-3">
                        <label class="form-label">
                            <i class="mdi mdi-account-heart-outline"></i>
                            Pegawai Konsul
                        </label>
                        <select class="form-select" id="configKonsulRecipients" multiple></select>
                        <div class="gizi-info-note" id="countKonsulRecipients">0 pegawai dipilih.</div>
                    </div>
                    <div class="gizi-recipient-panel p-3">
                        <label class="form-label">
                            <i class="mdi mdi-account-star-outline"></i>
                            Petugas Diit
                        </label>
                        <select class="form-select" id="configDiitRecipients" multiple></select>
                        <div class="gizi-info-note" id="countDiitRecipients">0 petugas dipilih.</div>
                    </div>
                </div>

                <div class="gizi-simple-section">
                    <div class="gizi-simple-section-title">
                        <i class="mdi mdi-chart-donut"></i> Ringkasan Formula
                    </div>
                    <div class="gizi-info-list" id="configGiziFormulaPreview"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitConfigGizi">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailGizi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content gizi-modal-content">
            <div class="modal-header gizi-modal-header">
                <div class="gizi-inline">
                    <div class="gizi-simple-icon"><i class="mdi mdi-file-search-outline"></i></div>
                    <div>
                        <h5 class="modal-title mb-0" id="detailGiziTitle">Detail Generate Gizi</h5>
                        <small class="text-muted">Detail tindakan sumber dan hasil pembagian.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailGiziLoading" class="text-center py-4">
                    <span class="spinner-border text-success"></span>
                    <div class="text-muted mt-2">Memuat detail Gizi...</div>
                </div>

                <div id="detailGiziContent" class="d-none">
                    <div class="gizi-simple-head">
                        <div class="gizi-inline">
                            <div class="gizi-simple-icon"><i class="mdi mdi-food-apple-outline"></i></div>
                            <div>
                                <div class="gizi-simple-title" id="detailGiziHeading">-</div>
                                <div class="gizi-simple-text" id="detailGiziSubheading">-</div>
                            </div>
                        </div>
                        <span class="gizi-simple-badge" id="detailGiziStatus">Status</span>
                    </div>

                    <div class="gizi-detail-meta" id="detailGiziMeta"></div>

                    <div class="gizi-detail-tabs" role="tablist" aria-label="Detail Gizi">
                        <button type="button" class="gizi-detail-tab active" data-detail-tab="overview">
                            <i class="mdi mdi-view-dashboard-outline"></i> Ringkasan
                        </button>
                        <button type="button" class="gizi-detail-tab" data-detail-tab="recipients">
                            <i class="mdi mdi-account-multiple-check-outline"></i> Penerima
                        </button>
                        <button type="button" class="gizi-detail-tab" data-detail-tab="actions">
                            <i class="mdi mdi-format-list-bulleted"></i> Tindakan
                        </button>
                    </div>

                    <div class="gizi-detail-panel active" data-detail-panel="overview">
                        <div class="gizi-detail-kpi mb-3" id="detailGiziKpi"></div>

                        <div class="row g-3 mb-3">
                            <div class="col-lg-6">
                                <div class="gizi-simple-section h-100">
                                    <div class="gizi-simple-section-title">
                                        <i class="mdi mdi-chart-donut"></i> Pool Pembagian
                                    </div>
                                    <div class="gizi-info-list" id="detailGiziPools"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="gizi-simple-section h-100">
                                    <div class="gizi-simple-section-title">
                                        <i class="mdi mdi-finance"></i> Komposisi Premi Bersama
                                    </div>
                                    <div class="gizi-info-list" id="detailGiziTogether"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="gizi-simple-section h-100">
                                    <div class="gizi-simple-section-title">
                                        <i class="mdi mdi-shape-outline"></i> Kelompok Data
                                    </div>
                                    <div class="gizi-info-list" id="detailGiziGroups"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="gizi-simple-section h-100">
                                    <div class="gizi-simple-section-title">
                                        <i class="mdi mdi-database-outline"></i> Tabel Sumber
                                    </div>
                                    <div class="gizi-info-list" id="detailGiziSources"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="gizi-detail-panel" data-detail-panel="recipients">
                        <div class="gizi-simple-section">
                            <div class="gizi-simple-section-title">
                                <i class="mdi mdi-account-multiple-check-outline"></i> Penerima Premi per Pegawai
                            </div>
                            <div class="gizi-info-note mb-2" id="detailGiziRecipientNote">-</div>
                            <div class="gizi-info-list" id="detailGiziRecipients"></div>
                        </div>
                    </div>

                    <div class="gizi-detail-panel" data-detail-panel="actions">
                        <div class="gizi-simple-section">
                            <div class="gizi-simple-section-title">
                                <i class="mdi mdi-format-list-bulleted"></i> Detail Tindakan
                            </div>
                            <div class="gizi-detail-filterbar">
                                <input type="text" class="form-control" id="detailGiziSearch"
                                    placeholder="Cari pasien, no rawat, tindakan, atau pelaksana">
                                <select class="form-select" id="detailGiziSourceFilter">
                                    <option value="all">Semua tabel sumber</option>
                                </select>
                                <div class="gizi-segmented" aria-label="Filter kelompok detail">
                                    <button type="button" class="active" data-detail-group="all">Semua</button>
                                    <button type="button" data-detail-group="konsul">Konsul</button>
                                    <button type="button" data-detail-group="diit">Diit</button>
                                </div>
                                <div class="gizi-detail-count" id="detailGiziRowCount">0 baris</div>
                            </div>
                            <div class="gizi-detail-table" id="detailGiziRows"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
