<div class="modal fade" id="modalDetailNonMedis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="non-medis-detail-header">
                <div class="non-medis-detail-header-main">
                    <div class="non-medis-detail-header-icon">
                        <i class="mdi mdi-clipboard-text-search-outline"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Detail Premi Pelayanan Non Medis</h5>
                        <small class="text-white-50" id="detailNonMedisMeta">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3 p-lg-4">
                <div id="detailNonMedisLoading" class="text-center py-5">
                    <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                    Memuat snapshot perhitungan...
                </div>

                <div id="detailNonMedisContent" class="d-none">
                    <div class="non-medis-detail-metrics">
                        <div class="non-medis-detail-metric">
                            <span>Transaksi</span>
                            <strong id="detailJumlahTransaksi">0</strong>
                        </div>
                        <div class="non-medis-detail-metric">
                            <span>Biaya Rawat</span>
                            <strong id="detailBiayaRawat">Rp 0</strong>
                        </div>
                        <div class="non-medis-detail-metric">
                            <span>Mapping Premi</span>
                            <strong id="detailMappingPremi">Rp 0</strong>
                        </div>
                        <div class="non-medis-detail-metric">
                            <span>BHP</span>
                            <strong id="detailBhp">Rp 0</strong>
                        </div>
                        <div class="non-medis-detail-metric">
                            <span>Kamar Inap</span>
                            <strong id="detailKamar">Rp 0</strong>
                        </div>
                        <div class="non-medis-detail-metric total">
                            <span>Total Final</span>
                            <strong id="detailTotalFinal">Rp 0</strong>
                            <small class="text-muted d-block mt-1" id="detailSebelumPembagi">
                                Sebelum pembagi: Rp 0
                            </small>
                        </div>
                    </div>

                    <div class="non-medis-formula rounded mb-3">
                        <i class="mdi mdi-function-variant"></i>
                        <span id="detailFormula">(Rp 0 + Rp 0 + Rp 0) / 1 = Rp 0</span>
                    </div>

                    <div class="non-medis-detail-section">
                        <div class="non-medis-detail-section-head">
                            <div>
                                <div class="non-medis-detail-section-title">Rekap Mapping Premi</div>
                                <small class="text-muted">Klik baris untuk melihat transaksi pembentuknya.</small>
                            </div>
                            <span class="badge bg-light text-dark" id="detailMappingCount">0 mapping</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover non-medis-detail-table">
                                <thead>
                                    <tr>
                                        <th>Premi</th>
                                        <th>Jenis Tindakan</th>
                                        <th>Jenis</th>
                                        <th class="text-end">Nilai Mapping</th>
                                        <th class="text-center">Data</th>
                                        <th class="text-end">Biaya Rawat</th>
                                        <th class="text-end">Dasar Hitung</th>
                                        <th class="text-end">Hasil</th>
                                    </tr>
                                </thead>
                                <tbody id="detailMappingRows"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="non-medis-detail-section">
                        <div class="non-medis-detail-section-head">
                            <div>
                                <div class="non-medis-detail-section-title">Jumlah Data per Penjamin</div>
                                <small class="text-muted" id="detailProviderMeta">
                                    Pilih mapping untuk melihat rekap penjamin.
                                </small>
                            </div>
                            <span class="badge bg-light text-dark" id="detailProviderCount">0 penjamin</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover non-medis-detail-table">
                                <thead>
                                    <tr>
                                        <th>Penjamin</th>
                                        <th>Kode</th>
                                        <th class="text-center">Jumlah Data</th>
                                        <th class="text-end">Biaya Rawat</th>
                                    </tr>
                                </thead>
                                <tbody id="detailProviderRows"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="non-medis-detail-section mb-0">
                        <div class="non-medis-detail-section-head">
                            <div>
                                <div class="non-medis-detail-section-title">Transaksi Khanza</div>
                                <small class="text-muted" id="detailTransactionMeta">
                                    Pilih mapping untuk melihat transaksi.
                                </small>
                            </div>
                            <div class="non-medis-transaction-toolbar">
                                <select id="filterDetailMapping" class="form-select">
                                    <option value="">Pilih mapping</option>
                                </select>
                                <input type="search" id="searchDetailTransaction" class="form-control"
                                    placeholder="Cari no. rawat / tindakan">
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 420px;">
                            <table class="table table-hover non-medis-detail-table">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>No. Rawat</th>
                                        <th>Sumber</th>
                                        <th>Tindakan</th>
                                        <th>Penjamin</th>
                                        <th>Pelaksana</th>
                                        <th class="text-end">Biaya Rawat</th>
                                    </tr>
                                </thead>
                                <tbody id="detailTransactionRows"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 bg-light py-2 px-4">
                <small class="text-muted me-auto">
                    <i class="mdi mdi-information-outline me-1"></i>
                    Data merupakan snapshot saat generate.
                </small>
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
