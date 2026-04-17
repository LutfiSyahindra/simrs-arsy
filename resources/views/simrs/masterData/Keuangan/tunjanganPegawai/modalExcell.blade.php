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
                            Import data tunjangan pegawai sesuai template sistem
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body px-5 py-4">

                <form id="tunjanganPegawaiExcellForm" enctype="multipart/form-data">
                    @csrf

                    <!-- STEP -->
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
                                Format sudah sesuai sistem, jangan diubah
                            </small>
                        </div>

                        <button type="button" id="downloadTemplateBtn" class="btn btn-dark px-3 shadow-sm">
                            <i data-feather="download"></i> Download Template
                        </button>
                    </div>

                    <!-- UPLOAD -->
                    <div class="mb-4">
                        <div class="border border-2 border-dashed rounded-4 p-5 text-center position-relative"
                            style="background: #f8fbff;">

                            <input type="file" id="myDropify" name="file"
                                class="position-absolute w-100 h-100 top-0 start-0 opacity-0" accept=".xls,.xlsx" />

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

                    <!-- INFO -->
                    <div class="row g-3 mb-4">

                        <!-- PANDUAN -->
                        <div class="col-md-6">
                            <div class="p-4 border rounded-4 h-100 bg-white shadow-sm">

                                <h6 class="fw-bold mb-3 text-dark">Panduan Pengisian</h6>

                                <div class="alert alert-info small">
                                    Jika satu pegawai memiliki beberapa tunjangan,
                                    isi dalam beberapa baris dengan <b>NIK yang sama</b>.
                                </div>

                                <div class="alert alert-warning small mt-2">
                                    <strong>Penting:</strong>
                                    <ul class="mb-0 ps-3">
                                        <li><b>referensi_id</b> diisi dengan <b>KODE</b> (JBT001, PRF002)</li>
                                        <li>Sistem akan otomatis konversi ke ID</li>
                                        <li>Jika kosong pada tunjangan tertentu → data tidak diimport</li>
                                    </ul>
                                </div>

                                <ul class="small mb-0 ps-3 mt-3">
                                    <li>Isi kolom: <b>Tunjangan_id, referensi_id, qty</b></li>
                                    <li>Gunakan kode tunjangan (contoh: <b>TJ001</b>)</li>
                                    <li>Satu baris hanya untuk <b>1 tunjangan</b></li>
                                </ul>

                                <hr>

                                <ul class="small mb-0 ps-3">
                                    <li><b>TJ001</b> → wajib referensi_id (kode jabatan)</li>
                                    <li><b>TJ005</b> → wajib referensi_id (kode profesi)</li>
                                    <li><b>TJ002</b> → isi qty (jumlah anak)</li>
                                    <li><b>TJ003</b> → otomatis</li>
                                    <li><b>TJ004</b> → otomatis dari masa kerja</li>
                                </ul>

                            </div>
                        </div>

                        <!-- REFERENSI -->
                        <div class="col-md-6">
                            <div class="p-4 border rounded-4 h-100 bg-light">

                                <h6 class="fw-bold mb-3 text-success">Referensi Tunjangan</h6>
                                <!-- ================== TUNJANGAN ================== -->
                                <div class="table-responsive mb-3" style="max-height:200px;">
                                    <table class="table table-sm table-hover text-center mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kode</th>
                                                <th>Tunjangan</th>
                                                <th>Tipe</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($tunjangan as $t)
                                                <tr>
                                                    <td class="fw-bold">{{ $t->kode }}</td>
                                                    <td>{{ $t->nama }}</td>

                                                    <td>
                                                        @if (in_array($t->kode, ["TJ001", "TJ005"]))
                                                            <span class="badge bg-primary">Referensi</span>
                                                        @elseif($t->kode == "TJ002")
                                                            <span class="badge bg-warning text-dark">Qty</span>
                                                        @elseif($t->kode == "TJ003")
                                                            <span class="badge bg-info text-dark">%</span>
                                                        @elseif($t->kode == "TJ004")
                                                            <span class="badge bg-success">Per Tahun</span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @if ($t->kode == "TJ001")
                                                            Gunakan kode jabatan
                                                        @elseif($t->kode == "TJ005")
                                                            Gunakan kode profesi
                                                        @elseif($t->kode == "TJ002")
                                                            Isi jumlah anak
                                                        @else
                                                            Otomatis
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ================== JABATAN ================== -->
                                <h6 class="fw-bold text-primary mb-2">Referensi Jabatan (TJ001)</h6>
                                <div class="table-responsive mb-3" style="max-height:150px;">
                                    <table class="table table-sm table-bordered text-center mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kode</th>
                                                <th>Nama Jabatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($jabatan as $j)
                                                <tr>
                                                    <td>{{ $j->kode }}</td>
                                                    <td>{{ $j->nama }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ================== PROFESI ================== -->
                                <h6 class="fw-bold text-success mb-2">Referensi Profesi (TJ005)</h6>
                                <div class="table-responsive" style="max-height:150px;">
                                    <table class="table table-sm table-bordered text-center mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kode</th>
                                                <th>Nama Profesi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($profesi as $p)
                                                <tr>
                                                    <td>{{ $p->kode }}</td>
                                                    <td>{{ $p->nama }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- CONTOH -->
                    <div class="mb-4">
                        <div class="p-4 border rounded-4 shadow-sm bg-white">

                            <h6 class="fw-bold mb-3 text-success">Contoh Excel</h6>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm text-center align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>NIK</th>
                                            <th>Nama</th>
                                            <th>Jabatan</th>
                                            <th>Status</th>
                                            <th>Masa Kerja</th>
                                            <th>Tunjangan_id</th>
                                            <th>referensi_id</th>
                                            <th>qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>123456</td>
                                            <td>Ahmad</td>
                                            <td>Kepala</td>
                                            <td>T</td>
                                            <td>10 Tahun</td>
                                            <td class="bg-warning fw-bold">TJ001</td>
                                            <td class="bg-info fw-bold">JBT001</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>123456</td>
                                            <td>Ahmad</td>
                                            <td>Kepala</td>
                                            <td>T</td>
                                            <td>10 Tahun</td>
                                            <td class="bg-warning fw-bold">TJ002</td>
                                            <td></td>
                                            <td class="bg-info fw-bold">2</td>
                                        </tr>
                                        <tr>
                                            <td>123456</td>
                                            <td>Ahmad</td>
                                            <td>Kepala</td>
                                            <td>T</td>
                                            <td>10 Tahun</td>
                                            <td class="bg-warning fw-bold">TJ003</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>123456</td>
                                            <td>Ahmad</td>
                                            <td>Kepala</td>
                                            <td>T</td>
                                            <td>10 Tahun</td>
                                            <td class="bg-warning fw-bold">TJ004</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>123456</td>
                                            <td>Ahmad</td>
                                            <td>Kepala</td>
                                            <td>T</td>
                                            <td>10 Tahun</td>
                                            <td class="bg-warning fw-bold">TJ005</td>
                                            <td class="bg-info fw-bold">PRF001</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                            Tutup
                        </button>

                        <button type="button" id="submitFormExcell" class="btn btn-dark px-4 shadow">
                            Upload Data
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>
