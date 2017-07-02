
public class BasicTest {
    public int a;

    public String b;

    private ByteTest c;

    public void test() {
        if (this.c == null) {
            System.out.println("c: null");
        }
        if (this.b == null) {
            System.out.println("b: null");
        } else {
            System.out.println("b:" + this.a);
        }
        System.out.println(this.testb());
    }

    public String testb()
    {
        return "hello";
    }

    public static void main(String[] var0) {
        BasicTest bt = new BasicTest();
        bt.test();
    }
}
