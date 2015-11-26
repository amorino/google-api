<?php

header('Access-Control-Allow-Origin: *');
$allowedExts = array('mp4', 'MP4', 'wma', 'png', 'MOV', 'mov', 'WEBM', 'webm');
$extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

$name = $_POST['text'];

if ((($_FILES['file']['type'] == 'video/mp4')
|| ($_FILES['file']['type'] == 'video/webm')
|| ($_FILES['file']['type'] == 'video/quicktime'))
&& ($_FILES['file']['size'] < 20000000)) {
    // && in_array($extension, $allowedExts)) {
    $id = md5(uniqid(rand(), true));
    if ($_FILES['file']['error'] > 0) {
        echo 'Return Code: '.$_FILES['file']['error'].'<br />';
    } else {
        if (file_exists('upload/'.$_FILES['file']['name'])) {
            $videoPath = 'upload/'.$id.'.'.$extension;
        } else {
            move_uploaded_file($_FILES['file']['tmp_name'], 'upload/'.$id.'.'.$extension);
            $videoPath = 'upload/'.$id.'.'.$extension;
        }
    }

    $key = file_get_contents('the_key.txt');

    require __DIR__.'/vendor/autoload.php';

    $application_name = 'The Longest Breath';
    $client_secret = '0ZGp--7Zf2ZSreq2MkaVCUME';
    $client_id = '732167316501-kr1oq3v4sdeiffhq8vqb7m0mos5thq4h.apps.googleusercontent.com';
    $scope = array('https://www.googleapis.com/auth/youtube.upload', 'https://www.googleapis.com/auth/youtube', 'https://www.googleapis.com/auth/youtubepartner');

    // $videoPath = 'upload/'.$_FILES['file']['name'];
    $videoTitle = 'The Longest Breath';
    $videoDescription = '';
    $videoCategory = '22';
    $videoTags = array('thelongestbreath', 'amazonas');

    try {
        // Client init
        $client = new Google_Client();
        $client->setApplicationName($application_name);
        $client->setClientId($client_id);
        $client->setAccessType('offline');
        $client->setAccessToken($key);
        $client->setScopes($scope);
        $client->setClientSecret($client_secret);

        if ($client->getAccessToken()) {
            /*
         * Check to see if our access token has expired. If so, get a new one and save it to file for future use.
         */
        if ($client->isAccessTokenExpired()) {
            $newToken = $client->getAccessToken();
            $client->refreshToken($newToken->refresh_token);
            // file_put_contents('the_key.txt', $client->getAccessToken());
        }

            $youtube = new Google_Service_YouTube($client);

        // Create a snipet with title, description, tags and category id
        $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($name);
            $snippet->setDescription($videoDescription);
            $snippet->setCategoryId($videoCategory);
            $snippet->setTags($videoTags);

        // Create a video status with privacy status. Options are "public", "private" and "unlisted".
        $status = new Google_Service_YouTube_VideoStatus();
            $status->setPrivacyStatus('unlisted');

        // Create a YouTube video with snippet and status
        $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

        // Size of each chunk of data in bytes. Setting it higher leads faster upload (less chunks,
        // for reliable connections). Setting it lower leads better recovery (fine-grained chunks)
        $chunkSizeBytes = 1 * 1024 * 1024;

        // Setting the defer flag to true tells the client to return a request which can be called
        // with ->execute(); instead of making the API call immediately.
        $client->setDefer(true);

        // Create a request for the API's videos.insert method to create and upload the video.
        $insertRequest = $youtube->videos->insert('status, snippet', $video);

        // Create a MediaFileUpload object for resumable uploads.
        $media = new Google_Http_MediaFileUpload(
            $client,
            $insertRequest,
            'video/*',
            null,
            true,
            $chunkSizeBytes
        );
            $media->setFileSize(filesize($videoPath));

        // Read the media file and upload it chunk by chunk.
            $status = false;
            $handle = fopen($videoPath, 'rb');
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }

            fclose($handle);

        /*
         * Video has successfully been upload, now lets perform some cleanup functions for this video
         */
        if ($status->status['uploadStatus'] == 'uploaded') {
            echo 'The video was uploaded';
        }

        // If you want to make other calls after the file upload, set setDefer back to false
        $client->setDefer(true);
        } else {
            echo 'Problems creating the client';
        }
    } catch (Google_Service_Exception $e) {
        print 'Caught Google service Exception '.$e->getCode().' message is '.$e->getMessage();
        print 'Stack trace is '.$e->getTraceAsString();
    } catch (Exception $e) {
        print 'Caught Google service Exception '.$e->getCode().' message is '.$e->getMessage();
        print 'Stack trace is '.$e->getTraceAsString();
    }
} else {
    echo 'Invalid file';
}
