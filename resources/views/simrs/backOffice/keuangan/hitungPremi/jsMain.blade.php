<script>
    $(function() {
        const menuCards = $('.generator-menu-card');
        const groupSections = $('.generator-group');
        const emptyState = $('#generatorMenuEmpty');
        const searchInput = $('#searchGeneratorPremi');
        const periodInput = $('#periodeGeneratorStatus');
        const statusInfo = $('#generatorStatusInfo');
        const filterButtons = $('.generator-filter-btn');
        const generatorLinks = $('.btn-open-generator[data-base-url]');
        let statusRequest = null;
        let activeCategory = 'all';

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

                statusInfo.text('Status UMUM/BPJS periode ' + periode + ' sudah diperbarui.');
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

        filterGeneratorMenu();
        loadGeneratorStatus();
    });
</script>
