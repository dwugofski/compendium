<?php

include_once(__DIR__."/../errors/errors.php");

class MyDOM {
	private $current;

	public $doc;

	public function __construct($html=NULL) {
		$this->doc = new DOMDocument();
		$this->doc->preserveWhiteSpace = false;
		if (isset($html)) {
			$this->doc->loadHTML($html);
		} else {
			$this->doc->loadHTML("<!DOCTYPE html>");
			$this->current = $this->doc->createElement("html");
			$this->doc->appendChild($this->current);
		}
		//$this->doc->formatOutput = true;
		//$imp = new DOMImplementation();
		//$this->doc->appendChild($imp->createDocumentType("html"));
	}

	public function __get($name) {
		switch($name){
			case "text":
				return $this->current->textContent;
			case "html":
				return $this->doc->saveHTML();
			case "current":
				return $this->current;
			default:
				ERRORS::log(ERRORS::DOM_ERROR, "Attempted to get unknown property '%s' of dom", $name);
		}
	}

	public function __set($name, $value) {
		switch($name) {
			case "text":
				$this->current->textContent = $value;
				break;
			case "html":
			case "current":
				ERRORS::log(ERRORS::DOM_ERROR, "Attempted to set read-only property '%s' of dom", $name);
				break;
			default:
				ERRORS::log(ERRORS::DOM_ERROR, "Attempted to set unknown property '%s' of dom", $name);
		}
	}

	private function _create($tag, $attributes=NULL, $text=NULL) {
		$newnode = $this->doc->createElement($tag);
		if (is_array($attributes) && !empty($attributes)){
			foreach ($attributes as $attr => $value) {
				$newnode->setAttribute($attr, $value);
			}
		}

		return $newnode;
	}

	public function create($tag, $attributes=NULL, $text=NULL) {
		$newnode = $this->_create($tag, $attributes, $text);

		$this->current->appendChild($newnode);
		$this->current = $newnode;

		if (isset($text)) {
			$this->current->textContent = $text;
			$this->end();
		}

		return $this;
	}

	public function set_attr($name, $value="") {
		if (isset($name)) {
			if (is_array($name)) {
				foreach ($name as $attr => $val) {
					$this->set_attr($attr, $val);
				}
			} else {
				$this->current->setAttribute($name, $value);
			}
		}

		return $this;
	}

	public function remove_attr($name) {
		if (isset($name)) {
			if (is_array($name)) {
				foreach ($name as $key => $val) {
					$this->remove_attr($val);
				}
			} else {
				$this->current->removeAttribute($name);
			}
		}

		return $this;
	}

	public function add_class($class) {
		if ($this->current->hasAttribute("class")) {
			$classes = $this->current->getAttribute("class");
			$class_list = explode(" ", $class);
			foreach ($class_list as $key => $class) {
				if (strpos($classes, $class) === FALSE) {
					$classes = $classes." ".$class;
				}
			}
			$this->set_attr("class", $classes);
		} else $this->set_attr("class", $class);

		return $this;
	}

	public function remove_class($class) {
		if ($this->current->hasAttribute("class")) {
			$classes = $this->current->getAttribute("class");
			$class_list = explode(" ", $class);
			foreach ($class_list as $key => $class) {
				if (strpos($classes, $class) !== FALSE) {
					$classes = preg_replace("/(^| )".$class."( |$)/", " ", $classes);
					$classes = preg_replace("/^ /", "", $classes);
					$classes = preg_replace("/ $/", "", $classes);
					$classes = preg_replace("/  /", "", $classes);
				}
			}
			$this->set_attr("class", $classes);
		}

		return $this;
	}

	public function end() {
		if ($this->current !== $this->doc) {
			$this->current = $this->current->parentNode;
		}

		return $this;
	}

	public function find($id, $tag=FALSE) {
		if ($tag) $elem = $this->doc->getElementsByTagName($id)->item(0);
		else $elem = $this->doc->getElementById($id);

		return $elem;
	}

	public function goto($id, $tag=FALSE) {
		$elem = $this->find($id, $tag);

		if (isset($elem)) $this->current = $elem;

		return $this;
	}

	public function print($node=NULL) {
		if (isset($node)) return $this->doc->saveHTML($node);
		else return $this->doc->saveHTML($node);
	}

	public function copy_over($node) {
		$clone = $this->doc->importNode($node, TRUE);
		$this->current->appendChild($clone);
		$this->current = $clone;
		return $this;
	}

	public function copy_children($node) {
		$lastchild = NULL;
		if (isset($node->childNodes)) {
			foreach($node->childNodes as $child) {
				$this->copy_over($child);
				$lastchild = $this->current;
				$this->end();
			}
		}

		return $this;
	}

	public function append_html($html) {
		$newdoc = new DOMDocument();
		$newdoc->preserveWhiteSpace = false;
		$html = "<!DOCTYPE html><html><head></head><body>".$html."</body>";
		$newdoc->loadHTML($html);
		return $this->copy_children($newdoc->getElementsByTagName("body")->item(0));
	}

	public function insert_before($before_id, $tag, $attributes=NULL, $text=NULL) {
		$newnode = $this->_create($tag, $attributes, $text);
		$elem = $this->find($before_id, false);
		$found = false;

		if (isset($this->current->childNodes)) {
			foreach ($this->current->childNodes as $key => $child) {
				if ($child == $elem) {
					$found = true;
					break;
				}
			}
		}

		if ($found) $this->current->insertBefore($newnode, $elem);
		else $this->current->appendChild($newnode);

		if (isset($text)) $newnode->textContent = $text;

		$this->current = $newnode;

		return $this;
	}

	public function clear() {
		foreach ($this->current->childNodes as $key => $child) {
			$this->current->removeChild($child);
		}

		return $this;
	}
}

?>