@push("style")
    <style>
        .unit-excel-modal .modal-content {
            background: #f8fafc;
        }

        .unit-excel-hero {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .96), rgba(37, 99, 235, .88)),
                linear-gradient(45deg, rgba(20, 184, 166, .18), rgba(245, 158, 11, .14));
        }

        .unit-excel-icon {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .18);
            flex: 0 0 auto;
        }

        .unit-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .unit-meta-item,
        .unit-step,
        .unit-action-panel,
        .unit-info-panel {
            border-radius: 8px;
        }

        .unit-meta-item {
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .1);
            padding: 10px 12px;
        }

        .unit-step {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            height: 100%;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 14px;
        }

        .unit-step-number {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            font-weight: 800;
            background: #e0f2fe;
            color: #0369a1;
        }

        .unit-action-panel,
        .unit-info-panel {
            border: 1px solid #e5e7eb;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .unit-template-panel {
            background: linear-gradient(135deg, #fff, #f0fdfa);
        }

        .unit-excel-modal .dropify-wrapper {
            min-height: 188px;
            height: 188px;
            border: 2px dashed #60a5fa;
            border-radius: 8px;
            background: #eff6ff;
        }

        .unit-reference-table {
            max-height: 370px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
        }

        .unit-reference-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8fafc;
        }

        .unit-code-pill {
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

        .unit-required-cell {
            background: #fff7ed !important;
            color: #9a3412;
            font-weight: 800;
        }

        @media (max-width: 767.98px) {
            .unit-excel-modal .modal-body {
                padding-left: 18px !important;
                padding-right: 18px !important;
            }

            .unit-meta-grid {
                grid-template-columns: 1fr;
            }

            .unit-excel-hero .d-flex,
            .unit-action-panel .d-flex,
            .unit-excel-footer {
                align-items: stretch !important;
                flex-direction: column;
            }

            .unit-excel-footer .btn {
                width: 100%;
            }
        }
    </style>
@endpush

<div class="modal fade unit-excel-modal" id="unitPegawaiModalExcell" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="unit-excel-hero px-4 px-md-5 py-4 text-white">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="unit-excel-icon">
                            <i data-feather="file-text"></i>
                        </div>

                        <div>
                            <div class="text-uppercase small fw-semibold opacity-75 mb-1">Import Excel</div>
                            <h4 class="fw-bold mb-1">Upload Unit Pegawai</h4>
                            <p class="mb-0 opacity-75">
                                Masukkan mapping unit pegawai dengan format template resmi sistem.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>

                <div class="unit-meta-grid mt-4">
                    <div class="unit-meta-item">
                        <div class="small opacity-75">Format file</div>
                        <div class="fw-bold">XLS / XLSX</div>
                    </div>
                    <div class="unit-meta-item">
                        <div class="small opacity-75">Ukuran maksimal</div>
                        <div class="fw-bold">5 MB</div>
                    </div>
                    <div class="unit-meta-item">
                        <div class="small opacity-75">Kolom wajib</div>
                        <div class="fw-bold">NIK & Unit_kode</div>
                    </div>
                </div>
            </div>

            <div class="modal-body px-5 py-4">
                <form id="unitPegawaiExcellForm" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="unit-step">
                                <div class="unit-step-number">1</div>
                                <div>
                                    <div class="fw-bold">Download Template</div>
                                    <small class="text-muted">Gunakan struktur kolom resmi.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="unit-step">
                                <div class="unit-step-number">2</div>
                                <div>
                                    <div class="fw-bold">Isi Kode Unit</div>
                                    <small class="text-muted">Satu baris mewakili satu unit untuk satu pegawai.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="unit-step">
                                <div class="unit-step-number">3</div>
                                <div>
                                    <div class="fw-bold">Upload File</div>
                                    <small class="text-muted">Sistem membaca kode unit dari master unit.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-lg-5">
                            <div class="unit-action-panel unit-template-panel h-100 p-4">
                                <h6 class="fw-bold mb-1">Gunakan Template Resmi</h6>
                                <p class="small text-muted mb-3">
                                    Jangan mengubah nama kolom. Isi kolom Unit_kode sesuai referensi unit.
                                </p>

                                <button type="button" id="downloadTemplateUnitBtn"
                                    class="btn btn-dark px-3 shadow-sm w-100">
                                    <i data-feather="download" class="me-1"></i>
                                    Download Template
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="unit-action-panel h-100 p-4">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">Upload File Excel</h6>
                                        <small class="text-muted">Pilih file .xls atau .xlsx yang sudah diisi.</small>
                                    </div>
                                    <i data-feather="upload-cloud" class="text-primary"></i>
                                </div>

                                <input type="file" id="unitDropify" name="file" class="dropify"
                                    accept=".xls,.xlsx" data-height="188" data-max-file-size="5M"
                                    data-allowed-file-extensions="xls xlsx"
                                    data-messages-default="Klik atau drag file Excel ke sini"
                                    data-messages-replace="Klik atau drag untuk mengganti file"
                                    data-messages-remove="Hapus" data-messages-error="File tidak valid" />
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-lg-5">
                            <div class="unit-info-panel h-100 p-4">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i data-feather="check-circle" class="text-success"></i>
                                    <h6 class="fw-bold mb-0 text-dark">Panduan Pengisian</h6>
                                </div>

                                <div class="alert alert-info small mb-3">
                                    Pakai kode dari tabel referensi master unit di sebelah kanan.
                                </div>

                                <div class="alert alert-warning small mb-0">
                                    Baris dengan NIK tidak valid, kode unit kosong, kode unit tidak ditemukan, atau
                                    mapping duplikat akan dilewati.
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="unit-info-panel h-100 p-4">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i data-feather="list" class="text-primary"></i>
                                        <h6 class="fw-bold mb-0 text-dark">Referensi Unit</h6>
                                    </div>
                                    <span class="badge bg-light text-secondary border">
                                        {{ count($guide) }} kode
                                    </span>
                                </div>

                                <div class="unit-reference-table">
                                    <table class="table table-sm table-hover align-middle text-center mb-0">
                                        <thead>
                                            <tr>
                                                <th>Kode</th>
                                                <th>Jenis</th>
                                                <th class="text-start">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($guide as $unit)
                                                <tr>
                                                    <td>
                                                        <span class="unit-code-pill">{{ $unit["kode"] }}</span>
                                                    </td>
                                                    <td>{{ $unit["jenis"] }}</td>
                                                    <td class="text-start">{{ $unit["keterangan"] }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-muted py-4">
                                                        Referensi unit belum tersedia.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="unit-info-panel p-4 mb-4">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i data-feather="grid" class="text-success"></i>
                                <h6 class="fw-bold mb-0 text-dark">Contoh Isi Excel</h6>
                            </div>
                            <span class="small text-muted">Ulangi NIK untuk setiap unit tambahan.</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Status</th>
                                        <th>Masa Kerja</th>
                                        <th>Unit_kode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="unit-required-cell">UNT001</td>
                                    </tr>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="unit-required-cell">UNT002</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="unit-excel-footer d-flex justify-content-between gap-2">
                        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                            Tutup
                        </button>

                        <button type="button" id="submitFormUnitExcell" class="btn btn-dark px-4 shadow">
                            <i data-feather="upload" class="me-1"></i>
                            Upload Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
