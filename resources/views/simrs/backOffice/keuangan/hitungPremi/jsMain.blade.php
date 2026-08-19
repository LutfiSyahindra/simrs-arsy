<script>
    $(function() {
        const menuCards = $('.generator-menu-card');
        const groupSections = $('.generator-group');
        const emptyState = $('#generatorMenuEmpty');
        const searchInput = $('#searchGeneratorPremi');
        const periodInput = $('#periodeGeneratorStatus');
        const statusInfo = $('#generatorStatusInfo');
        const periodPanel = periodInput.closest('.generator-period-panel');
        const periodLockState = $('#generatorPeriodLockState');
        const setPeriodButton = $('#btnSetGeneratorPeriod');
        const releasePeriodButton = $('#btnReleaseGeneratorPeriod');
        const filterButtons = $('.generator-filter-btn');
        const generatorLinks = $('.btn-open-generator[data-base-url]');
        const periodStorageKey = 'simrs.hitung-premi.generator-status-period.{{ auth()->id() ?? "guest" }}';
        let statusRequest = null;
        let activeCategory = 'all';
        let pinnedPeriod = readPinnedPeriod();

        function isValidPeriod(periode) {
            return /^\d{4}-(0[1-9]|1[0-2])$/.test(String(periode || ''));
        }

        function readPinnedPeriod() {
            try {
                const periode = window.localStorage.getItem(periodStorageKey);

                if (isValidPeriod(periode)) {
                    return periode;
                }

                window.localStorage.removeItem(periodStorageKey);
            } catch (error) {
                // Halaman tetap dapat dipakai ketika penyimpanan browser dinonaktifkan.
            }

            return null;
        }

        function savePinnedPeriod(periode) {
            try {
                window.localStorage.setItem(periodStorageKey, periode);
                return true;
            } catch (error) {
                return false;
            }
        }

        function removePinnedPeriod() {
            try {
                window.localStorage.removeItem(periodStorageKey);
            } catch (error) {
                // Tidak ada yang perlu dilakukan jika penyimpanan browser tidak tersedia.
            }
        }

        function applyPeriodPinState() {
            const isPinned = isValidPeriod(pinnedPeriod);

            if (isPinned) {
                periodInput.val(pinnedPeriod);
            }

            periodInput.prop('disabled', isPinned);
            periodPanel.toggleClass('is-pinned', isPinned);
            setPeriodButton.toggleClass('d-none', isPinned);
            releasePeriodButton.toggleClass('d-none', !isPinned);
            periodLockState
                .toggleClass('is-pinned', isPinned)
                .html(isPinned
                    ? '<i class="mdi mdi-pin"></i> Diset pada periode ' + pinnedPeriod
                    : '<i class="mdi mdi-pin-outline"></i> Periode belum diset');
        }

        function setActiveFilter(category) {
            activeCategory = category || 'all';
            filterButtons.each(function() {
                const button = $(this);
                const isActive = button.attr('data-filter-category') === activeCategory;

                button.toggleClass('active', isActive);
                button.attr('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        function filterGeneratorMenu() {
            const keyword = searchInput.val().toLowerCase().trim();
            let visibleCount = 0;

            menuCards.each(function() {
                const card = $(this);
                const category = card.attr('data-category');
                const haystack = `${card.data('title')} ${card.data('search') || ''}`.toLowerCase();
                const matchesCategory = activeCategory === 'all' || category === activeCategory;
                const matchesKeyword = !keyword || haystack.includes(keyword);
                const isVisible = matchesCategory && matchesKeyword;

                card.attr('data-filter-visible', isVisible ? 'true' : 'false');
                card.toggle(isVisible);
                if (isVisible) {
                    visibleCount++;
                }
            });

            groupSections.each(function() {
                const group = $(this);
                const visibleCards = group.find('.generator-menu-card[data-filter-visible="true"]').length;

                group.toggle(visibleCards > 0);
                group.find('[data-group-count]').text(`${visibleCards} menu`);
            });

            $('#generatorMenuCount').text(visibleCount);
            emptyState.toggle(visibleCount === 0);
        }

        function scrollToElement(element) {
            if (!element.length) {
                return;
            }

            $('html, body').animate({
                scrollTop: element.offset().top - 90
            }, 260);
        }

        function statusLoading() {
            menuCards.each(function() {
                $(this).find('[data-status-content]').html(
                    '<span class="generator-status-pill loading">' +
                    '<i class="mdi mdi-loading mdi-spin"></i> Memuat</span>'
                );
            });
        }

        function statusPill(label, status) {
            if (!status) {
                return '<span class="generator-status-pill unavailable">' +
                    '<i class="mdi mdi-minus-circle-outline"></i> ' + label + ': Tidak tersedia</span>';
            }

            if (status.generated) {
                const count = Number(status.count || 0);
                const locked = Number(status.locked_count || 0);
                const lockedText = locked > 0 ? ' / ' + locked + ' kunci' : '';

                return '<span class="generator-status-pill generated" title="' + count + ' data tersimpan' + lockedText + '">' +
                    '<i class="mdi mdi-check-circle-outline"></i> ' + label + ': ' + count + ' data</span>';
            }

            return '<span class="generator-status-pill missing">' +
                '<i class="mdi mdi-alert-circle-outline"></i> ' + label + ': Belum</span>';
        }

        function renderStatus(card, status) {
            const content = card.find('[data-status-content]');

            if (!status) {
                content.html(
                    '<span class="generator-status-pill unavailable">' +
                    '<i class="mdi mdi-minus-circle-outline"></i> Belum ada modul</span>'
                );
                return;
            }

            if (status.available === false) {
                content.html(
                    '<span class="generator-status-pill unavailable">' +
                    '<i class="mdi mdi-database-off-outline"></i> Status belum tersedia</span>'
                );
                return;
            }

            if (status.supports_split) {
                content.html(statusPill('UMUM', status.umum) + statusPill('BPJS', status.bpjs));
                return;
            }

            content.html(statusPill(status.label || 'Mandiri', status));
        }

        function updateGeneratorLinks() {
            const periode = periodInput.val();

            generatorLinks.each(function() {
                const link = $(this);
                const url = new URL(link.attr('data-base-url'), window.location.origin);

                if (periode) {
                    url.searchParams.set('periode', periode);
                }

                link.attr('href', url.toString());
            });
        }

        function loadGeneratorStatus() {
            const periode = periodInput.val();
            const statusUrl = periodInput.attr('data-status-url');

            updateGeneratorLinks();

            if (!periode || !statusUrl) {
                statusInfo.text('Pilih periode untuk melihat status generate.');
                return;
            }

            if (statusRequest) {
                statusRequest.abort();
            }

            statusLoading();
            statusInfo.text('Memeriksa status UMUM/BPJS periode ' + periode + '...');

            statusRequest = $.get(statusUrl, {
                periode: periode
            }).done(function(response) {
                const data = response.data || {};

                menuCards.each(function() {
                    const card = $(this);
                    renderStatus(card, data[card.attr('data-status-key')]);
                });

                statusInfo.text(
                    'Status UMUM/BPJS periode ' + periode + ' sudah diperbarui.' +
                    (pinnedPeriod === periode ? ' Periode ini tetap digunakan sampai set dilepaskan.' : '')
                );
            }).fail(function(xhr, status) {
                if (status === 'abort') {
                    return;
                }

                menuCards.each(function() {
                    $(this).find('[data-status-content]').html(
                        '<span class="generator-status-pill unavailable">' +
                        '<i class="mdi mdi-alert-outline"></i> Status gagal dimuat</span>'
                    );
                });
                statusInfo.text('Status periode ' + periode + ' belum bisa dimuat.');
            }).always(function() {
                statusRequest = null;
            });
        }

        searchInput.on('input', filterGeneratorMenu);
        periodInput.on('change', loadGeneratorStatus);

        setPeriodButton.on('click', function() {
            const periode = String(periodInput.val() || '');

            if (!isValidPeriod(periode)) {
                Swal.fire('Periode belum dipilih', 'Pilih periode status generate terlebih dahulu.', 'warning');
                return;
            }

            if (!savePinnedPeriod(periode)) {
                Swal.fire('Periode gagal diset', 'Penyimpanan browser tidak tersedia. Periksa pengaturan browser lalu coba kembali.', 'error');
                return;
            }

            pinnedPeriod = periode;
            applyPeriodPinState();
            loadGeneratorStatus();

            Swal.fire({
                icon: 'success',
                title: 'Periode berhasil diset',
                text: 'Periode ' + periode + ' tidak akan berubah saat halaman direfresh.',
                timer: 1800,
                showConfirmButton: false
            });
        });

        releasePeriodButton.on('click', function() {
            const releasedPeriod = pinnedPeriod;

            removePinnedPeriod();
            pinnedPeriod = null;
            applyPeriodPinState();
            updateGeneratorLinks();
            statusInfo.text(
                'Set periode ' + releasedPeriod + ' sudah dilepaskan. Periode sekarang dapat diubah.'
            );

            Swal.fire({
                icon: 'success',
                title: 'Set periode dilepaskan',
                text: 'Periode status generate sekarang dapat diubah kembali.',
                timer: 1600,
                showConfirmButton: false
            });
        });

        filterButtons.on('click', function() {
            setActiveFilter($(this).attr('data-filter-category'));
            filterGeneratorMenu();
        });

        $('[data-jump-group]').on('click', function() {
            const target = $(`#generatorGroup-${$(this).attr('data-jump-group')}`);

            setActiveFilter('all');
            searchInput.val('');
            filterGeneratorMenu();
            scrollToElement(target);
        });

        $('[data-focus-generator]').on('click', function() {
            const sourceKey = $(this).attr('data-focus-generator');
            const sourceCard = $(`[data-generator-key="${sourceKey}"]`);

            if (!sourceCard.length) {
                return;
            }

            setActiveFilter('all');
            searchInput.val('');
            filterGeneratorMenu();
            scrollToElement(sourceCard);

            sourceCard.addClass('is-highlighted');
            window.setTimeout(function() {
                sourceCard.removeClass('is-highlighted');
            }, 1300);
        });

        applyPeriodPinState();
        filterGeneratorMenu();
        loadGeneratorStatus();
    });
</script>
