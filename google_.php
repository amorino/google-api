<?php

// Call set_include_path() as needed to point to your client library.
// set_include_path($_SERVER['DOCUMENT_ROOT'] . '/google/vendor/google/apiclient/');
// require_once 'Google/Client.php';
// require_once 'Google/Service/YouTube.php';
require __DIR__ . '/vendor/autoload.php';
session_start();
// session_destroy();

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = '1062087119350-1hm5s2sbamphgg2cce2n1hrq7b3adlc3.apps.googleusercontent.com';
$OAUTH2_CLIENT_SECRET = 'DTBY_TxYkh0ee6FN16gQwr13';
$REDIRECT = 'http://localhost:8888/google/google.php';
$APPNAME = "The Longest Breath";


$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$client->setRedirectUri($REDIRECT);
$client->setApplicationName($APPNAME);
$client->setAccessType('offline');
$client->setApprovalPrompt('force');


// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);


if (isset($_GET['code'])) {
    if (strval($_SESSION['state']) !== strval($_GET['state'])) {
        die('The session state did not match.');
    }

    $client->authenticate($_GET['code']);
    $_SESSION['token'] = $client->getAccessToken();

}

if (isset($_SESSION['token'])) {
    $client->setAccessToken($_SESSION['token']);
    print_r ($_SESSION['token']);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
    try {
        // Call the channels.list method to retrieve information about the
        // currently authenticated user's channel.
        $channelsResponse = $youtube->channels->listChannels('contentDetails', array(
            'mine' => 'true',
        ));

        $htmlBody = '';
        foreach ($channelsResponse['items'] as $channel) {
            // Extract the unique playlist ID that identifies the list of videos
            // uploaded to the channel, and then call the playlistItems.list method
            // to retrieve that list.
            $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

            $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
                'playlistId' => $uploadsListId,
                'maxResults' => 50
            ));

            $htmlBody .= "<h3>Videos in list $uploadsListId</h3><ul>";
            foreach ($playlistItemsResponse['items'] as $playlistItem) {
                $htmlBody .= sprintf('<li>%s (%s)</li>', $playlistItem['snippet']['title'],
                    $playlistItem['snippet']['resourceId']['videoId']);
            }
            $htmlBody .= '</ul>';
        }
    } catch (Google_ServiceException $e) {
        $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
        $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    }

    $_SESSION['token'] = $client->getAccessToken();
} else {
    $state = mt_rand();
    $client->setState($state);
    $_SESSION['state'] = $state;

    $authUrl = $client->createAuthUrl();
    $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorise access</a> before proceeding.<p>
END;
}
?>

<!doctype html>
<html>
<head>
    <title>My Uploads</title>
</head>
<body>
<?php echo $htmlBody?>
</body>
</html>
