define(function (require, exports, module) {
    var TextCoder = {
        /**
         * 字符串 to Uint8Array
         * @param {string} str
         * @return BinaryBuffer
         */
        encode: function (str) {
            if ('string' !== typeof str) {
                str = '';
            }
            var buffer = new BinaryBuffer();
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
        },
        /**
         * Uint8Array to 字符串
         * @param arr
         */
        decode: function (arr) {
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
        },
        /**
         * 返回字符串长度
         * @param {string} str
         * @return {int}
         */
        len: function (str) {
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
        }
    };

    /**
     * 强转float
     * @param {*} value
     * @return {number}
     */
    function floatVal(value)
    {
        if ('number' === typeof value){
            return value;
        }
        var re = parseFloat(value);
        if (isNaN(re)) {
            re = 0.0;
        }
        return re;
    }
    
    var BIG_ENDIAN = 1, LITTLE_ENDIAN = 2, DEFAULT_SIZE = 1024;

    function BinaryBuffer(raw_data) {
        //如果传入了默认的buffer
        if ('[object Uint8Array]' === Object.prototype.toString.call(raw_data)) {
            this.buffer = raw_data;
            this.max_pos = this.write_pos = raw_data.byteLength;
        } else {
            this.buffer = new Uint8Array(DEFAULT_SIZE);
            this.max_pos = DEFAULT_SIZE;
        }
    }

    BinaryBuffer.prototype = {
        /**
         * 写入点
         */
        write_pos: 0,

        /**
         * 读数据点
         */
        read_pos: 0,

        /**
         * 最大的可写入位置
         */
        max_pos: 0,

        /**
         * 错误码
         */
        error_code: 0,

        /**
         * 字节顺序
         */
        endian: LITTLE_ENDIAN,

        /**
         * 写入一个值
         * @param {int} v
         */
        pushData: function (v) {
            //如果已经最大位置了，扩容先
            if (this.write_pos === this.max_pos) {
                this.max_pos *= 2;
                var new_arr = new Uint8Array(this.max_pos);
                new_arr.set(this.buffer);
                this.buffer = new_arr;
            }
            this.buffer[this.write_pos++] = v;
        },

        /**
         * 写入一个字节
         * @param {int} value
         */
        writeChar: function (value) {
            if (+value !== value) {
                value |= 0;
            }
            value &= 0xff;
            this.pushData(value);
        },

        /**
         * 写入一个16位整数
         * @param {int} value
         */
        writeShort: function (value) {
            if (+value !== value) {
                value |= 0;
            }
            var h = (value >> 8) & 0xff;
            var l = value & 0xff;
            if (BIG_ENDIAN === this.endian) {
                this.pushData(h);
                this.pushData(l);
            } else {
                this.pushData(l);
                this.pushData(h);
            }
        },

        /**
         * 写入一个整数
         * @param {int} value
         */
        writeInt: function (value) {
            if (+value !== value) {
                value |= 0;
            }
            var h = (value >> 16) & 0xffff;
            var l = value & 0xffff;
            if (BIG_ENDIAN === this.endian) {
                this.writeShort(h);
                this.writeShort(l);
            } else {
                this.writeShort(l);
                this.writeShort(h);
            }
        },

        /**
         * 写入一个64位整数
         * @param {int} value
         */
        writeBigInt: function(value){
            if (+value !== value) {
                value |= 0;
            }
            var h = (value >> 32) & 0xffffffff;
            var l = value & 0xffffffff;
            if (BIG_ENDIAN === this.endian) {
                this.writeInt(h);
                this.writeInt(l);
            } else {
                this.writeInt(l);
                this.writeInt(h);
            }
        },
        
        /**
         * 写入UTF-8字符串
         * @param {string} str
         */
        writeString: function (str) {
            if ('string' !== typeof str) {
                str = '';
            }
            //字符串先写入2字节代表长度
            var len = TextCoder.len(str);
            this.writeLength(len);
            var buffer = TextCoder.encode(str);
            this.joinBuffer(buffer);
        },

        /**
         * 写入浮点数
         * @param {number} value
         */
        writeFloat: function(value) {
            value = floatVal(value);
            var float_arr = new Float32Array(1);
            float_arr[0] = value;
            var arr = new Uint8Array(float_arr.buffer);
            for (var i = 0, len = 4; i < len; ++i) {
                this.pushData(arr[i]);
            }
        },

        /**
         * 写入64位浮点数
         * @param {number} value
         */
        writeDouble: function(value){
            value = floatVal(value);
            var float_arr = new Float64Array(1);
            float_arr[0] = value;
            var arr = new Uint8Array(float_arr.buffer);
            for (var i = 0, len = 8; i < len; ++i) {
                this.pushData(arr[i]);
            }
        },
        
        /**
         * 连接两个BinaryBuffer
         * @param {BinaryBuffer} buffer
         */
        joinBuffer: function(buffer){
            var arr = buffer.getByteArray();
            for (var i = 0, len = arr.length; i < len; ++i) {
                this.pushData(arr[i]);
            }
        },
        
        /**
         * 写入长度表示
         * @param {int} len
         */
        writeLength: function(len){
            //如果长度小于252 表示真实的长度
            if (len < 0xfc) {
                this.writeChar(len);
            } //如果长度小于等于65535，先写入 0xfc，后面再写入两位表示字符串长度
            else if (len < 0xffff) {
                this.writeChar(0xfc);
                this.writeShort(len);
            } //如果长度小于等于4GB，先写入 0xfe，后面再写入两位表示字符串长度
            else if (len <= 0xffffffff) {
                this.writeChar(0xfe);
                this.writeInt(len);
            } //更大
            else {
                this.writeChar(0xff);
                this.writeBigInt(len);
            }
        },

        /**
         * 获取ByteArray
         */
        getByteArray: function(){
            return this.buffer.slice(0, this.write_pos);
        }
    };
    module.exports = BinaryBuffer;
});
