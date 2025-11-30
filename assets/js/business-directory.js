/**
 * YARDLII Directory - Dual Filter Logic (Simplified)
 * Relies on global WPUF Autocomplete script for location logic.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Bundled Wrappers
    const wrappers = document.querySelectorAll('.yardlii-directory-wrapper');
    if (wrappers.length > 0) {
        wrappers.forEach(function(wrapper) {
            const tradeSelect = wrapper.querySelector('.yardlii-filter-trade');
            const locInput    = wrapper.querySelector('.yardlii-filter-location');
            const submitBtn   = wrapper.querySelector('.yardlii-dir-submit');
            const resetBtn    = wrapper.querySelector('.yardlii-dir-reset');
            const grid        = wrapper.querySelector('.yardlii-directory-grid');
            const trigger     = wrapper.getAttribute('data-trigger') || 'instant';
            
            if (grid && (tradeSelect || locInput)) {
                setupFilterLogic(tradeSelect, locInput, submitBtn, resetBtn, grid, trigger);
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
                grid = document.querySelector('.yardlii-directory-grid');
            }

            if (!grid) return;

            const tradeSelect = bar.querySelector('.yardlii-filter-trade');
            const locInput    = bar.querySelector('.yardlii-filter-location');
            const submitBtn   = bar.querySelector('.yardlii-dir-submit');
            const resetBtn    = bar.querySelector('.yardlii-dir-reset');
            const trigger     = bar.getAttribute('data-trigger') || 'instant';

            setupFilterLogic(tradeSelect, locInput, submitBtn, resetBtn, grid, trigger);
        });
    }

   function setupFilterLogic(tradeSelect, locInput, submitBtn, resetBtn, grid, trigger) {
        const cards = grid.getElementsByClassName('yardlii-business-card');

        function runFilter() {
            const tradeVal = tradeSelect ? tradeSelect.value.toLowerCase() : '';
            const locVal   = locInput ? locInput.value.toLowerCase().trim() : '';

            // Toggle Reset Button
            if (resetBtn) {
                if (tradeVal !== '' || locVal !== '') {
                    resetBtn.style.display = 'inline-block';
                } else {
                    resetBtn.style.display = 'none';
                }
            }

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

        // --- NEW: GOOGLE AUTOCOMPLETE INTEGRATION ---
        if (locInput) {
            const initDirAutocomplete = () => {
                // Prevent double initialization
                if (locInput.dataset.dirAutoInit) return; 
                
                // Safety Check: Is Google API actually ready?
                if (typeof google === 'undefined' || !google.maps || !google.maps.places) return;

                const autocomplete = new google.maps.places.Autocomplete(locInput, {
                    types: ['(cities)'],
                    componentRestrictions: { country: ['ca', 'us'] }
                });
                
                // When a city is selected from the dropdown...
                autocomplete.addListener('place_changed', function() {
                    // If we are in "Instant Search" mode, trigger the filter immediately.
                    // If in "Button" mode, do nothing (user must still click Search).
                    if (trigger !== 'button') {
                        runFilter();
                    }
                });
                
                locInput.dataset.dirAutoInit = 'true';
            };

            // Strategy A: Listen for the Global Router event (Async loading)
            document.addEventListener('yardliiGoogleMapsLoaded', initDirAutocomplete);

            // Strategy B: Check if Google is already loaded (Cached/Sync loading)
            if (typeof google !== 'undefined' && google.maps && google.maps.places) {
                initDirAutocomplete();
            }
        }
        // ---------------------------------------------

        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                if (tradeSelect) tradeSelect.selectedIndex = 0;
                if (locInput) locInput.value = '';
                runFilter(); 
            });
        }

        // --- EVENT LISTENERS ---
        if (trigger === 'button') {
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    runFilter();
                });
            }
            if (locInput) {
                locInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        runFilter();
                    }
                });
            }
        } else {
            // Instant Mode
            if (tradeSelect) tradeSelect.addEventListener('change', runFilter);
            
            if (locInput) {
                // Listen for standard typing
                locInput.addEventListener('keyup', runFilter);
                // Listen for changes (like Autocomplete selection or paste)
                locInput.addEventListener('change', runFilter);
            }
        }
    }
});