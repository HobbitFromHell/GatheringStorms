<?php

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}

// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Spells', 'class_list=' + JSON.stringify(charSheet.class_level))\n";
echo"</script>\n";

?>
