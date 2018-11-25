<?php

	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	$headers .= 'From: <no-reply@compendium.com>' . "\r\n";

	$email_subject = "Compendium: Verify User";
	$message = <<<HTML
<html>
	<head>
		<title>Compendium: Verify User</title>
	</head>
	<body>
		<h1>Welcome to the Compendium!</h1>
		<p>Hello, $user->username!</p>
		<p>
			Thank you for joining the Compendium! We look forward to your contribution to our community. Before you can access all the features and services we provide for you, however, you will need to verify you email address. To verify your identy, please click here.
		</p>
		<p>
			Thank you!<br/>
			The Compendium Team
		</p>
	</body>
</html>

HTML;

	mail($user->email, $subject, $message, $headers);

?>