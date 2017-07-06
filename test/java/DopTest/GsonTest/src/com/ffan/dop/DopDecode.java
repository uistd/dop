package com.ffan.dop;

import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.util.Base64;

/**
 * 解码数据
 */
public class DopDecode {
    /**
     * 错误代码
     */
    private static final int ERROR_SIZE = 1;
    private static final int ERROR_SIGN_CODE = 2;
    private static final int ERROR_DATA = 3;
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
    private ByteOrder order = ByteOrder.LITTLE_ENDIAN;

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
     * 设置字节序
     */
    private void setOrder(ByteOrder order) {
        this.order = order;
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
        if (ByteOrder.LITTLE_ENDIAN == this.order) {
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
        if (ByteOrder.LITTLE_ENDIAN == this.order) {
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
        if (ByteOrder.LITTLE_ENDIAN == this.order) {
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
        if (ByteOrder.LITTLE_ENDIAN == this.order) {
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
        System.out.println(l_1);
        System.out.println(l_2);
        if (ByteOrder.LITTLE_ENDIAN == this.order) {
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
        return buf.getDouble();
    }

    /**
     * 读出一个byte[]
     */
    private byte[] readByteArray(int size) {
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
    private long readLength() {
        short flag = this.readUnsignedByte();
        //长度小于252 直接表示
        if (flag < 0xfc) {
            return flag;
        } //长度小于65535
        else if (0xfc == flag) {
            return this.readUnsignedShort();
        } //长度小于4gb
        else if (0xfe == flag) {
            return this.readUnsignedInt();
        } //更长
        else {
            return this.readBigInt();
        }
    }

    /**
     * 读字符串
     */
    public String readString() {
        long len = this.readLength();
        if (0 == len) {
            return "";
        }
        //@todo long support
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
            this.order = ByteOrder.BIG_ENDIAN;
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
    private boolean unmack(String mask_key) {
        DopEncode.doMask(this.buffer, this.mask_data_pos, mask_key);
        this.opt_flag ^= DopEncode.OPTION_MASK;
        if (!this.checkSignCode()){
            this.error_code = ERROR_MASK;
            return false;
        }
        return true;
    }
}
