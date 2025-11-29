<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

use WP_User;
use WP_Query;

/**
 * Feature: Elementor Custom Query Modifiers
 * Handles backend logic for specific Elementor Query IDs.
 */
class ElementorQueryMods {

    public function register(): void {
        /**
         * Fix for Author Archive Loop Grids
         * Usage: Set Query ID to 'yardlii_author_listings' in Elementor
         */
        add_action('elementor/query/yardlii_author_listings', [$this, 'filter_by_profile_author']);
    }

    /**
     * Forces the query to filter by the Author ID of the profile being viewed.
     * @param WP_Query $query The Elementor query object.
     */
    public function filter_by_profile_author($query): void {
        // 1. Get the object currently being viewed (The User/Author)
        $current_object = get_queried_object();

        // 2. Validation: Ensure we are actually on an Author Archive page
        if ($current_object instanceof WP_User) {
            
            // 3. Force the query to match this author
            $query->set('author', $current_object->ID);

            // 4. Force Post Type to Listings
            $query->set('post_type', 'listings');
        }
    }
}