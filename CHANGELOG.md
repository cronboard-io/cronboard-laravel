# Changelog

All notable changes to `cronboard-io/cronboard-laravel` are documented in this file

## 0.1.1 - 2019-11-30

- fixed an issue where `Collection::keyBy` would not be available as proxy
- fixed an issue where command discovery from the scheduler would fail if it found a Job instance instead of class string
- fixed an issue where the console output stream could not be registered with Cronboard
- added integration tests for scheduled tasks and their lifecycle requests

## 0.1.0 - 2019-11-24

- initial release
