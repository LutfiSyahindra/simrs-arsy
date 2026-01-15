<div class="modal fade" id="info-header-modal" tabindex="-1" aria-labelledby="infoHeaderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

            {{-- MODAL HEADER --}}
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2" id="infoHeaderModalLabel">
                    <i class="ri-hospital-line text-primary fs-5"></i>
                    Pilih Poli & Dokter
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- MODAL BODY --}}
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Poli</label>
                    <select id="select-poli" class="form-select select2" data-width="100%">
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Dokter</label>
                    <select id="select-dokter" class="form-select select2" data-width="100%">
                    </select>
                </div>

            </div>

            {{-- MODAL FOOTER --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>
