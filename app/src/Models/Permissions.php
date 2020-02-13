<?php

namespace GuzabaPlatform\Crud\Models;

use Guzaba2\Authorization\Acl\Permission;
use Guzaba2\Base\Base;
use Guzaba2\Kernel\Kernel;
use Guzaba2\Mvc\ActiveRecordController;
use Guzaba2\Orm\ActiveRecord;
use Azonmedia\Reflection\ReflectionClass;
use GuzabaPlatform\Platform\Application\MysqlConnectionCoroutine;

class Permissions extends Base
{
    protected const CONFIG_DEFAULTS = [
        'services'      => [
            'ConnectionFactory',
        ]
    ];

    protected const CONFIG_RUNTIME = [];

    public static function get_tree()
    {
        // get all ActiveRecord classes that are loaded by the Kernel
        $controllers = ActiveRecord::get_active_record_classes(array_keys(Kernel::get_registered_autoloader_paths()));
        $controllers_classes = [];
        $non_controllers_classes = [];

        foreach ($controllers as $class_name) {
            $RClass = new ReflectionClass($class_name);

            if ($RClass->extendsClass(ActiveRecordController::class)) {
                $controllers_classes[$class_name] = $class_name;
            } else {
                $non_controllers_classes[$class_name] = $class_name;
            }
        }

        $controllers_tree = self::explode_tree($controllers_classes, "\\");
        $non_controllers_tree = self::explode_tree($non_controllers_classes, "\\", TRUE);

        return [$controllers_tree, $non_controllers_tree];
    }

    private static function explode_tree($array, $delimiter = '\\', bool $add_crud_actions = FALSE, $baseval = false)
    {
        if(!is_array($array)) return false;

        $splitRE   = '/' . preg_quote($delimiter, '/') . '/';

        $return_arr = array();

        foreach ($array as $key => $val) {
            // Get parent parts and the current leaf
            $parts	= preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
            $leaf_part = array_pop($parts);

            // Build parent structure
            // Might be slow for really deep and large structures
            $_parent_arr = &$return_arr;

            foreach ($parts as $part) {
                if (!isset($_parent_arr[$part])) {
                    $_parent_arr[$part] = array();
                } elseif (!is_array($_parent_arr[$part])) {
                    if ($baseval) {
                        $_parent_arr[$part] = array('__base_val' => $_parent_arr[$part]);
                    } else {
                        $_parent_arr[$part] = array();
                    }
                }
                $_parent_arr = &$_parent_arr[$part];
            }

            // Add the final part to the structure
            if (empty($_parent_arr[$leaf_part])) {
                $RClass = new ReflectionClass($val);

                $methods_arr = [];
                foreach ($RClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->class == $val && substr($method->name, 0, 1 ) !== "_" ) {
                        $methods_arr[$method->name] = $val . "::" . $method->name;
                    }
                }

                if($add_crud_actions) {
                    $methods_arr = array_merge([
                        'create' => $val . '::create',
                        'read' => $val . '::read',
                        'write' => $val . '::write',
                        'delete' => $val . '::delete',
                        'grant_permission' => $val . '::grant_permission',
                        'revoke_permission' => $val . '::revoke_permission'
                    ], $methods_arr);
                }

                $_parent_arr[$leaf_part] = $methods_arr;
            } elseif ($baseval && is_array($_parent_arr[$leaf_part])) {
                $_parent_arr[$leaf_part]['__base_val'] = $val;
            }
        }
        return $return_arr;
    }

