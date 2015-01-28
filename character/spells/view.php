<?php

// spells section
$output = new BuildOutput("Spells");

// id and parameter
$output->add("id", $pkid, 0, 0);
$output->add("class_list", $post_class_list, 0, 0);

if($view->class_list['ADP']) {
	// adept spells known
	$output->addRead("<div id='calcADPSpellBlock' class='spellBlock'>");
	$output->add("", "<span id='calcADPKnown' class='spellTitle'></span>", "", "<span id='editADPKnown'></span>");
	$output->add("", "<span id='calc6ADPKnown'></span>", "", "<span id='edit6ADPKnown'></span>");
	$output->add("", "<span id='calc5ADPKnown'></span>", "", "<span id='edit5ADPKnown'></span>");
	$output->add("", "<span id='calc4ADPKnown'></span>", "", "<span id='edit4ADPKnown'></span>");
	$output->add("", "<span id='calc3ADPKnown'></span>", "", "<span id='edit3ADPKnown'></span>");
	$output->add("", "<span id='calc2ADPKnown'></span>", "", "<span id='edit2ADPKnown'></span>");
	$output->add("", "<span id='calc1ADPKnown'></span>", "", "<span id='edit1ADPKnown'></span>");
	$output->add("", "<span id='calc0ADPKnown'></span>", "", "<span id='edit0ADPKnown'></span>");
	$output->addRead("</div>");
}

if($view->class_list['BBN']) {
	// barbarian rage powers
	$output->addEdit("<span id='editRagePowers'></span>", "");
}

if($view->class_list['BRD']) {
	// bard spells known
	$output->addRead("<div id='calcBRDSpellBlock' class='spellBlock'>");
	$output->add("", "<span id='calcBRDKnown' class='spellTitle'></span>", "", "<span id='editBRDKnown'></span>");
	$output->add("", "<span id='calc6BRDKnown'></span>", "", "<span id='edit6BRDKnown'></span>");
	$output->add("", "<span id='calc5BRDKnown'></span>", "", "<span id='edit5BRDKnown'></span>");
	$output->add("", "<span id='calc4BRDKnown'></span>", "", "<span id='edit4BRDKnown'></span>");
	$output->add("", "<span id='calc3BRDKnown'></span>", "", "<span id='edit3BRDKnown'></span>");
	$output->add("", "<span id='calc2BRDKnown'></span>", "", "<span id='edit2BRDKnown'></span>");
	$output->add("", "<span id='calc1BRDKnown'></span>", "", "<span id='edit1BRDKnown'></span>");
	$output->add("", "<span id='calc0BRDKnown'></span>", "", "<span id='edit0BRDKnown'></span>");
	$output->addRead("</div>");
}

