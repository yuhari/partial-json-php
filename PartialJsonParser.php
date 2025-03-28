<?php
/**
 *
 * The content is generated by using TextMate, and edited by yuhari.
 *
 *
 * 实现类似nodejs的partial-json库功能，实现不完整的json字符串解析出可用的数据
 * 支持解析出 object、array、string、number、boolean、null 数据;
 *
 * @author 	   yuhari
 * @maintainer yuhari
 * @version    1.0.0
 * @modified   2025/03/26 12:28:34
 *
 */

class PartialJsonParser {
    // 当前是否处理转义字符状态
    private $flagEscapeNext ;
    // 当前是否处于字符串状态，字符串状态的字符直接拼接
    private $flagInString ;
    // 当前strValue是否是string类型，如果有strKey且strValue为字符串的，就可以直接解析
    private $flagString ;
    // 当前是否有strKey，有strKey场景会先加入object解析中
    private $flagStrKey ;
    // 当前缓存的字符串
    private $strValue ;
    // 当前元素
    private $element ;
	
	private function initFlags() {
		$this->flagEscapeNext = false ;
		$this->flagInString = false ;
		$this->flagString = false ;
		$this->flagStrKey = false ;
		$this->strValue = '' ;
		$this->element = null ;
	}

    public function handleStr($str) {
		$this->initFlags() ;
		
        $root = new Element(null, 'root') ;
        $this->element = $root ;
        foreach (str_split($str) as $char) {
            $this->appendChar($char) ;
        }

        return $root->output() ;
    }

    public function appendChar($char) {
        // 转义状态的字符直接拼接
        if ($this->flagEscapeNext) {
            $this->flagEscapeNext = false ;
            $this->strValue .= $char ;
            return true ;
        }
        // 转义下一个字符
        if ($char == '\\') {
            $this->flagEscapeNext = true ;
            return true ;
        }
        // 遇到双引号触发字符串状态变化
        if ($char == '"') {
            if ($this->flagInString) $this->flagString = true ;

            // 这里支持有key的情况下，也触发值解析
            if ($this->flagInString && $this->flagStrKey) {
                $this->endValue() ;
            }
            $this->flagInString = !$this->flagInString ;
            return true ;
        }
        // 字符串状态直接拼接
        if ($this->flagInString) {
            $this->strValue .= $char ;
            return true ;
        }
        // 遇到空格直接跳过
        if (ctype_space($char)) {
            return true ;
        }

        switch($char) {
            case ':' :
                if ($this->element) {
                    $this->flagStrKey = true ;
                    $this->element->addKey($this->strValue) ;
                    $this->strValue = '' ;
                }
                break ;
            case '{':
                $this->flagStrKey = false ;
                $ele = New Element($this->element, 'object') ;
                $this->element->addElement($ele) ;
                $this->element = $ele ;
                break ;
            case '}': 
                if ($this->element) {
                    $this->endValue() ;
                    $this->element->setComplete() ;
                    $this->strValue = '' ;
                    $ele = $this->element->getParent() ;
                    $this->element = $ele ;
                }
                break ; 
            case '[':
                $this->flagStrKey = false ;
                $ele = New Element($this->element, 'array') ;
                $this->element->addElement($ele) ;
                $this->element = $ele ;
                break ;
            case ']':
                if ($this->element) {
                    $this->endValue() ;
                    $this->element->setComplete() ;
                    $this->strValue = '' ;
                    $ele = $this->element->getParent() ;
                    $this->element = $ele ;
                }
                break ;
            case ',':
                $this->flagStrKey = false ;
                $this->endValue() ;
                break ;
            default:
                $this->strValue .= $char ;
                break ;
        }
        $this->flagString = false ;
    }

    // 结束一个值的字符(",|]|}")触发解析
    public function endValue() {
        if ($this->strValue != '' || $this->flagString) {
            $ele = New Element($this->element, $this->flagString ? 'string' : 'number') ;
            $ele->addValue($this->strValue) ;
            $ele->setComplete() ;
            $this->strValue = '' ;
            $this->element->addElement($ele) ;

            $this->flagString = false ;
        }
    }
}

class Element {
    protected $parent ;
    protected $isComplete = false ;
    protected $value = null ;
    protected $ntype = null ;
    protected $currentKey = null ;

    public function __construct($parent, $ntype) {
        $this->parent = $parent ;
        $this->ntype = $ntype ;
    }

    public function getParent() {
        return $this->parent ;
    }

    public function setComplete() {
        $this->isComplete = true ;
    }

    public function isComplete() {
        return $this->isComplete ;
    }

    public function addKey($key) {
        $this->currentKey = $key ;
    }

    public function addValue($value) {
        if ($this->ntype == 'number') {
            if ($value == 'true') $this->value = true ;
            if ($value == 'false') $this->value = false ;
            if ($value == 'null') $this->value = null ;
            if (is_numeric($value)) $this->value = strpos($value, '.') !== false ? (float)$value : (int)$value;
        } elseif ($this->ntype == 'string') {
            $this->value = $value ;
        }
    }

    public function addElement($ele) {
        if (in_array($this->ntype, ['array', 'object', 'root'])) {
            if ($this->currentKey) {
                $this->value[$this->currentKey] = $ele ;
            } else {
                $this->value[] = $ele ;
            }
        }
       
        $this->currentKey = null ;
    }

    public function output() {
        $r = [] ;

        if ($this->ntype == 'root') {
            $r = $this->value[0] ? $this->value[0]->output() : null ;
        } elseif ($this->ntype == 'array') {
            if ($this->value === null) return $r ;
            foreach($this->value as $v) $r[] = $v->output() ;
        } elseif ($this->ntype == 'object') {
            if ($this->value === null) return $r ;
            foreach($this->value as $k => $v) $r[$k] = $v->output() ;
        } else {
            $r = $this->value ;
        }

        return $r ;
    }
}
