define(function (require, exports, module) {
    /**
     * 判断变量是否是数组
     * @param {*} tmp_var
     * @returns {boolean}
     */
    exports.isArray = function (tmp_var) {
        return '[object Array]' === Object.prototype.toString.apply(tmp_var);
    };

    /**
     * 判断变量是否存在
     * @param {*} tmp_var
     * @returns {boolean}
     */
    exports.isset = function (tmp_var) {
        return undefined !== tmp_var && null !== tmp_var;
    };

    /**
     * 将传入的值强转成int
     * @param {*} tmp_var
     * @return {int}
     */
    exports.intVal = function(tmp_var) {
        return tmp_var | 0;
    };

    /**
     * 传入的值转float 
     * @param {*} tmp_var
     * @return {number}
     */
    exports.floatVal = function(tmp_var){
        if ('number' === typeof tmp_var) {
            return tmp_var;
        }
        var re = parseFloat(tmp_var);
        if (isNaN(re)) {
            re = 0.0;
        }
        return re;
    };

    /**
     * 传入的值转string
     * @param {*} tmp_val
     * @return {string}
     */
    exports.strVal = function(tmp_val){
        return '' + tmp_val;
    }
});