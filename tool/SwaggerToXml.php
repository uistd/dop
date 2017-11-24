<?php
use FFan\Dop\Build\CodeBuf;
use FFan\Std\Common\Utils;

/**
 * Class SwaggerToXml
 */
class SwaggerToXml
{
    /**
     * @var array 模型
     */
    private $models;

    /**
     * @var array 模型的类名
     */
    private $model_name;

    /**
     * @var array
     */
    private $actions;

    /**
     * @var array 临时action，未确认最终的类名
     */
    private $tmp_action;

    /**
     * @var array 公共model
     */
    private $public_model;

    /**
     * @var array 已经完成生成的公共model
     */
    private $public_model_done_list;

    /**
     * @var array 公共model的命名
     */
    private $public_model_name;

    /**
     * @var string
     */
    private $save_file_name = '';

    /**
     * @var array 当前存在的model 使用的名称
     */
    private $exist_model_name;

    /**
     * @var array 当前存在的action 使用的名称
     */
    private $exist_action_name;

    /**
     * @var array 当前存在的action 使用的uri名称
     */
    private $exist_action_uri;

    /**
     * @var array 每个action 对应的fullPath名称
     */
    private $action_original_name;

    /**
     * @var string api分组名称
     */
    private $api_group = '';

    /**
     * @var string 协议文档
     */
    private $doc_file;

    /**
     * SwaggerToXml constructor.
     * @param string $doc_file
     * @param string $file_name
     */
    function __construct($doc_file, $file_name)
    {
        if (empty($file_name)) {
            $file_name = 'protocol';
        }
        $this->api_group = basename($file_name, '.xml');
        $this->save_file_name = $file_name;
        $this->parseCurrentFile();
        $this->doc_file = $doc_file;
        $this->read($doc_file);
    }

    /**
     * 解析当前文件, 保存好人工写入的一些东西
     */
    private function parseCurrentFile()
    {
        if (!is_file($this->save_file_name)) {
            return;
        }
        $xml_handle = new \DOMDocument();
        $xml_handle->load($this->save_file_name);
        $xml_path = new DOMXpath($xml_handle);
        $model_list = $xml_path->query('/protocol/model');
        if (null === $model_list) {
            return;
        }
        for ($i = 0; $i < $model_list->length; ++$i) {
            /** @var \DOMElement $model */
            $model = $model_list->item($i);
            if ($model->hasAttribute('swagger_ref')) {
                $name = $model->getAttribute('name');
                $ref = $model->getAttribute('swagger_ref');
                if (!isset($this->exist_model_name[$ref])) {
                    $this->exist_model_name[$ref] = $name;
                }
            }
        }
        $action_list = $xml_path->query('/protocol/action');
        for ($i = 0; $i < $action_list->length; ++$i) {
            /** @var \DOMElement $model */
            $action = $action_list->item($i);
            $action_name = $action->getAttribute('name');
            if ($action->hasAttribute('original_name')) {
                $original_name = $action->getAttribute('original_name');
                if (!isset($this->exist_action_name[$original_name])) {
                    $this->exist_action_name[$original_name] = $action_name;
                }
            }
            $node_list = $action->childNodes;
            for ($n = 0; $n < $node_list->length; ++$n) {
                $node = $node_list->item($n);
                if (XML_ELEMENT_NODE !== $node->nodeType) {
                    continue;
                }
                $node_name = strtolower($node->nodeName);
                if ('request' !== $node_name) {
                    continue;
                }
                $uri = $node->getAttribute('uri');
                if (empty($uri)) {
                    continue;
                }
                $this->exist_action_uri[$action_name] = $uri;
            }
        }
        //标识 已经被占用名称
        foreach ($this->exist_model_name as $tmp_name => $v) {
            $this->public_model_name[$tmp_name] = true;
        }
    }

    /**
     * 读协议文件
     * @param string $doc_file
     */
    private function read($doc_file)
    {
        echo 'Load ' . $doc_file . ' ...', PHP_EOL;
        $content = file_get_contents($doc_file);
        if (empty($content)) {
            exit('无法获取 ' . $doc_file . " 内容\n");
        }
        $schema = json_decode($content, true);
        if (json_last_error()) {
            exit(json_last_error_msg());
        }
        if (!isset($schema['swagger'], $schema['definitions'], $schema['tags'], $schema['paths'])) {
            exit("无法请别 swagger json\n");
        }
        echo 'Generating...', PHP_EOL;
        $this->readModels($schema['definitions']);
        $this->readAction($schema['paths']);
        $this->buildXml();
    }

