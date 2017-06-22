'use strict';
{{IMPORT_CODE_BUF}}

/**
 * {{$struct_note}}
 */
function {{$class_name}}() {
    {{code_buf::init_property}}
}

{{$class_name}}.prototype = {
    {{PROPERTY_CODE_BUF}}
    {{METHOD_CODE_BUF}}
};
module.exports = {{$class_name}};
