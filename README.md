👷‍♀️ Nothing to see here, please move on

## Command ideas

### Create

- [ ] Command
- [ ] Controller
- [ ] Event
- [ ] EventListener
- [ ] Exception
- [ ] Job
- [ ] Middleware
- [ ] Entity
- [ ] Mapper
- [ ] Notifier
- [ ] Activity

### Helpers

- [ ] AppInfo updater (interactive)
- [ ] Version management
- [ ] Changelog generator
- [ ] Release
  - Interactive (version, changelog, build, release, publish) 

### Skeleton

- [ ] Update CI jobs from skeleton (also done by .github repo action)
- [ ] Pull in frontend
- [ ] Update 


## Config file in app

- [ ] label to changelog mapping
- [ ] 

## Configuration

The configuration file in `$HOME/.nextcloud/ndc` will be read and used for global configuration of the command line tool.
```php 
<?php
return [
	'github_token' => 'ghp_mysecretgithubtoken'
];
```