    /**
     * 读出paths
     * @param array $paths
     */
    private function readAction($paths)
    {
        $tmp_action_arr = array();
        foreach ($paths as $full_path => $actions) {
            $path = preg_replace('#\{(.*?)\}#', 'by_$1', $full_path);
            $path_arr = \FFan\Std\Common\Str::split($path, '/');
            foreach ($actions as $method => $info) {
                $full_name = $method . $full_path;
                $tmp_action = array(
                    'path_arr' => $path_arr,
                    'method' => $method,
                    'info' => $info,
                    'full_path' => $full_name
                );
                //如果 这个名称已经定义过了
                if (isset($this->exist_action_name[$full_name])) {
                    $this->tmp_action[$this->exist_action_name[$full_name]] = $tmp_action;
                } else {
                    $tmp_action_arr[] = $tmp_action;
                }
            }
        }
        $rename_arr = array();
        while (!empty($tmp_action_arr)) {
            $tmp_action = array_shift($tmp_action_arr);
            $path_arr = $tmp_action['path_arr'];
            $name = $tmp_action['method'];
            $is_ok = false;
            for ($i = count($path_arr) - 1; $i >= 0; --$i) {
                $name .= '_' . $path_arr[$i];
                if (!isset($this->tmp_action[$name])) {
                    $is_ok = true;
                    break;
                }
            }

            //如果 没有找到，表示和之前冲突了，把之前的取回来，重新命名
            if (!$is_ok) {
                //防止死循环检测
                if (isset($rename_arr[$name])) {
                    if (++$rename_arr[$name] > 100) {
                        exit("检测到action名称冲突，无法生成\n");
                    }
                } else {
                    $rename_arr[$name] = 1;
                }
                $tmp_action_arr[] = $this->tmp_action[$name];
                unset($tmp_action_arr[$name]);
            }
            $this->tmp_action[$name] = $tmp_action;
        }
        foreach ($this->tmp_action as $name => $tmp_action) {
            $this->actions[$name] = $this->parseAction($tmp_action['info']);
            $this->action_original_name[$name] = $tmp_action['full_path'];
        }
        $this->tmp_action = null;
    }

    /**
     * 生成xml
     */
    private function buildXml()
    {
        $dom = new CodeBuf();
        $dom->pushStr('<?xml version="1.0" encoding="UTF-8"?>');
        //  创建根节点
        $dom->pushStr('<protocol type="swagger" doc="' . $this->doc_file . '">')->indent();
        $action_node = new CodeBuf();
        $model_node = new CodeBuf();
        $dom->push($model_node);
        $dom->push($action_node);
        $dom->backIndent()->pushStr('</protocol>');
        foreach ($this->actions as $name => $action_info) {
            $action_str = '<action name="' . $name . '"';
            if (isset($action_info['summary'])) {
                $action_str .= ' note="' . $action_info['summary'] . '"';
            }
            $action_str .= ' original_name="' . $this->action_original_name[$name] . '"';
            $action_node->pushStr($action_str . '>')->indent();
            $request_node = new CodeBuf();
            $response_node = new CodeBuf();
            $action_node->push($request_node);
            $action_node->push($response_node);
            $action_node->backIndent()->pushStr('</action>')->emptyLine();

            $tmp_pos = strpos($name, '_');
            if (false === $tmp_pos) {
                $method = 'get';
            } else {
                $method = substr($name, 0, $tmp_pos);
            }
            if (isset($this->exist_action_uri[$name])) {
                $uri = $this->exist_action_uri[$name];
            } else {
                $uri = str_replace($method, $this->api_group, $this->action_original_name[$name]);
            }
            $request_node->pushStr('<request method="' . $method . '" uri="' . $uri . '">')->indent();
            if (!empty($action_info['request'])) {
                $this->buildModelXml($request_node, $action_info['request'], $name);
            }
            $request_node->backIndent()->pushStr('</request>');
            if (!empty($action_info['response'])) {
                $response = $action_info['response'];
                $is_standard_api = isset($response['is_standard_api']);
                $str = '<response';
                if ($is_standard_api) {
                    $str .= ' extend="/api/result"';
                    unset($response['is_standard_api']);
                }
                $response_node->pushStr($str . '>')->indent();
                //如果剩下data 是model 类型
                if ($is_standard_api && is_array($response['data']['type']) && 'model' === $response['data']['type']['type']) {
                    $model_name = $name . '_data';
                    $response_node->pushStr('<model name="data" class_name="' . $model_name . '">')->indent();
                    $model_info = $this->models[$response['data']['type']['ref']];
                    $this->buildModelXml($response_node, $model_info, $name);
                    $response_node->backIndent()->pushStr('</model>');
                } else {
                    $this->buildModelXml($response_node, $action_info['response'], $name);
                }
                $response_node->backIndent()->pushStr('</response>');
            }
        }
        $this->buildPublicModel($model_node);
        $dom->pushStr("\n");
        $file_content = $dom->dump();
        $save_path = dirname($this->save_file_name);
        \FFan\Std\Common\Utils::pathWriteCheck($save_path);
        file_put_contents($this->save_file_name, $file_content);
        echo 'Done, save protocol file to ' . $this->save_file_name, PHP_EOL, PHP_EOL;
    }

