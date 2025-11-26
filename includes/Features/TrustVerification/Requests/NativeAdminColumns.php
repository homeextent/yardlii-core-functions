<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Requests;

use Yardlii\Core\Features\TrustVerification\Requests\CPT;
use WP_Post;
use WP_Query;

/**
 * Controls the native WordPress Admin List for Verification Requests.
 */
final class NativeAdminColumns
{
    public function register(): void
    {
        // 1. Columns
        add_filter('manage_' . CPT::POST_TYPE . '_posts_columns', [$this, 'defineColumns']);
        add_action('manage_' . CPT::POST_TYPE . '_posts_custom_column', [$this, 'renderColumn'], 10, 2);
        
        // 2. Row Actions (Hover links)
        add_filter('post_row_actions', [$this, 'handleRowActions'], 50, 2); // Priority 50 to run last
        
        // 3. Bulk Actions
        add_filter('bulk_actions-edit-' . CPT::POST_TYPE, [$this, 'registerBulkActions']);
        add_filter('handle_bulk_actions-edit-' . CPT::POST_TYPE, [$this, 'handleBulkProcessing'], 10, 3);

        // 4. Views (Pending | Approved | Rejected links)
        add_filter('views_edit-' . CPT::POST_TYPE, [$this, 'registerStatusViews']);

        // 5. "Send Copy" Checkbox
        add_action('restrict_manage_posts', [$this, 'renderToolbarExtras']);

        // 6. Fix "All" View (Query Modification)
        add_action('pre_get_posts', [$this, 'modifyMainQuery']);
    }

