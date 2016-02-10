var xhr = new XMLHttpRequest();
xhr.open("GET", "https://www.codecademy.com/", false);
// Add your code below!
xhr.send();<?php

// Replace with your access token
$ACCESS_TOKEN = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';

// Replace with your bot name and email/website to contact if there is a problem
// e.g., "mybot/0.1 (https://erikberg.com/)"
$USER_AGENT = 'xxxxxxxx';

// set time zone to use for output
$TIME_ZONE = 'America/New_York';

// PHP complains if time zone is not set
date_default_timezone_set($TIME_ZONE);

main();

function main()
{
    global $ACCESS_TOKEN, $USER_AGENT;

    // Set the API sport, method, id, format, and any parameters
    $host   = 'erikberg.com';
    $sport  = '';
    $method = 'events';
    $id     = '';
    $format = 'json';
    $parameters = array(
        'sport' => 'nba',
        'date'  => '20130414'
    );

    // Pass method, format, and parameters to build request url
    $url = buildURL($host, $sport, $method, $id, $format, $parameters);

    // Set the User Agent, Authorization header and allow gzip
    $default_opts = array(
        'http' => array(
            'user_agent' => $USER_AGENT,
            'header'     => array(
                'Accept-Encoding: gzip',
                'Authorization: Bearer ' . $ACCESS_TOKEN
            )
        )
    );
    stream_context_get_default($default_opts);
    $file = 'compress.zlib://' . $url;
    $fh   = fopen($file, 'rb');
    if ($fh && strpos($http_response_header[0], "200 OK") !== false) {
        $content = stream_get_contents($fh);
        fclose($fh);
        printResult($content);
    } else {
        // handle error, check $http_response_header for HTTP status code, etc.
        if ($fh) {
            $xmlstats_error = json_decode(stream_get_contents($fh));
            printf("Server returned %s error along with this message:\n%s\n",
                $xmlstats_error->error->code,
                $xmlstats_error->error->description);
        } else {
            print "A problem was encountered trying to connect to the server!\n";
            print_r(error_get_last());
        }
    }
}

function printResult($content)
{
    global $TIME_ZONE;

    // Parses the JSON content and returns a reference to
    // Events (https://erikberg.com/api/methods/events)
    $events = json_decode($content);

    // Create DateTime object using the ISO 8601 formatted events_date
    $date = DateTime::createFromFormat(DateTime::W3C, $events->events_date);

    printf("Events on %s\n\n", $date->format('l, F j, Y'));
    printf("%-35s %5s %34s\n", 'Time', 'Event', 'Status');

    // Loop through each Event (https://erikberg.com/api/objects/event)
    foreach ($events->event as $evt) {
        // Create DateTime object from start_date_time and set the desired time zone
        $time = DateTime::createFromFormat(DateTime::W3C, $evt->start_date_time);
        $time->setTimeZone(new DateTimeZone($TIME_ZONE));

        // Get team objects (https://erikberg.com/api/objects/team)
        $awayTeam = $evt->away_team;
        $homeTeam = $evt->home_team;

        printf("%12s %24s vs. %-24s %9s\n",
            $time->format('g:i A T'),
            $awayTeam->full_name,
            $homeTeam->full_name,
            $evt->event_status);
    }
}

// See https://erikberg.com/api/methods Request URL Convention for
// an explanation
function buildURL($host, $sport, $method, $id, $format, $parameters)
{
    $ary  = array($sport, $method, $id);
    $path = join('/', preg_grep('/^$/', $ary, PREG_GREP_INVERT));
    $url  = 'https://' . $host . '/' . $path . '.' . $format;

    // Check for parameters and create parameter string
    if (!empty($parameters)) {
        $paramlist = array();
        foreach ($parameters as $key => $value) {
            array_push($paramlist, rawurlencode($key) . '=' . rawurlencode($value));
        }
        $paramstring = join('&', $paramlist);
        if (!empty($paramlist)) { $url .= '?' . $paramstring; }
    }
    return $url;
}

