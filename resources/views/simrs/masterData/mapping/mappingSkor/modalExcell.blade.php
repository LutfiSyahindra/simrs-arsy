@push("style")
    <style>
        .skoring-excel-modal .modal-content {
            background: #f8fafc;
        }

        .skoring-excel-hero {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .96), rgba(30, 64, 175, .9)),
                linear-gradient(45deg, rgba(20, 184, 166, .18), rgba(249, 115, 22, .14));
        }

        .skoring-excel-icon {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .18);
            flex: 0 0 auto;
        }

        .skoring-excel-icon svg {
            width: 28px;
            height: 28px;
        }

        .skoring-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .skoring-meta-item {
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .1);
            border-radius: 8px;
            padding: 10px 12px;
        }

        .skoring-step {
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

        .skoring-step-number {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .skoring-step.is-template .skoring-step-number {
            background: #e0f2fe;
            color: #0369a1;
        }

        .skoring-step.is-fill .skoring-step-number {
            background: #fef3c7;
            color: #92400e;
        }

        .skoring-step.is-upload .skoring-step-number {
            background: #dcfce7;
            color: #166534;
        }

        .skoring-action-panel,
        .skoring-info-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .skoring-template-panel {
            background: linear-gradient(135deg, #fff, #f0fdfa);
        }

        .skoring-excel-modal .dropify-wrapper {
            min-height: 188px;
            height: 188px;
            border: 2px dashed #60a5fa;
            border-radius: 8px;
            background: #eff6ff;
            transition: border-color .2s ease, background .2s ease;
        }

        .skoring-excel-modal .dropify-wrapper:hover {
            border-color: #2563eb;
            background: #dbeafe;
        }

        .skoring-excel-modal .dropify-wrapper .dropify-message p {
            font-size: 15px;
            color: #1f2937;
            font-weight: 700;
        }

        .skoring-excel-modal .dropify-wrapper .dropify-message span.file-icon {
            color: #2563eb;
        }

        .skoring-rule-list {
            display: grid;
            gap: 10px;
        }

        .skoring-rule-item {
            display: grid;
            grid-template-columns: 32px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px;
        }

        .skoring-rule-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: #ecfdf5;
            color: #047857;
        }

        .skoring-reference-table {
            max-height: 370px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
        }

        .skoring-reference-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8fafc;
        }

        .skoring-code-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 68px;
            border-radius: 8px;
            background: #eef2ff;
            color: #3730a3;
            font-weight: 800;
            padding: 4px 8px;
        }

        .skoring-example-table th,
        .skoring-example-table td {
            white-space: nowrap;
        }

        .skoring-required-cell {
            background: #fff7ed !important;
            color: #9a3412;
            font-weight: 800;
        }

        .skoring-soft-badge {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 767.98px) {
            .skoring-excel-modal .modal-body {
                padding-left: 18px !important;
                padding-right: 18px !important;
            }

            .skoring-meta-grid {
                grid-template-columns: 1fr;
            }

            .skoring-excel-hero .d-flex,
            .skoring-action-panel .d-flex,
            .skoring-excel-footer {
                align-items: stretch !important;
                flex-direction: column;
            }

            .skoring-excel-footer .btn {
                width: 100%;
            }
        }
    </style>
@endpush

