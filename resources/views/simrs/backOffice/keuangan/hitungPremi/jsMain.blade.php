<script>
    $(function() {
        const menuCards = $('.generator-menu-card');
        const emptyState = $('#generatorMenuEmpty');
        const periodInput = $('#periodeGeneratePremi');
        const generatorLinks = $('.btn-open-generator[data-base-url]');

        function filterGeneratorMenu() {
            const keyword = $('#searchGeneratorPremi').val().toLowerCase().trim();
            let visibleCount = 0;

            menuCards.each(function() {
                const card = $(this);
                const haystack = `${card.data('title')} ${card.data('search') || ''}`.toLowerCase();
                const isVisible = !keyword || haystack.includes(keyword);

                card.toggle(isVisible);
                if (isVisible) {
                    visibleCount++;
                }
            });

            $('#generatorMenuCount').text(visibleCount);
            emptyState.toggle(visibleCount === 0);
        }

        function updateGeneratorLinks() {
            const periode = periodInput.val();

            generatorLinks.each(function() {
                const link = $(this);
                const url = new URL(link.data('base-url'), window.location.origin);

                if (periode) {
                    url.searchParams.set('periode', periode);
                }

                link.attr('href', url.toString());
            });
        }

        $('#searchGeneratorPremi').on('input', filterGeneratorMenu);
        periodInput.on('change', updateGeneratorLinks);
        updateGeneratorLinks();
    });
</script>
