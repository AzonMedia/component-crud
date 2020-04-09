<?php

namespace GuzabaPlatform\Crud\Models;

use Azonmedia\Exceptions\InvalidArgumentException;
use Guzaba2\Authorization\Acl\Permission;
use Guzaba2\Authorization\Role;
use Guzaba2\Authorization\User;
use Guzaba2\Base\Base;
use Guzaba2\Base\Exceptions\RunTimeException;
use Guzaba2\Kernel\Kernel;
use Guzaba2\Mvc\ActiveRecordController;
use Guzaba2\Orm\ActiveRecord;
use Azonmedia\Reflection\ReflectionClass;
use Guzaba2\Orm\Store\Interfaces\StructuredStoreInterface;
use Guzaba2\Orm\Store\Sql\Mysql;
use GuzabaPlatform\Platform\Application\MysqlConnectionCoroutine;

class Permissions extends Base
{
    protected const CONFIG_DEFAULTS = [
        'services'      => [
            'ConnectionFactory',
            'MysqlOrmStore',
        ]
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * Returns the tree of the classes as needed by the navigation hook.
     * @return array
     * @throws \Guzaba2\Base\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
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

    /**
     * Returns the permissions of a specific object.
     * @param string $class_name
     * @param int $object_id
     * @return array
     * @throws RunTimeException
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     */
    public static function get_permissions_by_id(string $class_name, int $object_id)
    {
        $Connection = self::get_service('ConnectionFactory')->get_connection(MysqlConnectionCoroutine::class, $CR);
        /** @var StructuredStoreInterface $StructuredStore */
        $StructuredStore = self::get_service('MysqlOrmStore');

        $meta_table = $StructuredStore::get_meta_table();
        $roles_table = Role::get_main_table();
        $users_table = User::get_main_table();
        $permissions_table = Permission::get_main_table();


        $q = "
SELECT
    roles.*,
    meta.meta_object_uuid,
    acl_permissions.action_name
FROM
    {$Connection::get_tprefix()}{$roles_table} as roles
    LEFT JOIN {$Connection::get_tprefix()}{$permissions_table} as acl_permissions
        ON
            acl_permissions.role_id = roles.role_id
        AND
            acl_permissions.class_id = :class_id
        AND
            acl_permissions.object_id = :object_id
    LEFT JOIN {$Connection::get_tprefix()}{$users_table} as users
        ON
            users.user_id = roles.role_id
    LEFT JOIN
        {$Connection::get_tprefix()}{$meta_table} as meta
        ON
            meta.meta_object_id = acl_permissions.permission_id
        AND
            meta.meta_class_id = :meta_class_id
-- WHERE
--    (users.user_id IS NULL OR users.user_id = 1)
ORDER BY
    roles.role_name
";
        //acl_permissions.class_name = :class_name
        //meta.meta_class_name = :meta_class_name

        $data = $Connection->prepare($q)->execute(['class_id' => $StructuredStore->get_class_id($class_name), 'object_id' => $object_id, 'meta_class_id' => $StructuredStore->get_class_id(Permission::class) ] )->fetchAll();

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