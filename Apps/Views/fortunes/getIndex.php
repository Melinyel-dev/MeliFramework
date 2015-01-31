<?php

echo '<table><tr><th>id</th><th>message</th></tr>';
foreach($fortunes as $id => $message) {
	echo '<tr><td>'.$id.'</td><td>'.htmlentities($message).'</td></tr>';
}
echo '</table>';