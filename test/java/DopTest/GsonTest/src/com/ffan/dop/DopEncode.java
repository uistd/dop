package com.ffan.dop;

import java.io.IOException;
import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

/**
 * Dop二进制打包
 */
public class DopEncode {
    //数据头标志位
    static final int OPTION_PID = 0x1;
    static final int OPTION_SIGN = 0x2;
    static final int OPTION_MASK = 0x4;
    static final int OPTION_ENDIAN = 0x8;
    //数据签名长度
    static final int SIGN_CODE_LEN = 8;
    //数据加密密钥最小长度
    static final int MIN_MASK_KEY_LEN = 8;

    //错误码
    static final int ERROR_TOO_BIG_DATA = 1;

    /**
     * byte buffer
     */
    private byte[] buffer;

    /**
     * 写入点
     */
    private int write_pos = 0;

    /**
     * 空间大小
     */
    private int max_size;

    /**
     * 标志位
     */
    private byte opt_flag = 0;

    /**
     * 写入的PID占用的长度
     */
    private int mask_beg_pos = 0;

    /**
     * 数据加密key
     */
    private String mask_key;

    /**
     * 是否出错
     */
    private int error_code = 0;

    /**
     * 构造函数
     *
     * @param size 初始分析内存大小
     */
    DopEncode(int size) {
        this.resize(size);
    }

    /**
     * 构造函数
     */
    public DopEncode() {
        this.resize(1024);
    }

    /**
     * 重新分配空间
     *
     * @param size 空间大小
     */
    private void resize(int size) {
        byte[] new_arr = new byte[size];
        if (this.write_pos > 0) {
            System.arraycopy(this.buffer, 0, new_arr, 0, this.write_pos);
        }
        this.max_size = size;
        this.buffer = new_arr;
    }

    /**
     * 判断是否还有足够的空间
     *
     * @param need_size 需要的空间
     */
    private boolean sizeCheck(int need_size) {
        if (this.max_size - this.write_pos >= need_size) {
            return true;
        }
        long new_size = this.max_size * 2;
        //一个 byte[] 最大长度是 0x7fffffff
        if (new_size > Integer.MAX_VALUE) {
            new_size = Integer.MAX_VALUE;
        }
        //数据太大，无法支持
        if (new_size - this.write_pos < need_size) {
            this.error_code = ERROR_TOO_BIG_DATA;
            return false;
        }
        this.resize(this.max_size * 2);
        return true;
    }

    /**
     * 写入一个 char
     */
    public void writeByte(short value) {
        if (!this.sizeCheck(1)) {
            return;
        }
        this.buffer[this.write_pos++] = (byte) (value & 0xff);
    }

    /**
     * 写入一个 char
     */
    public void writeByte(byte value) {
        if (!this.sizeCheck(1)) {
            return;
        }
        this.buffer[this.write_pos++] = value;
    }

    /**
     * 写入一个short
     */
    public void writeShort(short value) {
        if (!this.sizeCheck(2)) {
            return;
        }
        this.buffer[this.write_pos++] = (byte) (value & 0xff);
        this.buffer[this.write_pos++] = (byte) ((value >> 8) & 0xff);
    }

    /**
     * 写入一个short 可写入无符号 16位 int
     */
    public void writeShort(int value) {
        this.writeShort((short) (value & 0xffff));
    }

    /**
     * 写入一个int值
     */
    public void writeInt(int value) {
        if (!this.sizeCheck(4)) {
            return;
        }
        this.buffer[this.write_pos++] = (byte) (value & 0xff);
        this.buffer[this.write_pos++] = (byte) ((value >> 8) & 0xff);
        this.buffer[this.write_pos++] = (byte) ((value >> 16) & 0xff);
        this.buffer[this.write_pos++] = (byte) ((value >> 24) & 0xff);
    }

    /**
     * 写入一个int值（可写入32位无符号数）
     */
    public void writeInt(long value) {
        this.writeInt((int) (value & 0xffffffffL));
    }

    /**
     * 写入64位int
     */
    public void writeBigInt(long value) {
        this.writeInt((int) (value & 0xffffffffL));
        this.writeInt((int) ((value >> 32) & 0xffffffffL));
    }

    /**
     * 写入float
     */
    public void writeFloat(float value) {
        byte[] byte_arr = new byte[4];
        ByteBuffer buf = ByteBuffer.wrap(byte_arr);
        buf.order(ByteOrder.LITTLE_ENDIAN);
        buf.putFloat(value);
        this.writeByteArray(byte_arr);
    }

    /**
     * 写入double
     */
    public void writeDouble(double value) {
        byte[] byte_arr = new byte[8];
        ByteBuffer buf = ByteBuffer.wrap(byte_arr);
        buf.order(ByteOrder.LITTLE_ENDIAN);
        buf.putDouble(value);
        this.writeByteArray(byte_arr);
    }

    /**
     * 写入一个byte[]
     */
    public void writeByteArray(byte[] byte_arr) {
        if (!this.sizeCheck(byte_arr.length)) {
            return;
        }
        System.arraycopy(byte_arr, 0, this.buffer, this.write_pos, byte_arr.length);
        this.write_pos += byte_arr.length;
    }

