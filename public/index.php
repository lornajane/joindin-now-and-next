<?php

require "../vendor/autoload.php";

// configure this for anything that isn't OggCamp 2019
$event_url = "https://api.joind.in/v2.1/events/7477";

$date = new DateTimeImmutable();
// use this to test if you'd like to
$date = new DateTimeImmutable('20th October 2019 11:40');

// now don't edit anything else
$client = new GuzzleHttp\Client();
$opts = [];
$res = $client->request('GET', $event_url . "/talks?resultsperpage=120", $opts);

if ($res->getStatusCode() == 200) {
    $data = json_decode($res->getBody(), true);

    // separate the talks by track so we have an array of tracks with talks in each one
    // and talks are indexed by their start time
    $talks_by_track = [];
    foreach ($data['talks'] as $talk) {
        $talk['start_time'] = new DateTimeImmutable($talk['start_date']); // a DateTime representation
        // calculate a finish time
        $talk['finish_time'] = $talk['start_time']->add(new DateInterval('PT' . $talk['duration'] . 'M')); // also DateTime

        if ($talk['tracks'][0]['track_name']) {
            $talks_by_track[$talk['tracks'][0]['track_name']][$talk['start_date']] = $talk;
        }
    }

    // sort the talks within each track by date
    foreach ($talks_by_track as $track => $talks) {
        ksort($talks_by_track[$track]);
    }

    // now eliminate everything that has already finished
    foreach ($talks_by_track as $track => $talks) {
        foreach ($talks as $key => $talk) {
            // has this already finished? remove it!
            if ($date > $talk['finish_time']) {
                unset($talks_by_track[$track][$key]);
            }
        }
    }

    // so now the first item in each track should be "now" and the next one should be "next"
    // or if the first talk listed hasn't started yet, it is just "next"
    // beware very interesting PHP array logic
    $now = [];
    $next = [];
    $track_now_next = [];
    foreach ($talks_by_track as $track => $talks) {
        reset($talks);
        $talk = current($talks);
        // do we have a "now"?
        if ($date > $talk['start_time']) {
            $now[$track] = $talk;
            $track_now_next[$track]["now"] = $talk;
            $talk = next($talks);
        }
        // how about a next? Only if it starts in the next hour or so
        if ($date->add(new DateInterval('PT75M')) > $talk['start_time']) {
            $next[$track] = $talk;
            $track_now_next[$track]["next"] = $talk;
        }
    }
}

function getTalkTime($talk) {
    return $talk['start_time']->format('H:i');
}

function getTalk($talk) {
    $display = $talk['talk_title'];
    if($talk['speakers']) {
        $display .= " (";
        $i = 0;
        foreach ($talk['speakers'] as $speaker) {
            if ($i > 0) {
                $display .= ", ";
            }
            $display .= $speaker['speaker_name'];
            $i++;
        }
        $display .= ")";
    }
    return $display;
}

?>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="css/styles.css">
</head>
<body>
<div class="navBar">
<div class="logo"></div>
</div>

<?php foreach ($track_now_next as $track => $talks): ?>
<div class="container">
<h3><?=$track?></h3>

<?php foreach ($talks as $when => $talk): ?>
<li><b><?=$when?></b> (<?=getTalkTime($talk)?>) <b><?=$track?>: </b><?=getTalk($talk)?></li>
<?php endforeach; ?>
</div>
<?php endforeach; ?>

</body>
