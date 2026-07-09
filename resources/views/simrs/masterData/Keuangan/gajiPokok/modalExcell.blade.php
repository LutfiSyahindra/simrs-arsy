@push("style")
    <style>
        .gapok-excel-modal .modal-content {
            background: #f8fafc;
        }

        .gapok-excel-hero {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .96), rgba(30, 64, 175, .9)),
                linear-gradient(45deg, rgba(20, 184, 166, .18), rgba(249, 115, 22, .14));
        }

        .gapok-excel-icon {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .18);
            flex: 0 0 auto;
        }

        .gapok-excel-icon svg {
            width: 28px;
            height: 28px;
        }

        .gapok-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .gapok-meta-item {
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .1);
            border-radius: 8px;
            padding: 10px 12px;
        }

        .gapok-step {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            height: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 14px;
        }

        .gapok-step-number {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .gapok-step.is-template .gapok-step-number {
            background: #e0f2fe;
            color: #0369a1;
        }

        .gapok-step.is-fill .gapok-step-number {
            background: #fef3c7;
            color: #92400e;
        }

        .gapok-step.is-upload .gapok-step-number {
            background: #dcfce7;
            color: #166534;
        }

        .gapok-action-panel,
        .gapok-info-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .gapok-template-panel {
            background: linear-gradient(135deg, #fff, #f0fdfa);
        }

        .gapok-excel-modal .dropify-wrapper {
            min-height: 188px;
            height: 188px;
            border: 2px dashed #60a5fa;
            border-radius: 8px;
            background: #eff6ff;
            transition: border-color .2s ease, background .2s ease;
        }

        .gapok-excel-modal .dropify-wrapper:hover {
            border-color: #2563eb;
            background: #dbeafe;
        }

        .gapok-excel-modal .dropify-wrapper .dropify-message p {
            font-size: 15px;
            color: #1f2937;
            font-weight: 700;
        }

        .gapok-excel-modal .dropify-wrapper .dropify-message span.file-icon {
            color: #2563eb;
        }

        .gapok-rule-list {
            display: grid;
            gap: 10px;
        }

        .gapok-rule-item {
            display: grid;
            grid-template-columns: 32px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px;
        }

        .gapok-rule-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: #ecfdf5;
            color: #047857;
        }

        .gapok-note-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .gapok-note-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            padding: 14px;
        }

        .gapok-note-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            margin-bottom: 10px;
        }

        .gapok-note-add {
            background: #dcfce7;
            color: #166534;
        }

        .gapok-note-update {
            background: #e0f2fe;
            color: #0369a1;
        }

        .gapok-note-skip {
            background: #fef3c7;
            color: #92400e;
        }

        .gapok-example-table th,
        .gapok-example-table td {
            white-space: nowrap;
        }

        .gapok-required-cell {
            background: #fff7ed !important;
            color: #9a3412;
            font-weight: 800;
        }

        .gapok-soft-badge {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 767.98px) {
            .gapok-excel-modal .modal-body {
                padding-left: 18px !important;
                padding-right: 18px !important;
            }

            .gapok-meta-grid,
            .gapok-note-grid {
                grid-template-columns: 1fr;
            }

            .gapok-excel-hero .d-flex,
            .gapok-action-panel .d-flex,
            .gapok-excel-footer {
                align-items: stretch !important;
                flex-direction: column;
            }

            .gapok-excel-footer .btn {
                width: 100%;
            }
        }
    </style>
@endpush

