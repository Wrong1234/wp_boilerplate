<?php
namespace Perrystown\App\Referral\Validation;

if (!defined('ABSPATH')) exit;

class Validator {

    public static function wrap($callback, string $schema_key) {
        return function(\WP_REST_Request $request) use ($callback, $schema_key) {
            $errors = self::check($schema_key, $request);
            if (!empty($errors)) {
                return new \WP_REST_Response([
                    'status'  => false,
                    'message' => 'Validation failed.',
                    'errors'  => $errors,
                ], 422);
            }
            return \call_user_func($callback, $request);
        };
    }

    protected static function schema(string $key): array {
        $s = [
            'index' => [
                'page'     => ['type'=>'int?','sanitize'=>'int'],
                'per_page' => ['type'=>'int?','sanitize'=>'int'],
                'search'   => ['type'=>'string?','sanitize'=>'text'],
            ],
            'store' => [
                // required
                'name'  => ['type'=>'string','required'=>true,'sanitize'=>'text','rule'=>'nonempty'],
                'email' => ['type'=>'string','required'=>true,'sanitize'=>'text','rule'=>'nonempty'],
                'phone' => ['type'=>'string','required'=>true,'sanitize'=>'text','rule'=>'nonempty'],

                // optional patient (accept mm/dd/yyyy or yyyy-mm-dd; store yyyy-mm-dd)
                'dob'   => ['type'=>'string?','sanitize'=>'date_mmdd_or_iso','rule'=>'valid_date'],

                // optional dentist
                'dentist_name'  => ['type'=>'string?','sanitize'=>'text'],
                'practice'      => ['type'=>'string?','sanitize'=>'text'],
                'dentist_phone' => ['type'=>'string?','sanitize'=>'text'],
                'dentist_email' => ['type'=>'string?','sanitize'=>'text'],

                // optional
                'notes'    => ['type'=>'string?','sanitize'=>'text'],
                'file'     => ['type'=>'string?','sanitize'=>'text'], // URL fallback if not a file upload
                'consent'  => ['type'=>'int?','sanitize'=>'int'],
            ],
            'show' => [
                'id' => ['type'=>'int','required'=>true,'from'=>'param','sanitize'=>'int','rule'=>'positive'],
            ],
            'update' => [
                'id'    => ['type'=>'int','required'=>true,'from'=>'param','sanitize'=>'int','rule'=>'positive'],
                'name'          => ['type'=>'string?','sanitize'=>'text'],
                'email'         => ['type'=>'string?','sanitize'=>'text'],
                'phone'         => ['type'=>'string?','sanitize'=>'text'],
                'dob'           => ['type'=>'string?','sanitize'=>'date_mmdd_or_iso','rule'=>'valid_date'],
                'dentist_name'  => ['type'=>'string?','sanitize'=>'text'],
                'practice'      => ['type'=>'string?','sanitize'=>'text'],
                'dentist_phone' => ['type'=>'string?','sanitize'=>'text'],
                'dentist_email' => ['type'=>'string?','sanitize'=>'text'],
                'notes'         => ['type'=>'string?','sanitize'=>'text'],
                'file'          => ['type'=>'string?','sanitize'=>'text'],
            

                '_atleast_one'  => ['type'=>'meta','rule'=>'at_least_one_of:name,email,phone,dob,dentist_name,practice,dentist_phone,dentist_email,notes,file,consent'],
            ],
            'destroy' => [
                'id' => ['type'=>'int','required'=>true,'from'=>'param','sanitize'=>'int','rule'=>'positive'],
            ],
        ];
        return $s[$key] ?? [];
    }

    protected static function check(string $schema_key, \WP_REST_Request $req): array {
        $schema = self::schema($schema_key);
        $errors = [];

        // Update must have at least one field (or a file upload)
        if (isset($schema['_atleast_one'])) {
            $present = false;
            foreach (['name','email','phone','dob','dentist_name','practice','dentist_phone','dentist_email','notes','file','consent'] as $f) {
                if ($req->has_param($f)) {
                    $v = $req->get_param($f);
                    if ($v !== null && $v !== '') { $present = true; break; }
                }
            }
            if (!$present) {
                $files = $req->get_file_params();
                if (!empty($files['file']) && !empty($files['file']['tmp_name'])) $present = true;
            }
            if (!$present) $errors['fields'][] = 'at_least_one_field_required';
            unset($schema['_atleast_one']);
        }

        foreach ($schema as $field => $conf) {
            if ($field[0] === '_') continue;

            $has = $req->has_param($field);
            $raw = $req->get_param($field);

            if (($conf['required'] ?? false) && ($raw === null || $raw === '')) {
                $errors[$field][] = 'required';
                continue;
            }

            if ($has) {
                $val  = self::sanitize($conf['sanitize'] ?? null, $raw);
                $rule = $conf['rule'] ?? null;

                // Extra rules
                if ($rule === 'nonempty' && is_string($val) && trim($val) === '') {
                    $errors[$field][] = 'cannot_be_empty';
                }
                if ($rule === 'positive' && (!is_numeric($val) || intval($val) <= 0)) {
                    $errors[$field][] = 'must_be_positive';
                }
                if ($rule === 'valid_date' && $val !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                    $errors[$field][] = 'invalid_date';
                }

                // Only set back when present
                $req->set_param($field, $val);
            }
        }

        return $errors;
    }

    protected static function sanitize($which, $value) {
        switch ($which) {
            case 'int':
                return ($value === null || $value === '') ? null : intval($value);

            case 'text':
                return ($value === null) ? null : sanitize_text_field($value);

            // Accept mm/dd/yyyy OR yyyy-mm-dd; return yyyy-mm-dd for DB DATE
            case 'date_mmdd_or_iso':
                if ($value === null || $value === '') return null;

                // mm/dd/yyyy
                $dt = \DateTime::createFromFormat('m/d/Y', $value);
                if ($dt && $dt->format('m/d/Y') === $value) return $dt->format('Y-m-d');

                // yyyy-mm-dd (already ISO)
                $dt = \DateTime::createFromFormat('Y-m-d', $value);
                if ($dt && $dt->format('Y-m-d') === $value) return $value;

                // anything else => leave as-is; rule will mark invalid
                return $value;

            default:
                return $value;
        }
    }
}
