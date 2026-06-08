<div class="modal fade" id="modalSlipWhatsapp" tabindex="-1" aria-labelledby="modalSlipWhatsappLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalSlipWhatsappLabel">
                        <i class="mdi mdi-whatsapp text-success me-1"></i>
                        Kirim Slip Gaji Whatsapp
                    </h5>
                    <small class="text-muted">Periode <span id="waSlipPeriode">-</span></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="wa-slip-toolbar mb-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="checkAllSlipWhatsapp">
                        <label class="form-check-label fw-semibold" for="checkAllSlipWhatsapp">
                            Pilih Semua
                        </label>
                    </div>

                    <div class="input-group input-group-sm wa-slip-search">
                        <span class="input-group-text bg-white">
                            <i class="mdi mdi-magnify text-muted"></i>
                        </span>
                        <input type="text" id="searchSlipWhatsappPegawai" class="form-control"
                            placeholder="Cari pegawai...">
                    </div>

                    <span class="badge bg-success-subtle text-success" id="waSlipSelectedCount">0 dipilih</span>
                </div>

                <div class="wa-slip-loading text-center py-5" id="waSlipLoading">
                    <span class="spinner-border spinner-border-sm text-success me-1"></span>
                    Memuat penerima...
                </div>

                <div class="wa-slip-empty text-center py-5 d-none" id="waSlipEmpty">
                    <i class="mdi mdi-account-alert-outline mdi-36px text-muted d-block mb-2"></i>
                    <div class="fw-semibold">Belum ada penerima</div>
                    <small class="text-muted">Pastikan data gaji periode ini sudah digenerate dan nomor Whatsapp terisi.</small>
                </div>

                <div class="table-responsive wa-slip-table-wrap d-none" id="waSlipTableWrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center"></th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Status</th>
                                <th>No. Whatsapp</th>
                                <th class="text-end">Total Slip</th>
                            </tr>
                        </thead>
                        <tbody id="waSlipPegawaiList"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" id="btnSendSlipWhatsapp" class="btn btn-success btn-sm">
                    <i class="mdi mdi-send me-1"></i>
                    Kirim Slip Gaji Whatsapp
                </button>
            </div>
        </div>
    </div>
</div>