//    /**
//     * Returns the permissions of the controllers.
//     * @param string $class_name
//     * @param string $action_name
//     * @return mixed
//     * @throws \Guzaba2\Base\Exceptions\RunTimeException
//     */
//    public static function get_permissions(string $class_name, string $action_name)
//    {
//        $Connection = self::get_service('ConnectionFactory')->get_connection(MysqlConnectionCoroutine::class, $ScopeReference);
//
//        $q = "
//SELECT
//    roles.*,
//    meta.meta_object_uuid,
//    CASE WHEN roles.role_id = acl_permissions.role_id THEN 1 ELSE 0 END as granted,
//    CASE WHEN roles.role_id = acl_permissions.role_id THEN 'success' ELSE '' END as _rowVariant
//FROM
//    {$Connection::get_tprefix()}roles as roles
//LEFT JOIN
//    {$Connection::get_tprefix()}acl_permissions as acl_permissions
//    ON
//        roles.role_id = acl_permissions.role_id
//    AND
//        acl_permissions.class_name = :class_name
//    AND
//        acl_permissions.action_name = :action_name
//    AND
//		(acl_permissions.object_id IS NULL OR acl_permissions.object_id = 0)
//LEFT JOIN
//    {$Connection::get_tprefix()}users as users
//    ON
//        roles.role_id = users.user_id
//LEFT JOIN
//    {$Connection::get_tprefix()}object_meta as meta
//    ON
//        meta.meta_object_id = acl_permissions.permission_id
//WHERE
//	(users.user_id IS NULL OR users.user_id = 1)
//
//ORDER BY
//    roles.role_name
//";
//
//        $data = $Connection->prepare($q)->execute(['class_name' => $class_name, 'action_name' => $action_name])->fetchAll();
//        return $data;
//    }

    /**
     * Returns the permissions of a specific object.
     * @param string $class_name
     * @param string $object_id
     * @return array
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     */
    public static function get_permissions_by_id(string $class_name, string $object_id)
    {
        $Connection = self::get_service('ConnectionFactory')->get_connection(MysqlConnectionCoroutine::class, $ScopeReference);

/*
        $q = "
SELECT
    roles.*,
    meta.meta_object_uuid,
    acl_permissions.action_name
FROM
    {$Connection::get_tprefix()}roles as roles
LEFT JOIN
    {$Connection::get_tprefix()}acl_permissions as acl_permissions
    ON
        roles.role_id = acl_permissions.role_id
    AND
        acl_permissions.class_name = :class_name
    AND
        acl_permissions.object_id = :object_id
LEFT JOIN
    {$Connection::get_tprefix()}users as users
    ON
        roles.role_id = users.user_id
LEFT JOIN
    {$Connection::get_tprefix()}object_meta as meta
    ON
        meta.meta_object_id = acl_permissions.permission_id
    AND
        meta.meta_class_name = :meta_class_name
-- WHERE
--    (users.user_id IS NULL OR users.user_id = 1)
ORDER BY
    roles.role_name
";

        $data = $Connection->prepare($q)->execute(['class_name' => $class_name, 'object_id' => $object_id, 'meta_class_name' => Permission::class])->fetchAll();

        $ret = [];
        foreach ($data as $row) {
            print_r($row);

            $ret[$row['role_id']]['role_id'] = $row['role_id'];
            $ret[$row['role_id']]['role_name'] = $row['role_name'];

            if ($row['action_name']) {
                $ret[$row['role_id']][$row['action_name'] . '_granted'] = $row['meta_object_uuid'];
            }
        }
//print_r($ret);
*/


        $q = "
SELECT
    roles.*,
    meta.meta_object_uuid,
    acl_permissions.action_name
FROM
    {$Connection::get_tprefix()}roles as roles
LEFT JOIN
    {$Connection::get_tprefix()}acl_permissions as acl_permissions
    ON
        acl_permissions.role_id = roles.role_id
    AND
        acl_permissions.class_name = :class_name
    AND
        acl_permissions.object_id = :object_id
LEFT JOIN
    {$Connection::get_tprefix()}users as users
    ON
        users.user_id = roles.role_id
LEFT JOIN
    {$Connection::get_tprefix()}object_meta as meta
    ON
        meta.meta_object_id = acl_permissions.permission_id
    AND
        meta.meta_class_name = :meta_class_name
-- WHERE
--    (users.user_id IS NULL OR users.user_id = 1)
ORDER BY
    roles.role_name
";

        $data = $Connection->prepare($q)->execute(['class_name' => $class_name, 'object_id' => $object_id, 'meta_class_name' => Permission::class])->fetchAll();

        $ret = [];
        $object_actions = $class_name::get_object_actions();
        foreach ($data as $row) {
            if (!array_key_exists($row['role_id'], $ret)) {
                $ret[$row['role_id']]['role_id'] = $row['role_id'];
                $ret[$row['role_id']]['role_name'] = $row['role_name'];
                $ret[$row['role_id']]['permissions'] = [];
            }

            foreach ($object_actions as $object_action) {
                if ($row['action_name'] && $row['action_name'] === $object_action) {
                    $ret [$row['role_id']] ['permissions'] [ $object_action ] = $row['meta_object_uuid'];
                } elseif (!array_key_exists($object_action, $ret[$row['role_id']]['permissions'] )) {
                    $ret [$row['role_id']] ['permissions'] [ $object_action ] = '';
                }
            }

        }

        return $ret;
    }
}