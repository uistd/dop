'use strict';
var dopBase = require('./dop');
//固定值
var BIG_ENDIAN = 1, LITTLE_ENDIAN = 2, DEFAULT_SIZE = 1024;
var ERROR_SIZE = 1, ERROR_SIGN_CODE = 2, ERROR_DATA = 3, ERROR_MASK = 4;

function DopDecode(buffer) {
    if ('string' === typeof buffer) {
        buffer = dopBase.base64.decode(buffer);
    }
    if ('[object Uint8Array]' !== Object.prototype.toString.call(buffer)) {
        buffer = new Uint8Array(0);
    }
    this.buffer = buffer;
    this.max_pos = buffer.byteLength;
}

DopDecode.prototype = {
    /**
     * 读数据点
     */
    read_pos: 0,

    /**
     * 最大的可读位置
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
     * 读取一个有符号字节
     * @return {int}
     */
    readChar: function () {
        var re = this.readUnsignedChar();
        //负数还原
        if (re > 0x7f) {
            re = (0xff - re + 1 ) * -1;
        }
        return re;
    },

    /**
     * 读取一个无符号的字节
     * @return {int}
     */
    readUnsignedChar: function () {
        if (this.read_pos >= this.write_pos) {
            this.error_code = ERROR_SIZE;
            return 0;
        }
        return this.buffer[this.read_pos++];
    },

    /**
     * 读取带符号两个字节数字
     * @return {int}
     */
    readShort: function () {
        var re = this.readUnsignedShort();
        if (re > 0x7ffff) {
            re = (0xffff - re + 1 ) * -1;
        }
        return re;
    },

    /**
     * 读取无符号两个字节数字
     * @return {int}
     */
    readUnsignedShort: function () {
        var h, l;
        if (BIG_ENDIAN === this.endian) {
            h = this.readUnsignedChar();
            l = this.readUnsignedChar();
        } else {
            l = this.readUnsignedChar();
            h = this.readUnsignedChar();
        }
        return (h << 8) + l;
    },

    /**
     * 读取有符号int类型
     * @return {int}
     */
    readInt: function () {
        var re = this.readUnsignedInt();
        if (re > 0x7fffffff) {
            re = (0xffffffff - re + 1 ) * -1;
        }
        return re;
    },

    /**
     * 获取无符号int类型
     * @return {int}
     */
    readUnsignedInt: function () {
        var h, l;
        if (BIG_ENDIAN === this.endian) {
            h = this.readUnsignedShort();
            l = this.readUnsignedShort();
        } else {
            l = this.readUnsignedShort();
            h = this.readUnsignedShort();
        }
        return (h << 16) + l;
    },

    /**
     * 读无符合64位int
     * @return {int}
     */
    readBigInt: function () {
        var h, l;
        if (BIG_ENDIAN === this.endian) {
            h = this.readInt();
            l = this.readUnsignedInt();
        } else {
            l = this.readUnsignedInt();
            h = this.readInt();
        }
        return (h << 32) + l;
    },

    /**
     * 读出一个32位符点数
     * @return {number}
     */
    readFloat: function () {

    },

    /**
     * 读取长度
     * @return {int}
     */
    readLength: function () {
        var flag = this.readUnsignedChar();
        //长度小于252 直接表示
        if (flag < 0xfc) {
            return flag;
        } //长度小于65535
        else if (0xfc === flag) {
            return this.readUnsignedShort();
        } //长度小于4gb
        else if (0xfe === flag) {
            return this.readUnsignedInt();
        } //更长
        else {
            return this.readBigInt();
        }
    }
    
};