package com.ffan.dop;

import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.util.Base64;
import java.util.HashMap;
import java.util.LinkedHashMap;
import java.util.Map;

/**
 * 解码数据
 */
public class DopDecode {
    /**
     * 错误代码
     */
    private static final int ERROR_SIZE = 1;
    private static final int ERROR_SIGN_CODE = 2;
    public static final int ERROR_DATA = 3;
    private static final int ERROR_MASK = 4;

    /**
     * buffer
     */
    private byte[] buffer;

    /**
     * 读取游标位置
     */
    private int read_pos = 0;

    /**
     * 最大可读的位置
     */
    private int max_pos;

    /**
     * 错误代码
     */
    private int error_code = 0;

    /**
     * 是否解析出头信息
     */
    private boolean is_unpack_head = false;

    /**
     * 数据ID
     */
    private String pid;

    /**
     * 标志位
     */
    private int opt_flag;

    /**
     * 数据签名开始位置
     */
    private int sign_data_pos;

    /**
     * 数据加密开始的位置
     */
    private int mask_data_pos;

    /**
     * 字节序
     */
    private ByteOrder byte_order = ByteOrder.LITTLE_ENDIAN;

    /**
     * 构造函数
     */
    public DopDecode(byte[] byte_arr) {
        this.buffer = byte_arr;
        this.max_pos = byte_arr.length;
    }

    /**
     * 构造函数
     */
    public DopDecode(String base64_str) {
        byte[] byte_arr = Base64.getDecoder().decode(base64_str);
        this.buffer = byte_arr;
        this.max_pos = byte_arr.length;
    }

    /**
     * 可读检查
     */
    private Boolean sizeCheck(int need_size) {
        if (this.max_pos - this.read_pos < need_size) {
            this.error_code = ERROR_SIZE;
            return false;
        }
        return true;
    }

    /**
     * 读取一个有符号字节
     */
    public byte readByte() {
        if (!this.sizeCheck(1)) {
            return 0;
        }
        return this.buffer[this.read_pos++];
    }

    /**
     * 读取无符号字节
     */
    public short readUnsignedByte() {
        if (!this.sizeCheck(1)) {
            return 0;
        }
        return (short) (this.buffer[this.read_pos++] & 0xff);
    }

    /**
     * 读取一个有符号16位int
     */
    public short readShort() {
        if (!this.sizeCheck(2)) {
            return 0;
        }
        byte char_1 = this.buffer[this.read_pos++];
        byte char_2 = this.buffer[this.read_pos++];
        short result;
        if (ByteOrder.LITTLE_ENDIAN == this.byte_order) {
            result = (short) (((char_2 & 0xff) << 8) | (char_1 & 0xff));
        } else {
            result = (short) (((char_1 & 0xff) << 8) | (char_2 & 0xff));
        }
        return result;
    }

    /**
     * 读取一个有符号16位int
     */
    public int readUnsignedShort() {
        if (!this.sizeCheck(2)) {
            return 0;
        }
        byte char_1 = this.buffer[this.read_pos++];
        byte char_2 = this.buffer[this.read_pos++];
        int result;
        if (ByteOrder.LITTLE_ENDIAN == this.byte_order) {
            result = ((char_2 & 0xff) << 8) | (char_1 & 0xff);
        } else {
            result = ((char_1 & 0xff) << 8) | (char_2 & 0xff);
        }
        return result;
    }

    /**
     * 读取32位有符号int
     */
    public int readInt() {
        if (!this.sizeCheck(4)) {
            return 0;
        }
        byte char_1 = this.buffer[this.read_pos++];
        byte char_2 = this.buffer[this.read_pos++];
        byte char_3 = this.buffer[this.read_pos++];
        byte char_4 = this.buffer[this.read_pos++];
        int result;
        if (ByteOrder.LITTLE_ENDIAN == this.byte_order) {
            result = ((char_4 & 0xff) << 24) | ((char_3 & 0xff) << 16) |
                    ((char_2 & 0xff) << 8) | (char_1 & 0xff);
        } else {
            result = ((char_1 & 0xff) << 24) | ((char_2 & 0xff) << 16) |
                    ((char_3 & 0xff) << 8) | (char_4 & 0xff);
        }
        return result;
    }

