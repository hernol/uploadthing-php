# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - 2026-02-19

### Improved
- Upload polling now retries up to 5 times with 1-second delays when the status is "still working", improving reliability for slower uploads

## [2.0.0] - 2026-02-19

### Added
- MIME type detection utility in `AbstractResource` with extension-based and `finfo` fallback
- Webhook event models (`FileDeletedEvent`, `FileUpdatedEvent`, `FileUploadedEvent`, `UploadCompletedEvent`, `UploadFailedEvent`, `UploadStartedEvent`, `WebhookCreatedEvent`, `WebhookDeletedEvent`, `WebhookUpdatedEvent`, `GenericWebhookEvent`)
- `WebhookHandler` and `WebhookVerifier` utilities for processing incoming webhooks
- Environment-based configuration via `UPLOADTHING_API_KEY`, `UPLOADTHING_BASE_URL`, `UPLOADTHING_API_VERSION`, `UPLOADTHING_TIMEOUT`

### Changed
- **BREAKING**: Replaced custom `Client` / `Config` architecture with direct Guzzle-based `AbstractResource`
- **BREAKING**: Resources are now instantiated directly (`new Uploads()`) instead of through a client object
- **BREAKING**: Removed the `Files` resource — file management is no longer exposed as a standalone resource
- **BREAKING**: Simplified `Uploads` resource to a single `uploadFile()` method with S3 POST and poll-based finalization
- `AbstractResource` now reads configuration from environment variables with Laravel `env()` helper support
- Updated documentation (`README.md`, `docs/USAGE.md`, `docs/LARAVEL.md`) to reflect the simplified API

### Removed
- `Client` class and `Config` class
- Custom HTTP layer (`HttpClientInterface`, `GuzzleHttpClient`)
- All middleware (`RetryMiddleware`, `RateLimitMiddleware`, `LoggingMiddleware`)
- `Files` resource (CRUD operations for files)
- `UploadHelper` utility
- Request/response models (`CreateUploadRequest`, `CreateWebhookRequest`, `RenameFileRequest`, `UpdateWebhookRequest`, `FileListResponse`, `WebhookListResponse`, `PaginationMeta`, `UploadSession`)

## [1.2.1] - 2025-12-15

### Added
- `callbackUrl` and `callbackSlug` support in Uploads and Config classes

### Fixed
- Serializer missing properties on model hydration

## [1.1.0] - 2025-11-05

### Changed
- Package author and naming updated
- `File` model `updatedAt` field is now optional

### Fixed
- Resources now return `File` model instances instead of raw keys
- Return full item data instead of just the key from upload responses

## [1.0.0] - 2025-10-20

### Added
- UploadThing v6 API support with presigned URL upload flow
- S3 presigned upload and poll-based finalization

## [0.1.0] - 2025-10-09

### Added
- Initial project structure and scaffolding
- Core HTTP client implementation with PSR-18 compliance
- Authentication system with API key support
- Retry middleware with exponential backoff
- Rate limiting and logging middleware
- Files resource with CRUD operations
- Uploads resource for file upload sessions
- Webhooks resource for webhook management
- Comprehensive error handling with typed exceptions
- Serializer utility for JSON handling
- Multipart builder for file uploads
- Laravel and Symfony integration guides
- CI/CD pipeline with GitHub Actions
- Development tooling (PHPStan, Psalm, PHP-CS-Fixer, Rector)

### Security
- Secure API key handling
- Sanitized logging to prevent secret exposure

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for information on contributing to this project.

## Support

For support and questions:

- **Documentation**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/hernol/uploadthing-php/issues)
- **Discussions**: [GitHub Discussions](https://github.com/hernol/uploadthing-php/discussions)

---

*This changelog follows the [Keep a Changelog](https://keepachangelog.com/) format.*
