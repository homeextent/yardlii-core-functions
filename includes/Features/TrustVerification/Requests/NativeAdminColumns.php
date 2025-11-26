<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Requests;

use Yardlii\Core\Features\TrustVerification\Requests\CPT;
use WP_Post;

/**
 * Handles the columns and display for the native 'verification_request' admin screen.
 */
final class NativeAdminColumns
{
    public function register(): void
    {
        add_filter('manage_' . CPT::POST_TYPE . '_posts_columns', [$this, 'defineColumns']);
        add_action('manage_' . CPT::POST_TYPE . '_posts_custom_column', [$this, 'renderColumn'], 10, 2);
        add_filter('post_row_actions', [$this, 'removeQuickEdit'], 10, 2);
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function defineColumns(array $columns): array
    {
        // Reorder and rename
        // Helper to preserve checkbox if present
        $cb = $columns['cb'] ?? '<input type="checkbox" />';

        return [
            'cb'             => $cb,
            'title'          => __('User / Request', 'yardlii-core'), // WordPress puts title here automatically
            'tv_form'        => __('Form', 'yardlii-core'),
            'tv_status'      => __('Status', 'yardlii-core'),
            'tv_role'        => __('Current Role', 'yardlii-core'),
            'tv_processed'   => __('Processed By', 'yardlii-core'),
            'date'           => __('Submitted', 'yardlii-core'),
        ];
    }

    public function renderColumn(string $column, int $post_id): void
    {
        switch ($column) {
            case 'tv_form':
                echo esc_html((string) get_post_meta($post_id, '_vp_form_id', true));
                break;

            case 'tv_status':
                $status = get_post_status($post_id);
                if (!$status) {
                    echo '—';
                    break;
                }
                
                $label  = $this->getStatusLabel($status);
                // Use the same CSS classes we defined in our admin.css
                $class  = str_replace('vp_', '', $status); 
                printf('<span class="status-badge status-badge--%s">%s</span>', esc_attr($class), esc_html($label));
                
                // Employer Vouch Icon
                $type = get_post_meta($post_id, '_vp_verification_type', true);
                if ($status === 'vp_pending' && $type === 'employer_vouch') {
                     echo ' <span class="dashicons dashicons-businessperson" title="Waiting for Employer" style="color:#888;"></span>';
                }
                break;

            case 'tv_role':
                $uid = (int) get_post_meta($post_id, '_vp_user_id', true);
                $user = get_userdata($uid);
                if ($user) {
                    $roles = (array) $user->roles;
                    echo esc_html(implode(', ', $roles));
                } else {
                    echo '<span style="color:#a00;">(Deleted User)</span>';
                }
                break;

            case 'tv_processed':
                $aid = (int) get_post_meta($post_id, '_vp_processed_by', true);
                if ($aid > 0) {
                    $u = get_userdata($aid);
                    echo esc_html($u ? ($u->display_name ?: $u->user_login) : 'Unknown');
                } elseif ($aid === 0 && get_post_status($post_id) !== 'vp_pending') {
                     echo '<em style="color:#888;">System / Employer</em>';
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * @param array<string, string> $actions
     * @param WP_Post $post
     * @return array<string, string>
     */
    public function removeQuickEdit(array $actions, WP_Post $post): array
    {
        if ($post->post_type === CPT::POST_TYPE) {
            // 'inline hide-if-no-js' is the key used by WP for "Quick Edit"
            if (isset($actions['inline hide-if-no-js'])) {
                unset($actions['inline hide-if-no-js']);
            }
        }
        return $actions;
    }
    
    // Helper to translate status slugs
    private function getStatusLabel(string $slug): string {
        $map = [
            'vp_pending'  => 'Pending',
            'vp_approved' => 'Approved',
            'vp_rejected' => 'Rejected'
        ];
        return $map[$slug] ?? $slug;
    }
}