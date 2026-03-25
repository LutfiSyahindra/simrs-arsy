<!-- Modal Upload Excel Tunjangan Pegawai -->
<div class="modal fade" id="tunjanganPegawaiModalExcell" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <!-- HEADER -->
            <div class="px-4 py-4 text-white" style="background: linear-gradient(135deg, #1e293b, #334155);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4 class="fw-bold mb-1">Upload Tunjangan Pegawai</h4>
                        <small class="opacity-75">
                            Import data tunjangan pegawai dengan format Excel yang sudah ditentukan
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body px-5 py-4">

                <form id="tunjanganPegawaiExcellForm" enctype="multipart/form-data">
                    @csrf

                    <!-- STEP INDICATOR -->
                    <div class="d-flex align-items-center justify-content-between mb-4">

                        <div class="text-center flex-fill">
                            <div class="badge bg-dark rounded-circle p-2 mb-1">1</div>
                            <div class="small fw-semibold">Download Template</div>
                        </div>

                        <div class="flex-grow-1 border-top mx-2"></div>

                        <div class="text-center flex-fill">
                            <div class="badge bg-secondary rounded-circle p-2 mb-1">2</div>
                            <div class="small fw-semibold">Isi Data</div>
                        </div>

                        <div class="flex-grow-1 border-top mx-2"></div>

                        <div class="text-center flex-fill">
                            <div class="badge bg-primary rounded-circle p-2 mb-1">3</div>
                            <div class="small fw-semibold">Upload File</div>
                        </div>

                    </div>

                    <!-- DOWNLOAD -->
                    <div class="mb-4 p-4 rounded-4 border bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">Gunakan Template Resmi</div>
                            <small class="text-muted">
                                Format sudah sesuai sistem, tidak perlu modifikasi
                            </small>
                        </div>

                        <button type="button" id="downloadTemplateBtn" class="btn btn-dark px-3 shadow-sm">
                            <i data-feather="download"></i> Download Template
                        </button>
                    </div>

                    <!-- UPLOAD AREA -->
                    <div class="mb-4">

                        <div class="border border-2 border-dashed rounded-4 p-5 text-center position-relative"
                            style="background: #f8fbff; transition: all .3s;">

                            <input type="file" id="myDropify" name="file"
                                class="position-absolute w-100 h-100 top-0 start-0 opacity-0" style="cursor:pointer;"
                                accept=".xls,.xlsx" />

                            <div>
                                <i data-feather="upload-cloud" style="width:48px;height:48px;"
                                    class="text-primary mb-3"></i>

                                <h6 class="fw-semibold mb-1">
                                    Klik atau Drag File ke sini
                                </h6>

                                <small class="text-muted">
                                    XLS / XLSX • Maksimal 5MB
                                </small>
                            </div>

                        </div>

                    </div>

                    <!-- INFO GRID -->
                    <div class="row g-3 mb-4">

                        <!-- PANDUAN -->
                        <div class="col-md-6">
                            <div class="p-4 border rounded-4 h-100 bg-white shadow-sm">

                                <h6 class="fw-bold mb-3 text-dark">
                                    Panduan Pengisian
                                </h6>

                                <!-- ALERT -->
                                <div class="alert alert-info small">
                                    <strong>Catatan:</strong>
                                    Jika satu pegawai memiliki beberapa tunjangan,
                                    silakan isi dalam beberapa baris dengan <b>NIK yang sama</b>.
                                </div>

                                <ul class="small mb-0 ps-3">
                                    <li>Template sudah berisi <b>NIK & Nama pegawai</b></li>
                                    <li>Isi hanya kolom <b>Tunjangan_id</b></b></li>
                                    <li>Gunakan kode sesuai referensi (contoh: <b>TJ001</b>)</li>
                                    <li>Jangan mengubah urutan atau nama kolom</li>
                                    <li>Satu baris hanya untuk <b>1 jenis tunjangan</b></li>
                                </ul>

                            </div>
                        </div>

                        <!-- REFERENSI -->
                        <div class="col-md-6">
                            <div class="p-4 border rounded-4 h-100 bg-light">

                                <h6 class="fw-bold mb-3 text-success">
                                    Referensi Tunjangan
                                </h6>

                                <div class="table-responsive" style="max-height:200px;">
                                    <table class="table table-sm table-hover text-center mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kode</th>
                                                <th>Tunjangan</th>
                                                <th>Persentase (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="refTunjanganTable"></tbody>
                                    </table>
                                </div>

                                <small class="text-muted">
                                    Gunakan <b>kode (contoh: TJ001)</b> di Excel
                                </small>

                            </div>
                        </div>

                    </div>

                    <!-- CONTOH -->
                    <div class="mb-4">
                        <div class="p-4 border rounded-4 shadow-sm bg-white">

                            <h6 class="fw-bold mb-3 text-success">
                                Contoh Format Excel
                            </h6>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm text-center align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>NIK</th>
                                            <th>Nama</th>
                                            <th>Jabatan</th>
                                            <th>Status Kerja</th>
                                            <th>Masa Kerja</th>
                                            <th>Tunjangan_id</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr>
                                            <td>123456</td>
                                            <td>Ahmad</td>
                                            <td>Kepala Ruang</td>
                                            <td>T</td>
                                            <td>FT>1</td>
                                            <td class="bg-warning fw-bold">TJ001</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <small class="text-muted">
                                Sistem akan otomatis mencocokkan <b>kode tunjangan</b> ke database
                            </small>

                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="d-flex justify-content-between align-items-center">

                        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                            Tutup
                        </button>

                        <button type="button" id="submitFormExcell" class="btn btn-dark px-4 shadow">
                            <i data-feather="upload"></i> Upload Data
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>
</div>
