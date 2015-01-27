<?php

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}

// //////
// insert
// //////

$post_data = false;
// racial traits
if(post_data("t_characters_racial_traits", "racial_trait_id", "character_id", Array(Array("racialtrait", "detail")))) {
	$post_data = true;
}
// class features
if(post_data("t_characters_character_classes_class_features", "character_classes_class_feature_id", "character_id", Array(Array("classfeature", "detail")))) {
	$post_data = true;
}

echo "<script>\n";
if($post_data) {
	echo "buildSection('Main')\n";
	echo "</script>\n";
	return;
}
echo "buildSection('Languages')\n";
echo "</script>\n";


// ////////////////////////////
// communicate with parent page
// ////////////////////////////


?>