if($view->class_list['DRD'] || $view->characterSpells[spell]['CLR']) {
	// cleric & druid spells, with domain spells
	$output->addRead("<div id='calcCLRSpellBlock' class='spellBlock'>");
	$output->add("", "<span id='calcCLRKnown' class='spellTitle'></span>", "", "<span id='editCLRKnown'></span>");
	$output->add("", "<span id='calcDRDKnown' class='spellTitle'></span>", "", "<span id='editDRDKnown'></span>");
	$output->add("", "<span id='calc9CLRKnown'></span>", "", "<span id='edit9CLRKnown'></span>");
	$output->add("", "<span id='calc9DRDKnown'></span>", "", "<span id='edit9DRDKnown'></span>");
	$output->add("", "<span id='calc9DOMAINKnown'></span>", "", "<span id='edit9DOMAINKnown'></span>");
	$output->add("", "<span id='calc8CLRKnown'></span>", "", "<span id='edit8CLRKnown'></span>");
	$output->add("", "<span id='calc8DRDKnown'></span>", "", "<span id='edit8DRDKnown'></span>");
	$output->add("", "<span id='calc8DOMAINKnown'></span>", "", "<span id='edit8DOMAINKnown'></span>");
	$output->add("", "<span id='calc7CLRKnown'></span>", "", "<span id='edit7CLRKnown'></span>");
	$output->add("", "<span id='calc7DRDKnown'></span>", "", "<span id='edit7DRDKnown'></span>");
	$output->add("", "<span id='calc7DOMAINKnown'></span>", "", "<span id='edit7DOMAINKnown'></span>");
	$output->add("", "<span id='calc6CLRKnown'></span>", "", "<span id='edit6CLRKnown'></span>");
	$output->add("", "<span id='calc6DRDKnown'></span>", "", "<span id='edit6DRDKnown'></span>");
	$output->add("", "<span id='calc6DOMAINKnown'></span>", "", "<span id='edit6DOMAINKnown'></span>");
	$output->add("", "<span id='calc5CLRKnown'></span>", "", "<span id='edit5CLRKnown'></span>");
	$output->add("", "<span id='calc5DRDKnown'></span>", "", "<span id='edit5DRDKnown'></span>");
	$output->add("", "<span id='calc5DOMAINKnown'></span>", "", "<span id='edit5DOMAINKnown'></span>");
	$output->add("", "<span id='calc4CLRKnown'></span>", "", "<span id='edit4CLRKnown'></span>");
	$output->add("", "<span id='calc4DRDKnown'></span>", "", "<span id='edit4DRDKnown'></span>");
	$output->add("", "<span id='calc4DOMAINKnown'></span>", "", "<span id='edit4DOMAINKnown'></span>");
	$output->add("", "<span id='calc3CLRKnown'></span>", "", "<span id='edit3CLRKnown'></span>");
	$output->add("", "<span id='calc3DRDKnown'></span>", "", "<span id='edit3DRDKnown'></span>");
	$output->add("", "<span id='calc3DOMAINKnown'></span>", "", "<span id='edit3DOMAINKnown'></span>");
	$output->add("", "<span id='calc2CLRKnown'></span>", "", "<span id='edit2CLRKnown'></span>");
	$output->add("", "<span id='calc2DRDKnown'></span>", "", "<span id='edit2DRDKnown'></span>");
	$output->add("", "<span id='calc2DOMAINKnown'></span>", "", "<span id='edit2DOMAINKnown'></span>");
	$output->add("", "<span id='calc1CLRKnown'></span>", "", "<span id='edit1CLRKnown'></span>");
	$output->add("", "<span id='calc1DRDKnown'></span>", "", "<span id='edit1DRDKnown'></span>");
	$output->add("", "<span id='calc1DOMAINKnown'></span>", "", "<span id='edit1DOMAINKnown'></span>");
	$output->add("", "<span id='calc0CLRKnown'></span>", "", "<span id='edit0CLRKnown'></span>");
	$output->add("", "<span id='calc0DRDKnown'></span>", "", "<span id='edit0DRDKnown'></span>");
	$output->addRead("</div>");
	// domains and domain powers
	$output->add("", "<span id='calcDomains'></span>", "", "<span id='editDomains'></span>");
	$output->add("", "<span id='calcDomainPowers'></span>", "", "<span id='editChannelEnergy'></span>");
}

if($view->class_list['FTR']) {
	// fighter training
	$output->addEdit("<span id='editWeaponGroup'></span>", "");
	$output->addEdit("<span id='editWeaponMastery'></span>", "");
}

if($view->class_list['PAL']) {
	// paladin spells
	$output->addRead("<div id='calcPALSpellBlock' class='spellBlock'>");
	$output->add("", "<span id='calcPALKnown' class='spellTitle'></span>", "", 0);
	$output->add("", "<span id='calc4PALKnown'></span>", "", "<span id='edit4PALKnown'></span>");
	$output->add("", "<span id='calc3PALKnown'></span>", "", "<span id='edit3PALKnown'></span>");
	$output->add("", "<span id='calc2PALKnown'></span>", "", "<span id='edit2PALKnown'></span>");
	$output->add("", "<span id='calc1PALKnown'></span>", "", "<span id='edit1PALKnown'></span>");
	$output->addRead("</div>");
}

