<?php
/**
 * LEGACY CODE — DO NOT DEPLOY.
 *
 * This file is part of the "before" snapshot of the refactoring case study.
 * It intentionally reproduces the patterns found in a real 2010-era PHP
 * codebase: a global mysqli handle, credentials hardcoded in source, no
 * error handling, no connection pooling, no charset configuration.
 *
 * Every problem here is catalogued in docs/FINDINGS.md and fixed in the
 * Laravel version under app-laravel/.
 */

// Credentials in source control. Same file is deployed to prod with the
// values edited by hand over FTP.
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'cms';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    // Leaks internal details to the browser.
    die('DB connection failed: ' . mysqli_connect_error());
}

// Note the absence of mysqli_set_charset() — this is what makes some
// escaping approaches unreliable on legacy MySQL configurations.

function q($sql)
{
    global $conn;
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        // Full SQL and driver error printed to the page.
        die('Query failed: ' . $sql . ' — ' . mysqli_error($conn));
    }
    return $res;
}