    /**
     * 写入一个byte[]，带长度写入判断
     */
    public void writeByteArray(byte[] byte_arr, boolean write_len) {
        int length = byte_arr.length;
        if (write_len) {
            this.writeLength(length);
        }
        if (0 == length) {
            return;
        }
        if (!this.sizeCheck(length)) {
            return;
        }
        System.arraycopy(byte_arr, 0, this.buffer, this.write_pos, byte_arr.length);
        this.write_pos += byte_arr.length;
    }

    /**
     * 写入长度
     */
    public void writeLength(int length) {
        //如果长度小于252 表示真实的长度
        if (length < 0xfc) {
            this.writeByte((short) length);
        }
        //如果长度小于等于65535，先写入 0xfc，后面再写入两位表示字符串长度
        else if (length < 0xffff) {
            this.writeByte((short) 0xfc);
            this.writeShort(length);
        } else {
            this.writeByte((short) 0xfe);
            this.writeInt(length);
        }
    }

    /**
     * 写入字符串
     */
    public void writeString(String str) {
        byte[] str_byte = str.getBytes();
        this.writeLength(str_byte.length);
        this.writeByteArray(str_byte);
    }

    /**
     * 写入数据ID
     */
    public void writePid(String pid) {
        this.opt_flag |= DopEncode.OPTION_PID;
        this.writeString(pid);
        this.mask_beg_pos = this.write_pos;
    }

    /**
     * 设置数据加密
     */
    public void mask(String mask_key) {
        this.mask_key = mask_key;
        this.opt_flag |= OPTION_MASK;
        this.sign();
    }

    /**
     * 设置数据签名
     */
    public void sign() {
        this.opt_flag |= OPTION_SIGN;
    }

    /**
     * 返回打包好的数据
     * @return byte[]
     */
    public byte[] pack() {
        if (0 != (this.opt_flag & OPTION_SIGN)) {
            String signCode = signCode(this.buffer, 0, this.write_pos);
            this.writeByteArray(signCode.getBytes());
        }
        if (0 != (this.opt_flag & OPTION_MASK)) {
            doMask(this.buffer, this.mask_beg_pos, this.mask_key);
        }
        int current_len = this.write_pos;
        this.writeLength(this.write_pos);
        if (this.error_code > 0) {
            return new byte[0];
        }
        //+1，因为第1位是标志位
        byte[] result = new byte[this.write_pos + 1];
        result[0] = this.opt_flag;
        int length_len = this.write_pos - current_len;
        System.arraycopy(this.buffer, current_len, result, 1, length_len);
        System.arraycopy(this.buffer, 0, result, 1 + length_len, current_len);
        return result;
    }

    /**
     * 获取byte[]
     */
    public byte[] getBuffer() {
        byte[] result = new byte[this.write_pos];
        System.arraycopy(this.buffer, 0, result, 0, this.write_pos);
        return result;
    }

    /**
     * 生成数据签名
     */
    static String signCode(byte[] byte_arr, int begin, int length) {
        String md5_str;
        //需要复制一份新的出来
        if (length != byte_arr.length) {
            byte[] sub_byte = new byte[length];
            System.arraycopy(byte_arr, begin, sub_byte, 0, length);
            md5_str = md5(sub_byte);
        } else {
            md5_str = md5(byte_arr);
        }
        return md5_str.substring(0, SIGN_CODE_LEN);
    }

    /**
     * 数据加密
     */
    static void doMask(byte[] byte_arr, int begin_pos, String mask_key) {
        byte[] mask_key_arr = fixMaskKey(mask_key).getBytes();
        int pos = 0, key_ken = mask_key_arr.length;
        for (int i = begin_pos, len = byte_arr.length; i < len; ++i) {
            int index = pos++ % key_ken;
            byte_arr[i] ^= mask_key_arr[index];
        }
    }

    /**
     * 修正mask key
     */
    private static String fixMaskKey(String mask_key) {
        return (mask_key.length() < MIN_MASK_KEY_LEN) ? md5(mask_key) : mask_key;
    }

    /**
     * hexArr
     */
    private final static char[] hex_array = "0123456789abcdef".toCharArray();

    /**
     * md5加密
     *
     * @return hex string
     */
    private static String md5(String str) {
        return md5(str.getBytes());
    }

    /**
     * md5加密
     *
     * @return hex string
     */
    public static String md5(byte[] byte_arr) {
        try {
            MessageDigest msgDigest = MessageDigest.getInstance("MD5");
            byte[] dig_arr = msgDigest.digest(byte_arr);
            char[] hex_chars = new char[dig_arr.length * 2];
            for (int j = 0; j < dig_arr.length; j++) {
                int v = dig_arr[j] & 0xff;
                hex_chars[j * 2] = hex_array[v >>> 4];
                hex_chars[j * 2 + 1] = hex_array[v & 0x0f];
            }
            return new String(hex_chars);
        } catch (NoSuchAlgorithmException e) {
            return "NO_MD5_ALGORITHM";
        }
    }

    /**
     * 获取错误代码
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
            case ERROR_TOO_BIG_DATA:
                return "Dop binary does not support more than 2GB data";
            default:
                return "Unknown error";
        }
    }
}
