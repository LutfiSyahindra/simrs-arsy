<script>
    $(function() {
        const searchInput = $('#searchMapping');
        const cards = $('.mapping-menu-card');
        const countLabel = $('#mappingCount');
        const emptyState = $('#mappingEmpty');

        function updateMappingCards() {
            const keyword = searchInput.val().toLowerCase().trim();
            let visibleCount = 0;

            cards.each(function() {
                const card = $(this);
                const text = `${card.text()} ${card.data('search') || ''}`.toLowerCase();
                const isVisible = keyword === '' || text.includes(keyword);

                card.toggle(isVisible);
                if (isVisible) visibleCount++;
            });

            countLabel.text(`${visibleCount} menu tampil`);
            emptyState.toggle(visibleCount === 0);
        }

        searchInput.on('keyup', updateMappingCards);

        $('[data-mapping-draft="true"]').on('click', function(e) {
            e.preventDefault();

            Swal.fire({
                icon: 'info',
                title: 'Mapping masih draft',
                text: 'Menu ini belum memiliki halaman konfigurasi.',
                confirmButtonText: 'Mengerti'
            });
        });

        updateMappingCards();
    });
</script>
