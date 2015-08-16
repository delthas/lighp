<?php
namespace ctrl\backendApi\blog;

use core\http\HTTPRequest;

class BlogController extends \core\ApiBackController {
	public function executeInsertImage(HTTPRequest $req) {
		$config = $this->config()->read('backend');

		$publicDirname = __DIR__ . '/../../../public';
		$blogImgsDirname = 'img/blog/upload';

		if (!isset($_FILES['image'])) {
			throw new \RuntimeException('No file provided');
		}

		$fileData = $_FILES['image'];

		if ($fileData['error'] != 0) {
			throw new \RuntimeException('Cannot upload file: server error [#'.$fileData['error'].']');
		}

		$uploadInfo = pathinfo($fileData['name']);
		$uploadExtension = strtolower($uploadInfo['extension']);
		if (!in_array($uploadExtension, $config['uploads']['allowedExtensions'])) {
			throw new \RuntimeException('Cannot upload file: wrong image extension (provided: "'.$uploadExtension.'", accepted: "'.implode('", "', $config['uploads']['allowedExtensions']).'")');
		}

		if ($config['uploads']['randomizeFilename']) {
			do {
				$imageName = sha1(microtime() + rand(0, 100)) . '.' . $uploadExtension;
			} while(file_exists($publicDirname.'/'.$blogImgsDirname.'/'.$imageName));
		} else {
			$sanitizeFilename = function ($filename) {
				if (empty($filename)) {
					return null;
				}
				return preg_replace('/[^a-zA-Z0-9-]/', '_', $filename);
			};

			$imageName = $sanitizeFilename($uploadInfo['filename']);

			$postName = $sanitizeFilename($req->postData('postName'));
			if (empty($postName)) {
				$postName = '.';
			}
			$imageName = $postName . '/' . $imageName . '.' . $uploadExtension;
		}

		$uploadSource = $fileData['tmp_name'];
		$uploadDestDir = $publicDirname.'/'.$blogImgsDirname;
		$uploadDest = $uploadDestDir.'/'.$imageName;

		$uploadDirname = dirname($uploadDest);
		if (!is_dir($uploadDirname)) {
			mkdir($uploadDirname, 0777, true);
			chmod($uploadDirname, 0777);
		}

		$result = copy($uploadSource, $uploadDest);

		if ($result === false) {
			throw new \RuntimeException('Cannot upload file: error while copying file to "'.$uploadDest.'"');
		}

		chmod($uploadDest, 0777);

		$this->responseContent()->setData(array('path' => $blogImgsDirname.'/'.$imageName));
	}
}