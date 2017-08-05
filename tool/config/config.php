<?php

return array(
    'ffan-tpl' => array(
        'tpl_dir' => 'tool/views'
    ),
    'ffan-git:demo' => array(
        'url' => 'ssh://git@gitlab.ffan.biz:8022/dop/demo.git',
        'username' => 'devtool',
        'email' => 'huangshunzhao@wanda.cn'
    ),

    'ffan-git:pangu_dop' => array(
        'url' => 'ssh://git@gitlab.ffan.biz:8022/dop/pangu.git',
        'username' => 'doptool',
        'email' => '18844626@qq.com'
    ),

    'runtime_path' => dirname(dirname(__DIR__)) . '/test/runtime',
    'env' => 'dev'
);