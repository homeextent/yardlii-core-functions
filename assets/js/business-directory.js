/**
 * YARDLII Directory - Instant Search (Dynamic)
 * Supports multiple directory instances per page.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Find all search inputs on the page by class 
    const searchInputs = document.querySelectorAll('.yardlii-dir-search-input');

    if (searchInputs.length === 0) {
        return;
    }

    searchInputs.forEach(function(input) {
        input.addEventListener('keyup', function(e) {
            const filterText = e.target.value.toLowerCase();
            
            // Find the parent wrapper to ensure we only filter the correct grid
            const wrapper = input.closest('.yardlii-directory-wrapper');
            if (!wrapper) return;

            // Find the grid within this specific wrapper
            const grid = wrapper.querySelector('.yardlii-directory-grid');
            if (!grid) return;

            const cards = grid.getElementsByClassName('yardlii-business-card');

            // Loop through cards in this specific grid
            for (let i = 0; i < cards.length; i++) {
                const card = cards[i];
                const searchTerms = card.getAttribute('data-search');

                if (searchTerms && searchTerms.indexOf(filterText) > -1) {
                    card.style.display = ""; // Show
                } else {
                    card.style.display = "none"; // Hide
                }
            }
        });
    });
});