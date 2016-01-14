<?php
// Copyright 2016 Manuel Freiholz
// https://mfreiholz.de
//
// Requires PHP >= 5.4

//
// CONFIGURATION
//

// The token is used for authentication.
// It needs to be provided by the uploader as POST parameter "token".
define("MF_SECURITY_TOKEN", "1337");

// Local path for uploaded files (absolute path).
// Default: Directory of this script.
define("MF_UPLOAD_DIRECTORY", dirname(__FILE__));

// Public URL to the "MF_UPLOAD_DIRECTORY".
// ! No trailing slash
define("MF_BASE_URL", "http://example.com/share");

// Name of the POST parameter, which will contain the file.
define("MF_FILE_PARAM", "file");

//
// YOU MAY CHANGE SOMETHING HERE
//

// fileUploadSubFolderPath returns a sub-folder path for the current upload.
// No leading or trailing slashes!
// e.g. "folder1/folder2/2015/04"
function fileUploadSubFolderPath($fileName)
{
	global $config;
	$path = date("Y");
	$path.= "-";
	$path.= date("m");
	return $path;
}

/*
	Generates the file's name for the current upload.
	
	@param $fileName string Name of the uploaded file, given by uploader.
	
	@return New file name to use for storage (without extension!)
*/
function fileUploadFileName($fileName)
{
	global $config;
	return $fileName;
}

//
// DO NOT CHANGE ANYTHING BELOW
//

function fileUpload()
{
	global $config;

	// Authenticate
	if (!isset($_POST["token"]) || $_POST["token"] !== MF_SECURITY_TOKEN)
	{
		http_response_code(403);
		throw new Exception("Invalid access token");
	}
	
	// Check file
	if (!isset($_FILES[MF_FILE_PARAM]))
	{
		http_response_code(500);
		throw new Exception("Missing file");
	}

	// Create local upload directory
	$fname = $_FILES[MF_FILE_PARAM]["name"];
	if (empty($fname))
	{
		$fname = md5_file($_FILES[MF_FILE_PARAM]["tmp_name"]);
	}

	$dest = fileUploadSubFolderPath($fname);
	if (!empty($dest))
		$dest.= "/";
	$dest.= $fname;
	
	$localPath = MF_UPLOAD_DIRECTORY . "/" . $dest;
	if (strpos($localPath, "..") !== FALSE)
	{
		http_response_code(500);
		throw new Exception("Hacking attempt! n00b...");
	}
	if (!file_exists(dirname($localPath)) && !mkdir(dirname($localPath), 0777, true))
	{
		http_response_code(500);
		throw new Exception("Can not create upload directory");
	}
	if (file_exists($localPath))
	{
		http_response_code(500);
		throw new Exception("File already exists");
	}

	// Move file into upload_directory
	if (!move_uploaded_file($_FILES[MF_FILE_PARAM]["tmp_name"], $localPath))
	{
		http_response_code(500);
		throw new Exception("Upload failed");
	}
	
	// Respond with public URL to access this file
	header("Content-type: text/plain");
	$uri = MF_BASE_URL . "/" . $dest;
	echo $uri;
}

function testing()
{
	echo "Alright. You can use this URL to upload your files from XShare.<br>";
	echo '<p>Script path: ' . __FILE__ . '</p>';
	echo '<p>Script directory: ' . dirname(__FILE__) . '</p>';
	echo '<form action="" method="post" enctype="multipart/form-data">';
	echo '<input type="hidden" name="route" value="fileupload">';
	echo '<input type="hidden" name="token" value="' . MF_SECURITY_TOKEN . '">';
	echo '<input type="file" name="file"><input type="submit" value="Upload">';
	echo '</form>';
}

// main routes incoming requests
function main()
{
	if (isset($_POST["route"]))
	{
		$route = $_POST["route"];
		if ($route === "fileupload")
		{
			fileUpload();
		}
	}
	else
	{
		// testing();
	}
}

try
{
	main();
}
catch (Exception $e)
{
	header("Content-type: text/plain");
	echo "Exception message: " . $e->getMessage() . "\n";
	echo $e->getTraceAsString();
	error_log("Exception: " . $e->getMessage());
}

/*
// For 4.3.0 <= PHP <= 5.4.0
if (!function_exists('http_response_code'))
{
    function http_response_code($newcode = NULL)
    {
        static $code = 200;
        if($newcode !== NULL)
        {
            header('X-PHP-Response-Code: '.$newcode, true, $newcode);
            if(!headers_sent())
                $code = $newcode;
        }       
        return $code;
    }
}
*/