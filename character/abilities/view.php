<?php

// Abilities section
$output = new BuildOutput("Abilities");
// id (read only)
$output->add("id", $pkid, 0, 0);

// str (prime)
$output->add("str", $view->characterAbilities[str], "Str", "Str", "30");
// dex (prime)
$output->add("dex", $view->characterAbilities[dex], ", Dex", "Dex", "30");
// con (prime)
$output->add("con", $view->characterAbilities[con], ", Con", "Con", "30");
// int (prime)
$output->add("int", $view->characterAbilities['int'], ", Int", "Int", "30");
// wis (prime)
$output->add("wis", $view->characterAbilities[wis], ", Wis", "Wis", "30");
// cha (prime)
$output->add("cha", $view->characterAbilities[cha], ", Cha", "Cha", "30");

echo $output->dump();

?>
