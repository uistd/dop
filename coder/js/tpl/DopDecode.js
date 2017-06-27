'use strict';
var dopBase = require('./dop');
var DopEncode = require('./DopEncode');
//固定值
var BIG_ENDIAN = 1, LITTLE_ENDIAN = 2;
var ERROR_SIZE = 1, ERROR_SIGN_CODE = 2, ERROR_DATA = 3, ERROR_MASK = 4;

function DopDecode(buffer) {
    if ('string' === typeof buffer) {
        buffer = dopBase.base64.decode(buffer);
    }
    if ('[object Uint8Array]' !== Object.prototype.toString.call(buffer)) {
        buffer = new Uint8Array(0);
    }
    this.byte_array = buffer;
    this.max_pos = buffer.byteLength;
    this.data_view = new DataView(this.byte_array.byte_array);
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
     * 数据ID
     */
    pid: '',

    /**
     * 是否解析协议头
     */
    is_unpack_head: false,

    /**
     * 标志位
     */
    opt_flag: 0,

    /**
     * 数据签名开始的位置
     */
    sign_data_pos: 0,

    /**
     * 数据加密开始的位置
     */
    mask_data_pos: 0,

    /**
     * 是否是小字节序
     */
    is_little_endian: true,

    /**
     * 长度检查
     * @param {int} size
     */
    size_check: function(size){
        if (this.max_pos - this.read_pos < size) {
            this.error_code = ERROR_SIZE;
            return false;
        }
        return true;
    },
    
    /**
     * 读取一个有符号字节
     * @return {int}
     */
    readChar: function () {
        if(!this.size_check(1)) {
            return 0;
        }
        var result = this.data_view.getInt8(this.read_pos);
        this.read_pos++;
        return result;
    },

    /**
     * 读取一个无符号的字节
     * @return {int}
     */
    readUnsignedChar: function () {
        if(!this.size_check(1)) {
            return 0;
        }
        var result = this.data_view.getUint8(this.read_pos);
        this.read_pos++;
        return result;
    },

    /**
     * 读取带符号两个字节数字
     * @return {int}
     */
    readShort: function () {
        if(!this.size_check(2)) {
            return 0;
        }
        var result = this.data_view.getInt16(this.read_pos, this.is_little_endian);
        this.read_pos += 2;
        return result;
    },

    /**
     * 读取无符号两个字节数字
     * @return {int}
     */
    readUnsignedShort: function () {
        if(!this.size_check(2)) {
            return 0;
        }
        var result = this.data_view.getUint16(this.read_pos, this.is_little_endian);
        this.read_pos += 2;
        return result;
    },

    /**
     * 读取有符号int类型
     * @return {int}
     */
    readInt: function () {
        if(!this.size_check(4)) {
            return 0;
        }
        var result = this.data_view.getInt32(this.read_pos, this.is_little_endian);
        this.read_pos += 4;
        return result;
    },

    /**
     * 获取无符号int类型
     * @return {int}
     */
    readUnsignedInt: function () {
        if(!this.size_check(4)) {
            return 0;
        }
        var result = this.data_view.getUint32(this.read_pos, this.is_little_endian);
        this.read_pos += 4;
        return result;
    },

    /**
     * 读无符合64位int
     * 因为js对64位int的支持非常不好，暂时只能读出字符串，hex字符串
     * @return {string}
     */
    readBigInt: function () {
        if(!this.size_check(8)) {
            return 0;
        }
        var tmp_arr = new Uint8Array(8);
        for (var i = 0; i < 8; i++) {
            tmp_arr[i] = this.byte_array[this.read_pos++];
        }
        return dopBase.binToHex(tmp_arr);
    },

    /**
     * 读出一个32位符点数
     * @return {number}
     */
    readFloat: function () {
        if(!this.size_check(4)) {
            return 0;
        }
        //float 标准好像没有 高低位 的说法，不知道为什么js的api 还有高低位，暂时全部使用little endian
        var result = this.data_view.getFloat32(this.read_pos, true);
        this.read_pos += 4;
        return result;
    },

    /**
     * 读出一个64位符点数
     * @return {number}
     */
    readDouble: function(){
        if(!this.size_check(8)) {
            return 0;
        }
        var result = this.data_view.getFloat64(this.read_pos, true);
        this.read_pos += 8;
        return result;
    },

    /**
     * 取出一段
     * @param {int} size 长度
     * @return {Uint8Array}
     */
    slice: function(size){
        size |= 0;
        if (size <= 0) {
            this.error_code = ERROR_DATA;
            return this.byte_array;
        }
        //空间不够了
        if (this.max_pos - this.read_pos < size) {
            this.error_code = ERROR_SIZE;
            return this.byte_array;
        }
        var result = this.byte_array.slice(this.read_pos, size);
        this.read_pos += size;
        return result;
    },

    /**
     * 读出一个 Uint8Array
     * @return {Uint8Array}
     */
    readUint8Array: function(){
        var len = this.readLength();
        return this.slice(len);
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
    },

    /**
     * 读出字符串(UTF8)
     * @return {string}
     */
    readString: function(){
        var len = this.readLength();
        if (0 === len || this.error_code) {
            return '';
        }
        var arr = this.slice(len);
        return dopBase.binToStr(arr);
    },

    /**
     * 解析协议头
     */
    unpackHead: function(){
        if (this.is_unpack_head) {
            return;
        }
        this.is_unpack_head = true;
        this.opt_flag = this.readUnsignedChar();
        //字节序判断
        if (this.opt_flag & DopEncode.prototype.OPTION_ENDIAN) {
            this.is_little_endian = false;
        }
        var total_len = this.readLength();
        if (total_len !== this.max_pos - this.read_pos) {
            this.error_code = ERROR_SIZE;
            return;
        }
        //记录真正有效数据开始的位置，用于签名和加密
        this.sign_data_pos = this.read_pos;
        if (this.opt_flag & DopEncode.prototype.OPTION_PID) {
            this.pid = this.readString();
        }
        this.mask_data_pos = this.read_pos;
    },

    /**
     * 数据是否被加密
     * @return {boolean}
     */
    isMask: function(){
        this.unpackHead();
        return (this.opt_flag & DopEncode.prototype.OPTION_MASK) > 0;
    },

    /**
     * 数据签名校验
     * @return {boolean}
     */
    checkSignCode: function(){
        //如果剩余数据不够签名串，表示数据出错了
        if (this.max_pos - this.read_pos < DopEncode.prototype.SIGN_CODE_LEN) {
            this.error_code = ERROR_DATA;
            return false;
        }
        //找出参与签名的数据
        var end_pos = DopEncode.prototype.SIGN_CODE_LEN * -1;
        var sign_buf = this.byte_array.slice(this.byte_array, this.sign_data_pos, end_pos);
        var sign_code = DopEncode.prototype.signCode(sign_buf, sign_buf.length);
        var code = dopBase.binToStr(this.byte_array.slice(end_pos));
        if (sign_code !== code) {
            this.error_code = ERROR_SIGN_CODE;
            return false;
        }
        this.max_pos -= DopEncode.prototype.SIGN_CODE_LEN;
        this.opt_flag ^= DopEncode.prototype.OPTION_SIGN;
        return true;
    },

    /**
     * 数据解密
     * @param {int} beg_pos 数据开始位置
     * @param {string} mask_key 数据加密key
     */
    doMask: DopEncode.prototype.doMask,
    
    /**
     * 数据解密
     * @param {string} mask_key
     * @return {boolean}
     */
    unmask: function(mask_key){
        if (!(this.opt_flag & DopEncode.prototype.OPTION_MASK)) {
            return true;
        }
        this.doMask(this.mask_data_pos, mask_key);
        if (!this.checkSignCode()) {
            this.error_code = ERROR_MASK;
            return false;
        }
        this.opt_flag ^= DopEncode.prototype.OPTION_MASK;
        return true;
    },
    
    /**
     * 解压出数据
     * @return {boolean|Object}
     */
    unpack: function(mask_key){
        this.unpackHead();
        if (this.opt_flag & DopEncode.prototype.OPTION_MASK) {
            if ('string' !== typeof mask_key || 0 === mask_key.length) {
                this.error_code = ERROR_MASK;
            }
            this.unmask(mask_key);
            return false;
        }
        if ((this.opt_flag & DopEncode.prototype.OPTION_SIGN) && !this.checkSignCode()) {
            return false;
        }
        //先解析出协议
        var proto_buf = this.readUint8Array();
        if (this.error_code) {
            return false;
        }
        var protocol_decoder = new DopDecode(proto_buf);
        protocol_decoder.is_little_endian = this.is_little_endian;
        var struct_arr = protocol_decoder.readProtocolStruct();
        //再解出数据
        var result = this.readStructData(struct_arr);
        if (this.error_code) {
            return false;
        }
        return result;
    },

    /**
     * 读出数据
     * @param {object} struct_arr
     * @return {object}
     */
    readStructData: function(struct_arr){
        var result = {}, item;
        for (var name in struct_arr) {
            item = struct_arr[name];
            if (this.error_code > 0) {
                break;
            }
            result[name] = this.readItemData(item, true);
        }
        return result;
    },

    /**
     * 读出一项数据
     * @param {object} item
     * @param {boolean} is_property 是否为类属性
     */
    readItemData: function(item, is_property){
        var item_type = item['type'], value, i, length;
        switch (item_type) {
            case 1: //string
            case 4: //binary
                value = this.readString();
                break;
            case 3: //float
                value = this.readFloat();
                break;
            case 8: //double
                value = this.readDouble();
                break;
            case 5: //list
                length = this.readLength();
                value = [];
                if (length > 0) {
                    var sub_item = item['sub_item'];
                    for (i = 0; i < length; ++i) {
                        if (this.error_code) {
                            break;
                        }
                        value.push(this.readItemData(sub_item, false));
                    }
                }
                break;
            case 7: //map
                length = this.readLength();
                value = {};
                if (length > 0) {
                    var key_item = item['key_item'];
                    var value_item = item['value_item'];
                    for (i = 0; i < length; ++i) {
                        if (this.error_code) {
                            break;
                        }
                        var key = this.readItemData(key_item, false);
                        value[key] = this.readItemData(value_item, false);
                    }
                }
                break;
            case 6: //struct
                //如果是属性，要检查这个struct是否为null
                if (is_property) {
                    var data_flag = this.readUnsignedChar();
                    if (0xff !== data_flag) {
                        value = null;
                        break;
                    }
                }
                var sub_struct = item['sub_struct'];
                value = this.readStructData(sub_struct);
                break;
            default:
                value = this.tryReadInt(item_type);
                break;
        }
        return value;
    },

    /**
     * 尝试读int
     * @param {int} item_type
     * @return int|null
     */
    tryReadInt: function(item_type){
        /*
         0x12 => 'Char',
         0x92 => 'UnsignedChar',
         0x22 => 'Short',
         0xa2 => 'UnsignedShort',
         0x42 => 'Int',
         0xc2 => 'UnsignedInt',
         0x82 => 'Bigint'
         */
        var value;
        switch (item_type) {
            case 0x12:
                value = this.readChar();
                break;
            case 0x92:
                value = this.readUnsignedChar();
                break;
            case 0x22:
                value = this.readShort();
                break;
            case 0xa2:
                value = this.readUnsignedShort();
                break;
            case 0x42:
                value = this.readInt();
                break;
            case 0xc2:
                value = this.readUnsignedInt();
                break;
            case 0x82:
                value = this.readBigInt();
                break;
            default:
                value = null;
                this.error_code = ERROR_DATA;
        }
        return value;
    },

    /**
     * 解析协议
     * @return {object}
     */
    readProtocolStruct: function(){
        var result_arr = {}, item_name, item;
        while (0 === this.error_code && this.read_pos < this.max_pos) {
            item_name = this.readString();
            item = this.readProtocolItem();
            if (this.error_code > 0) {
                break;
            }
            result_arr[item_name] = item;
        }
        return result_arr;
    },

    /**
     * 解析协议 中的字段
     * @return {object}
     */
    readProtocolItem: function(){
        var result = {};
        var item_type = this.readUnsignedChar();
        result['type'] = item_type;
        switch (item_type) {
            case 5: //list
                result['sub_item'] = this.readProtocolItem();
                break;
            case 7: //map
                result['key_item'] = this.readProtocolItem();
                result['value_item'] = this.readProtocolItem();
                break;
            case 6: //struct
                //子struct协议
                var sub_buffer = this.readUint8Array();
                if (this.error_code > 0) {
                    return null;
                }
                var sub_protocol = new DopDecode(sub_buffer);
                sub_protocol.is_little_endian = this.is_little_endian;
                var sub_struct = sub_protocol.readProtocolStruct();
                var err_code = sub_protocol.getErrorCode();
                if (err_code > 0) {
                    this.error_code = err_code;
                } else {
                    result['sub_struct'] = sub_struct;
                }
                break;
        }
        return result;
    },

    /**
     * 获取错误码
     * @return {int}
     */
    getErrorCode: function(){
        return this.error_code;
    }
};
module.exports = DopDecode;