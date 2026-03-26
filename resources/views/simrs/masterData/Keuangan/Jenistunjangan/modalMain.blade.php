<!-- Modal Jenis Tunjangan -->
<div class="modal fade" id="tunjanganModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content border-0 shadow-sm rounded-4">

            <!-- HEADER -->
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">
                        Master Jenis Tunjangan
                    </h5>
                    <small class="text-muted">
                        Atur rule tunjangan berdasarkan tipe perhitungan
                    </small>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body pt-3">

                <form id="tunjanganForm">
                    @csrf

                    <!-- LIST -->
                    <div id="tunjanganContainer" class="d-flex flex-column gap-3">

                        <!-- ITEM -->
                        <div class="tunjangan-card p-3 rounded-4 border">

                            <div class="row g-3 align-items-end">

                                <!-- KODE -->
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Kode</label>
                                    <input type="text" name="kode[]" class="form-control form-control-sm bg-light"
                                        readonly>
                                </div>

                                <!-- NAMA -->
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Nama Tunjangan</label>
                                    <input type="text" name="nama[]" class="form-control form-control-sm"
                                        placeholder="Contoh: Tunjangan Anak">
                                </div>

                                <!-- TIPE -->
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Tipe</label>
                                    <select name="tipe[]" class="form-select form-select-sm tipeTunjangan">
                                        <option value="">Pilih</option>
                                        <option value="jabatan">Jabatan</option>
                                        <option value="profesi">Profesi</option>
                                        <option value="anak">Anak</option>
                                        <option value="pasangan">Suami/Istri</option>
                                        <option value="masa_kerja">Masa Kerja</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>

                                <!-- NILAI -->
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Nilai</label>
                                    <input type="number" name="nilai[]"
                                        class="form-control form-control-sm input-nilai" placeholder="Isi sesuai tipe">
                                </div>

                                <!-- REMOVE -->
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-sm btn-light removeRow">
                                        <i data-feather="trash-2"></i>
                                    </button>
                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- ADD BUTTON -->
                    <div class="d-flex justify-content-between align-items-center mt-4">

                        <button type="button" id="addRow" class="btn btn-light border">
                            <i data-feather="plus" class="me-1"></i>
                            Tambah
                        </button>

                        <small class="text-muted">
                            Gunakan tipe untuk menentukan cara perhitungan
                        </small>

                    </div>

                </form>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer border-0 pt-0">

                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit" form="tunjanganForm" class="btn btn-dark">
                    <i data-feather="save" class="me-1"></i>
                    Simpan
                </button>

            </div>

        </div>

    </div>
</div>
