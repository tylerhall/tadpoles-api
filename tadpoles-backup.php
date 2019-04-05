#!/usr/bin/php
<?PHP
    $email = 'user@domain.com'; // Your Tadpoles email address.
    $password = 'password'; // Your Tadpoles password.
    $kids = array('Alice', 'Bob'); // Put your kids' first names in this array, so we can skip downloading items where they're not the focus.
    $absolute_destination_folder = '/home/user/tadpoles-photos'; // Path to a folder where the downloaded items will be stored.
    $lat = ''; // Latitude of where photo was taken.
    $lng = ''; // Longitude of where photo was taken.

    date_default_timezone_set('America/Chicago'); // Set your local timezone so your photos are timestampped properly.

    // Login to Tadpoles and get an auth cookie.
    start($email, $password);

    // Here's how you'd download a bunch of months' worth of photos/videos...
    // download_all_attachments(2018, 2);
    // download_all_attachments(2018, 3);
    // download_all_attachments(2018, 4);
    // download_all_attachments(2018, 5);
    // download_all_attachments(2018, 6);
    // download_all_attachments(2018, 7);
    // download_all_attachments(2018, 8);
    // download_all_attachments(2018, 9);
    // download_all_attachments(2018, 10);
    // download_all_attachments(2018, 11);
    // download_all_attachments(2018, 12);
    // download_all_attachments(2019, 2);
    // download_all_attachments(2019, 3);
    // download_all_attachments(2019, 4);

    // Call this first to obtain the correct auth cookies to be able to call any other Tadpoles API method.
    function start($email, $password)
    {
        global $standard_headers;

        $standard_headers = array('Host: www.tadpoles.com', 'content-type: application/x-www-form-urlencoded; charset=utf-8', 'accept: */*', 'x-titanium-id: c5a5bca5-43c7-4b8f-b82a-fe1de0e4793c', 'x-requested-with: XMLHttpRequest', 'accept-language: en-us', 'user-agent: Appcelerator Titanium/7.1.1 (iPhone/12.2; iOS; en_US;), Appcelerator Titanium/7.1.1 (iPhone/12.2; iOS; en_US;) (gzip)');

        login($email, $password);
        admit();
    }

    // Login to the Tadpoles API.
    function login($email, $password)
    {
        global $standard_headers;
        global $cookie;

        $post_fields = array('service' => 'tadpoles', 'email' => $email, 'password' => $password);
        $response = curl('https://www.tadpoles.com/auth/login', $post_fields, true);

        preg_match('/^Set-Cookie:\s*(.+)="(.+)"/mi', $response, $cookies);
        $cookie_key = $cookies[1];
        $cookie_value = $cookies[2];
        $cookie = "$cookie_key=\"$cookie_value\"";
    }

    // This needs to be called immediately after login(). It takes the cookie returned by login()
    // and replaces it with a different (longer) cookie, which is required to make any other API requests.
    function admit()
    {
        global $standard_headers;
        global $cookie;

        $post_fields = array('state' => 'client', 'mac' => '00000000-0000-0000-0000-000000000000', 'os_name' => 'iphone', 'app_version' => '8.10.24', 'ostype' => '64bit', 'tz' => 'America/Chicago', 'battery_level' => '-1', 'locale' => 'en', 'logged_in' => '0', 'device_id' => '00000000-0000-0000-0000-000000000000', 'v' => '2');
        $response = curl('https://www.tadpoles.com/remote/v1/athome/admit', $post_fields, true);

        preg_match('/^Set-Cookie:\s*(.+)="(.+)"/mi', $response, $cookies);
        $cookie_key = $cookies[1];
        $cookie_value = $cookies[2];
        $cookie = "$cookie_key=\"$cookie_value\"";
    }

    // Generic function to grab data from Tadpole's API.
    // Automatically spoofs the HTTP headers to appear as if we're the Tadpoles iPhone app.
    // Uses the auth cookie obtained earlier in login() and admit().
    function curl($url, $post_fields = null, $return_headers = false)
    {
        global $standard_headers;
        global $cookie;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $standard_headers);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);

        if($return_headers == true) {
            curl_setopt($ch, CURLOPT_HEADER, 1);
        }

        if(!is_null($post_fields)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    // Get all the events for a given month.
    function events($year, $month)
    {
        $cursor = '';
        $events = array();

        // Tadpoles returns events spread across multiple pages of data.
        // Keep grabbing the next page until we've exhausted all available events.
        do {
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);

            $last_day = date('t', strtotime("$year-$month-01"));
            $last_day = str_pad($last_day, 2, '0', STR_PAD_LEFT);

            $earliest_ts = strtotime("$year-$month-01 00:00:00");
            $latest_ts = strtotime("$year-$month-$last_day 23:59:59");

            $params = array('num_events' => '100', 'state' => 'client', 'direction' => 'range', 'earliest_event_time' => $earliest_ts, 'latest_event_time' => $latest_ts, 'cursor' => $cursor);
            $jsonStr = curl('https://www.tadpoles.com/remote/v1/events?' . http_build_query($params));
            $json = json_decode($jsonStr);

            $events = array_merge($events, $json->events);
            $cursor = $json->cursor;
        } while(isset($json->cursor) && strlen($json->cursor) > 0);

        return $events;
    }

    // Downloads a full-resolution picture/video from Tadpoles.
    function download_attachment($key, $filename)
    {
        global $standard_headers;
        global $cookie;

        $fp = fopen($filename, 'w');

        $ch = curl_init('https://www.tadpoles.com/remote/v1/attachment?key=' . $key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $standard_headers);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_FILE, $fp);

        $response = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    // Download all pictures/videos for a given month and rename them based on their date and which kid is pictured.
    // Also, if exiftool is installed, set the exif date, lat/lng, and description and convert any png files to jpg.
    function download_all_attachments($year, $month)
    {
        global $kids;
        global $absolute_destination_folder;
        global $lat, $lng;

        $exiftool_path = shell_exec('which exiftool');
        if(strlen($exiftool_path) == 0) {
            unset($exiftool_path);
        }

        $events = events($year, $month);
        $count = count($events);
        foreach($events as $i => $e) {
            if($e->type == 'Activity') {
                foreach($e->new_attachments as $a) {
                    // Skip photos where our kids are not the primary child in the picture.
                    if(!in_array($e->member_display, $kids)) {
                        continue;
                    }

                    $description = $e->comment;
                    $date = date('Y-m-d H.i.s', $e->event_time);

                    // Parse Tadpoles' bizzare date format.
                    // NOTE: They don't seem to be sending this strange format any longer. Leaving this here just in case.
                    // $ts = explode('E', $e->create_time)[0];
                    // $ts = str_replace('.', '', $ts);
                    // $date = date('Y-m-d H.i.s', $ts);

                    // Build the filename: "folder/YYYY-mm-dd HH.mm.ss - Tadpoles - Kid Name.[jpg|mp4]"
                    $filename = $date . ' - Tadpoles - ' . $e->member_display;
                    if($a->mime_type == 'image/jpeg') {
                        $filename .= '.jpg';
                    } else if($a->mime_type == 'video/mp4') {
                        $filename .= '.mp4';
                    }
                    $filename = rtrim($absolute_destination_folder, '/') . '/' . $filename;

                    echo "# Downloading $i/$count: $filename\n";
                    download_attachment($a->key, $filename);

                    if(isset($exiftool_path)) {
                        set_exif_date($filename);
                        if(!empty($lat) && !empty($lng)) {
                            set_exif_coords($filename, $lat, $lng);
                        }
                        if(!empty($description)) {
                            set_exif_description($filename, $description);
                        }
                    }

                    // Set the file's modification date to match date taken for good measure.
                    touch($filename, strtotime($date));
                }
            }
        }
    }

    // Set the exif date based on the filename and convert any png files to jpg.
    function set_exif_date($filename) {
        echo "    Setting date for $filename\n";
        $cmd = "/usr/bin/exiftool -overwrite_original '-datetimeoriginal<filename' '$filename' 2>&1 1> /dev/null";
        $results = shell_exec($cmd);
        if(strpos($results, 'looks more like a PNG') !== false) {
            $results = shell_exec("/usr/bin/mogrify -format jpg '$filename'");
            $results = shell_exec($cmd);
        }
    }

    // Set the exif lat/lng and convert any png files to jpg.
    function set_exif_coords($filename, $lat, $lng) {
        echo "    Setting coords ($lat, $lng) for $filename\n";
        $cmd = "/usr/bin/exiftool -overwrite_original -XMP:GPSLongitude='$lng' -XMP:GPSLatitude='$lat' -GPSLongitudeRef='West' -GPSLatitudeRef='North' '$filename' 2>&1 1> /dev/null";
        $results = shell_exec($cmd);
        if(strpos($results, 'looks more like a PNG') !== false) {
            $results = shell_exec("/usr/bin/mogrify -format jpg '$filename'");
            $results = shell_exec($cmd);
        }
    }

    // Set the exif caption and convert any png files to jpg.
    function set_exif_description($filename, $description) {
        echo "    Setting description '$description' for $filename\n";
        $description = escapeshellarg($description);
        $cmd = "/usr/bin/exiftool -overwrite_original -Exif:ImageDescription=$description -IPTC:Caption-Abstract=$description -xmp:description=$description '$filename' 2>&1 1> /dev/null";
        $results = shell_exec($cmd);
        if(strpos($results, 'looks more like a PNG') !== false) {
            $results = shell_exec("/usr/bin/mogrify -format jpg '$filename'");
            $results = shell_exec($cmd);
        }
    }
