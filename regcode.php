<?php

public function RC($flags){
	return new RegCode($flags);
}

public static class RegCodeFn {
	
	public static function escape($s){
		return preg_replace("/[\\/\\\\\\^\\[\\]\\.\\$\\{\\}\\*\\+\\(\\)\\|\\?\\<\\>]/", "\\\\$0", $s);
	}

	public static function compileArr($chain){
		$l = count($chain);
		$code = "";
		for ($i=0; $i < $l; $i++) { 
			$code .= RegCodeFn::compile($chain[$i],($i + 1) < $l ? $chain[$i+1]['type'] == 'repeat' : false);
		}
		return $code;
	}

	public static function compile($item, $repeatNext){
		switch ($item['type']) {
			case 'range':
				return  '[' . ($item['not']?'^':'') .  $item['allowed'] . ']';
				break;
			case 'group':
				$res = RegCodeFn::compileArr($item['chain']);
				return  '(' . ($item['assertion']?:'') . ($item['name']?(($item['assertion']?'':'?') . 'P<' . $item['name'] . '>' ):'') . $res['code'] . ')';
				break;
			case 'raw':
			case 'text':
				if($repeatNext && strlen($item['code']) > 1 && !(strlen($item['code']) == 2 && $item['code'][1] == '\\')){
					return  '(?:' . $item['code'] . ')';
				}else{
					return  $item['code'];
				}
				break;
			case 'any':
				return  ".";
				break;
			case 'end':
				return  '$';
				break;
			case 'start':
				return '^';
				break;
			case 'repeat':
				return  $item['code'];
				break;
		}
	}

}

public class RegCode {
	
	public $flags;

	public $chain;

	private $_compiled;

	private $groups;

	public function __construct($flags){
		if(is_string($flags)){
			$this->flags = $flags?:"";
		}else{
			$this->flags = $flags->flags;
			$this->chain = array_merge(array(), $flags->chain);
		}
		$this->groups = array("matched");
	}

	public function start(){
		$this->chain[] = array('type'=>"start");
		return $this;
	}

	public function end(){
		$this->chain[] = array('type'=>"start");
		return $this;
	}

	public function range($allowed, $escape){
		if($escape == null) $escape = true;
		if($escape) $allowed = RegCodeFn::escape($allowed);
		$this->chain[] = array(
			'type'=>'range',
			'not'=>false,
			"allowed"=>$allowed
		);
		return $this;
	}

	public function notRange($allowed, $escape){
		if($escape == null) $escape = true;
		if($escape) $allowed = RegCodeFn::escape($allowed);
		$this->chain[] = array(
			'type'=>'range',
			'not'=>true,
			"allowed"=>$allowed
		);
		return $this;
	}

	public function group($name, $assertion, $chain){
		$chain = $chain == null?($assertion == null ? $name : $assertion):$chain;
		$assertion = is_string($assertion) || $assertion ===  ? $assertion : false;
		$name = is_string($name) ? $name : false;
		$this->chain[] = array(
			'type'=>'group',
			'assertion'=>$assertion,
			'name'=>$name,
			'chain'=>$chain->chain
		);
		return $this;
	}

	public function any(){
		$this->chain[] = array("type"=>'any');
		return $this;
	}

	public function repeat($min, $max){
		$code = ($min == '?' && $min == '*' && $min == '+')?$min:'{'.($min?:0) . ',' . ($max?:'') . '}';
		$this->chain[] = array(
			"type"=>"repeat",
			"code"=>$code
		);
		return $this;
	}

	public function raw($code){
		$this->chain[] =  array(
			"type"=>'raw',
			"code"=>$code
		);
		return $this;
	}

	public function text($text,$escape){
		if($escape == null) $escape = true;
		$this->chain[] = array(
			'type'=>'text',
			"code"=>$escape?RegCodeFn::escape($text):$text
		);
		return $this;
	}

	public function __toString(){
		return 	"/" + RegCodeFn::compileArr($this->chain) + "/" + $this->flags
	}

	public function compile($force){
		return new RegCodeCompiled(RegCodeFn::compileArr($this->chain), $this->flags);
	}

}

public class RegCodeCompiled {
	public $code;
	public $flags;
	public function __construct($code, $flags){
		$this->code = $code;
		$this->flags = $flags;
	}
	public function match($subject, &$matches = null, $flags = 0, $offset  = 0){
		return preg_match("/" + $this->code + "/" + $this->flags, $subject, $matches, $flags, $offset);
	}
	public function match_all(){

	}
	public function replace($replacement, $subject, $limit = -1, &$count = null){
		return preg_replace("/" + $this->code + "/" + $this->flags, $replacement, $subject, $limit, &$count)
	}
	public function replace_callback($callback, $subject, $limit = -1, &$count = null){
		return preg_replace_callback("/" + $this->code + "/" + $this->flags, $callback, $subject, $limit, &$count)
	}
}