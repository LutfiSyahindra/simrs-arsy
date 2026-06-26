<div class="modal fade" id="modalGenerateApotek" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formGenerateApotek">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Preview Generate Apotek</h5>
                    <small class="text-muted">Cek sumber data, mapping obat, formula, dan penerima sebelum menyimpan.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewApotekLoading" class="text-center py-4">
                    <span class="spinner-border text-danger"></span>
                    <div class="text-muted mt-2">Menghitung preview Apotek...</div>
                </div>

                <div id="previewApotekContent" class="d-none">
                    <div class="apotek-simple-head">
                        <div class="apotek-simple-main">
                            <div class="apotek-simple-icon"><i class="mdi mdi-pill"></i></div>
                            <div>
                                <div class="apotek-simple-title" id="generateApotekTitle">Memuat preview...</div>
                                <div class="apotek-simple-text" id="generateApotekText">
                                    Cek data dan nominal sebelum generate.
                                </div>
                            </div>
                        </div>
                        <span class="apotek-simple-badge" id="generateApotekTypeBadge">Jenis</span>
                    </div>

                    <div class="apotek-preview-grid mb-3" id="previewApotekStats"></div>

                    <div class="apotek-simple-section mb-3">
                        <div class="apotek-simple-section-title">Rumus yang Dipakai</div>
                        <div class="apotek-info-list" id="previewApotekFormula"></div>
                    </div>

                    <div class="apotek-simple-section mb-3">
                        <div class="apotek-simple-section-title">Penerima Formula</div>
                        <div class="apotek-simple-section-subtitle" id="previewApotekRecipientSubtitle">-</div>
                        <div class="apotek-recipient-list mt-2" id="previewApotekRecipients"></div>
                        <div id="previewApotekQuality"></div>
                    </div>

                    <div class="apotek-simple-section mb-0">
                        <div class="apotek-simple-section-title">Contoh Detail Obat Masuk Mapping</div>
                        <div class="apotek-source-table table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>No Rawat</th>
                                        <th>Pasien</th>
                                        <th>Kode</th>
                                        <th>Nama Obat</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Premi</th>
                                    </tr>
                                </thead>
                                <tbody id="previewApotekDetailRows"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitGenerateApotek" disabled>
                    <i class="mdi mdi-content-save-check-outline me-1"></i>
                    Generate dan Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalConfigApotek" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formConfigApotek">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalConfigApotekLabel">Konfigurasi Premi Apotek</h5>
                    <small class="text-muted">Konfigurasi UMUM dan BPJS disimpan terpisah.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenisConfigApotek">

                <div class="apotek-simple-head">
                    <div class="apotek-simple-main">
                        <div class="apotek-simple-icon"><i class="mdi mdi-tune-variant"></i></div>
                        <div>
                            <div class="apotek-simple-title" id="configApotekRuleTitle">Aturan Data</div>
                            <div class="apotek-simple-text" id="configApotekRuleText">
                                Pilih jenis untuk melihat aturan sumber data.
                            </div>
                        </div>
                    </div>
                    <span class="apotek-simple-badge" id="configApotekTypeBadge">Jenis</span>
                </div>

                <div class="apotek-simple-section mb-3">
                    <div class="apotek-simple-section-title">Ringkasan Konfigurasi</div>
                    <div class="apotek-info-list" id="configApotekFormulaPreview"></div>
                </div>

                <div class="apotek-config-section mb-3 d-none" id="configApotekUmumBpjsSection">
                    <h6><i class="mdi mdi-shield-plus-outline"></i> Cakupan Data UMUM</h6>
                    <label class="apotek-source-mode-option mb-0">
                        <input class="form-check-input" type="checkbox" id="configIncludeBpjsInUmum" value="1">
                        <span>
                            <strong>Hitung BPJS sekalian di UMUM</strong>
                            <small>Jika aktif, generate UMUM ikut mengambil reg_periksa.kd_pj = BPJ dan tab BPJS disembunyikan.</small>
                        </span>
                    </label>
                    <small class="apotek-field-help">
                        Saat aktif, UMUM membaca semua penjamin selain kd_pj "-" termasuk BPJS Kesehatan.
                    </small>
                </div>

                <div class="apotek-config-section mb-3 d-none" id="configApotekBpjsSourceSection">
                    <h6><i class="mdi mdi-calendar-sync-outline"></i> Sumber Data BPJS</h6>
                    <div class="apotek-source-mode-grid">
                        <label class="apotek-source-mode-option">
                            <input class="form-check-input" type="radio" name="configApotekSourcePeriodMode"
                                value="previous" checked>
                            <span>
                                <strong>Bulan Sebelumnya</strong>
                                <small>Generate periode aktif membaca data Khanza satu bulan sebelumnya.</small>
                            </span>
                        </label>
                        <label class="apotek-source-mode-option">
                            <input class="form-check-input" type="radio" name="configApotekSourcePeriodMode"
                                value="current">
                            <span>
                                <strong>Periode Generate</strong>
                                <small>Generate periode aktif membaca data Khanza pada bulan yang sama.</small>
                            </span>
                        </label>
                    </div>
                    <small class="apotek-field-help">
                        Pilihan ini hanya berlaku untuk BPJS. UMUM tetap memakai periode generate yang aktif.
                    </small>
                </div>

                <div class="apotek-config-section mb-3">
                    <h6><i class="mdi mdi-source-branch"></i> Mapping Obat</h6>
                    <label class="form-label">Mapping Farmasi</label>
                    <select class="form-select" id="configApotekMappings" multiple></select>
                    <small class="apotek-field-help">
                        Pilih master jenis tindakan yang berisi mapping_tindakan sumber FARMASI. Kode barang dari
                        detail_pemberian_obat hanya dihitung jika ada di mapping ini.
                    </small>
                    <div class="apotek-recipient-list mt-3" id="configApotekMappingPreview"></div>
                </div>

                <div class="apotek-config-grid mb-3">
                    <div class="apotek-config-section">
                        <h6><i class="mdi mdi-calculator-variant-outline"></i> Basis dan Persentase</h6>
                        <div class="mb-3">
                            <label class="form-label">Nominal per Data Obat</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" class="form-control" id="configTarifPerItemApotek">
                            </div>
                            <small class="apotek-field-help">Default 500 per baris obat yang cocok mapping.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pool Formula dari Grand Total</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configJasaFarmasiPercent" readonly>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="apotek-field-help">
                                Nilai ini dikunci: grand total diambil 50%, lalu 31%, 7%, dan 12% dihitung dari pool 50% tersebut.
                            </small>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Premi Bersama</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configPremiBersamaApotekPercent">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="apotek-field-help">Default 30% dari grand total.</small>
                        </div>
                    </div>

                    <div class="apotek-config-section">
                        <h6><i class="mdi mdi-function-variant"></i> Formula Penerima</h6>
                        <div class="row g-2">
                            <div class="col-7">
                                <label class="form-label">Persen 31</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configFormula31Percent">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Pembagi</label>
                                <input type="number" step="0.01" min="0.01" class="form-control"
                                    id="configFormula31Divider">
                            </div>
                            <div class="col-7">
                                <label class="form-label">Persen 7</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configFormula7Percent">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Pembagi</label>
                                <input type="number" step="0.01" min="0.01" class="form-control"
                                    id="configFormula7Divider">
                            </div>
                            <div class="col-7">
                                <label class="form-label">Persen 12</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configFormula12Percent">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Pembagi</label>
                                <input type="number" step="0.01" min="0.01" class="form-control"
                                    id="configFormula12Divider">
                            </div>
                        </div>
                        <small class="apotek-field-help">
                            Rumus default: grand total x 50% menjadi pool, lalu pool x 31% / 2.5,
                            pool x 7%, dan pool x 12% / 2.
                        </small>
                    </div>
                </div>

                <div class="apotek-config-grid">
                    <div class="apotek-config-section">
                        <h6><i class="mdi mdi-account-star-outline"></i> Penerima 31% / 2.5</h6>
                        <select class="form-select apotek-recipient-select" id="configApotekRecipients31" multiple></select>
                        <small class="apotek-field-help" id="countApotekRecipients31">0 penerima dipilih</small>
                    </div>
                    <div class="apotek-config-section">
                        <h6><i class="mdi mdi-account-tie-outline"></i> Penerima 7%</h6>
                        <select class="form-select apotek-recipient-select" id="configApotekRecipients7" multiple></select>
                        <small class="apotek-field-help" id="countApotekRecipients7">0 penerima dipilih</small>
                    </div>
                    <div class="apotek-config-section">
                        <h6><i class="mdi mdi-account-group-outline"></i> Penerima 12% / 2</h6>
                        <select class="form-select apotek-recipient-select" id="configApotekRecipients12" multiple></select>
                        <small class="apotek-field-help" id="countApotekRecipients12">0 penerima dipilih</small>
                    </div>
                    <div class="apotek-config-section">
                        <h6><i class="mdi mdi-clipboard-check-outline"></i> Preview Penerima</h6>
                        <div class="apotek-recipient-list" id="configApotekRecipientPreview"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitConfigApotek">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailApotek" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Detail Premi Apotek</h5>
                    <small class="text-muted">Audit sumber obat, rumus tersimpan, dan pembagian pegawai.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailApotekLoading" class="text-center py-5">
                    <span class="spinner-border text-danger"></span>
                    <div class="text-muted mt-2">Memuat detail Apotek...</div>
                </div>

                <div id="detailApotekContent" class="d-none">
                    <div class="apotek-simple-head">
                        <div class="apotek-simple-main">
                            <div class="apotek-simple-icon"><i class="mdi mdi-clipboard-text-search-outline"></i></div>
                            <div>
                                <div class="apotek-simple-title" id="detailApotekTitle">-</div>
                                <div class="apotek-simple-text" id="detailApotekMeta">-</div>
                                <div class="apotek-simple-text" id="detailApotekSourceMeta">-</div>
                            </div>
                        </div>
                        <span class="apotek-simple-badge" id="detailApotekStatusBadge">Status</span>
                    </div>

                    <div class="apotek-detail-grid mb-3" id="detailApotekStats"></div>

                    <div class="apotek-simple-section mb-3">
                        <div class="apotek-simple-section-title">Rumus Tersimpan</div>
                        <div class="apotek-info-list" id="detailApotekFormula"></div>
                    </div>

                    <div class="apotek-simple-section mb-3">
                        <div class="apotek-simple-section-title">Penerima Formula</div>
                        <div class="apotek-simple-section-subtitle" id="detailApotekRecipientSubtitle">-</div>
                        <div class="apotek-recipient-list mt-2" id="detailApotekRecipients"></div>
                    </div>

                    <div class="apotek-simple-section mb-3">
                        <div class="apotek-simple-section-title">Informasi per Penjamin</div>
                        <div class="apotek-simple-section-subtitle" id="detailApotekPenjaminSubtitle">-</div>
                        <div class="apotek-info-list mt-2" id="detailApotekPenjaminBreakdown"></div>
                    </div>

                    <div class="apotek-simple-section mb-0">
                        <div class="apotek-simple-section-title">Detail Obat Masuk Mapping</div>
                        <div class="apotek-detail-filter-panel">
                            <div class="apotek-detail-filter-head">
                                <div>
                                    <div class="apotek-detail-filter-title">
                                        <i class="mdi mdi-pill-multiple"></i>
                                        Filter Obat
                                    </div>
                                    <div class="apotek-simple-section-subtitle" id="detailApotekFilterSubtitle">-</div>
                                </div>
                                <button type="button" class="btn btn-light btn-sm" id="btnClearFilterDetailApotek">
                                    <i class="mdi mdi-filter-remove-outline me-1"></i> Reset
                                </button>
                            </div>
                            <div class="apotek-detail-filter-input">
                                <i class="mdi mdi-magnify"></i>
                                <input type="text" class="form-control" id="filterDetailApotekObat"
                                    placeholder="Nama atau kode obat">
                            </div>
                            <div class="apotek-detail-filter-stats">
                                <div class="apotek-detail-filter-card">
                                    <span>Jumlah Nama</span>
                                    <strong id="detailFilterJumlahNamaObat">0</strong>
                                </div>
                                <div class="apotek-detail-filter-card">
                                    <span>Baris Detail</span>
                                    <strong id="detailFilterJumlahBarisObat">0</strong>
                                </div>
                                <div class="apotek-detail-filter-card">
                                    <span>Total Qty</span>
                                    <strong id="detailFilterTotalQtyObat">0</strong>
                                </div>
                                <div class="apotek-detail-filter-card accent">
                                    <span>Total Premi</span>
                                    <strong id="detailFilterTotalPremiObat">Rp 0</strong>
                                </div>
                            </div>
                            <div class="apotek-detail-breakdown" id="detailApotekObatBreakdown"></div>
                        </div>
                        <div class="apotek-source-table table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>No Rawat</th>
                                        <th>Pasien</th>
                                        <th>Kode</th>
                                        <th>Nama Obat</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Premi</th>
                                    </tr>
                                </thead>
                                <tbody id="detailApotekDetailRows"></tbody>
                            </table>
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
