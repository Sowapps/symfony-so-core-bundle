<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Controller;

use Sowapps\SoCoreBundle\Entity\File;
use Sowapps\SoCoreBundle\Service\FileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;

class FileController extends AbstractController {
	
	const ACTION_DISPLAY = 'display';
	const ACTION_DOWNLOAD = 'download';
	
	public function download(File $file, string $key, string $extension, string $action, FileService $fileService): Response {
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
		
		$response = new BinaryFileResponse($fileService->getLocalFile($file), 200, $headers, true);
		$response->setContentDisposition($download ? 'attachment' : 'inline', $file->getOutputName() ?: $file->getName());
		
		return $response;
	}
	
}
