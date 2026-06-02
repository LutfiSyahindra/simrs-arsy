<!-- Modal Master skor -->
<div class="modal fade" id="skorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content border-0 shadow-sm rounded-4">

            <!-- HEADER -->
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold">
                        Master skor
                    </h5>
                    <small class="text-muted">
                        Kelola skor dan tunjangan tetap
                    </small>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">

                <form id="skorForm">
                    @csrf

                    <!-- LIST -->
                    <div id="skorContainer" class="d-flex flex-column gap-3">

                        <!-- ITEM -->
                        <div class="card border-0 shadow-sm skor-item">

                            <div class="card-body py-3">

                                <div class="row g-3 align-items-end">

                                    <!-- KODE -->
                                    <div class="col-md-2">
                                        <label class="form-label">Kode</label>
                                        <input type="text" name="kode[]" class="form-control" placeholder="SKR001"
                                            readonly>
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label">Jenis skor</label>
                                        <input type="text" name="jenis[]" class="form-control"
                                            placeholder="Contoh: Profesi">
                                    </div>

                                    <!-- NAMA -->
                                    <div class="col-md-5">
                                        <label class="form-label">Nama skor</label>
                                        <input type="text" name="nama[]" class="form-control"
                                            placeholder="Contoh: Medis">
                                    </div>

                                    <!-- TUNJANGAN -->
                                    <div class="col-md-4">
                                        <label class="form-label">Bobot Skor</label>
                                        <input type="number" name="bobot[]" class="form-control"
                                            placeholder="Contoh: 30">
                                    </div>

                                    <input type="text" name="skorId" id="skorId" value="">

                                    <!-- REMOVE -->
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-light btn-icon removeRow">
                                            <i data-feather="x"></i>
                                        </button>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- ADD BUTTON -->
                    <div class="d-flex justify-content-between align-items-center mt-4">

                        <button type="button" id="addRow" class="btn btn-outline-primary">
                            <i data-feather="plus" class="me-1"></i>
                            Tambah skor
                        </button>

                        <small class="text-muted">
                            Tambahkan skor sesuai kebutuhan
                        </small>

                    </div>

                </form>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer border-0 pt-0">

                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit" form="skorForm" class="btn btn-primary">
                    <i data-feather="save" class="me-1"></i>
                    Simpan Data
                </button>

            </div>

        </div>

    </div>
</div>
