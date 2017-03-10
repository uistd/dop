<?php
namespace ffan\dop;

use ffan\dop\demo\role\RoleInfo;
use ffan\dop\demo\role\RoleInfoChildren;

require_once '../vendor/autoload.php';
require_once 'runtime/build/demo/role/RoleInfo.php';

$role = new RoleInfo();
$child = new RoleInfoChildren();
$child->age = 8;
$child->gender = 1;
$child->name = '张三';
$child2 = new RoleInfoChildren();
$child2->age = 10;
$child2->gender = 1;
$child2->name = '李四';
$child3 = new RoleInfoChildren();
$child3->age = 12;
$child3->gender = 2;
$child3->name = '王五';
$role->children[] = $child;
$role->children[] = $child2;
$role->children[] = $child3;

$arr = get_object_vars($role);

echo json_encode($arr);
print_r($arr);