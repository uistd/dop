<?php
//php swagger2xml.php http://feed.intra.sit.ffan.com/v2/api-docs
use FFan\Dop\Build\CodeBuf;

require_once '../vendor/autoload.php';
if (!isset($argv[1])) {
    exit("请输入swagger api file\n");
}
$doc_file = $argv[1];
$file_name = isset($argv[2]) ? $argv[2] : 'swagger';

new SwaggerToXml($doc_file, $file_name);

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
     * SwaggerToXml constructor.
     * @param string $doc_file
     * @param string $file_name
     */
    function __construct($doc_file, $file_name)
    {
        $this->read($doc_file);
    }

    /**
     * 读协议文件
     * @param string $doc_file
     */
    private function read($doc_file)
    {
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
        foreach ($paths as $path => $actions) {
            $path = str_replace('{', '', $path);
            $path = str_replace('}', '', $path);
            $path_arr = \FFan\Std\Common\Str::split($path, '/');
            foreach ($actions as $method => $info) {
                $tmp_action_arr[] = array(
                    'path_arr' => $path_arr,
                    'method' => $method,
                    'info' => $info
                );
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
        $dom->pushStr('<protocol>')->indent();
        $action_node = new CodeBuf();
        $model_node = new CodeBuf();
        $dom->push($model_node);
        $dom->push($action_node);
        $dom->backIndent()->pushStr('</protocol>');
        foreach ($this->actions as $name => $action_info) {
            $action_name = str_replace('_', '/', $name);
            $action_str = '<action name="'. $action_name .'"';
            if (isset($action_info['summary'])) {
                $action_str .= ' note="'.$action_info['summary'].'"';
            }
            $action_node->pushStr($action_str .'>')->indent();
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
            if (!empty($action_info['request'])) {
                $request_node->pushStr('<request method="'. $method .'">')->indent();
                $this->buildModelXml($request_node, $action_info['request']);
                $request_node->backIndent()->pushStr('</request>');
            }
            if (!empty($action_info['response'])) {
                $response = $action_info['response'];
                $is_standard_api = isset($response['is_standard_api']);
                $str = '<response';
                if ($is_standard_api) {
                    $str .= ' extend="/api/result"';
                    unset($$response['is_standard_api']);
                }
                $response_node->pushStr($str .'>')->indent();
                //如果剩下data 是model 类型
                if ($is_standard_api && is_array($response['data']['type']) && 'model' === $response['data']['type']['type']) {
                    $model_name = $action_name .'_data';
                    $response_node->pushStr('<model name="data" class_name="'. $model_name .'">')->indent();
                    $model_info = $this->models[$response['data']['type']['ref']];
                    $this->buildModelXml($response_node, $model_info);
                    $response_node->backIndent()->pushStr('</model>');
                } else {
                    $this->buildModelXml($response_node, $action_info['response']);
                }
                $response_node->backIndent()->pushStr('</request>');
            }
        }
        print_r($this->public_model);
        echo $dom->dump();
    }

    /**
     * 生成model的xml
     * @param CodeBuf $request_code
     * @param array $items
     */
    private function buildModelXml($request_code, $items)
    {
        foreach($items as $name => $item) {
            $type = $item['type'];
            if (null === $type) {
                continue;
            }
            $note = isset($item['note']) ? $item['note'] : '';
            $item_node = $this->createTypeNode($type, $name, $note, false);
            $request_code->push($item_node);
        }
    }

    /**
     * 按类型生成node
     * @param string|array $type
     * @param string $name
     * @param $note
     * @param bool $is_sub_item
     * @return CodeBuf
     */
    private function createTypeNode($type, $name, $note = null, $is_sub_item = true)
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
                $sub_node = $this->createTypeNode($type['sub_item'], $name);
            } elseif ('model' === $type['type']) {
                $type_str = 'model';
                $ref = $type['ref'];
                if (!isset($this->public_model[$ref])) {
                    $this->public_model[$ref] = $name;
                }
                $extend_str = ' extend="'.$name.'"';
            }
        }
        $node_xml .= $type_str;
        $node_xml .= $extend_str;
        if (!$is_sub_item) {
            $node_xml .= ' name="'.$name.'"';
            if (!empty($note)) {
                $node_xml .= ' note="'. $note .'"';
            }
        }
        if (null === $sub_node) {
            $node_xml .= '/>';
            $result->pushStr($node_xml);
        } else {
            $node_xml .= '>';
            $result->pushStr($node_xml)->indent();
            $result->push($sub_node);
            $result->backIndent()->pushStr('</'.$type_str.'>');
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
            $ref = $response_info['schema']['$ref'];
            if (!isset($this->models[$ref])) {
                $response = array();
            } else {
                $response = $this->models[$ref];
                if (isset($response['data'], $response['status'], $response['message'])) {
                    $response['is_standard_api'] = true;
                    unset($response['status'], $response['message']);
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
        }
        //如果 有object  但没有ref，表示null
        elseif ('object' === $type) {
            return null;
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
}
