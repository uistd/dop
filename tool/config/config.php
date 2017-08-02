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

    'runtime_path' => dirname(dirname(__DIR__)) . '/test/runtime',
    'env' => 'dev'
);