<!-- Modal Upload Excel Gaji Pokok / Upah STR -->
<div class="modal fade gapok-excel-modal" id="gapokModalExcell" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <!-- HEADER -->
            <div class="gapok-excel-hero px-4 px-md-5 py-4 text-white">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="gapok-excel-icon">
                            <i data-feather="file-text"></i>
                        </div>

                        <div>
                            <div class="text-uppercase small fw-semibold opacity-75 mb-1">Import Excel</div>
                            <h4 class="fw-bold mb-1">Upload Komponen Gaji</h4>
                            <p class="mb-0 opacity-75">
                                Masukkan data gaji pokok pegawai dengan format template resmi sistem.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>

                <div class="gapok-meta-grid mt-4">
                    <div class="gapok-meta-item">
                        <div class="small opacity-75">Format file</div>
                        <div class="fw-bold">XLS / XLSX</div>
                    </div>
                    <div class="gapok-meta-item">
                        <div class="small opacity-75">Ukuran maksimal</div>
                        <div class="fw-bold">5 MB</div>
                    </div>
                    <div class="gapok-meta-item">
                        <div class="small opacity-75">Kolom wajib</div>
                        <div class="fw-bold">NIK & Komponen Gaji</div>
                    </div>
                </div>
            </div>

            <div class="modal-body px-5 py-4">

                <form id="gapokExcellForm" enctype="multipart/form-data">
                    @csrf

                    <!-- STEP -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="gapok-step is-template">
                                <div class="gapok-step-number">1</div>
                                <div>
                                    <div class="fw-bold">Download Template</div>
                                    <small class="text-muted">Gunakan file resmi agar struktur kolom tetap valid.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="gapok-step is-fill">
                                <div class="gapok-step-number">2</div>
                                <div>
                                    <div class="fw-bold">Isi Gaji Pokok / Upah STR</div>
                                    <small class="text-muted">Lengkapi nominal gaji pada baris pegawai yang valid.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="gapok-step is-upload">
                                <div class="gapok-step-number">3</div>
                                <div>
                                    <div class="fw-bold">Upload File</div>
                                    <small class="text-muted">Sistem membaca NIK lalu menyimpan nominal gaji pokok.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DOWNLOAD + UPLOAD -->
                    <div class="row g-3 mb-4">
                        <div class="col-lg-5">
                            <div class="gapok-action-panel gapok-template-panel h-100 p-4">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge gapok-soft-badge">
                                                Disarankan
                                            </span>
                                            <span class="small text-muted">Template terbaru</span>
                                        </div>

                                        <h6 class="fw-bold mb-1">Gunakan Template Resmi</h6>
                                        <p class="small text-muted mb-3">
                                            Jangan mengubah nama kolom. Isi hanya NIK pegawai dan nominal gaji pokok.
                                        </p>
                                    </div>
                                </div>

                                <button type="button" id="downloadTemplateBtn"
                                    class="btn btn-dark px-3 shadow-sm w-100">
                                    <i data-feather="download" class="me-1"></i>
                                    Download Template
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="gapok-action-panel h-100 p-4">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">Upload File Excel</h6>
                                        <small class="text-muted">Pilih file .xls atau .xlsx yang sudah diisi.</small>
                                    </div>
                                    <i data-feather="upload-cloud" class="text-primary"></i>
                                </div>

                                <input type="file" id="myDropify" name="file" class="dropify"
                                    accept=".xls,.xlsx" data-height="188" data-max-file-size="5M"
                                    data-allowed-file-extensions="xls xlsx"
                                    data-messages-default="Klik atau drag file Excel ke sini"
                                    data-messages-replace="Klik atau drag untuk mengganti file"
                                    data-messages-remove="Hapus" data-messages-error="File tidak valid" />

                                <div class="small text-primary fw-semibold mt-2">
                                    File akan divalidasi saat tombol Upload Data ditekan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INFO -->
                    <div class="row g-3 mb-4">

                        <!-- PANDUAN -->
                        <div class="col-lg-5">
                            <div class="gapok-info-panel h-100 p-4">

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i data-feather="check-circle" class="text-success"></i>
                                    <h6 class="fw-bold mb-0 text-dark">Panduan Pengisian</h6>
                                </div>

                                <div class="gapok-rule-list">
                                    <div class="gapok-rule-item">
                                        <div class="gapok-rule-icon">
                                            <i data-feather="users"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Pastikan NIK terdaftar</div>
                                            <small class="text-muted">
                                                Sistem mencocokkan pegawai berdasarkan NIK pada file Excel.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="gapok-rule-item">
                                        <div class="gapok-rule-icon">
                                            <i data-feather="dollar-sign"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Isi nominal Gaji Pokok / Upah STR</div>
                                            <small class="text-muted">
                                                Gunakan angka nominal tanpa simbol mata uang.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="gapok-rule-item">
                                        <div class="gapok-rule-icon">
                                            <i data-feather="columns"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Jangan ubah struktur kolom</div>
                                            <small class="text-muted">
                                                Nama dan urutan kolom template harus tetap sesuai sistem.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning small mb-0 mt-3">
                                    <strong>Penting:</strong> file di atas 5 MB atau selain .xls/.xlsx akan ditolak.
                                </div>

                            </div>
                        </div>

                        <!-- CATATAN -->
                        <div class="col-lg-7">
                            <div class="gapok-info-panel h-100 p-4">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i data-feather="list" class="text-primary"></i>
                                    <h6 class="fw-bold mb-0 text-dark">Status Import</h6>
                                </div>

                                <div class="gapok-note-grid">
                                    <div class="gapok-note-item">
                                        <div class="gapok-note-icon gapok-note-add">
                                            <i data-feather="plus"></i>
                                        </div>
                                        <div class="fw-bold">Ditambahkan</div>
                                        <small class="text-muted">
                                            Data gaji baru akan dibuat saat NIK valid dan belum memiliki gaji pokok.
                                        </small>
                                    </div>

                                    <div class="gapok-note-item">
                                        <div class="gapok-note-icon gapok-note-update">
                                            <i data-feather="refresh-cw"></i>
                                        </div>
                                        <div class="fw-bold">Diperbarui</div>
                                        <small class="text-muted">
                                            Nominal lama akan diperbarui jika pegawai sudah memiliki gaji pokok.
                                        </small>
                                    </div>

                                    <div class="gapok-note-item">
                                        <div class="gapok-note-icon gapok-note-skip">
                                            <i data-feather="alert-triangle"></i>
                                        </div>
                                        <div class="fw-bold">Dilewati</div>
                                        <small class="text-muted">
                                            Baris dengan NIK tidak valid atau nominal kosong tidak ikut diproses.
                                        </small>
                                    </div>
                                </div>

                                <div class="alert alert-info small mb-0 mt-3">
                                    Hasil import akan menampilkan jumlah data ditambahkan, diperbarui, dan dilewati.
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- CONTOH -->
                    <div class="gapok-info-panel p-4 mb-4">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i data-feather="grid" class="text-success"></i>
                                <h6 class="fw-bold mb-0 text-dark">Contoh Isi Excel</h6>
                            </div>
                            <span class="small text-muted">Isi nominal gaji pokok sesuai template.</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle mb-0 gapok-example-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Status</th>
                                        <th>Masa Kerja</th>
                                        <th>Gaji Pokok / Upah STR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Perawat</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="gapok-required-cell">3500000</td>
                                    </tr>
                                    <tr>
                                        <td>123457</td>
                                        <td>Siti</td>
                                        <td>Bidan</td>
                                        <td>FT</td>
                                        <td>3 Tahun</td>
                                        <td class="gapok-required-cell">2750000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="gapok-excel-footer d-flex justify-content-between gap-2">
                        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                            Tutup
                        </button>

                        <button type="button" id="submitFormExcell" class="btn btn-dark px-4 shadow">
                            <i data-feather="upload" class="me-1"></i>
                            Upload Data
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>
