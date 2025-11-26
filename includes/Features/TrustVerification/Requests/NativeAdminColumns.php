<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Requests;

use Yardlii\Core\Features\TrustVerification\Requests\CPT;
use WP_Post;
use WP_Query;

/**
 * Controls the native WordPress Admin List for Verification Requests.
 * Handles: Columns, Row Actions, Bulk Actions, and Status Filtering.
 */
final class NativeAdminColumns
{
    public function register(): void
    {
        // 1. Columns & Display
        add_filter('manage_' . CPT::POST_TYPE . '_posts_columns', [$this, 'defineColumns']);
        add_action('manage_' . CPT::POST_TYPE . '_posts_custom_column', [$this, 'renderColumn'], 10, 2);
        
        // 2. Row Actions (Hover links)
        add_filter('post_row_actions', [$this, 'handleRowActions'], 10, 2);
        
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
     * 1. Define Columns
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
            'tv_role'        => __('Role', 'yardlii-core'),
            'tv_processed'   => __('Processed By', 'yardlii-core'),
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
                
                $label  = $this->getStatusLabel($status);
                $class  = str_replace('vp_', '', $status); 
                
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
                    // Show the first role (usually primary)
                    $role_slug = reset($roles);
                    $role_name = ucfirst($role_slug); // Simple cap first
                    
                    // Try to get fancy WP role name
                    global $wp_roles;
                    if (isset($wp_roles->role_names[$role_slug])) {
                        $role_name = $wp_roles->role_names[$role_slug];
                    }

                    printf('%s<br><small style="color:#888;">%s</small>', esc_html($role_name), esc_html($role_slug));
                } else {
                    echo '<span style="color:#a00;">(Deleted User)</span>';
                }
                break;

