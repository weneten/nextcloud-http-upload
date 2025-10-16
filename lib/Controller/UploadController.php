<?php
namespace OCA\HttpUploader\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\ITempManager; // New dependency

class UploadController extends Controller {
    private IRootFolder $rootFolder; // Keep rootFolder for getUserFolder
    private string $userId;
    private ITempManager $tempManager; // New property

    public function __construct($appName, IRequest $request, IRootFolder $rootFolder, IUserSession $userSession, ITempManager $tempManager) {
        parent::__construct($appName, $request);
        $this->rootFolder = $rootFolder;
        $this->userId = $userSession->getUser()->getUID();
        $this->tempManager = $tempManager; // Assign new dependency
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

        // Use ITempManager to get a temporary folder
        $tempDir = $this->tempManager->getTemporaryFolder() . '/http_uploader_' . $this->userId . '_' . md5($fileName); // Use md5 for unique temp dir per file
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

        // Sanitize targetPath to prevent directory traversal issues
        $targetPath = trim($targetPath, '/');
        if (str_contains($targetPath, '..')) {
            return new DataResponse(['error' => 'Invalid target path'], Http::STATUS_BAD_REQUEST);
        }

        $tempDir = $this->tempManager->getTemporaryFolder() . '/http_uploader_' . $this->userId . '_' . md5($fileName); // Consistent temp dir name
        
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            // Ensure target folder exists or create it
            $targetFolder = $userFolder;
            if (!empty($targetPath)) {
                try {
                    $targetFolder = $userFolder->getFolder($targetPath);
                } catch (\OCP\Files\NotFoundException $e) {
                    // Folder does not exist, create it
                    $targetFolder = $userFolder->newFolder($targetPath);
                }
            }

            // Create the new file in the target folder
            $finalFile = $targetFolder->newFile($fileName);
            $finalStream = $finalFile->fopen('w');

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . '/chunk_' . $i;
                if (!file_exists($chunkPath)) {
                    fclose($finalStream);
                    $finalFile->delete(); // Clean up partially created file
                    // Attempt to clean up temp dir if possible
                    if (is_dir($tempDir)) {
                        array_map('unlink', glob("$tempDir/*"));
                        rmdir($tempDir);
                    }
                    return new DataResponse(['error' => 'Missing chunk ' . $i], Http::STATUS_BAD_REQUEST);
                }

                $chunkStream = fopen($chunkPath, 'r');
                stream_copy_to_stream($chunkStream, $finalStream);
                fclose($chunkStream);
                unlink($chunkPath); // Delete chunk after it's copied
            }

            fclose($finalStream);
            
            // Clean up the temporary directory
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }

            return new DataResponse(['status' => 'file_assembled', 'path' => $finalFile->getPath()]);
        } catch (\Exception $e) {
            // Attempt to clean up temp dir on error
            if (is_dir($tempDir)) {
                array_map('unlink', glob("$tempDir/*"));
                rmdir($tempDir);
            }
            return new DataResponse(['error' => 'Failed to assemble file: ' . $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}