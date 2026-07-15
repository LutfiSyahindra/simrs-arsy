<div class="modal fade" id="modalSlipDeliveryLog" tabindex="-1" aria-labelledby="modalSlipDeliveryLogLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content payroll-modal">
            <div class="modal-header">
                <div class="payroll-modal-title">
                    <div class="payroll-modal-icon is-blue">
                        <i class="mdi mdi-clipboard-text-clock-outline mdi-24px"></i>
                    </div>
                    <div>
                        <div class="payroll-kicker">Log Distribusi Slip</div>
                        <h5 class="modal-title fw-bold mb-0" id="modalSlipDeliveryLogLabel">Riwayat Email & WhatsApp</h5>
                        <small class="payroll-muted-copy">Status pengiriman berhasil dan gagal untuk periode aktif</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="wa-slip-overview mb-3">
                    <div class="wa-slip-stat">
                        <span>Periode</span>
                        <strong id="slipDeliveryLogPeriode">-</strong>
                    </div>
                    <div class="wa-slip-stat">
                        <span>Filter</span>
                        <strong id="slipDeliveryLogFilterText">Semua log</strong>
                    </div>
                    <div class="wa-slip-stat">
                        <span>Terakhir Refresh</span>
                        <strong id="slipDeliveryLogRefreshedAt">-</strong>
                    </div>
                </div>

                <div class="wa-slip-control-panel mb-3">
                    <div class="wa-slip-filter-grid">
                        <select id="filterDeliveryLogTahap" class="form-select form-select-sm">
                            <option value="">Semua Tahap</option>
                            <option value="1">Tahap 1</option>
                            <option value="2">Tahap 2</option>
                        </select>

                        <select id="filterDeliveryLogChannel" class="form-select form-select-sm">
                            <option value="">Semua Channel</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="email">Email</option>
                        </select>

                        <select id="filterDeliveryLogStatus" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            <option value="success">Berhasil</option>
                            <option value="failed">Gagal</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive wa-slip-table-wrap">
                    <table id="tableSlipDeliveryLog" class="table table-hover align-middle mb-0 w-100 delivery-log-table">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Waktu</th>
                                <th>Channel</th>
                                <th>Status</th>
                                <th>Tahap</th>
                                <th>Pegawai</th>
                                <th>Kontak</th>
                                <th>Pesan</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnRefreshSlipDeliveryLog" class="btn btn-outline-primary btn-sm">
                    <i class="mdi mdi-refresh me-1"></i>
                    Refresh
                </button>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
