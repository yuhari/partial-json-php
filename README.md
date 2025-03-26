# partial-json-php
Use PHP to implement code similar to the partial-json library that can be used for parsing incomplete JSON content.

# sample
	<?php
	
    $matcher = function($a, $b) {
	  echo "Expect: \n" ;
	  print_r($b) ;
	  echo "Result: \n" ;
	  print_r($a) ;
	  echo "Matched: " . ($a == $b ? 'Yes' : 'No') . "\n\n" ;
  	} ;

  	$m = new PartialJsonParser() ;

  	$matcher($m->handleStr('{"abv"'), []) ;

  	$matcher($m->handleStr('{"a":"b"'), ['a' => 'b']) ;

  	$matcher($m->handleStr('{"objectA":{"aa":[""], "bb":232,'), ['objectA' => ['aa' => [''], 'bb' => 232]]);

  	$matcher($m->handleStr('{"a":1, "b":"c"}'), ['a' => 1, 'b' => 'c']);

  	$matcher($m->handleStr('[1,2]'), [1, 2]);

  	$matcher($m->handleStr('[1,[2,3],4]'), [1, [2, 3], 4]);

  	$matcher($m->handleStr('[1,[2,[3,4]],5]'), [1, [2, [3, 4]], 5]);

  	$matcher($m->handleStr('[{"a":1},"b",2]'), [['a' => 1], 'b', 2]);

  	$matcher($m->handleStr('{"a":[1,{"b":[2,3]}],"c":4}'), [
    	'a' => [1, ['b' => [2, 3]]],
    	'c' => 4
  	]);

  	$matcher($m->handleStr('{"a":1,"b":[2,'), ['a' => 1, 'b' => [2]]);

  	$matcher($m->handleStr('{"a":"hello\\"world"}'), ['a' => 'hello"world']);

  	$matcher($m->handleStr('[true,false,null,42,3.14,"text"]'), [true, false, null, 42, 3.14, 'text']);
