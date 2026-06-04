@push("style")
    <style>
        .tindakan-source-note {
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background: #f8fafc;
            color: #475569;
            padding: 12px;
            font-size: 12px;
        }

        .tindakan-source-option {
            display: grid;
            gap: 3px;
            padding: 4px 0;
        }

        .tindakan-source-title {
            color: #111827;
            font-weight: 700;
            line-height: 1.25;
            word-break: break-word;
        }

        .tindakan-source-meta {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.35;
        }

        .tindakan-source-badge {
            display: inline-flex;
            align-items: center;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
        }

        .tindakan-modal-shell {
            overflow: hidden;
        }

        .tindakan-modal-hero {
            align-items: flex-start;
            background: linear-gradient(135deg, #0f766e, #2563eb);
            color: #fff;
            padding: 20px 22px;
        }

        .tindakan-modal-hero .btn-close {
            filter: invert(1) grayscale(100%);
            opacity: .85;
        }

        .tindakan-hero-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .26);
            font-size: 24px;
            flex: 0 0 auto;
        }

        .tindakan-step-strip {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .tindakan-step-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 5px 10px;
        }

        .tindakan-side-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 14px;
            height: 100%;
        }

        .tindakan-side-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: #ecfeff;
            color: #0e7490;
            font-size: 20px;
            flex: 0 0 auto;
        }

        .tindakan-count-card {
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 12px;
        }

        .tindakan-count-number {
            font-size: 28px;
            line-height: 1;
            font-weight: 800;
        }

        .tindakan-source-picker {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
        }

        .tindakan-source-picker-head {
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 12px;
        }

        .tindakan-source-toolbar {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .tindakan-result-info {
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            white-space: nowrap;
        }

        .tindakan-select-all-row {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
        }

        .tindakan-select-all-row .form-check {
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            padding: 5px 12px 5px 34px;
        }

        .tindakan-select-all-row .form-check-input {
            margin-top: .18rem;
        }

        .tindakan-source-list {
            max-height: 380px;
            overflow: auto;
            padding: 12px;
            display: grid;
            gap: 8px;
        }

        .tindakan-check-option {
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 10px 12px;
            cursor: pointer;
            transition: border-color .16s ease, background .16s ease, box-shadow .16s ease, transform .16s ease;
        }

        .tindakan-check-option:hover {
            border-color: #93c5fd;
            background: #f8fbff;
            transform: translateY(-1px);
        }

        .tindakan-check-option.is-checked {
            border-color: #22c55e;
            background: #f0fdf4;
            box-shadow: 0 8px 18px rgba(34, 197, 94, .1);
        }

        .tindakan-check-option input {
            margin-top: 3px;
        }

        .tindakan-source-empty {
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            color: #6b7280;
            padding: 18px;
            text-align: center;
        }

        .tindakan-selected-preview {
            border-top: 1px solid #e5e7eb;
            background: #fff;
            max-height: 110px;
            overflow: auto;
            padding: 10px;
        }

        .tindakan-selected-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            background: #f0fdf4;
            color: #166534;
            font-size: 12px;
            font-weight: 600;
            margin: 2px;
            max-width: 100%;
            padding: 4px 8px;
        }

        .tindakan-selected-remove {
            border: 0;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            display: inline-grid;
            height: 18px;
            place-items: center;
            width: 18px;
        }

        .tindakan-selected-remove:hover {
            background: #bbf7d0;
        }

        .tindakan-inline-editor {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            box-shadow: 0 10px 22px rgba(37, 99, 235, .08);
        }

        .tindakan-inline-head {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .tindakan-inline-title {
            align-items: center;
            color: #1e3a8a;
            display: flex;
            font-weight: 800;
            gap: 8px;
        }

        .tindakan-inline-hint {
            color: #64748b;
            font-size: 12px;
        }

        @media (max-width: 767.98px) {
            .tindakan-modal-hero,
            .tindakan-source-toolbar,
            .tindakan-inline-head {
                align-items: stretch;
                flex-direction: column;
            }

            .tindakan-result-info {
                width: 100%;
                text-align: center;
            }

            .tindakan-select-all-row {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
@endpush

<div class="modal fade" id="tindakanModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 tindakan-modal-shell">
            <div class="modal-header border-0 tindakan-modal-hero">
                <div class="d-flex gap-3">
                    <div class="tindakan-hero-icon">
                        <i class="mdi mdi-medical-bag"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-semibold mb-1">
                            Mapping Tindakan
                        </h5>
                        <small style="color: rgba(255,255,255,.82);">
                            Pilih jenis tindakan dulu, lalu cari dan centang tindakan dari data Khanza.
                        </small>

                        <div class="tindakan-step-strip">
                            <span class="tindakan-step-pill">
                                <i class="mdi mdi-numeric-1-circle-outline"></i>
                                Pilih jenis
                            </span>
                            <span class="tindakan-step-pill">
                                <i class="mdi mdi-numeric-2-circle-outline"></i>
                                Search 3 huruf
                            </span>
                            <span class="tindakan-step-pill">
                                <i class="mdi mdi-numeric-3-circle-outline"></i>
                                Centang tindakan
                            </span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="tindakanForm">
                    @csrf

                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="tindakan-side-panel">
                                <div class="d-flex gap-2 mb-3">
                                    <div class="tindakan-side-icon">
                                        <i class="mdi mdi-format-list-bulleted-type"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">Jenis tindakan</div>
                                        <div class="small text-muted">Kategori tempat tindakan akan dipasang.</div>
                                    </div>
                                </div>

                                <label class="form-label">Jenis Tindakan</label>
                                <select name="jnsTindakan_id" id="jenisTindakanSelect" class="form-select">
                                    <option value="">-- Pilih Jenis Tindakan --</option>
                                </select>
                                <div class="invalid-feedback" id="error-jnsTindakan_id"></div>

                                <div class="tindakan-count-card mt-3">
                                    <div class="small fw-semibold">DIPILIH</div>
                                    <div class="tindakan-count-number">
                                        <span id="tindakanSelectedCount">0</span>
                                    </div>
                                    <div class="small">tindakan siap disimpan</div>
                                </div>

                                <div class="tindakan-source-note mt-3">
                                    <i class="mdi mdi-information-outline me-1"></i>
                                    Data penjamin diambil dari tabel penjab. Detail laboratorium akan membawa judul
                                    pemeriksaan besarnya.
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="tindakan-source-picker">
                                <div class="tindakan-source-picker-head">
                                    <div class="tindakan-source-toolbar">
                                        <div>
                                            <div class="fw-bold text-dark">Cari tindakan Khanza</div>
                                            <div class="small text-muted">Ketik minimal 3 huruf, lalu centang hasilnya.</div>
                                        </div>
                                        <span class="tindakan-result-info" id="sourceResultInfo">
                                            Menunggu jenis tindakan
                                        </span>
                                    </div>

                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="mdi mdi-magnify text-muted"></i>
                                        </span>
                                        <input type="text" id="searchTindakanSource"
                                            class="form-control border-start-0" placeholder="Ketik minimal 3 huruf..."
                                            disabled>
                                        <button type="button" class="btn btn-light border" id="clearSearchTindakanSource"
                                            disabled>
                                            <i class="mdi mdi-close"></i>
                                        </button>
                                    </div>

                                    <div class="tindakan-select-all-row">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" id="checkAllVisibleTindakan"
                                                disabled>
                                            <label class="form-check-label small fw-semibold"
                                                id="checkAllVisibleTindakanLabel" for="checkAllVisibleTindakan">
                                                Pilih semua yang tampil
                                            </label>
                                        </div>

                                        <span class="small text-muted">
                                            Berlaku untuk hasil search saat ini.
                                        </span>
                                    </div>
                                </div>

                                <div id="sourceTindakanChecklist" class="tindakan-source-list">
                                    <div class="tindakan-source-empty">
                                        Pilih jenis tindakan terlebih dahulu.
                                    </div>
                                </div>

                                <div id="selectedTindakanPreview" class="tindakan-selected-preview text-muted small">
                                    Belum ada tindakan dipilih.
                                </div>
                            </div>
                            <div class="invalid-feedback d-block" id="error-source_keys"></div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <div class="me-auto small text-muted">
                    Mapping akan disimpan ke tabel <span class="fw-semibold">mapping_tindakan</span>.
                </div>

                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit" form="tindakanForm" class="btn btn-primary">
                    <i data-feather="save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
