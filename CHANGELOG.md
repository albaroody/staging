# Changelog

All notable changes to this project will be documented in this file.

## [0.1.6] - 2025-05-07

### Added
- Documentation for hasMany relationships with automatic child saving
- Required database columns: `parent_model`, `relationship_type`, `sort_order` for hasMany support
- Index on `[parent_staging_id, parent_model]` for better query performance

### Changed
- Updated README to accurately reflect all implemented features
- Improved automatic child saving examples and documentation
- Cleaned up config file (removed unused config options)

### Fixed
- Ensured backward compatibility - kept `model_id` and `expires_at` columns (marked as reserved for future use)
- Updated controller examples in README

## [0.1.5] - Previous releases

See git history for previous changelog entries.
