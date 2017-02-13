<?php
// Name: Piyush Zode
// ID: 

// display all errors on the browser
error_reporting(E_ALL);
ini_set('display_errors','On');

// if there are many files in your Dropbox it can take some time, so disable the max. execution time
set_time_limit(0);

require_once("DropboxClient.php");

// you have to create an app at https://www.dropbox.com/developers/apps and enter details below:
$dropbox = new DropboxClient(array(
	'app_key' => "vr1ii0ib7nfrbg3",      // Put your Dropbox API key here
	'app_secret' => "iwya1rfeykjybhq",   // Put your Dropbox API secret here
	'app_full_access' => false,
),'en');

$final_image = null;

// first try to load existing access token
$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
	#echo "loaded access token:";
	#print_r($access_token);
}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}

// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}


if(!empty($_FILES['file_to_upload'])) {
	#check if it's jpg or jpeg
	if($_FILES["file_to_upload"]["type"]=="image/jpeg" || $_FILES["file_to_upload"]["type"]=="image/jpg") {
		$file_name = $_FILES["file_to_upload"]["name"];
		$dropbox->UploadFile($_FILES["file_to_upload"]["tmp_name"], $file_name);
		$message = "File Uploaded Successfully!";
        echo "<script type='text/javascript'>alert('$message');</script>";
	}
	else {
		$message = "Please upload a jpg/jpeg image only!";
        echo "<script type='text/javascript'>alert('$message');</script>";
	}
}

if(isset($_GET['download_file'])) {
	$file_name = $_GET['download_file'];
	$test_file = "C:\wdmDownloads/"."download_".basename($file_name);
	$dropbox->DownloadFile($file_name, $test_file);
	#print $file_name;
	$final_image=$file_name;
}

if(isset($_POST['delete_file'])){
	$dropbox->Delete($_POST['delete_file']);
	$message = $_POST['delete_file']." Image Deleted Successfully!";
    echo "<script type='text/javascript'>alert('$message');</script>";
}

$files = $dropbox->GetFiles("",false);

function store_token($token, $name)
{
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}

function load_token($name)
{
	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
	@unlink("tokens/$name.token");
}


function enable_implicit_flush()
{
	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 0);
	@ini_set('implicit_flush', 1);
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	echo "<!-- ".str_repeat(' ', 2000)." -->";
}

function test($file) {
	$message ="Hi. reached here";
	echo "<script type='text/javascript'>alert('$message');</script>";
}

?>

<!DOCTYPE html>
<html>
<head><title>Project 4</title>
<link rel="stylesheet" type="text/css" href="mystylesheet.css">
<link rel="shortcut icon" href="Dropbox_Icon.ico">
<script>
		function test(filelink,filecontents,filename) {
			
			document.getElementById('displayImage').src = filelink;
			var a = document.createElement('a');
			a.href = filecontents;
			a.download = filename;
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
		}
</script>
</head>
<body>
	<div class="entirePage">
		<div class="headerPage">
			<?php
				$account_information = $dropbox->GetAccountInfo();
				echo "<p><b><h4><u>Project 4</u></h4></b></p>";
				echo "<p>Account Name: <b>".$account_information->display_name."</b></p>";
				echo "<p>Dropbox Email ID: <b>".$account_information->email."</b></p>";
				echo "<p>App Name : <b>cse5335_psz4127</b>" 
			?>
		</div>
		<div class="uploadPage">
			<p>
			<form enctype="multipart/form-data" action="album.php" method="post">
				<fieldset>
					<legend>
						<img src="Dropbox_Icon.png" width="15px" height="15px"></img>
						<b>Upload Image:</b>
					</legend>
					<br>
					<label>Select a File: <input name="file_to_upload" type="file"></label>
					<br><br>
	    			<input type="submit" value="Upload Image" class="Button_class">
				</fieldset>
			</form>
			</p>
			<br>
			<img src="Dropbox_Icon.png" width="15px" height="15px"></img>
			<b>Dropbox Images:</b><br>
			<?php
				if(!empty($files)){
					echo '<table style="width:100%">
						<tr>
						<th>Image Name</th>
						<th>Download Image</th>
						<th>Delete Image</th>
		  				</tr>';
					foreach(array_keys($files) as $file) {
						echo '<tr><td>'.$file.'</td>';
						$tmp = $dropbox->GetLink($file,false); 
						$img_data = base64_encode(file_get_contents($tmp)); 
						$image_contents = "data:image/jpeg;base64,".$img_data ?>
						
						<td><a href='#' onclick="test('<?php echo $tmp;?>','<?php echo $image_contents?>','<?php echo $file?>');">Click Here</a></td>

						<?php
						#echo '<td><a href="album.php?download_file='.$file.'">Click Here</a></td>'; 
						echo '<td><form action="album.php" method="post">
							<input type="hidden" name="delete_file" value="'.$file.'">
							<input type="submit" value="delete_file" class="Button_class">
							</form></td>';
						echo '</tr>';
					}
					echo '</table>';
				}
				else {
					echo '<p>Dropbox is Empty.</p>';
				}
			?>
		</div>
		<div class="imagePage">
			<br>
			<img src="Dropbox_Icon.png" width="15px" height="15px"></img>
			<b>Downloaded Image:</b><br>
			<?php
				if(!empty($files)) {
					#if(!is_null($final_image)) {
						#echo '<img id="displayImage" src="'.$dropbox->GetLink($final_image,false).'"/>';
						echo '<img id="displayImage" src="" alt="Click on the Download Image link to view any Image"/>';
						#echo '<a href="'.$dropbox->GetLink($final_image,false).'" download>Download</a>';
					#}
					#else {
					#	echo 'Click on the Download Image link to view any Image';
					#}
				}
				else {
					echo '<p>Dropbox is Empty.</p>';
				}
			?>
		</div>
	</div>
</body>
</html>