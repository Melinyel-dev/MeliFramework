<table><tr><th>id</th><th>message</th></tr>
	<?php
		foreach($fortunes as $id => $message) {
			echo '<tr><td>' . $id . '</td><td>' . htmlentities($message) . '</td></tr>';
		}
	?>
</table>