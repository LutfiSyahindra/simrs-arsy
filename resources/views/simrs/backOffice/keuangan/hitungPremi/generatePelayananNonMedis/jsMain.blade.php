<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const detailModal = new bootstrap.Modal(document.getElementById('modalDetailNonMedis'));
        let activeType = 'umum';
        let summaryData = null;
        let detailMappings = [];
        let selectedMappingId = '';

        function formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 2
            }).format(Number(value) || 0);
        }

        function formatNumber(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
        }

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function errorMessage(xhr) {
            const errors = xhr.responseJSON?.errors;

            if (errors) {
                return Object.values(errors).flat().join('<br>');
            }

            return xhr.responseJSON?.message || 'Terjadi kesalahan saat memproses data.';
        }

        function setDefaultPeriod() {
            const now = new Date();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            $('#periodeNonMedis').val(now.getFullYear() + '-' + month);
        }

        function updateSourcePeriodInfo(sourcePeriod) {
            const periode = $('#periodeNonMedis').val() || '-';
            const source = sourcePeriod || periode;

            $('#nonMedisSourcePeriod').text(
                activeType === 'bpjs' ?
                'Periode hasil ' + periode + ', sumber tindakan BPJS bulan sebelumnya: ' +
                    source + '.' :
                'Sumber tindakan Umum mengikuti periode yang dipilih: ' + source + '.'
            );
        }

        function renderDependency(selector, data, label) {
            const card = $(selector);
            const value = card.find('.non-medis-dependency-value');
            const note = card.find('.non-medis-dependency-note');

            card.removeClass('ready unlocked missing');

            if (data?.exists) {
                card.addClass(data.is_locked ? 'ready' : 'unlocked');
                value.text(formatRupiah(data.total));
                note.text(
                    (data.is_locked ? 'Terkunci' : 'Belum dikunci') +
                    (data.updated_at ? ' / ' + data.updated_at : '')
                );
                return;
            }

            card.addClass('missing');
            value.text('Belum tersedia');
            note.text('Generate ' + label + ' pada periode ini terlebih dahulu');
        }

        function renderSummary(data) {
            summaryData = data;
            updateSourcePeriodInfo(data.periode_sumber);
            renderDependency('#dependencyBhp', data.dependency_bhp, 'BHP');
            renderDependency('#dependencyKamar', data.dependency_kamar, 'Kamar');

            $('#summaryTransaksi').text(formatNumber(data.jumlah_transaksi));
            $('#summaryTindakan').text(
                formatNumber(data.jumlah_jenis_tindakan) + ' jenis tindakan'
            );
            $('#summaryBiayaRawat').text(formatRupiah(data.total_biaya_rawat));
            $('#summaryMapping').text(formatRupiah(data.total_mapping_premi));
            $('#summaryJumlahMapping').text(
                formatNumber(data.jumlah_mapping_premi) + ' mapping terhitung'
            );
            $('#summaryFinal').text(formatRupiah(data.total_final));
            $('#summaryFormula').text(
                formatRupiah(data.total_mapping_premi) + ' + ' +
                formatRupiah(data.total_bhp) + ' + ' +
                formatRupiah(data.total_kamar_inap) + ' = ' +
                formatRupiah(data.total_final)
            );

            const stateText = data.is_locked ?
                'Data terkunci. Ringkasan menampilkan snapshot perhitungan tersimpan.' :
                (data.is_generated ?
                    'Data sudah pernah digenerate. Preview memakai sumber dan mapping terbaru.' :
                    'Preview dihitung langsung dari sumber Khanza dan mapping aktif.');
            $('#nonMedisSummarySubtitle').text(
                data.is_locked || data.ready ?
                stateText :
                (data.readiness_message ||
                    'BHP dan Kamar Inap wajib tersedia dan terkunci.')
            );

            const canGenerate = data.ready &&
                Number(data.jumlah_mapping_premi) > 0 &&
                !data.is_locked;
            $('#btnGenerateNonMedis')
                .prop('disabled', !canGenerate)
                .attr(
                    'title',
                    data.is_locked ?
                    'Data periode ini terkunci' :
                    (!data.ready ?
                        (data.readiness_message ||
                            'BHP dan Kamar Inap wajib tersedia dan terkunci') :
                        '')
                );
        }

        function resetSummary() {
            summaryData = null;
            updateSourcePeriodInfo();
            renderDependency('#dependencyBhp', null, 'BHP');
            renderDependency('#dependencyKamar', null, 'Kamar');
            $('#summaryTransaksi').text('0');
            $('#summaryTindakan').text('0 jenis tindakan');
            $('#summaryBiayaRawat, #summaryMapping, #summaryFinal').text('Rp 0');
            $('#summaryJumlahMapping').text('0 mapping terhitung');
            $('#summaryFormula').text('Rp 0 + Rp 0 + Rp 0 = Rp 0');
            $('#btnGenerateNonMedis').prop('disabled', true);
        }

        function loadSummary() {
            const periode = $('#periodeNonMedis').val();

            if (!periode) {
                resetSummary();
                return;
            }

            $('#nonMedisSummarySubtitle').text('Memeriksa sumber data dan mapping...');
            $('#btnGenerateNonMedis').prop('disabled', true);

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generatePelayananNonMedis.summary") }}",
                data: {
                    periode: periode,
                    jenis_pelayanan: activeType
                },
                success: function(response) {
                    renderSummary(response.data || {});
                },
                error: function(xhr) {
                    resetSummary();
                    $('#nonMedisSummarySubtitle').text(
                        xhr.responseJSON?.message || 'Preview gagal dimuat.'
                    );
                }
            });
        }

        const resultTable = $('#tableGenerateNonMedis').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generatePelayananNonMedis.table") }}",
                data: function(data) {
                    data.periode = $('#periodeNonMedis').val();
                    data.jenis_pelayanan = activeType;
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'periode'
                },
                {
                    data: 'jenis_pelayanan_label',
                    render: function(data, type, row) {
                        return '<span class="non-medis-badge ' +
                            escapeHtml(row.jenis_pelayanan) + '">' +
                            escapeHtml(data) + '</span>';
                    }
                },
                {
                    data: 'jumlah_transaksi',
                    className: 'text-center',
                    render: function(data, type, row) {
                        return '<strong>' + formatNumber(data) + '</strong>' +
                            '<small class="d-block text-muted">' +
                            formatNumber(row.jumlah_jenis_tindakan) + ' jenis</small>';
                    }
                },
                {
                    data: 'total_mapping_premi',
                    className: 'text-end',
                    render: formatRupiah
                },
                {
                    data: 'total_bhp',
                    className: 'text-end',
                    render: formatRupiah
                },
                {
                    data: 'total_kamar_inap',
                    className: 'text-end',
                    render: formatRupiah
                },
                {
                    data: 'total_final',
                    className: 'text-end',
                    render: function(data) {
                        return '<strong class="text-primary">' +
                            formatRupiah(data) + '</strong>';
                    }
                },
                {
                    data: 'is_locked',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="non-medis-lock locked" title="' +
                                escapeHtml(
                                    [row.locked_by_name, row.locked_at].filter(Boolean).join(' / ')
                                ) + '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                        }

                        return '<span class="non-medis-lock open">' +
                            '<i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
                    }
                },
                {
                    data: 'generate_by_name',
                    render: function(data, type, row) {
                        return '<span class="fw-semibold">' + escapeHtml(data || '-') +
                            '</span><small class="d-block text-muted">' +
                            escapeHtml(row.generated_at || '-') + '</small>';
                    }
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            language: {
                processing: 'Memuat hasil generate...',
                search: '',
                searchPlaceholder: 'Cari hasil...',
                emptyTable: 'Belum ada hasil generate pada periode dan jenis ini.',
                zeroRecords: 'Data tidak ditemukan.',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Belum ada data',
                paginate: {
                    previous: 'Sebelumnya',
                    next: 'Berikutnya'
                }
            }
        });

        function refreshAll() {
            loadSummary();
            resultTable.ajax.reload();
        }

        $('.non-medis-type-tab').on('click', function() {
            activeType = $(this).data('type');
            $('.non-medis-type-tab').removeClass('active');
            $(this).addClass('active');
            $('#activeTypeBadge')
                .removeClass('umum bpjs')
                .addClass(activeType)
                .text(activeType.toUpperCase());
            updateSourcePeriodInfo();
            refreshAll();
        });

        $('#periodeNonMedis').on('change', refreshAll);

        $('#btnGenerateNonMedis').on('click', function() {
            if (!summaryData) {
                return;
            }

            const periode = $('#periodeNonMedis').val();
            const typeLabel = activeType === 'bpjs' ? 'BPJS' : 'UMUM';

            Swal.fire({
                icon: 'question',
                title: 'Generate Pelayanan Non Medis?',
                html:
                    '<div class="text-start small">' +
                    '<div class="mb-2">Periode: <strong>' + escapeHtml(periode) + '</strong></div>' +
                    '<div class="mb-2">Periode sumber: <strong>' +
                    escapeHtml(summaryData.periode_sumber || periode) + '</strong></div>' +
                    '<div class="mb-2">Jenis: <strong>' + typeLabel + '</strong></div>' +
                    '<div class="p-2 rounded bg-light">' +
                    escapeHtml($('#summaryFormula').text()) +
                    '</div></div>',
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-cog-play-outline me-1"></i> Generate',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    return $.ajax({
                        url: "{{ route("backOffice.keuangan.hitungPremi.generatePelayananNonMedis.store") }}",
                        method: 'POST',
                        data: {
                            periode: periode,
                            jenis_pelayanan: activeType
                        }
                    }).catch(function(xhr) {
                        Swal.showValidationMessage(errorMessage(xhr));
                    });
                },
                allowOutsideClick: function() {
                    return !Swal.isLoading();
                }
            }).then(function(result) {
                if (result.isConfirmed && result.value) {
                    Swal.fire('Berhasil', result.value.message, 'success');
                    refreshAll();
                }
            });
        });

        function changeLock(id, action, title, message) {
            Swal.fire({
                icon: action === 'lock' ? 'warning' : 'question',
                title: title,
                text: message,
                showCancelButton: true,
                confirmButtonText: action === 'lock' ? 'Kunci Data' : 'Buka Kunci',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    const template = action === 'lock' ?
                        "{{ route("backOffice.keuangan.hitungPremi.generatePelayananNonMedis.lock", ["id" => "__ID__"]) }}" :
                        "{{ route("backOffice.keuangan.hitungPremi.generatePelayananNonMedis.unlock", ["id" => "__ID__"]) }}";

                    return $.ajax({
                        url: template.replace('__ID__', id),
                        method: 'POST'
                    }).catch(function(xhr) {
                        Swal.showValidationMessage(errorMessage(xhr));
                    });
                }
            }).then(function(result) {
                if (result.isConfirmed && result.value) {
                    Swal.fire('Berhasil', result.value.message, 'success');
                    refreshAll();
                }
            });
        }

        $('#tableGenerateNonMedis').on('click', '.btn-lock-non-medis', function() {
            changeLock(
                $(this).data('id'),
                'lock',
                'Kunci hasil perhitungan?',
                'Data yang terkunci tidak dapat digenerate ulang.'
            );
        });

        $('#tableGenerateNonMedis').on('click', '.btn-unlock-non-medis', function() {
            changeLock(
                $(this).data('id'),
                'unlock',
                'Buka kunci hasil?',
                'Data dapat digenerate ulang setelah kunci dibuka.'
            );
        });

        function mappingLabel(detail) {
            return (detail.kode_jenis_tindakan || '-') + ' - ' +
                (detail.nama_jenis_tindakan || '-') + ' / ' +
                (detail.nama_premi || '-');
        }

        function renderMappingRows() {
            const rows = $('#detailMappingRows').empty();
            const select = $('#filterDetailMapping')
                .empty()
                .append('<option value="">Pilih mapping</option>');

            if (!detailMappings.length) {
                rows.html(
                    '<tr><td colspan="8" class="non-medis-empty">Tidak ada detail mapping.</td></tr>'
                );
                return;
            }

            detailMappings.forEach(function(detail) {
                const selected = String(detail.id) === String(selectedMappingId);
                const mappingValue = detail.jenis_mapping === 'persen' ?
                    formatNumber(detail.nilai_mapping) + '%' :
                    formatRupiah(detail.nilai_mapping);
                const baseValue = detail.jenis_mapping === 'persen' ?
                    formatRupiah(detail.dasar_hitung) :
                    formatNumber(detail.dasar_hitung) + ' data';

                rows.append(`
                    <tr class="non-medis-mapping-row ${selected ? 'active' : ''}"
                        data-id="${escapeHtml(detail.id)}">
                        <td>
                            <strong>${escapeHtml(detail.nama_premi || '-')}</strong>
                            <small class="d-block text-muted">${escapeHtml(detail.kode_premi || '-')}</small>
                        </td>
                        <td>
                            <strong>${escapeHtml(detail.nama_jenis_tindakan || '-')}</strong>
                            <small class="d-block text-muted">${escapeHtml(detail.kode_jenis_tindakan || '-')}</small>
                        </td>
                        <td><span class="non-medis-kind ${escapeHtml(detail.jenis_mapping)}">
                            ${escapeHtml(detail.jenis_mapping)}
                        </span></td>
                        <td class="text-end fw-semibold">${mappingValue}</td>
                        <td class="text-center">${formatNumber(detail.jumlah_data)}</td>
                        <td class="text-end">${formatRupiah(detail.total_biaya_rawat)}</td>
                        <td class="text-end">${baseValue}</td>
                        <td class="text-end fw-bold text-primary">${formatRupiah(detail.hasil_mapping)}</td>
                    </tr>
                `);

                select.append(
                    $('<option>', {
                        value: detail.id,
                        text: mappingLabel(detail),
                        selected: selected
                    })
                );
            });
        }

        function selectedMapping() {
            return detailMappings.find(function(item) {
                return String(item.id) === String(selectedMappingId);
            });
        }

        function renderTransactions() {
            const rows = $('#detailTransactionRows').empty();
            const detail = selectedMapping();
            const keyword = String($('#searchDetailTransaction').val() || '')
                .trim()
                .toLowerCase();

            if (!detail) {
                $('#detailTransactionMeta').text('Pilih mapping untuk melihat transaksi.');
                rows.html(
                    '<tr><td colspan="8" class="non-medis-empty">Belum ada mapping dipilih.</td></tr>'
                );
                return;
            }

            const transactions = (detail.data_tindakan || []).filter(function(item) {
                const haystack = [
                    item.no_rawat,
                    item.kd_tindakan,
                    item.nm_tindakan,
                    item.nama_penjamin,
                    item.kd_pj,
                    item.source_table
                ].join(' ').toLowerCase();

                return !keyword || haystack.includes(keyword);
            });

            $('#detailTransactionMeta').text(
                formatNumber(transactions.length) + ' dari ' +
                formatNumber((detail.data_tindakan || []).length) +
                ' transaksi / ' + mappingLabel(detail)
            );

            if (!transactions.length) {
                rows.html(
                    '<tr><td colspan="8" class="non-medis-empty">Transaksi tidak ditemukan.</td></tr>'
                );
                return;
            }

            transactions.forEach(function(item, index) {
                const provider = [item.kd_dokter, item.nip].filter(Boolean).join(' / ') || '-';

                rows.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(item.tanggal || '-')}
                            <small class="d-block text-muted">${escapeHtml(item.jam || '-')}</small>
                        </td>
                        <td class="fw-semibold">${escapeHtml(item.no_rawat || '-')}</td>
                        <td>
                            <span class="badge bg-light text-dark">${escapeHtml(item.sumber_tindakan || '-')}</span>
                            <small class="d-block text-muted">${escapeHtml(item.source_table || '-')}</small>
                        </td>
                        <td>
                            <strong>${escapeHtml(item.nm_tindakan || '-')}</strong>
                            <small class="d-block text-muted">${escapeHtml(item.kd_tindakan || '-')}</small>
                        </td>
                        <td>${escapeHtml(item.nama_penjamin || item.kd_pj || '-')}
                            <small class="d-block text-muted">${escapeHtml(item.kd_pj || '-')}</small>
                        </td>
                        <td>${escapeHtml(provider)}</td>
                        <td class="text-end fw-semibold">${formatRupiah(item.biaya_rawat)}</td>
                    </tr>
                `);
            });
        }

        $('#detailMappingRows').on('click', '.non-medis-mapping-row', function() {
            selectedMappingId = String($(this).data('id'));
            renderMappingRows();
            renderTransactions();
        });

        $('#filterDetailMapping').on('change', function() {
            selectedMappingId = String($(this).val() || '');
            renderMappingRows();
            renderTransactions();
        });

        $('#searchDetailTransaction').on('input', renderTransactions);

        $('#tableGenerateNonMedis').on('click', '.btn-detail-non-medis', function() {
            const id = $(this).data('id');
            detailMappings = [];
            selectedMappingId = '';
            $('#detailNonMedisContent').addClass('d-none');
            $('#detailNonMedisLoading').removeClass('d-none');
            $('#searchDetailTransaction').val('');
            detailModal.show();

            const url =
                "{{ route("backOffice.keuangan.hitungPremi.generatePelayananNonMedis.detail", ["id" => "__ID__"]) }}"
                .replace('__ID__', id);

            $.ajax({
                url: url,
                success: function(response) {
                    const data = response.data || {};
                    detailMappings = data.details || [];
                    selectedMappingId = detailMappings.length ?
                        String(detailMappings[0].id) : '';

                    $('#detailNonMedisMeta').text(
                        (data.periode || '-') + ' / ' +
                        (data.jenis_pelayanan_label || '-') + ' / Sumber ' +
                        (data.periode_sumber || '-') + ' / Generate oleh ' +
                        (data.generate_by_name || '-')
                    );
                    $('#detailJumlahTransaksi').text(
                        formatNumber(data.jumlah_transaksi) + ' transaksi'
                    );
                    $('#detailBiayaRawat').text(formatRupiah(data.total_biaya_rawat));
                    $('#detailMappingPremi').text(formatRupiah(data.total_mapping_premi));
                    $('#detailBhp').text(formatRupiah(data.total_bhp));
                    $('#detailKamar').text(formatRupiah(data.total_kamar_inap));
                    $('#detailTotalFinal').text(formatRupiah(data.total_final));
                    $('#detailFormula').text(
                        formatRupiah(data.total_mapping_premi) + ' + ' +
                        formatRupiah(data.total_bhp) + ' + ' +
                        formatRupiah(data.total_kamar_inap) + ' = ' +
                        formatRupiah(data.total_final)
                    );
                    $('#detailMappingCount').text(
                        formatNumber(detailMappings.length) + ' mapping'
                    );

                    renderMappingRows();
                    renderTransactions();
                    $('#detailNonMedisLoading').addClass('d-none');
                    $('#detailNonMedisContent').removeClass('d-none');
                },
                error: function(xhr) {
                    detailModal.hide();
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        });

        setDefaultPeriod();
        refreshAll();
    });
</script>
