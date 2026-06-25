<div class="modal fade" id="modalGenerateIcu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content icu-modal-content" id="formGenerateIcu">
            <div class="modal-header icu-modal-header">
                <div class="icu-simple-main">
                    <div class="icu-simple-icon"><i class="mdi mdi-play-circle-outline"></i></div>
                    <div>
                        <h5 class="modal-title mb-0">Preview Generate NICU</h5>
                        <small class="text-muted">Audit sumber data, mapping, dan formula premi sebelum disimpan.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewIcuLoading" class="text-center py-4">
                    <span class="spinner-border text-danger"></span>
                    <div class="text-muted mt-2">Menghitung preview NICU...</div>
                </div>

                <div id="previewIcuContent" class="d-none">
                    <div class="icu-simple-head">
                        <div class="icu-simple-main">
                            <div class="icu-simple-icon"><i class="mdi mdi-heart-pulse"></i></div>
                            <div>
                                <div class="icu-simple-title" id="generateNicuTitle">Memuat preview...</div>
                                <div class="icu-simple-text" id="generateNicuText">Cek data tindakan dan nominal premi.</div>
                            </div>
                        </div>
                        <span class="icu-simple-badge" id="generateNicuTypeBadge">Jenis</span>
                    </div>

                    <div class="icu-state-banner neutral" id="previewIcuStateBanner">
                        <i class="mdi mdi-progress-clock"></i>
                        <div>
                            <div class="icu-state-title">Menyiapkan audit generate</div>
                            <div class="icu-state-text">Preview akan memeriksa konfigurasi, data sumber, dan penerima premi.</div>
                        </div>
                    </div>

                    <div class="icu-flow mb-3" id="previewIcuFlow"></div>
                    <div class="icu-insight-strip mb-3" id="previewIcuInsights"></div>

                    <div class="icu-preview-grid mb-3" id="previewIcuStats"></div>

                    <div class="icu-detail-split mb-3">
                        <div class="icu-simple-section">
                            <div class="icu-simple-section-title">Formula Aktif</div>
                            <div class="icu-info-list" id="previewIcuFormula"></div>
                        </div>

                        <div class="icu-simple-section">
                            <div class="icu-simple-section-title">Penerima Premi</div>
                            <div class="icu-info-list" id="previewIcuRecipients"></div>
                        </div>
                    </div>

                    <div id="previewIcuWarnings"></div>

                    <div class="icu-simple-section mb-0">
                        <div class="icu-simple-section-title">Sampel Tindakan Terambil</div>
                        <div class="icu-simple-section-subtitle" id="previewIcuDetailSubtitle">-</div>
                        <div class="icu-detail-table mt-2" id="previewIcuDetails"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitGenerateIcu" disabled>
                    <i class="mdi mdi-content-save-check-outline me-1"></i>
                    Generate dan Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalConfigIcu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content icu-modal-content" id="formConfigIcu">
            <div class="modal-header icu-modal-header">
                <div class="icu-simple-main">
                    <div class="icu-simple-icon"><i class="mdi mdi-tune-variant"></i></div>
                    <div>
                        <h5 class="modal-title mb-0">Konfigurasi Premi NICU</h5>
                        <small class="text-muted">Konfigurasi UMUM dan BPJS disimpan terpisah.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenisConfigIcu">

                <div class="icu-simple-head">
                    <div class="icu-simple-main">
                        <div class="icu-simple-icon"><i class="mdi mdi-tune-variant"></i></div>
                        <div>
                            <div class="icu-simple-title" id="configIcuTitle">Konfigurasi NICU</div>
                            <div class="icu-simple-text" id="configIcuText">Pilih mapping tindakan dan persentase distribusi.</div>
                        </div>
                    </div>
                    <span class="icu-simple-badge" id="configIcuTypeBadge">Jenis</span>
                </div>

                <div class="icu-state-banner neutral" id="configIcuHealthBanner">
                    <i class="mdi mdi-clipboard-check-outline"></i>
                    <div>
                        <div class="icu-state-title">Konfigurasi siap disunting</div>
                        <div class="icu-state-text">Pilih mapping, tindakan kritikal, formula, dan penerima premi untuk jenis NICU aktif.</div>
                    </div>
                </div>

                <div class="icu-config-shell">
                    <aside class="icu-config-rail">
                        <div class="icu-config-heading">Aturan Data NICU</div>
                        <div class="icu-config-copy" id="configIcuRuleCopy">
                            UMUM memakai periode aktif, BPJS memakai data satu bulan sebelumnya.
                        </div>
                        <div class="icu-config-pill-row">
                            <span class="icu-config-pill"><i class="mdi mdi-bed-outline me-1"></i> kamar_inap NICU</span>
                            <span class="icu-config-pill"><i class="mdi mdi-clipboard-pulse-outline me-1"></i> rawat jalan/inap</span>
                            <span class="icu-config-pill"><i class="mdi mdi-shape-outline me-1"></i> multi mapping</span>
                        </div>

                        <div class="mb-3">
                            <div class="icu-config-section-title">
                                <i class="mdi mdi-shape-plus-outline"></i>
                                Mapping Tindakan
                            </div>
                            <select class="form-select" id="configIcuMapping" multiple></select>
                            <small class="text-muted d-block mt-2">
                                Bisa memilih lebih dari satu jenis mapping. Tindakan dalam range NICU harus cocok dengan salah satunya.
                            </small>
                        </div>

                        <div>
                            <div class="icu-config-section-title">
                                <i class="mdi mdi-alert-decagram-outline"></i>
                                Tindakan Kritikal Khusus
                            </div>
                            <select class="form-select" id="configCriticalActionName" multiple></select>
                            <small class="text-muted d-block mt-2">
                                Bisa memilih lebih dari satu nama tindakan rawat inap Khanza. Kode tindakan diabaikan, yang disimpan hanya namanya.
                            </small>
                        </div>

                        <div class="icu-config-live-total">
                            <small>Formula Utama</small>
                            <strong id="configIcuLiveSummary">30% pool, khusus 25%, reguler 75% / pembagi</strong>
                        </div>
                    </aside>

                    <div class="icu-config-main-card">
                        <div class="icu-config-section-title">
                            <i class="mdi mdi-calculator-variant-outline"></i>
                            Persentase Premi
                        </div>
                        <div class="row g-3 mb-3 icu-field-grid">
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Pool Perawat NICU</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configPerawatIcuPercent">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Pegawai NICU Khusus</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configPegawaiIcuKhususPercent">
                                    <span class="input-group-text">% dari NICU</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Perawat NICU Reguler</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configPerawatIcuRegulerPercent">
                                    <span class="input-group-text">% dari NICU</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Pembagi Pool Reguler</label>
                                <input type="number" min="1" max="999" class="form-control"
                                    id="configPerawatIcuDivider">
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Premi Bersama</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configPremiBersamaPercent">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Premi Medis</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configPremiMedisPercent">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Pembagi Premi Medis</label>
                                <input type="number" min="1" max="999999" class="form-control"
                                    id="configPremiMedisDivider">
                            </div>
                        </div>

                        <div class="icu-config-dashboard mb-3">
                            <div class="icu-simple-section">
                                <div class="icu-simple-section-title">Distribusi Formula</div>
                                <div id="configIcuDistributionBars"></div>
                            </div>
                            <div class="icu-simple-section">
                                <div class="icu-simple-section-title">Kesiapan Penerima</div>
                                <div id="configRecipientHealth"></div>
                            </div>
                        </div>

                        <div class="icu-config-section-title">
                            <i class="mdi mdi-account-multiple-check-outline"></i>
                            Penerima Premi
                        </div>
                        <div class="icu-recipient-grid mb-3">
                            <div class="icu-recipient-panel">
                                <label class="form-label">
                                    <i class="mdi mdi-account-heart-outline"></i>
                                    Perawat NICU
                                </label>
                                <select class="form-select" id="configPerawatIcuRecipients" multiple></select>
                                <div class="icu-recipient-help" id="countPerawatIcuRecipients">
                                    0 penerima dipilih. Jumlah penerima harus mengikuti pembagi pool reguler.
                                </div>
                            </div>
                            <div class="icu-recipient-panel">
                                <label class="form-label">
                                    <i class="mdi mdi-account-star-outline"></i>
                                    Pegawai NICU Khusus
                                </label>
                                <select class="form-select" id="configPegawaiIcuKhususRecipients" multiple></select>
                                <div class="icu-recipient-help" id="countPegawaiIcuKhususRecipients">
                                    0 penerima dipilih untuk pool khusus NICU.
                                </div>
                            </div>
                        </div>

                        <div class="icu-simple-section">
                            <div class="icu-simple-section-title">Ringkasan Formula</div>
                            <div class="icu-info-list" id="configIcuFormulaPreview"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitConfigIcu">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailIcu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content icu-modal-content">
            <div class="modal-header icu-modal-header">
                <div class="icu-simple-main">
                    <div class="icu-simple-icon"><i class="mdi mdi-clipboard-text-search-outline"></i></div>
                    <div>
                        <h5 class="modal-title mb-0">Detail Generate NICU</h5>
                        <small class="text-muted">Audit tindakan yang tersimpan dan pembagian premi.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailIcuLoading" class="text-center py-5">
                    <span class="spinner-border text-danger"></span>
                    <div class="text-muted mt-2">Memuat detail NICU...</div>
                </div>

                <div id="detailIcuContent" class="d-none">
                    <div class="icu-detail-premium">
                        <div class="icu-detail-hero">
                            <div class="icu-detail-hero-main">
                                <div class="icu-simple-icon"><i class="mdi mdi-clipboard-text-search-outline"></i></div>
                                <div>
                                    <div class="icu-detail-title" id="detailIcuTitle">-</div>
                                    <div class="icu-detail-subtitle" id="detailIcuMeta">-</div>
                                    <div class="icu-detail-subtitle" id="detailIcuSourceMeta">-</div>
                                </div>
                            </div>
                            <div class="icu-detail-badge-row">
                                <span class="icu-simple-badge" id="detailIcuStatusBadge">Status</span>
                                <span class="icu-simple-badge info" id="detailIcuSourceBadge">Sumber</span>
                            </div>
                        </div>

                        <div class="icu-detail-kpi-grid" id="detailIcuKpiGrid"></div>

                        <div class="icu-state-banner neutral compact" id="detailIcuNarrative">
                            <i class="mdi mdi-file-search-outline"></i>
                            <div>
                                <div class="icu-state-title">Memuat audit detail</div>
                                <div class="icu-state-text">Data tersimpan akan dirangkum berdasarkan periode, mapping, penerima, dan tindakan.</div>
                            </div>
                        </div>

                        <ul class="nav nav-pills icu-detail-tabs" id="detailIcuTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="detailIcuSummaryTab" data-bs-toggle="pill"
                                    data-bs-target="#detailIcuSummaryPane" type="button" role="tab">
                                    <i class="mdi mdi-view-dashboard-outline"></i>
                                    Ringkasan
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="detailIcuRecipientTab" data-bs-toggle="pill"
                                    data-bs-target="#detailIcuRecipientPane" type="button" role="tab">
                                    <i class="mdi mdi-account-multiple-check-outline"></i>
                                    Penerima
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="detailIcuActionTab" data-bs-toggle="pill"
                                    data-bs-target="#detailIcuActionPane" type="button" role="tab">
                                    <i class="mdi mdi-format-list-bulleted-square"></i>
                                    Tindakan
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content icu-detail-tab-content">
                            <div class="tab-pane fade show active" id="detailIcuSummaryPane" role="tabpanel"
                                aria-labelledby="detailIcuSummaryTab">
                                <div class="icu-detail-summary-grid">
                                    <div class="icu-simple-section">
                                        <div class="icu-simple-section-title">Formula & Pool</div>
                                        <div class="icu-info-list compact" id="detailIcuFormula"></div>
                                    </div>
                                    <div class="icu-simple-section">
                                        <div class="icu-simple-section-title">Mapping & Kritikal</div>
                                        <div id="detailIcuMappingAudit"></div>
                                    </div>
                                    <div class="icu-simple-section">
                                        <div class="icu-simple-section-title">Sumber Tindakan</div>
                                        <div class="icu-info-list compact" id="detailIcuSourceGroups"></div>
                                    </div>
                                    <div class="icu-simple-section">
                                        <div class="icu-simple-section-title">Audit Singkat</div>
                                        <div class="icu-info-list compact" id="detailIcuOverview"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="detailIcuRecipientPane" role="tabpanel"
                                aria-labelledby="detailIcuRecipientTab">
                                <div class="icu-detail-tab-head">
                                    <div>
                                        <div class="icu-simple-section-title mb-1">Penerima Premi</div>
                                        <div class="icu-simple-section-subtitle">Ringkasan pool dan daftar penerima tersimpan.</div>
                                    </div>
                                </div>
                                <div class="icu-info-list compact mb-3" id="detailIcuRecipients"></div>
                                <div class="icu-detail-table compact premium" id="detailIcuRecipientTable"></div>
                            </div>

                            <div class="tab-pane fade" id="detailIcuActionPane" role="tabpanel"
                                aria-labelledby="detailIcuActionTab">
                                <div class="icu-detail-tab-head">
                                    <div>
                                        <div class="icu-simple-section-title mb-1">Rincian Tindakan</div>
                                        <div class="icu-simple-section-subtitle" id="detailIcuDetailSubtitle">-</div>
                                    </div>
                                    <div class="icu-detail-search">
                                        <i class="mdi mdi-magnify"></i>
                                        <input type="search" class="form-control form-control-sm" id="detailIcuSearch"
                                            placeholder="Cari pasien, tindakan, dokter...">
                                    </div>
                                </div>
                                <div class="icu-detail-table premium" id="detailIcuDetails"></div>
                                <div class="icu-detail-filter-note d-none" id="detailIcuFilterNote"></div>
                            </div>
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



