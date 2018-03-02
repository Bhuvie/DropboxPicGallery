<!DOCTYPE html>
<html>
<head>
<title>Album</title>
</head>
<body>
<?php
require_once 'demo-lib.php';
error_reporting(0);
demo_init(); 
set_time_limit( 0 );

require_once 'DropboxClient.php';

$dropbox = new DropboxClient( array(
	'app_key' => "xxx",      // Put your Dropbox API key here
	'app_secret' => "xxx",   // Put your Dropbox API secret here
	'app_full_access' => false,
) );

$return_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?auth_redirect=1";

$bearer_token = demo_token_load( "bearer" );

if ( $bearer_token ) {
	$dropbox->SetBearerToken( $bearer_token );
} elseif ( ! empty( $_GET['auth_redirect'] ) ) // are we coming from dropbox's auth page?
{
	$bearer_token = $dropbox->GetBearerToken( null, $return_url );
	demo_store_token( $bearer_token, "bearer" );
} elseif ( ! $dropbox->IsAuthorized() ) {
	$auth_url = $dropbox->BuildAuthorizeUrl( $return_url );
	die( "Authentication required. <a href='$auth_url'>Continue.</a>" );
}

if(isset($_POST['submit']))
{
    $file_name = $_FILES['image']['name'];
    $file_size =$_FILES['image']['size'];
    $file_tmp =$_FILES['image']['tmp_name'];
    $file_type=$_FILES['image']['type'];
    move_uploaded_file($file_tmp, $file_name);
    $dropbox->UploadFile($file_name);
    unlink($file_name);
}



if(isset($_GET['delete']))
{
    $file_name=$_GET['delete'];
    $jpg_files = $dropbox->Search( "/", $file_name, 1 );
    $jpg_file = reset( $jpg_files );
    $dropbox->Delete($jpg_file);
}
?>
<h3>Upload Image</h3>
<form action="album.php" method="post" enctype="multipart/form-data">
Select image to upload:
<input type="file" name="image" id="image" accept="image/*" />
<input type="submit" value="Upload Image" name="submit" />
</form>
<br/><br/><h3>Files in the Dropbox listed:</h3>
<table border=1>
<tr>
<td>Image Name</td>
<td>Thumbnail</td>
<td>Delete Image</td>
</tr>
<?php
$files = $dropbox->GetFiles( "", false );
foreach($files as $file)
{
    $img_data = base64_encode( $dropbox->GetThumbnail( $file->path ) );
    print "<tr><td><a href='album.php?view=".$file->name."'>".$file->name."</a><td><a href='album.php?view=".$file->name."'>";
    print "<img src=\"data:image/jpeg;base64,$img_data\" alt=\"Generating thumbnail failed!\" style=\"border: 1px solid black;\"/></a></td><td><a href='album.php?delete=".$file->name."'>Delete this image</a></td></tr>";
}
?>
</table>
<h3>Image Section</h3>
Thumbnail:
<div name="thimg" id="thimg">Thumbnail of the selected image will be shown here.</div>
Full Image:<br/>
<img src="" alt="Select a image to see it here." style="border: 1px solid black;" id="imgsection" name="imgsection"/>
<?php
if(isset($_GET['view']))
{
    $file_name=$_GET['view'];
    $jpg_files = $dropbox->Search( "/", $file_name, 1 );
    $jpg_file = reset( $jpg_files );
    $dropbox->DownloadFile($jpg_file->path, "tmp_".$file_name );
    $img_data = base64_encode( $dropbox->GetThumbnail( $jpg_file->path ) );
    print "<script type='text/javascript'>
            function chngimg(filename)
            {
                var img=document.getElementById('imgsection');
                img.src='./'+filename;
                var thimg=document.getElementById('thimg');
                thimg.innerHTML='<img src=\"data:image/jpeg;base64,$img_data\" alt=\"Generating thumbnail failed!\" style=\"border: 1px solid black;\"/>';
            }
            chngimg('"."tmp_".$file_name."');</script>";

}
?>
</body>
</html>