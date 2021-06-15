<?php
require_once("../settings.php");

try
{
    if(array_key_exists("noSkip", $_GET))
        $skipDomains = array();
    else
        $skipDomains = array("graphia.app");

    if(array_key_exists("skipDomains", $_GET))
    {
        $skipDomainsText = $_GET["skipDomains"];
        $skipDomainsText = preg_replace("/[^a-zA-Z\.\s]/", "", $skipDomainsText);
        $skipDomainsText = preg_replace("/([^\s]+)\s+([^\s]+)/", "$1 $2", $skipDomainsText);
        $skipDomainsText = preg_replace("/^\s*([^\s]*)\s*$/", "$1", $skipDomainsText);

        if($skipDomainsText !== "")
        {
            $userSkipDomains = explode(" ", $skipDomainsText);
            $skipDomains = array_merge($skipDomains, $userSkipDomains);
        }
    }
    else
        $skipDomainsText = "";

    // Always true, so that if no domains are added, the fragment is still valid
    $skipDomainQueryFragment = "1";
    foreach($skipDomains as $skipDomain)
    {
        if(strlen($skipDomainQueryFragment) !== 0)
            $skipDomainQueryFragment .= " AND ";

        $skipDomainQueryFragment .= "email NOT LIKE '%$skipDomain'";
    }

    $db = database();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $select = "SELECT time FROM log WHERE $skipDomainQueryFragment ORDER BY time ASC LIMIT 1";
    $statement = $db->prepare($select);
    $statement->execute();
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    if($row != NULL)
        $earliestRow = $row['time'];
    else
        $earliestRow = 0;

    $earliestDate = date("Y-m-d", $earliestRow);
    $defaultFromDate = date("Y-m-d", strtotime("-30 days"));
    if($defaultFromDate < $earliestDate)
        $defaultFromDate = $earliestDate;
}
catch(Exception $e)
{
    echo $e->getMessage();
}

if(array_key_exists("from-date", $_GET) && strtotime($_GET["from-date"]))
    $fromDate = $_GET["from-date"];
else
    $fromDate = $defaultFromDate;

if(array_key_exists("to-date", $_GET) && strtotime($_GET["to-date"]))
    $toDate = $_GET["to-date"];
else
    $toDate = date("Y-m-d");

$fromTime = strtotime($fromDate);
$toTime = strtotime("$toDate + 1 day");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Tracking Log</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<style>
.highlight1 { background-color: firebrick; color: white; }
.highlight2 { background-color: chocolate; color: white; }
.highlight3 { background-color: gold; color: black; }
</style>
</head>
<body>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<div class="content">

<?php
try
{
    $select = "SELECT product, COUNT(product) FROM log " .
        "WHERE time BETWEEN $fromTime AND $toTime " .
        "AND $skipDomainQueryFragment " .
        "GROUP BY product " .
        "ORDER BY COUNT(product) DESC";
    $productStatement = $db->prepare($select);
    $productStatement->execute();
    $emailCounts = array();
    $emailSpans = array();
?>

<nav id="auth-navbar" class="navbar sticky-top navbar-light bg-light">
<a class="navbar-brand" href="#">Tracking Log</a>
<ul class="nav nav-pills">

<?php
    while($row = $productStatement->fetch(PDO::FETCH_ASSOC))
    {
        $product = $row['product'];
        echo "<li class=\"nav-item\">";
        echo "<a class=\"nav-link btn btn-outline\" href=\"#$product\">$product</a>";
        echo "</li>";
    }
}
catch(Exception $e)
{
    echo $e->getMessage();
}
?>

</ul>

<form class="form-inline" action="#">
Date Range:
<?php
echo "<input class=\"form-control\" type=\"date\" name=\"from-date\" min=\"$earliestDate\" value=\"$fromDate\"> - ";
echo "<input class=\"form-control\" type=\"date\" name=\"to-date\" min=\"$earliestDate\" value=\"$toDate\">";
?>
&nbsp;
Skip Domains:
<?php
echo "<input class=\"form-control\" type=\"text\" name=\"skipDomains\" value=\"$skipDomainsText\">";
?>
&nbsp;
  <input class="btn btn-primary" type="submit" value="Apply">
</form>


</nav>

<script>
function lookupIp(ip, ipClass)
{
    var req = new XMLHttpRequest();
    req.addEventListener("load", function()
    {
        var hostname = this.responseText;

        var elements = document.getElementsByClassName(ipClass);
        for(var i = 0; i < elements.length; i++)
        {
            var element = elements[i];
            element.innerText = hostname;
        }
    });

    req.open("GET", "reversednslookup.php?ip=" + ip);
    req.send();
}
</script>

