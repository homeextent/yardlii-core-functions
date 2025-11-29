/**
 * YARDLII: Elementor Tab Deep Linker
 * Allows opening specific tabs via URL parameters (e.g. ?tab=2)
 * Supports both Horizontal and Vertical Elementor Tabs.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Parse URL params
    const urlParams = new URLSearchParams(window.location.search);
    const targetTab = urlParams.get('tab'); // e.g. '2' or 'edit-profile'

    if (!targetTab) return;

    // 2. Elementor Tab Logic
    // We wait a moment for Elementor to initialize
    setTimeout(function() {
        // Find the tab widget container
        // Note: You must give your Tabs Widget a CSS ID of 'yardlii-dashboard-tabs' in Elementor
        const tabContainer = document.getElementById('yardlii-dashboard-tabs');
        
        if (!tabContainer) return;

        // Map text slugs to index if needed, or just use numeric index (1-based)
        // Assuming targetTab is a number (1, 2, 3, 4)
        const tabIndex = parseInt(targetTab);

        if (!isNaN(tabIndex)) {
            // Find the tab title element
            // Elementor structure: .e-n-tabs-heading > .e-n-tab-title[data-tab="..."]
            // Or standard: .elementor-tab-title
            
            // Try Standard/Legacy Tabs
            const title = tabContainer.querySelector(`.elementor-tab-title[data-tab="${tabIndex}"]`);
            if (title) {
                title.click();
                title.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            // Try Nested/Container Tabs (New Elementor)
            // These often rely on aria-controls
            const nestedTitle = tabContainer.querySelector(`#e-n-tabs-title-${tabIndex}`); // Elementor often uses this ID pattern
             if (nestedTitle) {
                nestedTitle.click();
            }
        }
    }, 500); // 500ms delay ensures Elementor JS is ready
});