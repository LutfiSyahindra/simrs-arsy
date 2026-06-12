<div class="modal fade" id="modalGenerateBhp" tabindex="-1" aria-labelledby="modalGenerateBhpLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalGenerateBhpLabel">Generate Kamar</h5>
                    <small class="text-muted">Masukkan nominal per hari inap yang akan dihitung</small>
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
                        <label for="jenisGenerateBhpLabel" class="form-label">Jenis Kamar</label>
                        <input type="hidden" id="jenisGenerateBhp">
                        <input type="text" id="jenisGenerateBhpLabel" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="periodeGenerateBhp" class="form-label">Periode</label>
                        <input type="text" id="periodeGenerateBhp" class="form-control" readonly>
                    </div>

                    <div>
                        <label for="nominalHitungBhp" class="form-label">Nominal Hitung</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="nominalHitungBhp" class="form-control"
                                inputmode="numeric" placeholder="Contoh: 25.000" autocomplete="off" required>
                        </div>
                        <div class="invalid-feedback" id="nominalHitungBhpError"></div>
                        <small class="text-muted">Total premi kamar = total hari inap x nominal per hari.</small>
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
                        <h5 class="modal-title fw-bold mb-0" id="modalDetailBhpLabel">Detail Generate Kamar</h5>
                        <small class="text-white-50">Snapshot penggunaan kamar yang digunakan dalam perhitungan</small>
                        <div>
                            <span class="bhp-detail-type-badge" id="detailBhpTypeBadge">-</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body bhp-detail-body">
                <div class="row g-2 g-lg-3 mb-3">
                    <div class="col-12 col-sm-6 col-lg">
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
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="bhp-detail-summary is-success">
                            <div class="bhp-detail-summary-icon">
                                <i class="mdi mdi-account-multiple-check-outline"></i>
                            </div>
                            <div>
                                <span>Jumlah Kamar</span>
                                <strong id="detailBhpJumlah">0 kamar</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="bhp-detail-summary is-success">
                            <div class="bhp-detail-summary-icon">
                                <i class="mdi mdi-calendar-clock-outline"></i>
                            </div>
                            <div>
                                <span>Total Hari Inap</span>
                                <strong id="detailBhpLama">0 hari</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg">
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
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="bhp-detail-summary is-total">
                            <div class="bhp-detail-summary-icon">
                                <i class="mdi mdi-wallet-outline"></i>
                            </div>
                            <div>
                                <span>Total Premi Kamar</span>
                                <strong class="text-primary" id="detailBhpTotal">Rp 0</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bhp-detail-formula mb-3">
                    <i class="mdi mdi-calculator-variant-outline text-primary"></i>
                    <span id="detailBhpFormula">0 hari x Rp 0 = Rp 0</span>
                </div>

                <div class="bhp-detail-filter-panel mb-3">
                    <div class="bhp-detail-filter-head">
                        <div class="bhp-detail-filter-heading">
                            <span class="bhp-detail-filter-icon">
                                <i class="mdi mdi-tune-variant"></i>
                            </span>
                            <div>
                                <strong>Filter Detail Penggunaan Kamar</strong>
                                <small>Pilih penjamin dan kamar untuk mempersempit data.</small>
                            </div>
                        </div>
                        <button type="button" id="btnResetDetailBhpFilters"
                            class="btn btn-sm bhp-detail-reset-filter" disabled>
                            <i class="mdi mdi-filter-remove-outline me-1"></i>
                            Reset Filter
                        </button>
                    </div>

                    <div class="bhp-detail-filter-body">
                        <div class="bhp-detail-search-field">
                            <label for="searchDetailBhp">
                                <i class="mdi mdi-magnify"></i>
                                Pencarian
                            </label>
                            <div class="input-group bhp-detail-search">
                                <span class="input-group-text">
                                    <i class="mdi mdi-magnify"></i>
                                </span>
                                <input type="text" id="searchDetailBhp" class="form-control"
                                    placeholder="No. rawat, tanggal, kamar, atau penjamin">
                                <button type="button" id="btnClearSearchDetailBhp"
                                    class="btn btn-light d-none" title="Hapus pencarian">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </div>
                        </div>

                        <div class="bhp-detail-select-field">
                            <label for="filterDetailPenjamin">
                                <i class="mdi mdi-shield-account-outline"></i>
                                Penjamin
                            </label>
                            <div class="bhp-detail-select-wrap">
                                <select id="filterDetailPenjamin" class="form-select">
                                    <option value="all">Semua Penjamin</option>
                                </select>
                                <small id="detailBhpPenjaminMeta">0 penjamin tersedia</small>
                            </div>
                        </div>

                        <div class="bhp-detail-select-field">
                            <label for="filterDetailKamar">
                                <i class="mdi mdi-bed-outline"></i>
                                Kamar
                            </label>
                            <div class="bhp-detail-select-wrap">
                                <select id="filterDetailKamar" class="form-select">
                                    <option value="all">Semua Kamar</option>
                                </select>
                                <small id="detailBhpKamarMeta">0 kamar tersedia</small>
                            </div>
                        </div>
                    </div>

                    <div class="bhp-detail-filter-footer">
                        <div class="bhp-detail-active-filter">
                            <span class="bhp-detail-active-filter-label">Filter aktif</span>
                            <div id="detailBhpActiveFilters" class="bhp-detail-active-filter-list">
                                <span class="bhp-detail-no-filter">Semua data ditampilkan</span>
                            </div>
                        </div>
                        <div class="bhp-detail-result-count">
                            <i class="mdi mdi-format-list-numbered me-1"></i>
                            <span id="detailBhpVisibleCount">0 dari 0 data</span>
                        </div>
                    </div>
                </div>

                <div id="detailBhpLoading" class="text-center py-5 d-none">
                    <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                    Memuat detail kamar...
                </div>

                <div id="detailBhpPenjaminSummary" class="bhp-penjamin-summary mb-3 d-none">
                    <div class="bhp-penjamin-summary-head">
                        <div>
                            <span class="bhp-penjamin-summary-kicker">Rekapitulasi</span>
                            <h6>Penggunaan Kamar per Penjamin</h6>
                            <small>Nilai rekap mengikuti pencarian dan filter yang sedang aktif.</small>
                        </div>
                        <span class="bhp-penjamin-summary-count" id="detailBhpPenjaminSummaryCount">
                            0 penjamin
                        </span>
                    </div>

                    <div class="bhp-penjamin-summary-totals">
                        <div class="bhp-penjamin-total-item">
                            <span>Pasien / No. Rawat</span>
                            <strong id="detailBhpFilteredRawat">0</strong>
                        </div>
                        <div class="bhp-penjamin-total-item">
                            <span>Penggunaan Kamar</span>
                            <strong id="detailBhpFilteredUsage">0</strong>
                        </div>
                        <div class="bhp-penjamin-total-item">
                            <span>Total Lama Inap</span>
                            <strong id="detailBhpFilteredLama">0 hari</strong>
                        </div>
                        <div class="bhp-penjamin-total-item is-money">
                            <span>Total Premi</span>
                            <strong id="detailBhpFilteredPremi">Rp 0</strong>
                        </div>
                    </div>

                    <div class="bhp-penjamin-summary-table-wrap">
                        <table class="table align-middle bhp-penjamin-summary-table">
                            <thead>
                                <tr>
                                    <th>Penjamin</th>
                                    <th class="text-center">Pasien</th>
                                    <th class="text-center">Penggunaan Kamar</th>
                                    <th class="text-center">Lama Inap</th>
                                    <th class="text-center">Rata-rata</th>
                                    <th class="text-end">Total Premi</th>
                                </tr>
                            </thead>
                            <tbody id="detailBhpPenjaminSummaryRows">
                                <tr>
                                    <td colspan="6" class="bhp-penjamin-summary-empty">
                                        Belum ada data rekap.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bhp-detail-table-wrap" id="detailBhpTableWrap">
                    <table class="table table-hover align-middle bhp-detail-table">
                        <thead>
                            <tr>
                                <th width="7%">No</th>
                                <th>No. Rawat</th>
                                <th>Tanggal Masuk</th>
                                <th>Kamar</th>
                                <th class="text-center">Lama</th>
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
