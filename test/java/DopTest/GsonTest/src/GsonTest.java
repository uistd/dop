import java.util.ArrayList;
import java.util.HashMap;

import com.ffan.dop.DopDecode;
import com.ffan.dop.DopEncode;
import com.ffan.dop.demo.data.SimpleData;
import com.ffan.dop.demo.data.TestArr;
import com.ffan.dop.demo.data.TestData;
import com.ffan.dop.demo.data.TestDataStruct;

public class GsonTest {
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
    	
    	data.string = "www.ffan.com";
    	
    	data.test_arr = new TestArr();
    	
    	data.test_arr.age = 30;
    	data.test_arr.name = "bluebird";
    	data.test_arr.mobile = "18018684626";
    	
    	data.struct = new TestDataStruct();
    	data.struct.first_name = "huang";
    	data.struct.last_name = "shunzhao";
    	data.struct.gender = 1;
    	
    	String json = data.gsonWrite();  	
    	System.out.println(json);
    	
    	TestData new_test = new TestData();
    	Boolean read_re = new_test.gsonRead(json);
    	
    	System.out.println(read_re);
    	
    	SimpleData simple_test = new SimpleData();
    	
    	simple_test.binary = new byte[0];
    	simple_test.int8 = 0x7f;
    	simple_test.uint8 = 0xff;
    	simple_test.int16 = 0x7fff;
    	simple_test.uint16 = 0xffff;
    	simple_test.int32 = 0x7fffffff;
    	simple_test.uint = 0xffffffffL;
    	simple_test.int64 = 0xfffffffffffL;
    	simple_test.float32 = 100.1F;
    	simple_test.double64 = 1000.1001010;
    	simple_test.string = "This is DOP test";
    	simple_test.list = new ArrayList<String>();
    	simple_test.list.add("this");
    	simple_test.list.add("many");
    	
    	simple_test.map = new HashMap<Integer, String>();
    	simple_test.map.put(10, "this is ten");
    	simple_test.map.put(1, "this is one");
    	simple_test.map.put(2, "this is two");
    	
    	String json2 = simple_test.gsonWrite();
    	System.out.println(json2);
    	
    	SimpleData test3 = new SimpleData();
    	Boolean re = test3.gsonRead(json2);
    	
    	System.out.println(re);

		DopEncode encode = new DopEncode();
		encode.writeByte((byte) 0x7f);
		encode.writeByte((short) 0xff);
		encode.writeShort((short) 0x7fff);
		encode.writeShort(0xffff);

		encode.writeInt(0x7fffffff);
		encode.writeInt(0xffffffffL);
		
		encode.writeBigInt(0x7fffffffffffffffL);
		
		encode.writeFloat(123743.13F);
		encode.writeDouble(9293892.929394);
		
		encode.writeString("www.ffan.com");
		encode.writeString("www.飞sss凡.com");
		encode.mask("www.ffan.com");
		encode.pack();
		
		byte[] pack_re = encode.getBuffer();
		DopDecode decode = new DopDecode(pack_re);
		byte re_1 = decode.readByte();
		short re_2 = decode.readUnsignedByte();
		short re_3 = decode.readShort();
		int re_4 = decode.readUnsignedShort();
		
		int re_5 = decode.readInt();
		long re_6 = decode.readUnsignedInt();
		long re_7 = decode.readBigInt();
		
		float re_8 = decode.readFloat();
		double re_9 = decode.readDouble();
		String re_10 = decode.readString();
		String re_11 = decode.readString();
		
		System.out.println(re_1);
		System.out.println(re_2);
		System.out.println(re_3);
		System.out.println(re_4);
		System.out.println(re_5);
		System.out.println(re_6);
		System.out.println(re_7);
		System.out.println(re_8);
    	System.out.println(re_9);
    	System.out.println(re_10);
    	System.out.println(re_11);
    	
    	System.out.println("end");
    }
}