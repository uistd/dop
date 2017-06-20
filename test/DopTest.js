const console = require('console');
var dop = require('../coder/js/tpl/dop');
var b64 = dop.base64.encode('Hi. 这里是中国');
console.info(b64);
console.info(dop.base64.decode(b64));
console.info(dop.md5('Hi dop'));

var arr = new Uint8Array(10);
arr[0] = 10;
arr[1] = 20;
arr[2] = 30;
arr[3] = 40;
arr[4] = 50;
arr[5] = 60;
arr[6] = 70;
arr[7] = 80;
arr[8] = 90;
arr[9] = 100;

console.info(dop.md5(arr));

console.info(dop.base64.encode(arr));