            case 'tv_processed':
                $aid = (int) get_post_meta($post_id, '_vp_processed_by', true);
                if ($aid > 0) {
                    $u = get_userdata($aid);
                    echo esc_html($u ? ($u->display_name ?: $u->user_login) : 'Unknown');
                    
                    $ts = (string) get_post_meta($post_id, '_vp_processed_date', true);
                    if ($ts) {
                        echo '<br><small style="color:#888;">' . esc_html(wp_date(get_option('date_format'), strtotime($ts))) . '</small>';
                    }
                } elseif ($aid === 0 && get_post_status($post_id) !== 'vp_pending') {
                     echo '<em style="color:#888;">System / Employer</em>';
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

        // Remove standard actions we don't want
        unset($actions['inline hide-if-no-js']); // Quick Edit
        unset($actions['edit']); // Standard Edit
        // Keep 'trash'

        $status = $post->post_status;
        $nonce  = wp_create_nonce('yardlii_tv_action_nonce');
        $base   = admin_url('admin.php');

        $new_actions = [];

        // Approve / Reject (Only for Pending)
        if ($status === 'vp_pending') {
            $approve_url = add_query_arg(['action' => 'yardlii_tv_approve', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['approve'] = sprintf('<a href="%s" style="color:green;font-weight:bold;">%s</a>', esc_url($approve_url), __('Approve', 'yardlii-core'));

            $reject_url = add_query_arg(['action' => 'yardlii_tv_reject', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['reject'] = sprintf('<a href="%s" style="color:#a00;">%s</a>', esc_url($reject_url), __('Reject', 'yardlii-core'));
        } 
        // Reopen / Resend (For processed items)
        else {
            $reopen_url = add_query_arg(['action' => 'yardlii_tv_reopen', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['reopen'] = sprintf('<a href="%s">%s</a>', esc_url($reopen_url), __('Re-open', 'yardlii-core'));

            $resend_url = add_query_arg(['action' => 'yardlii_tv_resend', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['resend'] = sprintf('<a href="%s">%s</a>', esc_url($resend_url), __('Resend Email', 'yardlii-core'));
        }

        // History (Always visible)
        $hist_nonce = wp_create_nonce('yardlii_tv_history');
        $new_actions['history'] = sprintf(
            '<a href="#" data-action="tv-row-history" data-post="%d" data-nonce="%s">%s</a>',
            $post->ID,
            esc_attr($hist_nonce),
            __('History', 'yardlii-core')
        );

        // Merge: Put our actions first, then trash
        return array_merge($new_actions, $actions);
    }

    /**
     * 4. Fix "All" View Logic
     * Force WP to show our custom statuses when no status is selected
     */
    public function modifyMainQuery(WP_Query $query): void
    {
        if (
            !is_admin() || 
            !$query->is_main_query() || 
            $query->get('post_type') !== CPT::POST_TYPE
        ) {
            return;
        }

        // If 'post_status' is empty (the "All" view), explicitly ask for our statuses
        // Otherwise WP defaults to 'publish' (which we don't use) or 'draft'.
        if (empty($_GET['post_status']) && empty($query->get('post_status'))) {
            $query->set('post_status', ['vp_pending', 'vp_approved', 'vp_rejected']);
        }
    }

    /**
     * 5. Register Status Views (Pending | Approved | Rejected links at top)
     * @param array<string, string> $views
     * @return array<string, string>
     */
    public function registerStatusViews(array $views): array
    {
        // We rebuild these to control the order and counts
        $base = admin_url('edit.php?post_type=' . CPT::POST_TYPE);
        
        $statuses = [
            'all'         => ['label' => __('All', 'yardlii-core'),      'status' => ''],
            'vp_pending'  => ['label' => __('Pending', 'yardlii-core'),  'status' => 'vp_pending'],
            'vp_approved' => ['label' => __('Approved', 'yardlii-core'), 'status' => 'vp_approved'],
            'vp_rejected' => ['label' => __('Rejected', 'yardlii-core'), 'status' => 'vp_rejected'],
            'trash'       => ['label' => __('Trash', 'yardlii-core'),    'status' => 'trash'],
        ];

        $new_views = [];
        $current = $_GET['post_status'] ?? '';

        foreach ($statuses as $key => $data) {
            // Count items
            $args = ['post_type' => CPT::POST_TYPE, 'post_status' => $data['status']];
            if ($key === 'all') $args['post_status'] = ['vp_pending', 'vp_approved', 'vp_rejected'];
            
            $q = new WP_Query($args);
            $count = $q->found_posts;
            
            if ($count > 0 || $key === 'all') {
                $class = ($current === $data['status']) ? 'current' : '';
                $url   = $key === 'all' ? $base : add_query_arg('post_status', $data['status'], $base);
                
                $new_views[$key] = sprintf(
                    '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                    esc_url($url),
                    esc_attr($class),
                    esc_html($data['label']),
                    $count
                );
            }
        }

        return $new_views;
    }

    /**
     * 6. Register Bulk Actions
     * @param array<string, string> $actions
     * @return array<string, string>
     */
    public function registerBulkActions(array $actions): array
    {
        unset($actions['edit']); // Remove bulk edit
        return array_merge([
            'yardlii_tv_bulk_approve' => __('Approve', 'yardlii-core'),
            'yardlii_tv_bulk_reject'  => __('Reject', 'yardlii-core'),
            'yardlii_tv_bulk_reopen'  => __('Re-open', 'yardlii-core'),
            'yardlii_tv_bulk_resend'  => __('Resend Email', 'yardlii-core'),
        ], $actions);
    }

    /**
     * 7. Handle Bulk Action Logic
     * @param string $redirect_to
     * @param string $action
     * @param array<int> $post_ids
     * @return string
     */
    public function handleBulkProcessing(string $redirect_to, string $action, array $post_ids): string
    {
        $map = [
            'yardlii_tv_bulk_approve' => 'approve',
            'yardlii_tv_bulk_reject'  => 'reject',
            'yardlii_tv_bulk_reopen'  => 'reopen',
            'yardlii_tv_bulk_resend'  => 'resend',
        ];

        if (!isset($map[$action])) {
            return $redirect_to;
        }

        // Lazy-load Decisions controller
        $decisions = new Decisions();
        $processed = 0;
        
        // Check for "Send Copy" checkbox (passed via GET or POST)
        $sendCopy = !empty($_REQUEST['tv_send_copy']);

        foreach ($post_ids as $id) {
            if ($decisions->applyDecision((int)$id, $map[$action], ['cc_self' => $sendCopy])) {
                $processed++;
            }
        }

        return add_query_arg([
            'tv_notice' => 'bulk_' . $map[$action], 
            'tv_count' => $processed
        ], $redirect_to);
    }

    /**
     * 8. Render "Send Copy" Checkbox in Toolbar
     * @param string $post_type
     */
    public function renderToolbarExtras(string $post_type): void
    {
        if ($post_type !== CPT::POST_TYPE) return;
        
        ?>
        <label style="margin-left:10px; line-height:30px; vertical-align:middle;">
            <input type="checkbox" name="tv_send_copy" value="1"> 
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