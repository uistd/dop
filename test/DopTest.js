const console = require('console');
var dop = require('../coder/js/tpl/dop');
var b64 = dop.base64.encode('Hi. 这里是中国');
console.info(b64);
console.info(dop.base64.decode(b64));