    /**
     * 1. Define Columns (Added Processed Date)
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function defineColumns(array $columns): array
    {
        $cb = $columns['cb'] ?? '<input type="checkbox" />';
        return [
            'cb'             => $cb,
            'title'          => __('User / Request', 'yardlii-core'), 
            'tv_form'        => __('Form', 'yardlii-core'),
            'tv_status'      => __('Status', 'yardlii-core'),
            'tv_role'        => __('Current Role', 'yardlii-core'),
            'tv_processed'   => __('Processed By', 'yardlii-core'),
            'tv_proc_date'   => __('Processed Date', 'yardlii-core'), // [NEW]
            'date'           => __('Submitted', 'yardlii-core'),
        ];
    }

    /**
     * 2. Render Column Content
     */
    public function renderColumn(string $column, int $post_id): void
    {
        switch ($column) {
            case 'tv_form':
                echo esc_html((string) get_post_meta($post_id, '_vp_form_id', true));
                break;

            case 'tv_status':
                $status = get_post_status($post_id);
                if (!$status) { echo '—'; break; }
                
                // Fix class generation for pills
                $class = str_replace(['vp_', '_'], ['', '-'], $status); 
                $label = $this->getStatusLabel($status);
                
                printf('<span class="status-badge status-badge--%s">%s</span>', esc_attr($class), esc_html($label));
                
                // Employer Vouch Icon
                $type = get_post_meta($post_id, '_vp_verification_type', true);
                if ($status === 'vp_pending' && $type === 'employer_vouch') {
                     echo ' <span class="dashicons dashicons-businessperson" title="Waiting for Employer" style="color:#888;margin-left:4px;"></span>';
                }
                break;

            case 'tv_role':
                $uid = (int) get_post_meta($post_id, '_vp_user_id', true);
                $user = get_userdata($uid);
                if ($user) {
                    $roles = (array) $user->roles;
                    $slug = reset($roles);
                    $role_name = ucfirst($slug); 
                    global $wp_roles;
                    if (isset($wp_roles->role_names[$slug])) {
                        $role_name = $wp_roles->role_names[$slug];
                    }
                    printf('%s<br><small style="color:#888;">%s</small>', esc_html($role_name), esc_html($slug));
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

            case 'tv_proc_date': // [NEW]
                $ts = (string) get_post_meta($post_id, '_vp_processed_date', true);
                if ($ts) {
                    echo esc_html(wp_date(get_option('date_format'), strtotime($ts)));
                    echo '<br><small style="color:#888;">' . esc_html(wp_date(get_option('time_format'), strtotime($ts))) . '</small>';
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * 3. Row Actions (Approve | Reject | History)
     * @param array<string, string> $actions
     * @param WP_Post $post
     * @return array<string, string>
     */
    public function handleRowActions(array $actions, WP_Post $post): array
    {
        if ($post->post_type !== CPT::POST_TYPE) return $actions;

        // Force remove "Edit" and "Quick Edit" which are useless here
        unset($actions['edit'], $actions['inline hide-if-no-js']);

        $status = $post->post_status;
        $nonce  = wp_create_nonce('yardlii_tv_action_nonce');
        $base   = admin_url('admin.php');

        $new_actions = [];

        // Approve / Reject (Only for Pending)
        if ($status === 'vp_pending') {
            $approve_url = add_query_arg(['action' => 'yardlii_tv_approve', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            // Note: We use 'yl_approve' key to avoid WP conflicts
            $new_actions['yl_approve'] = sprintf('<a href="%s" style="color:green;font-weight:bold;">%s</a>', esc_url($approve_url), __('Approve', 'yardlii-core'));

            $reject_url = add_query_arg(['action' => 'yardlii_tv_reject', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['yl_reject'] = sprintf('<a href="%s" style="color:#d63638;">%s</a>', esc_url($reject_url), __('Reject', 'yardlii-core'));
        } 
        // Reopen / Resend (For processed items)
        else {
            $reopen_url = add_query_arg(['action' => 'yardlii_tv_reopen', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['yl_reopen'] = sprintf('<a href="%s">%s</a>', esc_url($reopen_url), __('Re-open', 'yardlii-core'));

            $resend_url = add_query_arg(['action' => 'yardlii_tv_resend', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['yl_resend'] = sprintf('<a href="%s">%s</a>', esc_url($resend_url), __('Resend Email', 'yardlii-core'));
        }

        // History (Always visible)
        $hist_nonce = wp_create_nonce('yardlii_tv_history');
        $new_actions['yl_history'] = sprintf(
            '<a href="#" data-action="tv-row-history" data-post="%d" data-nonce="%s">%s</a>',
            $post->ID,
            esc_attr($hist_nonce),
            __('History', 'yardlii-core')
        );

        // Combine: Ours first, then Trash
        return $new_actions + $actions;
    }

    /**
     * 4. Fix "All" View & Filters
     * @param WP_Query $query
     */
    public function modifyMainQuery(WP_Query $query): void
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== CPT::POST_TYPE) {
            return;
        }

        // FIX: If no status is selected (The "All" view), force WP to include our custom statuses.
        // Without this, WP defaults to 'publish', which returns 0 results.
        if (empty($_GET['post_status'])) {
            $query->set('post_status', ['vp_pending', 'vp_approved', 'vp_rejected']);
        }

        // Support "Employer Vouch" Filter
        if (isset($_GET['verification_type']) && $_GET['verification_type'] === 'employer_vouch') {
             $meta_query = $query->get('meta_query') ?: [];
             $meta_query[] = [
                 'key' => '_vp_verification_type',
                 'value' => 'employer_vouch',
                 'compare' => '='
             ];
             $query->set('meta_query', $meta_query);
        }
    }

    /**
     * 5. Register Status Views (Top Filters)
     * Overrides the default views to show Approved/Rejected/Employer
     */
    public function registerStatusViews(array $views): array
    {
        $base = admin_url('edit.php?post_type=' . CPT::POST_TYPE);
        
        // Count Employer Vouches
        $emp_count = (new WP_Query([
            'post_type' => CPT::POST_TYPE,
            'post_status' => 'any',
            'meta_query' => [['key' => '_vp_verification_type', 'value' => 'employer_vouch']]
        ]))->found_posts;

        $statuses = [
            'all'         => ['label' => __('All', 'yardlii-core'),      'status' => ''],
            'vp_pending'  => ['label' => __('Pending', 'yardlii-core'),  'status' => 'vp_pending'],
            'vp_approved' => ['label' => __('Approved', 'yardlii-core'), 'status' => 'vp_approved'],
            'vp_rejected' => ['label' => __('Rejected', 'yardlii-core'), 'status' => 'vp_rejected'],
        ];

        $new_views = [];
        $current = $_GET['post_status'] ?? '';
        $is_emp  = isset($_GET['verification_type']);

        foreach ($statuses as $key => $data) {
            $args = ['post_type' => CPT::POST_TYPE, 'post_status' => $data['status']];
            if ($key === 'all') $args['post_status'] = ['vp_pending', 'vp_approved', 'vp_rejected'];
            
            $count = (new WP_Query($args))->found_posts;
            
            $class = ($current === $data['status'] && !$is_emp) ? 'current' : '';
            $url   = $key === 'all' ? $base : add_query_arg('post_status', $data['status'], $base);
            
            $new_views[$key] = sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url($url), $class, esc_html($data['label']), $count
            );
        }

        // Add Employer Vouch View
        $emp_class = $is_emp ? 'current' : '';
        $new_views['employer'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url(add_query_arg('verification_type', 'employer_vouch', $base)),
            $emp_class,
            __('Employer Vouch', 'yardlii-core'),
            $emp_count
        );
        
        // Keep Trash view if it exists
        if (isset($views['trash'])) {
            $new_views['trash'] = $views['trash'];
        }

        return $new_views;
    }

    /**
     * 6. Register Bulk Actions
     */
    public function registerBulkActions(array $actions): array
    {
        unset($actions['edit']); 
        return [
            'yardlii_tv_bulk_approve' => __('Approve', 'yardlii-core'),
            'yardlii_tv_bulk_reject'  => __('Reject', 'yardlii-core'),
            'yardlii_tv_bulk_reopen'  => __('Re-open', 'yardlii-core'),
            'yardlii_tv_bulk_resend'  => __('Resend Email', 'yardlii-core'),
        ] + $actions; // Prepend ours
    }

    /**
     * 7. Handle Bulk Action Logic
     */
    public function handleBulkProcessing(string $redirect_to, string $action, array $post_ids): string
    {
        $map = [
            'yardlii_tv_bulk_approve' => 'approve',
            'yardlii_tv_bulk_reject'  => 'reject',
            'yardlii_tv_bulk_reopen'  => 'reopen',
            'yardlii_tv_bulk_resend'  => 'resend',
        ];

        if (!isset($map[$action])) return $redirect_to;

        $decisions = new Decisions();
        $processed = 0;
        $sendCopy = !empty($_REQUEST['tv_send_copy']);

        foreach ($post_ids as $id) {
            if ($decisions->applyDecision((int)$id, $map[$action], ['cc_self' => $sendCopy])) {
                $processed++;
            }
        }

        return add_query_arg(['tv_notice' => 'bulk_' . $map[$action], 'tv_count' => $processed], $redirect_to);
    }

    /**
     * 8. Render "Send Copy" Checkbox in Toolbar
     */
    public function renderToolbarExtras(string $post_type): void
    {
        if ($post_type !== CPT::POST_TYPE) return;
        ?>
        <label style="margin-left:10px;line-height:30px;vertical-align:middle;font-size:13px;">
            <input type="checkbox" name="tv_send_copy" value="1" style="margin-top:-2px;"> 
            <?php esc_html_e('Send me a copy', 'yardlii-core'); ?>
        </label>
        <?php
    }

    private function getStatusLabel(string $slug): string {
        $map = [
            'vp_pending'  => 'Pending',
            'vp_approved' => 'Approved',
            'vp_rejected' => 'Rejected'
        ];
        return $map[$slug] ?? ucfirst(str_replace(['vp_', '_'], ['', ' '], $slug));
    }
}