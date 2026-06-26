@extends("template.partials.app")

@push("style")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    <style>
        .premium-generator-page {
            --pg-navy: #102a43;
            --pg-blue: #2563eb;
            --pg-muted: #64748b;
            --pg-line: #e2e8f0;
            color: #172033;
        }

        .premium-generator-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .generator-hero {
            align-items: center;
            background:
                radial-gradient(circle at 87% 10%, rgba(45, 212, 191, .27), transparent 29%),
                linear-gradient(135deg, #102a43 0%, #164e63 58%, #0f766e 100%);
            border-radius: 17px;
            box-shadow: 0 18px 38px rgba(15, 42, 67, .18);
            color: #fff;
            display: flex;
            gap: 24px;
            justify-content: space-between;
            overflow: hidden;
            padding: 25px;
            position: relative;
        }

        .generator-hero::after {
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 50%;
            content: "";
            height: 210px;
            position: absolute;
            right: -60px;
            top: -105px;
            width: 210px;
        }

        .generator-hero-main {
            align-items: center;
            display: flex;
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .generator-hero-icon {
            align-items: center;
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 14px;
            display: flex;
            flex: 0 0 auto;
            font-size: 28px;
            height: 58px;
            justify-content: center;
            width: 58px;
        }

        .generator-eyebrow {
            color: #99f6e4;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .1em;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .generator-title {
            font-size: 23px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .generator-description {
            color: rgba(255, 255, 255, .73);
            font-size: 13px;
            margin: 0;
            max-width: 650px;
        }

        .generator-period {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 12px;
            flex: 0 0 250px;
            padding: 12px;
            position: relative;
            z-index: 1;
        }

        .generator-period label {
            color: #ccfbf1;
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .05em;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .generator-period .form-control,
        .generator-period .input-group-text {
            background: #fff;
            border: 0;
            height: 38px;
        }

        .generator-toolbar {
            align-items: center;
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: 13px;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin: 16px 0;
            padding: 14px 16px;
        }

        .generator-toolbar-title {
            color: var(--pg-navy);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .generator-toolbar-subtitle {
            color: var(--pg-muted);
            font-size: 12px;
        }

        .generator-search {
            max-width: 290px;
            width: 100%;
        }

        .generator-search .form-control,
        .generator-search .input-group-text {
            border-color: var(--pg-line);
            height: 38px;
        }

        .generator-menu-grid {
            display: grid;
            gap: 15px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .generator-menu-card {
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
            display: flex;
            flex-direction: column;
            min-height: 245px;
            overflow: hidden;
            position: relative;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .generator-menu-card:hover {
            border-color: var(--card-color);
            box-shadow: 0 16px 32px rgba(15, 23, 42, .1);
            transform: translateY(-3px);
        }

        .generator-menu-accent {
            background: var(--card-color);
            height: 4px;
            width: 100%;
        }

        .generator-menu-body {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 18px;
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
            border-radius: 12px;
            color: var(--card-color);
            display: flex;
            flex: 0 0 auto;
            font-size: 25px;
            height: 50px;
            justify-content: center;
            width: 50px;
        }

        .generator-menu-status {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            color: #15803d;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 8px;
        }

        .generator-menu-status.preparation {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
        }

        .generator-menu-title {
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
            margin: 14px 0 5px;
        }

        .generator-menu-description {
            color: var(--pg-muted);
            font-size: 12px;
            line-height: 1.55;
            margin-bottom: 14px;
        }

        .generator-menu-source {
            align-items: center;
            background: #f8fafc;
            border-radius: 8px;
            color: #475569;
            display: flex;
            font-size: 11px;
            gap: 7px;
            margin-top: auto;
            padding: 8px 10px;
        }

        .generator-menu-footer {
            align-items: center;
            border-top: 1px solid #eef2f7;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            padding: 13px 18px;
        }

        .generator-menu-code {
            color: #94a3b8;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
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
            padding: 8px 12px;
            text-decoration: none;
        }

        .btn-open-generator:hover,
        .btn-open-generator:focus {
            color: #fff;
            filter: brightness(.94);
        }

        .btn-open-generator:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }

        .generator-empty {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 13px;
            color: var(--pg-muted);
            display: none;
            padding: 35px;
            text-align: center;
        }

        @media (max-width: 1199.98px) {
            .generator-menu-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {

            .generator-hero,
            .generator-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .generator-period,
            .generator-search {
                flex-basis: auto;
                max-width: none;
                width: 100%;
            }

            .generator-menu-grid {
                grid-template-columns: 1fr;
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

        <section class="generator-hero">
            <div class="generator-hero-main">
                <div class="generator-hero-icon">
                    <i class="mdi mdi-cog-transfer-outline"></i>
                </div>
                <div>
                    <div class="generator-eyebrow">Premium Generator</div>
                    <h4 class="generator-title">Menu Generate Premi</h4>
                    <p class="generator-description">
                        Pilih generator sesuai jenis premi yang akan diproses. Setiap menu memiliki sumber data
                        dan formula perhitungan masing-masing.
                    </p>
                </div>
            </div>
        </section>

        <div class="generator-toolbar">
            <div>
                <div class="generator-toolbar-title">Daftar Generator Premi</div>
                <div class="generator-toolbar-subtitle">
                    Tersedia <span id="generatorMenuCount">13</span> menu untuk memproses premi.
                </div>
            </div>
            <div class="input-group generator-search">
                <span class="input-group-text bg-white border-end-0">
                    <i class="mdi mdi-magnify text-muted"></i>
                </span>
                <input type="text" id="searchGeneratorPremi" class="form-control border-start-0"
                    placeholder="Cari menu generate...">
            </div>
        </div>

        <div class="generator-menu-grid" id="generatorMenuGrid">

            <article class="generator-menu-card" style="--card-color: #d97706; --card-soft: #fef3c7;"
                data-title="Generate Premi BHP" data-search="generate premi bhp bahan habis pakai alat kesehatan">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-medical-bag"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi BHP</h5>
                    <p class="generator-menu-description">
                        Memproses premi dari penggunaan bahan habis pakai pada transaksi pelayanan pasien.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        Pemakaian BHP dan transaksi pelayanan
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">BHP</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateBhp") }}" class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #059669; --card-soft: #d1fae5;"
                data-title="Generate Premi Kamar" data-search="generate premi kamar rawat inap kelas tarif hari perawatan">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-hospital-building"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi Kamar</h5>
                    <p class="generator-menu-description">
                        Memproses premi dari penggunaan kamar, lama inap, penjamin, dan status pembayaran.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        Registrasi rawat inap dan penggunaan kamar
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">KAMAR</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateKamar") }}" class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #2563eb; --card-soft: #dbeafe;"
                data-title="Generate Premi Pelayanan Non Medis"
                data-search="generate premi Pelayanan Non Medis pelayanan medis mapping jenis Pelayanan Non Medis">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-hospital-building"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi Pelayanan Non Medis</h5>
                    <p class="generator-menu-description">
                        Memproses premi dari transaksi Pelayanan Non Medis berdasarkan mapping jenis Pelayanan Non Medis dan persentasenya.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        Transaksi Pelayanan Non Medis dan mapping premi
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">Pelayanan Non Medis</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generatePelayananNonMedis") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #7c3aed; --card-soft: #ede9fe;"
                data-title="Generate Premi UGD" data-search="generate premi ugd dokter umum bpjs manual pasien">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-doctor"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi UGD</h5>
                    <p class="generator-menu-description">
                        Input manual jumlah pasien UGD per dokter, jenis pelayanan, dan ploting premi.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        Data real pelayanan UGD manual
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">UGD</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateUgd") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #be123c; --card-soft: #ffe4e6;"
                data-title="Generate Premi VK" data-search="generate premi vk tindakan umum bpjs manual tindakan">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-mother-nurse"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi VK</h5>
                    <p class="generator-menu-description">
                        Input manual jumlah tindakan VK berdasarkan tindakan Khanza, jenis pelayanan, dan ploting premi.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        Data tindakan Khanza dan input VK manual
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">VK</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateVk") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #0891b2; --card-soft: #cffafe;"
                data-title="Generate Premi Operasi" data-search="generate premi operasi tindakan bedah">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-knife "></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi Operasi</h5>
                    <p class="generator-menu-description">
                        Memproses pembagian premi untuk tenaga medis yang terlibat dalam tindakan operasi.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        Pelayanan paramedis dan unit kerja Operasi
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">Operasi</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateOperasi") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #0f766e; --card-soft: #ccfbf1;"
                data-title="Generate Premi Radiologi"
                data-search="generate premi radiologi umum bpjs periksa radiologi petugas manajemen">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-radioactive-circle-outline"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi Radiologi</h5>
                    <p class="generator-menu-description">
                        Mengambil transaksi radiologi Khanza otomatis, menghitung premi petugas dan premi bersama
                        sesuai konfigurasi UMUM/BPJS.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        periksa_radiologi dan registrasi penjamin
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">Radiologi</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateRadiologi") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #7c3aed; --card-soft: #ede9fe;"
                data-title="Generate Premi Laboratorium"
                data-search="generate premi laboratorium umum bpjs detail periksa lab bagian laborat bagian rs manajemen">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-flask-outline"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi Laboratorium</h5>
                    <p class="generator-menu-description">
                        Mengambil detail_periksa_lab Khanza otomatis, menghitung premi petugas dan premi bersama
                        sesuai konfigurasi UMUM/BPJS.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        detail_periksa_lab, periksa_lab, dan registrasi penjamin
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">Laboratorium</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #dc2626; --card-soft: #fee2e2;"
                data-title="Generate Premi ICU"
                data-search="generate premi icu umum bpjs kamar inap tindakan rawat kritikal mapping">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-heart-pulse"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi ICU</h5>
                    <p class="generator-menu-description">
                        Mengambil tindakan pasien dengan riwayat ICU, mapping tindakan, tindakan kritikal, dan formula premi ICU.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        kamar_inap, rawat dokter, rawat paramedis
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">ICU</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateIcu") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #0891b2; --card-soft: #cffafe;"
                data-title="Generate Premi NICU"
                data-search="generate premi nicu umum bpjs kamar inap tindakan rawat kritikal mapping">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-baby-face-outline"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi NICU</h5>
                    <p class="generator-menu-description">
                        Mengambil tindakan pasien dengan riwayat NICU, mapping tindakan, tindakan kritikal pilihan, dan formula premi NICU.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        kamar_inap, rawat dokter, rawat paramedis
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">NICU</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateNicu") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #15803d; --card-soft: #dcfce7;"
                data-title="Generate Premi Gizi"
                data-search="generate premi gizi umum bpjs konsul diit rawat jalan rawat inap mapping">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-food-apple-outline"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi Gizi</h5>
                    <p class="generator-menu-description">
                        Mengambil tindakan rawat jalan/inap sesuai mapping Konsul dan Diit, lalu menghitung pembagian
                        UMUM/BPJS dengan formula dinamis.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        rawat dokter, rawat paramedis, mapping Konsul/Diit
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">Gizi</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateGizi") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #db2777; --card-soft: #fce7f3;"
                data-title="Generate Premi Apotek" data-search="generate premi apotek farmasi obat detail pemberian obat">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-pill"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi Apotek</h5>
                    <p class="generator-menu-description">
                        Mengambil detail_pemberian_obat, mencocokkan kode obat ke mapping Farmasi, lalu menghitung
                        formula Apotek.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        detail_pemberian_obat dan mapping Farmasi
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">Apotek</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateApotek") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #4f46e5; --card-soft: #e0e7ff;"
                data-title="Generate Premi Casemix"
                data-search="generate premi casemix bpjs klaim verifikasi hasil questionnaire indikator">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-file-chart-outline"></i></div>
                        <span class="generator-menu-status">Siap</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi Casemix</h5>
                    <p class="generator-menu-description">
                        Menghitung premi tim Casemix dari hasil verifikasi BPJS, skor indikator kinerja, dan konfigurasi pembagian tim.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-clipboard-check-outline"></i>
                        Input biaya, hasil BPJS, dan questionnaire kinerja
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">Casemix</span>
                    <a href="{{ route("backOffice.keuangan.hitungPremi.generateCasemix") }}"
                        class="btn-open-generator">
                        Buka Generator <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </article>

            <article class="generator-menu-card" style="--card-color: #475569; --card-soft: #e2e8f0;"
                data-title="Generate Premi Rumah Sakit" data-search="generate premi rumah sakit manajemen pendapatan rs">
                <div class="generator-menu-accent"></div>
                <div class="generator-menu-body">
                    <div class="generator-menu-head">
                        <div class="generator-menu-icon"><i class="mdi mdi-hospital-building"></i></div>
                        <span class="generator-menu-status preparation">Persiapan</span>
                    </div>
                    <h5 class="generator-menu-title">Generate Premi Rumah Sakit</h5>
                    <p class="generator-menu-description">
                        Memproses bagian rumah sakit dari pendapatan pelayanan sesuai kebijakan distribusi.
                    </p>
                    <div class="generator-menu-source">
                        <i class="mdi mdi-database-outline"></i>
                        Pendapatan pelayanan rumah sakit
                    </div>
                </div>
                <div class="generator-menu-footer">
                    <span class="generator-menu-code">Rumah Sakit</span>
                    <button type="button" class="btn-open-generator" disabled>
                        Segera Hadir <i class="mdi mdi-progress-clock"></i>
                    </button>
                </div>
            </article>
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
