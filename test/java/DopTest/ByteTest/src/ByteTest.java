import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;

public class ByteTest {
    public static void main(String[] var0){
        BasicTest test = new BasicTest();
        if (null == test.byte_arr) {
            System.out.println("null");
        }
        if (null == test.test_map) {
            System.out.println("null");
        }
        if (null == test.test_object) {
            System.out.println("null");
        }
        if (null == test.test_str) {
            System.out.println("null");
        }
        if (null == test.list_test) {
            System.out.println("null");
        }
        test.list_test = new ArrayList<Integer>();
        test.list_test.add(null);
        test.list_test.add(null);
        test.list_test.add(null);
        test.list_test.add(null);
        if (null != test.list_test) {
            System.out.println("array not null");
        }
        System.out.println(test.list_test.get(0));
        System.out.println(test.list_test.get(1));
        System.out.println(test.list_test.get(2));
        
        test.test_map = new HashMap<>();
        test.test_map.put(null, null);
        test.test_map.put(null, null);
        test.test_map.put(null, null);
        System.out.println("Map test");
        for (Map.Entry<Integer, Integer> item : test.test_map.entrySet()) {
            System.out.println(item.getKey());
            System.out.println(item.getValue());
        }
    }
}
