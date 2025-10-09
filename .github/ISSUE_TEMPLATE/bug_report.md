---
name: Bug Report
about: Create a report to help us improve
title: '[BUG] '
labels: bug
assignees: ''
---

## Bug Description

A clear and concise description of what the bug is.

## Steps to Reproduce

1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior

A clear and concise description of what you expected to happen.

## Actual Behavior

A clear and concise description of what actually happened.

## Environment

- PHP Version: [e.g. 8.1, 8.2, 8.3]
- Package Version: [e.g. 1.0.0]
- Operating System: [e.g. Ubuntu 20.04, macOS 12.0, Windows 10]
- Framework (if applicable): [e.g. Laravel 10, Symfony 6]

## Code Example

```php
// Minimal code example that reproduces the issue
use UploadThing\Client;
use UploadThing\Config;

$config = Config::create()->withApiKey('your-api-key');
$client = Client::create($config);

// Your code here
```

## Error Messages/Logs

```
Paste any error messages or logs here
```

## Additional Context

Add any other context about the problem here.

## Checklist

- [ ] I have searched existing issues to ensure this is not a duplicate
- [ ] I have provided all the required information
- [ ] I have tested this with the latest version
- [ ] I have included a minimal code example
