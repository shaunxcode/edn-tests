edn = require "../../../src/reader"
fs = require "fs"
micro = require "microtime"

testJson = (data) -> 
	now = micro.now() 
	JSON.parse data
	end = micro.now()
	console.log "JSON: #{end - now}"

testEdn = (data) -> 
	now = micro.now()
	edn.parse data
	end = micro.now()
	console.log "EDN: #{end - now}"

for file in ["large-symbol-map", "vector-of-vectors"]
	console.log "COMPARE #{file}"
	testJson fs.readFileSync "./#{file}.json", "utf-8"
	testEdn fs.readFileSync "./#{file}.edn", "utf-8"

