@push("style")
    <style>
        .unit-pegawai-picker {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }

        .unit-pegawai-picker-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 12px 14px;
        }

        .unit-pegawai-list {
            max-height: 360px;
            overflow: auto;
            padding: 10px;
            display: grid;
            gap: 8px;
        }

        .unit-pegawai-option {
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 10px 12px;
            cursor: pointer;
            transition: border-color .16s ease, background .16s ease;
        }

        .unit-pegawai-option:hover {
            border-color: #93c5fd;
            background: #f8fbff;
        }

        .unit-pegawai-option input {
            margin-top: 3px;
        }

        .unit-pegawai-name {
            color: #111827;
            font-weight: 700;
            word-break: break-word;
        }

        .unit-pegawai-meta {
            color: #6b7280;
            font-size: 12px;
        }

        .unit-pegawai-empty {
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            color: #6b7280;
            padding: 18px;
            text-align: center;
        }

        @media (max-width: 767.98px) {
            .unit-pegawai-picker-head {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
@endpush

<div class="modal fade" id="unitPegawaiModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold">
                        Unit Pegawai
                    </h5>
                    <small class="text-muted">
                        Pilih unit lalu centang pegawai yang masuk unit tersebut
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="unitPegawaiForm">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Jenis Unit</label>
                            <select id="jenisUnitSelect" class="form-select">
                                <option value="">-- Pilih Jenis Unit --</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Nama Unit</label>
                            <select name="unit_id" id="unitSelect" class="form-select">
                                <option value="">-- Pilih Nama Unit --</option>
                            </select>
                            <div class="invalid-feedback" id="error-unit_id"></div>
                        </div>
                    </div>

                    <div class="unit-pegawai-picker">
                        <div class="unit-pegawai-picker-head">
                            <div>
                                <small class="text-muted fw-semibold">PEGAWAI</small>
                                <div class="small text-muted">
                                    <span id="pegawaiCheckedCount">0</span> pegawai dipilih
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                <div class="input-group input-group-sm" style="width: min(260px, 100%);">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchPegawaiChecklist"
                                        class="form-control border-start-0" placeholder="Cari pegawai...">
                                </div>

                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkAllPegawai">
                                    <label class="form-check-label small fw-semibold" for="checkAllPegawai">
                                        Pilih semua
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="pegawaiChecklist" class="unit-pegawai-list">
                            <div class="unit-pegawai-empty">
                                Pilih unit untuk menampilkan pegawai.
                            </div>
                        </div>
                    </div>
                    <div class="invalid-feedback d-block" id="error-nik"></div>

                    <input type="hidden" id="unitPegawaiId">
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit" form="unitPegawaiForm" class="btn btn-primary">
                    <i data-feather="save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