    /**
     * 生成公共的model
     * @param CodeBuf $model_code_buf
     */
    private function buildPublicModel($model_code_buf)
    {
        while (!empty($this->public_model)) {
            $key = key($this->public_model);
            $name_arr = $this->public_model[$key];
            unset($this->public_model[$key]);
            $this->public_model_done_list[$key] = true;
            $model_name = $name_arr['name'];
            $model_buf = new CodeBuf();
            $note = str_replace('#/definitions/', '', $key);
            $model_buf->pushStr('<model name="' . $model_name . '" note="' . $note . '" swagger_ref="' . $key . '">')->indent();
            $model = $this->models[$key];
            $this->buildModelXml($model_buf, $model, $name_arr['parent_name']);
            $model_buf->backIndent()->pushStr('</model>');
            $model_code_buf->push($model_buf)->emptyLine();
            $this->public_model_done_list[$key] = true;
        }
    }

    /**
     * 生成公共model命名
     * @param string $name
     * @param string $parentName
     * @return string
     */
    private function makePublicModelName($name, $parentName)
    {
        $public_name = \FFan\Std\Common\Str::underlineName($parentName);
        $name_arr = explode('_', $public_name);
        $tmp_name_arr = [];
        $tmp_name = '';
        for ($i = count($name_arr) - 1; $i >= 0; --$i) {
            $each_name = $name_arr[$i];
            $tmp_name_arr[] = $each_name;
            $tmp_name = join('_', $tmp_name_arr) . '_' . $name;
            if (!isset($this->public_model_name[$tmp_name])) {
                $this->public_model_name[$tmp_name] = true;
                return $tmp_name;
            }
        }
        //到最后都还没有找到，采用数字 编号
        $i = 0;
        $tmp_tmp_name = $tmp_name . $i;
        while (!isset($this->public_model_name[$tmp_tmp_name])) {
            $i++;
            $tmp_tmp_name = $tmp_name . $i;
        }
        $this->public_model_name[$tmp_tmp_name] = true;
        return $tmp_tmp_name;
    }

    /**
     * 生成model的xml
     * @param CodeBuf $code_buf
     * @param array $items
     * @param string $parent_name 父级名称
     */
    private function buildModelXml($code_buf, $items, $parent_name)
    {
        foreach ($items as $name => $item) {
            $type = $item['type'];
            if (null === $type) {
                continue;
            }
            $note = isset($item['note']) ? $item['note'] : '';
            $item_node = $this->createTypeNode($type, $name, $parent_name, $note, false);
            $code_buf->push($item_node);
        }
    }

    /**
     * 按类型生成node
     * @param string|array $type
     * @param string $name
     * @param string $parent_name 父级名称
     * @param $note
     * @param bool $is_sub_item
     * @return CodeBuf
     */
    private function createTypeNode($type, $name, $parent_name, $note = null, $is_sub_item = true)
    {
        $result = new CodeBuf();
        $sub_node = null;
        $node_xml = '<';
        $type_str = $extend_str = '';
        if (is_string($type)) {
            $type_str = $type;
        } else {
            $arr_type = $type['type'];
            if ('array' === $arr_type) {
                $type_str = 'list';
                $sub_node = $this->createTypeNode($type['sub_item'], $name, $parent_name);
            } elseif ('model' === $type['type']) {
                $type_str = 'model';
                $ref = $type['ref'];
                if (!isset($this->public_model_done_list[$ref]) && !isset($this->public_model[$ref])) {
                    if (isset($this->exist_model_name[$ref])) {
                        $extend_name = $this->exist_model_name[$ref];
                    } else {
                        $extend_name = $this->makePublicModelName($name, $parent_name);
                    }
                    $this->public_model[$ref] = array('name' => $extend_name, 'parent_name' => $parent_name);
                }
                $extend_str = ' extend="' . $this->public_model[$ref]['name'] . '"';
            }
        }
        $node_xml .= $type_str;
        $node_xml .= $extend_str;
        if (!$is_sub_item) {
            $node_xml .= ' name="' . $name . '"';
            if (!empty($note)) {
                $node_xml .= ' note="' . $note . '"';
            }
        }
        if (null === $sub_node) {
            $node_xml .= '/>';
            $result->pushStr($node_xml);
        } else {
            $node_xml .= '>';
            $result->pushStr($node_xml)->indent();
            $result->push($sub_node);
            $result->backIndent()->pushStr('</' . $type_str . '>');
        }
        return $result;
    }

