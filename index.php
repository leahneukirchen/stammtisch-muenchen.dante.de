<!DOCTYPE html>
<html lang="de">
<?php

$weekdays = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
$months = ['???', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

$db = new SQLite3(":memory:");

function load_table($base) {
  global $db;
  if (($handle = fopen($base . ".csv", "r")) !== FALSE) {
    $header = fgetcsv($handle, 0, ";");
    $create = "CREATE TABLE $base (";
    $insert = "INSERT INTO $base VALUES (";
    foreach ($header as $index => $field) {
      if ($index > 0) {
        $create .= ", ";
        $insert .= ", ";
      }
      $create .= strtolower($field);
      $insert .= ":" . strtolower($field);
    }
    $create .= ")";
    $insert .= ")";
    $db->exec($create);
    $ins = $db->prepare($insert);
    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
      $ins->reset();
      foreach ($data as $index => $value) {
        $ins->bindValue($index+1, $value);
      }
      $ins->execute();
    }
    fclose($handle);
  }
}

load_table("dates");
load_table("addresses");

?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TeX-Stammtisch in München</title>
  <style>
body {
  background-color: #f7f7f7;
  color: #333333;
  font-family: "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
  font-size: 16px;
  width: 100%;
  margin: 0;
  padding: 0;
}

a:link, a:visited {
  color: #424242;
}

a:hover, a:active {
  color: #555555;
}

h1 {
  font-size: 1.5em;
  padding: 1.5em 0 0 0;
}

h2 {
  font-size: 1.25em;
}

main {
  background: #ffffff;
  -webkit-box-shadow: 0 10px 20px 0 rgba(236, 236, 236, 0.86);
          box-shadow: 0 10px 20px 0 rgba(236, 236, 236, 0.86);
  max-width: 800px;
  margin: 60px auto;
}

.content {
  max-width: 740px;
  height: auto;
  margin: 0 auto;
  padding: 0 20px 20px 20px;
}

.headerimg {
  background-image: url(./header.jpeg);
  background-size: cover;
  background-repeat: no-repeat;
  width: 100%;
  height: 130px;
  margin: 25px 0 1em 0;
}

footer {
  font-style: italic;
  text-align: right;
}

  </style>
</head>

<body>
<main>
<div class="content">
<h1>TeX-Stammtisch in München</h1>
<div class="headerimg"></div>

<h2>Unser Thema</h2>

<p>
Unser Stammtisch ist ein offener Treff für alle, die an Diskussion und
Information über das Textsatzsystem TeX und sein Umfeld und über
Schriftsatz, Typografie und Druckereiwesen in irgendeiner Weise
interessiert sind.  Willkommen sind vor allem auch Einsteiger auf der
Suche nach Tipps, Erfahrungen und Ratschlägen zum Einsatz von LaTeX
und anderen TeX-Formaten.
</p>

<h2 id="next">Nächster Termin</h2>

