<div class="modal fade" id="modalGenerateFisio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formGenerateFisio">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Generate Premi Fisio</h5>
                    <small class="text-muted">Input sumber generate lalu cek preview perhitungan.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="fisio-simple-head">
                    <div class="fisio-simple-main">
                        <div class="fisio-simple-icon"><i class="mdi mdi-calculator-variant-outline"></i></div>
                        <div>
                            <div class="fisio-simple-title" id="generateFisioTitle">Generate Fisio</div>
                            <div class="fisio-simple-text" id="generateFisioText">Pilih sumber perhitungan.</div>
                        </div>
                    </div>
                    <span class="fisio-simple-badge" id="generateFisioTypeBadge">Jenis</span>
                </div>

                <div class="fisio-config-grid mb-3">
                    <div class="fisio-config-section" id="generateFisioUmumFields">
                        <h6><i class="mdi mdi-format-list-bulleted-square"></i> Tindakan UMUM</h6>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <small class="fisio-field-help">Pilih lebih dari satu tindakan, lalu isi jumlah masing-masing.</small>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddGenerateFisioItem">
                                <i class="mdi mdi-plus"></i>
                                Tambah
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle fisio-action-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Tindakan</th>
                                        <th style="width: 130px;">Jumlah</th>
                                        <th style="width: 58px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="generateFisioItemRows"></tbody>
                            </table>
                        </div>
                        <div class="fisio-empty-state mt-3 d-none" id="generateFisioItemEmpty">
                            Belum ada tindakan dipilih.
                        </div>
                    </div>

                    <div class="fisio-config-section d-none" id="generateFisioBpjsFields">
                        <h6><i class="mdi mdi-account-multiple-check-outline"></i> Pasien BPJS</h6>
                        <div class="mb-0">
                            <label class="form-label">Jumlah Pasien</label>
                            <input type="number" min="1" step="1" class="form-control" id="generateFisioJumlahPasien" value="1">
                            <small class="fisio-field-help">Grand total mengikuti nominal BPJS pada konfigurasi.</small>
                        </div>
                    </div>

                    <div class="fisio-config-section">
                        <h6><i class="mdi mdi-source-branch"></i> Rumus Aktif</h6>
                        <div class="fisio-info-list" id="generateFisioFormulaPreview"></div>
                    </div>
                </div>

                <div id="previewFisioLoading" class="text-center py-4 d-none">
                    <span class="spinner-border text-success"></span>
                    <div class="text-muted mt-2">Menghitung preview fisio...</div>
                </div>

                <div id="previewFisioContent" class="d-none">
                    <div class="fisio-preview-grid mb-3" id="previewFisioStats"></div>

                    <div class="fisio-simple-section mb-3">
                        <div class="fisio-simple-section-title">Rincian Tindakan</div>
                        <div class="fisio-recipient-list mb-3" id="previewFisioSources"></div>
                    </div>

                    <div class="fisio-simple-section mb-3">
                        <div class="fisio-simple-section-title">Rincian Rumus</div>
                        <div class="fisio-info-list" id="previewFisioFormula"></div>
                    </div>

                    <div class="fisio-simple-section mb-0">
                        <div class="fisio-simple-section-title">Penerima Premi</div>
                        <div class="fisio-recipient-list" id="previewFisioRecipients"></div>
                        <div id="previewFisioQuality"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-outline-primary fw-bold" id="btnPreviewGenerateFisio">
                    <i class="mdi mdi-eye-outline me-1"></i>
                    Hitung Preview
                </button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitGenerateFisio" disabled>
                    <i class="mdi mdi-content-save-check-outline me-1"></i>
                    Generate dan Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalConfigFisio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formConfigFisio">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalConfigFisioLabel">Konfigurasi Premi Fisio</h5>
                    <small class="text-muted">Rumus UMUM dan BPJS disimpan terpisah.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenisConfigFisio">

                <div class="fisio-simple-head">
                    <div class="fisio-simple-main">
                        <div class="fisio-simple-icon"><i class="mdi mdi-tune-variant"></i></div>
                        <div>
                            <div class="fisio-simple-title" id="configFisioTitle">Konfigurasi Fisio</div>
                            <div class="fisio-simple-text" id="configFisioText">Atur rumus dan petugas penerima.</div>
                        </div>
                    </div>
                    <span class="fisio-simple-badge" id="configFisioTypeBadge">Jenis</span>
                </div>

                <div class="fisio-config-grid mb-3">
                    <div class="fisio-config-section">
                        <h6><i class="mdi mdi-cash-multiple"></i> Basis Grand Total</h6>
                        <div class="mb-3">
                            <label class="form-label">Nominal BPJS per Pasien</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" class="form-control" id="configFisioGrandNominal">
                            </div>
                            <small class="fisio-field-help" id="configFisioGrandHelp">UMUM memakai harga tindakan pada daftar tindakan.</small>
                        </div>
                    </div>

                    <div class="fisio-config-section">
                        <h6><i class="mdi mdi-account-star-outline"></i> Petugas 1</h6>
                        <div class="mb-3">
                            <label class="form-label">Mode Hitung</label>
                            <select class="form-select" id="configFisioPetugas1Mode">
                                <option value="percent">Persen dari grand total</option>
                                <option value="nominal">Nominal</option>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Persen</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configFisioPetugas1Percent">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nominal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" class="form-control" id="configFisioPetugas1Nominal">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fisio-config-section">
                        <h6><i class="mdi mdi-account-arrow-right-outline"></i> Petugas 2</h6>
                        <div class="mb-3">
                            <label class="form-label">Mode Hitung</label>
                            <select class="form-select" id="configFisioPetugas2Mode">
                                <option value="percent">Persen dari Petugas 1</option>
                                <option value="nominal">Nominal</option>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Persen</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configFisioPetugas2Percent">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nominal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" class="form-control" id="configFisioPetugas2Nominal">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fisio-config-grid mb-3">
                    <div class="fisio-config-section">
                        <h6><i class="mdi mdi-account-group-outline"></i> Premi Bersama</h6>
                        <div class="mb-3">
                            <label class="form-label">Mode Hitung</label>
                            <select class="form-select" id="configFisioBersamaMode">
                                <option value="percent">Persen dari Petugas 1</option>
                                <option value="nominal">Nominal</option>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Persen</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configFisioBersamaPercent">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nominal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" class="form-control" id="configFisioBersamaNominal">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fisio-config-section">
                        <h6><i class="mdi mdi-account-check-outline"></i> Petugas Penerima</h6>
                        <div class="mb-3">
                            <label class="form-label">Petugas 1</label>
                            <select class="form-select" id="configFisioPetugas1"></select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Petugas 2</label>
                            <select class="form-select" id="configFisioPetugas2"></select>
                        </div>
                    </div>

                    <div class="fisio-config-section">
                        <h6><i class="mdi mdi-clipboard-text-outline"></i> Ringkasan</h6>
                        <div class="fisio-info-list" id="configFisioFormulaPreview"></div>
                    </div>
                </div>

                <div class="fisio-config-section">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h6 class="mb-0"><i class="mdi mdi-format-list-checks"></i> Daftar Tindakan dan Harga UMUM</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddFisioAction">
                            <i class="mdi mdi-plus"></i>
                            Tambah
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle fisio-action-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Kode</th>
                                    <th>Nama Tindakan</th>
                                    <th style="width: 170px;">Harga</th>
                                    <th style="width: 100px;">Aktif</th>
                                    <th style="width: 170px;">Catatan</th>
                                    <th style="width: 58px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="configFisioActionRows"></tbody>
                        </table>
                    </div>
                    <div class="fisio-empty-state mt-3 d-none" id="configFisioActionEmpty">
                        Belum ada tindakan aktif.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitConfigFisio">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailFisio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Detail Premi Fisio</h5>
                    <small class="text-muted">Audit sumber input dan pembagian nominal.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailFisioLoading" class="text-center py-5">
                    <span class="spinner-border text-success"></span>
                    <div class="text-muted mt-2">Memuat detail fisio...</div>
                </div>

                <div id="detailFisioContent" class="d-none">
                    <div class="fisio-simple-head">
                        <div class="fisio-simple-main">
                            <div class="fisio-simple-icon"><i class="mdi mdi-clipboard-text-search-outline"></i></div>
                            <div>
                                <div class="fisio-simple-title" id="detailFisioTitle">-</div>
                                <div class="fisio-simple-text" id="detailFisioMeta">-</div>
                            </div>
                        </div>
                        <span class="fisio-simple-badge" id="detailFisioStatusBadge">Status</span>
                    </div>

                    <div class="fisio-detail-grid mb-3" id="detailFisioStats"></div>

                    <div class="fisio-simple-section mb-3">
                        <div class="fisio-simple-section-title">Rincian Tindakan</div>
                        <div class="fisio-recipient-list mb-3" id="detailFisioSources"></div>
                    </div>

                    <div class="fisio-simple-section mb-3">
                        <div class="fisio-simple-section-title">Rumus Tersimpan</div>
                        <div class="fisio-info-list" id="detailFisioFormula"></div>
                    </div>

                    <div class="fisio-simple-section mb-0">
                        <div class="fisio-simple-section-title">Penerima Premi</div>
                        <div class="fisio-recipient-list" id="detailFisioRecipients"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
