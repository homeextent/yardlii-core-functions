<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Requests;

use Yardlii\Core\Features\TrustVerification\Requests\CPT;
use WP_Post;
use WP_Query;
use WP_User_Query;

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
        
        // 3. Logic (Search & Filter)
        add_action('pre_get_posts', [$this, 'modifyMainQuery']);
        // Silence default search SQL so we can keep 's' in the query object (fixing the UI label)
        add_filter('posts_search', [$this, 'killDefaultSearchSQL'], 10, 2);
        
        // 4. Notifications
        add_action('admin_notices', [$this, 'displayAdminNotices']);
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
            'tv_user'        => __('User / Request', 'yardlii-core'),
            'tv_form'        => __('Form', 'yardlii-core'),
            'tv_status'      => __('Status', 'yardlii-core'),
            'tv_role'        => __('Current Role', 'yardlii-core'),
            'tv_processed'   => __('Processed By', 'yardlii-core'),
            'tv_proc_date'   => __('Processed Date', 'yardlii-core'),
            'date'           => __('Submitted', 'yardlii-core'),
        ];
    }

    /**
     * 2. Render Column Content
     */
    public function renderColumn(string $column, int $post_id): void
    {
        switch ($column) {
            case 'tv_user':
                $uid = (int) get_post_meta($post_id, '_vp_user_id', true);
                $user = get_userdata($uid);
                
                $edit_link = get_edit_post_link($post_id);
                $title = _draft_or_post_title($post_id);
                
                echo '<strong><a class="row-title" href="' . esc_url((string)$edit_link) . '">' . esc_html($title) . '</a></strong>';
                
                if ($user) {
                    printf('<br><a href="mailto:%1$s">%1$s</a>', esc_html($user->user_email));
                    if ($user->user_login !== $user->user_email) {
                         printf('<span style="color:#888"> (%s)</span>', esc_html($user->user_login));
                    }
                } else {
                    echo '<br><span style="color:#a00;">(Deleted User)</span>';
                }
                break;

            case 'tv_form':
                echo esc_html((string) get_post_meta($post_id, '_vp_form_id', true));
                break;

            case 'tv_status':
                $status = get_post_status($post_id);
                if (!$status) { echo '—'; break; }
                
                $label  = $this->getStatusLabel($status);
                $class  = str_replace(['vp_', '_'], ['', '-'], $status); 
                
                printf('<span class="status-badge status-badge--%s">%s</span>', esc_attr($class), esc_html($label));
                
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
                    $role_name = ucfirst((string)$slug); 
                    global $wp_roles;
                    if (isset($wp_roles->role_names[$slug])) {
                        $role_name = $wp_roles->role_names[$slug];
                    }
                    printf('%s<br><small style="color:#888;">%s</small>', esc_html($role_name), esc_html((string)$slug));
                } else {
                    echo '—';
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

            case 'tv_proc_date':
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
     * 3. Row Actions
     * @param array<string, string> $actions
     * @param WP_Post $post
     * @return array<string, string>
     */
    public function handleRowActions(array $actions, WP_Post $post): array
    {
        if ($post->post_type !== CPT::POST_TYPE) return $actions;

        unset($actions['edit'], $actions['inline hide-if-no-js'], $actions['trash']);

        $status = $post->post_status;
        $nonce  = wp_create_nonce('yardlii_tv_action_nonce');
        $base   = admin_url('admin.php');

        $new_actions = [];

        if ($status === 'vp_pending') {
            $approve_url = add_query_arg(['action' => 'yardlii_tv_approve', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['yl_approve'] = sprintf('<a href="%s" style="color:green;font-weight:bold;">%s</a>', esc_url($approve_url), __('Approve', 'yardlii-core'));

            $reject_url = add_query_arg(['action' => 'yardlii_tv_reject', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['yl_reject'] = sprintf('<a href="%s" style="color:#d63638;">%s</a>', esc_url($reject_url), __('Reject', 'yardlii-core'));
        } 
        else {
            $reopen_url = add_query_arg(['action' => 'yardlii_tv_reopen', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['yl_reopen'] = sprintf('<a href="%s">%s</a>', esc_url($reopen_url), __('Re-open', 'yardlii-core'));

            $resend_url = add_query_arg(['action' => 'yardlii_tv_resend', 'post' => $post->ID, '_wpnonce' => $nonce], $base);
            $new_actions['yl_resend'] = sprintf('<a href="%s">%s</a>', esc_url($resend_url), __('Resend Email', 'yardlii-core'));
        }

        $hist_nonce = wp_create_nonce('yardlii_tv_history');
        $new_actions['yl_history'] = sprintf(
            '<a href="#" data-action="tv-row-history" data-post="%d" data-nonce="%s">%s</a>',
            $post->ID,
            esc_attr($hist_nonce),
            __('History', 'yardlii-core')
        );

        return $new_actions + $actions;
    }

    /**
     * 4. Notifications (Only Success/Action feedback)
     */
    public function displayAdminNotices(): void
    {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== CPT::POST_TYPE) return;

        if (isset($_GET['tv_notice'])) {
            $map = [
                'approve'      => __('Request approved.', 'yardlii-core'),
                'reject'       => __('Request rejected.', 'yardlii-core'),
                'reopen'       => __('Request reopened.', 'yardlii-core'),
                'resend'       => __('Email resent.', 'yardlii-core'),
                'bulk_approve' => __('Selected requests approved.', 'yardlii-core'),
                'bulk_reject'  => __('Selected requests rejected.', 'yardlii-core'),
            ];
            $notice = sanitize_key($_GET['tv_notice']);
            
            if (isset($map[$notice])) {
                printf(
                    '<div class="yardlii-banner yardlii-banner--success yardlii-banner--dismiss" style="margin: 15px 0 15px 0; display:block;">' .
                    '<p><strong>%s</strong> %s</p>' .
                    '</div>',
                    esc_html__('Success:', 'yardlii-core'),
                    esc_html($map[$notice])
                );
            }
        }
    }

    /**
     * 5. Fix "All" View & Enable Robust Search
     * @param WP_Query $query
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

        $our_statuses = ['vp_pending', 'vp_approved', 'vp_rejected'];

        // A. Fix "All" View
        if (empty($_GET['post_status']) && empty($query->get('post_status'))) {
            $query->set('post_status', $our_statuses);
        }

        // B. Employer Vouch Filter
        if (isset($_GET['verification_type']) && $_GET['verification_type'] === 'employer_vouch') {
             $meta_query = $query->get('meta_query') ?: [];
             $meta_query[] = ['key' => '_vp_verification_type', 'value' => 'employer_vouch', 'compare' => '='];
             $query->set('meta_query', $meta_query);
        }

        // C. Robust Search (Title OR User Meta)
        $search_term = $query->get('s');
        if (!empty($search_term)) {
            // 1. Standard Title Search
            $title_search_args = [
                'post_type'   => CPT::POST_TYPE,
                'post_status' => $our_statuses,
                's'           => $search_term,
                'fields'      => 'ids',
                'posts_per_page' => -1
            ];
            $title_ids = get_posts($title_search_args);

            // 2. User Meta Search
            $user_query = new WP_User_Query([
                'search'         => '*' . $search_term . '*',
                'search_columns' => ['user_login', 'user_email', 'display_name'],
                'fields'         => 'ID',
                'number'         => 100
            ]);
            
            $user_ids = $user_query->get_results();
            $user_post_ids = [];

            if (!empty($user_ids)) {
                $user_post_ids = get_posts([
                    'post_type'      => CPT::POST_TYPE,
                    'post_status'    => $our_statuses,
                    'fields'         => 'ids',
                    'posts_per_page' => -1,
                    'meta_query'     => [
                        [
                            'key'     => '_vp_user_id',
                            'value'   => $user_ids,
                            'compare' => 'IN'
                        ]
                    ]
                ]);
            }

            // 3. Merge
            $merged_ids = array_unique(array_merge($title_ids, $user_post_ids));

            if (!empty($merged_ids)) {
                $query->set('post__in', $merged_ids);
                // [FIX] We DO NOT clear 's' here. We want native WP UI to see it.
                // Instead, we use killDefaultSearchSQL to prevent the SQL query from failing.
            } else {
                $query->set('post__in', [0]);
            }
        }
    }

    /**
     * 6. Silence Default Search SQL
     * We have manually found the IDs in modifyMainQuery() and set post__in.
     * Now we must tell WP *not* to run its default "AND post_title LIKE %s" logic.
     */
    public function killDefaultSearchSQL(string $search, WP_Query $query): string
    {
        if (
            !is_admin() || 
            !$query->is_main_query() || 
            $query->get('post_type') !== CPT::POST_TYPE || 
            !$query->is_search()
        ) {
            return $search;
        }

        // We have handled the search logic via post__in.
        // Return empty string to disable the default SQL LIKE clause.
        return '';
    }

    /**
     * 7. Register Status Views
     * @param array<string, string> $views
     * @return array<string, string>
     */
    public function registerStatusViews(array $views): array
    {
        $base = admin_url('edit.php?post_type=' . CPT::POST_TYPE);
        
        $emp_count = (new WP_Query([
            'post_type' => CPT::POST_TYPE, 'post_status' => 'any',
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
                esc_url($url),
                $class,
                esc_html($data['label']),
                $count
            );
        }

        $emp_class = $is_emp ? 'current' : '';
        $new_views['employer'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url(add_query_arg('verification_type', 'employer_vouch', $base)),
            $emp_class,
            __('Employer Vouch', 'yardlii-core'),
            $emp_count
        );

        return $new_views;
    }

    /**
     * 8. Register Bulk Actions
     * @param array<string, string> $actions
     * @return array<string, string>
     */
    public function registerBulkActions(array $actions): array
    {
        unset($actions['edit'], $actions['trash']); 
        return [
            'yardlii_tv_bulk_approve' => __('Approve', 'yardlii-core'),
            'yardlii_tv_bulk_reject'  => __('Reject', 'yardlii-core'),
            'yardlii_tv_bulk_reopen'  => __('Re-open', 'yardlii-core'),
            'yardlii_tv_bulk_resend'  => __('Resend Email', 'yardlii-core'),
        ] + $actions;
    }

    /**
     * 9. Handle Bulk Action Logic
     * @param string $redirect_to
     * @param string $action
     * @param array<int|string> $post_ids
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