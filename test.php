<?php

include_once(__DIR__."/com/php/util/dom.php");

$dom = new MyDOM();

$dom->create("head", NULL, "");
$dom->create("body");

$dom->create("h1", NULL, "Hello World!");

$dom->create("div");
$dom->create("div", ["id"=>"foo"]);
$dom->end();
$dom->create("div", ["id"=>"bar"]);
$dom->text = "bar";
$dom->goto("foo");
$dom->text = "foo";
$dom->goto("bar");
$dom->text = "foo";
$dom->end();

$new_html = <<<HTML
<div id="groot">
	<div id="foobar">Foobar</div>
</div>
<div id="root2">
	<div id="foobar2"></div>
</div>
HTML;
//$newdom = new MyDOM($new_html);
//$newdom->goto("root", TRUE);

$dom->append_html($new_html);
$dom->goto("root2");
$dom->text = "Foobar2";
$dom->add_class("foo");
$dom->add_class("bar foobar");
$dom->add_class("barfoo barbar");
$dom->remove_class("foo foobar barbar");

echo($dom->html);

?>