# SqirrelMail 1.5.2

A version of SquirrelMail for PHP 7.0 and above with additional changes for compatibility or security reasons.


This version contains the following changes:
  * Legacy constructors replaced with `__construct`.
  * While/List/Each are now `ForEach`.
  * Instances of `mt_rand` are now cryptographically secure, using `random_int`.
  * Message IDs are generated differently.
    * The ID is now a Version 5 UUID based off 64 cryptographically secure random bytes.
    * The domain now matches the value of the username variable, if it contains an "@". Otherwise, it falls back to the SERVER_NAME variable like normal.
  * Instances of `SizeOf` are now `StrLen` because PHP is not C.
  * Instances of `create_function` are now inline functions.
  * The `X-Frame-Options: SAMEORIGIN` header has been replaced with CSP's `frame-ancestors` header. This is set based on the `provider_uri` preference, and accepts any http or https domains or subdomains that match.
    * A new variable in the config will be added at a later point.

Additionally, some minor fixes that would cause warnings or strange failures have also been resolved.
Changes to the official source code are tracked in the `trunk` branch and merged with `master` as soon as possible. I'm looking into a way to automate this process.
