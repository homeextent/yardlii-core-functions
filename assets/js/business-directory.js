/**
 * YARDLII Directory - Dual Filter Logic (Bundled + Decoupled)
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Handle Bundled Wrappers (Search and Grid together)
    const wrappers = document.querySelectorAll('.yardlii-directory-wrapper');
    if (wrappers.length > 0) {
        wrappers.forEach(function(wrapper) {
            // Only process if this wrapper actually HAS a search bar inside it
            // (If hide_search="true" was used, these won't exist inside)
            const tradeSelect = wrapper.querySelector('.yardlii-filter-trade');
            const locInput    = wrapper.querySelector('.yardlii-filter-location');
            const grid        = wrapper.querySelector('.yardlii-directory-grid');
            
            if (grid && (tradeSelect || locInput)) {
                setupFilterLogic(tradeSelect, locInput, grid);
            }
        });
    }

    // 2. Handle Standalone Search Bars (Decoupled)
    const remoteBars = document.querySelectorAll('.yardlii-standalone-search');
    if (remoteBars.length > 0) {
        remoteBars.forEach(function(bar) {
            const targetId = bar.getAttribute('data-target');
            if (!targetId) return;

            const grid = document.getElementById(targetId);
            if (!grid) return;

            const tradeSelect = bar.querySelector('.yardlii-filter-trade');
            const locInput    = bar.querySelector('.yardlii-filter-location');

            setupFilterLogic(tradeSelect, locInput, grid);
        });
    }

    /**
     * Shared Filter Logic
     * Attaches listeners to inputs and filters the specific grid.
     */
    function setupFilterLogic(tradeSelect, locInput, grid) {
        const cards = grid.getElementsByClassName('yardlii-business-card');

        function runFilter() {
            const tradeVal = tradeSelect ? tradeSelect.value.toLowerCase() : '';
            const locVal   = locInput ? locInput.value.toLowerCase().trim() : '';

            for (let i = 0; i < cards.length; i++) {
                const card = cards[i];
                const cardTrade = card.getAttribute('data-trade') || '';
                const cardLoc   = card.getAttribute('data-location') || '';

                let tradeMatch = true;
                if (tradeVal !== '') {
                    tradeMatch = cardTrade.indexOf(tradeVal) > -1;
                }

                let locMatch = true;
                if (locVal !== '') {
                    locMatch = cardLoc.indexOf(locVal) > -1;
                }

                if (tradeMatch && locMatch) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            }
        }

        if (tradeSelect) tradeSelect.addEventListener('change', runFilter);
        if (locInput)    locInput.addEventListener('keyup', runFilter);
    }
});