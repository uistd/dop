'use strict';
var dopBase = require('./{{$dop_base_path}}/dop');
{{IMPORT_CODE_BUF}}

/**
 * {{$struct_note}}
 * @param {Object} data 初始数据
 */
function {{$class_name}}(data) {
    {{code_buf::init_property}}
    if ('object' === typeof data) {
        this.arrayUnpack(data);
    }
}

{{$class_name}}.prototype = {
    {{PROPERTY_CODE_BUF}}
    {{METHOD_CODE_BUF}}
};
module.exports = {{$class_name}};
