<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Requests;

use Yardlii\Core\Features\TrustVerification\Requests\CPT;

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

    public function defineColumns(array $columns): array
    {
        // Define new column order
        $new = [
            'cb'             => $columns['cb'],
            'title'          => __('User / Request', 'yardlii-core'), // WP puts title here
            'tv_form'        => __('Form', 'yardlii-core'),
            'tv_status'      => __('Status', 'yardlii-core'),
            'tv_role'        => __('Current Role', 'yardlii-core'),
            'tv_processed'   => __('Processed By', 'yardlii-core'),
            'date'           => __('Submitted', 'yardlii-core'),
        ];
        return $new;
    }

    public function renderColumn(string $column, int $post_id): void
    {
        switch ($column) {
            case 'tv_form':
                echo esc_html((string) get_post_meta($post_id, '_vp_form_id', true));
                break;

            case 'tv_status':
                $status = get_post_status($post_id);
                $label  = $this->getStatusLabel($status);
                // Use the same CSS classes defined in our admin CSS
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
                    echo esc_html($u ? $u->display_name : 'Unknown');
                } elseif ($aid === 0 && get_post_status($post_id) !== 'vp_pending') {
                     echo '<em style="color:#888;">System / Employer</em>';
                } else {
                    echo '—';
                }
                break;
        }
    }

    public function removeQuickEdit(array $actions, \WP_Post $post): array
    {
        if ($post->post_type === CPT::POST_TYPE) {
            unset($actions['inline hide-if-no-js']); // Remove Quick Edit
        }
        return $actions;
    }
    
    private function getStatusLabel($slug) {
        $map = [
            'vp_pending'  => 'Pending',
            'vp_approved' => 'Approved',
            'vp_rejected' => 'Rejected'
        ];
        return $map[$slug] ?? $slug;
    }
}