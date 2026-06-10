<style>
    .main-wrapper .page-wrapper .page-content.has-workspace-tabs {
        padding-top: 82px;
    }

    .workspace-tabs {
        align-items: center;
        background: linear-gradient(135deg, #fff, #f8fafc);
        border: 1px solid #dbe3ee;
        border-radius: 11px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
        display: flex;
        gap: 6px;
        left: 265px;
        margin: 0;
        min-height: 46px;
        padding: 6px;
        position: fixed;
        right: 25px;
        top: 68px;
        transition: left .1s ease, right .1s ease;
        z-index: 970;
    }

    .sidebar-folded .workspace-tabs {
        left: 95px;
    }

    .workspace-tabs-label {
        align-items: center;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 8px;
        color: #1d4ed8;
        display: flex;
        flex: 0 0 auto;
        font-size: 12px;
        font-weight: 800;
        gap: 6px;
        height: 32px;
        padding: 0 10px;
        white-space: nowrap;
    }

    .workspace-tabs-count {
        align-items: center;
        background: #2563eb;
        border-radius: 999px;
        color: #fff;
        display: inline-flex;
        font-size: 10px;
        height: 18px;
        justify-content: center;
        min-width: 18px;
        padding: 0 5px;
    }

    .workspace-tabs-list {
        align-items: center;
        display: flex;
        flex: 1;
        gap: 6px;
        min-width: 0;
        overflow-x: hidden;
        overscroll-behavior-inline: contain;
        scroll-behavior: smooth;
    }

    .workspace-tab {
        align-items: center;
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        color: #475569;
        display: inline-flex;
        flex: 0 0 auto;
        max-width: 240px;
        min-height: 32px;
        position: relative;
        transition: border-color .15s ease, background .15s ease, color .15s ease, transform .15s ease;
    }

    .workspace-tab:hover {
        border-color: #93c5fd;
        color: #1d4ed8;
        transform: translateY(-1px);
    }

    .workspace-tab.is-active {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    .workspace-tab.is-active::after {
        background: #2563eb;
        border-radius: 999px 999px 0 0;
        bottom: -1px;
        content: "";
        height: 3px;
        left: 9px;
        position: absolute;
        right: 9px;
    }

    .workspace-tab-link {
        align-items: center;
        color: inherit;
        display: flex;
        gap: 7px;
        min-width: 0;
        padding: 6px 7px 6px 10px;
        text-decoration: none;
    }

    .workspace-tab-link:hover {
        color: inherit;
    }

    .workspace-tab-dot {
        background: #94a3b8;
        border-radius: 999px;
        flex: 0 0 auto;
        height: 7px;
        width: 7px;
    }

    .workspace-tab.is-active .workspace-tab-dot {
        background: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .workspace-tab-title {
        font-size: 12px;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .workspace-tab-close {
        align-items: center;
        background: transparent;
        border: 0;
        border-radius: 5px;
        color: #94a3b8;
        display: inline-flex;
        flex: 0 0 auto;
        font-size: 15px;
        height: 24px;
        justify-content: center;
        margin-right: 4px;
        padding: 0;
        width: 24px;
    }

    .workspace-tab-close:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    .workspace-tabs-actions {
        align-items: center;
        display: flex;
        flex: 0 0 auto;
        gap: 4px;
    }

    .workspace-tabs-action {
        align-items: center;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        color: #64748b;
        display: inline-flex;
        height: 31px;
        justify-content: center;
        padding: 0;
        width: 31px;
    }

    .workspace-tabs-action:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    .workspace-tabs-action:disabled {
        cursor: not-allowed;
        opacity: .4;
    }

    .workspace-tabs-action.is-danger:hover {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #dc2626;
    }

    @media (max-width: 767.98px) {
        .main-wrapper .page-wrapper .page-content.has-workspace-tabs {
            padding-top: 78px;
        }

        .workspace-tabs {
            left: 15px;
            right: 15px;
            top: 67px;
        }

        .workspace-tabs-label {
            display: none;
        }

        .workspace-tab {
            max-width: 180px;
        }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
        .workspace-tabs {
            left: 25px;
            right: 25px;
        }
    }
</style>

<div class="workspace-tabs" id="workspaceTabs" aria-label="Tab halaman">
    <div class="workspace-tabs-label">
        <i data-feather="layers"></i>
        <span>Halaman</span>
        <span class="workspace-tabs-count" id="workspaceTabsCount">0</span>
    </div>

    <button type="button" class="workspace-tabs-action" id="workspaceScrollLeft"
        title="Geser tab ke kiri" aria-label="Geser tab ke kiri">
        <i data-feather="chevron-left"></i>
    </button>

    <div class="workspace-tabs-list" id="workspaceTabsList"></div>

    <div class="workspace-tabs-actions">
        <button type="button" class="workspace-tabs-action" id="workspaceScrollRight"
            title="Geser tab ke kanan" aria-label="Geser tab ke kanan">
            <i data-feather="chevron-right"></i>
        </button>
        <button type="button" class="workspace-tabs-action is-danger" id="workspaceCloseOtherTabs"
            title="Tutup tab lainnya" aria-label="Tutup tab lainnya">
            <i data-feather="minus-square"></i>
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const storageKey = 'simrs.workspace-tabs.{{ auth()->id() ?? "guest" }}';
        const tabsElement = document.getElementById('workspaceTabsList');
        const tabsCountElement = document.getElementById('workspaceTabsCount');
        const scrollLeftButton = document.getElementById('workspaceScrollLeft');
        const scrollRightButton = document.getElementById('workspaceScrollRight');
        const closeOthersButton = document.getElementById('workspaceCloseOtherTabs');
        const maxTabs = 12;

        if (!tabsElement) {
            return;
        }

        function normalizeUrl(value) {
            try {
                const url = new URL(value, window.location.origin);

                if (url.origin !== window.location.origin) {
                    return null;
                }

                url.hash = '';

                if (url.pathname.length > 1) {
                    url.pathname = url.pathname.replace(/\/+$/, '');
                }

                return url.href;
            } catch (error) {
                return null;
            }
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function cleanTitle(value) {
            return String(value || '')
                .replace(/\s+/g, ' ')
                .trim()
                .slice(0, 70);
        }

        function fallbackTitle(url) {
            try {
                const parts = new URL(url).pathname.split('/').filter(Boolean);
                const lastPart = decodeURIComponent(parts[parts.length - 1] || 'Dashboard');

                return lastPart
                    .replace(/([a-z])([A-Z])/g, '$1 $2')
                    .replace(/[-_]+/g, ' ')
                    .replace(/\b\w/g, function(character) {
                        return character.toUpperCase();
                    });
            } catch (error) {
                return 'Halaman';
            }
        }

        function titleFromCurrentPage(currentUrl) {
            const sidebarTitle = titleFromSidebar(currentUrl);
            const candidates = [
                sidebarTitle,
                document.querySelector('.master-title')?.textContent,
                document.querySelector('.page-breadcrumb .breadcrumb-item.active')?.textContent,
                document.querySelector('.finance-master-page h5')?.textContent,
                document.querySelector('h1, h2, h3, h4, h5')?.textContent,
                document.title.replace(/\s*[-|]\s*NobleUI.*$/i, '')
            ];

            for (const candidate of candidates) {
                const title = cleanTitle(candidate);

                if (title &&
                    !/^nobleui/i.test(title) &&
                    !/^simrs\s*arsy$/i.test(title)) {
                    return title;
                }
            }

            return fallbackTitle(currentUrl);
        }

        function titleFromSidebar(url) {
            const sidebarLinks = Array.from(document.querySelectorAll('.sidebar .nav a.nav-link[href]'));
            const sidebarLink = sidebarLinks.find(function(link) {
                const href = link.getAttribute('href') || '';

                if (!href ||
                    href.startsWith('#') ||
                    href.startsWith('javascript:') ||
                    link.dataset.bsToggle) {
                    return false;
                }

                return normalizeUrl(link.href) === url;
            });

            return sidebarLink ? cleanTitle(
                sidebarLink.querySelector('.link-title')?.textContent || sidebarLink.textContent
            ) : '';
        }

        function titleFromLink(link, url) {
            const candidates = [
                link.dataset.tabTitle,
                link.closest('.mapping-menu-card')?.querySelector('.mapping-card-title')?.textContent,
                link.closest('.master-card')?.querySelector('.master-title')?.textContent,
                link.querySelector('.link-title')?.textContent,
                titleFromSidebar(url),
                link.textContent
            ];

            for (const candidate of candidates) {
                const title = cleanTitle(candidate);

                if (title &&
                    !/^buka mapping$/i.test(title) &&
                    !/^simrs\s*arsy$/i.test(title)) {
                    return title;
                }
            }

            return fallbackTitle(url);
        }

        function loadTabs() {
            try {
                const stored = JSON.parse(localStorage.getItem(storageKey) || '[]');

                if (!Array.isArray(stored)) {
                    return [];
                }

                return stored
                    .map(function(tab) {
                        const url = normalizeUrl(tab.url);

                        return url ? {
                            url: url,
                            title: cleanTitle(tab.title) || fallbackTitle(url)
                        } : null;
                    })
                    .filter(Boolean)
                    .filter(function(tab, index, tabs) {
                        return tabs.findIndex(function(item) {
                            return item.url === tab.url;
                        }) === index;
                    })
                    .slice(-maxTabs);
            } catch (error) {
                return [];
            }
        }

        let tabs = loadTabs();
        const currentUrl = normalizeUrl(window.location.href);

        tabs = tabs.map(function(tab) {
            const sidebarTitle = titleFromSidebar(tab.url);
            const invalidTitle = !tab.title ||
                /^simrs\s*arsy$/i.test(tab.title) ||
                /^nobleui/i.test(tab.title) ||
                /^buka mapping$/i.test(tab.title);

            return {
                url: tab.url,
                title: sidebarTitle || (invalidTitle ? fallbackTitle(tab.url) : tab.title)
            };
        });

        function saveTabs() {
            try {
                localStorage.setItem(storageKey, JSON.stringify(tabs.slice(-maxTabs)));
            } catch (error) {
                // Tab tetap berfungsi selama halaman aktif meskipun storage browser tidak tersedia.
            }
        }

        function addOrUpdateTab(url, title) {
            const normalizedUrl = normalizeUrl(url);

            if (!normalizedUrl) {
                return;
            }

            const existing = tabs.find(function(tab) {
                return tab.url === normalizedUrl;
            });

            if (existing) {
                existing.title = cleanTitle(title) || existing.title;
            } else {
                tabs.push({
                    url: normalizedUrl,
                    title: cleanTitle(title) || fallbackTitle(normalizedUrl)
                });

                if (tabs.length > maxTabs) {
                    const inactiveIndex = tabs.findIndex(function(tab) {
                        return tab.url !== currentUrl;
                    });

                    tabs.splice(inactiveIndex >= 0 ? inactiveIndex : 0, 1);
                }
            }

            saveTabs();
        }

        function renderTabs() {
            tabsElement.innerHTML = tabs.map(function(tab) {
                const active = tab.url === currentUrl;

                return `
                    <div class="workspace-tab ${active ? 'is-active' : ''}" data-tab-url="${escapeHtml(tab.url)}">
                        <a href="${escapeHtml(tab.url)}" class="workspace-tab-link"
                            title="${escapeHtml(tab.title)}" data-workspace-tab-link="true">
                            <span class="workspace-tab-dot"></span>
                            <span class="workspace-tab-title">${escapeHtml(tab.title)}</span>
                        </a>
                        ${tabs.length > 1 ? `
                            <button type="button" class="workspace-tab-close" data-close-tab="${escapeHtml(tab.url)}"
                                title="Tutup ${escapeHtml(tab.title)}" aria-label="Tutup ${escapeHtml(tab.title)}">
                                &times;
                            </button>
                        ` : ''}
                    </div>
                `;
            }).join('');

            const activeTab = tabsElement.querySelector('.workspace-tab.is-active');

            if (activeTab) {
                activeTab.scrollIntoView({
                    behavior: 'auto',
                    block: 'nearest',
                    inline: 'nearest'
                });
            }

            if (closeOthersButton) {
                closeOthersButton.disabled = tabs.length <= 1;
            }

            if (tabsCountElement) {
                tabsCountElement.textContent = tabs.length;
            }

            window.requestAnimationFrame(updateScrollActions);
        }

        function updateScrollActions() {
            const maxScroll = Math.max(0, tabsElement.scrollWidth - tabsElement.clientWidth);

            if (scrollLeftButton) {
                scrollLeftButton.disabled = tabsElement.scrollLeft <= 2;
            }

            if (scrollRightButton) {
                scrollRightButton.disabled = tabsElement.scrollLeft >= maxScroll - 2;
            }
        }

        if (currentUrl) {
            addOrUpdateTab(currentUrl, titleFromCurrentPage(currentUrl));
        }

        renderTabs();

        document.addEventListener('click', function(event) {
            const closeButton = event.target.closest('[data-close-tab]');

            if (closeButton) {
                event.preventDefault();
                event.stopPropagation();

                const closedUrl = normalizeUrl(closeButton.dataset.closeTab);
                const closedIndex = tabs.findIndex(function(tab) {
                    return tab.url === closedUrl;
                });

                if (closedIndex < 0 || tabs.length <= 1) {
                    return;
                }

                const wasActive = closedUrl === currentUrl;
                tabs.splice(closedIndex, 1);
                saveTabs();

                if (wasActive) {
                    const nextTab = tabs[Math.min(closedIndex, tabs.length - 1)];
                    window.location.href = nextTab.url;
                    return;
                }

                renderTabs();
                return;
            }

            const link = event.target.closest('a[href]');

            if (!link ||
                event.defaultPrevented ||
                event.button !== 0 ||
                event.ctrlKey ||
                event.metaKey ||
                event.shiftKey ||
                event.altKey ||
                link.hasAttribute('download') ||
                link.target === '_blank' ||
                link.dataset.noWorkspaceTab !== undefined ||
                link.dataset.bsToggle ||
                link.getAttribute('role') === 'button') {
                return;
            }

            const href = link.getAttribute('href') || '';

            if (!href ||
                href.startsWith('#') ||
                href.startsWith('javascript:') ||
                href.startsWith('mailto:') ||
                href.startsWith('tel:')) {
                return;
            }

            const url = normalizeUrl(link.href);

            if (!url) {
                return;
            }

            addOrUpdateTab(url, titleFromLink(link, url));
        }, true);

        closeOthersButton?.addEventListener('click', function() {
            if (!currentUrl) {
                return;
            }

            tabs = tabs.filter(function(tab) {
                return tab.url === currentUrl;
            });
            saveTabs();
            renderTabs();
        });

        scrollLeftButton?.addEventListener('click', function() {
            tabsElement.scrollBy({
                left: -Math.max(220, tabsElement.clientWidth * .65),
                behavior: 'smooth'
            });
        });

        scrollRightButton?.addEventListener('click', function() {
            tabsElement.scrollBy({
                left: Math.max(220, tabsElement.clientWidth * .65),
                behavior: 'smooth'
            });
        });

        tabsElement.addEventListener('wheel', function(event) {
            if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) {
                return;
            }

            const maxScroll = tabsElement.scrollWidth - tabsElement.clientWidth;

            if (maxScroll <= 0) {
                return;
            }

            event.preventDefault();
            tabsElement.scrollLeft += event.deltaY;
        }, {
            passive: false
        });

        tabsElement.addEventListener('scroll', updateScrollActions);
        window.addEventListener('resize', updateScrollActions);

        window.addEventListener('storage', function(event) {
            if (event.key !== storageKey) {
                return;
            }

            tabs = loadTabs();

            if (currentUrl && !tabs.some(function(tab) {
                    return tab.url === currentUrl;
                })) {
                addOrUpdateTab(currentUrl, titleFromCurrentPage(currentUrl));
            }

            renderTabs();
        });
    });
</script>
