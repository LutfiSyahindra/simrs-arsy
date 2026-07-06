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
                                UMUM memakai jumlah x nominal. BPJS mengikuti konfigurasi persen, pembagi, dan mode pembagian.
                            </small>
                            <div class="vk-bpjs-preview d-none mt-2" id="previewBpjsGenerateVk"></div>
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

<div class="modal fade vk-detail-modal" id="modalDetailVk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-3 overflow-hidden">
            <div class="vk-detail-shell">
                <div class="vk-detail-hero">
                    <div class="vk-detail-hero-top">
                        <div class="vk-detail-identity">
                            <div class="vk-detail-icon">
                                <i class="mdi mdi-shield-account-outline"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="vk-eyebrow">Detail Generate BPJS</div>
                                <div class="vk-detail-title" id="detailVkTitle">VK BPJS</div>
                                <div class="vk-detail-meta" id="detailVkMeta">-</div>
                            </div>
                        </div>
                        <div class="vk-detail-actions">
                            <span class="vk-detail-status" id="detailVkStatus">
                                <i class="mdi mdi-lock-open-variant-outline"></i>
                                Terbuka
                            </span>
                            <button type="button" class="vk-detail-copy" id="btnCopyDetailVk">
                                <i class="mdi mdi-content-copy"></i>
                                Salin
                            </button>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                    </div>
                    <div class="vk-detail-kpis">
                        <div class="vk-detail-kpi">
                            <span>Total VK awal</span>
                            <strong id="detailVkAwal">Rp 0</strong>
                        </div>
                        <div class="vk-detail-kpi">
                            <span>Pool BPJS</span>
                            <strong id="detailVkPool">Rp 0</strong>
                        </div>
                        <div class="vk-detail-kpi">
                            <span>Hasil Rumus</span>
                            <strong id="detailVkHasil">Rp 0</strong>
                        </div>
                        <div class="vk-detail-kpi">
                            <span>Total Pegawai</span>
                            <strong id="detailVkDibagikan">Rp 0</strong>
                        </div>
                    </div>
                </div>
                <div class="vk-detail-body">
                    <div class="vk-detail-badges" id="detailVkBadges"></div>

                    <div class="vk-detail-tabs" role="tablist">
                        <button type="button" class="vk-detail-tab active" data-view="overview">
                            <i class="mdi mdi-view-dashboard-outline"></i>
                            Ringkasan
                        </button>
                        <button type="button" class="vk-detail-tab" data-view="pegawai">
                            <i class="mdi mdi-account-group-outline"></i>
                            Pegawai
                        </button>
                        <button type="button" class="vk-detail-tab" data-view="formula">
                            <i class="mdi mdi-calculator-variant-outline"></i>
                            Formula
                        </button>
                    </div>

                    <div class="vk-detail-view active" data-view="overview">
                        <div class="vk-detail-overview-grid">
                            <div class="vk-detail-panel vk-detail-panel-main">
                                <div class="vk-detail-panel-title">
                                    <i class="mdi mdi-transit-connection-variant text-primary"></i>
                                    Alur Perhitungan
                                </div>
                                <div class="vk-formula-flow vk-formula-flow-wide" id="detailVkFormulaFlow"></div>
                                <div class="vk-detail-total-line">
                                    <span>Total pemberian pegawai dipilih</span>
                                    <strong id="detailVkTotalSelected">Rp 0</strong>
                                </div>
                            </div>
                            <div class="vk-detail-side">
                                <div class="vk-detail-panel">
                                    <div class="vk-detail-panel-title">
                                        <i class="mdi mdi-chart-donut text-primary"></i>
                                        Komposisi
                                    </div>
                                    <div class="vk-detail-composition" id="detailVkComposition"></div>
                                </div>
                                <div class="vk-detail-panel">
                                    <div class="vk-detail-panel-title">
                                        <i class="mdi mdi-information-outline text-primary"></i>
                                        Informasi Generate
                                    </div>
                                    <div id="detailVkInfo"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vk-detail-view" data-view="pegawai">
                        <div class="vk-detail-recipient-layout">
                            <div class="vk-detail-panel">
                                <div class="vk-detail-panel-title">
                                    <i class="mdi mdi-account-multiple-check-outline text-primary"></i>
                                    Ringkasan Pegawai
                                </div>
                                <div class="vk-detail-recipient-metrics" id="detailVkRecipientMetrics"></div>
                            </div>
                            <div class="vk-detail-panel vk-detail-panel-main">
                                <div class="vk-recipient-toolbar">
                                    <div>
                                        <div class="vk-detail-panel-title mb-1">
                                            <i class="mdi mdi-account-group-outline text-primary"></i>
                                            Pegawai Penerima
                                        </div>
                                        <small class="text-muted" id="detailVkRecipientSummary">0 pegawai penerima.</small>
                                    </div>
                                    <div class="input-group vk-recipient-search">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="mdi mdi-magnify text-muted"></i>
                                        </span>
                                        <input type="search" class="form-control border-start-0" id="detailVkRecipientSearch"
                                            placeholder="Cari pegawai...">
                                    </div>
                                </div>
                                <div class="vk-recipient-list" id="detailVkRecipients"></div>
                            </div>
                        </div>
                    </div>

                    <div class="vk-detail-view" data-view="formula">
                        <div class="vk-detail-grid">
                            <div class="vk-detail-panel">
                                <div class="vk-detail-panel-title">
                                    <i class="mdi mdi-function-variant text-primary"></i>
                                    Rumus Tersimpan
                                </div>
                                <div class="vk-formula-flow" id="detailVkFormulaOnly"></div>
                            </div>
                            <div class="vk-detail-panel">
                                <div class="vk-detail-panel-title">
                                    <i class="mdi mdi-clipboard-text-outline text-primary"></i>
                                    Snapshot Konfigurasi
                                </div>
                                <div id="detailVkConfigSnapshot"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfigVk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content border-0 rounded-3" id="formConfigVk">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Konfigurasi VK BPJS</h5>
                    <small class="text-muted">
                        Persen diambil dari total VK awal, dibagi pembagi, lalu dibagikan sesuai mode.
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenisConfigVk" value="bpjs">
                <div class="alert alert-primary py-2">
                    Rumus: <strong>Total VK awal x Persen BPJS / Pembagi</strong>.
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Persen BPJS</label>
                        <div class="input-group">
                            <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                id="configVkBpjsPercent">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Contoh 4 berarti mengambil 4% dari total VK awal.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pembagi</label>
                        <input type="number" step="1" min="1" max="999999" class="form-control"
                            id="configVkBpjsPembagi">
                        <small class="text-muted">Hasil persen BPJS akan dibagi angka ini.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mode Pembagian</label>
                        <select class="form-select" id="configVkDistributionMode">
                            <option value="rata">Bagi rata ke semua pegawai</option>
                            <option value="per_pegawai">Setiap pegawai mendapat hasil perhitungan</option>
                        </select>
                        <small class="text-muted">
                            Bagi rata membagi hasil rumus ke penerima. Mode per pegawai memberi nominal hasil rumus
                            penuh kepada setiap pegawai.
                        </small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Pegawai Penerima</label>
                        <select class="form-select" id="configVkRecipients" multiple></select>
                        <small class="text-muted">
                            Saat generate BPJS, hasil akhir mengikuti mode pembagian di atas.
                        </small>
                        <div class="invalid-feedback d-block" id="configVkRecipientsError"></div>
                    </div>
                    <div class="col-12">
                        <div class="vk-bpjs-preview" id="configVkPreview"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="btnSubmitConfigVk" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-content-save-check-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>
