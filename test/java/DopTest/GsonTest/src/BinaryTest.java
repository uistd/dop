import com.ffan.dop.DopEncode;
import com.ffan.dop.demo.data.TestArr;
import com.ffan.dop.demo.data.TestData;
import com.ffan.dop.demo.data.TestDataStruct;

import java.util.ArrayList;
import java.util.Base64;
import java.util.HashMap;

public class BinaryTest {
    public static void main(String[] var0) {
        TestData data = new TestData();
        data.binary = new byte[0];
        data.int8 = 0x7f;
        data.uint8 = 0xff;
        data.int16 = 0x7fff;
        data.uint16 = 0xffff;
        data.int32 = 0x7fffffff;
        data.uint = 0xffffffffL;
        data.int64 = 0xfffffffffffL;
        data.float32 = 100.1F;
        data.double64 = 1000.1001010;
        data.string = "This is DOP test";
        data.list = new ArrayList<String>();
        data.list.add("this");
        data.list.add("many");

        data.list_list = new ArrayList<ArrayList<Integer>>();
        ArrayList<Integer> sub_list = new ArrayList<Integer>();
        sub_list.add(20);
        sub_list.add(10);
        sub_list.add(40);
        data.list_list.add(sub_list);

        data.map = new HashMap<Integer, String>();
        data.map.put(10, "this is ten");
        data.map.put(1, "this is one");
        data.map.put(2, "this is two");
        data.test_arr = new TestArr();

        data.test_arr.age = 30;
        data.test_arr.name = "bluebird";
        data.test_arr.mobile = "18018684626";

        data.struct = new TestDataStruct();
        data.struct.first_name = "huang";
        data.struct.last_name = "shunzhao";
        data.struct.gender = 1;
        
        byte[] bin_data_1 = data.binaryEncode();
        System.out.println("len:" + bin_data_1.length + " md5:" + DopEncode.md5(bin_data_1));
        
        byte[] bin_data_2 = data.binaryEncode(true);
        System.out.println("len:" + bin_data_2.length + " md5:" + DopEncode.md5(bin_data_2));

        byte[] bin_data_3 = data.binaryEncode(true, true);
        System.out.println("len:" + bin_data_3.length + " md5:" + DopEncode.md5(bin_data_3));

        byte[] bin_data_4 = data.binaryEncode(true, "www.ffan.com");
        System.out.println("len:" + bin_data_4.length + " md5:" + DopEncode.md5(bin_data_4));
        
        TestData data2 = new TestData();
        System.out.println(0 == data2.binaryDecode(bin_data_1) ? "success" : "failed");
        System.out.println(0 == data2.binaryDecode(bin_data_2) ? "success" : "failed");
        System.out.println(0 == data2.binaryDecode(bin_data_3) ? "success" : "failed");
        System.out.println(0 == data2.binaryDecode(bin_data_4, "www.ffan.com") ? "success" : "failed");
    }
}
