import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

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
        MessageDigest msgDigest = MessageDigest.getInstance("MD5");
        
        
        System.out.println("ok");    
    }
}
