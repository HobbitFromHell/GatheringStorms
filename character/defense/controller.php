<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}

// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Description')\n";

// last (meaningful) section calculates all sections
echo "calcAbilities()\n";
echo "calcMain()\n";
echo "calcFeats()\n";
echo "calcLanguages()\n";
echo "calcOffense()\n";
echo "calcTreasure()\n";
echo "</script>\n";

?>
