/**
 * YARDLII Directory - Dual Filter Logic (Auto-Discovery + Trigger Modes)
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Handle Bundled Wrappers (Search and Grid together)
    const wrappers = document.querySelectorAll('.yardlii-directory-wrapper');
    if (wrappers.length > 0) {
        wrappers.forEach(function(wrapper) {
            const tradeSelect = wrapper.querySelector('.yardlii-filter-trade');
            const locInput    = wrapper.querySelector('.yardlii-filter-location');
            const submitBtn   = wrapper.querySelector('.yardlii-dir-submit');
            const grid        = wrapper.querySelector('.yardlii-directory-grid');
            
            // Get Trigger Mode (default instant)
            const trigger = wrapper.getAttribute('data-trigger') || 'instant';
            
            if (grid && (tradeSelect || locInput)) {
                setupFilterLogic(tradeSelect, locInput, submitBtn, grid, trigger);
            }
        });
    }

    // 2. Handle Standalone Search Bars (Decoupled)
    const remoteBars = document.querySelectorAll('.yardlii-standalone-search');
    if (remoteBars.length > 0) {
        remoteBars.forEach(function(bar) {
            let targetId = bar.getAttribute('data-target');
            let grid = null;

            if (targetId) {
                // Explicit Targeting
                grid = document.getElementById(targetId);
            } else {
                // AUTO-DISCOVERY: Find the first grid on the page
                grid = document.querySelector('.yardlii-directory-grid');
                if (grid) {
                    console.log('[YARDLII] Auto-discovered directory grid for search bar.');
                }
            }

            if (!grid) return;

            const tradeSelect = bar.querySelector('.yardlii-filter-trade');
            const locInput    = bar.querySelector('.yardlii-filter-location');
            const submitBtn   = bar.querySelector('.yardlii-dir-submit');
            const trigger     = bar.getAttribute('data-trigger') || 'instant';

            setupFilterLogic(tradeSelect, locInput, submitBtn, grid, trigger);
        });
    }

    /**
     * Shared Filter Logic
     */
    function setupFilterLogic(tradeSelect, locInput, submitBtn, grid, trigger) {
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

        if (trigger === 'button' && submitBtn) {
            // Button Mode: Only fire on click or Enter key
            submitBtn.addEventListener('click', runFilter);
            if (locInput) {
                locInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') runFilter();
                });
            }
        } else {
            // Instant Mode: Fire on change/keyup
            if (tradeSelect) tradeSelect.addEventListener('change', runFilter);
            if (locInput)    locInput.addEventListener('keyup', runFilter);
        }
    }
});