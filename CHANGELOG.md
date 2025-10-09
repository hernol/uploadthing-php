# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project structure and scaffolding
- Core HTTP client implementation with PSR-18 compliance
- Authentication system with API key support
- Retry middleware with exponential backoff
- Rate limiting middleware (placeholder)
- Logging middleware with PSR-3 integration
- Files resource with CRUD operations
- Uploads resource for file upload sessions
- Webhooks resource for webhook management
- Comprehensive error handling with typed exceptions
- Serializer utility for JSON handling
- Multipart builder for file uploads
- Complete documentation suite
- Laravel and Symfony integration guides
- CI/CD pipeline with GitHub Actions
- Development tooling (PHPStan, Psalm, PHP-CS-Fixer, Rector)
- Test structure and configuration

### Changed
- Nothing yet

### Deprecated
- Nothing yet

### Removed
- Nothing yet

### Fixed
- Nothing yet

### Security
- Secure API key handling
- Sanitized logging to prevent secret exposure
- Input validation and error handling

## [0.1.0] - TBD

### Added
- Initial release
- Basic file upload functionality
- API client for UploadThing REST API
- Framework integrations for Laravel and Symfony
- Comprehensive documentation
- Test coverage

---

## Release Notes

### Version 0.1.0 (Planned)

This will be the initial release of the UploadThing PHP Client, providing:

- **Core Features**: Complete API client for UploadThing REST API
- **File Management**: Upload, list, get, delete, and rename files
- **Upload Sessions**: Create and manage file upload sessions
- **Webhook Support**: Configure and manage webhooks
- **Error Handling**: Comprehensive exception handling with typed errors
- **Framework Integration**: Ready-to-use Laravel and Symfony integrations
- **Documentation**: Complete documentation with examples and guides
- **Testing**: Unit and integration test coverage
- **CI/CD**: Automated testing and code quality checks

### Future Releases

#### Version 0.2.0 (Planned)
- Enhanced file upload features
- Progress tracking for uploads
- Chunked upload support
- Presigned URL support

#### Version 0.3.0 (Planned)
- Advanced webhook features
- Webhook signature verification
- Event filtering and routing
- Webhook retry logic

#### Version 1.0.0 (Planned)
- Stable API
- Performance optimizations
- Advanced error handling
- Comprehensive test coverage

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for information on contributing to this project.

## Support

For support and questions:

- **Documentation**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/uploadthing/uploadthing-php/issues)
- **Discussions**: [GitHub Discussions](https://github.com/uploadthing/uploadthing-php/discussions)

---

*This changelog follows the [Keep a Changelog](https://keepachangelog.com/) format.*
