<?php

$dom->goto("display_h1")->text = "The Compendium";
$dom->goto("display_h2")->text = "A place where worlds meet";

$content_html = <<<HTML
				<h1>Chapter 1: Lorem</h1>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h2>Section 1: Ipsum:</h2>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h3>Dolor:</h3>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h4>Sit:</h4>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h5>Amet</h5>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h6>Adipiscing</h6>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel. Integer quis dictum diam. <b>Phasellus</b> tempus ante sed quam congue, sed pulvinar massa pretium. Integer sed commodo lorem, eget gravida purus. <a href="/foobar">Aenean</a> vitae <a href="">metus</a> at enim vulputate facilisis ac nec velit. Ut mattis accumsan nunc non lacinia. Donec dapibus sem dignissim fermentum bibendum. Praesent sodales quam in lorem scelerisque porttitor. Morbi sit amet lectus et augue molestie vulputate non sit amet tortor. Vestibulum facilisis commodo euismod. Donec turpis felis, mollis nec feugiat at, vestibulum non enim.
				</p>
				<p>
					Pellentesque congue quam et sapien porttitor, ultrices dignissim metus pretium. Aliquam vel lorem semper, bibendum justo sed, vulputate dolor. Aliquam vulputate tincidunt libero, eu dignissim tellus sagittis non. Quisque condimentum, nulla et eleifend eleifend, enim odio sagittis odio, at varius metus risus nec odio. Etiam sagittis et diam vitae gravida. Ut nec euismod massa, quis aliquet odio. Nunc a sem ut nisi gravida elementum. Vestibulum lobortis tincidunt posuere. Praesent dictum metus sit amet magna faucibus bibendum. Nunc luctus metus imperdiet maximus auctor.
				</p>
				<p>
					Sed augue nisi, condimentum sit amet nunc quis, tempus mattis ligula. Vivamus sagittis ipsum urna, ut feugiat ex ullamcorper eu. Nam vitae nunc ac risus rutrum placerat at nec quam. Cras mollis diam sed pretium laoreet. Pellentesque eget pellentesque felis. Ut vitae massa a tortor tempus consequat. Praesent dignissim velit est, sit amet ultrices dui pharetra eget. Ut ullamcorper euismod sodales.
				</p>
				<p>
					Cras ornare consectetur libero vel posuere. Fusce suscipit ac leo vitae ullamcorper. In blandit vehicula ex, eu commodo magna semper nec. Proin fermentum mollis dolor a gravida. Sed porttitor magna felis. Nunc pellentesque mollis aliquet. Mauris dignissim augue non suscipit congue. Vivamus egestas ex vel libero rhoncus, fermentum vestibulum lectus aliquam. Pellentesque a pharetra leo. Ut eget metus dapibus, pulvinar nibh et, ultricies nulla. Vivamus odio risus, volutpat at gravida vel, laoreet eget augue. Nullam id est nisl. Proin tempus urna ut vehicula vestibulum. Nullam leo diam, ultricies eget massa ut, posuere placerat turpis. Nam accumsan lectus sit amet felis hendrerit molestie. Etiam ut ligula ut tellus congue gravida.
				</p>
HTML;
$dom->goto("content");
$dom->append_html($content_html);

$dom->goto("main");
$dom->add_class("no-sidebar");

echo($dom->html);

?>