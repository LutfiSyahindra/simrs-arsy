<div class="modal fade" id="plotingPremiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-sm rounded-4">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold" id="plotingPremiModalLabel">
                        Master ploting premi
                    </h5>
                    <small class="text-muted">
                        Kelola ploting premi dan klasifikasi data
                    </small>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="plotingPremiForm">
                    @csrf
                    <input type="hidden" name="plotingPremiId" id="plotingPremiId">

                    <div id="plotingPremiContainer" class="d-flex flex-column gap-3"></div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="button" id="addPlotingRow" class="btn btn-outline-primary">
                            <i data-feather="plus" class="me-1"></i>
                            Tambah Ploting Premi
                        </button>

                        <small class="text-muted">
                            Tambahkan ploting premi sesuai kebutuhan
                        </small>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit" form="plotingPremiForm" id="submitPlotingPremi" class="btn btn-primary">
                    <i data-feather="save" class="me-1"></i>
                    Simpan Data
                </button>
            </div>
        </div>
    </div>
</div>
