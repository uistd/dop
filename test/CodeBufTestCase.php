<?php
namespace ffan\dop;

require_once '../vendor/autoload.php';

//测试缩进 退格
$buf = new CodeBuf();
$buf->push('第一行');
$buf->indentIncrease();
$buf->push('第二行缩进');
$buf->pushIndent('第三行缩进');
$buf->push('第四行退格');
$buf->indentDecrease();
$buf->push('第五行退格');
$buf->push('bye');
echo $buf->dump(), PHP_EOL;

//测试 写入一个子buf
$sub_buf = new CodeBuf();
$sub_buf->push('子buf 第一行');
$sub_buf->pushIndent('子buf 第二行 缩进');
$sub_buf->push('子buf 第三行 退格');
$buf = new CodeBuf();
$buf->push('第一行');
$buf->indentIncrease();
$buf->push('第二行缩进');
$buf->pushBuffer($sub_buf);
$buf->push('第三行');
$buf->push('第四行');
$buf->indentDecrease();
$buf->push('第五行退格');
$buf->push('bye');
echo $buf->dump(), PHP_EOL;

//测试 写入一个子buf的引用

$sub_buf = new CodeBuf();
$sub_buf->push('子buf 第一行');
$sub_buf->pushIndent('子buf 第二行 缩进');
$sub_buf->push('子buf 第三行 退格');
$buf = new CodeBuf();
$buf->push('第一行');
$buf->indentIncrease();
$buf->push('第二行缩进');
$buf->pushBuffer($sub_buf, true);
$buf->push('第三行');
$buf->push('第四行');
$buf->indentDecrease();
$buf->push('第五行退格');
$buf->push('bye');
//子buf可继续写入
$sub_buf->push('子buf继续写入');
echo $buf->dump(), PHP_EOL;

//$sub_buf = new CodeBuf(); 
