<div class="modal fade" id="modalConfigPremiDokter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable pd-config-modal-dialog">
        <form class="modal-content pd-config-modal" id="formConfigPremiDokter">
            <div class="modal-header">
                <div>
                    <div class="pd-config-kicker">Pusat Kendali</div>
                    <h5 class="modal-title">Konfigurasi Premi Dokter</h5>
                    <div class="pd-config-header-meta">Atur formula, mapping tindakan, dan dokter penerima dalam satu panel.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="pd-config-toolbar">
                    <div class="pd-config-tabs" role="tablist" aria-label="Tab konfigurasi premi dokter">
                        <button type="button" class="pd-config-tab active" data-config-pane-target="formula">
                            <i class="mdi mdi-calculator-variant-outline"></i>
                            <span>Formula</span>
                        </button>
                        <button type="button" class="pd-config-tab" data-config-pane-target="visite">
                            <i class="mdi mdi-stethoscope"></i>
                            <span>Jasa Visite</span>
                        </button>
                        <button type="button" class="pd-config-tab" data-config-pane-target="rawat_jalan">
                            <i class="mdi mdi-hospital-box-outline"></i>
                            <span>Rawat Jalan</span>
                        </button>
                        <button type="button" class="pd-config-tab" data-config-pane-target="poli">
                            <i class="mdi mdi-hospital-building"></i>
                            <span>Poli</span>
                        </button>
                        <button type="button" class="pd-config-tab" data-config-pane-target="ecg">
                            <i class="mdi mdi-heart-pulse"></i>
                            <span>ECG</span>
                        </button>
                        <button type="button" class="pd-config-tab" data-config-pane-target="konsul_wa">
                            <i class="mdi mdi-whatsapp"></i>
                            <span>Konsul WA</span>
                        </button>
                        <button type="button" class="pd-config-tab" data-config-pane-target="penerima_lain">
                            <i class="mdi mdi-account-multiple-check-outline"></i>
                            <span>Penerima Lain</span>
                        </button>
                    </div>
                </div>

                <div class="pd-config-summary-grid">
                    <div class="pd-config-summary-card">
                        <div class="pd-config-summary-label">Formula Visite</div>
                        <div class="pd-config-summary-value" id="configSummaryFormula">-</div>
                    </div>
                    <div class="pd-config-summary-card">
                        <div class="pd-config-summary-label">Jasa Visite</div>
                        <div class="pd-config-summary-value" id="configSummaryVisite">0 mapping / 0 dokter</div>
                    </div>
                    <div class="pd-config-summary-card">
                        <div class="pd-config-summary-label">Rawat Jalan</div>
                        <div class="pd-config-summary-value" id="configSummaryRawatJalan">0 mapping / 0 dokter</div>
                    </div>
                    <div class="pd-config-summary-card">
                        <div class="pd-config-summary-label">Poli</div>
                        <div class="pd-config-summary-value" id="configSummaryPoli">0 mapping / 0 sumber / 0 filter / 0 penerima</div>
                    </div>
                    <div class="pd-config-summary-card">
                        <div class="pd-config-summary-label">ECG</div>
                        <div class="pd-config-summary-value" id="configSummaryEcg">0 mapping / 0 dokter</div>
                    </div>
                    <div class="pd-config-summary-card">
                        <div class="pd-config-summary-label">Konsul WA</div>
                        <div class="pd-config-summary-value" id="configSummaryKonsulWa">0 mapping / 0 dokter</div>
                    </div>
                    <div class="pd-config-summary-card">
                        <div class="pd-config-summary-label">Total Penerima</div>
                        <div class="pd-config-summary-value" id="configSummaryPenerima">0 dokter</div>
                    </div>
                </div>

                <div class="pd-config-scope-grid">
                    <div class="pd-config-scope-card">
                        <div class="pd-scope-icon"><i class="mdi mdi-account-cash-outline"></i></div>
                        <div>
                            <div class="pd-config-summary-label">Data UMUM</div>
                            <div class="pd-config-scope-value">Periode generate aktif</div>
                        </div>
                    </div>
                    <div class="pd-config-scope-card pd-config-scope-card-control">
                        <div class="pd-scope-icon"><i class="mdi mdi-calendar-sync-outline"></i></div>
                        <div>
                            <label class="pd-config-summary-label" for="configSourcePeriodMode">Sumber Data BPJS</label>
                            <select class="form-select" id="configSourcePeriodMode">
                                <option value="current">Periode Berjalan</option>
                                <option value="previous">Bulan Sebelumnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="pd-config-scope-card">
                        <div class="pd-scope-icon"><i class="mdi mdi-shield-plus-outline"></i></div>
                        <div>
                            <div class="pd-config-summary-label">Mode BPJS Aktif</div>
                            <div class="pd-config-scope-value" id="configScopeBpjsModeText">Periode Berjalan</div>
                        </div>
                    </div>
                </div>

                <div class="pd-config-body">
                    <div class="pd-config-pane active" data-config-pane="formula">
                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-format-list-checks"></i>
                                        Formula Jasa Visite
                                    </div>
                                    <div class="pd-config-subtitle">Dasar perhitungan visite UMUM dan BPJS.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeFormulaVisite">0% UMUM / 0% BPJS</span>
                            </div>
                            <div class="pd-config-field-grid">
                                <div class="pd-config-field">
                                    <label for="configVisiteUmumPercent">Persen Default UMUM</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-end" id="configVisiteUmumPercent" min="0" max="100" step="0.0001" value="50">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="pd-config-field">
                                    <label for="configVisiteBpjsNominal">Nominal BPJS per Data</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control text-end" id="configVisiteBpjsNominal" min="0" step="1" value="0">
                                    </div>
                                </div>
                                <div class="pd-config-field">
                                    <label for="configVisiteBpjsPercent">Persen BPJS</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-end" id="configVisiteBpjsPercent" min="0" max="100" step="0.0001" value="50">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="pd-config-section mb-0">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-account-group-outline"></i>
                                        Formula Kebersamaan
                                    </div>
                                    <div class="pd-config-subtitle">Persentase dan pembagi untuk premi kebersamaan.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeKebersamaan">0 pembagi</span>
                            </div>
                            <div class="pd-config-field-grid">
                                <div class="pd-config-field">
                                    <label for="configKebersamaanUmumPercent">Persen Visite UMUM</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-end" id="configKebersamaanUmumPercent" min="0" max="100" step="0.0001" value="30">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="pd-config-field">
                                    <label for="configKebersamaanBpjsNominal">Nominal BPJS per Tindakan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control text-end" id="configKebersamaanBpjsNominal" min="0" step="1" value="40000">
                                    </div>
                                </div>
                                <div class="pd-config-field">
                                    <label for="configKebersamaanBpjsPercent">Persen Visite BPJS</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-end" id="configKebersamaanBpjsPercent" min="0" max="100" step="0.0001" value="30">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="pd-config-field">
                                    <label for="configKebersamaanDivider">Pembagi Grand Total</label>
                                    <input type="number" class="form-control text-end" id="configKebersamaanDivider" min="1" step="1" value="4">
                                </div>
                                <div class="pd-config-field">
                                    <label for="configKebersamaanOnlyUmum">Sumber Dokter</label>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="configKebersamaanOnlyUmum">
                                        <label class="form-check-label" for="configKebersamaanOnlyUmum">
                                            Hanya hitung dokter umum
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="pd-config-pane" data-config-pane="visite">
                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-clipboard-pulse-outline"></i>
                                        Mapping Tindakan Visite
                                    </div>
                                    <div class="pd-config-subtitle">Data rawat dicocokkan ke rincian mapping tindakan yang dipilih.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeMappingVisite">0 mapping</span>
                            </div>
                            <select id="configMappingTindakan" class="form-select" multiple></select>
                        </section>

                        <div class="pd-config-two-column">
                            <section class="pd-config-section">
                                <div class="pd-config-section-head">
                                    <div>
                                        <div class="pd-config-title">
                                            <i class="mdi mdi-account-tie-outline"></i>
                                            Dokter Umum
                                        </div>
                                        <div class="pd-config-subtitle">Persen bisa diatur per dokter.</div>
                                    </div>
                                    <span class="pd-config-badge" id="configBadgeDokterUmum">0 dokter</span>
                                </div>
                                <select id="doctorSelectUmum" class="form-select pd-doctor-select" data-category="umum" multiple></select>
                                <div id="doctorRowsUmum" class="mt-3"></div>
                            </section>

                            <section class="pd-config-section">
                                <div class="pd-config-section-head">
                                    <div>
                                        <div class="pd-config-title">
                                            <i class="mdi mdi-account-heart-outline"></i>
                                            Dokter Spesialis 65%
                                        </div>
                                        <div class="pd-config-subtitle">Kategori spesialis dengan default 65%.</div>
                                    </div>
                                    <span class="pd-config-badge" id="configBadgeDokterSpesialis65">0 dokter</span>
                                </div>
                                <select id="doctorSelectSpesialis65" class="form-select pd-doctor-select" data-category="spesialis_65" multiple></select>
                                <div id="doctorRowsSpesialis65" class="mt-3"></div>
                            </section>
                        </div>

                        <section class="pd-config-section mb-0">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-account-star-outline"></i>
                                        Dokter Spesialis 80%
                                    </div>
                                    <div class="pd-config-subtitle">Kategori spesialis dengan default 80%.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeDokterSpesialis80">0 dokter</span>
                            </div>
                            <select id="doctorSelectSpesialis80" class="form-select pd-doctor-select" data-category="spesialis_80" multiple></select>
                            <div id="doctorRowsSpesialis80" class="mt-3"></div>
                        </section>
                    </div>

                    <div class="pd-config-pane" data-config-pane="rawat_jalan">
                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-source-branch"></i>
                                        Jenis Jasa Rawat Jalan
                                    </div>
                                    <div class="pd-config-subtitle">UMUM dan BPJS diproses terpisah saat generate.</div>
                                </div>
                            </div>
                            <div class="pd-config-rj-split">
                                <div class="pd-config-rj-split-card">
                                    <span>UMUM</span>
                                    <strong>Periode generate</strong>
                                </div>
                                <div class="pd-config-rj-split-card">
                                    <span>BPJS</span>
                                    <strong id="rawatJalanBpjsModePreview">Periode Berjalan</strong>
                                </div>
                            </div>
                        </section>

                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-clipboard-text-search-outline"></i>
                                        Mapping Tindakan Rawat Jalan
                                    </div>
                                    <div class="pd-config-subtitle">Setiap mapping dapat memakai pengkali nominal atau persen.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeRawatJalanMapping">0 mapping</span>
                            </div>
                            <select id="configRawatJalanMappingTindakan" class="form-select" multiple></select>
                            <div id="rawatJalanMappingRows" class="mt-3">
                                <div class="pd-empty-state">
                                    <i class="mdi mdi-clipboard-search-outline"></i>
                                    <span>Belum ada mapping Rawat Jalan dipilih.</span>
                                </div>
                            </div>
                        </section>

                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-account-cash-outline"></i>
                                        Dokter Rawat Jalan Reguler
                                    </div>
                                    <div class="pd-config-subtitle">Premi mengikuti total mapping dikali pengkali yang dipilih.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeDokterRawatJalan">0 dokter</span>
                            </div>
                            <select id="doctorSelectRawatJalan" class="form-select pd-doctor-select" data-category="jasa_rawat_jalan" multiple></select>
                            <div id="doctorRowsRawatJalan" class="mt-3"></div>
                        </section>

                        <section class="pd-config-section mb-0">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-account-star-outline"></i>
                                        Dokter Khusus Rawat Jalan
                                    </div>
                                    <div class="pd-config-subtitle">Dokter khusus dihitung dari jumlah data mapping dikali nominal khusus.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeDokterRawatJalanKhusus">0 dokter</span>
                            </div>
                            <div class="pd-config-two-column">
                                <div>
                                    <div class="pd-config-field mb-3">
                                        <label for="configRawatJalanKhusus45000Nominal">Nominal Kelompok 45.000</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control text-end" id="configRawatJalanKhusus45000Nominal" min="0" step="1" value="45000">
                                        </div>
                                    </div>
                                    <select id="doctorSelectRawatJalanKhusus45000" class="form-select pd-rj-special-doctor-select" data-group-key="rawat_jalan_khusus_45000" multiple></select>
                                    <div id="doctorRowsRawatJalanKhusus45000" class="mt-3"></div>
                                </div>
                                <div>
                                    <div class="pd-config-field mb-3">
                                        <label for="configRawatJalanKhusus72000Nominal">Nominal Kelompok 72.000</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control text-end" id="configRawatJalanKhusus72000Nominal" min="0" step="1" value="72000">
                                        </div>
                                    </div>
                                    <select id="doctorSelectRawatJalanKhusus72000" class="form-select pd-rj-special-doctor-select" data-group-key="rawat_jalan_khusus_72000" multiple></select>
                                    <div id="doctorRowsRawatJalanKhusus72000" class="mt-3"></div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="pd-config-pane" data-config-pane="poli">
                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-calculator-variant-outline"></i>
                                        Formula Jasa Poli
                                    </div>
                                    <div class="pd-config-subtitle">Grand total = total biaya rawat dari filter sumber dikali persentase.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgePoliFormula">30% - Dibagi rata</span>
                            </div>
                            <div class="pd-config-field-grid">
                                <div class="pd-config-field">
                                    <label for="configPoliPercent">Persen Grand Total</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-end" id="configPoliPercent" min="0" max="100" step="0.0001" value="30">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="pd-config-field">
                                    <label for="configPoliDistributionMode">Mode Pembagian</label>
                                    <select class="form-select" id="configPoliDistributionMode">
                                        <option value="split_evenly">Dibagi rata</option>
                                        <option value="full_amount">Diberikan penuh</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-source-branch"></i>
                                        Jenis Jasa Poli
                                    </div>
                                    <div class="pd-config-subtitle">UMUM memakai periode generate, BPJS mengikuti mode sumber data BPJS.</div>
                                </div>
                            </div>
                            <div class="pd-config-rj-split">
                                <div class="pd-config-rj-split-card">
                                    <span>UMUM</span>
                                    <strong>Periode generate</strong>
                                </div>
                                <div class="pd-config-rj-split-card">
                                    <span>BPJS</span>
                                    <strong id="poliBpjsModePreview">Periode Berjalan</strong>
                                </div>
                            </div>
                        </section>

                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-clipboard-text-search-outline"></i>
                                        Mapping Tindakan Poli
                                    </div>
                                    <div class="pd-config-subtitle">Bisa memilih lebih dari satu master mapping tindakan.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgePoliMapping">0 mapping</span>
                            </div>
                            <select id="configPoliMappingTindakan" class="form-select" multiple></select>
                        </section>

                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-account-filter-outline"></i>
                                        Filter Data Poli
                                    </div>
                                    <div class="pd-config-subtitle">Pilih dokter sumber dan tindakan dari Mapping Tindakan Poli yang datanya akan diambil.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgePoliFilter">0 sumber / 0 dokter / 0 tindakan</span>
                            </div>
                            <div class="pd-config-three-column">
                                <div>
                                    <label class="pd-config-mini-label" for="configPoliFilterSources">Sumber Data Rawat</label>
                                    <select id="configPoliFilterSources" class="form-select" multiple></select>
                                </div>
                                <div>
                                    <label class="pd-config-mini-label" for="configPoliFilterDoctors">Dokter Sumber Data</label>
                                    <select id="configPoliFilterDoctors" class="form-select" multiple></select>
                                </div>
                                <div>
                                    <label class="pd-config-mini-label" for="configPoliFilterTindakan">Tindakan yang Difilter Dokter</label>
                                    <select id="configPoliFilterTindakan" class="form-select" multiple></select>
                                </div>
                            </div>
                            <div class="pd-config-help-text mt-2">
                                Jika sumber, dokter, atau tindakan filter kosong, Jasa Poli belum bisa digenerate.
                            </div>
                        </section>

                        <section class="pd-config-section mb-0">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-account-group-outline"></i>
                                        Dokter Penerima Poli
                                    </div>
                                    <div class="pd-config-subtitle">Grand total dari filter data Poli dialokasikan ke dokter penerima ini.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeDokterPoli">0 dokter</span>
                            </div>
                            <select id="doctorSelectPoli" class="form-select pd-doctor-select" data-category="jasa_poli" multiple></select>
                            <div id="doctorRowsPoli" class="mt-3"></div>
                        </section>
                    </div>

                    <div class="pd-config-pane" data-config-pane="ecg">
                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-calculator-variant-outline"></i>
                                        Formula Jasa ECG
                                    </div>
                                    <div class="pd-config-subtitle">Grand total = seluruh data ECG yang sesuai mapping dikali nominal lalu dibagi pembagi.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeEcgFormula">Rp 5.000 / 3</span>
                            </div>
                            <div class="pd-config-field-grid">
                                <div class="pd-config-field">
                                    <label for="configEcgNominal">Nominal per Data</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control text-end" id="configEcgNominal" min="0" step="1" value="5000">
                                    </div>
                                </div>
                                <div class="pd-config-field">
                                    <label for="configEcgDivider">Pembagi Grand Total</label>
                                    <input type="number" class="form-control text-end" id="configEcgDivider" min="1" step="1" value="3">
                                </div>
                                <div class="pd-config-field">
                                    <label for="configEcgDistributionMode">Mode Pembagian</label>
                                    <select class="form-select" id="configEcgDistributionMode">
                                        <option value="split_evenly">Dibagi rata</option>
                                        <option value="full_amount">Diberikan penuh</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-source-branch"></i>
                                        Jenis Jasa ECG
                                    </div>
                                    <div class="pd-config-subtitle">UMUM dan BPJS diproses terpisah saat generate.</div>
                                </div>
                            </div>
                            <div class="pd-config-rj-split">
                                <div class="pd-config-rj-split-card">
                                    <span>UMUM</span>
                                    <strong>Periode generate</strong>
                                </div>
                                <div class="pd-config-rj-split-card">
                                    <span>BPJS</span>
                                    <strong id="ecgBpjsModePreview">Periode Berjalan</strong>
                                </div>
                            </div>
                        </section>

                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-clipboard-text-search-outline"></i>
                                        Mapping Tindakan ECG
                                    </div>
                                    <div class="pd-config-subtitle">Bisa memilih lebih dari satu master mapping tindakan.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeEcgMapping">0 mapping</span>
                            </div>
                            <select id="configEcgMappingTindakan" class="form-select" multiple></select>
                        </section>

                        <section class="pd-config-section mb-0">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-account-heart-outline"></i>
                                        Dokter Penerima ECG
                                    </div>
                                    <div class="pd-config-subtitle">Dokter dipakai sebagai penerima hasil ECG, bukan filter data sumber.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeDokterEcg">0 dokter</span>
                            </div>
                            <select id="doctorSelectEcg" class="form-select pd-doctor-select" data-category="jasa_ecg" multiple></select>
                            <div id="doctorRowsEcg" class="mt-3"></div>
                        </section>
                    </div>

                    <div class="pd-config-pane" data-config-pane="konsul_wa">
                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-calculator-variant-outline"></i>
                                        Formula Konsul WA
                                    </div>
                                    <div class="pd-config-subtitle">Premi tiap dokter = jumlah data dokter tersebut dikali nominal per data.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeKonsulWaFormula">Rp 0 / data</span>
                            </div>
                            <div class="pd-config-field-grid">
                                <div class="pd-config-field">
                                    <label for="configKonsulWaNominal">Nominal per Data</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control text-end" id="configKonsulWaNominal" min="0" step="1" value="0">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-source-branch"></i>
                                        Jenis Konsul WA
                                    </div>
                                    <div class="pd-config-subtitle">UMUM memakai periode generate, BPJS mengikuti mode sumber data BPJS.</div>
                                </div>
                            </div>
                            <div class="pd-config-rj-split">
                                <div class="pd-config-rj-split-card">
                                    <span>UMUM</span>
                                    <strong>Periode generate</strong>
                                </div>
                                <div class="pd-config-rj-split-card">
                                    <span>BPJS</span>
                                    <strong id="konsulWaBpjsModePreview">Periode Berjalan</strong>
                                </div>
                            </div>
                        </section>

                        <section class="pd-config-section">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-clipboard-text-search-outline"></i>
                                        Mapping Tindakan Konsul WA
                                    </div>
                                    <div class="pd-config-subtitle">Bisa memilih lebih dari satu master mapping tindakan.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeKonsulWaMapping">0 mapping</span>
                            </div>
                            <select id="configKonsulWaMappingTindakan" class="form-select" multiple></select>
                        </section>

                        <section class="pd-config-section mb-0">
                            <div class="pd-config-section-head">
                                <div>
                                    <div class="pd-config-title">
                                        <i class="mdi mdi-account-heart-outline"></i>
                                        Dokter Penerima Konsul WA
                                    </div>
                                    <div class="pd-config-subtitle">Premi dihitung per dokter berdasarkan jumlah data masing-masing.</div>
                                </div>
                                <span class="pd-config-badge" id="configBadgeDokterKonsulWa">0 dokter</span>
                            </div>
                            <select id="doctorSelectKonsulWa" class="form-select pd-doctor-select" data-category="konsul_wa" multiple></select>
                            <div id="doctorRowsKonsulWa" class="mt-3"></div>
                        </section>
                    </div>

                    <div class="pd-config-pane" data-config-pane="penerima_lain">
                        <div class="pd-config-two-column">
                            <section class="pd-config-section">
                                <div class="pd-config-section-head">
                                    <div>
                                        <div class="pd-config-title">
                                            <i class="mdi mdi-account-group-outline"></i>
                                            Dokter Kebersamaan
                                        </div>
                                        <div class="pd-config-subtitle">Premi dibagi dari formula kebersamaan.</div>
                                    </div>
                                    <span class="pd-config-badge" id="configBadgeDokterKebersamaan">0 dokter</span>
                                </div>
                                <select id="doctorSelectKebersamaan" class="form-select pd-doctor-select" data-category="kebersamaan" multiple></select>
                                <div id="doctorRowsKebersamaan" class="mt-3"></div>
                            </section>

                            <section class="pd-config-section">
                                <div class="pd-config-section-head">
                                    <div>
                                        <div class="pd-config-title">
                                            <i class="mdi mdi-hospital-marker"></i>
                                            Dokter Jasa Operasi
                                        </div>
                                        <div class="pd-config-subtitle">Premi operasi = nominal input dikali persen dokter.</div>
                                    </div>
                                    <span class="pd-config-badge" id="configBadgeDokterOperasi">0 dokter</span>
                                </div>
                                <select id="doctorSelectOperasi" class="form-select pd-doctor-select" data-category="jasa_operasi" multiple></select>
                                <div id="doctorRowsOperasi" class="mt-3"></div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary" id="btnSaveConfigPremiDokter">
                    <i class="mdi mdi-content-save-outline"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailPremiDokter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable pd-detail-modal-dialog">
        <div class="modal-content pd-detail-modal">
            <div class="modal-header">
                <div>
                    <div class="pd-detail-kicker">Audit Generate Premi Dokter</div>
                    <h5 class="modal-title">Detail Premi Dokter</h5>
                    <div class="pd-detail-modal-meta" id="detailPremiDokterMeta">Memuat detail...</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="pd-detail-summary-grid">
                    <div class="pd-detail-metric">
                        <div class="pd-metric-label">Total Premi</div>
                        <div class="pd-metric-value" id="detailTotalPremi">Rp 0</div>
                    </div>
                    <div class="pd-detail-metric">
                        <div class="pd-metric-label">Grand Total</div>
                        <div class="pd-metric-value" id="detailGrandTotal">Rp 0</div>
                    </div>
                    <div class="pd-detail-metric">
                        <div class="pd-metric-label">Dokter</div>
                        <div class="pd-metric-value" id="detailJumlahDokter">0</div>
                    </div>
                    <div class="pd-detail-metric">
                        <div class="pd-metric-label">Transaksi</div>
                        <div class="pd-metric-value" id="detailJumlahTransaksi">0</div>
                    </div>
                    <div class="pd-detail-metric">
                        <div class="pd-metric-label">Mapping</div>
                        <div class="pd-metric-value" id="detailJumlahMapping">0</div>
                    </div>
                    <div class="pd-detail-metric">
                        <div class="pd-metric-label">Status</div>
                        <div class="pd-metric-value" id="detailStatus">-</div>
                    </div>
                </div>
                <div class="pd-detail-layout">
                    <div>
                        <div class="pd-detail-side-head">
                            <div class="pd-detail-side-title">Dokter Penerima</div>
                            <div class="pd-detail-side-count" id="detailDoctorCountPill">0 dokter</div>
                        </div>
                        <div class="pd-detail-grid" id="detailPremiDokterRows"></div>
                    </div>
                    <div class="pd-rawat-panel">
                        <div class="pd-rawat-head">
                            <div>
                                <div class="pd-rawat-title" id="detailRawatTitle">Data Rawat Tersimpan</div>
                                <div class="pd-rawat-note" id="detailRawatNote">Pilih dokter untuk melihat data rawat.</div>
                            </div>
                        </div>
                        <div class="pd-rawat-filter-panel">
                            <div>
                                <label class="pd-rawat-filter-label" for="detailRawatSearch">Cari data</label>
                                <input type="search" class="form-control" id="detailRawatSearch"
                                    placeholder="No rawat, pasien, tindakan, dokter...">
                            </div>
                            <div>
                                <label class="pd-rawat-filter-label" for="detailFilterSource">Sumber</label>
                                <select class="form-select" id="detailFilterSource">
                                    <option value="">Semua sumber</option>
                                </select>
                            </div>
                            <div>
                                <label class="pd-rawat-filter-label" for="detailFilterTindakan">Tindakan</label>
                                <select class="form-select" id="detailFilterTindakan">
                                    <option value="">Semua tindakan</option>
                                </select>
                            </div>
                            <div>
                                <label class="pd-rawat-filter-label" for="detailFilterPenjamin">Penjamin</label>
                                <select class="form-select" id="detailFilterPenjamin">
                                    <option value="">Semua penjamin</option>
                                </select>
                            </div>
                            <div>
                                <label class="pd-rawat-filter-label" for="detailFilterDateStart">Dari</label>
                                <input type="date" class="form-control" id="detailFilterDateStart">
                            </div>
                            <div>
                                <label class="pd-rawat-filter-label" for="detailFilterDateEnd">Sampai</label>
                                <input type="date" class="form-control" id="detailFilterDateEnd">
                            </div>
                            <button type="button" class="btn btn-outline-secondary pd-rawat-filter-reset"
                                id="btnResetRawatFilter" title="Reset filter">
                                <i class="mdi mdi-filter-remove-outline"></i>
                            </button>
                        </div>
                        <div class="pd-rawat-insights">
                            <div class="pd-rawat-insight">
                                <div class="pd-rawat-insight-label">Hasil filter</div>
                                <div class="pd-rawat-insight-value" id="detailFilteredCount">0 data</div>
                            </div>
                            <div class="pd-rawat-insight">
                                <div class="pd-rawat-insight-label">Total biaya</div>
                                <div class="pd-rawat-insight-value" id="detailFilteredTotal">Rp 0</div>
                            </div>
                            <div class="pd-rawat-insight">
                                <div class="pd-rawat-insight-label">Sumber dominan</div>
                                <div class="pd-rawat-insight-value" id="detailTopSource">-</div>
                            </div>
                            <div class="pd-rawat-insight">
                                <div class="pd-rawat-insight-label">Tindakan dominan</div>
                                <div class="pd-rawat-insight-value" id="detailTopAction">-</div>
                            </div>
                        </div>
                        <div class="pd-filter-pill-row" id="detailActiveFilters"></div>
                        <div class="table-responsive pd-rawat-table-wrap">
                            <table class="table table-hover align-middle pd-rawat-table">
                                <thead>
                                    <tr>
                                        <th>Sumber</th>
                                        <th>Tanggal</th>
                                        <th>Pasien</th>
                                        <th>Tindakan</th>
                                        <th>Penjamin</th>
                                        <th>Petugas</th>
                                        <th class="text-end">Biaya</th>
                                    </tr>
                                </thead>
                                <tbody id="detailRawatRows">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Pilih dokter untuk melihat data rawat.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
