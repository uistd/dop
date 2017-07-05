import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.Base64;

public class ByteTest {
    public static void main(String[] var0) throws NoSuchAlgorithmException {
        ByteBuffer test = ByteBuffer.allocate(1024);
        test.order(ByteOrder.LITTLE_ENDIAN);
        test.putShort((short) 0x1234);
        test.putShort((short) 65534);

        test.putInt(0x12345678);
        test.putInt(0xffffffff);
        
        test.putChar((char)0x12);
        test.putChar((char)0xff);
        test.putChar((char)-127);
        test.putChar((char)254);
        
        byte[] arr = new byte[3];
        arr[0] = 0x1;
        arr[1] = 0x2;
        arr[2] = 0x3;
        
        byte[] arr2 = new byte[6];
        System.arraycopy(arr, 0, arr2, 0, 3);

        byte[] arr3 = new byte[10];
        for (int i = 0; i < 10; ++i) {
            arr3[i] = (byte) (190 + i);
        }
        String base64_str = Base64.getEncoder().encodeToString(arr3);
        System.out.println(base64_str);
        System.out.println("ok");    
    }
}
