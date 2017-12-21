<?php

namespace UiStd\Dop\Tool;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Common\Config;
use UiStd\Common\ConfigBase;
use UiStd\Common\Str;
use UiStd\Common\Utils;

/**
 * Class MysqlToXml
 */
class MysqlToXml extends ConfigBase
{
    /**
     * @var string
     */
    private $file_name;

    /**
     * @var \Mysqli
     */
    private $mysal_obj;

    /**
     * @var string 数据库名称
     */
    private $db_name;

    /**
     * @var CodeBuf
     */
    private $file_buf;

    /**
     * @var string mysql配置名称
     */
    private $config_name;

    /**
     * SwaggerToXml constructor.
     * @param string $file_name
     * @param array $mysql_conf
     * @throws \Exception
     */
    public function __construct($file_name, $mysql_conf)
    {
        $this->file_name = $file_name;
        $this->initConfig($mysql_conf);
        $this->config_name = $this->getConfigString('config_name', 'main');
        $config_key = 'uis-mysql:' . $this->config_name;
        $db_config = Config::get($config_key);
        //如果存在数据库配置
        if (is_array($db_config)) {
            $host = $db_config['host'];
            $port = isset($db_config['port']) ? $db_config['port'] : 3306;
            $user = $db_config['user'];
            $password = $db_config['password'];
            $database = $db_config['database'];
        } else {
            $host = $this->getConfig('host', '127.0.0.1');
            $user = $this->getConfig('user');
            $password = $this->getConfig('password', '');
            $database = $this->getConfig('database');
            $port = 3306;
            if (false !== strpos($host, ':')) {
                $tmp = Str::split($host, ':');
                $host = $tmp[0];
                $port = (int)$tmp[1];
            }
        }
        if (empty($user) || empty($database)) {
            throw new \Exception($this->file_name . ' 缺少mysql配置');
        }
        $link_obj = new \mysqli($host, $user, $password, 'information_schema', $port);
        if ($link_obj->connect_errno) {
            throw new \Exception($link_obj->connect_error);
        }
        $this->mysal_obj = $link_obj;
        $this->db_name = $database;
        $this->file_buf = new CodeBuf();
        $conf_arr = array('type="mysql"');
        if (!empty($this->config_name)) {
            $conf_arr[] = 'config_name="' . $this->config_name . '"';
        } else {
            $conf_arr[] = 'host="' . $host . ':' . $port . '"';
            $conf_arr[] = 'user="' . $user . '"';
            $conf_arr[] = 'password="' . $password . '"';
            $conf_arr[] = 'database="' . $database . '"';
        }
        $conf_arr_str = join(' ', $conf_arr);
        $this->file_buf->pushStr('<?xml version="1.0" encoding="UTF-8"?>');
        $this->file_buf->pushStr('<protocol ' . $conf_arr_str . '>');
        $this->file_buf->indent();
        $this->build();
    }

    /**
     * 生成
     */
    private function build()
    {
        $all_tables = $this->queryMany('SELECT * FROM tables where table_schema="' . $this->db_name . '" order by TABLE_NAME');
        foreach ($all_tables as $table) {
            $this->makeTable($table);
        }
        $this->file_buf->backIndent()->pushStr('</protocol>')->emptyLine();
        $content = $this->file_buf->dump();
        file_put_contents($this->file_name, $content);
        echo $this->file_name . ' done!', PHP_EOL;
    }

    /**
     * @param string $sql
     * @return array
     */
    private function queryMany($sql)
    {
        $res = $this->mysal_obj->query($sql);
        $rows = array();
        if (!$res) {
            return $rows;
        }
        while ($row = $res->fetch_array()) {
            $rows[] = $row;
        }
        $res->free();
        return $rows;
    }

    /**
     * 按数据表生成
     * @param array $table_info
     */
    private function makeTable($table_info)
    {
        $code_buf = new CodeBuf();
        $table_name = $table_info['TABLE_NAME'];
        $comment = empty($table_info['TABLE_COMMENT']) ? 'table of ' . $table_name : $table_info['TABLE_COMMENT'];
        $code_buf->pushStr('<data name="' . $table_name . '_model" note="' . $comment . '">')->indent();
        $columns = $this->queryMany('SELECT * FROM columns where table_name="' . $table_name . '"');
        foreach ($columns as $column) {
            $name = $column['COLUMN_NAME'];
            $type = $column['DATA_TYPE'];
            $note = $column['COLUMN_COMMENT'];
            $item_type = $this->varType($type);
            $code_buf->pushStr('<' . $item_type . ' name="' . $name . '" note="' . $note . '"/>');
        }
        $code_buf->backIndent()->pushStr('</data>');
        $this->file_buf->push($code_buf)->emptyLine();
    }

    /**
     * mysql类型转php类型
     * @param $data_type
     * @return string
     */
    private function varType($data_type)
    {
        static $type_map = array(
            'INTEGER' => 'int',
            'INT' => 'int',
            'TINYINT' => 'int',
            'SMALLINT' => 'int',
            'MEDIUMINT' => 'int',
            'BIGINT' => 'bigint',
            'FLOAT' => 'float',
            'DOUBLE' => 'double',
        );
        $type = strtoupper($data_type);
        if (isset($type_map[$type])) {
            return $type_map[$type];
        } else {
            return 'string';
        }
    }

    /**
     * 检测整个目录
     * @param string $folder
     */
    public static function folderDetect($folder)
    {
        $dir_fd = opendir($folder);
        if (!$dir_fd) {
            return;
        }
        while ($file = readdir($dir_fd)) {
            $file = strtolower($file);
            if ('.' === $file{0}) {
                continue;
            }
            $full_file = Utils::joinFilePath($folder, $file);
            if (is_dir($full_file)) {
                self::folderDetect($full_file);
            }
            if ('.xml' === substr($file, -4)) {
                self::xmlInstance($full_file);
            }
        }
    }

    /**
     * @param \DOMElement $node
     * @return null|array
     */
    private static function getAllAttribute($node)
    {
        $attributes = $node->attributes;
        $count = $attributes->length;
        $result = null;
        for ($i = 0; $i < $count; ++$i) {
            $tmp = $attributes->item($i);
            $name = $tmp->nodeName;
            $value = $tmp->nodeValue;
            $result[$name] = $value;
        }
        return $result;
    }

    /**
     * 获取实例
     * @param string $file_name
     */
    private static function xmlInstance($file_name)
    {
        $xml_doc = new \DOMDocument();
        $xml_doc->load($file_name);
        $xml_path = new \DOMXPath($xml_doc);
        $protocol = $xml_path->query('/protocol');
        $main_node = $protocol->item(0);
        if ('mysql' !== $main_node->getAttribute('type')) {
            return;
        }
        $mysql_conf = self::getAllAttribute($main_node);
        echo $file_name . ' begin!', PHP_EOL;
        new self($file_name, $mysql_conf);
    }
}
