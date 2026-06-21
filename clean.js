const fs = require('fs');

let js = fs.readFileSync('/tmp/raw_script.js', 'utf8');

js = js.replace(/<\?= e\(\$user\['role'\]\) \?>/g, "officer");
js = js.replace(/<\?= json_encode\([\s\S]*?\$\w+\)\)\) \?>/g, "[]");
// replace exactly the json_encode block
js = js.replace(/<\?= json_encode.*? \?>/s, "[]");

fs.writeFileSync('/tmp/cleaned.js', js);
