<?php
namespace Perrystown\App\Contact\Validation;

if ( ! defined('ABSPATH') ) exit;

class Validator {
    public static function wrap($callback, string $schema_key) {
        return function(\WP_REST_Request $request) use ($callback, $schema_key) {
            $errors = self::validate_and_sanitize($schema_key, $request);
            if (!empty($errors)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $errors,
                ], 422);
            }
            return \call_user_func($callback, $request);
        };
    }

    protected static function schema(string $key): array {
        $schemas = [
            'index' => [
                'page'     => ['type' => 'int?', 'sanitize' => 'int'],
                'per_page' => ['type' => 'int?', 'sanitize' => 'int'],
                'search'   => ['type' => 'string?', 'sanitize' => 'text'],
            ],
            'store' => [
                'name'    => ['type' => 'string', 'required' => true, 'sanitize' => 'text',   'rule' => 'nonempty'],
                'email'   => ['type' => 'string', 'required' => true, 'sanitize' => 'email',  'rule' => 'email'],
                'phone'   => ['type' => 'string', 'required' => true, 'sanitize' => 'text',   'rule' => 'nonempty'],
                'message' => ['type' => 'string?',                 'sanitize' => 'message'],
            ],
            'show'    => [
                'id' => ['type' => 'int', 'required' => true, 'from' => 'param', 'sanitize' => 'int', 'rule' => 'positive'],
            ],
            'destroy' => [
                'id' => ['type' => 'int', 'required' => true, 'from' => 'param', 'sanitize' => 'int', 'rule' => 'positive'],
            ],
            // NEW: update (id from param; at least one updatable field present)
            'update' => [
                'id'      => ['type' => 'int',    'required' => true, 'from' => 'param', 'sanitize' => 'int',   'rule' => 'positive'],
                'name'    => ['type' => 'string?','sanitize' => 'text'],
                'email'   => ['type' => 'string?','sanitize' => 'email'],
                'phone'   => ['type' => 'string?','sanitize' => 'text'],
                'message' => ['type' => 'string?','sanitize' => 'message'],
                '_atleast_one' => ['type' => 'meta', 'rule' => 'at_least_one_of:name,email,phone,message'],
            ],
        ];
        return $schemas[$key] ?? [];
    }

    protected static function validate_and_sanitize(string $schema_key, \WP_REST_Request $request): array {
        $schema = self::schema($schema_key);
        $errors = [];

        // Handle meta rule first for update
        if (isset($schema['_atleast_one'])) {
            $fields = explode(',', substr($schema['_atleast_one']['rule'], strlen('at_least_one_of:')));
            $present = false;
            foreach ($fields as $f) {
                $v = $request->get_param($f);
                if ($v !== null && $v !== '') { $present = true; break; }
            }
            if (!$present) {
                $errors['fields'][] = 'at_least_one_of_name_email_phone_message_required';
            }
            unset($schema['_atleast_one']);
        }

        foreach ($schema as $field => $conf) {
            $from = $conf['from'] ?? 'body';
            $raw  = ($from === 'param') ? $request->get_param($field)
                  : (($from === 'query') ? ($request->get_query_params()[$field] ?? null)
                  : $request->get_param($field));

            if (($conf['required'] ?? false) && ($raw === null || $raw === '')) {
                $errors[$field][] = 'required'; continue;
            }

            $sanitized = self::apply_sanitizer($conf['sanitize'] ?? null, $raw);

            $type = $conf['type'] ?? null;
            if ($type === 'int'   && !is_numeric($sanitized))                 { $errors[$field][] = 'must_be_int'; }
            if ($type === 'int?'  && !is_null($sanitized) && !is_numeric($sanitized)) { $errors[$field][] = 'must_be_int_or_null'; }
            if ($type === 'string' && !is_string($sanitized))                 { $errors[$field][] = 'must_be_string'; }

            $rule = $conf['rule'] ?? null;
            if ($rule === 'nonempty' && (is_string($sanitized) ? trim($sanitized)==='' : empty($sanitized))) { $errors[$field][] = 'cannot_be_empty'; }
            if ($rule === 'email' && $sanitized !== null && $sanitized !== '' && !is_email($sanitized))      { $errors[$field][] = 'invalid_email'; }
            if ($rule === 'positive' && (!is_numeric($sanitized) || intval($sanitized) <= 0))                { $errors[$field][] = 'must_be_positive'; }

            $request->set_param($field, (is_numeric($sanitized) && $conf['type'] ?? '' === 'int') ? intval($sanitized) : $sanitized);
        }

        return $errors;
    }

    protected static function apply_sanitizer($which, $value) {
        switch ($which) {
            case 'int':     return ($value === null || $value === '') ? null : intval($value);
            case 'text':    return is_string($value) ? sanitize_text_field($value) : '';
            case 'email':   return sanitize_email($value);
            case 'message': return is_string($value) ? wp_kses_post($value) : '';
            default:        return $value;
        }
    }
}
