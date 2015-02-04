<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

post_data("t_characters_racial_traits", "id", "character_id", Array(Array("racialtrait", "racial_trait_id"), "detail"));
post_data("t_characters_character_classes_class_features", "id", "character_id", Array(Array("classfeature", "character_Classes_class_feature_id"), "detail"));


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "calcRacialtraits()\n";
echo "buildSection('Treasure', 'size=' + charSheet.size)\n";
echo "</script>\n";

?>