<?php
$next = $db->querySingle("SELECT strftime('%w', date) as weekday,
                                 strftime('%H:%M', date) as time,
                                 strftime('%d', date) as day,
                                 strftime('%m', date) as month,
                                 strftime('%Y', date) as year,
                                 datetime(date) as datetime,
                                 signup,
                                 name,
                                 address,
                                 city,
                                 detail,
                                 url
                          FROM dates
                          INNER JOIN addresses ON location = id
                          WHERE date > date('now')
                          ORDER BY date
                          LIMIT 1", true);

if ($next) {

$json = [
  "@context" => "https://schema.org",
  "@type" => "Event",
  "name" => "TeX-Stammtisch München",
  "startDate" => $next["datetime"],
  "eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
  "eventStatus" => "https://schema.org/EventScheduled",
  "location" => [
    "@type" => "Place",
    "name" => $next["name"],
    "address" => [
      "@type" => "PostalAddress",
      "streetAddress" => $next["address"],
      "addressLocality" => $next["city"],
    ]
  ],
  "organizer" => [
    "@type" => "Person",
    "name" => "Leah Neukirchen",
  ],
];

?>

<script type="application/ld+json">
<?= json_encode($json) . PHP_EOL ?>
</script>

<p>
Am <?= $weekdays[$next["weekday"]] ?>, den <?= 0+$next["day"] ?>. <?= $months[$next["month"]] ?> <?= $next["year"] ?> ist wieder TeX-Stammtisch in München.
</p>

<p>
Wir treffen uns um <?= $next["time"] ?> Uhr hier:
</p>

<blockquote>
<?= $next["name"] ?><br>
<?= $next["address"] ?><br>
<?= $next["city"] ?><br>
<?= $next["detail"] ?><br>
<a href="<?= $next["url"] ?>"><?= $next["url"] ?></a><br>
</blockquote>

<?php
if ($next["signup"]) {
?>
<p>
Um ausreichend Plätze reservieren zu können, wird eine Rückmeldung unter
<a href="<?= $next["signup"] ?>"><?= $next["signup"] ?></a>
erbeten.
</p>
<?php
}
?>

<p>
Alle, die sich für TeX, LaTeX und guten Schriftsatz interessieren, sind
herzlich eingeladen.
</p>

<?php
$res = $db->query("SELECT strftime('%w', date) as weekday,
                          date(date) as date
                          FROM dates
                          WHERE date > date('now')
                          ORDER BY date
                          LIMIT 5
                          OFFSET 1");
if ($res->fetchArray()) {
  $res->reset();
?>

<h2>Terminvorschau</h2>

Außer dem oben angegebenen nächsten Termin sind die bislang geplanten weiteren
Termine:

<ul>
<?php
while ($row = $res->fetchArray()) {
?>
<li><?= $weekdays[$row["weekday"]] ?>, <?= $row["date"] ?></li>
<?php
}
?>
</ul>

<?php
}

} else {
?>
Derzeit steht kein nächster Termin fest.
<?php
}
?>

<h2>Vergangene Termine</h2>

<ul>
<?php
$res = $db->query("SELECT strftime('%w', date) as weekday,
                          date(date) as date,
                          location
                   FROM dates
                   WHERE date < date('now')
                   ORDER BY date DESC");
while ($row = $res->fetchArray()) {
?>
<li><?= $weekdays[$row["weekday"]] ?>, <?= $row["date"] ?> im <?= $row["location"] ?></li>
<?php
}
?>
</ul>

<h2 id="about">Über den Münchner TeX-Stammtisch</h2>

<p>
Unser Stammtisch wurde im Oktober 1998 von Michael Niedermair gegründet,
lange Zeit von Uwe Siart geleitet,
und wird gegenwärtig von <a href="mailto:leah@vuxu.org">Leah Neukirchen</a>
organisiert.
</p>

<p>
Wir treffen uns alle zwei Monate in der ersten Woche der geradzahligen Monate,
jeweils um 19 Uhr.
Dabei wechseln wir den Wochentag zyklisch von Dienstag bis Donnerstag.
Falls der Termin wirklich einmal auf einen Feiertag trifft oder in
allgemeine Urlaubszeit fällt, weichen wir auf die zweite Woche aus.
</p>

<h2 id="list">Mailingliste</h2>

<p>
Für die Stammtischteilnehmer und sonstige Interessierte gibt es auf dem Server
von <a href="https://dante.de">Dante e. V.</a>
eine geschlossene Mailingliste. Es gibt dort Ankündigungen des
jeweils nächsten Stammtischtermins und diverse Diskussionen unter den
Mitgliedern. Wer in diese Liste aufgenommen werden möchte,
kann sich <a href="https://lists.dante.de/mailman/listinfo/stammtisch-muenchen">online anmelden</a>.
</p>

<h2 id="list">Bayerischer TeX-Stammtisch</h2>

<p>
Es gibt in Bayern zwei TeX-Stammtische – in Erlangen und in
München.  Ursprünglich trafen sich die Mitglieder beider Stammtische
einmal jährlich abwechselnd im Raum Nürnberg und in München zu
gemeinsamen Vorträgen, Diskussion und Erfahrungsaustausch mit
anschließendem gemütlichen Beisammensein.  Die Teilnehmerzahl und das
Einzugsgebiet wuchsen.  Inzwischen wurde daraus ein jährliches Treffen
von TeX-Interessierten aus dem (typischerweise aber nicht
notwendigerweise) süddeutschen Raum, das von den Mitgliedern der
Stammtische Erlangen und München veranstaltet wird.
</p>

<p>
Der bayrische TeX-Stammtisch hat inzwischen
<a href="https://baytex.dante.de/">seine eigene Website baytex.dante.de</a>,
einen Überblick über vergangene Stammtische gibts
<a href="https://www.ks-ingenieurconsult.de/TeX/Stammtisch.html">hier</a>.
</p>

<footer>
Letzte Aktualisierung: <?= strftime("%Y-%m-%d %H:%M") ?> &middot;
<a href="https://www.dante.de/impressum/">Impressum</a>
</footer>

</div>
</main>
</body>
