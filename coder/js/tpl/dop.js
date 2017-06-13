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
    }
});