<?php

function summariseList($list, $outputFunction)
{
    $maxElements = 4;
    $otherPercent = 0.0;
    $numElements = 0;

    $totalCount = 0;
    foreach($list as $element => $elementCount)
        $totalCount += $elementCount;

    foreach($list as $element => $elementCount)
    {
        $percent = ($elementCount / $totalCount) * 100;

        if($numElements < $maxElements)
        {
            $roundedPercent = round(($elementCount / $totalCount) * 100);
            $outputFunction($element, $roundedPercent);
        }
        else
            $otherPercent += $percent;

        $numElements++;
    }

    if($otherPercent > 0)
    {
        $roundedOtherPercent = round($otherPercent);
        echo "Others ($roundedOtherPercent%)";
    }
}

function emailVerified($email)
{
    $db = $GLOBALS["db"];
    $select = "SELECT COUNT(address) FROM emails WHERE address = '$email' AND verified = 1";
    $statement = $db->prepare($select);
    $statement->execute();
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    $verified = $row['COUNT(address)'] > 0;

    return $verified;
}

function googleLuckyLink($text)
{
    return "<a href=\"https://www.google.com/search?q=$text&btnI\">$text</a>";
}

function mailToLink($email, $product)
{
    if(!emailVerified($email))
        return $email;

    $htmlProduct = rawurlencode($product);
    return "<a href=\"mailto:$email?subject=$htmlProduct\">$email</a>";
}

function secondsToSpan($seconds)
{
    if($seconds === 0)
        return "Once";

    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");
    $span = $dtF->diff($dtT);

    if($seconds < 60)
        return $span->format('%ss');
    else if($seconds < 3600)
        return $span->format('%im');
    else if($seconds < 86400)
        return $span->format('%hh');

    return $span->format('%ad');
}

function epochTimeToHumanReadable($time)
{
    $tz = 'Europe/London';
    $now = new DateTime("now", new DateTimeZone($tz));
    $now->settime(0, 0);

    $dt = clone $now;
    $dt->setTimestamp($time);

    $dtDateOnly = clone $dt;
    $dtDateOnly->settime(0, 0);

    $diff = $dtDateOnly->diff($now);
    $daysDiff = $diff->days;

    if($daysDiff == 0)
        return $dt->format('H:i') . " Today";
    else if($daysDiff == 1)
        return $dt->format('H:i') . " Yesterday";
    else if($daysDiff < 7)
        return $dt->format('H:i l');

    return $dt->format('H:i d-m-Y');
}

