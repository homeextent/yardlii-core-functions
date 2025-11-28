/**
 * YARDLII Business Directory - Instant Search
 * Filters the grid based on data-search attributes.
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('yardlii-dir-search');
    const gridContainer = document.getElementById('yardlii-dir-grid');

    // Safety check: ensure elements exist before running
    if (!searchInput || !gridContainer) {
        return;
    }

    const cards = gridContainer.getElementsByClassName('yardlii-business-card');

    searchInput.addEventListener('keyup', function(e) {
        const filterText = e.target.value.toLowerCase();

        // Loop through all cards and toggle visibility
        for (let i = 0; i < cards.length; i++) {
            const card = cards[i];
            // We search against the pre-compiled keyword string in the data attribute
            const searchTerms = card.getAttribute('data-search');

            if (searchTerms && searchTerms.indexOf(filterText) > -1) {
                card.style.display = ""; // Show
            } else {
                card.style.display = "none"; // Hide
            }
        }
    });
});