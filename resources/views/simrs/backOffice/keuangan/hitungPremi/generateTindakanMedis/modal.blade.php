<div class="modal fade" id="modalDetailTindakanMedis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="tm-detail-header">
                <div class="tm-detail-header-main">
                    <div class="tm-detail-header-icon">
                        <i class="mdi mdi-clipboard-text-search-outline"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Detail Premi Tindakan Medis</h5>
                        <small class="text-white-50" id="detailTindakanMedisMeta">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3 p-lg-4">
                <div id="detailTindakanMedisLoading" class="text-center py-5">
                    <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                    Memuat snapshot perhitungan...
                </div>

                <div id="detailTindakanMedisContent" class="d-none">
                    <div class="tm-detail-metrics">
                        <div class="tm-detail-metric">
                            <span>Transaksi</span>
                            <strong id="detailJumlahTransaksiMedis">0</strong>
                        </div>
                        <div class="tm-detail-metric">
                            <span>Biaya Rawat</span>
                            <strong id="detailBiayaRawatMedis">Rp 0</strong>
                        </div>
                        <div class="tm-detail-metric">
                            <span>Mapping</span>
                            <strong id="detailMappingMedis">Rp 0</strong>
                        </div>
                        <div class="tm-detail-metric">
                            <span>UGD</span>
                            <strong id="detailUgdMedis">Rp 0</strong>
                        </div>
                        <div class="tm-detail-metric">
                            <span>VK</span>
                            <strong id="detailVkMedis">Rp 0</strong>
                        </div>
                        <div class="tm-detail-metric">
                            <span>Pool ICU BPJS</span>
                            <strong id="detailIcuPoolBpjsMedis">Rp 0</strong>
                        </div>
                        <div class="tm-detail-metric grand">
                            <span>Grand Total</span>
                            <strong id="detailGrandMedis">Rp 0</strong>
                        </div>
                        <div class="tm-detail-metric">
                            <span>Setelah Pembagi</span>
                            <strong id="detailFinalMedis">Rp 0</strong>
                        </div>
                        <div class="tm-detail-metric">
                            <span>Dibagikan</span>
                            <strong id="detailDibagikanMedis">Rp 0</strong>
                        </div>
                    </div>

                    <div class="tm-formula mb-3">
                        <i class="mdi mdi-function-variant"></i>
                        <span id="detailFormulaMedis">
                            Grand total sebelum pembagi: <strong>Rp 0</strong>
                            <span class="tm-formula-muted">(Rp 0 + Rp 0 + Rp 0 + Rp 0)</span> &rarr; / 1 = Rp 0
                        </span>
                    </div>

                    <div class="tm-insight-strip" id="detailInsightMedis"></div>

                    <div class="tm-detail-section">
                        <div class="tm-detail-section-head">
                            <div>
                                <div class="tm-detail-section-title">Distribusi Pegawai</div>
                                <small class="text-muted" id="detailDistributionNoteMedis">
                                    Total diterima sudah termasuk tambahan ICU/NICU jika ada.
                                </small>
                            </div>
                            <div class="tm-transaction-toolbar">
                                <input type="search" id="searchDetailDistributionMedis" class="form-control"
                                    placeholder="Cari pegawai">
                                <select id="filterDetailDistributionBonusMedis" class="form-select">
                                    <option value="all">Semua penerima</option>
                                    <option value="bonus">Ada ICU/NICU</option>
                                    <option value="no_bonus">Tanpa ICU/NICU</option>
                                </select>
                                <span class="badge bg-light text-dark" id="detailDistributionCountMedis">
                                    0 penerima
                                </span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover tm-detail-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Pegawai</th>
                                        <th class="text-end">Dasar</th>
                                        <th class="text-end">ICU</th>
                                        <th class="text-end">NICU</th>
                                        <th class="text-end">Total Diterima</th>
                                    </tr>
                                </thead>
                                <tbody id="detailDistributionRowsMedis"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tm-detail-section">
                        <div class="tm-detail-section-head">
                            <div>
                                <div class="tm-detail-section-title">Rekap Mapping Premi</div>
                                <small class="text-muted">Pilih baris mapping untuk melihat rawat pembentuknya.</small>
                            </div>
                            <div class="tm-transaction-toolbar">
                                <input type="search" id="searchDetailMappingMedis" class="form-control"
                                    placeholder="Cari mapping">
                                <span class="badge bg-light text-dark" id="detailMappingCountMedis">0 mapping</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover tm-detail-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Tindakan</th>
                                        <th>Sumber</th>
                                        <th>Jenis</th>
                                        <th class="text-end">Nilai</th>
                                        <th class="text-center">Data</th>
                                        <th class="text-end">Sebelum</th>
                                        <th class="text-end">Sesudah</th>
                                    </tr>
                                </thead>
                                <tbody id="detailMappingRowsMedis"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tm-detail-section">
                        <div class="tm-detail-section-head">
                            <div>
                                <div class="tm-detail-section-title">Rekap Sumber Data</div>
                                <small class="text-muted" id="detailSourceMetaMedis">
                                    Pilih mapping untuk melihat komposisi sumber.
                                </small>
                            </div>
                            <span class="badge bg-light text-dark" id="detailSourceCountMedis">0 sumber</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover tm-detail-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Sumber</th>
                                        <th class="text-center">Data</th>
                                        <th class="text-end">Biaya Rawat</th>
                                    </tr>
                                </thead>
                                <tbody id="detailSourceRowsMedis"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tm-insight-strip" id="detailSelectedInsightMedis"></div>

                    <div class="tm-detail-section mb-0">
                        <div class="tm-detail-section-head">
                            <div>
                                <div class="tm-detail-section-title">Data Rawat Tersimpan</div>
                                <small class="text-muted" id="detailRawatMetaMedis">
                                    Pilih mapping untuk melihat snapshot rawat.
                                </small>
                            </div>
                            <div class="tm-transaction-toolbar">
                                <select id="filterDetailMappingMedis" class="form-select">
                                    <option value="">Pilih mapping</option>
                                </select>
                                <select id="filterDetailRawatSourceMedis" class="form-select">
                                    <option value="all">Semua sumber</option>
                                </select>
                                <select id="filterDetailRawatPelaksanaMedis" class="form-select">
                                    <option value="all">Semua pelaksana</option>
                                    <option value="doctor">Dokter</option>
                                    <option value="paramedic">Paramedis</option>
                                    <option value="drpr">Dokter & Paramedis</option>
                                    <option value="routed">Dialihkan ke perawat</option>
                                    <option value="karcis">Karcis BPJS</option>
                                </select>
                                <input type="search" id="searchDetailRawatMedis" class="form-control"
                                    placeholder="Cari no. rawat / tindakan">
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 430px;">
                            <table class="table table-hover tm-detail-table mb-0">
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
                                <tbody id="detailRawatRowsMedis"></tbody>
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
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
