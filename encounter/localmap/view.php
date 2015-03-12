<?php

// maps section
$output = new BuildOutput("Localmap");

// id
$output->add("id", $pkid, 0, 0);

// map image
echo($view->encounterLocalmap['image_tag'] . "<br>");

// map details
$output->add("map_details", $view->encounterLocalmap['map_details'], "", "Map Details<br>", "textarea");

echo $output->dump(1);

?>

<div id="divFileUpload">
<form action="/encounter/upload.php?id=<?php echo $pkid; ?>" method="POST" enctype="multipart/form-data" target="fileUpload">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Upload Image" name="submit">
</form>

<iframe id="fileUpload" name="fileUpload" style="display:none"></iframe>
</div>
<script>
	$('#divFileUpload').appendTo('#localmapEdit')
</script>
