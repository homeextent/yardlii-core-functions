/**
 * YARDLII Directory - Fixed Button Logic (v3.23)
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Bundled Wrappers
    const wrappers = document.querySelectorAll('.yardlii-directory-wrapper');
    if (wrappers.length > 0) {
        wrappers.forEach(function(wrapper) {
            const tradeSelect = wrapper.querySelector('.yardlii-filter-trade');
            const locInput    = wrapper.querySelector('.yardlii-filter-location');
            const submitBtn   = wrapper.querySelector('.yardlii-dir-submit');
            const grid        = wrapper.querySelector('.yardlii-directory-grid');
            const trigger     = wrapper.getAttribute('data-trigger') || 'instant';
            
            if (grid && (tradeSelect || locInput)) {
                setupFilterLogic(tradeSelect, locInput, submitBtn, grid, trigger);
            }
        });
    }

    // 2. Decoupled Search Bars
    const remoteBars = document.querySelectorAll('.yardlii-standalone-search');
    if (remoteBars.length > 0) {
        remoteBars.forEach(function(bar) {
            let targetId = bar.getAttribute('data-target');
            let grid = null;

            if (targetId) {
                grid = document.getElementById(targetId);
            } else {
                // Auto-discovery
                grid = document.querySelector('.yardlii-directory-grid');
            }

            if (!grid) return;

            const tradeSelect = bar.querySelector('.yardlii-filter-trade');
            const locInput    = bar.querySelector('.yardlii-filter-location');
            const submitBtn   = bar.querySelector('.yardlii-dir-submit');
            const trigger     = bar.getAttribute('data-trigger') || 'instant';

            setupFilterLogic(tradeSelect, locInput, submitBtn, grid, trigger);
        });
    }

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

        if (trigger === 'button') {
            // STRICT BUTTON MODE
            // 1. Click Listener on Button
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent form sub if inside form
                    runFilter();
                });
            }
            // 2. Enter Key Listener on Text Input (Standard UX)
            if (locInput) {
                locInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        runFilter();
                    }
                });
            }
            // NOTE: We intentionally DO NOT listen to 'change' on select here.
        } else {
            // INSTANT MODE
            if (tradeSelect) tradeSelect.addEventListener('change', runFilter);
            if (locInput)    locInput.addEventListener('keyup', runFilter);
        }
    }
});