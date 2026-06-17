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
            <li class="nav-item nav-category">Main</li>
            <li class="nav-item">
                <a href="dashboard.html" class="nav-link">
                    <i class="link-icon" data-feather="box"></i>
                    <span class="link-title">Dashboard</span>
                </a>
            </li>

            {{-- Settings --}}
            @can("SIMRS.USERS")
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
            @endcan

            {{-- Master Data --}}
            @can("SIMRS.USERS")
                <li class="nav-item nav-category">Master Data</li>
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

            {{-- Display --}}
            <li class="nav-item nav-category">Pelayanan</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#display" role="button" aria-expanded="false"
                    aria-controls="display">
                    <i class="link-icon" data-feather="monitor"></i>
                    <span class="link-title">Display</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="display">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.display.poliws") }}" class="nav-link">Poli WS</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.display.pipp") }}" class="nav-link">Pipp</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.display.kasir") }}" class="nav-link">Kasir WS</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.display.anjungan") }}" class="nav-link">Anjungan</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.display.apotek") }}" class="nav-link">Apotek</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.display.admisi") }}" class="nav-link">Admisi</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.display.igd") }}" class="nav-link">IGD</a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Petugas Panggil --}}
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#petugasPanggil" role="button"
                    aria-expanded="false" aria-controls="petugasPanggi">
                    <i class="link-icon" data-feather="volume-2"></i>
                    <span class="link-title">Petugas Panggil</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="petugasPanggil">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.petugasPanggil.poliws") }}" class="nav-link">Poli</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.petugasPanggil.pipp.pippPanggil") }}"
                                class="nav-link">Pipp</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.petugasPanggil.kasir.kasirWs") }}"
                                class="nav-link">Kasir</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("petugasPanggil.admisi.admisiPanggil") }}" class="nav-link">Admisi</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route("pelayanan.petugasPanggil.igd.igdPanggil") }}" class="nav-link">IGD</a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Back Office --}}
            <li class="nav-item nav-category">Back Office</li>
            {{-- Keuangan --}}
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
                            <a href="{{ route("pelayanan.petugasPanggil.poliws") }}" class="nav-link">Dashboard</a>
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
                            <a href="{{ route("backOffice.keuangan.hitungPremi") }}" class="nav-link">Hitung Premi</a>
                        </li>
                    </ul>
                </div>
            </li>
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
