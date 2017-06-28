'use strict';
var dopBase = require('./dop');

//固定值
var DEFAULT_SIZE = 1024, ERROR_OUT_OF_MEMORY = 1;

function DopEncode(size) {
    size |= 0;
    this.checkEndian();
    this.resize(size||DEFAULT_SIZE);
}

DopEncode.prototype = {
    /**
     * 标志位
     */
    OPTION_PID: 0x1,
    OPTION_SIGN: 0x2,
    OPTION_MASK: 0x4,
    OPTION_ENDIAN: 0x8,
    SIGN_CODE_LEN: 8,
    MIN_MASK_KEY_LEN: 8,

    /**
     * DataView
     */
    data_view: null,

    /**
     * Uint8Array
     */
    byte_array: null,

    /**
     * 写入点
     */
    write_pos: 0,

    /**
     * 最大的可写入位置
     */
    max_size: 0,

    /**
     * 标志位
     */
    opt_flag: 0,

    /**
     * 是否小字节序
     */
    is_little_endian: true,

    /**
     * 写入的pid占据的长度
     */
    pid_pos: 0,

    /**
     * 数据加密的key
     */
    mask_key: '',

    /**
     * 错误代码
     */
    error_code: 0,

    /**
     * 字节序判断
     */
    checkEndian: function(){
        var m = new Uint32Array(1);
        m[0] = 0x12345678;
        var c = new Uint8Array(m.buffer);
        this.is_little_endian = (0x12 === c[3] && 0x34 === c[2]);
    },

    /**
     * 内存扩展容量
     * @param {int} new_size
     * @return {boolean}
     */
    resize: function (new_size) {
        var new_arr = new ArrayBuffer(new_size);
        //内存不够
        if (new_arr.byteLength !== new_size) {
            this.error_code = ERROR_OUT_OF_MEMORY;
            return false;
        }
        var new_byte_array = new Uint8Array(new_arr);
        if (this.byte_array) {
            for (var i = 0; i < this.write_pos; ++i) {
                new_byte_array[i] = this.byte_array[i];
            }
        }
        this.buffer = new_arr;
        this.max_size = new_size;
        this.byte_array = new_byte_array;
        this.data_view = new DataView(new_arr);
        return true;
    },

    /**
     * 检查可写入字节数
     * @param {int} need_size
     * @return {boolean}
     */
    checkSize: function (need_size) {
        //如果已经最大位置了，扩容先
        if (this.max_pos - this.write_pos < need_size) {
            return this.resize(this.max_size * 2);
        }
        return true;
    },

    /**
     * 写入一个字节
     * @param {int} value
     */
    writeChar: function (value) {
        if (this.error_code || !this.checkSize(1)) {
            return;
        }
        value |= 0;
        this.data_view.setUint8(this.write_pos, value);
        this.write_pos++;
    },

    /**
     * 写入一个16位整数
     * @param {int} value
     */
    writeShort: function (value) {
        if (this.error_code || !this.checkSize(2)) {
            return;
        }
        value |= 0;
        this.data_view.setUint16(this.write_pos, value, this.is_little_endian);
        this.write_pos += 2;
    },

    /**
     * 写入一个整数
     * @param {int} value
     */
    writeInt: function (value) {
        if (this.error_code || !this.checkSize(4)) {
            return;
        }
        value |= 0;
        this.data_view.setUint32(this.write_pos, value, this.is_little_endian);
        this.write_pos += 4;
    },

    /**
     * 写入一个64位整数
     * 由于JavaScript 不支持64位整数，只能做特殊处理
     * @param {int|string} value
     */
    writeBigInt: function writeBigInt(value) {
        if (this.error_code || !this.checkSize(8)) {
            return;
        }
        //可以传入16进制字符串，表示js不支持的超过0x1fffffffffffff的数字
        if ('string' === typeof value && /^(0x)?[a-f\d]{1,16}$/i.test(value)) {
            value = value.replace(/^0x/i, '');
            var hex_arr = new Uint8Array(8), pos, i;
            //将字符串补齐16位
            for (i = value.length; i < 16; ++i) {
                value = '0' + value;
            }
            for (i = 0; i < 8; ++i) {
                pos = i * 2;
                hex_arr[i] = parseInt(value.charAt(pos) + value.charAt(pos + 1), 16);
            }
            //如果是小字节编码，就要重排一下顺序
            if (this.is_little_endian) {
                hex_arr.reverse();
            }
            this.writeUint8Array(hex_arr, false);
        } else {
            if (value < 0) {
                value = 0xFFFFFFFF + value + 1
            }
            value = parseInt(value, 10).toString(16);
            this.writeBigInt(value);
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
        var buffer = dopBase.strToBin(str);
        this.writeUint8Array(buffer, true);
    },

    /**
     * 写入浮点数
     * @param {number} value
     */
    writeFloat: function (value) {
        if (this.error_code || !this.checkSize(4)) {
            return;
        }
        this.data_view.setFloat32(this.write_pos, value, this.is_little_endian);
        this.write_pos += 4;
    },

    /**
     * 写入64位浮点数
     * @param {number} value
     */
    writeDouble: function (value) {
        if (this.error_code || !this.checkSize(8)) {
            return;
        }
        this.data_view.setFloat64(this.write_pos, value, this.is_little_endian);
        this.write_pos += 8;
    },

    /**
     * 连接两个buffer
     * @param {DopEncode} buffer
     */
    joinBuffer: function (buffer) {
        if (null === buffer.byte_array) {
            return;
        }
        for (var i = 0; i < buffer.write_pos; ++i) {
            this.writeChar(buffer.byte_array[i]);
        }
    },

    /**
     * 写入Uint8Array
     * @param {Uint8Array} arr
     * @param {boolean} len_head 是否要先写入长度head
     */
    writeUint8Array: function(arr, len_head){
        if (!(arr instanceof Uint8Array)) {
            return;
        }
        var len = arr.length;
        if (len_head) {
            this.writeLength(len);
        }
        if (this.error_code || !this.checkSize(len)) {
            return;
        }
        for (var i = 0; i < len; ++i) {
            this.byte_array[this.write_pos++] = arr[i];
        }
    },

    /**
     * 写入长度表示
     * @param {int} len
     */
    writeLength: function (len) {
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
     * @return Uint8Array
     */
    dumpUint8Array: function () {
        if (null === this.byte_array) {
            return new Uint8Array(0);
        }
        return this.byte_array.slice(0, this.write_pos);
    },

    /**
     * 写入数据ID
     * @param {string} pid
     */
    writePid: function (pid) {
        this.writeString(pid);
        this.pid_pos = this.write_pos;
        this.opt_flag |= DopEncode.prototype.OPTION_PID;
    },

    /**
     * 设置数据加密标志
     * @param {string} mask_key
     */
    mask: function (mask_key) {
        this.opt_flag |= DopEncode.prototype.OPTION_MASK;
        this.mask_key = mask_key;
    },

    /**
     * 设置数据签名标志
     */
    sign: function () {
        this.opt_flag |= DopEncode.prototype.OPTION_SIGN;
    },

    /**
     * 返回最终结果
     * @return Uint8Array
     */
    pack: function () {
        if (this.opt_flag & DopEncode.prototype.OPTION_SIGN) {
            var sign_code = this.signCode(this.byte_array, this.write_pos);
            var buffer = dopBase.strToBin(sign_code);
            this.writeUint8Array(buffer, false);
        }
        if (this.opt_flag & DopEncode.prototype.OPTION_MASK) {
            this.doMask(this.pid_pos, this.write_pos, this.mask_key);
        }
        if (!this.is_little_endian) {
            this.opt_flag |= DopEncode.prototype.OPTION_ENDIAN;
        }
        var current_pos = this.write_pos;
        this.writeChar(this.opt_flag);
        this.writeLength(current_pos);
        var new_buffer = new DopEncode(this.write_pos);
        //内在不够的情况
        if (new_buffer.error_code) {
            return new Uint8Array(0);
        }
        new_buffer.writeChar(this.opt_flag);
        new_buffer.writeLength(current_pos);
        for (var i = 0; i < current_pos; ++i) {
            new_buffer.byte_array[new_buffer.write_pos++] = this.byte_array[i];
        }
        return new_buffer.byte_array;
    },

    /**
     * 生成签名串
     * @param {Uint8Array} bin_arr
     * @param {int} length
     */
    signCode: function (bin_arr, length) {
        var md5_str = dopBase.md5(bin_arr, length);
        return md5_str.substr(0, DopEncode.prototype.SIGN_CODE_LEN);

    },

    /**
     * 数据加密
     * @param {int} beg_pos 数据开始位置
     * @param {int} end_pos 结束位置
     * @param {string} mask_key 数据加密key
     */
    doMask: function (beg_pos, end_pos, mask_key) {
        var key = this.fixMaskKey(mask_key), pos = 0;
        var key_arr = dopBase.strToBin(key), index;
        for (var i = beg_pos; i < end_pos; ++i) {
            index = pos++ % key_arr.length;
            this.byte_array[i] ^= key_arr[index];
        }
    },

    /**
     * mask key处理
     * @param {string} key
     */
    fixMaskKey: function (key) {
        if ('string' !== typeof key) {
            key = String(key);
        }
        if (key.length < DopEncode.prototype.MIN_MASK_KEY_LEN) {
            key = dopBase.md5(key);
        }
        return key;
    }

};
module.exports = DopEncode;
