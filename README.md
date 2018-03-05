# Disputor

Generate dispute reports of LicenseWP licenses

## Running Disputor

From CLI, run the following:

    $ php disputor.php DATABASE LICENSE_KEY

So for example:

    $ php disputor.php licensewp EF34-5AAB-328C-414A

The dispute document will be written to the output/ directory.

## Writable directories

Disputor needs a `tmp` and `output` directory that are both writable by the script.

## Database credentials

Disputor need access to your database. Create a file `db.php` in the root folder and add the following code.

```
<?php
return ['username' => 'root', 'password' => 'root'];
```
