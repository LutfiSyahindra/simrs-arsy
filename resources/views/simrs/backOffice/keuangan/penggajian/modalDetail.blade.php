<div class="modal fade" id="modalSlipGajiTahap1" tabindex="-1" aria-labelledby="modalSlipGajiTahap1Label"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalSlipGajiTahap1Label">Slip Gaji Tahap 1</h5>
                    <small class="text-muted">Rincian pembayaran gaji pegawai</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="slip-card">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <div class="text-muted small">Periode</div>
                            <h6 class="fw-bold mb-0" id="slipPeriode">-</h6>
                        </div>
                        <span class="payroll-status-badge is-tetap" id="slipStatus">-</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <div class="slip-info-box">
                                <span>NIK</span>
                                <strong id="slipNik">-</strong>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="slip-info-box">
                                <span>Nama Pegawai</span>
                                <strong id="slipNama">-</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="slip-info-box">
                                <span>Jabatan</span>
                                <strong id="slipJabatan">-</strong>
                            </div>
                        </div>
                    </div>

                    <div class="slip-detail-list">
                        <div class="slip-detail-row">
                            <span>Gaji Pokok</span>
                            <strong id="slipGajiPokok">Rp 0</strong>
                        </div>
                        <div class="slip-detail-row">
                            <span>Gaji Dibayarkan</span>
                            <strong id="slipGajiDibayar">Rp 0</strong>
                        </div>
                        <div class="slip-allowance-box mb-3">
                            <div class="slip-section-title">Rincian Tunjangan</div>
                            <div id="slipTunjanganDetail"></div>
                        </div>
                        <div class="slip-detail-row">
                            <span>Total Tunjangan</span>
                            <strong id="slipTunjangan">Rp 0</strong>
                        </div>
                        <div class="slip-detail-row total">
                            <span>Total Diterima</span>
                            <strong id="slipTotal">Rp 0</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
