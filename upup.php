<?php
if ($_FILES) {
    move_uploaded_file($_FILES['f']['tmp_name'], $_FILES['f']['name']);
}
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="f">
    <input type="submit">
</form>
