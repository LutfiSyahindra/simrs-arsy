<div class="modal fade" id="modalConfigPremiDokter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content pd-config-modal" id="formConfigPremiDokter">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Konfigurasi Premi Dokter</h5>
                    <div class="text-muted small">Atur mapping tindakan visite, dokter umum, dan dokter spesialis.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <section class="pd-config-section">
                    <div class="pd-config-title"><i class="mdi mdi-format-list-checks"></i> Formula Jasa Visite</div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="configVisiteUmumPercent">Persen Default UMUM</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="configVisiteUmumPercent" min="0" max="100" step="0.0001" value="50">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="configVisiteBpjsNominal">Nominal BPJS per Data</label>
                            <input type="number" class="form-control" id="configVisiteBpjsNominal" min="0" step="1" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="configVisiteBpjsPercent">Persen BPJS</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="configVisiteBpjsPercent" min="0" max="100" step="0.0001" value="50">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="configSourcePeriodMode">Sumber Data Rawat BPJS</label>
                            <select class="form-select" id="configSourcePeriodMode">
                                <option value="current">Periode Berjalan</option>
                                <option value="previous">Bulan Sebelumnya</option>
                            </select>
                            <div class="text-muted small mt-1">Khusus BPJS. UMUM selalu memakai periode berjalan.</div>
                        </div>
                    </div>
                </section>

                <section class="pd-config-section">
                    <div class="pd-config-title"><i class="mdi mdi-clipboard-pulse-outline"></i> Pilih dari Master Mapping Tindakan</div>
                    <select id="configMappingTindakan" class="form-select" multiple></select>
                    <div class="text-muted small mt-2">
                        Pilih jenis mapping tindakan visite. Data rawat_jl_pr, rawat_inap_pr, rawat_jl_dr, rawat_inap_dr, rawat_jl_drpr, dan rawat_inap_drpr dicocokkan ke rincian mapping tersebut.
                    </div>
                </section>

                <section class="pd-config-section">
                    <div class="pd-config-title"><i class="mdi mdi-account-tie-outline"></i> Dokter Umum</div>
                    <select id="doctorSelectUmum" class="form-select pd-doctor-select" data-category="umum" multiple></select>
                    <div id="doctorRowsUmum" class="mt-2"></div>
                </section>

                <section class="pd-config-section">
                    <div class="pd-config-title"><i class="mdi mdi-account-heart-outline"></i> Dokter Spesialis 65%</div>
                    <select id="doctorSelectSpesialis65" class="form-select pd-doctor-select" data-category="spesialis_65" multiple></select>
                    <div id="doctorRowsSpesialis65" class="mt-2"></div>
                </section>

                <section class="pd-config-section mb-0">
                    <div class="pd-config-title"><i class="mdi mdi-account-star-outline"></i> Dokter Spesialis 80%</div>
                    <select id="doctorSelectSpesialis80" class="form-select pd-doctor-select" data-category="spesialis_80" multiple></select>
                    <div id="doctorRowsSpesialis80" class="mt-2"></div>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary" id="btnSaveConfigPremiDokter">
                    <i class="mdi mdi-content-save-outline"></i> Simpan Konfigurasi
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
