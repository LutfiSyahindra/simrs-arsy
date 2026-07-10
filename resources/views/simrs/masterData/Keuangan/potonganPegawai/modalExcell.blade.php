<div class="modal fade" id="potonganPegawaiModalExcell" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-light">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Import Potongan Pegawai</h5>
                    <small class="text-muted">Template sudah memuat kode dan nama potongan.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <form id="potonganPegawaiExcellForm" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="border rounded-3 p-4 h-100 bg-white">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">Template Excel</h6>
                                        <small class="text-muted">Isi nominal pada baris potongan yang ingin diimport.</small>
                                    </div>
                                    <i data-feather="download" class="text-primary"></i>
                                </div>

                                <button type="button" id="downloadTemplateBtn" class="btn btn-dark w-100">
                                    <i data-feather="download" class="me-1"></i>
                                    Download Template
                                </button>

                                <hr>

                                <h6 class="fw-bold mb-2">Referensi Potongan</h6>
                                <div class="table-responsive" style="max-height: 260px;">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kode</th>
                                                <th>Nama</th>
                                                <th>Tipe</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($potongan as $p)
                                                <tr>
                                                    <td class="fw-semibold">{{ $p->kode }}</td>
                                                    <td>{{ $p->nama }}</td>
                                                    <td>{{ str_replace("_", " ", $p->tipe) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-muted text-center py-3">
                                                        Referensi potongan belum tersedia.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="border rounded-3 p-4 h-100 bg-white">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">Upload File Excel</h6>
                                        <small class="text-muted">Format file .xls atau .xlsx, maksimal 5 MB.</small>
                                    </div>
                                    <i data-feather="upload-cloud" class="text-primary"></i>
                                </div>

                                <input type="file" id="myDropify" name="file" class="dropify"
                                    accept=".xls,.xlsx" data-height="188" data-max-file-size="5M"
                                    data-allowed-file-extensions="xls xlsx"
                                    data-messages-default="Klik atau drag file Excel ke sini"
                                    data-messages-replace="Klik atau drag untuk mengganti file"
                                    data-messages-remove="Hapus" data-messages-error="File tidak valid" />

                                <div class="alert alert-warning small mb-0 mt-3">
                                    Baris kosong akan dilewati.
                                    Khusus persen total gaji, template memberi Nominal 0 dan nominal final dihitung saat generate gaji tahap 2.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between gap-2 mt-4">
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
