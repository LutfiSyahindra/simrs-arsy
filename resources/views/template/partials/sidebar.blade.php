<!-- partial:partials/_sidebar.html -->
<nav class="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            SIMRS<span>Arsy</span>
        </a>
        <div class="sidebar-toggler not-active">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    <div class="sidebar-body">
        <ul class="nav">
            {{-- Main --}}
            @can("SIMRS.DASHBOARD")
                <li class="nav-item nav-category">Main</li>
                <li class="nav-item">
                    <a href="dashboard.html" class="nav-link">
                        <i class="link-icon" data-feather="box"></i>
                        <span class="link-title">Dashboard</span>
                    </a>
                </li>
            @endcan

            {{-- Settings --}}
            {{-- @can("SIMRS.SETTINGS") --}}
                <li class="nav-item nav-category">Settings</li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#users" role="button" aria-expanded="false"
                        aria-controls="users">
                        <i class="link-icon" data-feather="users"></i>
                        <span class="link-title">Auth</span>
                        <i class="link-arrow" data-feather="chevron-down"></i>
                    </a>
                    <div class="collapse" id="users">
                        <ul class="nav sub-menu">
                            <li class="nav-item">
                                <a href="{{ route("users.index") }}" class="nav-link">Users</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route("roles.role") }}" class="nav-link">Role</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route("permissions.permissions") }}" class="nav-link">Permission</a>
                            </li>
                        </ul>
                    </div>
                </li>
            {{-- @endcan --}}

            {{-- Master Data --}}
            @can("SIMRS.MASTER_DATA")
                <li class="nav-item nav-category">Master Data</li>
                @can("SIMRS.MASTER_DATA.KEUANGAN")
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#master_keuangan" role="button"
                            aria-expanded="false" aria-controls="master_keuangan">
                            <i class="link-icon" data-feather="users"></i>
                            <span class="link-title">Master Keuangan</span>
                            <i class="link-arrow" data-feather="chevron-down"></i>
                        </a>
                        <div class="collapse" id="master_keuangan">
                            <ul class="nav sub-menu">
                                <li class="nav-item">
                                    <a href="{{ route("masterData.keuangan.jabatan") }}" class="nav-link">Jabatan</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("masterData.keuangan.profesi") }}" class="nav-link">Profesi</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("masterData.keuangan.tunjangan") }}" class="nav-link">Jenis Tunjangan</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("masterData.keuangan.potongan") }}" class="nav-link">Jenis Potongan</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("masterData.keuangan.skor") }}" class="nav-link">Master Skor</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("masterData.keuangan.unit") }}" class="nav-link">Master Unit</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("masterData.keuangan.jnsTindakan") }}" class="nav-link">Master Jenis
                                        Tindakan</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("masterData.keuangan.jnsPremi") }}" class="nav-link">Master Jenis
                                        Premi</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("masterData.keuangan.plotingPremi") }}" class="nav-link">Master Ploting
                                        Premi</a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#mapping_data" role="button" aria-expanded="false"
                            aria-controls="mapping_data">
                            <i class="link-icon" data-feather="map"></i>
                            <span class="link-title">Mapping</span>
                            <i class="link-arrow" data-feather="chevron-down"></i>
                        </a>
                        <div class="collapse" id="mapping_data">
                            <ul class="nav sub-menu">
                                <li class="nav-item">
                                    <a href="{{ route("masterData.mapping") }}" class="nav-link">Mapping Data</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endcan
            @endcan

            {{-- Display --}}
            @can("SIMRS.PELAYANAN")
                <li class="nav-item nav-category">Pelayanan</li>
                @can("SIMRS.PELAYANAN.DISPLAY")
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#display" role="button" aria-expanded="false"
                            aria-controls="display">
                            <i class="link-icon" data-feather="monitor"></i>
                            <span class="link-title">Display</span>
                            <i class="link-arrow" data-feather="chevron-down"></i>
                        </a>
                        <div class="collapse" id="display">
                            <ul class="nav sub-menu">
                                @can("SIMRS.PELAYANAN.DISPLAY.POLIWS")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.display.poliws") }}" class="nav-link">Poli WS</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.DISPLAY.PIPP")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.display.pipp") }}" class="nav-link">Pipp</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.DISPLAY.KASIR")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.display.kasir") }}" class="nav-link">Kasir WS</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.DISPLAY.ADMISI")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.display.anjungan") }}" class="nav-link">Anjungan</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.DISPLAY.Apotek")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.display.apotek") }}" class="nav-link">Apotek</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.DISPLAY.ADMISI")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.display.admisi") }}" class="nav-link">Admisi</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.DISPLAY.IGD")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.display.igd") }}" class="nav-link">IGD</a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcan

                {{-- Petugas Panggil --}}
                @can("SIMRS.PELAYANAN.PETUGAS_PANGGIL")
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#petugasPanggil" role="button"
                            aria-expanded="false" aria-controls="petugasPanggi">
                            <i class="link-icon" data-feather="volume-2"></i>
                            <span class="link-title">Petugas Panggil</span>
                            <i class="link-arrow" data-feather="chevron-down"></i>
                        </a>
                        <div class="collapse" id="petugasPanggil">
                            <ul class="nav sub-menu">
                                @can("SIMRS.PELAYANAN.PETUGAS_PANGGIL.POLIWS")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.petugasPanggil.poliws") }}" class="nav-link">Poli</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.PETUGAS_PANGGIL.PIPP")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.petugasPanggil.pipp.pippPanggil") }}"
                                            class="nav-link">Pipp</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.PETUGAS_PANGGIL.KASIR")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.petugasPanggil.kasir.kasirWs") }}"
                                            class="nav-link">Kasir</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.PETUGAS_PANGGIL.ADMISI")
                                    <li class="nav-item">
                                        <a href="{{ route("petugasPanggil.admisi.admisiPanggil") }}" class="nav-link">Admisi</a>
                                    </li>
                                @endcan
                                @can("SIMRS.PELAYANAN.PETUGAS_PANGGIL.IGD")
                                    <li class="nav-item">
                                        <a href="{{ route("pelayanan.petugasPanggil.igd.igdPanggil") }}" class="nav-link">IGD</a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcan
            @endcan

            {{-- Back Office --}}
            @can("SIMRS.BACK_OFFICE")
                <li class="nav-item nav-category">Back Office</li>
                {{-- Keuangan --}}
                @can("SIMRS.BACK_OFFICE.KEUANGAN")
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#keuangan" role="button" aria-expanded="false"
                            aria-controls="keuangan">
                            <i class="link-icon" data-feather="dollar-sign"></i>
                            <span class="link-title">Keuangan</span>
                            <i class="link-arrow" data-feather="chevron-down"></i>
                        </a>
                        <div class="collapse" id="keuangan">
                            <ul class="nav sub-menu">
                                <li class="nav-item">
                                    <a href="{{ route("backOffice.keuangan.dashboard") }}" class="nav-link">Dashboard</a>
                                </li>
                                {{-- <li class="nav-item">
                            <a href="{{ route("masterData.keuangan.gapok") }}" class="nav-link">Gaji
                                Pokok</a>
                        </li> --}}
                                {{-- <li class="nav-item">
                            <a href="{{ route("masterData.keuangan.tunjanganPegawai") }}" class="nav-link">Tunjangan
                                Pegawai</a>
                        </li> --}}
                                <li class="nav-item">
                                    <a href="{{ route("backOffice.keuangan.premi") }}" class="nav-link">Tindakan</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("backOffice.keuangan.penggajian") }}" class="nav-link">Penggajian</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}" class="nav-link">Hitung
                                        Premi</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endcan
            @endcan
        </ul>
    </div>
</nav>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let searchInput = document.getElementById("navbarForm");
        if (!searchInput) return; // kalau elemen ga ada, hentikan

        searchInput.addEventListener("keyup", function() {
            let query = this.value.toLowerCase().trim();
            let menuItems = document.querySelectorAll(".sidebar-body .nav-item");

            menuItems.forEach(function(item) {
                let text = item.innerText.toLowerCase();

                if (item.classList.contains("nav-category")) {
                    if (query === "") {
                        item.style.display = "";
                    } else {
                        let nextMenu = item.nextElementSibling;
                        if (nextMenu && nextMenu.style.display !== "none") {
                            item.style.display = "";
                        } else {
                            item.style.display = "none";
                        }
                    }
                } else {
                    if (query === "") {
                        item.style.display = "";
                    } else if (text.includes(query)) {
                        item.style.display = "";
                        let parentCollapse = item.closest(".collapse");
                        if (parentCollapse) {
                            parentCollapse.classList.add("show");
                        }
                    } else {
                        item.style.display = "none";
                    }
                }
            });
        });
    });
</script>

<!-- partial -->
