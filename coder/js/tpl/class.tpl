define(function (require, exports, module) {
    'use strict';
    {{IMPORT_CODE_BUF}}

    /**
     * {{$struct_note}}
     */
    function {{$className}}() {
    }

    {{$className}}.prototype = {
        {{PROPERTY_CODE_BUF}}
	{{METHOD_CODE_BUF}}
    };
    module.exports = {{$className}};
});
