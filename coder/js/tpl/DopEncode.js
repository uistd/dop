'use strict';
var dopBase = require('./dop');

//固定值
var LITTLE_ENDIAN = 0, BIG_ENDIAN = 1, DEFAULT_SIZE = 1024;

function DopEncode(size) {
    size |= 0;
    this.buffer = new Uint8Array(size || DEFAULT_SIZE);
    this.max_size = DEFAULT_SIZE;
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
     * 字节序
     */
    endian: LITTLE_ENDIAN,

    /**
     * 写入的pid占据的长度
     */
    pid_pos: 0,

    /**
     * 数据加密的key
     */
    mask_key: '',

    /**
     * 内存扩展容量
     * @param {int} new_size
     */
    resize: function (new_size) {
        var new_arr = new Uint8Array(new_size);
        for (var i = 0; i < this.max_size; ++i) {
            new_arr[i] = this.buffer[i];
        }
        this.buffer = new_arr;
        this.max_size = new_size;
    },

    /**
     * 写入数据
     * @param {int}  value
     */
    pushData: function (value) {
        //如果已经最大位置了，扩容先
        if (this.write_pos === this.max_size) {
            this.resize(this.max_size * 2);
        }
        this.buffer[this.write_pos++] = value;
    },

    /**
     * 写入一个字节
     * @param {int} value
     */
    writeChar: function (value) {
        value |= 0;
        value &= 0xff;
        this.pushData(value);
    },

    /**
     * 写入一个16位整数
     * @param {int} value
     */
    writeShort: function (value) {
        value |= 0;
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
        value |= 0;
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
    writeBigInt: function (value) {
        value |= 0;
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
        var len = dopBase.strlen(str);
        this.writeLength(len);
        var buffer = dopBase.strToBin(str);
        this.joinBuffer(buffer);
    },

    /**
     * 写入浮点数
     * @param {number} value
     */
    writeFloat: function (value) {
        value = dopBase.floatVal(value);
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
    writeDouble: function (value) {
        value = dopBase.floatVal(value);
        var float_arr = new Float64Array(1);
        float_arr[0] = value;
        var arr = new Uint8Array(float_arr.buffer);
        for (var i = 0, len = 8; i < len; ++i) {
            this.pushData(arr[i]);
        }
    },

    /**
     * 写入dop的option标志位
     */
    writeDopOptionFlag: function (pid, sign, mask) {
        var opt_flag = 0;
        if (pid) {
            opt_flag |= this.prototype.OPTION_PID;
        }
        if (sign || mask) {
            opt_flag |= this.prototype.OPTION_SIGN;
        }
        if (mask) {
            opt_flag |= this.prototype.OPTION_MASK;
        }
        var buffer = new ArrayBuffer(2);
        new DataView(buffer).setInt16(0, 256, true);
        if (256 !== new Int16Array(buffer)[0]) {
            opt_flag |= this.prototype.OPTION_ENDIAN;
        }
        this.writeChar(opt_flag);
    },

    /**
     * 连接两个buffer
     * @param {DopEncode} buffer
     */
    joinBuffer: function (buffer) {
        for (var i = 0; i < buffer.write_pos; ++i) {
            this.pushData(buffer.buffer[i]);
        }
    },

    /**
     * 写入Uint8Array
     * @param {Uint8Array} arr
     */
    writeUint8Array: function(arr){
        var len = arr.length;
        this.writeLength(len);
        for (var i = 0; i < len; ++i) {
            this.pushData(arr[i]);
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
     */
    getBuffer: function () {
        return this.buffer.slice(0, this.write_pos);
    },

    /**
     * 写入数据ID
     * @param {string} pid
     */
    writePid: function (pid) {
        this.writeString();
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
        var result_buf = new DopEncode();
        result_buf.writeChar(this.opt_flag);
        result_buf.writeLength(this.write_pos);
        if (this.opt_flag & DopEncode.prototype.OPTION_SIGN) {
            var sign_code = this.signCode(this.buffer, this.write_pos);
            var buffer = dopBase.strToBin(sign_code);
            this.joinBuffer(buffer);
        }
        if (this.opt_flag & DopEncode.prototype.OPTION_MASK) {
            this.doMask(this.pid_pos, this.mask_key);
        }
        //字节序判断
        var m = new Uint32Array(1);
        m[0] = 0x12345678;
        var c = new Uint8Array(m.buffer);
        if (0x12 === c[0] && 0x34 === c[1]) {
            this.opt_flag |= DopEncode.prototype.OPTION_ENDIAN;
        }
        var current_pos = this.write_pos;
        this.writeChar(this.opt_flag);
        this.writeLength(current_pos);
        var new_buffer = new DopEncode(this.write_pos);
        new_buffer.writeChar(this.opt_flag);
        new_buffer.writeLength(current_pos);
        for (var i = 0; i < current_pos; ++i) {
            new_buffer.buffer[new_buffer.write_pos++] = this.buffer[i];
        }
        return new_buffer.buffer;
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
     * @param {string} mask_key 数据加密key
     */
    doMask: function (beg_pos, mask_key) {
        var key = this.fixMaskKey(mask_key), pos = 0;
        var key_arr = dopBase.strToBin(key), index;
        for (var i = beg_pos; i < this.write_pos; ++i) {
            index = pos++ % key_arr.write_pos;
            this.buffer[i] ^= key_arr.buffer[index];
        }
    },

    /**
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
