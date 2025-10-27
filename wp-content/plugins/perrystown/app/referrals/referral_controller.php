<?php
namespace Perrystown\App\Referral;

if (!defined('ABSPATH')) exit;

class Referral_Controller {

    public static function index(\WP_REST_Request $request) {
        global $wpdb; $table = Referral_Table::table_name();

        $page     = max(1, intval($request->get_param('page') ?? 1));
        $per_page = max(1, min(100, intval($request->get_param('per_page') ?? 10)));
        $offset   = ($page - 1) * $per_page;
        $search   = trim((string)($request->get_param('search') ?? ''));

        $where_sql = '1=1'; $args = [];
        if ($search !== '') {
            $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            $parts  = [];
            foreach ($tokens as $tok) {
                $like = '%' . $wpdb->esc_like($tok) . '%';
                $parts[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s OR dentist_name LIKE %s OR practice LIKE %s OR notes LIKE %s)';
                array_push($args, $like, $like, $like, $like, $like, $like);
            }
            $where_sql .= ' AND ' . implode(' AND ', $parts);
        }

        $total_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total     = (int) ($args ? $wpdb->get_var($wpdb->prepare($total_sql, $args)) : $wpdb->get_var($total_sql));

        $rows_sql = "SELECT id, name, email, phone, dob, dentist_name, practice, dentist_phone, dentist_email, notes, file_url, created_at
                     FROM {$table}
                     WHERE {$where_sql}
                     ORDER BY created_at DESC, id DESC
                     LIMIT %d OFFSET %d";
        $rows_args = array_merge($args, [ $per_page, $offset ]);
        $rows      = $wpdb->get_results($wpdb->prepare($rows_sql, $rows_args), ARRAY_A);

        return new \WP_REST_Response([
            'status'       => true,
            'message'      => 'Referrals fetched successfully.',
            'data'         => $rows,
            'search'       => $search,
            'current_page' => $page,
            'per_page'     => $per_page,
            'total_pages'  => (int) ceil($total / $per_page),
        ], 200);
    }

    // Public create
    public static function store(\WP_REST_Request $request) {
        global $wpdb; $table = Referral_Table::table_name();

        // required
        $name  = $request->get_param('name');
        $email = $request->get_param('email');
        $phone = $request->get_param('phone');

        $dob           = $request->get_param('dob');
        $dentist_name  = $request->get_param('dentist_name');
        $practice      = $request->get_param('practice');
        $dentist_phone = $request->get_param('dentist_phone');
        $dentist_email = $request->get_param('dentist_email');
        $notes         = $request->get_param('notes');
        // File upload OR URL
        $files = $request->get_file_params();
        $file_url = null;

        if (!empty($files['file']) && !empty($files['file']['tmp_name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $movefile = wp_handle_upload($files['file'], ['test_form' => false]);
            if (!empty($movefile['error'])) {
                return new \WP_REST_Response(['status'=>false,'message'=>'Upload failed: ' . $movefile['error']], 400);
            }

            $filetype   = wp_check_filetype(basename($movefile['file']), null);
            $attachment = [
                'guid'           => $movefile['url'],
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name(basename($movefile['file'])),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
            $attach_id = wp_insert_attachment($attachment, $movefile['file']);
            if (!is_wp_error($attach_id)) {
                $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);
            }
            $file_url = esc_url_raw($movefile['url']);
        } else {
            $file_url = (string) $request->get_param('file');
            if ($file_url !== '') $file_url = esc_url_raw($file_url);
            else $file_url = null;
        }

        $ok = $wpdb->insert($table, [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'dob'           => ($dob === '' ? null : $dob), // Y-m-d or null
            'dentist_name'  => ($dentist_name === '' ? null : $dentist_name),
            'practice'      => ($practice === '' ? null : $practice),
            'dentist_phone' => ($dentist_phone === '' ? null : $dentist_phone),
            'dentist_email' => ($dentist_email === '' ? null : $dentist_email),
            'notes'         => ($notes === '' ? null : $notes),
            'file_url'      => $file_url,
            'created_at'    => current_time('mysql'),
        ], ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s']);

        if ($ok === false) {
            return new \WP_REST_Response(['status'=>false,'message'=>'Failed to save referral.'], 500);
        }

        return new \WP_REST_Response([
            'status'  => true,
            'message' => 'Referral submitted successfully.',
            'data'    => ['id' => (int)$wpdb->insert_id],
        ], 201);
    }

    //show
    public static function show(\WP_REST_Request $request) {
        global $wpdb; $table = Referral_Table::table_name();
        $id = intval($request->get_param('id'));

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT id, name, email, phone, dob, dentist_name, practice, dentist_phone, dentist_email, notes, file_url, created_at FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) return new \WP_REST_Response(['status'=>false,'message'=>'Referral not found.'], 404);

        return new \WP_REST_Response([
            'status'=>true,'message'=>'Referral fetched successfully.','data'=>$row
        ], 200);
    }

    // Allow POST for multipart file replace
    public static function update(\WP_REST_Request $request) {
        global $wpdb; $table = Referral_Table::table_name();
        $id = intval($request->get_param('id'));

        $data = []; $format = [];
        $fields = [
            'name'=>'%s','email'=>'%s','phone'=>'%s',
            'dob'=>'%s','dentist_name'=>'%s','practice'=>'%s',
            'dentist_phone'=>'%s','dentist_email'=>'%s','notes'=>'%s',
            'file_url'=>'%s','consent'=>'%d'
        ];

        foreach ($fields as $f => $fmt) {
            $val = $request->get_param($f);
            if ($f === 'consent' && $val !== null && $val !== '') $val = intval($val) ? 1 : 0;
            if ($val !== null && $val !== '') { $data[$f] = $val; $format[] = $fmt; }
        }

        // file upload replacement
        $files = $request->get_file_params();
        if (!empty($files['file']) && !empty($files['file']['tmp_name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $movefile = wp_handle_upload($files['file'], ['test_form' => false]);
            if (!empty($movefile['error'])) return new \WP_REST_Response(['status'=>false,'message'=>'Upload failed: ' . $movefile['error']], 400);

            $filetype   = wp_check_filetype(basename($movefile['file']), null);
            $attachment = [
                'guid'           => $movefile['url'],
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name(basename($movefile['file'])),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
            $attach_id = wp_insert_attachment($attachment, $movefile['file']);
            if (!is_wp_error($attach_id)) {
                $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);
            }
            $data['file_url'] = esc_url_raw($movefile['url']);
            $format[] = '%s';
        }

        if (empty($data)) return new \WP_REST_Response(['status'=>false,'message'=>'No fields to update.'], 400);

        $updated = $wpdb->update($table, $data, ['id'=>$id], $format, ['%d']);
        if ($updated === false) return new \WP_REST_Response(['status'=>false,'message'=>'Update failed.'], 500);

        return new \WP_REST_Response([
            'status'=>true,'message'=>'Referral updated successfully.','data'=>['id'=>$id]
        ], 200);
    }

    public static function destroy(\WP_REST_Request $request) {
        global $wpdb; $table = Referral_Table::table_name();
        $id = intval($request->get_param('id'));

        $deleted = $wpdb->delete($table, ['id'=>$id], ['%d']);
        if (!$deleted) return new \WP_REST_Response(['status'=>false,'message'=>'Delete failed or referral not found.'], 400);

        return new \WP_REST_Response(['status'=>true,'message'=>'Referral deleted successfully.'], 200);
    }
}