    /**
     * 读取32位无符号int
     */
    public long readUnsignedInt() {
        if (!this.sizeCheck(4)) {
            return 0;
        }
        byte char_1 = this.buffer[this.read_pos++];
        byte char_2 = this.buffer[this.read_pos++];
        byte char_3 = this.buffer[this.read_pos++];
        byte char_4 = this.buffer[this.read_pos++];
        long result;
        if (ByteOrder.LITTLE_ENDIAN == this.byte_order) {
            result = (((long) char_4 & 0xff) << 24) | (long) ((char_3 & 0xff) << 16) |
                    (long) ((char_2 & 0xff) << 8) | (long) (char_1 & 0xff);
        } else {
            result = (((long) char_1 & 0xff) << 24) | ((char_2 & 0xff) << 16) |
                    ((char_3 & 0xff) << 8) | (char_4 & 0xff);
        }
        return result;
    }

    /**
     * 读64位有符号int
     */
    public long readBigInt() {
        long l_1 = this.readUnsignedInt();
        long l_2 = this.readUnsignedInt();
        if (ByteOrder.LITTLE_ENDIAN == this.byte_order) {
            return (l_2 << 32) | l_1;
        } else {
            return (l_1 << 32) | l_2;
        }
    }

    /**
     * 读出float
     */
    public float readFloat() {
        byte[] tmp_byte = this.readByteArray(4);
        if (null == tmp_byte) {
            return 0.0F;
        }
        ByteBuffer buf = ByteBuffer.wrap(tmp_byte);
        buf.order(this.byte_order);
        return buf.getFloat();
    }

    /**
     * 读出double
     */
    public double readDouble() {
        byte[] tmp_byte = this.readByteArray(8);
        if (null == tmp_byte) {
            return 0.0;
        }
        ByteBuffer buf = ByteBuffer.wrap(tmp_byte);
        buf.order(this.byte_order);
        return buf.getDouble();
    }

    /**
     * 读出一个byte[]
     */
    private byte[] readByteArray(int size) {
        if (size <= 0) {
            return new byte[0];
        }
        if (!this.sizeCheck(size)) {
            return null;
        }
        byte[] result = new byte[size];
        System.arraycopy(this.buffer, this.read_pos, result, 0, size);
        this.read_pos += size;
        return result;
    }

    /**
     * 读出一个长度
     */
    private int readLength() {
        short flag = this.readUnsignedByte();
        //长度小于252 直接表示
        if (flag < 0xfc) {
            return flag;
        } //长度小于65535
        else if (0xfc == flag) {
            return this.readUnsignedShort();
        } //长度小于4gb
        else {
            int len = this.readInt();
            if (len < 0) {
                len = 0;
            }
            return len;
        }
    }

    /**
     * 读字符串
     */
    public String readString() {
        int len = this.readLength();
        if (len <= 0) {
            return "";
        }
        byte[] str_byte = this.readByteArray((int) len);
        if (null == str_byte) {
            return "";
        }
        return new String(str_byte);
    }

    /**
     * 解出头信息
     */
    private void unpackHead() {
        if (this.is_unpack_head) {
            return;
        }
        this.is_unpack_head = true;
        this.opt_flag = this.readByte();
        if (0 != (this.opt_flag & DopEncode.OPTION_ENDIAN)) {
            this.byte_order = ByteOrder.BIG_ENDIAN;
        }
        long total_len = this.readLength();
        if (this.max_pos - this.read_pos != total_len) {
            this.error_code = ERROR_SIZE;
            return;
        }
        this.sign_data_pos = this.read_pos;
        if (0 != (this.opt_flag & DopEncode.OPTION_PID)) {
            this.pid = this.readString();
        }
        this.mask_data_pos = this.read_pos;
    }

