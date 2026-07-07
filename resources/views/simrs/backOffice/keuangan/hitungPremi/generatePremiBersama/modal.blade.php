<div class="modal fade" id="modalConfigPremiBersama" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable pb-config-dialog">
        <form class="modal-content pb-config-modal" id="formConfigPremiBersama">
            <div class="modal-header pb-config-header">
                <div class="pb-config-header-main">
                    <div class="pb-config-title-icon">
                        <i class="mdi mdi-tune-variant"></i>
                    </div>
                    <div>
                        <div class="pb-config-kicker">Konfigurasi Premium</div>
                        <h5 class="pb-config-title">Konfigurasi Premi Bersama</h5>
                        <div class="pb-config-subtitle" id="configHeaderSubtitle">
                            Periode aktif mengikuti pilihan generate.
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pb-config-body">
                <div class="pb-config-loader d-none" id="configPremiBersamaLoading">
                    <span class="spinner-border spinner-border-sm text-primary"></span>
                    <div>
                        <strong id="configPremiBersamaLoadingTitle">Menyiapkan konfigurasi...</strong>
                        <span id="configPremiBersamaLoadingText">Mohon tunggu sebentar.</span>
                    </div>
                </div>

                <div class="pb-config-summary-grid">
                    <div class="pb-config-summary-card total">
                        <div class="pb-config-summary-label">Grand Total Preview</div>
                        <div class="pb-config-summary-value" id="configSummaryTotalValue">Rp 0</div>
                        <div class="pb-config-summary-foot" id="configSummaryTotalFoot">Preview periode aktif</div>
                    </div>
                    <div class="pb-config-summary-card">
                        <div class="pb-config-summary-label">Mapping UMUM</div>
                        <div class="pb-config-summary-value" id="configSummaryMappingValue">-</div>
                        <div class="pb-config-summary-foot" id="configSummaryMappingFoot">Belum dipilih</div>
                    </div>
                    <div class="pb-config-summary-card">
                        <div class="pb-config-summary-label">Karcis BPJS ke UMUM</div>
                        <div class="pb-config-summary-value" id="configSummaryActionValue">0</div>
                        <div class="pb-config-summary-foot" id="configSummaryActionFoot">Belum ada karcis khusus</div>
                    </div>
                    <div class="pb-config-summary-card">
                        <div class="pb-config-summary-label">Filter Dokter</div>
                        <div class="pb-config-summary-value" id="configSummaryDoctorValue">Nonaktif</div>
                        <div class="pb-config-summary-foot" id="configSummaryDoctorFoot">Semua dokter masuk normal</div>
                    </div>
                    <div class="pb-config-summary-card">
                        <div class="pb-config-summary-label">Sumber Khusus</div>
                        <div class="pb-config-summary-value" id="configSummarySourceValue">0 rule</div>
                        <div class="pb-config-summary-foot" id="configSummarySourceFoot">Routing default</div>
                    </div>
                </div>

                <div class="pb-config-grid">
                    <section class="pb-config-section">
                        <div class="pb-config-section-head">
                            <div class="pb-config-section-title-wrap">
                                <div class="pb-config-section-icon">
                                    <i class="mdi mdi-source-branch"></i>
                                </div>
                                <div>
                                    <h6 class="pb-config-section-title">Mapping Premi & Generator</h6>
                                    <div class="pb-config-section-note">Sumber mapping, plotting, dan aturan ruang rawat.</div>
                                </div>
                            </div>
                        </div>
                        <div class="pb-config-section-body">
                            <div class="pb-config-form-grid">
                                <div>
                                    <label class="form-label" for="configMappingUmum">Mapping Premi UMUM</label>
                                    <select id="configMappingUmum" class="form-select" required></select>
                                    <div class="pb-config-help" id="configMappingUmumNote">Memuat mapping premi...</div>
                                </div>
                                <div>
                                    <label class="form-label" for="configMappingBpjs">Mapping Premi BPJS</label>
                                    <select id="configMappingBpjs" class="form-select"></select>
                                    <div class="pb-config-help">Disiapkan untuk alur BPJS.</div>
                                </div>
                                <div>
                                    <label class="form-label" for="configBpjsSourceMode">Sumber Data BPJS</label>
                                    <select id="configBpjsSourceMode" class="form-select">
                                        <option value="previous">Bulan Sebelumnya</option>
                                        <option value="current">Periode Generate</option>
                                    </select>
                                    <div class="pb-config-help" id="configBpjsSourceModeNote">
                                        Berlaku untuk data rawat BPJS dan karcis BPJS yang ditarik ke UMUM.
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label" for="configOperasiBpjsPercent">Operasi BPJS ke Premi Bersama (%)</label>
                                    <input type="number" min="0" max="100" step="0.0001" id="configOperasiBpjsPercent" class="form-control" value="20">
                                    <div class="pb-config-help">
                                        Rata-rata pegawai instrumen + rata-rata pegawai anestesi dikali persen ini.
                                    </div>
                                </div>
                            </div>

                            <div class="pb-config-ploting-grid mt-3">
                                <div>
                                    <label class="form-label" for="configUgdPloting">Plotting UGD</label>
                                    <select id="configUgdPloting" class="form-select"></select>
                                </div>
                                <div>
                                    <label class="form-label" for="configVkPloting">Plotting VK</label>
                                    <select id="configVkPloting" class="form-select"></select>
                                </div>
                                <div>
                                    <label class="form-label" for="configKamarPloting">Plotting Kamar</label>
                                    <select id="configKamarPloting" class="form-select"></select>
                                </div>
                                <div>
                                    <label class="form-label" for="configBhpPloting">Plotting BHP</label>
                                    <select id="configBhpPloting" class="form-select"></select>
                                </div>
                            </div>

                            <div class="pb-config-switch-list">
                                <label class="pb-config-switch" for="configIgnoreIcu">
                                    <span>
                                        <strong>Abaikan ICU</strong>
                                        <span>Tindakan dalam ruang ICU tidak masuk hitungan rawat.</span>
                                    </span>
                                    <input class="form-check-input m-0" type="checkbox" id="configIgnoreIcu" checked>
                                </label>
                                <label class="pb-config-switch" for="configIgnoreNicu">
                                    <span>
                                        <strong>Abaikan NICU</strong>
                                        <span>Tindakan dalam ruang NICU tidak masuk hitungan rawat.</span>
                                    </span>
                                    <input class="form-check-input m-0" type="checkbox" id="configIgnoreNicu" checked>
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="pb-config-section">
                        <div class="pb-config-section-head">
                            <div class="pb-config-section-title-wrap">
                                <div class="pb-config-section-icon">
                                    <i class="mdi mdi-account-filter-outline"></i>
                                </div>
                                <div>
                                    <h6 class="pb-config-section-title">Karcis BPJS Ikut UMUM & Filter Dokter</h6>
                                    <div class="pb-config-section-note">Tindakan BPJS terpilih dihitung di UMUM dan dikeluarkan dari BPJS.</div>
                                </div>
                            </div>
                        </div>
                        <div class="pb-config-section-body">
                            <div class="mb-3">
                                <label class="form-label" for="configIncludedActions">Tindakan Karcis BPJS yang Ikut UMUM</label>
                                <select id="configIncludedActions" class="form-select" multiple size="8"></select>
                                <div class="pb-config-help">Pilih satu atau lebih tindakan karcis BPJS. Tindakan ini akan masuk hitungan UMUM dan tidak dihitung ulang saat alur BPJS dipakai.</div>
                            </div>
                            <div class="pb-config-form-grid">
                                <div>
                                    <label class="form-label" for="configDoctorCodes">Dokter Terpilih</label>
                                    <select id="configDoctorCodes" class="form-select" multiple size="6"></select>
                                </div>
                                <div>
                                    <label class="form-label" for="configDoctorActions">Tindakan Filter Dokter</label>
                                    <select id="configDoctorActions" class="form-select" multiple size="6"></select>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="pb-config-section mb-0">
                    <div class="pb-config-section-head">
                        <div class="pb-config-section-title-wrap">
                            <div class="pb-config-section-icon">
                                <i class="mdi mdi-sitemap-outline"></i>
                            </div>
                            <div>
                                <h6 class="pb-config-section-title">Mapping Sumber Data ke Tindakan</h6>
                                <div class="pb-config-section-note">Routing khusus untuk enam tabel rawat Khanza.</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddSourceMapping">
                            <i class="mdi mdi-plus"></i> Tambah Mapping
                        </button>
                    </div>
                    <div class="pb-config-section-body">
                        <div id="sourceMappingRows"></div>
                        <div class="pb-config-help">
                            Tindakan tanpa mapping sumber tetap menerima data dari tabel rawat yang cocok.
                        </div>
                    </div>
                </section>
            </div>

            <div class="modal-footer pb-config-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary" id="btnSaveConfigPremiBersama">
                    <i class="mdi mdi-content-save-outline"></i> Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailPremiBersama" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable pb-detail-dialog">
        <div class="modal-content pb-detail-modal border-0">
            <div class="pb-detail-header">
                <div class="pb-detail-header-main">
                    <div class="pb-detail-title-icon">
                        <i class="mdi mdi-clipboard-text-search-outline"></i>
                    </div>
                    <div>
                        <h5 class="pb-detail-title mb-0">Detail Premi Bersama</h5>
                        <div class="pb-detail-subtitle" id="detailPremiBersamaMeta">Memuat snapshot perhitungan...</div>
                        <div class="pb-detail-chip-row">
                            <span class="pb-detail-chip" id="detailStatusBadge">Status</span>
                            <span class="pb-detail-chip" id="detailGeneratedBy">Generate oleh -</span>
                            <span class="pb-detail-chip" id="detailLockedInfo">Belum dikunci</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pb-detail-body">
                <div id="detailPremiBersamaLoading" class="pb-detail-loading">
                    <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                    Memuat snapshot perhitungan...
                </div>

                <div id="detailPremiBersamaContent" class="d-none">
                    <div class="pb-detail-metrics">
                        <div class="pb-detail-metric">
                            <span>Sumber Generator</span>
                            <strong id="detailGeneratorTotal">Rp 0</strong>
                        </div>
                        <div class="pb-detail-metric">
                            <span>Tindakan Rawat</span>
                            <strong id="detailRawatTotal">Rp 0</strong>
                        </div>
                        <div class="pb-detail-metric">
                            <span>Transaksi</span>
                            <strong id="detailTransactionCount">0</strong>
                        </div>
                        <div class="pb-detail-metric">
                            <span>Penerima</span>
                            <strong id="detailRecipientCount">0</strong>
                        </div>
                        <div class="pb-detail-metric">
                            <span>Total Skor</span>
                            <strong id="detailTotalSkor">0</strong>
                        </div>
                        <div class="pb-detail-metric grand">
                            <span>Grand Total</span>
                            <strong id="detailGrandTotal">Rp 0</strong>
                        </div>
                        <div class="pb-detail-metric">
                            <span>Dibagikan</span>
                            <strong id="detailDibagikan">Rp 0</strong>
                        </div>
                    </div>

                    <div class="pb-formula-strip">
                        <i class="mdi mdi-function-variant"></i>
                        <span id="detailFormulaBersama">
                            Grand total: <strong>Rp 0</strong>
                            <span class="pb-formula-muted">(Rp 0 generator + Rp 0 tindakan)</span>
                            dibagikan berdasarkan skor pegawai.
                        </span>
                    </div>

                    <div class="pb-info-strip" id="detailInsightBersama"></div>

                    <div class="pb-detail-section">
                        <div class="pb-detail-section-head">
                            <div>
                                <div class="pb-detail-section-title">Distribusi Pegawai</div>
                                <small class="text-muted" id="detailDistributionNoteBersama">
                                    Pembagian berdasarkan skor pegawai terhadap total skor.
                                </small>
                            </div>
                            <div class="pb-transaction-toolbar">
                                <input type="search" id="searchDetailDistributionBersama" class="form-control" placeholder="Cari pegawai">
                                <select id="filterDetailDistributionLimitBersama" class="form-select">
                                    <option value="25">Top 25</option>
                                    <option value="50">Top 50</option>
                                    <option value="all">Semua</option>
                                </select>
                                <span class="badge bg-light text-dark" id="detailDistributionCountBersama">0 penerima</span>
                            </div>
                        </div>
                        <div class="table-responsive pb-table-scroll sm">
                            <table class="table table-hover pb-detail-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Pegawai</th>
                                        <th class="text-end">Skor</th>
                                        <th class="text-end">%</th>
                                        <th class="text-end">Diterima</th>
                                    </tr>
                                </thead>
                                <tbody id="detailDistributionRowsBersama"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pb-detail-section">
                        <div class="pb-detail-section-head">
                            <div>
                                <div class="pb-detail-section-title">Rekap Mapping Premi</div>
                                <small class="text-muted">Pilih baris mapping untuk melihat rawat pembentuknya.</small>
                            </div>
                            <div class="pb-transaction-toolbar">
                                <input type="search" id="searchDetailMappingBersama" class="form-control" placeholder="Cari mapping">
                                <span class="badge bg-light text-dark" id="detailMappingCountBersama">0 mapping</span>
                            </div>
                        </div>
                        <div class="table-responsive pb-table-scroll">
                            <table class="table table-hover pb-detail-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Tindakan</th>
                                        <th>Sumber</th>
                                        <th>Jenis</th>
                                        <th class="text-end">Nilai</th>
                                        <th class="text-center">Data</th>
                                        <th class="text-end">Dasar</th>
                                        <th class="text-end">Hasil</th>
                                    </tr>
                                </thead>
                                <tbody id="detailMappingRowsBersama"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pb-detail-section">
                        <div class="pb-detail-section-head">
                            <div>
                                <div class="pb-detail-section-title">Rekap Sumber Data</div>
                                <small class="text-muted" id="detailSourceMetaBersama">
                                    Pilih mapping untuk melihat komposisi sumber.
                                </small>
                            </div>
                            <span class="badge bg-light text-dark" id="detailSourceCountBersama">0 sumber</span>
                        </div>
                        <div class="table-responsive pb-table-scroll sm">
                            <table class="table table-hover pb-detail-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Sumber</th>
                                        <th class="text-center">Data</th>
                                        <th class="text-end">Biaya Rawat</th>
                                    </tr>
                                </thead>
                                <tbody id="detailSourceRowsBersama"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pb-info-strip" id="detailSelectedInsightBersama"></div>

                    <div class="pb-detail-section mb-0">
                        <div class="pb-detail-section-head">
                            <div>
                                <div class="pb-detail-section-title">Data Rawat Tersimpan</div>
                                <small class="text-muted" id="detailRawatMetaBersama">
                                    Pilih mapping untuk melihat snapshot rawat.
                                </small>
                            </div>
                            <div class="pb-transaction-toolbar">
                                <select id="filterDetailMappingBersama" class="form-select">
                                    <option value="">Pilih mapping</option>
                                </select>
                                <select id="filterDetailRawatSourceBersama" class="form-select">
                                    <option value="all">Semua sumber</option>
                                </select>
                                <select id="filterDetailRawatPelaksanaBersama" class="form-select">
                                    <option value="all">Semua pelaksana</option>
                                    <option value="doctor">Dokter</option>
                                    <option value="paramedic">Paramedis</option>
                                    <option value="routed">Routing dokter</option>
                                </select>
                                <input type="search" id="searchDetailRawatBersama" class="form-control" placeholder="Cari no. rawat / tindakan">
                            </div>
                        </div>
                        <div class="table-responsive pb-table-scroll lg">
                            <table class="table table-hover pb-detail-table mb-0">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>No. Rawat</th>
                                        <th>Pasien</th>
                                        <th>Sumber</th>
                                        <th>Tindakan</th>
                                        <th>Penjamin</th>
                                        <th>Pelaksana</th>
                                        <th class="text-end">Biaya</th>
                                    </tr>
                                </thead>
                                <tbody id="detailRawatRowsBersama"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2 px-4">
                <small class="text-muted me-auto">
                    <i class="mdi mdi-information-outline me-1"></i>
                    Snapshot mengikuti konfigurasi saat generate.
                </small>
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
