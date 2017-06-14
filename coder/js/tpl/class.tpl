define(function (require, exports, module) {
    'use strict';
    var dopBase = require('./{{$dop_base_path}}/dop');
    {{IMPORT_CODE_BUF}}

    /**
     * {{$struct_note}}
     */
    function {{$class_name}}() {
    }

    {{$class_name}}.prototype = {
        {{PROPERTY_CODE_BUF}}
	    {{METHOD_CODE_BUF}}
    };
    module.exports = {{$class_name}};
});
