# Changelog

All notable changes to `shipping-sdk-laravel` will be documented in this file.

## v1.1.0 - 2026-01-30

### What's Changed

* Bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 by @dependabot[bot] in https://github.com/DinasJp/shipping-sdk-laravel/pull/3
* Bump actions/checkout from 5 to 6 by @dependabot[bot] in https://github.com/DinasJp/shipping-sdk-laravel/pull/2
* Updated shipping sdk bindings to v1.1.0

**Full Changelog**: https://github.com/DinasJp/shipping-sdk-laravel/compare/v1.0.0...v1.1.0

## [v1.1.0]

### Added

- **Car Management Methods**:
  
  - `holdCars(array $items, ?array $shipDateLimit = null)` - Hold cars from shipping with optional date limit
  - `releaseCars(array $items)` - Release cars for shipping
  - `withholdCars(array $items, ?string $reason = null)` - Withhold cars upon arrival with optional reason
  - `grantCars(array $items)` - Grant cars (clear withhold status)
  - `setYardEta(array $items)` - Set yard ETA for cars (accepts array of items with chassis and eta keys)
  
- **Photo Management**:
  
  - `carPhotos()` - Get CarPhotosApi instance for direct API access
  - `getCarPhotos(array $params = [])` - Get car photos with filters
  - `storeCarPhotos(array $photos)` - Store car photos from URLs
  - `storeCarPhotoFiles(array $photos)` - Store car photos from file uploads
  
- **Document Management**:
  
  - `carDocuments()` - Get CarDocumentsApi instance for direct API access
  - `storeCarDocuments(array $documents)` - Store car documents from URLs
  - `storeCarDocumentFiles(array $documents)` - Store car documents from file uploads
  

### Changed

- **getCars()** method now supports additional filters:
  
  - `port_code` - Filter by port code
  - `vehicle_state` - Filter by vehicle state
  - `vehicle_type` - Filter by vehicle type
  - `docs` - Filter by documents presence
  - `price_terms` - Filter by price terms
  
- Refactored photo and document operations to use dedicated API classes (`CarPhotosApi`, `CarDocumentsApi`)
  
- Car management methods now accept simple arrays as first parameter instead of associative arrays for better usability
  
- Updated documentation with comprehensive examples for all operations
  
- Enhanced facade with proper type hints for all methods
  

### Fixed

- Webhook methods now use correct `string $name` parameter instead of `int $id`
- Car management methods signatures simplified for easier usage
