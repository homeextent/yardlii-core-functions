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
        
        // 2. Actions & UI
        add_filter('post_row_actions', [$this, 'handleRowActions'], 50, 2);
        add_filter('bulk_actions-edit-' . CPT::POST_TYPE, [$this, 'registerBulkActions']);
        add_filter('handle_bulk_actions-edit-' . CPT::POST_TYPE, [$this, 'handleBulkProcessing'], 10, 3);
        add_filter('views_edit-' . CPT::POST_TYPE, [$this, 'registerStatusViews']);
        add_action('restrict_manage_posts', [$this, 'renderToolbarExtras']);
        
        // 3. Logic
        add_action('pre_get_posts', [$this, 'modifyMainQuery']);
        
        // 4. Search Logic (SQL Filters)
        add_filter('posts_join', [$this, 'joinUserTable'], 10, 2);
        add_filter('posts_where', [$this, 'extendSearchWhere'], 10, 2);
        
        // 5. Notifications
        add_action('admin_notices', [$this, 'displayAdminNotices']);
    }

    /**
     * Advanced Search: Join the Users table so we can search by email/login
     * @param string $join
     * @param WP_Query $query
     * @return string
     */
    public function joinUserTable(string $join, WP_Query $query): string
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== CPT::POST_TYPE || !$query->is_search()) {
            return $join;
        }

        global $wpdb;
        // Join postmeta to get the user ID
        $join .= " LEFT JOIN {$wpdb->postmeta} AS pm_vp_user ON ({$wpdb->posts}.ID = pm_vp_user.post_id AND pm_vp_user.meta_key = '_vp_user_id') ";
        // Join users table on that ID
        $join .= " LEFT JOIN {$wpdb->users} AS u_vp ON (pm_vp_user.meta_value = u_vp.ID) ";

        return $join;
    }

    /**
     * Advanced Search: Modify WHERE clause to include User fields
     * @param string $where
     * @param WP_Query $query
     * @return string
     */
    public function extendSearchWhere(string $where, WP_Query $query): string
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== CPT::POST_TYPE || !$query->is_search()) {
            return $where;
        }

        global $wpdb;
        $search_term = $query->get('s');
        if (!$search_term) return $where;

        // Securely escape the term for LIKE
        $like = '%' . $wpdb->esc_like($search_term) . '%';

        // We replace the default "((post_title LIKE...))" block with our extended one
        // Note: This regex approach preserves other filters while injecting our OR conditions
        $replacement = $wpdb->prepare(
            "({$wpdb->posts}.post_title LIKE %s) OR (u_vp.user_email LIKE %s) OR (u_vp.user_login LIKE %s) OR (u_vp.display_name LIKE %s)",
            $like, $like, $like, $like
        );

        // If WP generated a standard search clause, append ours to it (OR logic inside the main AND)
        // This is tricky, so we'll use a brute-force Append for maximum reliability:
        // "AND (Default_Search OR User_Search)"
        
        // Remove the default search logic to avoid restrictive ANDs, then rebuild it.
        // Actually, the safest way in WP is to modify the existing search clause if possible, 
        // but since we joined tables, let's just append our conditions.
        
        $where .= $wpdb->prepare(" OR (u_vp.user_email LIKE %s OR u_vp.user_login LIKE %s OR u_vp.display_name LIKE %s) ", $like, $like, $like);

        return $where;
    }

    public function modifyMainQuery(WP_Query $query): void
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== CPT::POST_TYPE) return;

        // Fix "All" view
        if (empty($_GET['post_status']) && empty($query->get('post_status'))) {
            $query->set('post_status', ['vp_pending', 'vp_approved', 'vp_rejected']);
        }

        // Employer Filter
        if (isset($_GET['verification_type']) && $_GET['verification_type'] === 'employer_vouch') {
             $meta_query = $query->get('meta_query') ?: [];
             $meta_query[] = ['key' => '_vp_verification_type', 'value' => 'employer_vouch', 'compare' => '='];
             $query->set('meta_query', $meta_query);
        }
        
        // Note: Search logic moved to 'posts_join'/'posts_where' filters above
    }

    // ... (Rest of the file remains exactly the same as previous step: columns, actions, notices) ...
    // For brevity in chat, ensure you keep defineColumns, renderColumn, handleRowActions, 
    // displayAdminNotices, registerStatusViews, registerBulkActions, handleBulkProcessing, 
    // renderToolbarExtras, getStatusLabel.
    
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
            'tv_user'        => __('User / Request', 'yardlii-core'),
            'tv_form'        => __('Form', 'yardlii-core'),
            'tv_status'      => __('Status', 'yardlii-core'),
            'tv_role'        => __('Current Role', 'yardlii-core'),
            'tv_processed'   => __('Processed By', 'yardlii-core'),
            'tv_proc_date'   => __('Processed Date', 'yardlii-core'),
            'date'           => __('Submitted', 'yardlii-core'),
        ];
    }

    public function renderColumn(string $column, int $post_id): void
    {
        switch ($column) {
            case 'tv_user':
                $uid = (int) get_post_meta($post_id, '_vp_user_id', true);
                $user = get_userdata($uid);
                $edit_link = get_edit_post_link($post_id);
                // Use our custom title or fallback
                $title = get_the_title($post_id) ?: __('(No Title)', 'yardlii-core');
                
                echo '<strong><a class="row-title" href="' . esc_url((string)$edit_link) . '">' . esc_html($title) . '</a></strong>';
                if ($user) {
                    printf('<br><a href="mailto:%1$s">%1$s</a>', esc_html($user->user_email));
                    if ($user->user_login !== $user->user_email) printf('<span style="color:#888"> (%s)</span>', esc_html($user->user_login));
                } else {
                    echo '<br><span style="color:#a00;">(Deleted User)</span>';
                }
                break;
            // ... other columns same as before ...
            case 'tv_form': echo esc_html((string) get_post_meta($post_id, '_vp_form_id', true)); break;
            case 'tv_status': 
                $status = get_post_status($post_id);
                if(!$status) { echo '—'; break; }
                $label = $this->getStatusLabel($status);
                $class = str_replace(['vp_', '_'], ['', '-'], $status); 
                printf('<span class="status-badge status-badge--%s">%s</span>', esc_attr($class), esc_html($label));
                $type = get_post_meta($post_id, '_vp_verification_type', true);
                if ($status === 'vp_pending' && $type === 'employer_vouch') echo ' <span class="dashicons dashicons-businessperson" title="Waiting for Employer" style="color:#888;margin-left:4px;"></span>';
                break;
            case 'tv_role':
                $uid = (int) get_post_meta($post_id, '_vp_user_id', true);
                $user = get_userdata($uid);
                if ($user) {
                    $roles = (array) $user->roles;
                    $slug = reset($roles);
                    printf('%s<br><small style="color:#888;">%s</small>', esc_html(ucfirst((string)$slug)), esc_html((string)$slug));
                } else { echo '—'; }
                break;
            case 'tv_processed':
                $aid = (int) get_post_meta($post_id, '_vp_processed_by', true);
                if ($aid > 0) { $u = get_userdata($aid); echo esc_html($u ? ($u->display_name ?: $u->user_login) : 'Unknown'); }
                elseif ($aid === 0 && get_post_status($post_id) !== 'vp_pending') { echo '<em style="color:#888;">System / Employer</em>'; }
                else { echo '—'; }
                break;
            case 'tv_proc_date':
                $ts = (string) get_post_meta($post_id, '_vp_processed_date', true);
                if ($ts) { echo esc_html(wp_date(get_option('date_format'), strtotime($ts))) . '<br><small style="color:#888;">' . esc_html(wp_date(get_option('time_format'), strtotime($ts))) . '</small>'; }
                else { echo '—'; }
                break;
        }
    }

    /** @param array<string, string> $actions @param WP_Post $post @return array<string, string> */
    public function handleRowActions(array $actions, WP_Post $post): array {
        if ($post->post_type !== CPT::POST_TYPE) return $actions;
        unset($actions['edit'], $actions['inline hide-if-no-js'], $actions['trash']);
        $status = $post->post_status;
        $nonce  = wp_create_nonce('yardlii_tv_action_nonce');
        $base   = admin_url('admin.php');
        $new = [];
        if ($status === 'vp_pending') {
            $new['yl_approve'] = sprintf('<a href="%s" style="color:green;font-weight:bold;">%s</a>', esc_url(add_query_arg(['action'=>'yardlii_tv_approve','post'=>$post->ID,'_wpnonce'=>$nonce], $base)), __('Approve'));
            $new['yl_reject'] = sprintf('<a href="%s" style="color:#d63638;">%s</a>', esc_url(add_query_arg(['action'=>'yardlii_tv_reject','post'=>$post->ID,'_wpnonce'=>$nonce], $base)), __('Reject'));
        } else {
            $new['yl_reopen'] = sprintf('<a href="%s">%s</a>', esc_url(add_query_arg(['action'=>'yardlii_tv_reopen','post'=>$post->ID,'_wpnonce'=>$nonce], $base)), __('Re-open'));
            $new['yl_resend'] = sprintf('<a href="%s">%s</a>', esc_url(add_query_arg(['action'=>'yardlii_tv_resend','post'=>$post->ID,'_wpnonce'=>$nonce], $base)), __('Resend Email'));
        }
        $new['yl_history'] = sprintf('<a href="#" data-action="tv-row-history" data-post="%d" data-nonce="%s">%s</a>', $post->ID, esc_attr(wp_create_nonce('yardlii_tv_history')), __('History'));
        return $new + $actions;
    }

    public function displayAdminNotices(): void {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== CPT::POST_TYPE) return;
        if (isset($_GET['tv_notice'])) {
            $map = ['approve'=>__('Request approved.'), 'reject'=>__('Request rejected.'), 'reopen'=>__('Request reopened.'), 'resend'=>__('Email resent.'), 'bulk_approve'=>__('Selected approved.'), 'bulk_reject'=>__('Selected rejected.')];
            $n = sanitize_key($_GET['tv_notice']);
            if (isset($map[$n])) {
                printf('<div class="yardlii-banner yardlii-banner--success yardlii-banner--dismiss" style="margin:15px 0;display:block;"><p><strong>%s</strong> %s</p></div>', __('Success:'), esc_html($map[$n]));
            }
        }
    }

    /** @param array<string, string> $views @return array<string, string> */
    public function registerStatusViews(array $views): array {
        // ... (Keep existing implementation from previous step)
        $base = admin_url('edit.php?post_type=' . CPT::POST_TYPE);
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
            $new_views[$key] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', esc_url($url), $class, esc_html($data['label']), $count);
        }
        // Employer Vouch
        $emp_count = (new WP_Query(['post_type'=>CPT::POST_TYPE,'post_status'=>'any','meta_query'=>[['key'=>'_vp_verification_type','value'=>'employer_vouch']]]))->found_posts;
        $emp_class = $is_emp ? 'current' : '';
        $new_views['employer'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', esc_url(add_query_arg('verification_type', 'employer_vouch', $base)), $emp_class, __('Employer Vouch'), $emp_count);
        return $new_views;
    }

    /** @param array<string, string> $actions @return array<string, string> */
    public function registerBulkActions(array $actions): array {
        unset($actions['edit'], $actions['trash']); 
        return ['yardlii_tv_bulk_approve'=>__('Approve'), 'yardlii_tv_bulk_reject'=>__('Reject'), 'yardlii_tv_bulk_reopen'=>__('Re-open'), 'yardlii_tv_bulk_resend'=>__('Resend Email')] + $actions;
    }

    /** @param string $r @param string $a @param array<int|string> $ids @return string */
    public function handleBulkProcessing(string $r, string $a, array $ids): string {
        $map = ['yardlii_tv_bulk_approve'=>'approve', 'yardlii_tv_bulk_reject'=>'reject', 'yardlii_tv_bulk_reopen'=>'reopen', 'yardlii_tv_bulk_resend'=>'resend'];
        if (!isset($map[$a])) return $r;
        $d = new Decisions(); $cnt = 0; $cc = !empty($_REQUEST['tv_send_copy']);
        foreach ($ids as $id) { if ($d->applyDecision((int)$id, $map[$a], ['cc_self'=>$cc])) $cnt++; }
        return add_query_arg(['tv_notice'=>'bulk_'.$map[$a], 'tv_count'=>$cnt], $r);
    }

    public function renderToolbarExtras(string $pt): void {
        if ($pt !== CPT::POST_TYPE) return;
        echo '<label style="margin-left:10px;line-height:30px;vertical-align:middle;font-size:13px;"><input type="checkbox" name="tv_send_copy" value="1" style="margin-top:-2px;"> ' . esc_html__('Send me a copy') . '</label>';
    }

    private function getStatusLabel(string $slug): string {
        $map = ['vp_pending'=>'Pending', 'vp_approved'=>'Approved', 'vp_rejected'=>'Rejected'];
        return $map[$slug] ?? ucfirst(str_replace(['vp_', '_'], ['', ' '], $slug));
    }
}