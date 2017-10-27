<?php
//php swagger2xml.php http://feed.intra.sit.ffan.com/v2/api-docs
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
     * @var array 公共的model
     */
    private $public_model;

    /**
     * @var array 临时action，未确认最终的类名
     */
    private $tmp_action;

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
        $dom = new DomDocument('1.0', 'utf-8');
        //  创建根节点
        $article = $dom->createElement('protocol');
        //公共的model
        if (!empty($this->public_model)) {
            foreach ($this->public_model as $name => $v) {
                if (!isset($this->model_name[$name])) {
                    continue;
                }
                $class_name = $this->model_name[$name];
                $model_node = $dom->createElement('model');
                $model_node->setAttribute('name', $class_name);
                //todo

            }
        }
        $dom->appendchild($article);
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
                print_r($response_info);
                $response = array();
            } else {
                $response = $this->models[$ref];
            }
            $action['response'] = $response;
        }
        return $action;
    }

    /**
     * 一个参数
     * @param string $name
     * @param array $item
     * @param bool $is_public 如果 遇到model，是否是公共的
     * @return array|string
     */
    private function parseItem($name, $item, $is_public = false)
    {
        //引用其它
        if (isset($item['$ref'])) {
            if (!isset($this->model_name[$name])) {
                $this->model_name[$item['$ref']] = $name;
                if ($is_public) {
                    $this->public_model[$item['$ref']] = true;
                }
            }
            return array(
                'type' => 'model',
                'ref' => $item['$ref']
            );
        }
        if (!isset($item['type']) && isset($item['schema'])) {
            return $this->parseItem($name, $item['schema'], $is_public);
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
                'sub_item' => $this->parseItem($name, $item['items'], $is_public)
            );
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
            $model['summary'] = $name;
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
            $model[$name] = $this->parseItem($name, $item, true);
        }
        return $model;
    }
}
