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

            {{-- Lainnya --}}
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
