'use strict';
//在比较老的ios js引擎不支持  Uint8Array slice方法
if ('function' !== typeof Uint8Array.prototype.slice) {
    Uint8Array.prototype.slice = Array.prototype.slice;
}

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
exports.intVal = function (tmp_var) {
    return tmp_var | 0;
};

/**
 * 传入的值转float
 * @param {*} tmp_var
 * @return {number}
 */
exports.floatVal = function (tmp_var) {
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
exports.strVal = function (tmp_val) {
    return '' + tmp_val;
};

/**
 * 字符串 to Uint8Array
 * @param {string} str
 * @return DopEncode
 */
exports.strToBin = function (str) {
    if ('string' !== typeof str) {
        str = '';
    }
    var DopEncode = require('./DopEncode');
    var buffer = new DopEncode();
    for (var i = 0; i < str.length; i++) {
        var code = str.charCodeAt(i);
        if (code <= 0x7f) {
            buffer.writeChar(code);
        } else if (code <= 0x7ff) {
            buffer.writeChar(0xc0 | (0x1f & (code >> 6)));
            buffer.writeChar(0x80 | (0x3f & code));
        } else if (code <= 0xffff) {
            buffer.writeChar(0xe0 | (0x0f & (code >> 12)));
            buffer.writeChar(0x80 | (0x3f & (code >> 6)));
            buffer.writeChar(0x80 | (0x3f & code));
        } else if (code <= 0x1fffff) {
            buffer.writeChar(0xf0 | (0x07 & (code >> 18)));
            buffer.writeChar(0x80 | (0x3f & (code >> 12)));
            buffer.writeChar(0x80 | (0x3f & (code >> 6)));
            buffer.writeChar(0x80 | (0x3f & code));
        } else if (code <= 0x03ffffff) {
            buffer.writeChar(0xf8 | (0x03 & (code >> 24)));
            buffer.writeChar(0x80 | (0x3f & (code >> 18)));
            buffer.writeChar(0x80 | (0x3f & (code >> 12)));
            buffer.writeChar(0x80 | (0x3f & (code >> 6)));
            buffer.writeChar(0x80 | (0x3f & code));
        } else if (code <= 0x7fffffff) {
            buffer.writeChar(0xfc | (0x01 & (code >> 30)));
            buffer.writeChar(0x80 | (0x3f & (code >> 24)));
            buffer.writeChar(0x80 | (0x3f & (code >> 18)));
            buffer.writeChar(0x80 | (0x3f & (code >> 12)));
            buffer.writeChar(0x80 | (0x3f & (code >> 6)));
            buffer.writeChar(0x80 | (0x3f & code));
        }
    }
    return buffer;
};

/**
 * Uint8Array to 字符串
 * @param arr
 */
exports.binToStr = function (arr) {
    var result = '';
    var total_len = arr.length;
    for (var i = 0; i < total_len;) {
        var ord = arr[i], code;
        //0xxxxxxx
        if (ord <= 0x7f) {
            code = arr[i++];
        }
        //110xxxxx 10xxxxxx
        else if (ord <= 0xdf) {
            code = (arr[i++] ^ 0xc0) << 6;
            code += (arr[i++] ^ 0x80) & 0x3f;
        }
        //1110xxxx 10xxxxxx 10xxxxxx
        else if (ord <= 0xef) {
            code = (arr[i++] ^ 0xe0) << 12;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 6;
            code += (arr[i++] ^ 0x80) & 0x3f;
        }
        //11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
        else if (ord <= 0xf7) {
            code = (arr[i++] ^ 0xf0) << 18;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 12;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 6;
            code += (arr[i++] ^ 0x80) & 0x3f;
        }
        //111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
        else if (ord <= 0xfb) {
            code = (arr[i++] ^ 0xf8) << 24;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 18;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 12;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 6;
            code += (arr[i++] ^ 0x80) & 0x3f;
        }
        //1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
        else if (ord < 0xfd) {
            code = (arr[i++] ^ 0xfc) << 30;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 24;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 18;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 12;
            code += ((arr[i++] ^ 0x80) & 0x3f) << 6;
            code += (arr[i++] ^ 0x80) & 0x3f;
        } else {
            //不能识别的，就是空格
            code = 32;
        }
        result += String.fromCharCode(code);
    }
    return result;
};

/**
 * 获取字符串的真实长度
 * @param {string} str
 * @returns {number}
 */
exports.strlen = function (str) {
    if ('string' !== typeof str) {
        return 0;
    }
    var len = 0;
    for (var i = 0; i < str.length; i++) {
        var code = str.charCodeAt(i);
        if (code <= 0x7f) {
            len++;
        } else if (code <= 0x7ff) {
            len += 2;
        } else if (code <= 0xffff) {
            len += 3;
        } else if (code <= 0x1fffff) {
            len += 4;
        } else if (code <= 0x03ffffff) {
            len += 5;
        } else if (code <= 0x7fffffff) {
            len += 6;
        }
    }
    return len;
};

/**
 * base64编码（核心代码拷贝于GitHub）
 */
exports.base64 = (function () {
    var lookup = [];
    var revLookup = [];

    var code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    for (var i = 0, len = code.length; i < len; ++i) {
        lookup[i] = code[i];
        revLookup[code.charCodeAt(i)] = i;
    }

    revLookup['-'.charCodeAt(0)] = 62;
    revLookup['_'.charCodeAt(0)] = 63;

    function placeHoldersCount(b64) {
        var len = b64.length;
        return b64[len - 2] === '=' ? 2 : b64[len - 1] === '=' ? 1 : 0;
    }

    function toByteArray(b64) {
        if ('string' !== typeof b64 || b64.length % 4 > 0) {
            b64 = '';
        }
        var i, j, l, tmp, placeHolders, arr;
        var len = b64.length;
        placeHolders = placeHoldersCount(b64);

        arr = new Uint8Array((len * 3 / 4) - placeHolders);

        // if there are placeholders, only get up to the last complete 4 chars
        l = placeHolders > 0 ? len - 4 : len;

        var L = 0;

        for (i = 0, j = 0; i < l; i += 4, j += 3) {
            tmp = (revLookup[b64.charCodeAt(i)] << 18) | (revLookup[b64.charCodeAt(i + 1)] << 12) | (revLookup[b64.charCodeAt(i + 2)] << 6) | revLookup[b64.charCodeAt(i + 3)];
            arr[L++] = (tmp >> 16) & 0xFF;
            arr[L++] = (tmp >> 8) & 0xFF;
            arr[L++] = tmp & 0xFF;
        }

        if (placeHolders === 2) {
            tmp = (revLookup[b64.charCodeAt(i)] << 2) | (revLookup[b64.charCodeAt(i + 1)] >> 4);
            arr[L++] = tmp & 0xFF;
        } else if (placeHolders === 1) {
            tmp = (revLookup[b64.charCodeAt(i)] << 10) | (revLookup[b64.charCodeAt(i + 1)] << 4) | (revLookup[b64.charCodeAt(i + 2)] >> 2);
            arr[L++] = (tmp >> 8) & 0xFF;
            arr[L++] = tmp & 0xFF;
        }

        return arr;
    }

    function tripletToBase64(num) {
        return lookup[num >> 18 & 0x3F] + lookup[num >> 12 & 0x3F] + lookup[num >> 6 & 0x3F] + lookup[num & 0x3F];
    }

    function encodeChunk(uint8, start, end) {
        var tmp;
        var output = [];
        for (var i = start; i < end; i += 3) {
            tmp = (uint8[i] << 16) + (uint8[i + 1] << 8) + (uint8[i + 2]);
            output.push(tripletToBase64(tmp));
        }
        return output.join('');
    }

    function fromByteArray(uint8) {
        var tmp;
        var len = uint8.length;
        var extraBytes = len % 3; // if we have 1 byte left, pad 2 bytes
        var output = '';
        var parts = [];
        var maxChunkLength = 16383; // must be multiple of 3

        // go through the array every three bytes, we'll deal with trailing stuff later
        for (var i = 0, len2 = len - extraBytes; i < len2; i += maxChunkLength) {
            parts.push(encodeChunk(uint8, i, (i + maxChunkLength) > len2 ? len2 : (i + maxChunkLength)));
        }

        // pad the end with zeros, but make sure to not forget the extra bytes
        if (extraBytes === 1) {
            tmp = uint8[len - 1];
            output += lookup[tmp >> 2];
            output += lookup[(tmp << 4) & 0x3F];
            output += '=='
        } else if (extraBytes === 2) {
            tmp = (uint8[len - 2] << 8) + (uint8[len - 1]);
            output += lookup[tmp >> 10];
            output += lookup[(tmp >> 4) & 0x3F];
            output += lookup[(tmp << 2) & 0x3F];
            output += '=';
        }

        parts.push(output);

        return parts.join('');
    }

    return {
        decode: function (b64) {
            var result = toByteArray(b64);
            return exports.binToStr(result);
        },
        encode: function (str) {
            if ('string' !== typeof str) {
                str = '';
            }
            var buffer = exports.strToBin(str);
            return fromByteArray(buffer.getBuffer());
        },
        decode2Bite: toByteArray
    };
})();