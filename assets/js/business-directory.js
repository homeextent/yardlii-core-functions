/**
 * YARDLII Directory - Dual Filter Logic (Trade + Location)
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Find all Directory Wrappers on the page
    const wrappers = document.querySelectorAll('.yardlii-directory-wrapper');

    if (wrappers.length === 0) return;

    wrappers.forEach(function(wrapper) {
        
        const tradeSelect = wrapper.querySelector('.yardlii-filter-trade');
        const locInput    = wrapper.querySelector('.yardlii-filter-location');
        const grid        = wrapper.querySelector('.yardlii-directory-grid');
        
        if (!grid) return; // Safety check

        const cards = grid.getElementsByClassName('yardlii-business-card');

        // The Filter Function
        function runFilter() {
            // Get values
            const tradeVal = tradeSelect ? tradeSelect.value.toLowerCase() : '';
            const locVal   = locInput ? locInput.value.toLowerCase().trim() : '';

            for (let i = 0; i < cards.length; i++) {
                const card = cards[i];
                
                // Get Card Data
                const cardTrade = card.getAttribute('data-trade') || '';
                const cardLoc   = card.getAttribute('data-location') || '';

                // Logic:
                // 1. Trade Match: If dropdown is empty, match everything. Else match exact string.
                // 2. Loc Match: If input is empty, match everything. Else match partial string (indexOf).
                
                let tradeMatch = true;
                if (tradeVal !== '') {
                    // Check if card trade contains the selected value
                    tradeMatch = cardTrade.indexOf(tradeVal) > -1;
                }

                let locMatch = true;
                if (locVal !== '') {
                    locMatch = cardLoc.indexOf(locVal) > -1;
                }

                // Show if BOTH match
                if (tradeMatch && locMatch) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            }
        }

        // Attach Listeners
        if (tradeSelect) {
            tradeSelect.addEventListener('change', runFilter);
        }
        if (locInput) {
            locInput.addEventListener('keyup', runFilter);
        }
    });
});