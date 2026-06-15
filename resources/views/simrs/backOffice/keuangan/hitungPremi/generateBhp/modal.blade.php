<div class="modal fade" id="modalGenerateBhp" tabindex="-1" aria-labelledby="modalGenerateBhpLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalGenerateBhpLabel">Generate BHP</h5>
                    <small class="text-muted">Masukkan nominal per pasien yang akan dihitung</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <form id="formGenerateBhp">
                <div class="modal-body">
                    <div class="bhp-modal-info mb-3">
                        Data periode yang sama akan diperbarui dan detail pasien akan disesuaikan dengan data
                        terbaru dari Khanza.
                    </div>

                    <div class="mb-3">
                        <label for="jenisGenerateBhpLabel" class="form-label">Jenis BHP</label>
                        <input type="hidden" id="jenisGenerateBhp">
                        <input type="text" id="jenisGenerateBhpLabel" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="periodeGenerateBhp" class="form-label">Periode Hasil</label>
                        <input type="text" id="periodeGenerateBhp" class="form-control" readonly>
                        <small class="text-muted d-block mt-1" id="periodeGenerateSourceInfo"></small>
                    </div>

                    <div>
                        <label for="nominalHitungBhp" class="form-label">Nominal Hitung</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="nominalHitungBhp" class="form-control"
                                inputmode="numeric" placeholder="Contoh: 25.000" autocomplete="off" required>
                        </div>
                        <div class="invalid-feedback" id="nominalHitungBhpError"></div>
                        <small class="text-muted">Total BHP = jumlah pasien yang memenuhi kriteria x nominal.</small>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitGenerateBhp" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-calculator-variant-outline me-1"></i>
                        Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailBhp" tabindex="-1" aria-labelledby="modalDetailBhpLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 bhp-detail-modal">
            <div class="bhp-detail-header" id="detailBhpHeader">
                <div class="bhp-detail-header-main">
                    <div class="bhp-detail-header-icon">
                        <i class="mdi mdi-clipboard-text-search-outline"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalDetailBhpLabel">Detail Generate BHP</h5>
                        <small class="text-white-50">Snapshot pasien yang digunakan dalam perhitungan</small>
                        <div>
                            <span class="bhp-detail-type-badge" id="detailBhpTypeBadge">-</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body bhp-detail-body">
                <div class="row g-2 g-lg-3 mb-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="bhp-detail-summary">
                            <div class="bhp-detail-summary-icon">
                                <i class="mdi mdi-calendar-month-outline"></i>
                            </div>
                            <div>
                                <span>Periode</span>
                                <strong id="detailBhpPeriode">-</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="bhp-detail-summary is-success">
                            <div class="bhp-detail-summary-icon">
                                <i class="mdi mdi-account-multiple-check-outline"></i>
                            </div>
                            <div>
                                <span>Jumlah BHP</span>
                                <strong id="detailBhpJumlah">0 pasien</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="bhp-detail-summary is-warning">
                            <div class="bhp-detail-summary-icon">
                                <i class="mdi mdi-cash-multiple"></i>
                            </div>
                            <div>
                                <span>Nominal Hitung</span>
                                <strong id="detailBhpNominal">Rp 0</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="bhp-detail-summary is-total">
                            <div class="bhp-detail-summary-icon">
                                <i class="mdi mdi-wallet-outline"></i>
                            </div>
                            <div>
                                <span>Total BHP</span>
                                <strong class="text-primary" id="detailBhpTotal">Rp 0</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bhp-detail-formula mb-3">
                    <i class="mdi mdi-calculator-variant-outline text-primary"></i>
                    <span id="detailBhpFormula">0 pasien x Rp 0 = Rp 0</span>
                </div>

                <div class="bhp-detail-toolbar mb-2">
                    <div class="input-group input-group-sm bhp-detail-search">
                        <span class="input-group-text bg-white">
                            <i class="mdi mdi-magnify text-muted"></i>
                        </span>
                        <input type="text" id="searchDetailBhp" class="form-control"
                            placeholder="Cari no. rawat, tanggal, atau nama penjamin...">
                        <button type="button" id="btnClearSearchDetailBhp" class="btn btn-light d-none"
                            title="Hapus pencarian">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                    <span class="bhp-detail-result-count" id="detailBhpVisibleCount">0 data</span>
                </div>

                <div class="bhp-penjamin-filters mb-2" id="detailBhpPenjaminFilters"></div>

                <div id="detailBhpLoading" class="text-center py-5 d-none">
                    <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                    Memuat detail BHP...
                </div>

                <div class="bhp-detail-table-wrap" id="detailBhpTableWrap">
                    <table class="table table-hover align-middle bhp-detail-table">
                        <thead>
                            <tr>
                                <th width="7%">No</th>
                                <th>No. Rawat</th>
                                <th>Tanggal Registrasi</th>
                                <th>Penjamin</th>
                                <th class="text-center" width="7%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detailBhpRows"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-0 bg-white py-2 px-4">
                <small class="text-muted me-auto">
                    <i class="mdi mdi-information-outline me-1"></i>
                    Data merupakan snapshot saat proses generate.
                </small>
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
