<?php
namespace Perrystown\App\Contact;

if ( ! defined('ABSPATH') ) exit;

class Contact_Controller {

    // List with pagination + multi-word search
    public static function index(\WP_REST_Request $request) {
        global $wpdb;
        $table = Contact_Table::table_name();

        $page     = max(1, intval($request->get_param('page') ?? 1));
        $per_page = max(1, min(100, intval($request->get_param('per_page') ?? 10)));
        $offset   = ($page - 1) * $per_page;
        $search   = trim((string)($request->get_param('search') ?? ''));

        $where_sql  = '1=1';
        $where_args = [];

        if ($search !== '') {
            // AND across tokens, OR across fields
            $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            $parts  = [];
            foreach ($tokens as $tok) {
                $like    = '%' . $wpdb->esc_like($tok) . '%';
                $parts[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s OR message LIKE %s)';
                array_push($where_args, $like, $like, $like, $like);
            }
            if (!empty($parts)) {
                $where_sql .= ' AND ' . implode(' AND ', $parts);
            }
        }

        // Total
        $total_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total     = (int) ($where_args ? $wpdb->get_var($wpdb->prepare($total_sql, $where_args)) : $wpdb->get_var($total_sql));

        // Rows
        $rows_sql  = "SELECT id, name, email, phone, message, created_at
                      FROM {$table}
                      WHERE {$where_sql}
                      ORDER BY created_at DESC, id DESC
                      LIMIT %d OFFSET %d";
        $rows_args = array_merge($where_args, [ $per_page, $offset ]);
        $rows      = $wpdb->get_results($wpdb->prepare($rows_sql, $rows_args), ARRAY_A);

        return new \WP_REST_Response([
            'success'       => true,
            'message'       => 'Contacts fetched successfully.',
            'data'          => $rows,
            'search'        => $search,
            'current_page'  => $page,
            'per_page'      => $per_page,
            'total_pages'   => (int) ceil($total / $per_page),
        ], 200);
    }

    // Create
    public static function store(\WP_REST_Request $request) {
        global $wpdb; $table = Contact_Table::table_name();

        // Already validated/sanitized by Validator.
        $name    = $request->get_param('name');
        $email   = $request->get_param('email');
        $phone   = $request->get_param('phone');
        $message = $request->get_param('message');

        $ok = $wpdb->insert($table, [
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone,
            'message'    => ($message === '' ? null : $message),
            'created_at' => current_time('mysql'),
        ], ['%s','%s','%s','%s','%s']);

        if ($ok === false) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to save contact.',
            ], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Contact created successfully.',
            'data'    => ['id' => (int) $wpdb->insert_id],
        ], 201);
    }

    // Show single 
    public static function show(\WP_REST_Request $request) {
        global $wpdb; $table = Contact_Table::table_name();

        $id  = intval($request->get_param('id'));
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name, email, phone, message, created_at FROM {$table} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if (!$row) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Contact not found.',
            ], 404);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Contact fetched successfully.',
            'data'    => $row,
        ], 200);
    }

    // Update 
    public static function update(\WP_REST_Request $request) {
        global $wpdb; $table = Contact_Table::table_name();
        $id = intval($request->get_param('id'));

        $data = []; $format = [];
        foreach (['name'=>'%s','email'=>'%s','phone'=>'%s','message'=>'%s'] as $field => $fmt) {
            $val = $request->get_param($field);
            if ($val !== null && $val !== '') {
                $data[$field] = $val;
                $format[] = $fmt;
            }
        }

        if (empty($data)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'No fields to update.',
            ], 400);
        }

        $updated = $wpdb->update($table, $data, ['id'=>$id], $format, ['%d']);

        if ($updated === false) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Update failed.',
            ], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Contact updated successfully.',
            'data'    => ['id' => $id],
        ], 200);
    }

    // Delete
    public static function destroy(\WP_REST_Request $request) {
        global $wpdb; $table = Contact_Table::table_name();
        $id = intval($request->get_param('id'));

        $deleted = $wpdb->delete($table, ['id'=>$id], ['%d']);

        if (!$deleted) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Delete failed or contact not found.',
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Contact deleted successfully.',
        ], 200);
    }
}
