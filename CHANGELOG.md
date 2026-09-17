# Changelog

All notable changes to `laravel-atompark-sms-channel` will be documented in this file.

## 0.2.0 - 2026-09-17

### Changed

- **Breaking:** `AtomParkChannel` now inspects the AtomPark response instead of discarding it. AtomPark reports failures as an `{"error": ..., "code": ...}` body under HTTP `200`, so sends that never left the gateway previously succeeded silently. A failed send now logs to the `error` channel, with the recipient number masked, and throws `CouldNotSendNotification`. Queued notifications that used to report success will now fail and retry.

### Added

- `Exceptions\CouldNotSendNotification`, carrying the AtomPark `errorCode` and `errorMessage`.

## 0.1.0 - 2026-03-11

- Initial release of the AtomPark SMS notification channel
- Adds `AtomParkChannel` for sending SMS via AtomPark
- Provides `Sms` value object and `AtomParkClient` HTTP client
