<!-- Modal Upload Excel Gaji Pokok -->
<div class="modal fade" id="gapokModalExcell" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <!-- HEADER -->
            <div class="px-4 py-4 text-white" style="background: linear-gradient(135deg, #0d6efd, #5aa2ff);">

                <div class="d-flex justify-content-between align-items-start">

                    <div>
                        <h4 class="fw-bold mb-1">
                            Upload Gaji Pokok
                        </h4>
                        <small class="opacity-75">
                            Import data gaji pegawai dengan template Excel resmi
                        </small>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>

                </div>

            </div>

            <div class="modal-body px-5 py-4">

                <form id="gapokExcellForm" enctype="multipart/form-data">
                    @csrf

                    <!-- STEP INDICATOR -->
                    <div class="d-flex align-items-center gap-3 mb-4">

                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2">1</span>
                            <small class="fw-semibold">Download Template</small>
                        </div>

                        <div class="flex-grow-1 border-top"></div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary rounded-circle p-2">2</span>
                            <small class="fw-semibold">Upload File</small>
                        </div>

                    </div>

                    <!-- DOWNLOAD -->
                    <div class="mb-4 p-4 rounded-4 border bg-light d-flex justify-content-between align-items-center">

                        <div>
                            <div class="fw-semibold">Gunakan Template Resmi</div>
                            <small class="text-muted">
                                Format sudah disesuaikan dengan sistem
                            </small>
                        </div>

                        <button type="button" id="downloadTemplateBtn" class="btn btn-success px-3 shadow-sm">
                            <i data-feather="download"></i> Download
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

                                <h6 class="fw-bold mb-3 text-primary">
                                    Panduan
                                </h6>

                                <ul class="small mb-0 ps-3">
                                    <li>Jangan ubah struktur kolom</li>
                                    <li>Isi hanya <b>Gaji Pokok</b></li>
                                    <li>Simpan dalam format Excel</li>
                                    <li>Upload kembali ke sistem</li>
                                </ul>

                            </div>
                        </div>

                        <!-- CATATAN -->
                        <div class="col-md-6">
                            <div class="p-4 border rounded-4 h-100 bg-light">

                                <h6 class="fw-bold mb-3 text-warning">
                                    Catatan Penting
                                </h6>

                                <p class="small mb-0">
                                    Sistem hanya membaca data sesuai template.
                                    Perubahan struktur akan menyebabkan gagal upload.
                                </p>

                            </div>
                        </div>

                    </div>

                    <!-- PREVIEW TABLE -->
                    <div class="mb-4">

                        <div class="p-4 border rounded-4 shadow-sm">

                            <h6 class="fw-bold mb-3 text-success">
                                Contoh Data
                            </h6>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered text-center align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>NIK</th>
                                            <th>Nama</th>
                                            <th>Jabatan</th>
                                            <th>Status</th>
                                            <th>Masa Kerja</th>
                                            <th class="bg-warning">Gaji Pokok</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr>
                                            <td>123456</td>
                                            <td>Ahmad</td>
                                            <td>Perawat</td>
                                            <td><span class="badge bg-primary">T</span></td>
                                            <td><span class="badge bg-success">PT</span></td>
                                            <td class="bg-warning fw-bold">3.500.000</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <small class="text-muted">
                                Hanya Kolom <b class="text-warning">Gaji Pokok</b> yang wajib diisi.
                            </small>

                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="d-flex justify-content-between align-items-center">

                        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                            Tutup
                        </button>

                        <button type="button" id="submitFormExcell" class="btn btn-primary px-4 shadow">
                            <i data-feather="upload"></i> Upload Sekarang
                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>
</div>
