<div class="modal fade" id="potonganModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-sm rounded-4">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">
                        Master Jenis Potongan
                    </h5>
                    <small class="text-muted">
                        Atur rule potongan berdasarkan tipe perhitungan
                    </small>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-3">
                <form id="potonganForm">
                    @csrf
                    <input type="hidden" id="potonganId">

                    <div id="potonganContainer" class="d-flex flex-column gap-3"></div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="button" id="addRow" class="btn btn-light border">
                            <i data-feather="plus" class="me-1"></i>
                            Tambah
                        </button>

                        <small class="text-muted">
                            Manual diisi per pegawai, nominal dan persentase dihitung otomatis.
                        </small>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit" form="potonganForm" class="btn btn-dark">
                    <i data-feather="save" class="me-1"></i>
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>