<!-- Modal Upload Excel Skoring Pegawai -->
<div class="modal fade skoring-excel-modal" id="skoringPegawaiModalExcell" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <!-- HEADER -->
            <div class="skoring-excel-hero px-4 px-md-5 py-4 text-white">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="skoring-excel-icon">
                            <i data-feather="file-text"></i>
                        </div>

                        <div>
                            <div class="text-uppercase small fw-semibold opacity-75 mb-1">Import Excel</div>
                            <h4 class="fw-bold mb-1">Upload Skoring Pegawai</h4>
                            <p class="mb-0 opacity-75">
                                Masukkan data skoring pegawai dengan format template resmi sistem.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>

                <div class="skoring-meta-grid mt-4">
                    <div class="skoring-meta-item">
                        <div class="small opacity-75">Format file</div>
                        <div class="fw-bold">XLS / XLSX</div>
                    </div>
                    <div class="skoring-meta-item">
                        <div class="small opacity-75">Ukuran maksimal</div>
                        <div class="fw-bold">5 MB</div>
                    </div>
                    <div class="skoring-meta-item">
                        <div class="small opacity-75">Kolom wajib</div>
                        <div class="fw-bold">NIK & Skoring_kode</div>
                    </div>
                </div>
            </div>

            <div class="modal-body px-5 py-4">

                <form id="skoringPegawaiExcellForm" enctype="multipart/form-data">
                    @csrf

                    <!-- STEP -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="skoring-step is-template">
                                <div class="skoring-step-number">1</div>
                                <div>
                                    <div class="fw-bold">Download Template</div>
                                    <small class="text-muted">Gunakan file resmi agar struktur kolom tetap valid.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="skoring-step is-fill">
                                <div class="skoring-step-number">2</div>
                                <div>
                                    <div class="fw-bold">Isi Data Skoring</div>
                                    <small class="text-muted">Satu baris mewakili satu kode skor untuk satu pegawai.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="skoring-step is-upload">
                                <div class="skoring-step-number">3</div>
                                <div>
                                    <div class="fw-bold">Upload File</div>
                                    <small class="text-muted">Sistem membaca kode skor lalu mengonversinya ke ID.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DOWNLOAD + UPLOAD -->
                    <div class="row g-3 mb-4">
                        <div class="col-lg-5">
                            <div class="skoring-action-panel skoring-template-panel h-100 p-4">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge skoring-soft-badge">
                                                Disarankan
                                            </span>
                                            <span class="small text-muted">Template terbaru</span>
                                        </div>

                                        <h6 class="fw-bold mb-1">Gunakan Template Resmi</h6>
                                        <p class="small text-muted mb-3">
                                            Jangan mengubah nama kolom. Isi hanya data pegawai dan kode skor yang tersedia.
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
                            <div class="skoring-action-panel h-100 p-4">
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
                            <div class="skoring-info-panel h-100 p-4">

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i data-feather="check-circle" class="text-success"></i>
                                    <h6 class="fw-bold mb-0 text-dark">Panduan Pengisian</h6>
                                </div>

                                <div class="skoring-rule-list">
                                    <div class="skoring-rule-item">
                                        <div class="skoring-rule-icon">
                                            <i data-feather="users"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Pegawai dengan banyak skor</div>
                                            <small class="text-muted">
                                                Buat beberapa baris dengan NIK yang sama.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="skoring-rule-item">
                                        <div class="skoring-rule-icon">
                                            <i data-feather="hash"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Isi kolom Skoring_kode</div>
                                            <small class="text-muted">
                                                Gunakan kode seperti SKR001 atau SKR002, bukan ID database.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="skoring-rule-item">
                                        <div class="skoring-rule-icon">
                                            <i data-feather="alert-triangle"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Kode kosong dilewati</div>
                                            <small class="text-muted">
                                                Baris tanpa Skoring_kode tidak akan ikut diimport.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning small mb-0 mt-3">
                                    <strong>Penting:</strong> pastikan kode skor tersedia pada tabel referensi di samping
                                    sebelum upload.
                                </div>

                            </div>
                        </div>

                        <!-- REFERENSI -->
                        <div class="col-lg-7">
                            <div class="skoring-info-panel h-100 p-4">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i data-feather="list" class="text-primary"></i>
                                        <h6 class="fw-bold mb-0 text-dark">Referensi Skoring</h6>
                                    </div>
                                    <span class="badge bg-light text-secondary border">
                                        {{ count($guide) }} kode
                                    </span>
                                </div>

                                <div class="skoring-reference-table">
                                    <table class="table table-sm table-hover align-middle text-center mb-0">
                                        <thead>
                                            <tr>
                                                <th>Kode</th>
                                                <th>Jenis</th>
                                                <th class="text-start">Keterangan</th>
                                                <th>Bobot</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($guide as $s)
                                                <tr>
                                                    <td>
                                                        <span class="skoring-code-pill">{{ $s["kd_skor"] }}</span>
                                                    </td>
                                                    <td>{{ $s["jenis"] }}</td>
                                                    <td class="text-start">{{ $s["keterangan"] }}</td>
                                                    <td class="fw-bold text-success">{{ $s["bobot_skor"] }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-muted py-4">
                                                        Referensi skoring belum tersedia.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- CONTOH -->
                    <div class="skoring-info-panel p-4 mb-4">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i data-feather="grid" class="text-success"></i>
                                <h6 class="fw-bold mb-0 text-dark">Contoh Isi Excel</h6>
                            </div>
                            <span class="small text-muted">Ulangi NIK untuk setiap skor tambahan.</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle mb-0 skoring-example-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Status</th>
                                        <th>Masa Kerja</th>
                                        <th>Skoring_kode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="skoring-required-cell">SKR001</td>
                                    </tr>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="skoring-required-cell">SKR002</td>
                                    </tr>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="skoring-required-cell">SKR003</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="skoring-excel-footer d-flex justify-content-between gap-2">
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
