<?php

namespace Perrystown\App\Service\Validation;

if (!defined('ABSPATH')) exit;

class Validator {
    public static function wrap($callback, string $schema_key) {
        return function(\WP_REST_Request $request) use ($callback, $schema_key) {
            $errors = self::check($schema_key, $request);
            if (!empty($errors)) {
                return new \WP_REST_Response([
                    'status' => false,
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
                'name'        => ['type'=>'string','required'=>true,'sanitize'=>'text','rule'=>'nonempty'],
                'title'       => ['type'=>'string?','sanitize'=>'text'],
                'rate'        => ['type'=>'string?','sanitize'=>'text'],
                'description' => ['type'=>'string?','sanitize'=>'html'],
                'image'       => ['type'=>'string?','sanitize'=>'text'],
                '_require_image_file_or_url' => ['type'=>'meta','rule'=>'image_required'],
            ],
            'show' => [
                'id' => ['type'=>'int','required'=>true,'from'=>'param','sanitize'=>'int','rule'=>'positive'],
            ],
            'update' => [
                'id'          => ['type'=>'int','required'=>true,'from'=>'param','sanitize'=>'int','rule'=>'positive'],
                'name'        => ['type'=>'string?','sanitize'=>'text'],
                'title'       => ['type'=>'string?','sanitize'=>'text'],
                'rate'        => ['type'=>'string?','sanitize'=>'text'],
                'description' => ['type'=>'string?','sanitize'=>'html'],
                'image'       => ['type'=>'string?','sanitize'=>'text'],
                '_atleast_one'=> ['type'=>'meta','rule'=>'at_least_one_of:name,title,rate,description,image'],
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

        // Meta rule: require image (file OR URL) for store
        if (isset($schema['_require_image_file_or_url'])) {
            $files = $req->get_file_params();
            $fileOk = !empty($files['image']) && !empty($files['image']['tmp_name']) && strpos(($files['image']['type'] ?? ''), 'image/') === 0;
            $url    = $req->get_param('image');
            if (!$fileOk && (empty($url) || !is_string($url) || trim($url) === '')) {
                $errors['image'][] = 'image_required_file_or_url';
            }
            unset($schema['_require_image_file_or_url']);
        }

        if (isset($schema['_atleast_one'])) {
            $fields  = explode(',', substr($schema['_atleast_one']['rule'], strlen('at_least_one_of:')));
            $present = false;
            foreach ($fields as $f) { 
                $v = $req->get_param($f); 
                if ($v !== null && $v !== '') { 
                    $present = true; 
                    break; 
                } 
            }
            if (!$present) { 
                $files = $req->get_file_params();
                if (!empty($files['image']) && !empty($files['image']['tmp_name'])) $present = true;
            }
            if (!$present) { 
                $errors['fields'][] = 'at_least_one_field_required'; 
            }
            unset($schema['_atleast_one']);
        }

        $files = $req->get_file_params();

        foreach ($schema as $field => $conf) {
            $from = $conf['from'] ?? 'body';

            if ($from === 'file') {
                $file = $files[$field] ?? null;
                if (($conf['required'] ?? false) && (empty($file) || !isset($file['tmp_name']))) {
                    $errors[$field][] = 'required_file'; continue;
                }
                if (!empty($file) && !empty($file['type']) && strpos($file['type'], 'image/') !== 0) {
                    $errors[$field][] = 'invalid_image_type';
                }
                continue;
            }

            $raw = $req->get_param($field);

            if (($conf['required'] ?? false) && ($raw === null || $raw === '')) {
                $errors[$field][] = 'required'; continue;
            }

            if ($raw !== null && $raw !== '') {
                $val = self::sanitize($conf['sanitize'] ?? null, $raw);

                $type = $conf['type'] ?? null;
                if ($type === 'int' && !is_numeric($val)) { 
                    $errors[$field][] = 'must_be_int'; 
                }
                if ($type === 'int?' && !is_null($val) && !is_numeric($val)) { 
                    $errors[$field][] = 'must_be_int_or_null'; 
                }
                if ($type === 'string' && !is_string($val)) { 
                    $errors[$field][] = 'must_be_string'; 
                }

                $rule = $conf['rule'] ?? null;
                if ($rule === 'nonempty' && is_string($val) && trim($val) === '') { 
                    $errors[$field][] = 'cannot_be_empty'; 
                }
                if ($rule === 'positive' && (!is_numeric($val) || intval($val) <= 0)) { 
                    $errors[$field][] = 'must_be_positive'; 
                }

                $req->set_param($field, $val);
            }
        }

        return $errors;
    }

    protected static function sanitize($which, $value) {
        switch ($which) {
            case 'int':  return ($value === null || $value === '') ? null : intval($value);
            case 'text': return is_string($value) ? sanitize_text_field($value) : '';
            case 'html': return is_string($value) ? wp_kses_post($value) : '';
            default:     return $value;
        }
    }
}