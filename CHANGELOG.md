# Change Log

Synergy Wholesale WHMCS SSL Module

## 3.0.6 [Updated 24/11/2022]

### Fixed
- Compatibility issues with WHMCS 8.6
- Returning a list of approver emails in configuration step
- Properly setting certificate's remote ID when renewing certificate 

### Removed
- The error handler override has been removed, restoring WHMCS' native error handling when using this module.


## 3.0.5 [Updated 03/12/2020]

### Fixed
- Compatibility issues with WHMCS 8


## 3.0.4 [Updated 23/08/2019]
### Added
- Added Private key field when configuring SSL Certificates
- Added Business Category dropdown for Comodo EV SSL Certificates

### Fixed
- Fixed issue for Private key not saving correctly

## 3.0.3 [Updated 05/03/2019]
### Added
- Fixed a bug with "Resend Configuration Email" button not working when Certificate order hadn't been placed with Synergy Wholesale.

## 3.0.1 [Updated 19/10/2018]
### Fixed
- Fixed a bug with connecting to the API

## 3.0 [Updated 20/09/2018]
### Added
- Support for Comodo SSL Certificates
- Displays CA Bundles

### Changed
- Validation section has been refined

## 2.1 [Updated 12/01/2018]
### Added
- Full support for WHMCS 7.x
- Add function to reissue SSL certificate
- Add CSR decode page

### Changed
- Improve CSR generation

### Fixed
- Various bugs

## 1.0 [Updated 05/10/2012]
### Added
- Initial Release
