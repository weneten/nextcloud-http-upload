<?php
namespace OCA\HttpUploader\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\IUserSession;

class UploadController extends Controller {
    private $userFolder;
    private $userId;

    public function __construct($appName, IRequest $request, IRootFolder $rootFolder, IUserSession $userSession) {
        parent::__construct($appName, $request);
        $this->userFolder = $rootFolder->getUserFolder($userSession->getUser()->getUID());
        $this->userId = $userSession->getUser()->getUID();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function uploadChunk() {
        $fileName = $this->request->getParam('fileName');
        $chunkIndex = (int)$this->request->getParam('chunkIndex');
        $totalChunks = (int)$this->request->getParam('totalChunks');
        $chunk = $this->request->getUploadedFile('chunk');

        if (!$fileName || !$chunk || $chunk['error'] !== UPLOAD_ERR_OK) {
            return new DataResponse(['error' => 'Invalid chunk upload'], Http::STATUS_BAD_REQUEST);
        }

        $tempDir = \OC::$server->getTempManager()->getTemporaryFolder() . 'http_uploader_' . $this->userId . '_' . $fileName;
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $chunkPath = $tempDir . '/chunk_' . $chunkIndex;
        move_uploaded_file($chunk['tmp_name'], $chunkPath);

        return new DataResponse(['status' => 'chunk_uploaded', 'chunkIndex' => $chunkIndex]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function assembleFile() {
        $fileName = $this->request->getParam('fileName');
        $totalChunks = (int)$this->request->getParam('totalChunks');
        $targetPath = $this->request->getParam('targetPath', '');

        $tempDir = \OC::$server->getTempManager()->getTemporaryFolder() . 'http_uploader_' . $this->userId . '_' . $fileName;
        $finalPath = $this->userFolder->getPath() . '/' . $targetPath . '/' . $fileName;

        try {
            $finalFile = $this->userFolder->newFile($targetPath . '/' . $fileName);
            $finalStream = $finalFile->fopen('w');

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . '/chunk_' . $i;
                if (!file_exists($chunkPath)) {
                    fclose($finalStream);
                    $finalFile->delete();
                    return new DataResponse(['error' => 'Missing chunk ' . $i], Http::STATUS_BAD_REQUEST);
                }

                $chunkStream = fopen($chunkPath, 'r');
                stream_copy_to_stream($chunkStream, $finalStream);
                fclose($chunkStream);
                unlink($chunkPath);
            }

            fclose($finalStream);
            rmdir($tempDir);

            return new DataResponse(['status' => 'file_assembled', 'path' => $finalPath]);
        } catch (\Exception $e) {
            return new DataResponse(['error' => 'Failed to assemble file: ' . $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}