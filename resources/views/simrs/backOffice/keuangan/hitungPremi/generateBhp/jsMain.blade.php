<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const generateModal = new bootstrap.Modal(document.getElementById('modalGenerateBhp'));
        const detailModal = new bootstrap.Modal(document.getElementById('modalDetailBhp'));
        const typeConfig = {
            umum: {
                label: 'Umum',
                description: 'Perhitungan BHP pasien rawat inap non-BPJS',
                criteria: [
                    'Status lanjut pasien adalah rawat inap.',
                    'Penjamin selain BPJS Kesehatan dan kode kosong dan -.',
                    'Piutang belum lunas untuk Asuransi dan Sudah Bayar untuk penjamin Umum.'
                ]
            },
            bpjs: {
                label: 'BPJS',
                description: 'Perhitungan BHP pasien rawat inap dengan kode penjamin BPJ',
                criteria: [
                    'Status lanjut pasien adalah rawat inap.',
                    'Kode penjamin pasien adalah BPJ.',
                    'Piutang pasien berstatus Belum Lunas.',
                    'Data sumber diambil dari satu bulan sebelum periode generate.'
                ]
            }
        };
        let activeType = 'umum';
        let activeSummary = {
            ploting_nominals: []
        };

        function formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(value) || 0);
        }

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function formatInputNumber(value) {
            const numeric = String(value || '').replace(/\D/g, '').replace(/^0+(?=\d)/, '');

            return numeric ? new Intl.NumberFormat('id-ID').format(Number(numeric)) : '';
        }

        function nominalSummaryText(data) {
            return data.nominal_is_mixed ? 'Beragam' : formatRupiah(data.nominal_hitung);
        }

        function renderPlotingNominalInputs(plotingNominals) {
            const items = Array.isArray(plotingNominals) ? plotingNominals : [];
            const container = $('#plotingNominalContainer');

            if (!items.length) {
                container.html(`
                    <div class="alert alert-warning mb-0 py-2">
                        Master Ploting Premi belum tersedia. Tambahkan ploting terlebih dahulu.
                    </div>
                `);
                $('#btnSubmitGenerateBhp').prop('disabled', true);

                return;
            }

            $('#btnSubmitGenerateBhp').prop('disabled', false);
            container.html(items.map(function(item) {
                const id = Number(item.id || 0);
                const text = item.text || item.ploting || '-';
                const kode = item.kode ? item.kode : '';
                const nominal = Number(item.nominal_hitung || 0) > 0 ?
                    formatInputNumber(item.nominal_hitung) :
                    '';

                return `
                    <div class="border rounded-2 p-2">
                        <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">${escapeHtml(text)}</div>
                                <small class="text-muted">${escapeHtml(kode || 'Ploting Premi')}</small>
                            </div>
                            <div class="input-group input-group-sm" style="max-width: 220px;">
                                <span class="input-group-text">Rp</span>
                                <input type="text"
                                    class="form-control ploting-nominal-input text-end"
                                    name="nominal_hitung[${id}]"
                                    data-ploting-id="${id}"
                                    value="${escapeHtml(nominal)}"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    placeholder="0">
                            </div>
                        </div>
                    </div>
                `;
            }).join(''));
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
            $('#btnGenerateBhpLabel').text('Generate BHP ' + config.label);
            $('#resultBhpTitle').text('Hasil Generate BHP ' + config.label);
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
                url: "{{ route("backOffice.keuangan.hitungPremi.generateBhp.summary") }}",
                type: 'GET',
                data: {
                    periode: $('#periodeBhp').val(),
                    jenis_bhp: activeType
                },
                success: function(response) {
                    const data = response.data || {};
                    activeSummary = data;
                    const generatedPloting = Number(data.generated_ploting_count || 0);
                    const plotingCount = Number(data.ploting_count || 0);
                    $('#summaryJumlahBhp').text((data.jumlah_bhp || 0) + ' pasien');
                    $('#summaryNominalBhp').text(nominalSummaryText(data));
                    $('#summaryTotalBhp').text(formatRupiah(data.total_bhp));
                    $('#summaryPeriode').text(
                        (data.periode || '-') + ' / ' + (data.jenis_bhp_label || '-') +
                        ' / Sumber ' + (data.periode_sumber || '-') +
                        ' / ' + generatedPloting + ' dari ' + plotingCount + ' ploting'
                    );
                    $('#summaryFormulaBhp').text(data.nominal_is_mixed ?
                        (data.jumlah_bhp || 0) +
                        ' pasien x nominal masing-masing ploting' :
                        (data.jumlah_bhp || 0) + ' pasien x ' +
                        formatRupiah(data.nominal_hitung) + ' x ' +
                        generatedPloting + ' ploting'
                    );
                    updateLockState(data);
                },
                error: function() {
                    activeSummary = {
                        ploting_nominals: []
                    };
                    $('#summaryJumlahBhp').text('0 pasien');
                    $('#summaryNominalBhp').text('Rp 0');
                    $('#summaryTotalBhp').text('Rp 0');
                    $('#summaryPeriode').text('-');
                    $('#summaryFormulaBhp').text('0 pasien x Rp 0 x 0 ploting');
                    updateLockState({
                        is_locked: false,
                        jumlah_bhp: 0,
                        generated_ploting_count: 0,
                        ploting_count: 0
                    });
                }
            });
        }

        function updateLockState(data) {
            const isLocked = Boolean(data.is_locked);
            const generatedPloting = Number(data.generated_ploting_count || 0);
            const plotingCount = Number(data.ploting_count || 0);
            const hasResult = generatedPloting > 0;
            const status = $('#activeBhpLockStatus');
            const generateButton = $('#btnGenerateBhp');
            const lockAllButton = $('#btnLockAllBhp');
            const lockCard = $('#summaryLockCard');
            const lockedBy = data.locked_by_name || '';
            const lockedAt = data.locked_at || '';
            const lockedPloting = Number(data.locked_ploting_count || 0);
            const unlockedPloting = Math.max(generatedPloting - lockedPloting, 0);
            const plotingInfo = generatedPloting + ' dari ' + plotingCount + ' ploting';

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
                'Data BHP Terkunci' :
                'Generate BHP ' + typeConfig[activeType].label
            );
            lockAllButton.prop('disabled', unlockedPloting < 1);
            lockAllButton.attr(
                'title',
                unlockedPloting > 0 ?
                'Kunci ' + unlockedPloting + ' data BHP yang masih terbuka.' :
                (hasResult ? 'Semua data BHP sudah terkunci.' : 'Belum ada data BHP yang bisa dikunci.')
            );

            lockCard.toggleClass('is-locked', isLocked);
            $('#summaryLockIcon').attr(
                'class',
                isLocked ? 'mdi mdi-lock' : 'mdi mdi-lock-open-variant-outline'
            );

            if (!hasResult) {
                $('#summaryLockValue').text('Belum Ada Data');
                $('#summaryLockNote').text(
                    plotingCount > 0 ? 'Generate data terlebih dahulu' :
                    'Master Ploting Premi belum tersedia'
                );
                $('#heroActiveStatus').text('Belum ada hasil generate');
                $('#actionStatusMessage').text(
                    plotingCount > 0 ?
                    'Belum ada hasil generate untuk konteks yang dipilih.' :
                    'Tambahkan Master Ploting Premi terlebih dahulu sebelum generate.'
                );
                return;
            }

            if (isLocked) {
                $('#summaryLockValue').text('Terkunci');
                $('#summaryLockNote').text(
                    ([lockedBy, lockedAt].filter(Boolean).join(' / ') || 'Data terlindungi') +
                    ' / ' + plotingInfo
                );
                $('#heroActiveStatus').text('Ada ploting terkunci dan tidak dapat digenerate ulang');
                $('#actionStatusMessage').html(
                    '<i class="mdi mdi-lock me-1"></i>' +
                    (data.locked_ploting_count || 0) + ' ploting sudah dikunci' +
                    (lockedBy ? ' oleh <strong>' + escapeHtml(lockedBy) + '</strong>' : '') +
                    '.'
                );
                return;
            }

            $('#summaryLockValue').text('Belum Dikunci');
            $('#summaryLockNote').text('Data masih dapat digenerate ulang / ' + plotingInfo);
            $('#heroActiveStatus').text('Data tersedia untuk ' + plotingInfo);
            $('#actionStatusMessage').html(
                '<i class="mdi mdi-check-circle-outline me-1"></i>' +
                'Hasil tersedia untuk <strong>' + escapeHtml(plotingInfo) +
                '</strong> dan masih dapat digenerate ulang.'
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
                        <strong class="d-block text-dark">Belum ada hasil BHP</strong>
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
                url: "{{ route("backOffice.keuangan.hitungPremi.generateBhp.table") }}",
                type: 'GET',
                data: function(data) {
                    data.periode = $('#periodeBhp').val();
                    data.jenis_bhp = activeType;
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
                    data: 'jenis_bhp_label',
                    name: 'jenis_bhp',
                    render: function(data, type, row) {
                        const badgeClass = row.jenis_bhp === 'bpjs' ?
                            'bg-primary-subtle text-primary' :
                            'bg-success-subtle text-success';

                        return '<span class="badge ' + badgeClass + '">' +
                            escapeHtml(data || '-') + '</span>';
                    }
                },
                {
                    data: 'ploting_label',
                    name: 'nama_ploting',
                    render: function(data, type, row) {
                        return `
                            <strong class="text-dark">${escapeHtml(row.nama_ploting || '-')}</strong>
                            <small class="d-block text-muted">${escapeHtml(row.kode_ploting || '-')}</small>
                        `;
                    }
                },
                {
                    data: 'jumlah_bhp',
                    name: 'jumlah_bhp',
                    className: 'text-center',
                    render: function(data) {
                        return new Intl.NumberFormat('id-ID').format(Number(data) || 0) +
                            ' pasien';
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
                    data: 'total_bhp',
                    name: 'total_bhp',
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

        $('#tableGenerateBhp').on('click', '.btn-lock-bhp', function() {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'warning',
                title: 'Kunci data BHP?',
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
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateBhp.lock", ["id" => "__ID__"]) }}"
                        .replace('__ID__', id),
                    type: 'POST',
                    success: function(response) {
                        refreshBhpData();
                        Swal.fire({
                            icon: 'success',
                            title: 'Data Dikunci',
                            text: response.message ||
                                'Data BHP berhasil dikunci.',
                            timer: 1600,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengunci',
                            text: getErrorMessage(xhr,
                                'Data BHP gagal dikunci.')
                        });
                    }
                });
            });
        });

        $('#tableGenerateBhp').on('click', '.btn-unlock-bhp', function() {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'question',
                title: 'Buka kunci data BHP?',
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
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateBhp.unlock", ["id" => "__ID__"]) }}"
                        .replace('__ID__', id),
                    type: 'POST',
                    success: function(response) {
                        refreshBhpData();
                        Swal.fire({
                            icon: 'success',
                            title: 'Kunci Dibuka',
                            text: response.message ||
                                'Kunci data BHP berhasil dibuka.',
                            timer: 1600,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Membuka Kunci',
                            text: getErrorMessage(xhr,
                                'Kunci data BHP gagal dibuka.')
                        });
                    }
                });
            });
        });

        $('#btnLockAllBhp').on('click', function() {
            Swal.fire({
                icon: 'warning',
                title: 'Kunci semua data BHP?',
                text: 'Semua data BHP ' + typeConfig[activeType].label + ' periode ' +
                    ($('#periodeBhp').val() || '-') + ' yang masih terbuka akan dikunci.',
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-lock-check-outline me-1"></i> Kunci Semua',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d97706'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateBhp.lockAll") }}",
                    type: 'POST',
                    data: {
                        periode: $('#periodeBhp').val(),
                        jenis_bhp: activeType
                    },
                    success: function(response) {
                        refreshBhpData();
                        Swal.fire({
                            icon: 'success',
                            title: 'Data Dikunci',
                            text: response.message ||
                                'Semua data BHP berhasil dikunci.',
                            timer: 1600,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengunci',
                            text: getErrorMessage(xhr,
                                'Data BHP gagal dikunci.')
                        });
                    }
                });
            });
        });

        $('#tableGenerateBhp').on('click', '.btn-delete-bhp', function() {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data BHP?',
                text: 'Data yang dihapus tidak bisa dikembalikan.',
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-delete-outline me-1"></i> Hapus Data',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateBhp.delete", ["id" => "__ID__"]) }}"
                        .replace('__ID__', id),
                    type: 'DELETE',
                    success: function(response) {
                        refreshBhpData();
                        Swal.fire({
                            icon: 'success',
                            title: 'Data Dihapus',
                            text: response.message ||
                                'Data BHP berhasil dihapus.',
                            timer: 1600,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: getErrorMessage(xhr,
                                'Data BHP gagal dihapus.')
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
                    text: 'Pilih periode BHP terlebih dahulu.'
                });
                return;
            }

            $('#modalGenerateBhpLabel').text('Generate BHP ' + config.label);
            $('#jenisGenerateBhp').val(activeType);
            $('#jenisGenerateBhpLabel').val(config.label);
            $('#periodeGenerateBhp').val(periode);
            $('#periodeGenerateSourceInfo').text(
                        'Data sumber: ' + getSourcePeriod(periode, activeType) +
                (activeType === 'bpjs' ? ' (bulan sebelumnya).' : '.') +
                ' Ploting bernominal Rp 0 tidak akan digenerate.'
            );
            renderPlotingNominalInputs(activeSummary.ploting_nominals || []);
            $('#nominalHitungBhpError').text('');
            generateModal.show();

            setTimeout(function() {
                $('.ploting-nominal-input:first').trigger('focus');
            }, 250);
        });

        $('#plotingNominalContainer').on('input', '.ploting-nominal-input', function() {
            const numeric = this.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
            this.value = formatInputNumber(numeric);
            $(this).removeClass('is-invalid');
            $('#nominalHitungBhpError').text('');
        });

        $('#formGenerateBhp').on('submit', function(event) {
            event.preventDefault();

            const submitButton = $('#btnSubmitGenerateBhp');
            const originalHtml = submitButton.html();
            const nominalPayload = {};
            let firstInvalidInput = null;
            let hasPositiveNominal = false;
            const jenisBhp = $('#jenisGenerateBhp').val();
            const typeLabel = typeConfig[jenisBhp].label;

            $('.ploting-nominal-input').each(function() {
                const input = $(this);
                const plotingId = input.data('ploting-id');
                const nominal = this.value.replace(/\D/g, '');
                nominalPayload[plotingId] = nominal;

                if (nominal && Number(nominal) > 0) {
                    hasPositiveNominal = true;
                }

                if (nominal && Number(nominal) > 999999999999) {
                    input.addClass('is-invalid');
                    firstInvalidInput = firstInvalidInput || input;
                }
            });

            if (firstInvalidInput) {
                firstInvalidInput.trigger('focus');
                $('#nominalHitungBhpError').text(
                    'Nominal hitung per ploting maksimal Rp 999.999.999.999.'
                );
                return;
            }

            if (!hasPositiveNominal) {
                $('.ploting-nominal-input:first').trigger('focus');
                $('#nominalHitungBhpError').text(
                    'Isi minimal satu nominal ploting lebih dari Rp 0. Nominal Rp 0 akan dilewati.'
                );
                return;
            }

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateBhp.store") }}",
                type: 'POST',
                data: {
                    periode: $('#periodeGenerateBhp').val(),
                    jenis_bhp: jenisBhp,
                    nominal_hitung: nominalPayload
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
                        text: response.message || ('BHP ' + typeLabel +
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
                            'BHP ' + typeLabel + ' gagal digenerate.'
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

        function updateDetailVisibleCount() {
            const visible = $('#detailBhpRows tr[data-search]:visible').length;
            const total = detailBhpItems.length;
            $('#detailBhpVisibleCount').text(visible + ' dari ' + total + ' data');
        }

        function applyDetailFilters() {
            const keyword = ($('#searchDetailBhp').val() || '').toLowerCase();

            $('#detailBhpRows tr[data-search]').each(function() {
                const searchable = $(this).data('search') || '';
                const penjamin = String($(this).data('penjamin') || '');
                const matchesSearch = searchable.indexOf(keyword) !== -1;
                const matchesPenjamin = activePenjaminFilter === 'all' ||
                    penjamin === activePenjaminFilter;

                $(this).toggle(matchesSearch && matchesPenjamin);
            });

            $('#btnClearSearchDetailBhp').toggleClass('d-none', keyword.length === 0);
            updateDetailVisibleCount();
        }

        function renderPenjaminFilters(details) {
            const filters = $('#detailBhpPenjaminFilters');
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

            filters.empty().append(`
                <button type="button" class="bhp-penjamin-filter active" data-penjamin="all">
                    Semua (${details.length})
                </button>
            `);

            Object.entries(penjaminCounts).forEach(function([key, item]) {
                filters.append(`
                    <button type="button" class="bhp-penjamin-filter"
                        data-penjamin="${escapeHtml(key)}">
                        ${escapeHtml(item.label)} (${item.count})
                    </button>
                `);
            });
        }

        function renderDetailRows(details) {
            const rows = $('#detailBhpRows');
            detailBhpItems = details;
            activePenjaminFilter = 'all';
            rows.empty();
            renderPenjaminFilters(details);

            if (!details.length) {
                rows.append(
                    `<tr>
                        <td colspan="5" class="bhp-detail-empty">
                            <i class="mdi mdi-database-off-outline mdi-24px d-block mb-1"></i>
                            Detail pasien tidak tersedia.
                        </td>
                    </tr>`
                );
                updateDetailVisibleCount();
                return;
            }

            details.forEach(function(detail, index) {
                const search = [
                    detail.no_rawat,
                    detail.tgl_registrasi,
                    detail.kd_pj,
                    detail.nama_penjamin
                ].join(' ').toLowerCase();

                rows.append(`
                    <tr data-search="${escapeHtml(search)}"
                        data-penjamin="${escapeHtml(detail.kd_pj || '-')}">
                        <td><span class="bhp-detail-number">${index + 1}</span></td>
                        <td>
                            <span class="fw-semibold text-dark">${escapeHtml(detail.no_rawat || '-')}</span>
                        </td>
                        <td>${escapeHtml(detail.tgl_registrasi || '-')}</td>
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

        $('#tableGenerateBhp').on('click', '.btn-detail-bhp', function() {
            const id = $(this).data('id');

            $('#searchDetailBhp').val('');
            $('#btnClearSearchDetailBhp').addClass('d-none');
            $('#detailBhpPenjaminFilters').empty();
            $('#detailBhpVisibleCount').text('0 data');
            $('#detailBhpRows').empty();
            $('#detailBhpLoading').removeClass('d-none');
            $('#detailBhpTableWrap').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateBhp.detail", ["id" => "__ID__"]) }}"
                    .replace('__ID__', id),
                type: 'GET',
                success: function(response) {
                    const data = response.data || {};
                    $('#modalDetailBhpLabel').text(
                        'Detail Generate BHP ' + (data.jenis_bhp_label || '') +
                        ' / ' + (data.ploting_label || '-')
                    );
                    $('#detailBhpHeader').toggleClass('is-bpjs', data.jenis_bhp === 'bpjs');
                    $('#detailBhpTypeBadge').text(data.jenis_bhp_label || '-');
                    $('#detailBhpPeriode').text(
                        (data.periode || '-') + ' / Sumber ' +
                        (data.periode_sumber || '-') + ' / Ploting ' +
                        (data.ploting_label || '-')
                    );
                    $('#detailBhpJumlah').text((data.jumlah_bhp || 0) + ' pasien');
                    $('#detailBhpNominal').text(formatRupiah(data.nominal_hitung));
                    $('#detailBhpTotal').text(formatRupiah(data.total_bhp));
                    $('#detailBhpFormula').text(
                        (data.jumlah_bhp || 0) + ' pasien x ' +
                        formatRupiah(data.nominal_hitung) + ' = ' +
                        formatRupiah(data.total_bhp)
                    );
                    renderDetailRows(data.details || []);
                    $('#detailBhpTableWrap').removeClass('d-none');
                },
                error: function(xhr) {
                    detailModal.hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: getErrorMessage(xhr, 'Detail BHP gagal dimuat.')
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

        $('#detailBhpPenjaminFilters').on('click', '.bhp-penjamin-filter', function() {
            activePenjaminFilter = String($(this).data('penjamin'));
            $('.bhp-penjamin-filter').removeClass('active');
            $(this).addClass('active');
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
