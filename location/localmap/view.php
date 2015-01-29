<?php

// maps section
$output = new BuildOutput("Localmap");

// id
$output->add("id", $pkid, 0, 0);

// map image
$output->add("", $view->image, "", "Select local map image to upload: <input type=\"file\" name=\"xlocalmapfile\" id=\"xlocalmapfile\">");

echo $output->dump(0);

?>

<form action="/location/upload.php?id=<?php echo $pkid; ?>" method="POST" enctype="multipart/form-data" target="fileUpload">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Upload Image" name="submit">
</form>

<iframe id="fileUpload" name="fileUpload" style="display:none"></iframe>
