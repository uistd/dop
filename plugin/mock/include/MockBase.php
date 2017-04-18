<?php

namespace ffan\dop\plugin\mock;

/**
 * Class MockBase 基础类
 * @package ffan\dop\plugin\mock
 */
class MockBase
{
    /**
     * 随机生成字符串
     * @param int $min_len
     * @param int $max_len
     * @return string
     */
    public static function strRangeMock($min_len, $max_len)
    {
        static $str_table = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $total_len = strlen($str_table);
        $re_len = mt_rand($min_len, $max_len);
        $result_str = '';
        for ($i = 0; $i < $re_len; ++$i) {
            $result_str .= $str_table[mt_rand(0, $total_len)];
        }
        return $result_str;
    }

    /**
     * 生成手机号
     * @return string
     */
    public static function mobileTypeMock()
    {
        //手机有效号码前3位
        $prefix_number = array(
            '134', '135', '136', '137', '138', '139', '150', '151', '152', '158', '159', '182', '183', '184',
            '186', '130', '131', '132', '155', '156', '176',
            '180', '181', '189', '133', '153', '177', '170'
        );
        $prefix = $prefix_number[array_rand($prefix_number)];
        $suffix = (string)mt_rand(10000000, 99999999);
        return $prefix . $suffix;
    }

    /**
     * 生成邮箱
     * @return string
     */
    public static function emailTypeMock()
    {
        //暂时就生成QQ邮箱
        $code = (string)mt_rand(10000000, 999999999999);
        return $code . '@qq.com';
    }

    /**
     * 随机生成
     */
    public static function chineseNameTypeMock()
    {
        static $first_name_table = array('赵', '孙', '李', '周', '郑', '王', '陈', '杨', '朱', '许', '何', '张', '严', '魏', '谢', '邹', '苏', '范', '彭', '马', '雷', '黄', '罗', '安');
        static $last_name_table = array('伟', '刚', '勇', '毅', '俊', '峰', '强', '军', '平', '保', '东', '文', '辉', '力', '明', '永', '健', '世', '广', '志',
            '义', '兴', '良', '海', '山', '仁', '波', '宁', '贵', '福', '生', '龙', '元', '全', '国', '胜', '学', '祥', '才', '发', '武', '新', '利', '清',
            '飞', '彬', '富', '顺', '信', '子', '杰', '涛', '昌', '成', '康', '星', '光', '天', '达', '安', '岩', '中', '茂', '进', '林', '有', '坚', '和',
            '彪', '博', '诚', '先', '敬', '震', '振', '壮', '会', '思', '群', '豪', '心', '邦', '承', '乐', '绍', '功', '松', '善', '厚', '庆', '磊', '民',
            '友', '裕', '河', '哲', '江', '超', '浩', '亮', '政', '谦', '亨', '奇', '固', '之', '轮', '翰', '朗', '伯', '宏', '言', '若', '鸣', '朋', '斌',
            '梁', '栋', '维', '启', '克', '伦', '翔', '旭', '鹏', '泽', '晨', '辰', '士', '以', '建', '家', '致', '树', '炎', '德', '行', '时', '泰', '盛',
            '雄', '琛', '钧', '冠', '策', '腾', '楠', '榕', '风', '航', '弘', '秀', '娟', '英', '华', '慧', '巧', '美', '娜', '静', '淑', '惠', '珠', '翠',
            '雅', '芝', '玉', '萍', '红', '娥', '玲', '芬', '芳', '燕', '彩', '春', '菊', '兰', '凤', '洁', '梅', '琳', '素', '云', '莲', '真', '环', '雪',
            '荣', '爱', '妹', '霞', '香', '月', '莺', '媛', '艳', '瑞', '凡', '佳', '嘉', '琼', '勤', '珍', '贞', '莉', '桂', '娣', '叶', '璧', '璐', '娅',
            '琦', '晶', '妍', '茜', '秋', '珊', '莎', '锦', '黛', '青', '倩', '婷', '姣', '婉', '娴', '瑾', '颖', '露', '瑶', '怡', '婵', '雁', '蓓', '纨',
            '仪', '荷', '丹', '蓉', '眉', '君', '琴', '蕊', '薇', '菁', '梦', '岚', '苑', '婕', '馨', '瑗', '琰', '韵', '融', '园', '艺', '咏', '卿', '聪',
            '澜', '纯', '毓', '悦', '昭', '冰', '爽', '琬', '茗', '羽', '希', '欣', '飘', '育', '滢', '馥', '筠', '柔', '竹', '霭', '凝', '晓', '欢', '霄',
            '枫', '芸', '菲', '寒', '伊', '亚', '宜', '可', '姬', '舒', '影', '荔', '枝', '丽', '阳', '妮', '宝', '贝', '初', '程', '梵', '罡', '恒', '鸿',
            '桦', '骅', '剑', '娇', '纪', '宽', '苛', '灵', '玛', '媚', '琪', '晴', '容', '睿', '烁', '堂', '唯', '威', '韦', '雯', '苇', '萱', '阅', '彦',
            '宇', '雨', '洋', '忠', '宗', '曼', '紫', '逸', '贤', '蝶', '菡', '绿', '蓝', '儿', '翠', '烟');
        $first_name = $first_name_table[array_rand($first_name_table)];
        $last_name = $last_name_table[array_rand($last_name_table)];
        //60%的概率是3个字
        if (mt_rand(0, 100) > 40) {
            $last_name .= $last_name_table[array_rand($last_name_table)];
        }
        return $first_name . $last_name;
    }

    /**
     * 随机生成日期（从去年今日 到 明年今日 之间随机）
     */
    public static function dateTypeMock()
    {
        $sec_beg = strtotime('-1 year');
        $sec_end = strtotime('+1 year');
        $sec = mt_rand($sec_beg, $sec_end);
        return date('Y-m-d', $sec);
    }

    /**
     * 随机生成日期 - 时间（从去年今日 到 明年今日 之间随机）
     */
    public static function dateTimeTypeMock()
    {
        $sec_beg = strtotime('-1 year');
        $sec_end = strtotime('+1 year');
        $sec = mt_rand($sec_beg, $sec_end);
        return date('Y-m-d H:i:s', $sec);
    }

    /**
     * 随机浮点数
     * @param float $min
     * @param float $max
     * @param int $precision 小数点后位数
     * @return float
     */
    public static function floatRangeMock($min, $max, $precision = 2)
    {
        $min = (int)$min;
        $max = (int)$max;
        if ($max <= $min) {
            $max = $min + 1;
        }
        $rand = mt_rand();
        $rand_max = mt_getrandmax();
        return round($min + $rand / $rand_max * ($max - $min), $precision);
    }
}
