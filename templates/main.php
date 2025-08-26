<?php
style(\OCA\HttpUploader\AppInfo\Application::APP_ID, 'largefileupload');
script(\OCA\HttpUploader\AppInfo\Application::APP_ID, 'largefileupload');
?>

<div class="upload-container">
    <h2>Large File Upload</h2>
    <form id="uploadForm">
        <input type="file" id="fileInput" required>
        <input type="text" id="targetPath" placeholder="Target folder path (e.g., /Documents)" value="">
        <button type="submit">Upload File</button>
    </form>
    <progress id="progressBar" value="0" max="100"></progress>
    <div id="status"></div>
</div>