    /**
     * 数据是否加密
     */
    public boolean isMask() {
        if (!this.is_unpack_head) {
            this.unpackHead();
        }
        return (this.opt_flag & DopEncode.OPTION_MASK) > 0;
    }

    /**
     * 获取PID
     */
    public String getPid() {
        return null == this.pid ? "" : this.pid;
    }

    /**
     * 数据签名校验
     */
    private boolean checkSignCode() {
        //如果剩余数据不够签名串，表示数据出错了
        if (this.max_pos - this.read_pos < DopEncode.SIGN_CODE_LEN) {
            this.error_code = ERROR_DATA;
            return false;
        }
        int sign_code_pos = this.max_pos - DopEncode.SIGN_CODE_LEN;
        byte[] sign_code_byte = new byte[DopEncode.SIGN_CODE_LEN];
        System.arraycopy(this.buffer, sign_code_pos, sign_code_byte, 0, DopEncode.SIGN_CODE_LEN);
        String sign_code = DopEncode.signCode(this.buffer, this.sign_data_pos, sign_code_pos - this.sign_data_pos);
        if (!sign_code.equals(new String(sign_code_byte))) {
            this.error_code = ERROR_SIGN_CODE;
            return false;
        }
        this.max_pos -= DopEncode.SIGN_CODE_LEN;
        this.opt_flag ^= DopEncode.OPTION_SIGN;
        return true;
    }

    /**
     * 数据解密
     */
    private boolean unmask(String mask_key) {
        DopEncode.doMask(this.buffer, this.mask_data_pos, mask_key);
        this.opt_flag ^= DopEncode.OPTION_MASK;
        if (!this.checkSignCode()) {
            this.error_code = ERROR_MASK;
            return false;
        }
        return true;
    }

    /**
     * 解出数据
     */
    public DopStruct unpack() {
        if (!this.is_unpack_head) {
            this.unpackHead();
        }
        if (0 != (this.opt_flag & DopEncode.OPTION_SIGN) && !this.checkSignCode()) {
            return null;
        }
        Map<String, DopProtocol> dop_protocol = this.readProtocol(this.readLength());
        if (null == dop_protocol) {
            return null;
        }
        DopStruct result = this.readProtocolData(dop_protocol);
        if (this.error_code > 0) {
            return null;
        }
        return result;
    }

    /**
     * 解出数据
     */
    public DopStruct unpack(String mask_key) {
        if (this.isMask() && !this.unmask(mask_key)) {
            return null;
        }
        return this.unpack();
    }

    /**
     * 读出协议
     */
    private Map<String, DopProtocol> readProtocol(int length) {
        int end_pos = this.read_pos + length;
        if (end_pos > this.max_pos) {
            return null;
        }
        Map<String, DopProtocol> result = new LinkedHashMap<String, DopProtocol>();
        while (0 == this.error_code && this.read_pos < end_pos) {
            String name = this.readString();
            DopProtocol item = this.readIProtocolItem();
            result.put(name, item);
        }
        if (this.error_code > 0) {
            return null;
        }
        return result;
    }

    /**
     * 读出协议类型
     */
    private DopProtocol readIProtocolItem() {
        byte type = this.readByte();
        DopProtocol item = new DopProtocol();
        item.type = type;
        switch (type) {
            case ItemType.ARR_TYPE:
                item.value_item = this.readIProtocolItem();
                break;
            case ItemType.MAP_TYPE:
                item.key_item = this.readIProtocolItem();
                item.value_item = this.readIProtocolItem();
                break;
            case ItemType.STRUCT_TYPE:
                int sub_protocol_len = this.readLength();
                item.struct = this.readProtocol(sub_protocol_len);
            case ItemType.INT_TYPE_CHAR:
            case ItemType.INT_TYPE_U_CHAR:
            case ItemType.INT_TYPE_SHORT:
            case ItemType.INT_TYPE_U_SHORT:
            case ItemType.INT_TYPE_INT:
            case ItemType.INT_TYPE_U_INT:
            case ItemType.INT_TYPE_BIG_INT:
                item.int_type = type;
                item.type = ItemType.INT_TYPE;
                break;
        }
        return item;
    }

