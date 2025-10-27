<?php
// =====

// 2. SERVICE CONTROLLER (service_controller.php)
// ============================================

namespace Perrystown\App\Service;

if (!defined('ABSPATH')) exit;

class Service_Controller {

    // GET /services 
    public static function index(\WP_REST_Request $request) {
        global $wpdb; $table = Service_Table::table_name();

        $page     = max(1, intval($request->get_param('page') ?? 1));
        $per_page = max(1, min(100, intval($request->get_param('per_page') ?? 10)));
        $offset   = ($page - 1) * $per_page;
        $search   = trim((string)($request->get_param('search') ?? ''));

        $where_sql = '1=1'; $args = [];
        if ($search !== '') {
            $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            $parts = [];
            foreach ($tokens as $tok) {
                $like = '%' . $wpdb->esc_like($tok) . '%';
                $parts[] = '(name LIKE %s OR title LIKE %s OR rate LIKE %s OR description LIKE %s)';
                array_push($args, $like, $like, $like, $like);
            }
            $where_sql .= ' AND ' . implode(' AND ', $parts);
        }

        $total_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total = (int) ($args ? $wpdb->get_var($wpdb->prepare($total_sql, $args)) : $wpdb->get_var($total_sql));

        $rows_sql = "SELECT id, name, title, rate, description, image, created_at
                     FROM {$table}
                     WHERE {$where_sql}
                     ORDER BY created_at DESC, id DESC
                     LIMIT %d OFFSET %d";
        $rows_args = array_merge($args, [ $per_page, $offset ]);
        $rows = $wpdb->get_results($wpdb->prepare($rows_sql, $rows_args), ARRAY_A);

        return new \WP_REST_Response([
            'status'        => true,
            'message'       => 'Services fetched successfully.',
            'data'          => $rows,
            'search'        => $search,
            'current_page'  => $page,
            'per_page'      => $per_page,
            'total_pages'   => (int) ceil($total / $per_page),
        ], 200);
    }

    // POST /services — create
    public static function store(\WP_REST_Request $request) {
        global $wpdb; $table = Service_Table::table_name();

        $name        = $request->get_param('name');
        $title       = $request->get_param('title');
        $rate        = $request->get_param('rate');
        $description = $request->get_param('description');

        $files = $request->get_file_params();
        $imageUrl = null;

        if (!empty($files['image']) && !empty($files['image']['tmp_name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $movefile = wp_handle_upload($files['image'], ['test_form' => false]);
            if (!empty($movefile['error'])) {
                return new \WP_REST_Response(['status'=>false,'message'=>'Upload failed: ' . $movefile['error']], 400);
            }

            $filetype = wp_check_filetype(basename($movefile['file']), null);
            $attachment = [
                'guid'           => $movefile['url'],
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name(basename($movefile['file'])),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];
            $attach_id = wp_insert_attachment($attachment, $movefile['file']);
            if (is_wp_error($attach_id)) {
                return new \WP_REST_Response(['status'=>false,'message'=>'Failed to create media attachment.'], 500);
            }
            $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            $imageUrl = esc_url_raw($movefile['url']);
        } else {
            $imageUrl = $request->get_param('image');
        }

        $ok = $wpdb->insert($table, [
            'name'        => $name,
            'title'       => ($title === '' ? null : $title),
            'rate'        => ($rate === '' ? null : $rate),
            'description' => ($description === '' ? null : $description),
            'image'       => $imageUrl,
            'created_at'  => current_time('mysql'),
        ], ['%s','%s','%s','%s','%s','%s']);

        if ($ok === false) {
            return new \WP_REST_Response(['status'=>false,'message'=>'Failed to save service.'], 500);
        }

        return new \WP_REST_Response([
            'status' => true,
            'message' => 'Service created successfully.',
            'data'    => ['id' => (int) $wpdb->insert_id],
        ], 201);
    }

    // GET /services/{id}
    public static function show(\WP_REST_Request $request) {
        global $wpdb; $table = Service_Table::table_name();
        $id = intval($request->get_param('id'));

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT id, name, title, rate, description, image, created_at FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) return new \WP_REST_Response(['status'=>false,'message'=>'Service not found.'], 404);

        return new \WP_REST_Response([
            'status'=>true,'message'=>'Service fetched successfully.','data'=>$row
        ], 200);
    }

    // PUT/PATCH/POST /services/{id} — update
    public static function update(\WP_REST_Request $request) {
        global $wpdb; 
        $table = Service_Table::table_name();
        $id = intval($request->get_param('id'));

        $data = []; 
        $format = [];

        // Accept normal text fields from request
        foreach (['name'=>'%s','title'=>'%s','rate'=>'%s','description'=>'%s','image'=>'%s'] as $field => $fmt) {
            $val = $request->get_param($field);
            if ($val !== null && $val !== '') {
                $data[$field] = $val;
                $format[] = $fmt;
            }
        }

        // If POST multipart includes a new image file, upload & override image URL
        if (in_array($request->get_method(), ['POST','PUT','PATCH'], true)) {
            $files = $request->get_file_params();
            if (!empty($files['image']) && !empty($files['image']['tmp_name'])) {
                if (empty($files['image']['type']) || strpos($files['image']['type'], 'image/') !== 0) {
                    return new \WP_REST_Response(['status'=>false,'message'=>'Invalid image type.'], 422);
                }

                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $movefile = wp_handle_upload($files['image'], ['test_form' => false]);
                if (!empty($movefile['error'])) {
                    return new \WP_REST_Response(['status'=>false,'message'=>'Upload failed: ' . $movefile['error']], 400);
                }

                $filetype = wp_check_filetype(basename($movefile['file']), null);
                $attachment = [
                    'guid'           => $movefile['url'],
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => sanitize_file_name(basename($movefile['file'])),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ];
                $attach_id = wp_insert_attachment($attachment, $movefile['file']);
                if (is_wp_error($attach_id)) {
                    return new \WP_REST_Response(['status'=>false,'message'=>'Failed to create media attachment.'], 500);
                }
                $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);

                $data['image'] = esc_url_raw($movefile['url']);
                $format[] = '%s';
            }
        }

        if (empty($data)) {
            return new \WP_REST_Response(['status'=>false,'message'=>'No fields to update.'], 400);
        }

        $updated = $wpdb->update($table, $data, ['id'=>$id], $format, ['%d']);
        if ($updated === false) {
            return new \WP_REST_Response(['status'=>false,'message'=>'Update failed.'], 500);
        }

        return new \WP_REST_Response([
            'status' => true,
            'message' => 'Service updated successfully.',
            'data'    => ['id' => $id]
        ], 200);
    }

    // DELETE /services/{id}
    public static function destroy(\WP_REST_Request $request) {
        global $wpdb; $table = Service_Table::table_name();
        $id = intval($request->get_param('id'));

        $deleted = $wpdb->delete($table, ['id'=>$id], ['%d']);
        if (!$deleted) {
            return new \WP_REST_Response(['status'=>false,'message'=>'Delete failed or service not found.'], 400);
        }

        return new \WP_REST_Response(['status'=>true,'message'=>'Service deleted successfully.'], 200);
    }
}

