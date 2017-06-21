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
 * 判断变量是否是Object
 * @param {*} tmp_var
 * @returns {boolean}
 */
exports.isObject = function(tmp_var) {
    return '[object Object]' === Object.prototype.toString.apply(tmp_var);
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
        encode: function (buffer) {
            if ('string' === typeof buffer) {
                var tmp_buf = exports.strToBin(buffer);
                buffer = tmp_buf.getBuffer();
            }
            return fromByteArray(buffer);
        },
        decode2Byte: toByteArray
    };
})();

/**
 * Md5 核心代码拷贝自网上
 */
exports.md5 = (function(){
    var HEX_CHARS = '0123456789abcdef'.split('');
    var EXTRA = [128, 32768, 8388608, -2147483648];

    /**
     * Md5 class
     */
    function Md5() {
        var buffer = new ArrayBuffer(68);
        this.buffer8 = new Uint8Array(buffer);
        this.blocks = new Uint32Array(buffer);
        this.h0 = this.h1 = this.h2 = this.h3 = this.start = this.bytes = 0;
        this.finalized = this.hashed = false;
        this.first = true;
    }

    Md5.prototype.update = function (message, length) {
        if (this.finalized) {
            return;
        }
        var notString = typeof message !== 'string';
        if (notString && message.constructor === ArrayBuffer) {
            message = new Uint8Array(message);
        }
        var code, index = 0, i, blocks = this.blocks;
        length |= 0;
        if (0 === length) {
            length = message.length;
        }
        var buffer8 = this.buffer8;

        while (index < length) {
            if (this.hashed) {
                this.hashed = false;
                for (var _i = 0; _i <=16; ++i) {
                    blocks[_i] = 0;
                }
            }
            if (notString) {
                for (i = this.start; index < length && i < 64; ++index) {
                    buffer8[i++] = message[index];
                }
            } else {
                for (i = this.start; index < length && i < 64; ++index) {
                    code = message.charCodeAt(index);
                    if (code < 0x80) {
                        buffer8[i++] = code;
                    } else if (code < 0x800) {
                        buffer8[i++] = 0xc0 | (code >> 6);
                        buffer8[i++] = 0x80 | (code & 0x3f);
                    } else if (code < 0xd800 || code >= 0xe000) {
                        buffer8[i++] = 0xe0 | (code >> 12);
                        buffer8[i++] = 0x80 | ((code >> 6) & 0x3f);
                        buffer8[i++] = 0x80 | (code & 0x3f);
                    } else {
                        code = 0x10000 + (((code & 0x3ff) << 10) | (message.charCodeAt(++index) & 0x3ff));
                        buffer8[i++] = 0xf0 | (code >> 18);
                        buffer8[i++] = 0x80 | ((code >> 12) & 0x3f);
                        buffer8[i++] = 0x80 | ((code >> 6) & 0x3f);
                        buffer8[i++] = 0x80 | (code & 0x3f);
                    }
                }
            }
            this.lastByteIndex = i;
            this.bytes += i - this.start;
            if (i >= 64) {
                this.start = i - 64;
                this.hash();
                this.hashed = true;
            } else {
                this.start = i;
            }
        }
        return this;
    };

    Md5.prototype.finalize = function () {
        if (this.finalized) {
            return;
        }
        this.finalized = true;
        var blocks = this.blocks, i = this.lastByteIndex;
        blocks[i >> 2] |= EXTRA[i & 3];
        if (i >= 56) {
            if (!this.hashed) {
                this.hash();
            }
            blocks[0] = blocks[16];
            blocks[16] = blocks[1] = blocks[2] = blocks[3] =
                blocks[4] = blocks[5] = blocks[6] = blocks[7] =
                    blocks[8] = blocks[9] = blocks[10] = blocks[11] =
                        blocks[12] = blocks[13] = blocks[14] = blocks[15] = 0;
        }
        blocks[14] = this.bytes << 3;
        this.hash();
    };

    Md5.prototype.hash = function () {
        var a, b, c, d, bc, da, blocks = this.blocks;

        if (this.first) {
            a = blocks[0] - 680876937;
            a = (a << 7 | a >>> 25) - 271733879 << 0;
            d = (-1732584194 ^ a & 2004318071) + blocks[1] - 117830708;
            d = (d << 12 | d >>> 20) + a << 0;
            c = (-271733879 ^ (d & (a ^ -271733879))) + blocks[2] - 1126478375;
            c = (c << 17 | c >>> 15) + d << 0;
            b = (a ^ (c & (d ^ a))) + blocks[3] - 1316259209;
            b = (b << 22 | b >>> 10) + c << 0;
        } else {
            a = this.h0;
            b = this.h1;
            c = this.h2;
            d = this.h3;
            a += (d ^ (b & (c ^ d))) + blocks[0] - 680876936;
            a = (a << 7 | a >>> 25) + b << 0;
            d += (c ^ (a & (b ^ c))) + blocks[1] - 389564586;
            d = (d << 12 | d >>> 20) + a << 0;
            c += (b ^ (d & (a ^ b))) + blocks[2] + 606105819;
            c = (c << 17 | c >>> 15) + d << 0;
            b += (a ^ (c & (d ^ a))) + blocks[3] - 1044525330;
            b = (b << 22 | b >>> 10) + c << 0;
        }

        a += (d ^ (b & (c ^ d))) + blocks[4] - 176418897;
        a = (a << 7 | a >>> 25) + b << 0;
        d += (c ^ (a & (b ^ c))) + blocks[5] + 1200080426;
        d = (d << 12 | d >>> 20) + a << 0;
        c += (b ^ (d & (a ^ b))) + blocks[6] - 1473231341;
        c = (c << 17 | c >>> 15) + d << 0;
        b += (a ^ (c & (d ^ a))) + blocks[7] - 45705983;
        b = (b << 22 | b >>> 10) + c << 0;
        a += (d ^ (b & (c ^ d))) + blocks[8] + 1770035416;
        a = (a << 7 | a >>> 25) + b << 0;
        d += (c ^ (a & (b ^ c))) + blocks[9] - 1958414417;
        d = (d << 12 | d >>> 20) + a << 0;
        c += (b ^ (d & (a ^ b))) + blocks[10] - 42063;
        c = (c << 17 | c >>> 15) + d << 0;
        b += (a ^ (c & (d ^ a))) + blocks[11] - 1990404162;
        b = (b << 22 | b >>> 10) + c << 0;
        a += (d ^ (b & (c ^ d))) + blocks[12] + 1804603682;
        a = (a << 7 | a >>> 25) + b << 0;
        d += (c ^ (a & (b ^ c))) + blocks[13] - 40341101;
        d = (d << 12 | d >>> 20) + a << 0;
        c += (b ^ (d & (a ^ b))) + blocks[14] - 1502002290;
        c = (c << 17 | c >>> 15) + d << 0;
        b += (a ^ (c & (d ^ a))) + blocks[15] + 1236535329;
        b = (b << 22 | b >>> 10) + c << 0;
        a += (c ^ (d & (b ^ c))) + blocks[1] - 165796510;
        a = (a << 5 | a >>> 27) + b << 0;
        d += (b ^ (c & (a ^ b))) + blocks[6] - 1069501632;
        d = (d << 9 | d >>> 23) + a << 0;
        c += (a ^ (b & (d ^ a))) + blocks[11] + 643717713;
        c = (c << 14 | c >>> 18) + d << 0;
        b += (d ^ (a & (c ^ d))) + blocks[0] - 373897302;
        b = (b << 20 | b >>> 12) + c << 0;
        a += (c ^ (d & (b ^ c))) + blocks[5] - 701558691;
        a = (a << 5 | a >>> 27) + b << 0;
        d += (b ^ (c & (a ^ b))) + blocks[10] + 38016083;
        d = (d << 9 | d >>> 23) + a << 0;
        c += (a ^ (b & (d ^ a))) + blocks[15] - 660478335;
        c = (c << 14 | c >>> 18) + d << 0;
        b += (d ^ (a & (c ^ d))) + blocks[4] - 405537848;
        b = (b << 20 | b >>> 12) + c << 0;
        a += (c ^ (d & (b ^ c))) + blocks[9] + 568446438;
        a = (a << 5 | a >>> 27) + b << 0;
        d += (b ^ (c & (a ^ b))) + blocks[14] - 1019803690;
        d = (d << 9 | d >>> 23) + a << 0;
        c += (a ^ (b & (d ^ a))) + blocks[3] - 187363961;
        c = (c << 14 | c >>> 18) + d << 0;
        b += (d ^ (a & (c ^ d))) + blocks[8] + 1163531501;
        b = (b << 20 | b >>> 12) + c << 0;
        a += (c ^ (d & (b ^ c))) + blocks[13] - 1444681467;
        a = (a << 5 | a >>> 27) + b << 0;
        d += (b ^ (c & (a ^ b))) + blocks[2] - 51403784;
        d = (d << 9 | d >>> 23) + a << 0;
        c += (a ^ (b & (d ^ a))) + blocks[7] + 1735328473;
        c = (c << 14 | c >>> 18) + d << 0;
        b += (d ^ (a & (c ^ d))) + blocks[12] - 1926607734;
        b = (b << 20 | b >>> 12) + c << 0;
        bc = b ^ c;
        a += (bc ^ d) + blocks[5] - 378558;
        a = (a << 4 | a >>> 28) + b << 0;
        d += (bc ^ a) + blocks[8] - 2022574463;
        d = (d << 11 | d >>> 21) + a << 0;
        da = d ^ a;
        c += (da ^ b) + blocks[11] + 1839030562;
        c = (c << 16 | c >>> 16) + d << 0;
        b += (da ^ c) + blocks[14] - 35309556;
        b = (b << 23 | b >>> 9) + c << 0;
        bc = b ^ c;
        a += (bc ^ d) + blocks[1] - 1530992060;
        a = (a << 4 | a >>> 28) + b << 0;
        d += (bc ^ a) + blocks[4] + 1272893353;
        d = (d << 11 | d >>> 21) + a << 0;
        da = d ^ a;
        c += (da ^ b) + blocks[7] - 155497632;
        c = (c << 16 | c >>> 16) + d << 0;
        b += (da ^ c) + blocks[10] - 1094730640;
        b = (b << 23 | b >>> 9) + c << 0;
        bc = b ^ c;
        a += (bc ^ d) + blocks[13] + 681279174;
        a = (a << 4 | a >>> 28) + b << 0;
        d += (bc ^ a) + blocks[0] - 358537222;
        d = (d << 11 | d >>> 21) + a << 0;
        da = d ^ a;
        c += (da ^ b) + blocks[3] - 722521979;
        c = (c << 16 | c >>> 16) + d << 0;
        b += (da ^ c) + blocks[6] + 76029189;
        b = (b << 23 | b >>> 9) + c << 0;
        bc = b ^ c;
        a += (bc ^ d) + blocks[9] - 640364487;
        a = (a << 4 | a >>> 28) + b << 0;
        d += (bc ^ a) + blocks[12] - 421815835;
        d = (d << 11 | d >>> 21) + a << 0;
        da = d ^ a;
        c += (da ^ b) + blocks[15] + 530742520;
        c = (c << 16 | c >>> 16) + d << 0;
        b += (da ^ c) + blocks[2] - 995338651;
        b = (b << 23 | b >>> 9) + c << 0;
        a += (c ^ (b | ~d)) + blocks[0] - 198630844;
        a = (a << 6 | a >>> 26) + b << 0;
        d += (b ^ (a | ~c)) + blocks[7] + 1126891415;
        d = (d << 10 | d >>> 22) + a << 0;
        c += (a ^ (d | ~b)) + blocks[14] - 1416354905;
        c = (c << 15 | c >>> 17) + d << 0;
        b += (d ^ (c | ~a)) + blocks[5] - 57434055;
        b = (b << 21 | b >>> 11) + c << 0;
        a += (c ^ (b | ~d)) + blocks[12] + 1700485571;
        a = (a << 6 | a >>> 26) + b << 0;
        d += (b ^ (a | ~c)) + blocks[3] - 1894986606;
        d = (d << 10 | d >>> 22) + a << 0;
        c += (a ^ (d | ~b)) + blocks[10] - 1051523;
        c = (c << 15 | c >>> 17) + d << 0;
        b += (d ^ (c | ~a)) + blocks[1] - 2054922799;
        b = (b << 21 | b >>> 11) + c << 0;
        a += (c ^ (b | ~d)) + blocks[8] + 1873313359;
        a = (a << 6 | a >>> 26) + b << 0;
        d += (b ^ (a | ~c)) + blocks[15] - 30611744;
        d = (d << 10 | d >>> 22) + a << 0;
        c += (a ^ (d | ~b)) + blocks[6] - 1560198380;
        c = (c << 15 | c >>> 17) + d << 0;
        b += (d ^ (c | ~a)) + blocks[13] + 1309151649;
        b = (b << 21 | b >>> 11) + c << 0;
        a += (c ^ (b | ~d)) + blocks[4] - 145523070;
        a = (a << 6 | a >>> 26) + b << 0;
        d += (b ^ (a | ~c)) + blocks[11] - 1120210379;
        d = (d << 10 | d >>> 22) + a << 0;
        c += (a ^ (d | ~b)) + blocks[2] + 718787259;
        c = (c << 15 | c >>> 17) + d << 0;
        b += (d ^ (c | ~a)) + blocks[9] - 343485551;
        b = (b << 21 | b >>> 11) + c << 0;

        if (this.first) {
            this.h0 = a + 1732584193 << 0;
            this.h1 = b - 271733879 << 0;
            this.h2 = c - 1732584194 << 0;
            this.h3 = d + 271733878 << 0;
            this.first = false;
        } else {
            this.h0 = this.h0 + a << 0;
            this.h1 = this.h1 + b << 0;
            this.h2 = this.h2 + c << 0;
            this.h3 = this.h3 + d << 0;
        }
    };

    Md5.prototype.hex = function () {
        this.finalize();

        var h0 = this.h0, h1 = this.h1, h2 = this.h2, h3 = this.h3;

        return HEX_CHARS[(h0 >> 4) & 0x0F] + HEX_CHARS[h0 & 0x0F] +
            HEX_CHARS[(h0 >> 12) & 0x0F] + HEX_CHARS[(h0 >> 8) & 0x0F] +
            HEX_CHARS[(h0 >> 20) & 0x0F] + HEX_CHARS[(h0 >> 16) & 0x0F] +
            HEX_CHARS[(h0 >> 28) & 0x0F] + HEX_CHARS[(h0 >> 24) & 0x0F] +
            HEX_CHARS[(h1 >> 4) & 0x0F] + HEX_CHARS[h1 & 0x0F] +
            HEX_CHARS[(h1 >> 12) & 0x0F] + HEX_CHARS[(h1 >> 8) & 0x0F] +
            HEX_CHARS[(h1 >> 20) & 0x0F] + HEX_CHARS[(h1 >> 16) & 0x0F] +
            HEX_CHARS[(h1 >> 28) & 0x0F] + HEX_CHARS[(h1 >> 24) & 0x0F] +
            HEX_CHARS[(h2 >> 4) & 0x0F] + HEX_CHARS[h2 & 0x0F] +
            HEX_CHARS[(h2 >> 12) & 0x0F] + HEX_CHARS[(h2 >> 8) & 0x0F] +
            HEX_CHARS[(h2 >> 20) & 0x0F] + HEX_CHARS[(h2 >> 16) & 0x0F] +
            HEX_CHARS[(h2 >> 28) & 0x0F] + HEX_CHARS[(h2 >> 24) & 0x0F] +
            HEX_CHARS[(h3 >> 4) & 0x0F] + HEX_CHARS[h3 & 0x0F] +
            HEX_CHARS[(h3 >> 12) & 0x0F] + HEX_CHARS[(h3 >> 8) & 0x0F] +
            HEX_CHARS[(h3 >> 20) & 0x0F] + HEX_CHARS[(h3 >> 16) & 0x0F] +
            HEX_CHARS[(h3 >> 28) & 0x0F] + HEX_CHARS[(h3 >> 24) & 0x0F];
    };
    return function(data, length){
        length |= 0;
        if (0 === length) {
            length = data.length;
        }
        var md5 = new Md5();
        md5.update(data, length);
        return md5.hex();
    }
})();