    /**
     * 读出数据
     */
    private DopStruct readProtocolData(Map<String, DopProtocol> protocol_list) {
        DopStruct result = new DopStruct();
        for (Map.Entry<String, DopProtocol> item : protocol_list.entrySet()) {
            String name = item.getKey();
            DopProtocol protocol = item.getValue();
            Item value = this.readItemData(protocol, true);
            if (this.error_code > 0 || null == value) {
                break;
            }
            result.addItem(name, value);
        }
        return result;
    }

    /**
     * 读出一个值
     */
    private Item readItemData(DopProtocol protocol, boolean is_property) {
        byte type = protocol.type;
        switch (type) {
            case ItemType.INT_TYPE:
                return new IntItem(this.readIntItem(protocol.int_type));
            case ItemType.STRING_TYPE:
                return new StringItem(this.readString());
            case ItemType.ARR_TYPE:
                int array_size = this.readLength();
                ArrayItem result = new ArrayItem(array_size);
                DopProtocol sub_item = protocol.value_item;
                for (int i = 0; i < array_size; ++i) {
                    if (this.error_code > 0) {
                        break;
                    }
                    result.add(this.readItemData(sub_item, false));
                }
                return result;
            case ItemType.MAP_TYPE:
                int map_size = this.readLength();
                MapItem map_result = new MapItem(map_size);
                DopProtocol key_item = protocol.key_item;
                DopProtocol value_item = protocol.value_item;
                for (int i = 0; i < map_size; ++i) {
                    if (this.error_code > 0) {
                        break;
                    }
                    map_result.add(this.readItemData(key_item, false), this.readItemData(value_item, false));
                }
                return map_result;
            case ItemType.STRUCT_TYPE:
                //如果是属性，要先读出一个标志位，判断是否为NULL
                if (is_property) {
                    short flag = this.readUnsignedByte();
                    if (0xff != flag) {
                        return new NullItem();
                    }
                }
                DopStruct sub_struct = this.readProtocolData(protocol.struct);
                return new StructItem(sub_struct);
            case ItemType.BINARY_TYPE:
                int len = this.readLength();
                byte[] byte_arr = this.readByteArray(len);
                return new BinaryItem(byte_arr);
            case ItemType.FLOAT_TYPE:
                return new FloatItem(this.readFloat());
            case ItemType.DOUBLE_TYPE:
                return new DoubleItem(this.readDouble());
            case ItemType.BOOL_TYPE:
                boolean value = 0 != this.readByte();
                return new BoolItem(value);
        }
        return null;
    }

    /**
     * 读出int的值
     */
    private long readIntItem(byte int_type) {
        long result = 0;
        switch (int_type) {
            case ItemType.INT_TYPE_CHAR:
                result = this.readByte();
                break;
            case ItemType.INT_TYPE_U_CHAR:
                result = this.readUnsignedByte();
                break;
            case ItemType.INT_TYPE_SHORT:
                result = this.readShort();
                break;
            case ItemType.INT_TYPE_U_SHORT:
                result = this.readUnsignedShort();
                break;
            case ItemType.INT_TYPE_INT:
                result = this.readInt();
                break;
            case ItemType.INT_TYPE_U_INT:
                result = this.readUnsignedInt();
                break;
            case ItemType.INT_TYPE_BIG_INT:
                result = this.readBigInt();
                break;
        }
        return result;
    }

    /**
     * 错误代码
     */
    public int getErrorCode() {
        return this.error_code; 
    }

    /**
     * 获取错误消息
     */
    public String getErrorMessage() {
        if (0 == this.error_code) {
            return "success";
        }
        switch (this.error_code) {
            case ERROR_DATA:
                return "Binary data error";
            case ERROR_MASK:
                return "Data is encrypted";
            case ERROR_SIGN_CODE:
                return "Data signature verification failed";
            case ERROR_SIZE:
                return "Lack of data";
            default:
                return "Unknown error";
        }
    }
}
