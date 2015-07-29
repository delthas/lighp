<?php
namespace ctrl\backendApi\blog;

class BlogController extends \core\ApiBackController {
	public function executeInsertImage() {
		$config = $this->config()->read('backend');

		$publicDirname = __DIR__ . '/../../../public';
		$blogImgsDirname = 'img/blog/upload';

		if (!isset($_FILES['image'])) {
			throw new \RuntimeException('No file provided');
		}

		$fileData = $_FILES['image'];

		if ($fileData['error'] != 0) {
			throw new \RuntimeException('Cannot upload file : server error [#'.$fileData['error'].']');
		}

		$uploadInfo = pathinfo($fileData['name']);
		$uploadExtension = strtolower($uploadInfo['extension']);
		if (!in_array($uploadExtension, $config['uploads']['allowedExtensions'])) {
			throw new \RuntimeException('Cannot upload file : wrong image extension (provided : "'.$uploadExtension.'", accepted : "'.implode('", "', $config['uploads']['allowedExtensions']).'")');
		}

		do {
			$imageName = sha1(microtime() + rand(0, 100)) . '.' . $uploadExtension;
		} while(file_exists($publicDirname.'/'.$blogImgsDirname.'/'.$imageName));

		$uploadSource = $fileData['tmp_name'];
		$uploadDestDir = $publicDirname.'/'.$blogImgsDirname;
		$uploadDest = $uploadDestDir.'/'.$imageName;

		if (!is_dir($uploadDestDir)) {
			mkdir($uploadDestDir, 0777, true);
			chmod($uploadDestDir, 0777);
		}

		$result = copy($uploadSource, $uploadDest);

		if ($result === false) {
			throw new \RuntimeException('Cannot upload file : error while copying file to "'.$uploadDest.'"');
		}

		chmod($uploadDest, 0777);

		$this->responseContent()->setData(array('path' => $blogImgsDirname.'/'.$imageName));
	}
}