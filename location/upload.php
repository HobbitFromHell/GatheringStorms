<?php

$target_dir = "img/";
$target_ext = basename( $_FILES["fileToUpload"]["name"] );
$uploadOk = 1;
$errMessage = "";
$imageFileType = pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION);
$target_file = $target_dir . "map" . $_GET[id] . "." . $imageFileType;

// Check if image file is a actual image or fake image
if(isset($_GET[id])) {
	$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
	if($check == false) {
		$uploadOk = 0;
	}

	// Check if file already exists
	if (file_exists($target_file)) {
		$uploadOk = 2;
	}
	
	// Check file size
	if ($_FILES["fileToUpload"]["size"] > 500000) {
		$uploadOk = 0;
	}
	
	// Allow certain file formats
	if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
		$uploadOk = 0;
	}

	// Check if $uploadOk is set to 0 by an error
	// if everything is ok, try to upload file
	if ($uploadOk == 0) {
		echo("<script>alert('Sorry, your file was not uploaded.');</script>");
	}
	else {
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
			if ($uploadOk == 2) {
				// replacement image uploaded, requires refresh of window
				echo("<script>parent.location.href = '/location/?id={$_GET[id]}';</script>");
			}
			else {
				echo("<script>parent.buildSection('Localmap');</script>");
			}
		}
		else {
			echo("<script>alert('Sorry, there was an error uploading your file.');</script>");
		}
	}
}

?>
