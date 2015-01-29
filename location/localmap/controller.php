<?php

// //////////
// validation
// //////////

if(strpos($pkid, "x") < 1) {
	echo "FAIL: Location ID {$pkid} is invalid";
	return false;
}


// //////
// upload
// //////

$target_dir = "img/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);

// Check if image file is a actual image or fake image
if(isset($_POST[id])) {
	preecho($_POST);
	preecho($_FILES);

	$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
	if($check !== false) {
		echo "File is an image - " . $check["mime"] . ".";
		$uploadOk = 1;
	}
	else {
		echo "File is not an image.";
		$uploadOk = 0;
	}

	// Check if file already exists
	if (file_exists($target_file)) {
		echo "Sorry, file already exists.";
		$uploadOk = 0;
	}
	
	// Check file size
	if ($_FILES["fileToUpload"]["size"] > 500000) {
		echo "Sorry, your file is too large.";
		$uploadOk = 0;
	}
	
	// Allow certain file formats
	if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
		echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
		$uploadOk = 0;
	}
	
	// Check if $uploadOk is set to 0 by an error
	// if everything is ok, try to upload file
	if ($uploadOk == 0) {
		echo "Sorry, your file was not uploaded.";
		}
	else {
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
			echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
		}
		else {
			echo "Sorry, there was an error uploading your file.";
		}
	}
}



// //////
// select
// //////

$view = new DataCollector;

if (file_exists("../img/map" . $pkid . ".png")) {
	$view->image = "<b>Local map</b><br><img src=\"img/map" . $pkid . ".png\">";
}
elseif (file_exists("../img/map" . $pkid . ".gif")) {
	$view->image = "<b>Local map</b><br><img src=\"img/map" . $pkid . ".gif\">";
}
elseif (file_exists("../img/map" . $pkid . ".jpg")) {
	$view->image = "<b>Local map</b><br><img src=\"img/map" . $pkid . ".jpg\">";
}
elseif (file_exists("../img/map" . $pkid . ".jpeg")) {
	$view->image = "<b>Local map</b><br><img src=\"img/map" . $pkid . ".jpeg\">";
}
else {
	$view->image = "No local map available";
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Organization')\n";
echo "</script>\n";

?>
