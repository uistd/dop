public class ByteTest {
    public static void main(String[] args) {
        String string = "中文";
        byte[] by = string.getBytes();
        System.out.println("byte len" + by.length);
        String str = new String(by);
        System.out.println("str="+str);
    }
}