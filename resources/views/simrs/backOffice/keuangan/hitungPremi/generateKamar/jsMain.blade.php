<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const generateModal = new bootstrap.Modal(document.getElementById('modalGenerateBhp'));
        const detailModal = new bootstrap.Modal(document.getElementById('modalDetailBhp'));
        const detailModalElement = $('#modalDetailBhp');
        const detailPenjaminSelect = $('#filterDetailPenjamin').select2({
            dropdownParent: detailModalElement,
            width: '100%',
            minimumResultsForSearch: 5,
            language: {
                noResults: function() {
                    return 'Penjamin tidak ditemukan';
                },
                searching: function() {
                    return 'Mencari penjamin...';
                }
            }
        });
        const detailKamarSelect = $('#filterDetailKamar').select2({
            dropdownParent: detailModalElement,
            width: '100%',
            minimumResultsForSearch: 0,
            language: {
                noResults: function() {
                    return 'Kamar tidak ditemukan';
                },
                searching: function() {
                    return 'Mencari kamar...';
                }
            }
        });
        const typeConfig = {
            umum: {
                label: 'Umum',
                description: 'Perhitungan Kamar pasien rawat inap non-BPJS',
                criteria: [
                    'Status lanjut pasien adalah rawat inap.',
                    'Penjamin selain BPJS Kesehatan dan kode kosong dan -.',
                    'Piutang belum lunas untuk Asuransi dan Sudah Bayar untuk penjamin Umum.'
                ]
            },
            bpjs: {
                label: 'BPJS',
                description: 'Perhitungan Kamar pasien rawat inap dengan kode penjamin BPJ',
                criteria: [
                    'Status lanjut pasien adalah rawat inap.',
                    'Kode penjamin pasien adalah BPJ.',
                    'Piutang pasien berstatus Belum Lunas.',
                    'Data sumber diambil dari satu bulan sebelum periode generate.'
                ]
            }
        };
        let activeType = 'umum';

        function formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(value) || 0);
        }

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function formatKamarLabel(kodeKamar) {
            const parts = String(kodeKamar || '-').split('.');

            if (parts.length < 2) {
                return parts[0];
            }

            return parts.join(' / ');
        }

        function getErrorMessage(xhr, fallback) {
            const errors = xhr.responseJSON?.errors;

            if (errors) {
                const firstError = Object.values(errors).flat()[0];
                if (firstError) {
                    return firstError;
                }
            }

            return xhr.responseJSON?.message || fallback;
        }

        function getSourcePeriod(periode, type) {
            const parts = String(periode || '').split('-').map(Number);

            if (parts.length !== 2 || !parts[0] || !parts[1]) {
                return '-';
            }

            const source = new Date(parts[0], parts[1] - 1 - (type === 'bpjs' ? 1 : 0), 1);

            return source.getFullYear() + '-' +
                String(source.getMonth() + 1).padStart(2, '0');
        }

        function updateSourcePeriodInfo() {
            const sourcePeriod = getSourcePeriod($('#periodeBhp').val(), activeType);
            const message = activeType === 'bpjs' ?
                'Periode hasil tetap ' + ($('#periodeBhp').val() || '-') +
                    ', data BPJS dibaca dari bulan sebelumnya: ' + sourcePeriod + '.' :
                'Data Umum dibaca dari periode yang dipilih: ' + sourcePeriod + '.';

            $('#periodeSourceHelp').text(message);
        }

        function updateTypeDisplay() {
            const config = typeConfig[activeType];

            $('.btn-type-bhp').toggleClass('active', false);
            $(`.btn-type-bhp[data-type="${activeType}"]`).addClass('active');
            $('#activeTypeBadge').toggleClass('is-bpjs', activeType === 'bpjs');
            $('#activeTypeBadgeText').text(config.label);
            $('#actionActiveType').text(config.label);
            $('#heroActiveType').text(config.label);
            $('#activeTypeDescription span').text(config.description);
            $('#btnGenerateBhpLabel').text('Generate Kamar ' + config.label);
            $('#resultBhpTitle').text('Hasil Generate Kamar ' + config.label);
            updateSourcePeriodInfo();

            $('#activeCriteriaList').html(config.criteria.map(function(criteria) {
                return `
                    <div class="bhp-criteria-item">
                        <i class="mdi mdi-check-circle"></i>
                        <span>${escapeHtml(criteria)}</span>
                    </div>
                `;
            }).join(''));
        }

        function loadSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateKamar.summary") }}",
                type: 'GET',
                data: {
                    periode: $('#periodeBhp').val(),
                    jenis_kamar: activeType
                },
                success: function(response) {
                    const data = response.data || {};
                    $('#summaryJumlahBhp').text((data.jumlah_kamar || 0) + ' kamar');
                    $('#summaryJumlahLamaInap').text((data.jumlah_lama_inap || 0) + ' hari');
                    $('#summaryNominalBhp').text(formatRupiah(data.nominal_hitung));
                    $('#summaryTotalBhp').text(formatRupiah(data.total_lama_inap));
                    $('#summaryPeriode').text(
                        (data.periode || '-') + ' / ' + (data.jenis_kamar_label || '-') +
                        ' / Sumber ' + (data.periode_sumber || '-')
                    );
                    $('#summaryFormulaBhp').text(
                        (data.jumlah_lama_inap || 0) + ' hari x ' +
                        formatRupiah(data.nominal_hitung)
                    );
                    updateLockState(data);
                },
                error: function() {
                    $('#summaryJumlahBhp').text('0 kamar');
                    $('#summaryJumlahLamaInap').text('0 hari');
                    $('#summaryNominalBhp').text('Rp 0');
                    $('#summaryTotalBhp').text('Rp 0');
                    $('#summaryPeriode').text('-');
                    $('#summaryFormulaBhp').text('0 hari x Rp 0');
                    updateLockState({
                        is_locked: false,
                        jumlah_kamar: 0
                    });
                }
            });
        }

        function updateLockState(data) {
            const isLocked = Boolean(data.is_locked);
            const hasResult = Number(data.jumlah_kamar || 0) > 0;
            const status = $('#activeBhpLockStatus');
            const generateButton = $('#btnGenerateBhp');
            const lockCard = $('#summaryLockCard');
            const lockedBy = data.locked_by_name || '';
            const lockedAt = data.locked_at || '';

            status.toggleClass('d-none', !hasResult);
            status.toggleClass('is-locked', isLocked);
            status.toggleClass('is-open', !isLocked);
            status.html(isLocked ?
                '<i class="mdi mdi-lock"></i><span>Terkunci</span>' :
                '<i class="mdi mdi-lock-open-variant-outline"></i><span>Belum dikunci</span>'
            );

            generateButton.prop('disabled', isLocked);
            generateButton.attr(
                'title',
                isLocked ?
                'Data sudah dikunci. Admin harus membuka kunci sebelum generate ulang.' :
                ''
            );
            $('#btnGenerateBhpLabel').text(
                isLocked ?
                'Data Kamar Terkunci' :
                'Generate Kamar ' + typeConfig[activeType].label
            );

            lockCard.toggleClass('is-locked', isLocked);
            $('#summaryLockIcon').attr(
                'class',
                isLocked ? 'mdi mdi-lock' : 'mdi mdi-lock-open-variant-outline'
            );

            if (!hasResult) {
                $('#summaryLockValue').text('Belum Ada Data');
                $('#summaryLockNote').text('Generate data terlebih dahulu');
                $('#heroActiveStatus').text('Belum ada hasil generate');
                $('#actionStatusMessage').text(
                    'Belum ada hasil generate untuk konteks yang dipilih.'
                );
                return;
            }

            if (isLocked) {
                $('#summaryLockValue').text('Terkunci');
                $('#summaryLockNote').text(
                    [lockedBy, lockedAt].filter(Boolean).join(' / ') || 'Data terlindungi'
                );
                $('#heroActiveStatus').text('Data terkunci dan tidak dapat digenerate ulang');
                $('#actionStatusMessage').html(
                    '<i class="mdi mdi-lock me-1"></i>' +
                    'Data sudah dikunci' +
                    (lockedBy ? ' oleh <strong>' + escapeHtml(lockedBy) + '</strong>' : '') +
                    '.'
                );
                return;
            }

            $('#summaryLockValue').text('Belum Dikunci');
            $('#summaryLockNote').text('Data masih dapat digenerate ulang');
            $('#heroActiveStatus').text('Data tersedia dan masih dapat diperbarui');
            $('#actionStatusMessage').html(
                '<i class="mdi mdi-check-circle-outline me-1"></i>' +
                'Hasil tersedia dan masih dapat digenerate ulang.'
            );
        }

        const tableGenerateBhp = $('#tableGenerateBhp').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50],
            order: [
                [1, 'desc']
            ],
            dom: "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'i>>" +
                'rt' +
                "<'row g-2 align-items-center mt-3'<'col-12 col-md-6'i><'col-12 col-md-6 d-flex justify-content-md-end'p>>",
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Belum ada hasil generate',
                zeroRecords: `
                    <div class="py-4 text-center text-muted">
                        <i class="mdi mdi-database-search-outline mdi-36px d-block mb-2 text-primary"></i>
                        <strong class="d-block text-dark">Belum ada hasil kamar</strong>
                        <span class="small">Pilih jenis dan periode, lalu lakukan generate pertama.</span>
                    </div>
                `,
                processing: 'Memuat data...',
                paginate: {
                    previous: '<i class="mdi mdi-chevron-left"></i>',
                    next: '<i class="mdi mdi-chevron-right"></i>'
                }
            },
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateKamar.table") }}",
                type: 'GET',
                data: function(data) {
                    data.periode = $('#periodeBhp').val();
                    data.jenis_kamar = activeType;
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    data: 'periode',
                    name: 'periode',
                    render: function(data) {
                        return '<span class="fw-bold text-dark">' + escapeHtml(data || '-') +
                            '</span>';
                    }
                },
                {
                    data: 'jenis_kamar_label',
                    name: 'jenis_kamar',
                    render: function(data, type, row) {
                        const badgeClass = row.jenis_kamar === 'bpjs' ?
                            'bg-primary-subtle text-primary' :
                            'bg-success-subtle text-success';

                        return '<span class="badge ' + badgeClass + '">' +
                            escapeHtml(data || '-') + '</span>';
                    }
                },
                {
                    data: 'jumlah_kamar',
                    name: 'jumlah_kamar',
                    className: 'text-center',
                    render: function(data) {
                        return new Intl.NumberFormat('id-ID').format(Number(data) || 0) +
                            ' kamar';
                    }
                },
                {
                    data: 'jumlah_lama_inap',
                    name: 'jumlah_lama_inap',
                    className: 'text-center',
                    render: function(data) {
                        return new Intl.NumberFormat('id-ID').format(Number(data) || 0) +
                            ' hari';
                    }
                },
                {
                    data: 'nominal_hitung',
                    name: 'nominal_hitung',
                    className: 'text-end bhp-currency',
                    render: function(data) {
                        return formatRupiah(data);
                    }
                },
                {
                    data: 'total_lama_inap',
                    name: 'total_lama_inap',
                    className: 'text-end bhp-currency',
                    render: function(data) {
                        return formatRupiah(data);
                    }
                },
                {
                    data: 'generated_at',
                    name: 'generated_at',
                    defaultContent: '-'
                },
                {
                    data: 'is_locked',
                    name: 'is_locked',
                    className: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        if (!data) {
                            return `
                                <span class="bhp-lock-status is-open">
                                    <i class="mdi mdi-lock-open-variant-outline"></i>
                                    Belum Dikunci
                                </span>
                            `;
                        }

                        const lockInfo = [
                            row.locked_by_name || '',
                            row.locked_at || ''
                        ].filter(Boolean).join(' / ');

                        return `
                            <span class="bhp-lock-status is-locked"
                                title="${escapeHtml(lockInfo || 'Data terkunci')}">
                                <i class="mdi mdi-lock"></i>
                                Terkunci
                            </span>
                        `;
                    }
                },
                {
                    data: 'generate_by_name',
                    name: 'generate_by_name',
                    className: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                                <span class="bhp-users users">
                                    <i class="mdi mdi-account-check-outline"></i>
                                    ${escapeHtml(data || '-')}
                                </span>
                            `;
                    }
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ]
        });

        $('#periodeBhp').datepicker({
            format: 'yyyy-mm',
            startView: 'months',
            minViewMode: 'months',
            autoclose: true,
            todayHighlight: true,
            orientation: 'bottom auto'
        }).on('changeDate', function() {
            updatePeriodDisplay();
            tableGenerateBhp.ajax.reload();
            loadSummary();
        });

        function updatePeriodDisplay() {
            const periode = $('#periodeBhp').val() || '-';
            $('#heroActivePeriod').text(periode);
            $('#actionActivePeriod').text(periode);
            updateSourcePeriodInfo();
        }

        $('.btn-type-bhp').on('click', function() {
            activeType = $(this).data('type');
            updateTypeDisplay();
            tableGenerateBhp.ajax.reload();
            loadSummary();
        });

        $('#searchBhp').on('keyup', function() {
            tableGenerateBhp.search(this.value).draw();
        });

        $('#btnRefreshBhp').on('click', function() {
            tableGenerateBhp.ajax.reload(null, false);
            loadSummary();
        });

        function refreshBhpData() {
            tableGenerateBhp.ajax.reload(null, false);
            loadSummary();
        }

        $('#tableGenerateBhp').on('click', '.btn-lock-kamar', function() {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'warning',
                title: 'Kunci data kamar?',
                text: 'Setelah dikunci, data tidak dapat digenerate ulang sampai Admin membuka kunci.',
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-lock me-1"></i> Kunci Data',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d97706'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateKamar.lock", ["id" => "__ID__"]) }}"
                        .replace('__ID__', id),
                    type: 'POST',
                    success: function(response) {
                        refreshBhpData();
                        Swal.fire({
                            icon: 'success',
                            title: 'Data Dikunci',
                            text: response.message ||
                                'Data kamar berhasil dikunci.',
                            timer: 1600,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengunci',
                            text: getErrorMessage(xhr,
                                'Data kamar gagal dikunci.')
                        });
                    }
                });
            });
        });

        $('#tableGenerateBhp').on('click', '.btn-unlock-kamar', function() {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'question',
                title: 'Buka kunci data kamar?',
                text: 'Data dapat digenerate ulang setelah kunci dibuka.',
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-lock-open-variant-outline me-1"></i> Buka Kunci',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#059669'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateKamar.unlock", ["id" => "__ID__"]) }}"
                        .replace('__ID__', id),
                    type: 'POST',
                    success: function(response) {
                        refreshBhpData();
                        Swal.fire({
                            icon: 'success',
                            title: 'Kunci Dibuka',
                            text: response.message ||
                                'Kunci data kamar berhasil dibuka.',
                            timer: 1600,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Membuka Kunci',
                            text: getErrorMessage(xhr,
                                'Kunci data kamar gagal dibuka.')
                        });
                    }
                });
            });
        });

        $('#btnGenerateBhp').on('click', function() {
            const periode = $('#periodeBhp').val();
            const config = typeConfig[activeType];

            if (!periode) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Periode belum dipilih',
                    text: 'Pilih periode kamar terlebih dahulu.'
                });
                return;
            }

            $('#modalGenerateBhpLabel').text('Generate Kamar ' + config.label);
            $('#jenisGenerateBhp').val(activeType);
            $('#jenisGenerateBhpLabel').val(config.label);
            $('#periodeGenerateBhp').val(periode);
            $('#periodeGenerateSourceInfo').text(
                'Data sumber: ' + getSourcePeriod(periode, activeType) +
                (activeType === 'bpjs' ? ' (bulan sebelumnya).' : '.')
            );
            $('#nominalHitungBhp').val('').removeClass('is-invalid');
            $('#nominalHitungBhpError').text('');
            generateModal.show();

            setTimeout(function() {
                $('#nominalHitungBhp').trigger('focus');
            }, 250);
        });

        $('#nominalHitungBhp').on('input', function() {
            const numeric = this.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
            this.value = numeric ? new Intl.NumberFormat('id-ID').format(Number(numeric)) : '';
            $(this).removeClass('is-invalid');
        });

        $('#formGenerateBhp').on('submit', function(event) {
            event.preventDefault();

            const submitButton = $('#btnSubmitGenerateBhp');
            const originalHtml = submitButton.html();
            const nominal = $('#nominalHitungBhp').val().replace(/\D/g, '');
            const jenisBhp = $('#jenisGenerateBhp').val();
            const typeLabel = typeConfig[jenisBhp].label;

            if (!nominal || Number(nominal) < 1) {
                $('#nominalHitungBhp')
                    .addClass('is-invalid')
                    .trigger('focus');
                $('#nominalHitungBhpError').text('Nominal hitung wajib lebih dari Rp 0.');
                return;
            }

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateKamar.store") }}",
                type: 'POST',
                data: {
                    periode: $('#periodeGenerateBhp').val(),
                    jenis_kamar: jenisBhp,
                    nominal_hitung: nominal
                },
                beforeSend: function() {
                    submitButton.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-1"></span> Generate...'
                    );
                },
                success: function(response) {
                    generateModal.hide();
                    tableGenerateBhp.ajax.reload(null, false);
                    loadSummary();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message || ('Kamar ' + typeLabel +
                            ' berhasil digenerate.'),
                        timer: 1800,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Generate gagal',
                        text: getErrorMessage(
                            xhr,
                            'Kamar ' + typeLabel + ' gagal digenerate.'
                        )
                    });
                },
                complete: function() {
                    submitButton.prop('disabled', false).html(originalHtml);
                }
            });
        });

        let detailBhpItems = [];
        let activePenjaminFilter = 'all';
        let activeKamarFilter = 'all';
        let detailNominalHitung = 0;

        function detailMatchesFilters(detail, keyword) {
            const searchable = [
                detail.no_rawat,
                detail.tgl_masuk,
                detail.kd_kamar,
                detail.kd_pj,
                detail.nama_penjamin
            ].join(' ').toLowerCase();

            const matchesSearch = searchable.indexOf(keyword) !== -1;
            const matchesPenjamin = activePenjaminFilter === 'all' ||
                String(detail.kd_pj || '-') === activePenjaminFilter;
            const matchesKamar = activeKamarFilter === 'all' ||
                String(detail.kd_kamar || '-') === activeKamarFilter;

            return matchesSearch && matchesPenjamin && matchesKamar;
        }

        function updateDetailVisibleCount() {
            const visible = $('#detailBhpRows tr[data-search]').filter(function() {
                return $(this).css('display') !== 'none';
            }).length;
            const total = detailBhpItems.length;
            $('#detailBhpVisibleCount').text(visible + ' dari ' + total + ' data');
        }

        function updateDetailFilterStatus() {
            const keyword = ($('#searchDetailBhp').val() || '').trim();
            const activeFilters = [];

            if (keyword) {
                activeFilters.push({
                    icon: 'mdi-magnify',
                    label: 'Pencarian: ' + keyword
                });
            }

            if (activePenjaminFilter !== 'all') {
                activeFilters.push({
                    icon: 'mdi-shield-account-outline',
                    label: $('#filterDetailPenjamin option:selected').text()
                });
            }

            if (activeKamarFilter !== 'all') {
                activeFilters.push({
                    icon: 'mdi-bed-outline',
                    label: $('#filterDetailKamar option:selected').text()
                });
            }

            const container = $('#detailBhpActiveFilters').empty();

            if (!activeFilters.length) {
                container.append(
                    '<span class="bhp-detail-no-filter">Semua data ditampilkan</span>'
                );
            } else {
                activeFilters.forEach(function(filter) {
                    container.append(`
                        <span class="bhp-detail-active-filter-chip">
                            <i class="mdi ${filter.icon}"></i>
                            ${escapeHtml(filter.label)}
                        </span>
                    `);
                });
            }

            $('#btnResetDetailBhpFilters').prop('disabled', activeFilters.length === 0);
        }

        function renderPenjaminSummary() {
            const keyword = ($('#searchDetailBhp').val() || '').toLowerCase();
            const filteredDetails = detailBhpItems.filter(function(detail) {
                return detailMatchesFilters(detail, keyword);
            });
            const summary = {};
            const allRawat = new Set();
            let totalLama = 0;

            filteredDetails.forEach(function(detail) {
                const key = detail.kd_pj || '-';
                const lama = Number(detail.lama) || 0;
                const noRawat = detail.no_rawat || '-';

                if (!summary[key]) {
                    summary[key] = {
                        kd_pj: key,
                        nama_penjamin: detail.nama_penjamin || key,
                        rawat: new Set(),
                        penggunaan: 0,
                        lama: 0
                    };
                }

                summary[key].rawat.add(noRawat);
                summary[key].penggunaan++;
                summary[key].lama += lama;
                allRawat.add(noRawat);
                totalLama += lama;
            });

            const summaries = Object.values(summary).sort(function(first, second) {
                if (second.lama !== first.lama) {
                    return second.lama - first.lama;
                }

                return first.nama_penjamin.localeCompare(second.nama_penjamin, 'id');
            });
            const rows = $('#detailBhpPenjaminSummaryRows').empty();
            const totalPremi = totalLama * detailNominalHitung;

            $('#detailBhpPenjaminSummaryCount').text(summaries.length + ' penjamin');
            $('#detailBhpFilteredRawat').text(allRawat.size);
            $('#detailBhpFilteredUsage').text(filteredDetails.length);
            $('#detailBhpFilteredLama').text(totalLama + ' hari');
            $('#detailBhpFilteredPremi').text(formatRupiah(totalPremi));

            if (!summaries.length) {
                rows.append(`
                    <tr>
                        <td colspan="6" class="bhp-penjamin-summary-empty">
                            <i class="mdi mdi-database-search-outline"></i>
                            Tidak ada rekap yang sesuai dengan filter.
                        </td>
                    </tr>
                `);
                return;
            }

            summaries.forEach(function(item) {
                const average = item.penggunaan > 0 ? item.lama / item.penggunaan : 0;

                rows.append(`
                    <tr>
                        <td>
                            <span class="bhp-penjamin-summary-name">
                                ${escapeHtml(item.nama_penjamin)}
                            </span>
                            <span class="bhp-penjamin-summary-code">
                                ${escapeHtml(item.kd_pj)}
                            </span>
                        </td>
                        <td class="text-center">
                            <strong>${item.rawat.size}</strong>
                            <small>pasien</small>
                        </td>
                        <td class="text-center">
                            <strong>${item.penggunaan}</strong>
                            <small>penggunaan</small>
                        </td>
                        <td class="text-center">
                            <strong>${item.lama}</strong>
                            <small>hari</small>
                        </td>
                        <td class="text-center">
                            <strong>${new Intl.NumberFormat('id-ID', {
                                maximumFractionDigits: 1
                            }).format(average)}</strong>
                            <small>hari / penggunaan</small>
                        </td>
                        <td class="text-end">
                            <strong class="bhp-penjamin-summary-money">
                                ${formatRupiah(item.lama * detailNominalHitung)}
                            </strong>
                        </td>
                    </tr>
                `);
            });
        }

        function applyDetailFilters() {
            const keyword = ($('#searchDetailBhp').val() || '').toLowerCase();

            $('#detailBhpRows tr[data-search]').each(function() {
                const detailIndex = Number($(this).data('detail-index'));
                const detail = detailBhpItems[detailIndex];

                $(this).toggle(detail ? detailMatchesFilters(detail, keyword) : false);
            });

            $('#btnClearSearchDetailBhp').toggleClass('d-none', keyword.length === 0);
            updateDetailFilterStatus();
            updateDetailVisibleCount();
            renderPenjaminSummary();
        }

        function renderPenjaminFilters(details) {
            const select = $('#filterDetailPenjamin');
            const penjaminCounts = {};

            details.forEach(function(detail) {
                const key = detail.kd_pj || '-';
                const label = detail.nama_penjamin || key;

                if (!penjaminCounts[key]) {
                    penjaminCounts[key] = {
                        label: label,
                        count: 0
                    };
                }

                penjaminCounts[key].count++;
            });

            select.empty().append(
                $('<option>', {
                    value: 'all',
                    text: 'Semua Penjamin (' + details.length + ')'
                })
            );

            Object.entries(penjaminCounts)
                .sort(function([, first], [, second]) {
                    return first.label.localeCompare(second.label, 'id');
                })
                .forEach(function([key, item]) {
                    select.append(
                        $('<option>', {
                            value: key,
                            text: item.label + ' [' + key + '] - ' + item.count + ' data'
                        })
                    );
                });

            $('#detailBhpPenjaminMeta').text(
                Object.keys(penjaminCounts).length + ' penjamin tersedia'
            );
            select.val('all').trigger('change.select2');
        }

        function renderKamarFilters(details) {
            const select = $('#filterDetailKamar');
            const kamarCounts = {};

            details.forEach(function(detail) {
                const key = detail.kd_kamar || '-';
                kamarCounts[key] = (kamarCounts[key] || 0) + 1;
            });

            select.empty().append(
                $('<option>', {
                    value: 'all',
                    text: 'Semua Kamar (' + details.length + ')'
                })
            );

            Object.entries(kamarCounts)
                .sort(function([first], [second]) {
                    return first.localeCompare(second, 'id');
                })
                .forEach(function([key, count]) {
                    select.append(
                        $('<option>', {
                            value: key,
                            text: formatKamarLabel(key) + ' - ' + count + ' penggunaan'
                        })
                    );
                });

            $('#detailBhpKamarMeta').text(
                Object.keys(kamarCounts).length + ' kamar tersedia'
            );
            select.val('all').trigger('change.select2');
        }

        function renderDetailRows(details) {
            const rows = $('#detailBhpRows');
            detailBhpItems = details;
            activePenjaminFilter = 'all';
            activeKamarFilter = 'all';
            rows.empty();
            renderPenjaminFilters(details);
            renderKamarFilters(details);
            updateDetailFilterStatus();
            renderPenjaminSummary();

            if (!details.length) {
                rows.append(
                    `<tr>
                        <td colspan="7" class="bhp-detail-empty">
                            <i class="mdi mdi-database-off-outline mdi-24px d-block mb-1"></i>
                            Detail penggunaan kamar tidak tersedia.
                        </td>
                    </tr>`
                );
                updateDetailVisibleCount();
                return;
            }

            details.forEach(function(detail, index) {
                const search = [
                    detail.no_rawat,
                    detail.tgl_masuk,
                    detail.kd_kamar,
                    detail.kd_pj,
                    detail.nama_penjamin
                ].join(' ').toLowerCase();

                rows.append(`
                    <tr data-detail-index="${index}"
                        data-search="${escapeHtml(search)}"
                        data-penjamin="${escapeHtml(detail.kd_pj || '-')}"
                        data-kamar="${escapeHtml(detail.kd_kamar || '-')}">
                        <td><span class="bhp-detail-number">${index + 1}</span></td>
                        <td>
                            <span class="fw-semibold text-dark">${escapeHtml(detail.no_rawat || '-')}</span>
                        </td>
                        <td>${escapeHtml(detail.tgl_masuk || '-')}</td>
                        <td>${escapeHtml(detail.kd_kamar || '-')}</td>
                        <td class="text-center">${escapeHtml(detail.lama || 0)} hari</td>
                        <td>
                            <span class="bhp-penjamin-name">${escapeHtml(detail.nama_penjamin || '-')}</span>
                            <span class="bhp-penjamin-code">${escapeHtml(detail.kd_pj || '-')}</span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-light btn-copy-rawat"
                                data-no-rawat="${escapeHtml(detail.no_rawat || '')}"
                                title="Salin nomor rawat">
                                <i class="mdi mdi-content-copy"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            updateDetailVisibleCount();
        }

        $('#tableGenerateBhp').on('click', '.btn-detail-kamar', function() {
            const id = $(this).data('id');

            $('#searchDetailBhp').val('');
            $('#btnClearSearchDetailBhp').addClass('d-none');
            $('#filterDetailPenjamin').empty()
                .append('<option value="all">Semua Penjamin</option>')
                .trigger('change.select2');
            $('#filterDetailKamar').empty()
                .append('<option value="all">Semua Kamar</option>')
                .trigger('change.select2');
            $('#detailBhpPenjaminMeta').text('0 penjamin tersedia');
            $('#detailBhpKamarMeta').text('0 kamar tersedia');
            $('#detailBhpVisibleCount').text('0 dari 0 data');
            detailNominalHitung = 0;
            $('#detailBhpPenjaminSummaryCount').text('0 penjamin');
            $('#detailBhpFilteredRawat').text('0');
            $('#detailBhpFilteredUsage').text('0');
            $('#detailBhpFilteredLama').text('0 hari');
            $('#detailBhpFilteredPremi').text('Rp 0');
            $('#detailBhpPenjaminSummaryRows').html(`
                <tr>
                    <td colspan="6" class="bhp-penjamin-summary-empty">
                        Belum ada data rekap.
                    </td>
                </tr>
            `);
            $('#detailBhpActiveFilters').html(
                '<span class="bhp-detail-no-filter">Semua data ditampilkan</span>'
            );
            $('#btnResetDetailBhpFilters').prop('disabled', true);
            $('#detailBhpRows').empty();
            $('#detailBhpLoading').removeClass('d-none');
            $('#detailBhpPenjaminSummary').addClass('d-none');
            $('#detailBhpTableWrap').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateKamar.detail", ["id" => "__ID__"]) }}"
                    .replace('__ID__', id),
                type: 'GET',
                success: function(response) {
                    const data = response.data || {};
                    $('#modalDetailBhpLabel').text(
                        'Detail Generate Kamar ' + (data.jenis_kamar_label || '')
                    );
                    $('#detailBhpHeader').toggleClass('is-bpjs', data.jenis_kamar === 'bpjs');
                    $('#detailBhpTypeBadge').text(data.jenis_kamar_label || '-');
                    $('#detailBhpPeriode').text(
                        (data.periode || '-') + ' / Sumber ' +
                        (data.periode_sumber || '-')
                    );
                    $('#detailBhpJumlah').text((data.jumlah_kamar || 0) + ' kamar');
                    $('#detailBhpLama').text((data.jumlah_lama_inap || 0) + ' hari');
                    $('#detailBhpNominal').text(formatRupiah(data.nominal_hitung));
                    $('#detailBhpTotal').text(formatRupiah(data.total_lama_inap));
                    detailNominalHitung = Number(data.nominal_hitung) || 0;
                    $('#detailBhpFormula').text(
                        (data.jumlah_lama_inap || 0) + ' hari x ' +
                        formatRupiah(data.nominal_hitung) + ' = ' +
                        formatRupiah(data.total_lama_inap)
                    );
                    renderDetailRows(data.details || []);
                    $('#detailBhpPenjaminSummary').removeClass('d-none');
                    $('#detailBhpTableWrap').removeClass('d-none');
                },
                error: function(xhr) {
                    detailModal.hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: getErrorMessage(xhr, 'Detail kamar gagal dimuat.')
                    });
                },
                complete: function() {
                    $('#detailBhpLoading').addClass('d-none');
                }
            });
        });

        $('#searchDetailBhp').on('input', applyDetailFilters);

        $('#btnClearSearchDetailBhp').on('click', function() {
            $('#searchDetailBhp').val('').trigger('focus');
            applyDetailFilters();
        });

        $('#filterDetailPenjamin').on('change', function() {
            activePenjaminFilter = String(this.value);
            applyDetailFilters();
        });

        $('#filterDetailKamar').on('change', function() {
            activeKamarFilter = String(this.value);
            applyDetailFilters();
        });

        $('#btnResetDetailBhpFilters').on('click', function() {
            activePenjaminFilter = 'all';
            activeKamarFilter = 'all';
            $('#searchDetailBhp').val('');
            detailPenjaminSelect.val('all').trigger('change.select2');
            detailKamarSelect.val('all').trigger('change.select2');
            applyDetailFilters();
        });

        $('#detailBhpRows').on('click', '.btn-copy-rawat', function() {
            const noRawat = String($(this).data('no-rawat') || '');

            if (!noRawat) {
                return;
            }

            const copyPromise = navigator.clipboard?.writeText ?
                navigator.clipboard.writeText(noRawat) :
                new Promise(function(resolve, reject) {
                    const input = $('<textarea>')
                        .css({
                            position: 'fixed',
                            opacity: 0
                        })
                        .val(noRawat)
                        .appendTo('body');

                    input[0].focus();
                    input[0].select();

                    try {
                        document.execCommand('copy') ?
                            resolve() :
                            reject(new Error('Copy command failed.'));
                    } catch (error) {
                        reject(error);
                    } finally {
                        input.remove();
                    }
                });

            copyPromise.then(function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'No. rawat disalin',
                    showConfirmButton: false,
                    timer: 1200
                });
            }).catch(function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak dapat menyalin',
                    text: 'Silakan salin nomor rawat secara manual.'
                });
            });
        });

        updateTypeDisplay();
        updatePeriodDisplay();
        loadSummary();
    });
</script>
