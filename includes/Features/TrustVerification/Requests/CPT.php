<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Requests;

final class CPT
{
    public const POST_TYPE = 'verification_request';

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType'], 0);   // priority 0 (earliest)
    add_action('init', [$this, 'registerStatuses'], 0);   // priority 0 (earliest)
    }

    public function registerPostType(): void
    {
        register_post_type(self::POST_TYPE, [
            'label'               => __('Verifications', 'yardlii-core'),
            'labels'              => [
                'name'               => __('Verifications', 'yardlii-core'),
                'singular_name'      => __('Verification Request', 'yardlii-core'),
                'menu_name'          => __('Verifications', 'yardlii-core'),
                'all_items'          => __('All Requests', 'yardlii-core'),
                'not_found'          => __('No requests found.', 'yardlii-core'),
                'not_found_in_trash' => __('No requests found in Trash.', 'yardlii-core'),
            ],
            'public'              => false,  // Keep false (not viewable on frontend)
            'show_ui'             => true,   // [CHANGED] Show in Admin UI
            'show_in_menu'        => true,   // [CHANGED] Show in Admin Menu
            'menu_position'       => 58,     // Below "Users"
            'menu_icon'           => 'dashicons-id-alt', // ID Card Icon
            'supports'            => ['title'],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            // Disable "Add New" button (requests are created programmatically)
            'capabilities' => [
                'create_posts' => 'do_not_allow', 
            ],
        ]);
    }

    public function registerStatuses(): void
    {
        foreach ([
            'vp_pending'  => __('Pending', 'yardlii-core'),
            'vp_approved' => __('Approved', 'yardlii-core'),
            'vp_rejected' => __('Rejected', 'yardlii-core'),
        ] as $status => $label) {
            register_post_status($status, [
                'label' => $label,
                'public' => false,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop("$label <span class='count'>(%s)</span>", "$label <span class='count'>(%s)</span>", 'yardlii-core'),
            ]);
        }
    }
}
