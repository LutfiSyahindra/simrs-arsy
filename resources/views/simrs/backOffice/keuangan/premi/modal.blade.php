<div class="modal fade" id="modalDetailPremi" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header align-items-start">
                <div class="d-flex align-items-start gap-3">
                    <!-- Avatar -->
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width:42px;height:42px;font-weight:600;" id="avatarPegawai">
                        ?
                    </div>

                    <div>
                        <h5 class="modal-title mb-0">Detail Premi</h5>
                        <div class="small text-muted mt-1">
                            <strong id="namaPegawai" class="text-primary"></strong>
                            <span class="mx-1">•</span>
                            Periode:
                            <span id="periodePremi">-</span>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">

                <!-- TAB FILTER -->
                <ul class="nav nav-tabs mb-3" id="tabDetailPremi">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" data-sumber="">Semua</button>
                    </li>

                    <li class="nav-item">
                        <button type="button" class="nav-link" data-sumber="RAWAT">Rawat (Semua)</button>
                    </li>

                    <li class="nav-item tab-rs">
                        <button type="button" class="nav-link" data-sumber="RAWAT_JALAN">Rawat Jalan</button>
                    </li>
                    <li class="nav-item tab-rs">
                        <button type="button" class="nav-link" data-sumber="RAWAT_INAP">Rawat Inap</button>
                    </li>

                    <li class="nav-item tab-layanan">
                        <button type="button" class="nav-link" data-sumber="RAWAT"
                            data-layanan="Rawat Jalan">RJ</button>
                    </li>

                    <li class="nav-item tab-layanan">
                        <button type="button" class="nav-link" data-sumber="RAWAT"
                            data-layanan="Rawat Jalan dr dan Paramedis">RJ (Dr & Pr)</button>
                    </li>

                    <li class="nav-item tab-layanan">
                        <button type="button" class="nav-link" data-sumber="RAWAT"
                            data-layanan="Rawat Inap">RI</button>
                    </li>

                    <li class="nav-item tab-layanan">
                        <button type="button" class="nav-link" data-sumber="RAWAT"
                            data-layanan="Rawat Inap dr dan Paramedis">RI (Dr & Pr)</button>
                    </li>

                    <li class="nav-item">
                        <button type="button" class="nav-link" data-sumber="OPERASI">Operasi</button>
                    </li>

                    <li class="nav-item">
                        <button type="button" class="nav-link" data-sumber="LAB">Lab</button>
                    </li>

                    <li class="nav-item">
                        <button type="button" class="nav-link" data-sumber="RADIOLOGI">Radiologi</button>
                    </li>
                </ul>

                <!-- RINGKASAN -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-muted">Total Premi</small>
                                <div id="totalPremiTab" class="fw-semibold text-primary fs-5">Rp 0</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-muted">Kategori Aktif</small>
                                <div>
                                    <span class="badge bg-secondary" id="badgeKategori">Semua</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-muted">Jumlah Tindakan</small>
                                <div class="fw-semibold">
                                    <span id="jumlahData">0</span> Data
                                </div>
                                <small class="text-muted">Sesuai filter aktif</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="table-responsive">
                    <table id="tableDetailPremi" class="table table-sm table-striped w-100">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">Tanggal</th>
                                <th width="18%">No Rawat</th>
                                <th>Tindakan</th>
                                <th width="18%">Layanan</th>
                                <th width="15%" class="text-end">Premi (Rp)</th>
                            </tr>
                        </thead>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