try
{
    $select = "SELECT product, COUNT(product) FROM log " .
        "WHERE time BETWEEN $fromTime AND $toTime " .
        "AND $skipDomainQueryFragment " .
        "GROUP BY product " .
        "ORDER BY COUNT(product) DESC";
    $productStatement = $db->prepare($select);
    $productStatement->execute();
    $emailCounts = array();
    $emailSpans = array();

    while($row = $productStatement->fetch(PDO::FETCH_ASSOC))
    {
        $product = $row['product'];
        echo "<a name=\"$product\"></a>";
        echo "<h2>$product</h2>";
?>

<table class="table">
<tr>
<th>Count</th>
<th>Users</th>
<th>Count per User</th>
<th colspan="2">Domains</th>
<th colspan="2">Email addresses</th>
</tr>

<?php
        $count = $row['COUNT(product)'];

        $htmlProduct = rawurlencode($product);

        $select = "SELECT email, lower(email), time FROM log " .
            "WHERE time BETWEEN $fromTime AND $toTime " .
            "AND $skipDomainQueryFragment " .
            "AND product = \"$product\" " .
            "ORDER BY time DESC";
        $emailStatement = $db->prepare($select);
        $emailStatement->execute();

        echo "<tr>";
        echo "<td>$count</td>";

        $domainCounts = array();
        $recentDomains = array();
        $recentEmails = array();
        while($emailRow = $emailStatement->fetch(PDO::FETCH_ASSOC))
        {
            $email = $emailRow['lower(email)'];
            $domain = substr(strrchr($email, "@"), 1);
            $time = $emailRow['time'];

            if(emailVerified($email))
            {
                if(!array_key_exists($domain, $domainCounts))
                    $domainCounts[$domain] = 0;

                $domainCounts[$domain]++;

                if(!in_array($domain, $recentDomains))
                    array_push($recentDomains, $domain);
            }

            if(!array_key_exists($product, $emailCounts))
                $emailCounts[$product] = array();

            if(!array_key_exists($email, $emailCounts[$product]))
                $emailCounts[$product][$email] = 0;

            $emailCounts[$product][$email]++;

            if(!in_array($email, $recentEmails))
                array_push($recentEmails, $email);

            if(!array_key_exists($product, $emailSpans))
                $emailSpans[$product] = array();

            if(!array_key_exists($email, $emailSpans[$product]))
            {
                $emailSpans[$product][$email] = array(
                    "first" => $time,
                    "last" => $time
                );
            }

            if($time < $emailSpans[$product][$email]["first"])
                $emailSpans[$product][$email]["first"] = $time;

            if($time > $emailSpans[$product][$email]["last"])
                $emailSpans[$product][$email]["last"] = $time;
        }

        arsort($domainCounts);
        arsort($emailCounts[$product]);
        $numUsers = sizeof($emailCounts[$product]);
        $countPerUser = round($count / $numUsers, 1);

        $totalSpan = 0;
        foreach($emailSpans[$product] as $element)
            $totalSpan += ($element["last"] - $element["first"]);
        $spanPerUser = secondsToSpan(round($totalSpan / $numUsers));

        echo "<td>$numUsers</td>";
        echo "<td>$countPerUser</td>";

        echo "<td>";
        echo "<strong>Recent</strong>:<br>";
        foreach(array_slice($recentDomains, 0, 5) as $element)
            echo googleLuckyLink($element) . "<br>";
        echo "</td>";

        echo "<td>";
        echo "<strong>Overall</strong>:<br>";
        summariseList($domainCounts, function($element, $percent)
        {
            echo googleLuckyLink($element) . " ($percent%)<br>";
        });
        echo "</td>";

        echo "<td>";
        echo "<strong>Recent</strong>:<br>";
        foreach(array_slice($recentEmails, 0, 5) as $element)
            echo mailToLink($element, $product) . "<br>";
        echo "</td>";

        echo "<td>";
        echo "<strong>Overall</strong>:<br>";
        summariseList($emailCounts[$product], function($element, $percent)
        {
            echo mailToLink($element, $product) . " ($percent%)<br>";
        });
        echo "</td>";
        echo "</tr>";
?>

</table>

<table class="table">
<?php
        $select = "SELECT version, os FROM log " .
            "WHERE time BETWEEN $fromTime AND $toTime " .
            "AND product = '$product'" .
            "AND $skipDomainQueryFragment";
        $versionStatement = $db->prepare($select);
        $versionStatement->execute();

        $productVersions = array();
        $totalVersions = 0;

        while($row = $versionStatement->fetch(PDO::FETCH_ASSOC))
        {
            $productVersionNumber = $row['version'];
            $osDetail = explode(" ", $row['os']);

            $productVersion = &$productVersions[$productVersionNumber];
            $productVersion['count']++;
            $totalVersions++;

            $os = &$productVersion['oses'][$osDetail[0]];
            $os['count']++;

            $versionString = $osDetail[3] . ' (' . $osDetail[1] . ')';
            if($osDetail[0] === 'linux')
                $versionString = $osDetail[2] . ' ' . $versionString;

            $os['versions'][$versionString]++;
        }

        function sortByCount($a, $b)
        {
            if(!is_array($a) && is_array($b))
                return -1;

            if(!is_array($b) && is_array($a))
                return 1;

            if($a['count'] === $b['count'])
                return 0;

            return ($a['count'] > $b['count']) ? -1 : 1;
        }

        uasort($productVersions, 'sortByCount');
        foreach($productVersions as &$os)
        {
            uasort($os['oses'], 'sortByCount');
            foreach($os['oses'] as &$osData)
                arsort($osData['versions']);
        }

        $productVersions = array_filter($productVersions, function($data) use ($totalVersions)
        {
            $percentage = ($data['count'] * 100) / $totalVersions;
            return $percentage > 1.0;
        });

        echo "<tr>\n";
        foreach($productVersions as $productVersionNumber => $data)
        {
            $versionPercentage = round(($data['count'] * 100) / $totalVersions);
            echo "<th>$productVersionNumber ($versionPercentage%)</th>\n";
        }
        echo "</tr>\n";

        echo "<tr>\n";
        foreach($productVersions as $productVersionNumber => $data)
        {
            echo "<td>\n";
            foreach($data['oses'] as $os => $osData)
            {
                $osVersionDetail = "";
                foreach($osData['versions'] as $osVersion => $osVersionCount)
                {
                    $osVersionPercentage = round(($osVersionCount * 100) / $osData['count']);

                    if($osVersionPercentage === 0.0)
                        $osVersionPercentage = '<1';

                    if(strlen($osVersionDetail) !== 0)
                        $osVersionDetail .= "\n";

                    $osVersionDetail .= "$osVersion ($osVersionPercentage%)";
                }

                $osPercentage = round(($osData['count'] * 100) / $data['count']);
                echo "<div data-toggle='tooltip' title='$osVersionDetail'>$os ($osPercentage%)</div>\n";
            }
            echo "</td>\n";
        }
        echo "</tr>\n";
?>

</table>

<table class="table">
<?php
        $select = "SELECT locale, COUNT(locale) FROM log " .
            "WHERE time BETWEEN $fromTime AND $toTime " .
            "AND product = '$product'" .
            "AND $skipDomainQueryFragment " .
            "GROUP BY locale";
        $localeStatement = $db->prepare($select);
        $localeStatement->execute();

        $locales = array();
        while($row = $localeStatement->fetch(PDO::FETCH_ASSOC))
            $locales[$row['locale']] = intval($row['COUNT(locale)']);

        arsort($locales);

        $totalRows = array_sum($locales);
        $locales = array_filter($locales, function($count) use ($totalRows)
        {
            $percentage = ($count * 100) / $totalRows;
            return $percentage > 1.0;
        });

        $totalRows = array_sum($locales);

        echo "<tr>\n";
        foreach(array_keys($locales) as $locale)
            echo "<th>$locale</th>";
        echo "</tr>\n";

        echo "<tr>\n";
        foreach($locales as $locale => $count)
        {
            $percent = ($count * 100) / $totalRows;
            $roundedPercent = round($percent);

            echo "<td>$roundedPercent%</td>";
        }
        echo "</tr>\n";
?>
</table>

<table class="table">
<tr>
<th>Email Address</th>
<th>Count</th>
<th>Span</th>
<th>Per Day</th>
<th>Locale</th>
<th>Version</th>
<th>Time and Date</th>
<th>IP Address</th>
</tr>

<?php
        $select = "SELECT ip, email, lower(email), locale, version, os, time FROM log " .
            "WHERE time BETWEEN $fromTime AND $toTime " .
            "AND product = '$product'" .
            "AND $skipDomainQueryFragment " .
            "ORDER BY time DESC";
        $statement = $db->prepare($select);
        $statement->execute();

        while($row = $statement->fetch(PDO::FETCH_ASSOC))
        {
            $ip = $row['ip'];
            $ipClass = str_replace(".", "", $ip);

            $email = $row['lower(email)'];
            $locale = $row['locale'];
            $version = $row['version'];
            $os = $row['os'];
            $time = $row['time'];
            $count = $emailCounts[$product][$email];

            echo "<tr>";

            echo "<td>" . mailToLink($email, $product) . "</td>";

            if($count >= 15)
                echo "<td class=\"highlight1\">";
            else if($count >= 10)
                echo "<td class=\"highlight2\">";
            else if($count >= 5)
                echo "<td class=\"highlight3\">";
            else
                echo "<td>";

            echo "$count</td>";

            $secondsSpan = $emailSpans[$product][$email]["last"] -
                $emailSpans[$product][$email]["first"];
            $span = secondsToSpan($secondsSpan);
            echo "<td>$span</td>";

            $daysSpan = $secondsSpan / 86400;
            if($daysSpan > 1)
            {
                $daysSpan = max($daysSpan, 1);
                $usesPerDay = round($count / $daysSpan, 1);
            }
            else
                $usesPerDay = "";

            echo "<td>$usesPerDay</td>";

            echo "<td>$locale</td>";

            if(strlen($os) > 0)
                echo "<td>$version ($os)</td>";
            else
                echo "<td>$version</td>";

            $localTime = epochTimeToHumanReadable($time);

            echo "<td>$localTime</td>";
            echo "<td><a href=\"#\" onClick=\"lookupIp('$ip', '$ipClass'); return false;\">" .
                "<div class=\"$ipClass\">$ip</div></a></td>";
            echo "</tr>";
        }
?>

</table>

<?php
    }
}
catch(Exception $e)
{
    echo $e->getMessage();
}
?>

</div>
</body>
</html>
<?php

?>