if($view->class_list['RGR']) {
	// ranger spells
	$output->addEdit("<span id='editFavouredEnemy'></span>", "");
	$output->addRead("<div id='calcRGRSpellBlock' class='spellBlock'>");
	$output->add("", "<span id='calcRGRKnown' class='spellTitle'></span>", "", 0);
	$output->add("", "<span id='calc4RGRKnown'></span>", "", "<span id='edit4RGRKnown'></span>");
	$output->add("", "<span id='calc3RGRKnown'></span>", "", "<span id='edit3RGRKnown'></span>");
	$output->add("", "<span id='calc2RGRKnown'></span>", "", "<span id='edit2RGRKnown'></span>");
	$output->add("", "<span id='calc1RGRKnown'></span>", "", "<span id='edit1RGRKnown'></span>");
	$output->addRead("</div>");
}

if($view->class_list['SOR']) {
	// sorcerer spells known
	$output->addRead("<div id='calcSORSpellBlock' class='spellBlock'>");
	$output->add("", "<span id='calcSORKnown' class='spellTitle'></span>", "", "<span id='editSORKnown'></span>");
	$output->add("", "<span id='calc9SORKnown'></span>", "", "<span id='edit9SORKnown'></span>");
	$output->add("", "<span id='calc8SORKnown'></span>", "", "<span id='edit8SORKnown'></span>");
	$output->add("", "<span id='calc7SORKnown'></span>", "", "<span id='edit7SORKnown'></span>");
	$output->add("", "<span id='calc6SORKnown'></span>", "", "<span id='edit6SORKnown'></span>");
	$output->add("", "<span id='calc5SORKnown'></span>", "", "<span id='edit5SORKnown'></span>");
	$output->add("", "<span id='calc4SORKnown'></span>", "", "<span id='edit4SORKnown'></span>");
	$output->add("", "<span id='calc3SORKnown'></span>", "", "<span id='edit3SORKnown'></span>");
	$output->add("", "<span id='calc2SORKnown'></span>", "", "<span id='edit2SORKnown'></span>");
	$output->add("", "<span id='calc1SORKnown'></span>", "", "<span id='edit1SORKnown'></span>");
	$output->add("", "<span id='calc0SORKnown'></span>", "", "<span id='edit0SORKnown'></span>");
	$output->addRead("</div>");
	// bloodline
	$output->add("", "<span id='calcBloodline'></span>", "", "<span id='editBloodline'></span>");
}

if($view->class_list['WIZ']) {
	// wizard spells
	$output->addRead("<div id='calcWIZSpellBlock' class='spellBlock'>");
	$output->add("", "<span id='calcWIZKnown' class='spellTitle'></span>", "", "<span id='editWIZKnown'></span>");
	$output->add("", "<span id='calc9WIZKnown'></span>", "", "<span id='edit9WIZKnown'></span>");
	$output->add("", "<span id='calc8WIZKnown'></span>", "", "<span id='edit8WIZKnown'></span>");
	$output->add("", "<span id='calc7WIZKnown'></span>", "", "<span id='edit7WIZKnown'></span>");
	$output->add("", "<span id='calc6WIZKnown'></span>", "", "<span id='edit6WIZKnown'></span>");
	$output->add("", "<span id='calc5WIZKnown'></span>", "", "<span id='edit5WIZKnown'></span>");
	$output->add("", "<span id='calc4WIZKnown'></span>", "", "<span id='edit4WIZKnown'></span>");
	$output->add("", "<span id='calc3WIZKnown'></span>", "", "<span id='edit3WIZKnown'></span>");
	$output->add("", "<span id='calc2WIZKnown'></span>", "", "<span id='edit2WIZKnown'></span>");
	$output->add("", "<span id='calc1WIZKnown'></span>", "", "<span id='edit1WIZKnown'></span>");
	$output->add("", "<span id='calc0WIZKnown'></span>", "", "<span id='edit0WIZKnown'></span>");
	$output->addRead("</div>");
	// arcane school
	$output->add("", "<br><span id='calcArcaneSchool'></span>", "", "<span id='editArcaneSchool'></span>");
}

echo $output->dump(1);

?>
