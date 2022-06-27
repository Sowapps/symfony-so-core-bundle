<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller;

use DateTime;
use InvalidArgumentException;
use Sowapps\SoCore\Core\Controller\AbstractController;
use Sowapps\SoCore\Entity\File;
use Sowapps\SoCore\Service\ControllerService;
use Sowapps\SoCore\Service\FileService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FileController extends AbstractController {
	
	const ACTION_DISPLAY = 'display';
	const ACTION_DOWNLOAD = 'download';
	
	private FileService $fileService;
	
	/**
	 * FileController constructor
	 *
	 * @param ControllerService $controllerService
	 * @param FileService $fileService
	 */
	public function __construct(ControllerService $controllerService, FileService $fileService) {
		parent::__construct($controllerService);
		$this->fileService = $fileService;
	}
	
	public function download(File $file, string $key, string $extension, string $action): Response {
		$download = $action === self::ACTION_DOWNLOAD;
		
		if( $key !== $file->getPrivateKey() ) {
			throw new FileException();
		}
		
		if( $extension !== $file->getExtension() ) {
			throw new FileException();
		}
		
		// Close session before downloading file to unlock it
		session_write_close();
		
		$headers = [];
		
		if( $file->getMimeType() === 'image/svg' ) {
			// Hardcode mime type mapping alias for SVG to render in browsers
			$headers['Content-Type'] = 'image/svg+xml';
		}
		
		$response = new BinaryFileResponse($this->fileService->getLocalFile($file), 200, $headers, true);
		$response->setContentDisposition($download ? 'attachment' : 'inline', $file->getOutputName() ?: $file->getName());
		
		return $response;
	}
	
	public function upload(Request $request, string $purpose): JsonResponse {
		var_dump($purpose);
		die('TEST {WESH}');
		$uploadedFile = $request->files->get('file');
		$file = $this->fileService->upload($uploadedFile, $purpose, new DateTime('+1 day'));
		if( !$file ) {
			throw new InvalidArgumentException();
		}
		
		return $this->json([
			'file' => $file,
		]);
	}
	
}
