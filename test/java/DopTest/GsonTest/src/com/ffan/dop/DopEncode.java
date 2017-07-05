package com.ffan.dop;

import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

/**
 * Dop二进制打包
 */
public class DopEncode {
    public static final int OPTION_PID = 0x1;
    public static final int OPTION_SIGN = 0x2;
    public static final int OPTION_MASK = 0x4;
    public static final int OPTION_ENDIAN = 0x8;
    public static final int SIGN_CODE_LEN = 8;
    public static final int MIN_MASK_KEY_LEN = 8;

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
    private int pid_pos = 0;

    /**
     * 数据加密key
     */
    private String mask_key;

    /**
     * 错误码
     */
    int error_code = 0;

    /**
     * 构造函数
     *
     * @param size 初始分析内存大小
     */
    public DopEncode(int size) {
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
    private void sizeCheck(int need_size) {
        if (this.max_size - this.write_pos >= need_size) {
            return;
        }
        this.resize(this.max_size * 2);
    }

    /**
     * 写入一个 char
     */
    public void writeByte(short value) {
        this.sizeCheck(1);
        this.buffer[this.write_pos++] = (byte) (value & 0xff);
    }

    /**
     * 写入一个 char
     */
    public void writeByte(byte value) {
        this.sizeCheck(1);
        this.buffer[this.write_pos++] = value;
    }

    /**
     * 写入一个short
     */
    public void writeShort(short value) {
        this.sizeCheck(2);
        byte h = (byte) ((value >> 8) & 0xff);
        byte l = (byte) (value & 0xff);
        this.buffer[this.write_pos++] = h;
        this.buffer[this.write_pos++] = l;
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
        this.sizeCheck(4);
        this.buffer[this.write_pos++] = (byte) ((value >> 24) & 0xff);
        this.buffer[this.write_pos++] = (byte) ((value >> 16) & 0xff);
        this.buffer[this.write_pos++] = (byte) ((value >> 8) & 0xff);
        this.buffer[this.write_pos++] = (byte) ((value) & 0xff);
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
        this.writeInt((int) ((value >> 32) & 0xffffffffL));
        this.writeInt((int) (value & 0xffffffffL));
    }

    /**
     * 写入长度
     */
    public void writeLength(long length) {
        //如果长度小于252 表示真实的长度
        if (length < 0xfc) {
            this.writeByte((short) length);
        }
        //如果长度小于等于65535，先写入 0xfc，后面再写入两位表示字符串长度
        else if (length < 0xffff) {
            this.writeByte((short) 0xfc);
            this.writeShort((int) length);
        }
        //如果长度小于等于4GB，先写入 0xfe，后面再写入两位表示字符串长度
        else if (length <= 0xffffffffL) {
            this.writeByte((short) 0xfe);
            this.writeInt(length);
        } else {
            this.writeByte((short) 0xff);
            this.writeBigInt(length);
        }
    }

    /**
     * 写入字符串
     */
    public void writeString(String str) {
        int len = str.length();
        this.writeLength(len);
        this.sizeCheck(len);
        byte[] str_byte = str.getBytes();
        System.arraycopy(this.buffer, this.write_pos, str_byte, 0, len);
        this.write_pos += len;
    }

    /**
     * 写入数据ID
     */
    public void writePid(String pid) {
       this.opt_flag |= DopEncode.OPTION_PID;
       this.writeString(pid);
       this.pid_pos = this.write_pos;
    }

    /**
     * 设置数据加密
     */
    public void mask(String mask_key) {
        this.mask_key = mask_key;
        this.opt_flag |= OPTION_MASK;
    }

    /**
     * 设置数据签名
     */
    public void sign() {
        this.opt_flag |= OPTION_SIGN;
    }

    /**
     * 生成数据签名
     */
    private void signData() {
        
    }

    /**
     * 数据加密
     */
    private void doMask() {
        
    }

    /**
     * 修正mask key
     */
    public static String fixMaskKey(String mask_key) {
        return (mask_key.length() < MIN_MASK_KEY_LEN) ? md5(mask_key) : mask_key;
    }

    /**
     * hexArr
     */
    private final static char[] hex_array = "0123456789abcdef".toCharArray();
    
    /**
     * md5加密
     * @return hex string
     */
    public static String md5(String str) {
        try {
            MessageDigest msgDigest = MessageDigest.getInstance("MD5");
            byte[] dig_arr = msgDigest.digest(str.getBytes());
            char[] hex_chars = new char[dig_arr.length * 2];
            for ( int j = 0; j < dig_arr.length; j++ ) {
                int v = dig_arr[j] & 0xff;
                hex_chars[j * 2] = hex_array[v >>> 4];
                hex_chars[j * 2 + 1] = hex_array[v & 0x0f];
            }
            return new String(hex_chars);
        } catch (NoSuchAlgorithmException e) {
            return "NO_MD5_ALGORITHM";
        }
    }
}