    /**
     * 解析action
     * @param array $action_info
     * @return array
     */
    private function parseAction($action_info)
    {
        $action = array(
            'summary' => $action_info['summary']
        );
        $request = array();
        if (empty($action_info['parameters'])) {
            $action_info['parameters'] = array();
        }
        foreach ($action_info['parameters'] as $param) {
            $name = $param['name'];
            $item = array('name' => $name);
            if (isset($param['description'])) {
                $item['note'] = $param['description'];
            }
            $item['type'] = $this->parseItem($name, $param);
            //如果 参数 是在body里，那要合并
            if ('body' === $param['in'] && is_array($item['type']) && 'model' === $item['type']['type']) {
                $model_name = $item['type']['ref'];
                $model = isset($this->models[$model_name]) ? $this->models[$model_name] : array();
                foreach ($model as $item_name => $model_item) {
                    $request[$item_name] = $model_item;
                }
            } else {
                $request[$name] = $item;
            }
        }
        $action['request'] = $request;
        foreach ($action_info['responses'] as $status_id => $response_info) {
            if (!isset($response_info['schema'])) {
                continue;
            }
            $response = array();
            //有的response 并不是#ref.
            if (isset($response_info['schema']['$ref'])) {
                $ref = $response_info['schema']['$ref'];
                if (isset($this->models[$ref])) {
                    $response = $this->models[$ref];
                    if (isset($response['data'], $response['status'], $response['message'])) {
                        $response['is_standard_api'] = true;
                        unset($response['status'], $response['message']);
                    }
                }
            }
            $action['response'] = $response;
        }
        return $action;
    }

    /**
     * 一个参数
     * @param string $name
     * @param array $item
     * @return array|string
     */
    private function parseItem($name, $item)
    {
        //引用其它
        if (isset($item['$ref'])) {
            if (!isset($this->model_name[$name])) {
                $this->model_name[$item['$ref']] = $name;
            }
            return array(
                'type' => 'model',
                'ref' => $item['$ref']
            );
        }
        if (!isset($item['type']) && isset($item['schema'])) {
            return $this->parseItem($name, $item['schema']);
        }
        $type = $item['type'];
        $format = isset($item['format']) ? $item['format'] : null;
        if ('integer' === $type) {
            if ('int64' === $format) {
                $type = 'bigint';
            } else {
                $type = 'int';
            }
        } elseif ('double' === $type) {
            if ('double' === $format) {
                $type = 'double';
            } else {
                $type = 'float';
            }
        } elseif ('array' === $type) {
            return array(
                'type' => 'array',
                'sub_item' => $this->parseItem($name, $item['items'])
            );
        } //如果 有object  但没有ref，表示null
        elseif ('object' === $type) {
            return null;
        } elseif ('number' === $type) {
            $type = 'float';
        }
        return $type;
    }

    /**
     * 解析models
     * @param array $definitions
     */
    private function readModels($definitions)
    {
        foreach ($definitions as $name => $struct) {
            if (!isset($struct['type']) || 'object' !== $struct['type']) {
                continue;
            }
            $model_name = '#/definitions/' . $name;
            $model = $this->parseModels($struct);
            $this->models[$model_name] = $model;
        }
    }

    /**
     * 解析model
     * @param array $struct
     * @return array
     */
    private function parseModels($struct)
    {
        $model = array();
        foreach ($struct['properties'] as $name => $item) {
            $tmp_item = array('type' => $this->parseItem($name, $item));
            if (isset($item['description'])) {
                $tmp_item['note'] = $item['description'];
            }
            $model[$name] = $tmp_item;
        }
        return $model;
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
     * 获取实例
     * @param string $file_name
     */
    private static function xmlInstance($file_name)
    {
        $xml_doc = new DOMDocument();
        $xml_doc->load($file_name);
        $xml_path = new DOMXPath($xml_doc);
        $protocol = $xml_path->query('/protocol');
        $main_node = $protocol->item(0);
        if ('swagger' !== $main_node->getAttribute('type')) {
            return;
        }
        $doc = $main_node->getAttribute('doc');
        new self($doc, $file_name);
    }
}
