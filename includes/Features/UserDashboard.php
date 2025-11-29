<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

use WP_Query;

/**
 * Feature: Custom User Dashboard
 * Renders a "My Listings" grid using the Yardlii Card style.
 * Usage: [yardlii_user_dashboard]
 */
class UserDashboard {

    private string $coreUrl;
    private string $coreVersion;

    public function __construct(string $coreUrl, string $coreVersion)
    {
        $this->coreUrl = $coreUrl;
        $this->coreVersion = $coreVersion;
    }

    public function register(): void {
        add_shortcode('yardlii_user_dashboard', [$this, 'render_dashboard']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Handle Delete Action
        add_action('init', [$this, 'handle_delete_action']);
    }

    public function enqueue_assets(): void {
        wp_register_style(
            'yardlii-business-directory',
            $this->coreUrl . 'assets/css/business-directory.css',
            [],
            $this->coreVersion
        );
    }

    public function handle_delete_action(): void {
        if (isset($_GET['action'], $_GET['pid'], $_GET['_wpnonce']) && $_GET['action'] === 'yardlii_delete_post') {
            $post_id = (int) $_GET['pid'];
            
            if (!wp_verify_nonce($_GET['_wpnonce'], 'yardlii_delete_' . $post_id)) {
                return;
            }

            if (!is_user_logged_in() || get_current_user_id() !== (int) get_post_field('post_author', $post_id)) {
                return;
            }

            wp_trash_post($post_id);
            
            // Redirect to remove query args
            wp_redirect(remove_query_arg(['action', 'pid', '_wpnonce']));
            exit;
        }
    }

    /**
     * @param array<string, mixed>|string|null $atts
     * @return string
     */
    public function render_dashboard($atts): string {
        if (!is_user_logged_in()) {
            return '<div class="yardlii-alert">Please log in to view your dashboard.</div>';
        }

        wp_enqueue_style('yardlii-business-directory');

        $user_id = get_current_user_id();

        $args = [
            'post_type'      => 'listings',
            'author'         => $user_id,
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'pending', 'draft']
        ];

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            return '<div class="yardlii-no-results">You haven\'t posted any listings yet.</div>';
        }

        // Status Label Map
        $status_map = [
            'publish' => 'Live',
            'pending' => 'Under Review',
            'draft'   => 'Draft',
            'future'  => 'Scheduled',
            'trash'   => 'Trash'
        ];

        ob_start();
        
        echo '<div class="yardlii-directory-grid" style="--yardlii-card-width: 280px;">';

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $status  = get_post_status();
            
            // Status Badge Logic
            $status_label = isset($status_map[$status]) ? $status_map[$status] : ucfirst($status);
            $status_class = 'status-' . $status;

            // Edit Link Logic
            $base_edit_url = site_url('/edit/');
            
            // Safely get the edit page ID from WPUF settings
            $edit_page_id = $this->get_wpuf_option('edit_page_id', 'wpuf_frontend_posting');
            
            if ($edit_page_id) {
                $base_edit_url = get_permalink((int)$edit_page_id);
            }

            // FIX: Add WPUF security nonce
            $edit_url = add_query_arg([
                'pid'      => $post_id,
                '_wpnonce' => wp_create_nonce('wpuf_edit') 
            ], $base_edit_url);

            $delete_url = wp_nonce_url(
                add_query_arg(['action' => 'yardlii_delete_post', 'pid' => $post_id]),
                'yardlii_delete_' . $post_id
            );

            // Visuals
            $image_url = get_the_post_thumbnail_url($post_id, 'medium');
            ?>
            <div class="yardlii-business-card dashboard-card">
                <div class="ybc-header">
                    <?php if ($image_url): ?>
                        <img src="<?php echo esc_url($image_url); ?>" class="ybc-logo" alt="" />
                    <?php else: ?>
                        <div class="ybc-logo-placeholder"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                    
                    <span class="ybc-status-pill <?php echo esc_attr($status_class); ?>">
                        <?php echo esc_html($status_label); ?>
                    </span>
                </div>

                <div class="ybc-body">
                    <h3 class="ybc-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <div class="ybc-meta">
                         <span class="ybc-date"><i class="fas fa-calendar-alt"></i> <?php echo get_the_date(); ?></span>
                    </div>
                </div>

                <div class="ybc-footer actions">
                    <a href="<?php echo esc_url($edit_url); ?>" class="ybc-btn btn-edit"><i class="fas fa-pencil-alt"></i> Edit</a>
                    <a href="<?php echo esc_url($delete_url); ?>" class="ybc-btn btn-delete" onclick="return confirm('Are you sure you want to delete this listing?');"><i class="fas fa-trash"></i></a>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Safe wrapper for wpuf_get_option
     * @param string $option
     * @param string $section
     * @return mixed
     */
    private function get_wpuf_option(string $option, string $section) {
        if (function_exists('wpuf_get_option')) {
            return wpuf_get_option($option, $section);
        }
        return false;
    }
}