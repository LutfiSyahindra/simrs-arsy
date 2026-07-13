@extends("template.partials.app")

@php
    $generatorGroups = [
        [
            "key" => "source",
            "badge" => "Langkah 1",
            "title" => "Generator Sumber",
            "subtitle" => "Jalankan dulu generator dasar yang menjadi bahan generator gabungan.",
            "icon" => "mdi-database-sync-outline",
            "generators" => [
                [
                    "key" => "ugd",
                    "short" => "UGD",
                    "title" => "Generator Premi UGD",
                    "description" => "Input jumlah pasien UGD per dokter, jenis pelayanan, dan ploting premi.",
                    "source" => "Data real pelayanan UGD manual",
                    "classification" => "Sumber Tindakan Medis",
                    "feeds" => ["Tindakan Medis", "Premi Bersama"],
                    "keywords" => "ugd dokter umum bpjs manual pasien tindakan medis bersama",
                    "icon" => "mdi-doctor",
                    "color" => "#7c3aed",
                    "soft" => "#f3e8ff",
                    "url" => route("backOffice.keuangan.hitungPremi.generateUgd"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
                [
                    "key" => "vk",
                    "short" => "VK",
                    "title" => "Generator Premi VK",
                    "description" => "Input jumlah tindakan VK berdasarkan tindakan Khanza, jenis pelayanan, dan ploting premi.",
                    "source" => "Data tindakan Khanza dan input VK manual",
                    "classification" => "Sumber Tindakan Medis",
                    "feeds" => ["Tindakan Medis", "Premi Bersama"],
                    "keywords" => "vk tindakan umum bpjs manual tindakan medis bersama",
                    "icon" => "mdi-mother-nurse",
                    "color" => "#be123c",
                    "soft" => "#ffe4e6",
                    "url" => route("backOffice.keuangan.hitungPremi.generateVk"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
                [
                    "key" => "kamar",
                    "short" => "Kamar",
                    "title" => "Generator Premi Kamar",
                    "description" => "Memproses premi dari penggunaan kamar, lama inap, penjamin, dan status pembayaran.",
                    "source" => "Registrasi rawat inap dan penggunaan kamar",
                    "classification" => "Sumber Pelayanan Non Medis",
                    "feeds" => ["Pelayanan Non Medis", "Premi Bersama"],
                    "keywords" => "kamar rawat inap kelas tarif hari perawatan non medis bersama",
                    "icon" => "mdi-hospital-building",
                    "color" => "#059669",
                    "soft" => "#d1fae5",
                    "url" => route("backOffice.keuangan.hitungPremi.generateKamar"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
                [
                    "key" => "bhp",
                    "short" => "BHP",
                    "title" => "Generator Premi BHP",
                    "description" => "Memproses premi dari penggunaan bahan habis pakai pada transaksi pelayanan pasien.",
                    "source" => "Pemakaian BHP dan transaksi pelayanan",
                    "classification" => "Sumber Pelayanan Non Medis",
                    "feeds" => ["Pelayanan Non Medis", "Premi Bersama"],
                    "keywords" => "bhp bahan habis pakai alat kesehatan non medis bersama",
                    "icon" => "mdi-medical-bag",
                    "color" => "#d97706",
                    "soft" => "#fef3c7",
                    "url" => route("backOffice.keuangan.hitungPremi.generateBhp"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
                [
                    "key" => "laboratorium",
                    "short" => "Laborat",
                    "title" => "Generator Premi Laboratorium",
                    "description" => "Mengambil transaksi laboratorium Khanza otomatis dan menghitung premi petugas serta premi bersama.",
                    "source" => "detail_periksa_lab, periksa_lab, dan registrasi penjamin",
                    "classification" => "Sumber Premi Bersama",
                    "feeds" => ["Premi Bersama"],
                    "keywords" => "laboratorium laborat detail periksa lab umum bpjs bersama",
                    "icon" => "mdi-flask-outline",
                    "color" => "#7c3aed",
                    "soft" => "#ede9fe",
                    "url" => route("backOffice.keuangan.hitungPremi.generateLaboratorium"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
                [
                    "key" => "radiologi",
                    "short" => "Radiologi",
                    "title" => "Generator Premi Radiologi",
                    "description" => "Mengambil transaksi radiologi Khanza otomatis dan menghitung premi petugas serta premi bersama.",
                    "source" => "periksa_radiologi dan registrasi penjamin",
                    "classification" => "Sumber Premi Bersama",
                    "feeds" => ["Premi Bersama"],
                    "keywords" => "radiologi umum bpjs periksa radiologi petugas bersama",
                    "icon" => "mdi-radioactive-circle-outline",
                    "color" => "#0f766e",
                    "soft" => "#ccfbf1",
                    "url" => route("backOffice.keuangan.hitungPremi.generateRadiologi"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
                [
                    "key" => "apotek",
                    "short" => "Apotek",
                    "title" => "Generator Premi Apotek",
                    "description" => "Mencocokkan kode obat ke mapping Farmasi, lalu menghitung formula Apotek.",
                    "source" => "detail_pemberian_obat dan mapping Farmasi",
                    "classification" => "Sumber Premi Bersama",
                    "feeds" => ["Premi Bersama"],
                    "keywords" => "apotek farmasi obat detail pemberian obat bersama",
                    "icon" => "mdi-pill",
                    "color" => "#db2777",
                    "soft" => "#fce7f3",
                    "url" => route("backOffice.keuangan.hitungPremi.generateApotek"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
                [
                    "key" => "operasi",
                    "short" => "Operasi",
                    "title" => "Generator Premi Operasi",
                    "description" => "Memproses pembagian premi untuk tenaga medis yang terlibat dalam tindakan operasi.",
                    "source" => "Pelayanan paramedis dan unit kerja Operasi",
                    "classification" => "Sumber Premi Bersama",
                    "feeds" => ["Premi Bersama"],
                    "keywords" => "operasi tindakan bedah tenaga medis bersama",
                    "icon" => "mdi-knife",
                    "color" => "#0891b2",
                    "soft" => "#cffafe",
                    "url" => route("backOffice.keuangan.hitungPremi.generateOperasi"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
                [
                    "key" => "gizi",
                    "short" => "Gizi",
                    "title" => "Generator Premi Gizi",
                    "description" => "Mengambil tindakan Konsul dan Diit, lalu menghitung pembagian UMUM/BPJS.",
                    "source" => "rawat dokter, rawat paramedis, mapping Konsul/Diit",
                    "classification" => "Sumber Premi Bersama",
                    "feeds" => ["Premi Bersama"],
                    "keywords" => "gizi konsul diit rawat jalan rawat inap mapping bersama",
                    "icon" => "mdi-food-apple-outline",
                    "color" => "#15803d",
                    "soft" => "#dcfce7",
                    "url" => route("backOffice.keuangan.hitungPremi.generateGizi"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
                [
                    "key" => "nicu",
                    "short" => "NICU",
                    "title" => "Generator Premi NICU",
                    "description" => "Mengambil tindakan pasien dengan riwayat NICU dan formula premi NICU.",
                    "source" => "kamar_inap, rawat dokter, rawat paramedis",
                    "classification" => "Sumber Premi Bersama",
                    "feeds" => ["Premi Bersama"],
                    "keywords" => "nicu umum bpjs kamar inap tindakan rawat kritikal mapping bersama",
                    "icon" => "mdi-baby-face-outline",
                    "color" => "#0891b2",
                    "soft" => "#cffafe",
                    "url" => route("backOffice.keuangan.hitungPremi.generateNicu"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "source",
                ],
            ],
        ],
        [
            "key" => "derived",
            "badge" => "Langkah 2",
            "title" => "Generator Turunan",
            "subtitle" => "Jalankan setelah sumber utamanya selesai supaya hasilnya tidak kosong atau timpang.",
            "icon" => "mdi-source-merge",
            "generators" => [
                [
                    "key" => "tindakan-medis",
                    "short" => "Tindakan Medis",
                    "title" => "Generator Premi Tindakan Medis",
                    "description" => "Menggabungkan elemen dari Generator Premi UGD dan Generator Premi VK.",
                    "source" => "UGD dan VK",
                    "classification" => "Gabungan medis",
                    "depends_on" => ["ugd", "vk"],
                    "keywords" => "tindakan medis ugd vk dokter perawat mapping premi",
                    "icon" => "mdi-stethoscope",
                    "color" => "#0f766e",
                    "soft" => "#ccfbf1",
                    "url" => route("backOffice.keuangan.hitungPremi.generateTindakanMedis"),
                    "enabled" => true,
                    "status" => "Gabungan",
                    "status_class" => "aggregate",
                    "category" => "aggregate",
                ],
                [
                    "key" => "pelayanan-non-medis",
                    "short" => "Non Medis",
                    "title" => "Generator Premi Pelayanan Non Medis",
                    "description" => "Menggabungkan elemen dari Generator Premi Kamar dan Generator Premi BHP.",
                    "source" => "Kamar dan BHP",
                    "classification" => "Gabungan non medis",
                    "depends_on" => ["kamar", "bhp"],
                    "keywords" => "pelayanan non medis kamar bhp mapping premi",
                    "icon" => "mdi-hospital-building",
                    "color" => "#2563eb",
                    "soft" => "#dbeafe",
                    "url" => route("backOffice.keuangan.hitungPremi.generatePelayananNonMedis"),
                    "enabled" => true,
                    "status" => "Gabungan",
                    "status_class" => "aggregate",
                    "category" => "aggregate",
                ],
            ],
        ],
        [
            "key" => "final",
            "badge" => "Langkah 3",
            "title" => "Generator Premi Bersama",
            "subtitle" => "Jalankan setelah semua sumber Premi Bersama di bawah ini sudah siap.",
            "icon" => "mdi-chart-donut",
            "generators" => [
                [
                    "key" => "premi-bersama",
                    "short" => "Bersama",
                    "title" => "Generator Premi Bersama",
                    "description" => "Menggabungkan hasil sumber unit, lalu membagikan grand total berdasarkan skor pegawai.",
                    "source" => "UGD, Laborat, Radiologi, Apotek, VK, Kamar, Operasi, BHP, Gizi, dan NICU",
                    "classification" => "Final bersama",
                    "depends_on" => ["ugd", "laboratorium", "radiologi", "apotek", "vk", "kamar", "operasi", "bhp", "gizi", "nicu"],
                    "keywords" => "premi bersama ugd laboratorium laborat radiologi apotek vk kamar operasi bhp gizi nicu skor pegawai",
                    "icon" => "mdi-chart-donut",
                    "color" => "#1d4ed8",
                    "soft" => "#dbeafe",
                    "url" => route("backOffice.keuangan.hitungPremi.generatePremiBersama"),
                    "enabled" => true,
                    "status" => "Final",
                    "status_class" => "final",
                    "category" => "aggregate",
                ],
            ],
        ],
        [
            "key" => "independent",
            "badge" => "Sesuai kebutuhan",
            "title" => "Generator Mandiri dan Khusus",
            "subtitle" => "Generator ini diklasifikasikan terpisah karena tidak menjadi prasyarat utama tiga generator gabungan di atas.",
            "icon" => "mdi-shape-outline",
            "generators" => [
                [
                    "key" => "dokter",
                    "short" => "Dokter",
                    "title" => "Generator Premi Dokter",
                    "description" => "Mengelola kategori premi dokter dan memproses jasa dokter umum maupun spesialis.",
                    "source" => "Enam tabel rawat Khanza dan konfigurasi dokter",
                    "classification" => "Profesi dokter",
                    "keywords" => "dokter visite jasa operasi rawat jalan poli igd ecg konsul wa kehadiran spesialis umum",
                    "icon" => "mdi-doctor",
                    "color" => "#9333ea",
                    "soft" => "#f3e8ff",
                    "url" => route("backOffice.keuangan.hitungPremi.generatePremiDokter"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "independent",
                ],
                [
                    "key" => "icu",
                    "short" => "ICU",
                    "title" => "Generator Premi ICU",
                    "description" => "Mengambil tindakan pasien dengan riwayat ICU, mapping tindakan, dan formula premi ICU.",
                    "source" => "kamar_inap, rawat dokter, rawat paramedis",
                    "classification" => "Unit kritikal mandiri",
                    "keywords" => "icu umum bpjs kamar inap tindakan rawat kritikal mapping",
                    "icon" => "mdi-heart-pulse",
                    "color" => "#dc2626",
                    "soft" => "#fee2e2",
                    "url" => route("backOffice.keuangan.hitungPremi.generateIcu"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "independent",
                ],
                [
                    "key" => "fisio",
                    "short" => "Fisio",
                    "title" => "Generator Premi Fisio",
                    "description" => "Menghitung premi fisioterapi manual untuk UMUM dan BPJS dari konfigurasi tindakan.",
                    "source" => "Konfigurasi tindakan, rumus, dan petugas penerima",
                    "classification" => "Penunjang rehabilitasi",
                    "keywords" => "fisio fisioterapi umum bpjs tindakan harga pasien manual konfigurasi",
                    "icon" => "mdi-human-cane",
                    "color" => "#0f766e",
                    "soft" => "#ccfbf1",
                    "url" => route("backOffice.keuangan.hitungPremi.generateFisio"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "independent",
                ],
                [
                    "key" => "driver",
                    "short" => "Driver",
                    "title" => "Generator Premi Driver Ambulance",
                    "description" => "Input tujuan ambulance dan jumlah perjalanan, lalu hitung premi driver.",
                    "source" => "Konfigurasi tujuan, harga, dan persentase premi",
                    "classification" => "Transportasi pasien",
                    "keywords" => "driver ambulance sopir tujuan harga perjalanan premi bersama",
                    "icon" => "mdi-ambulance",
                    "color" => "#0f766e",
                    "soft" => "#ccfbf1",
                    "url" => route("backOffice.keuangan.hitungPremi.generatePremiDriver"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "independent",
                ],
                [
                    "key" => "casemix",
                    "short" => "Casemix",
                    "title" => "Generator Premi Casemix",
                    "description" => "Menghitung premi tim Casemix dari hasil verifikasi BPJS dan indikator kinerja.",
                    "source" => "Input biaya, hasil BPJS, dan questionnaire kinerja",
                    "classification" => "Klaim BPJS",
                    "keywords" => "casemix bpjs klaim verifikasi hasil questionnaire indikator",
                    "icon" => "mdi-file-chart-outline",
                    "color" => "#4f46e5",
                    "soft" => "#e0e7ff",
                    "url" => route("backOffice.keuangan.hitungPremi.generateCasemix"),
                    "enabled" => true,
                    "status" => "Siap",
                    "status_class" => "ready",
                    "category" => "independent",
                ],
                [
                    "key" => "rumah-sakit",
                    "short" => "RS",
                    "title" => "Generator Premi Rumah Sakit",
                    "description" => "Memproses bagian rumah sakit dari pendapatan pelayanan sesuai kebijakan distribusi.",
                    "source" => "Pendapatan pelayanan rumah sakit",
                    "classification" => "Manajemen rumah sakit",
                    "keywords" => "rumah sakit manajemen pendapatan rs",
                    "icon" => "mdi-hospital-building",
                    "color" => "#475569",
                    "soft" => "#e2e8f0",
                    "url" => null,
                    "enabled" => false,
                    "status" => "Persiapan",
                    "status_class" => "preparation",
                    "category" => "preparation",
                ],
            ],
        ],
    ];

    $allGenerators = collect($generatorGroups)->flatMap(fn ($group) => $group["generators"]);
    $generatorLookup = $allGenerators->keyBy("key");
    $activeCount = $allGenerators->where("enabled", true)->count();
    $filters = [
        ["key" => "all", "label" => "Semua", "count" => $allGenerators->count()],
        ["key" => "source", "label" => "Sumber", "count" => $allGenerators->where("category", "source")->count()],
        ["key" => "aggregate", "label" => "Gabungan", "count" => $allGenerators->where("category", "aggregate")->count()],
        ["key" => "independent", "label" => "Mandiri", "count" => $allGenerators->where("category", "independent")->count()],
        ["key" => "preparation", "label" => "Persiapan", "count" => $allGenerators->where("category", "preparation")->count()],
    ];
    $requestedPeriode = request("periode");
    $defaultPeriode = is_string($requestedPeriode) && preg_match("/^\d{4}-\d{2}$/", $requestedPeriode)
        ? $requestedPeriode
        : now()->format("Y-m");
@endphp

@push("style")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    <style>
        .premium-generator-page {
            --pg-ink: #172033;
            --pg-muted: #64748b;
            --pg-soft: #f8fafc;
            --pg-line: #e2e8f0;
            --pg-dark: #102a43;
            color: var(--pg-ink);
        }

        .premium-generator-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 12px;
            padding: 0;
        }

        .generator-overview {
            align-items: stretch;
            background: var(--pg-soft);
            border: 1px solid var(--pg-line);
            border-left: 4px solid #0f766e;
            border-radius: 8px;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1fr) auto;
            margin-bottom: 16px;
            padding: 18px;
        }

        .generator-overview-main {
            display: flex;
            gap: 14px;
        }

        .generator-overview-icon {
            align-items: center;
            background: #ccfbf1;
            border-radius: 8px;
            color: #0f766e;
            display: flex;
            flex: 0 0 auto;
            font-size: 27px;
            height: 48px;
            justify-content: center;
            width: 48px;
        }

        .generator-eyebrow {
            color: #0f766e;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .generator-title {
            color: var(--pg-dark);
            font-size: 21px;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 5px;
        }

        .generator-description {
            color: var(--pg-muted);
            font-size: 13px;
            line-height: 1.55;
            margin: 0;
            max-width: 820px;
        }

        .generator-overview-metrics {
            align-items: center;
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(92px, 1fr));
        }

        .generator-period-panel {
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: 8px;
            grid-column: 1 / -1;
            padding: 10px 12px;
        }

        .generator-period-panel label {
            color: #334155;
            display: block;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .generator-period-panel .form-control {
            border-color: var(--pg-line);
            height: 36px;
        }

        .generator-period-hint {
            color: var(--pg-muted);
            display: block;
            font-size: 11px;
            line-height: 1.4;
            margin-top: 6px;
        }

        .generator-metric {
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: 8px;
            min-width: 92px;
            padding: 10px 12px;
        }

        .generator-metric-value {
            color: var(--pg-dark);
            display: block;
            font-size: 20px;
            font-weight: 800;
            line-height: 1;
        }

        .generator-metric-label {
            color: var(--pg-muted);
            display: block;
            font-size: 11px;
            margin-top: 5px;
        }

        .generator-flow {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 16px;
        }

        .generator-flow-step {
            align-items: flex-start;
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: 8px;
            color: inherit;
            cursor: pointer;
            display: flex;
            gap: 10px;
            padding: 12px;
            text-align: left;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
            width: 100%;
        }

        .generator-flow-step:hover,
        .generator-flow-step:focus {
            border-color: #0f766e;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
            color: inherit;
            outline: 0;
            transform: translateY(-1px);
        }

        .generator-flow-icon {
            align-items: center;
            background: #ecfdf5;
            border-radius: 8px;
            color: #0f766e;
            display: flex;
            flex: 0 0 auto;
            font-size: 20px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .generator-flow-badge {
            color: #0f766e;
            display: block;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .generator-flow-title {
            color: #0f172a;
            display: block;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 3px;
        }

        .generator-flow-subtitle {
            color: var(--pg-muted);
            display: block;
            font-size: 11px;
            line-height: 1.4;
        }

        .generator-toolbar {
            align-items: center;
            border-bottom: 1px solid var(--pg-line);
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 14px;
        }

        .generator-toolbar-title {
            color: var(--pg-dark);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .generator-toolbar-subtitle {
            color: var(--pg-muted);
            font-size: 12px;
        }

        .generator-toolbar-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .generator-filter {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .generator-filter-btn {
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: 999px;
            color: #334155;
            cursor: pointer;
            font-size: 11px;
            font-weight: 800;
            min-height: 34px;
            padding: 7px 11px;
        }

        .generator-filter-btn.active,
        .generator-filter-btn:hover,
        .generator-filter-btn:focus {
            background: var(--pg-dark);
            border-color: var(--pg-dark);
            color: #fff;
            outline: 0;
        }

        .generator-search {
            max-width: 280px;
            width: 100%;
        }

        .generator-search .form-control,
        .generator-search .input-group-text {
            border-color: var(--pg-line);
            height: 38px;
        }

        .generator-groups {
            display: grid;
            gap: 24px;
        }

        .generator-group {
            scroll-margin-top: 90px;
        }

        .generator-group-header {
            align-items: flex-end;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .generator-group-title-row {
            align-items: center;
            display: flex;
            gap: 9px;
            margin-bottom: 4px;
        }

        .generator-group-icon {
            align-items: center;
            background: #eef2ff;
            border-radius: 8px;
            color: #3730a3;
            display: flex;
            flex: 0 0 auto;
            font-size: 18px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .generator-group-badge {
            color: #0f766e;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .generator-group-title {
            color: #0f172a;
            font-size: 17px;
            font-weight: 800;
            margin: 0;
        }

        .generator-group-subtitle {
            color: var(--pg-muted);
            font-size: 12px;
            margin: 0;
        }

        .generator-group-count {
            background: #f1f5f9;
            border: 1px solid var(--pg-line);
            border-radius: 999px;
            color: #334155;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 800;
            padding: 6px 10px;
        }

        .generator-menu-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .generator-menu-card {
            --card-color: #0f766e;
            --card-soft: #ccfbf1;
            background: #fff;
            border: 1px solid var(--pg-line);
            border-left: 4px solid var(--card-color);
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
            display: flex;
            flex-direction: column;
            min-height: 252px;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .generator-menu-card:hover {
            border-color: var(--card-color);
            box-shadow: 0 14px 28px rgba(15, 23, 42, .09);
            transform: translateY(-2px);
        }

        .generator-menu-card.is-highlighted {
            animation: generator-card-pulse 1.2s ease;
        }

        @keyframes generator-card-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(15, 118, 110, .35);
            }

            70% {
                box-shadow: 0 0 0 9px rgba(15, 118, 110, 0);
            }

            100% {
                box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
            }
        }

        .generator-menu-body {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 16px;
        }

        .generator-menu-head {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .generator-menu-icon {
            align-items: center;
            background: var(--card-soft);
            border-radius: 8px;
            color: var(--card-color);
            display: flex;
            flex: 0 0 auto;
            font-size: 24px;
            height: 46px;
            justify-content: center;
            width: 46px;
        }

        .generator-menu-status {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            color: #15803d;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 8px;
            white-space: nowrap;
        }

        .generator-menu-status.aggregate {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .generator-menu-status.final {
            background: #eef2ff;
            border-color: #c7d2fe;
            color: #3730a3;
        }

        .generator-menu-status.preparation {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
        }

        .generator-menu-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.35;
            margin: 13px 0 5px;
        }

        .generator-menu-classification {
            color: var(--card-color);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .generator-menu-description {
            color: var(--pg-muted);
            font-size: 12px;
            line-height: 1.55;
            margin-bottom: 12px;
        }

        .generator-menu-source {
            align-items: flex-start;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 8px;
            color: #475569;
            display: flex;
            font-size: 11px;
            gap: 7px;
            line-height: 1.45;
            margin-bottom: 10px;
            padding: 8px 10px;
        }

        .generator-period-status {
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 9px 10px;
        }

        .generator-period-status-title {
            align-items: center;
            color: #475569;
            display: flex;
            font-size: 11px;
            font-weight: 800;
            gap: 6px;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .generator-status-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .generator-status-pill {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #64748b;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            line-height: 1;
            min-height: 27px;
            padding: 6px 9px;
        }

        .generator-status-pill .mdi {
            font-size: 14px;
        }

        .generator-status-pill.generated {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #15803d;
        }

        .generator-status-pill.missing {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #c2410c;
        }

        .generator-status-pill.unavailable {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
        }

        .generator-status-pill.loading {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .generator-dependency {
            margin-top: auto;
        }

        .generator-dependency-label {
            color: #94a3b8;
            display: block;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .generator-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .generator-chip,
        .generator-chip-button {
            align-items: center;
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: 999px;
            color: #334155;
            display: inline-flex;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            min-height: 26px;
            padding: 6px 9px;
        }

        .generator-chip-button {
            cursor: pointer;
        }

        .generator-chip-button:hover,
        .generator-chip-button:focus {
            background: var(--card-soft);
            border-color: var(--card-color);
            color: var(--card-color);
            outline: 0;
        }

        .generator-menu-footer {
            align-items: center;
            border-top: 1px solid #eef2f7;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            padding: 12px 16px;
        }

        .generator-menu-code {
            color: #94a3b8;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .btn-open-generator {
            align-items: center;
            background: var(--card-color);
            border: 0;
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            justify-content: center;
            min-height: 34px;
            padding: 8px 12px;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-open-generator:hover,
        .btn-open-generator:focus {
            color: #fff;
            filter: brightness(.94);
            outline: 0;
        }

        .btn-open-generator:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }

        .generator-empty {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: var(--pg-muted);
            display: none;
            margin-top: 16px;
            padding: 32px;
            text-align: center;
        }

        @media (max-width: 1199.98px) {
            .generator-overview,
            .generator-flow {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .generator-menu-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .generator-overview,
            .generator-flow,
            .generator-menu-grid {
                grid-template-columns: 1fr;
            }

            .generator-overview-main,
            .generator-toolbar,
            .generator-group-header {
                align-items: stretch;
                flex-direction: column;
            }

            .generator-overview-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .generator-toolbar-actions,
            .generator-search {
                justify-content: flex-start;
                max-width: none;
                width: 100%;
            }

            .generator-menu-footer {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
@endpush

@section("content")
    <div class="premium-generator-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Keuangan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Generate Premi</li>
            </ol>
        </nav>

        <section class="generator-overview">
            <div class="generator-overview-main">
                <div class="generator-overview-icon">
                    <i class="mdi mdi-cog-transfer-outline"></i>
                </div>
                <div>
                    <div class="generator-eyebrow">Hitung Premi</div>
                    <h4 class="generator-title">Pilih generator sesuai urutan sumber datanya</h4>
                    <p class="generator-description">
                        Generator sumber ditempatkan di awal, generator turunan berada setelahnya, lalu Premi Bersama
                        menjadi tahap final karena membutuhkan banyak sumber unit yang sudah selesai digenerate.
                    </p>
                </div>
            </div>
            <div class="generator-overview-metrics">
                <div class="generator-metric">
                    <span class="generator-metric-value">{{ $activeCount }}</span>
                    <span class="generator-metric-label">Generator aktif</span>
                </div>
                <div class="generator-metric">
                    <span class="generator-metric-value">{{ $allGenerators->where("category", "aggregate")->count() }}</span>
                    <span class="generator-metric-label">Generator gabungan</span>
                </div>
                <div class="generator-period-panel">
                    <label for="periodeGeneratorStatus">Periode Status Generate</label>
                    <input type="month" id="periodeGeneratorStatus" class="form-control"
                        value="{{ $defaultPeriode }}"
                        data-status-url="{{ route("backOffice.keuangan.hitungPremi.generatorStatus") }}">
                    <small class="generator-period-hint" id="generatorStatusInfo">
                        Memeriksa status UMUM/BPJS periode {{ $defaultPeriode }}.
                    </small>
                </div>
            </div>
        </section>

        <div class="generator-flow" aria-label="Alur generator premi">
            @foreach ($generatorGroups as $group)
                <button type="button" class="generator-flow-step" data-jump-group="{{ $group["key"] }}">
                    <span class="generator-flow-icon"><i class="mdi {{ $group["icon"] }}"></i></span>
                    <span>
                        <span class="generator-flow-badge">{{ $group["badge"] }}</span>
                        <span class="generator-flow-title">{{ $group["title"] }}</span>
                        <span class="generator-flow-subtitle">{{ count($group["generators"]) }} menu</span>
                    </span>
                </button>
            @endforeach
        </div>

        <div class="generator-toolbar">
            <div>
                <div class="generator-toolbar-title">Daftar Generator Premi</div>
                <div class="generator-toolbar-subtitle">
                    Menampilkan <span id="generatorMenuCount">{{ $allGenerators->count() }}</span> menu sesuai filter.
                </div>
            </div>
            <div class="generator-toolbar-actions">
                <div class="generator-filter" aria-label="Filter generator">
                    @foreach ($filters as $filter)
                        <button type="button" class="generator-filter-btn {{ $filter["key"] === "all" ? "active" : "" }}"
                            data-filter-category="{{ $filter["key"] }}" aria-pressed="{{ $filter["key"] === "all" ? "true" : "false" }}">
                            {{ $filter["label"] }} {{ $filter["count"] }}
                        </button>
                    @endforeach
                </div>
                <div class="input-group generator-search">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="mdi mdi-magnify text-muted"></i>
                    </span>
                    <input type="text" id="searchGeneratorPremi" class="form-control border-start-0"
                        placeholder="Cari generator...">
                </div>
            </div>
        </div>

        <div class="generator-groups" id="generatorGroups">
            @foreach ($generatorGroups as $group)
                <section class="generator-group" id="generatorGroup-{{ $group["key"] }}"
                    data-group-key="{{ $group["key"] }}">
                    <div class="generator-group-header">
                        <div>
                            <div class="generator-group-title-row">
                                <span class="generator-group-icon"><i class="mdi {{ $group["icon"] }}"></i></span>
                                <div>
                                    <div class="generator-group-badge">{{ $group["badge"] }}</div>
                                    <h5 class="generator-group-title">{{ $group["title"] }}</h5>
                                </div>
                            </div>
                            <p class="generator-group-subtitle">{{ $group["subtitle"] }}</p>
                        </div>
                        <span class="generator-group-count" data-group-count>{{ count($group["generators"]) }} menu</span>
                    </div>

                    <div class="generator-menu-grid">
                        @foreach ($group["generators"] as $generator)
                            <article class="generator-menu-card"
                                style="--card-color: {{ $generator["color"] }}; --card-soft: {{ $generator["soft"] }};"
                                data-title="{{ $generator["title"] }}"
                                data-search="{{ $generator["title"] }} {{ $generator["short"] }} {{ $generator["classification"] }} {{ $generator["source"] }} {{ $generator["keywords"] }}"
                                data-category="{{ $generator["category"] }}" data-generator-key="{{ $generator["key"] }}"
                                data-status-key="{{ $generator["key"] }}">
                                <div class="generator-menu-body">
                                    <div class="generator-menu-head">
                                        <div class="generator-menu-icon"><i class="mdi {{ $generator["icon"] }}"></i></div>
                                        <span class="generator-menu-status {{ $generator["status_class"] }}">{{ $generator["status"] }}</span>
                                    </div>
                                    <h5 class="generator-menu-title">{{ $generator["title"] }}</h5>
                                    <div class="generator-menu-classification">{{ $generator["classification"] }}</div>
                                    <p class="generator-menu-description">{{ $generator["description"] }}</p>
                                    <div class="generator-menu-source">
                                        <i class="mdi mdi-database-outline"></i>
                                        <span>{{ $generator["source"] }}</span>
                                    </div>
                                    <div class="generator-period-status" data-status-panel>
                                        <div class="generator-period-status-title">
                                            <i class="mdi mdi-calendar-check-outline"></i>
                                            <span>Status UMUM/BPJS periode</span>
                                        </div>
                                        <div class="generator-status-list" data-status-content>
                                            <span class="generator-status-pill loading">
                                                <i class="mdi mdi-loading mdi-spin"></i>
                                                Memuat
                                            </span>
                                        </div>
                                    </div>

                                    <div class="generator-dependency">
                                        @if (!empty($generator["depends_on"]))
                                            <span class="generator-dependency-label">Sumber yang perlu dicek</span>
                                            <div class="generator-chip-list">
                                                @foreach ($generator["depends_on"] as $sourceKey)
                                                    @php($sourceGenerator = $generatorLookup->get($sourceKey))
                                                    <button type="button" class="generator-chip-button"
                                                        data-focus-generator="{{ $sourceKey }}">
                                                        {{ $sourceGenerator["short"] ?? $sourceKey }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @elseif (!empty($generator["feeds"]))
                                            <span class="generator-dependency-label">Dipakai oleh</span>
                                            <div class="generator-chip-list">
                                                @foreach ($generator["feeds"] as $feed)
                                                    <span class="generator-chip">{{ $feed }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="generator-dependency-label">Klasifikasi</span>
                                            <div class="generator-chip-list">
                                                <span class="generator-chip">{{ $generator["classification"] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="generator-menu-footer">
                                    <span class="generator-menu-code">{{ $generator["short"] }}</span>
                                    @if ($generator["enabled"])
                                        <a href="{{ $generator["url"] }}?periode={{ $defaultPeriode }}"
                                            class="btn-open-generator" data-base-url="{{ $generator["url"] }}">
                                            Buka <i class="mdi mdi-arrow-right"></i>
                                        </a>
                                    @else
                                        <button type="button" class="btn-open-generator" disabled>
                                            Segera Hadir <i class="mdi mdi-progress-clock"></i>
                                        </button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="generator-empty" id="generatorMenuEmpty">
            <i class="mdi mdi-magnify mdi-28px d-block mb-2"></i>
            Menu generator premi tidak ditemukan.
        </div>
    </div>
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.jsMain")
@endpush
