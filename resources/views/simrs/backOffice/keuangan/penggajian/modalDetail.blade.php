<div class="modal fade" id="modalSlipGajiTahap1" tabindex="-1" aria-labelledby="modalSlipGajiTahap1Label"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content payroll-modal">
            <div class="modal-header">
                <div class="payroll-modal-title">
                    <div class="payroll-modal-icon is-blue">
                        <i class="mdi mdi-file-document-outline mdi-24px"></i>
                    </div>
                    <div>
                        <div class="payroll-kicker">Rincian Payroll</div>
                        <h5 class="modal-title fw-bold mb-0" id="modalSlipGajiTahap1Label">Slip Gaji Tahap 1</h5>
                        <small class="payroll-muted-copy">Detail komponen gaji pegawai periode terpilih</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="slip-card">
                    <div class="slip-identity-panel mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div class="d-flex align-items-start gap-3 min-w-0">
                                <div class="slip-avatar">
                                    <i class="mdi mdi-account-outline mdi-24px"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="payroll-kicker">Pegawai</div>
                                    <h6 class="fw-bold mb-1" id="slipNama">-</h6>
                                    <div class="payroll-muted-copy" id="slipJabatan">-</div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="payroll-kicker">Status</div>
                                <span class="payroll-status-badge is-tetap mt-1" id="slipStatus">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <div class="slip-info-box">
                                <span>NIK</span>
                                <strong id="slipNik">-</strong>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="slip-info-box">
                                <span>Periode</span>
                                <strong id="slipPeriode">-</strong>
                            </div>
                        </div>
                    </div>

                    <div class="slip-detail-list mb-3">
                        <div class="slip-detail-row">
                            <span id="slipGajiPokokLabel">Gaji Pokok</span>
                            <strong id="slipGajiPokok">Rp 0</strong>
                        </div>
                        <div class="slip-detail-row">
                            <span id="slipGajiDibayarLabel">Gaji Dibayarkan</span>
                            <strong id="slipGajiDibayar">Rp 0</strong>
                        </div>
                        <div class="slip-detail-row d-none" id="slipPremiUtamaRow">
                            <span>Premi Sesuai Konfigurasi</span>
                            <strong id="slipPremiUtama">Rp 0</strong>
                        </div>
                    </div>

                    <div class="slip-allowance-box mb-3">
                        <div class="slip-section-title" id="slipDetailListTitle">Rincian Tunjangan</div>
                        <div id="slipTunjanganDetail"></div>
                        <div class="slip-detail-row">
                            <span id="slipTotalTunjanganLabel">Total Tunjangan</span>
                            <strong id="slipTunjangan">Rp 0</strong>
                        </div>
                    </div>

                    <div class="slip-allowance-box mb-3" id="slipPremiBox">
                        <div class="slip-section-title">Rincian Premi Konfigurasi</div>
                        <div id="slipPremiDetail"></div>
                        <div class="slip-detail-row">
                            <span>Total Premi</span>
                            <strong id="slipPremi">Rp 0</strong>
                        </div>
                    </div>

                    <div class="slip-allowance-box mb-3 d-none" id="slipPotonganBox">
                        <div class="slip-section-title">Rincian Potongan</div>
                        <div id="slipPotonganDetail"></div>
                        <div class="slip-detail-row">
                            <span>Total Potongan</span>
                            <strong id="slipPotongan">Rp 0</strong>
                        </div>
                    </div>

                    <div class="slip-detail-list">
                        <div class="slip-detail-row total">
                            <span>Total Diterima</span>
                            <strong id="slipTotal">Rp 0</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
